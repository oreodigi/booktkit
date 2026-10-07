<?php

namespace Tests\Feature\Hardening;

use App\Models\BoxOfficeSale;
use App\Models\Event\Booking;
use App\Models\Event\IssuedTicket;
use App\Models\Event\Slot;
use App\Models\Event\SlotSeats;
use App\Models\Organizer;
use App\Services\Payments\AuthoritativeTicketPricingService;
use App\Services\Payments\CouponService;
use App\Services\Payments\PlatformFeeCalculator;
use App\Models\Payments\PaymentFeeRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\RefreshLegacyDatabase;
use Tests\TestCase;

class PosAndPricingTest extends TestCase
{
    use RefreshLegacyDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_per_ticket_fee_is_multiplied_by_quantity_and_free_orders_have_no_fee(): void
    {
        $rule = new PaymentFeeRule(['percentage' => 0, 'fixed_amount' => 0, 'per_ticket_amount' => 1000, 'fee_bearer' => 'additional']);
        $fees = app(PlatformFeeCalculator::class);
        $this->assertSame(3000, $fees->calculateRule(30000, $rule, null, 3)['platform_fee']);
        $this->assertSame(33000, $fees->calculateRule(30000, $rule, null, 3)['customer_total']);
        $this->assertSame(0, $fees->calculateRule(0, $rule, null, 2)['platform_fee']);
    }

    private function posEvent(Organizer $organizer): array
    {
        $event = $this->event($organizer, ['event_type' => 'box_office', 'box_office_enabled' => 1]);
        $location = DB::table('box_office_locations')->insertGetId(['event_id' => $event->id, 'name' => 'Gate counter', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $ticket = $this->ticket($event, ['price' => 200, 'ticket_available' => 10]);
        return [$event, $location, $ticket];
    }

    private function sale(array $overrides = []): array
    {
        return array_merge(['sale_uuid' => (string) Str::uuid(), 'customer_name' => 'Walk In', 'customer_phone' => '9000000000', 'customer_email' => 'walk@example.invalid',
            'payment_method' => 'cash'], $overrides);
    }

    public function test_pos_respects_payment_method_and_identity_settings(): void
    {
        $organizer = $this->organizer();
        [$event, $location, $ticket] = $this->posEvent($organizer);
        DB::table('box_office_settings')->insert(['organizer_id' => $organizer->id, 'allow_cash' => 0, 'allow_upi' => 1, 'allow_card' => 1, 'allow_other' => 0, 'allow_aadhaar' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $items = [['ticket_id' => $ticket->id, 'quantity' => 1]];
        $this->actingAs($organizer, 'organizer');

        $this->post(route('organizer.boxoffice.store'), $this->sale(['event_id' => $event->id, 'location_id' => $location, 'items' => $items]))->assertSessionHasErrors('payment_method');
        $this->post(route('organizer.boxoffice.store'), $this->sale(['event_id' => $event->id, 'location_id' => $location, 'items' => $items, 'payment_method' => 'upi', 'aadhaar_number' => '123412341234']))->assertSessionHasErrors('aadhaar_number');
        $this->post(route('organizer.boxoffice.store'), $this->sale(['event_id' => $event->id, 'location_id' => $location, 'items' => $items, 'payment_method' => 'upi']))->assertRedirect();
        $this->assertSame(1, BoxOfficeSale::where('event_id', $event->id)->count());
    }

    public function test_staff_sale_from_the_organizer_workspace_is_attributed_and_needs_a_shift(): void
    {
        $organizer = $this->organizer();
        [$event, $location, $ticket] = $this->posEvent($organizer);
        $staff = $this->staff($organizer, ['box_office.sell'], null, ['role' => 'cashier', 'department' => 'sales']);
        $staff->assignments()->create(['event_id' => $event->id, 'box_office_location_id' => $location]);
        $payload = $this->sale(['event_id' => $event->id, 'location_id' => $location, 'items' => [['ticket_id' => $ticket->id, 'quantity' => 1]]]);

        $this->actingAsStaff($staff)->post(route('organizer.boxoffice.store'), $payload)->assertSessionHasErrors('shift');
        DB::table('box_office_shifts')->insert(['organizer_id' => $organizer->id, 'event_id' => $event->id, 'location_id' => $location, 'staff_id' => $staff->id, 'status' => 'open', 'opening_cash' => 0, 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAsStaff($staff)->post(route('organizer.boxoffice.store'), $payload)->assertRedirect();
        $this->assertSame($staff->id, (int) BoxOfficeSale::where('event_id', $event->id)->value('staff_id'));
    }

    public function test_void_returns_stock_and_cancels_tickets(): void
    {
        $organizer = $this->organizer();
        [$event, $location, $ticket] = $this->posEvent($organizer);
        $this->actingAs($organizer, 'organizer')->post(route('organizer.boxoffice.store'),
            $this->sale(['event_id' => $event->id, 'location_id' => $location, 'items' => [['ticket_id' => $ticket->id, 'quantity' => 3]]]))->assertRedirect();
        $sale = BoxOfficeSale::where('event_id', $event->id)->firstOrFail();
        $this->assertSame(7, (int) $ticket->fresh()->ticket_available);

        $this->post(route('organizer.boxoffice.void.request', $sale->id), ['reason' => 'Wrong ticket type'])->assertRedirect();
        $this->post(route('organizer.boxoffice.void.approve', $sale->id))->assertRedirect();

        $this->assertSame('voided', $sale->fresh()->status);
        $this->assertSame(10, (int) $ticket->fresh()->ticket_available, 'Voiding must return stock.');
        $this->assertSame(0, IssuedTicket::where('booking_id', $sale->booking_id)->where('status', 'active')->count(), 'Voided tickets must not admit.');
        $this->assertSame(3, DB::table('box_office_ledger_entries')->where('sale_id', $sale->id)->where('entry_type', 'like', '%reversal')->count());
    }

    public function test_seats_are_priced_by_the_server_and_cannot_be_sold_twice(): void
    {
        $event = $this->event($this->organizer());
        $ticket = $this->ticket($event, ['price' => 100]);
        $slot = Slot::create(['event_id' => $event->id, 'ticket_id' => $ticket->id, 'slot_unique_id' => 4242, 'type' => 1, 'name' => 'Row A', 'price' => 900,
            'number_of_seat' => 2, 'pos_x' => 0, 'pos_y' => 0, 'width' => 10, 'height' => 10, 'round' => 0, 'rotate' => 0, 'background_color' => '#fff', 'border_color' => '#000', 'font_size' => 12]);
        $seat = SlotSeats::create(['slot_id' => $slot->id, 'name' => 'A1', 'price' => 900, 'is_deactive' => 0]);
        $pricing = app(AuthoritativeTicketPricingService::class);

        $quote = $pricing->quote($event->id, [['ticket_id' => $ticket->id, 'quantity' => 1, 'seat_id' => $seat->id, 'slot_id' => $slot->id]]);
        $this->assertSame(90000, $quote['ticket_amount']);
        $this->assertSame($seat->id, $quote['items'][0]['seat_id']);

        $this->booking($event, ['variation' => json_encode([['ticket_id' => $ticket->id, 'qty' => 1, 'seat_id' => $seat->id, 'slot_id' => $slot->id, 'unique_id' => 's']])]);
        $this->expectException(ValidationException::class);
        $pricing->quote($event->id, [['ticket_id' => $ticket->id, 'quantity' => 1, 'seat_id' => $seat->id, 'slot_id' => $slot->id]]);
    }

    public function test_coupon_discount_is_calculated_on_the_server(): void
    {
        $event = $this->event($this->organizer());
        DB::table('coupons')->insert(['name' => 'Ten', 'code' => 'TEN', 'type' => 'percentage', 'value' => 10, 'events' => json_encode([$event->id]),
            'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString()]);
        $coupons = app(CouponService::class);
        $this->assertSame(5000, $coupons->discount('TEN', $event->id, 50000)['amount']);
        $this->assertSame(0, $coupons->discount('TEN', $event->id + 1, 50000)['amount'], 'Coupon restricted to other events.');
        $this->assertSame(0, $coupons->discount('NOPE', $event->id, 50000)['amount']);
    }
}
