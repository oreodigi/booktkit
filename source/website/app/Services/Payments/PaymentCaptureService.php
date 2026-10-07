<?php

namespace App\Services\Payments;

use App\Jobs\Payments\TransferOrganizerPayment;
use App\Models\Event\Booking;
use App\Models\Payments\PaymentOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Single authority for turning a verified, captured Razorpay payment into a confirmed booking.
 * Used by the web callback, the v1 API verify endpoint, the webhook and the recovery command.
 *
 * Idempotent: an order that already has a booking returns it. If finalization fails after money
 * was captured, the order is marked `captured_unfinalized` with the error so recovery can retry
 * or refund it; it is never left looking unpaid.
 */
class PaymentCaptureService
{
    public const UNFINALIZED = 'captured_unfinalized';

    public function __construct(
        private BookingFinalizationService $finalizer,
        private PaymentLedgerService $ledger
    ) {
    }

    public function complete(PaymentOrder $order, string $gatewayPaymentId): Booking
    {
        try {
            $booking = DB::transaction(function () use ($order, $gatewayPaymentId) {
                $locked = PaymentOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($locked->booking_id) {
                    return Booking::findOrFail($locked->booking_id);
                }
                $locked->update([
                    'gateway_payment_id' => $gatewayPaymentId,
                    'status' => 'paid',
                    'paid_at' => $locked->paid_at ?: now(),
                ]);
                $booking = $this->finalizer->finalize($locked);
                $this->ledger->recordPaid($locked->fresh());
                return $booking;
            });
        } catch (\Throwable $e) {
            $this->markUnfinalized($order, $gatewayPaymentId, $e);
            throw $e;
        }

        $this->clearFinalizationError($order);
        $this->scheduleTransfer($order->fresh());
        return $booking;
    }

    public function markUnfinalized(PaymentOrder $order, ?string $gatewayPaymentId, \Throwable $e): void
    {
        $fresh = PaymentOrder::find($order->id);
        if (!$fresh || $fresh->booking_id) return;
        $data = ['status' => self::UNFINALIZED];
        if ($gatewayPaymentId) $data['gateway_payment_id'] = $gatewayPaymentId;
        if ($this->hasErrorColumns()) {
            $data['finalization_error'] = mb_substr($e->getMessage(), 0, 1000);
            $data['finalization_attempts'] = (int) $fresh->finalization_attempts + 1;
        }
        $fresh->update($data);
        Log::warning('Captured payment could not be finalized', ['payment_order' => $fresh->uuid, 'error' => $e->getMessage()]);
    }

    private function clearFinalizationError(PaymentOrder $order): void
    {
        if ($this->hasErrorColumns()) {
            PaymentOrder::whereKey($order->id)->whereNotNull('finalization_error')->update(['finalization_error' => null]);
        }
    }

    private function scheduleTransfer(PaymentOrder $order): void
    {
        if ($order->status !== 'paid' || $order->settlement_mode !== 'razorpay_split') return;
        try {
            TransferOrganizerPayment::dispatch($order->id);
        } catch (\Throwable $e) {
            // Transfer failures never undo a confirmed booking; the transfer row records the error
            // and admins can retry from Payment Operations.
            report($e);
        }
    }

    private function hasErrorColumns(): bool
    {
        static $has = null;
        return $has ??= Schema::hasColumn('payment_orders', 'finalization_error');
    }
}
