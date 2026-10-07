<?php

namespace Tests\Feature\Hardening;

use App\Models\Event\Booking;
use App\Models\Event\IssuedTicket;
use App\Models\Payments\PaymentOrder;
use App\Services\Payments\AuthoritativeTicketPricingService;
use App\Services\Payments\LegacyAppBookingVerifier;
use App\Services\Payments\PaymentOrderService;
use App\Services\Tickets\TicketAdmissionService;
use App\Services\Tickets\TicketIssuanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\RefreshLegacyDatabase;
use Tests\TestCase;

class PaymentsAndAdmissionTest extends TestCase
{
    use RefreshLegacyDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        DB::table('online_gateways')->where('keyword', 'razorpay')->update(['information' => json_encode(['key' => 'rzp_test_x', 'secret' => 'secret', 'webhook_secret' => 'whsec_test'])]);
    }

    private function quote(int $eventId, array $items, string $channel = 'web'): array
    {
        return app(AuthoritativeTicketPricingService::class)->quote($eventId, $items, $channel);
    }

    public function test_quote_refuses_unpublished_or_ended_events_but_pos_can_sell_counter_only_events(): void
    {
        $organizer = $this->organizer();
        $draft = $this->event($organizer, ['status' => '0', 'event_type' => 'box_office', 'box_office_enabled' => 1]);
        $ticket = $this->ticket($draft);
        try { $this->quote($draft->id, [['ticket_id' => $ticket->id, 'quantity' => 1]]); $this->fail('Unpublished event was quoted online.'); }
        catch (ValidationException $e) { $this->assertStringContainsString('not available', json_encode($e->errors())); }
        $this->assertSame(50000, $this->quote($draft->id, [['ticket_id' => $ticket->id, 'quantity' => 1]], 'box_office')['ticket_amount']);

        $ended = $this->event($organizer, ['end_date_time' => now()->subDay()->toDateTimeString()]);
        $old = $this->ticket($ended);
        $this->expectException(ValidationException::class);
        $this->quote($ended->id, [['ticket_id' => $old->id, 'quantity' => 1]]);
    }

    public function test_quote_enforces_max_per_order_across_lines(): void
    {
        $event = $this->event($this->organizer());
        $ticket = $this->ticket($event, ['max_ticket_buy_type' => 'limited', 'max_buy_ticket' => 3]);
        $this->assertSame(3, $this->quote($event->id, [['ticket_id' => $ticket->id, 'quantity' => 3]])['quantity']);
        $this->expectException(ValidationException::class);
        $this->quote($event->id, [['ticket_id' => $ticket->id, 'quantity' => 2], ['ticket_id' => $ticket->id, 'quantity' => 2]]);
    }

    private function paidOrder(int $qty = 1): PaymentOrder
    {
        $event = $this->event($this->organizer());
        $ticket = $this->ticket($event, ['price' => 250, 'ticket_available' => 5]);
        $quote = $this->quote($event->id, [['ticket_id' => $ticket->id, 'quantity' => $qty]]);
        $order = app(PaymentOrderService::class)->createFromPricing($event->id, $event->organizer_id, $quote['ticket_amount'], 0, 'test-' . uniqid(),
            ['items' => $quote['items'], 'quantity' => $quote['quantity'], 'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'], 'tax_rate' => 0, 'sales_channel' => 'web']);
        $order->update(['gateway_order_id' => 'order_' . uniqid(), 'status' => 'pending', 'customer_snapshot' => ['customer_id' => 'guest', 'fname' => 'Qa', 'lname' => 'B', 'email' => 'qa@example.invalid', 'phone' => '1', 'country' => 'India', 'address' => 'x']]);
        return $order->fresh();
    }

    private function postCapturedWebhook(PaymentOrder $order, string $paymentId, ?int $amount = null)
    {
        $payload = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => [
            'id' => $paymentId, 'order_id' => $order->gateway_order_id, 'amount' => $amount ?? $order->customer_total, 'currency' => 'INR', 'status' => 'captured', 'amount_refunded' => 0,
        ]]]]);
        return $this->call('POST', '/api/v1/webhooks/razorpay', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $payload, 'whsec_test'), 'HTTP_X_RAZORPAY_EVENT_ID' => uniqid('evt')], $payload);
    }

    public function test_captured_webhook_creates_the_booking_once_even_if_the_browser_never_returned(): void
    {
        $order = $this->paidOrder(2);
        $this->postCapturedWebhook($order, 'pay_A1')->assertOk();
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->booking_id);
        $this->assertSame(2, IssuedTicket::where('booking_id', $order->booking_id)->count());
        $this->assertSame(1, DB::table('payment_ledger_entries')->where('payment_order_id', $order->id)->where('entry_type', 'customer_payment')->count());

        $this->postCapturedWebhook($order, 'pay_A1')->assertOk();
        $this->assertSame(1, Booking::where('event_id', $order->event_id)->count());
    }

    public function test_webhook_with_mismatched_amount_is_flagged_not_booked(): void
    {
        $order = $this->paidOrder();
        $this->postCapturedWebhook($order, 'pay_B1', 100)->assertOk();
        $order->refresh();
        $this->assertSame('captured_unfinalized', $order->status);
        $this->assertNull($order->booking_id);
    }

    public function test_unfulfillable_capture_is_recorded_and_recovered_later(): void
    {
        $order = $this->paidOrder(2);
        DB::table('tickets')->where('event_id', $order->event_id)->update(['ticket_available' => 1]);
        $this->postCapturedWebhook($order, 'pay_C1')->assertOk();
        $order->refresh();
        $this->assertSame('captured_unfinalized', $order->status);
        $this->assertStringContainsString('stock', strtolower((string) $order->finalization_error));

        DB::table('tickets')->where('event_id', $order->event_id)->update(['ticket_available' => 5]);
        $this->artisan('payments:recover-unfinalized')->assertExitCode(0);
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->booking_id);
    }

    public function test_legacy_app_cannot_mark_its_own_booking_paid(): void
    {
        $event = $this->event($this->organizer());
        $ticket = $this->ticket($event, ['price' => 800]);
        $payload = ['fname' => 'A', 'lname' => 'B', 'email' => 'a@example.invalid', 'phone' => '1', 'country' => 'India', 'address' => 'x', 'event_id' => $event->id,
            'gateway' => 'razorpay', 'gatewayType' => 'online', 'quantity' => 1, 'event_date' => now()->toDateString(), 'total' => 0, 'discount' => 0, 'tax' => 0,
            'total_early_bird_dicount' => 0, 'paymentStatus' => 'completed', 'customer_id' => 1, 'selTickets' => [['ticket_id' => $ticket->id, 'qty' => 1, 'name' => 'General']]];
        $response = $this->postJson('/api/event-booking', $payload);
        $booking = Booking::where('event_id', $event->id)->latest('id')->firstOrFail();
        $this->assertSame('pending', $booking->paymentStatus);
        $this->assertEquals(800.0, (float) $booking->price);
        $this->assertSame('guest', (string) $booking->customer_id);
    }

    public function test_legacy_app_razorpay_payment_is_verified_with_razorpay(): void
    {
        $event = $this->event($this->organizer());
        $ticket = $this->ticket($event, ['price' => 800]);
        $this->app->bind(LegacyAppBookingVerifier::class, fn ($app) => new class($app->make(AuthoritativeTicketPricingService::class)) extends LegacyAppBookingVerifier {
            protected function fetchPayment(string $paymentId): object { return (object) ['status' => 'captured', 'currency' => 'INR', 'amount' => 80000]; }
        });
        $input = ['event_id' => $event->id, 'gateway' => 'razorpay', 'razorpay_payment_id' => 'pay_app_1', 'selTickets' => [['ticket_id' => $ticket->id, 'qty' => 1, 'name' => 'General']]];
        $this->assertSame('completed', app(LegacyAppBookingVerifier::class)->decide($input)['status']);
        $this->assertSame('pending', app(LegacyAppBookingVerifier::class)->decide(['razorpay_payment_id' => ''] + $input)['status']);
        Booking::forceCreate(['booking_id' => 'used', 'event_id' => $event->id, 'gateway_payment_id' => 'pay_app_1', 'paymentStatus' => 'completed']);
        $this->assertSame('pending', app(LegacyAppBookingVerifier::class)->decide($input)['status'], 'A payment id can pay for one booking only.');
    }

    private function issued(array $eventOverrides = []): array
    {
        $organizer = $this->organizer();
        $event = $this->event($organizer, $eventOverrides);
        $ticket = $this->ticket($event, ['reentry_policy' => 'unlimited']);
        $booking = $this->booking($event, ['variation' => json_encode([['ticket_id' => $ticket->id, 'qty' => 1, 'name' => 'General', 'unique_id' => 'q']])]);
        return [$organizer, $event, app(TicketIssuanceService::class)->ensureForBooking($booking)[0]['token']];
    }

    public function test_admission_rejects_tickets_for_another_event_or_a_future_date(): void
    {
        [$organizer, $event, $token] = $this->issued();
        $admission = app(TicketAdmissionService::class);
        $this->assertSame('wrong_event', $admission->admit($token, 'organizer', $organizer->id, null, null, 'entry', null, false, null, $event->id + 999)['reason_code']);
        $this->assertSame('success', $admission->admit($token, 'organizer', $organizer->id, null, null, 'entry', null, false, null, $event->id)['alert_type']);

        [$organizer2, , $futureToken] = $this->issued(['start_date' => now()->addDays(5)->toDateString(), 'end_date_time' => now()->addDays(6)->toDateTimeString()]);
        $this->assertSame('wrong_date', $admission->admit($futureToken, 'organizer', $organizer2->id)['reason_code']);
    }

    public function test_gate_and_override_reach_the_engine(): void
    {
        [$organizer, $event, $token] = $this->issued();
        DB::table('event_access_policies')->insert(['event_id' => $event->id, 'organizer_id' => $organizer->id, 'is_enabled' => 1, 'credential_mode' => 'ticket_or_credential', 'reentry_policy' => 'unlimited', 'created_at' => now(), 'updated_at' => now()]);
        $gate = DB::table('event_gates')->insertGetId(['event_id' => $event->id, 'organizer_id' => $organizer->id, 'name' => 'Main entry', 'code' => 'MAIN', 'mode' => 'entry', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $admission = app(TicketAdmissionService::class);
        $this->assertSame('success', $admission->admit($token, 'organizer', $organizer->id, null, null, 'entry', $gate)['alert_type']);
        $this->assertSame('gate_direction_denied', $admission->admit($token, 'organizer', $organizer->id, null, null, 'exit', $gate)['reason_code']);
        $this->assertSame('already_inside', $admission->admit($token, 'organizer', $organizer->id, null, null, 'entry', $gate)['reason_code']);
        $this->assertSame('success', $admission->admit($token, 'organizer', $organizer->id, null, null, 'entry', $gate, true, 'Wristband fault at gate')['alert_type']);
        $this->assertDatabaseHas('access_scans', ['gate_id' => $gate, 'is_override' => 1, 'override_reason' => 'Wristband fault at gate']);
    }
}
