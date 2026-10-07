<?php

namespace App\Services\Payments;

use App\Models\BasicSettings\Basic;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\Ticket;
use App\Models\Payments\PaymentOrder;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Api;

/**
 * Server-side decision for bookings posted by the legacy customer-app endpoint (POST /api/event-booking).
 *
 * The client's totals and `paymentStatus` are never trusted:
 *  - server price 0                                       -> free
 *  - Razorpay payment id that Razorpay confirms captured,
 *    for at least the server amount, not used before      -> completed
 *  - anything else                                         -> pending (organizer/admin confirms)
 */
class LegacyAppBookingVerifier
{
    public function __construct(private AuthoritativeTicketPricingService $pricing)
    {
    }

    /** @return array{status:string,price:float,tax:float,discount:float,gateway_payment_id:?string,quote:array} */
    public function decide(array $input): array
    {
        $eventId = (int) ($input['event_id'] ?? 0);
        $quote = $this->pricing->quote($eventId, $this->items($eventId, $input), 'mobile');
        $taxRate = (float) (optional(Basic::select('tax')->first())->tax ?? 0);
        $taxPaise = (int) round($quote['ticket_amount'] * $taxRate / 100);
        $result = [
            'price' => $quote['ticket_amount'] / 100,
            'tax' => $taxPaise / 100,
            'discount' => $quote['discount'] / 100,
            'gateway_payment_id' => null,
            'quote' => $quote,
        ];

        if ((int) $quote['ticket_amount'] === 0) {
            return $result + ['status' => 'free'];
        }

        $paymentId = trim((string) ($input['razorpay_payment_id'] ?? ''));
        if (strtolower((string) ($input['gateway'] ?? '')) === 'razorpay' && $paymentId !== ''
            && $this->razorpayPaymentIsValid($paymentId, (int) $quote['ticket_amount'] + $taxPaise)) {
            $result['gateway_payment_id'] = $paymentId;
            return $result + ['status' => 'completed'];
        }

        return $result + ['status' => 'pending'];
    }

    private function items(int $eventId, array $input): array
    {
        $lines = $input['selTickets'] ?? [];
        if (is_string($lines)) $lines = json_decode($lines, true) ?: [];
        $items = [];
        foreach ((array) $lines as $line) {
            if (!is_array($line) || empty($line['ticket_id']) || (int) ($line['qty'] ?? 0) < 1) continue;
            $items[] = ['ticket_id' => (int) $line['ticket_id'], 'quantity' => (int) $line['qty'], 'variation' => $line['name'] ?? null,
                'pass_product_id' => $line['pass_product_id'] ?? null, 'event_date_ids' => $line['event_date_ids'] ?? []];
        }
        if (!$items) {
            // Online events: one server-managed ticket, quantity from the request.
            $ticket = Ticket::where('event_id', $eventId)->orderBy('id')->first();
            $qty = (int) ($input['quantity'] ?? 0);
            if (!$ticket || $qty < 1 || optional(Event::find($eventId))->event_type !== 'online') {
                throw ValidationException::withMessages(['selTickets' => 'Please select at least one ticket.']);
            }
            $items[] = ['ticket_id' => $ticket->id, 'quantity' => $qty];
        }
        return $items;
    }

    private function razorpayPaymentIsValid(string $paymentId, int $expectedPaise): bool
    {
        if (!Schema::hasColumn('bookings', 'gateway_payment_id')) return false;
        if (Booking::where('gateway_payment_id', $paymentId)->exists()) return false;
        if (PaymentOrder::where('gateway_payment_id', $paymentId)->exists()) return false;
        try {
            $payment = $this->fetchPayment($paymentId);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
        return (string) $payment->status === 'captured'
            && strtoupper((string) $payment->currency) === 'INR'
            && (int) $payment->amount >= $expectedPaise;
    }

    /** @return object with status, currency and amount (paise) */
    protected function fetchPayment(string $paymentId): object
    {
        return $this->api()->payment->fetch($paymentId);
    }

    protected function api(): Api
    {
        $config = json_decode((string) optional(OnlineGateway::whereKeyword('razorpay')->first())->information, true) ?: [];
        return new Api($config['key'] ?? '', $config['secret'] ?? '');
    }
}
