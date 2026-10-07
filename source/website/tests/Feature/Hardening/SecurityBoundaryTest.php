<?php

namespace Tests\Feature\Hardening;

use App\Models\Event\Booking;
use App\Models\Event\EventImage;
use App\Models\Event\IssuedTicket;
use App\Models\Event\Slot;
use App\Services\Tickets\TicketIssuanceService;
use Illuminate\Support\Facades\DB;
use Tests\RefreshLegacyDatabase;
use Tests\TestCase;

class SecurityBoundaryTest extends TestCase
{
    use RefreshLegacyDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_http_migrate_route_is_gone_and_cron_routes_need_a_token(): void
    {
        $this->getJson('/migrate')->assertNotFound();
        $this->getJson('/send-ticket')->assertNotFound();
        config(['booktkit.cron_http_token' => 'expected-token']);
        $this->getJson('/send-ticket?token=wrong')->assertNotFound();
    }

    public function test_confirmation_page_does_not_leak_another_buyers_tickets(): void
    {
        $event = $this->event($this->organizer());
        $booking = $this->booking($event);

        $this->get(route('event_booking.complete', ['id' => $event->id, 'booking_id' => $booking->id]))->assertRedirect(route('customer.login'));
        $this->assertSame(0, IssuedTicket::where('booking_id', $booking->id)->count(), 'Viewing must not issue tickets for an unauthorised visitor.');

        $this->actingAs($this->customer(), 'customer')
            ->get(route('event_booking.complete', ['id' => $event->id, 'booking_id' => $booking->id]))->assertForbidden();
    }

    public function test_paid_online_event_cannot_be_booked_free_by_posting_a_free_price_type(): void
    {
        $event = $this->event($this->organizer(), ['event_type' => 'online', 'meeting_url' => 'https://meet.example/x']);
        $this->ticket($event, ['pricing_type' => 'normal', 'price' => 499]);

        $this->post(route('check-out2'), ['event_id' => $event->id, 'pricing_type' => 'free', 'quantity' => 2]);
        $this->assertEquals(998.0, (float) session('sub_total'), 'Server price must be used for the online cart.');

        $this->post(route('ticket.booking', $event->id), ['event' => json_encode(['id' => $event->id]), 'total' => 0, 'fname' => 'A', 'email' => 'a@example.invalid'])
            ->assertSessionHas('error', 'Please select a payment method.');
        $this->assertSame(0, Booking::where('event_id', $event->id)->count());
    }

    public function test_free_booking_requires_the_server_to_price_the_cart_at_zero(): void
    {
        $event = $this->event($this->organizer());
        $paid = $this->ticket($event, ['price' => 300]);
        session(['event' => $event, 'selTickets' => [['ticket_id' => $paid->id, 'qty' => 1, 'name' => 'General', 'price' => 0]], 'sub_total' => 0, 'total' => 0]);

        $this->post(route('ticket.booking', $event->id), ['total' => 0, 'fname' => 'A', 'email' => 'a@example.invalid', 'gateway' => 'stripe'])
            ->assertSessionHas('message', 'This payment method is not available.');
        $this->assertSame(0, Booking::where('event_id', $event->id)->count());
    }

    public function test_legacy_scanner_endpoints_go_through_the_unified_engine(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer);
        $booking = $this->booking($event, ['variation' => json_encode([['ticket_id' => $this->ticket($event)->id, 'qty' => 1, 'name' => 'General', 'unique_id' => 'u1']])]);
        $token = app(TicketIssuanceService::class)->ensureForBooking($booking)[0]['token'];

        $this->postJson('/organizer/check-qrcode', ['booking_id' => $token])->assertStatus(401);

        $this->actingAs($organizer, 'organizer')->postJson('/organizer/check-qrcode', ['booking_id' => $booking->booking_id . '__u1'])
            ->assertJson(['alert_type' => 'error', 'reason_code' => 'legacy_qr']);
        $this->assertNull($booking->fresh()->scanned_tickets);

        $this->actingAs($organizer, 'organizer')->postJson('/organizer/check-qrcode', ['booking_id' => $token])->assertJson(['alert_type' => 'success']);
        $this->assertNotNull(IssuedTicket::where('booking_id', $booking->id)->value('checked_in_at'));
        $this->assertNull($booking->fresh()->scanned_tickets, 'Legacy column is no longer written.');

        $other = $this->organizer();
        $this->actingAs($other, 'organizer')->postJson('/organizer/check-qrcode', ['booking_id' => $token])->assertJson(['reason_code' => 'forbidden']);
    }

    public function test_admin_web_scanner_requires_admin_login(): void
    {
        $this->post('/admin/check-qrcode', ['booking_id' => 'btk_x'])->assertRedirect();
        $this->assertDatabaseCount('access_scans', 0);
    }

    public function test_staff_cannot_reach_money_routes_or_sell_from_the_organizer_pos(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, ['event_type' => 'box_office', 'box_office_enabled' => 1]);
        $staff = $this->staff($organizer, ['payments.view', 'box_office.sell', 'ai.use'], $event);

        $this->actingAsStaff($staff)->post(route('organizer.payments.preference'), ['preference' => 'razorpay_split'])->assertForbidden();
        $this->actingAsStaff($staff)->post(route('organizer.payouts.kyc.save'), [])->assertForbidden();
        $this->actingAsStaff($staff)->post(route('organizer.withdraw.send-request'), [])->assertForbidden();
        $this->actingAsStaff($staff)->post(route('organizer.boxoffice.store'), [])->assertForbidden();
        $this->actingAsStaff($staff)->get(route('organizer.boxoffice.index'))->assertRedirect(route('staff.boxoffice.index'));
        $this->actingAsStaff($staff)->post(route('organizer.update_password'), [])->assertForbidden();
    }

    public function test_staff_are_limited_to_their_assigned_events(): void
    {
        $organizer = $this->organizer();
        $assigned = $this->event($organizer);
        $other = $this->event($organizer);
        $staff = $this->staff($organizer, ['events.view', 'events.manage'], $assigned);

        $this->actingAsStaff($staff)->post(route('organizer.event_management.event.event_status', $other->id), ['status' => 0])->assertForbidden();
        $this->actingAsStaff($staff)->post(route('organizer.event_management.event.event_status', $assigned->id), ['status' => 0])->assertRedirect();
        $this->assertSame('0', (string) $assigned->fresh()->status);
    }

    public function test_team_manager_cannot_grant_permissions_they_do_not_hold(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer);
        $manager = $this->staff($organizer, ['team.manage', 'tickets.scan'], $event);

        $this->actingAsStaff($manager)->post(route('organizer.staff.store'), [
            'name' => 'New', 'username' => 'new-' . uniqid(), 'password' => 'secret-pass', 'role' => 'ticket_checker', 'department' => 'admissions',
            'permissions' => ['tickets.scan', 'access.override'], 'event_id' => $event->id,
        ])->assertForbidden();
        $this->assertDatabaseMissing('organizer_staff', ['name' => 'New']);
    }

    public function test_ticket_checker_can_be_assigned_to_a_venue_event_without_box_office(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, ['box_office_enabled' => 0]);
        $this->actingAs($organizer, 'organizer')->post(route('organizer.staff.store'), [
            'name' => 'Gate One', 'username' => 'gate-' . uniqid(), 'password' => 'secret-pass', 'role' => 'ticket_checker', 'department' => 'admissions', 'event_id' => $event->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('organizer_staff', ['name' => 'Gate One', 'organizer_id' => $organizer->id]);
    }

    public function test_organizer_cannot_touch_another_organizers_tickets_seat_maps_or_gallery(): void
    {
        $owner = $this->organizer();
        $event = $this->event($owner);
        $ticket = $this->ticket($event);
        $slot = Slot::create(['event_id' => $event->id, 'ticket_id' => $ticket->id, 'slot_unique_id' => 777, 'type' => 1, 'name' => 'A', 'price' => 100, 'number_of_seat' => 1, 'pos_x' => 0, 'pos_y' => 0, 'width' => 10, 'height' => 10, 'round' => 0, 'rotate' => 0, 'background_color' => '#fff', 'border_color' => '#000', 'font_size' => 12]);
        $image = EventImage::forceCreate(['event_id' => $event->id, 'image' => 'x.jpg']);
        EventImage::forceCreate(['event_id' => $event->id, 'image' => 'y.jpg']);

        $intruder = $this->organizer();
        $this->actingAs($intruder, 'organizer');
        $this->postJson(route('organizer.ticket_management.delete_ticket'), ['id' => $ticket->id])->assertNotFound();
        $this->postJson(route('organizer.event_management.seat_mapping.slot.delete'), ['slot_id' => $slot->id])->assertNotFound();
        $this->postJson(route('organizer.event.imgdbrmv'), ['fileid' => $image->id])->assertNotFound();
        $this->postJson(route('organizer.event.imagermv'), ['fileid' => $image->id])->assertNotFound();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseHas('slots', ['id' => $slot->id]);
        $this->assertDatabaseHas('event_images', ['id' => $image->id]);
    }

    public function test_ticket_settings_cannot_publish_or_reassign_an_event(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, ['status' => '0']);
        $this->actingAs($organizer, 'organizer')->postJson(route('organizer.event_management.update_ticket_setting'), [
            'event_id' => $event->id, 'status' => 1, 'organizer_id' => 999, 'event_type' => 'online', 'instructions' => '<p>Bring ID</p>',
        ])->assertOk();
        $fresh = $event->fresh();
        $this->assertSame('0', (string) $fresh->status);
        $this->assertSame($organizer->id, (int) $fresh->organizer_id);
        $this->assertSame('venue', $fresh->event_type);
    }

    public function test_paid_bookings_events_and_sold_tickets_are_never_hard_deleted(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer);
        $ticket = $this->ticket($event);
        $booking = $this->booking($event, ['variation' => json_encode([['ticket_id' => $ticket->id, 'qty' => 1, 'name' => 'General', 'unique_id' => 'z']])]);
        $this->actingAs($organizer, 'organizer');

        $this->post(route('organizer.event_booking.delete', $booking->id))->assertRedirect();
        $this->post(route('organizer.event_booking.bulk_delete'), ['ids' => [$booking->id]]);
        $this->post(route('organizer.ticket_management.delete_ticket'), ['id' => $ticket->id])->assertRedirect();
        $this->post(route('organizer.event_management.delete_event', $event->id))->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseHas('events', ['id' => $event->id]);

        $foreign = $this->booking($this->event($this->organizer()), ['paymentStatus' => 'pending', 'gatewayType' => 'offline']);
        $this->post(route('organizer.event_booking.bulk_delete'), ['ids' => [$foreign->id]]);
        $this->assertDatabaseHas('bookings', ['id' => $foreign->id]);
    }

    public function test_razorpay_bookings_cannot_be_rejected_by_hand_and_rejection_revokes_tickets(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer);
        $online = $this->booking($event, ['booking_source' => 'box_office']);
        $this->actingAs($organizer, 'organizer')->post(route('organizer.event_booking.update_payment_status', $online->id), ['payment_status' => 'rejected']);
        $this->assertSame('completed', $online->fresh()->paymentStatus);

        $offline = $this->booking($event, ['gatewayType' => 'offline', 'paymentStatus' => 'completed', 'email' => '']);
        app(TicketIssuanceService::class)->ensureForBooking($offline);
        $this->actingAs($organizer, 'organizer')->post(route('organizer.event_booking.update_payment_status', $offline->id), ['payment_status' => 'rejected']);
        $this->assertSame('rejected', $offline->fresh()->paymentStatus);
        $this->assertSame('revoked', IssuedTicket::where('booking_id', $offline->id)->value('status'));
    }

    public function test_admin_sub_role_without_permission_cannot_open_workforce_or_box_office_settings(): void
    {
        $roleId = DB::table('role_permissions')->insertGetId(['name' => 'Support only', 'permissions' => json_encode(['Support Ticket'])]);
        $admin = $this->admin();
        $admin->role_id = $roleId; $admin->save();
        $this->actingAs($admin, 'admin')->from('/admin/dashboard')->get(route('admin.organizer_workforce.index'))->assertRedirect('/admin/dashboard');
        $this->actingAs($admin, 'admin')->from('/admin/dashboard')->get(route('admin.boxoffice.settings'))->assertRedirect('/admin/dashboard');
    }
}
