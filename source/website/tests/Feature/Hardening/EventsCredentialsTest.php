<?php

namespace Tests\Feature\Hardening;

use App\Models\Access\Credential;
use App\Models\Event\EventDates;
use App\Models\Event\IssuedTicket;
use App\Services\Access\AccessControlService;
use App\Services\Access\CredentialInventoryService;
use App\Services\Events\EventActor;
use App\Services\Events\EventFormService;
use App\Services\Tickets\TicketIssuanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\RefreshLegacyDatabase;
use Tests\TestCase;

class EventsCredentialsTest extends TestCase
{
    use RefreshLegacyDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    private function syncDates($event, array $data): void
    {
        $service = app(EventFormService::class);
        $m = new \ReflectionMethod($service, 'syncDates'); $m->setAccessible(true);
        $m->invoke($service, $event, $data, true);
    }

    public function test_sessions_with_passes_or_bookings_cannot_be_removed(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, ['date_type' => 'multiple']);
        $day1 = EventDates::create(['event_id' => $event->id, 'start_date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_date' => now()->addDay()->toDateString(), 'end_time' => '18:00']);
        $day2 = EventDates::create(['event_id' => $event->id, 'start_date' => now()->addDays(2)->toDateString(), 'start_time' => '10:00', 'end_date' => now()->addDays(2)->toDateString(), 'end_time' => '18:00']);
        $this->booking($event, ['event_date' => $day1->start_date . ' 10:00']);
        $keepOnlyDay2 = ['date_type' => 'multiple', 'date_ids' => [$day2->id], 'm_start_date' => [$day2->start_date], 'm_start_time' => ['10:00'], 'm_end_date' => [$day2->end_date], 'm_end_time' => ['18:00']];

        try { $this->syncDates($event, $keepOnlyDay2); $this->fail('A session with bookings was removed.'); }
        catch (ValidationException $e) { $this->assertDatabaseHas('event_dates', ['id' => $day1->id]); }

        $this->expectException(ValidationException::class);
        $this->syncDates($event, ['date_type' => 'single']);
    }

    public function test_renaming_a_counter_keeps_its_id_and_used_counters_are_deactivated_not_deleted(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, ['event_type' => 'box_office', 'box_office_enabled' => 1]);
        $counter = DB::table('box_office_locations')->insertGetId(['event_id' => $event->id, 'name' => 'North', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $used = DB::table('box_office_locations')->insertGetId(['event_id' => $event->id, 'name' => 'South', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('box_office_shifts')->insert(['organizer_id' => $organizer->id, 'event_id' => $event->id, 'location_id' => $used, 'staff_id' => $this->staff($organizer, [])->id, 'status' => 'closed', 'opening_cash' => 0, 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $service = app(EventFormService::class);
        $m = new \ReflectionMethod($service, 'syncBoxOfficeLocations'); $m->setAccessible(true);
        $m->invoke($service, $event->fresh(), ['box_office_locations' => [['id' => $counter, 'name' => 'North Gate', 'address' => 'x', 'active' => 1]]]);

        $this->assertDatabaseHas('box_office_locations', ['id' => $counter, 'name' => 'North Gate']);
        $this->assertDatabaseHas('box_office_locations', ['id' => $used, 'active' => 0]);
    }

    public function test_duplicate_copies_counters_passes_and_access_setup_without_sales(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, ['event_type' => 'box_office', 'box_office_enabled' => 1, 'date_type' => 'multiple']);
        $day = EventDates::create(['event_id' => $event->id, 'start_date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_date' => now()->addDay()->toDateString(), 'end_time' => '18:00']);
        $ticket = $this->ticket($event);
        DB::table('box_office_locations')->insert(['event_id' => $event->id, 'name' => 'Main', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('event_gates')->insert(['event_id' => $event->id, 'organizer_id' => $organizer->id, 'name' => 'Gate A', 'code' => 'a', 'mode' => 'entry', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $pass = DB::table('event_pass_products')->insertGetId(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'event_id' => $event->id, 'organizer_id' => $organizer->id, 'ticket_id' => $ticket->id,
            'code' => 'DAY', 'name' => 'Day pass', 'pass_type' => 'single_day', 'price' => 10000, 'sold_quantity' => 7, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('event_pass_dates')->insert(['pass_product_id' => $pass, 'event_date_id' => $day->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->booking($event);

        $copy = app(EventFormService::class)->duplicateEvent($event, EventActor::organizer($organizer));
        $this->assertSame(1, DB::table('box_office_locations')->where('event_id', $copy->id)->count());
        $this->assertSame(1, DB::table('event_gates')->where('event_id', $copy->id)->count());
        $newPass = DB::table('event_pass_products')->where('event_id', $copy->id)->first();
        $this->assertNotNull($newPass);
        $this->assertSame(0, (int) $newPass->sold_quantity);
        $this->assertSame((int) $copy->dates->first()->id, (int) DB::table('event_pass_dates')->where('pass_product_id', $newPass->id)->value('event_date_id'));
        $this->assertSame(0, DB::table('bookings')->where('event_id', $copy->id)->count());
    }

    public function test_credentials_rfid_wristband_batches_type_check_and_revoke(): void
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer);
        $ticket = $this->ticket($event, ['admission_pass_type' => 'rfid_wristband', 'allow_mobile_qr_before_assignment' => 0]);
        $booking = $this->booking($event, ['variation' => json_encode([['ticket_id' => $ticket->id, 'qty' => 1, 'name' => 'General', 'unique_id' => 'r']])]);
        $issued = app(TicketIssuanceService::class)->ensureForBooking($booking)[0];
        $this->actingAs($organizer, 'organizer');

        $this->post(route('organizer.access.batch'), ['event_id' => $event->id, 'credential_type' => 'rfid_wristband', 'batch_code' => 'B1', 'identifiers' => "RF-1\nRF-2"])->assertSessionHasNoErrors();
        app(CredentialInventoryService::class)->createBatch($organizer->id, $event->id, 'qr_badge', 'B2', ['QB-1']);

        $this->post(route('organizer.access.assign'), ['ticket_reference' => $issued['uuid'], 'credential_identifier' => 'QB-1'])->assertSessionHasErrors('credential');
        $this->post(route('organizer.access.assign'), ['ticket_reference' => $issued['uuid'], 'credential_identifier' => 'RF-1'])->assertSessionHasNoErrors();
        $this->assertSame('success', app(AccessControlService::class)->scan('RF-1', 'organizer', $organizer->id)['alert_type']);

        $this->post(route('organizer.access.revoke'), ['credential_identifier' => 'RF-1', 'reason' => 'lost'])->assertSessionHasNoErrors();
        $this->assertSame('revoked', Credential::where('display_code', app(CredentialInventoryService::class)->resolve('RF-1')->display_code)->value('status'));
        $this->assertSame('credential_revoked', app(AccessControlService::class)->scan('RF-1', 'organizer', $organizer->id, null, null, 'exit')['reason_code']);
        $this->assertSame('active', IssuedTicket::find($issued['id'])->status, 'Revoking a wristband keeps the ticket valid.');
    }

    public function test_admin_impersonation_is_read_only_and_can_be_ended(): void
    {
        $organizer = $this->organizer();
        $staff = $this->staff($organizer, ['box_office.sell']);
        $this->actingAs($staff, 'staff')->withSession(['staff_impersonated_by_admin' => 1])
            ->post(route('staff.shifts.open'), ['event_id' => 1, 'location_id' => 1, 'opening_cash' => 0])->assertForbidden();
        $this->actingAs($staff, 'staff')->withSession(['staff_impersonated_by_admin' => 1])
            ->post(route('staff.impersonation.end'))->assertRedirect(route('admin.organizer_workforce.index'));
        $this->assertDatabaseHas('staff_audit_logs', ['staff_id' => $staff->id, 'action' => 'admin_impersonation_ended']);
    }
}
