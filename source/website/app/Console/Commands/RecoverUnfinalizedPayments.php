<?php

namespace App\Console\Commands;

use App\Models\Payments\PaymentOrder;
use App\Services\Payments\PaymentCaptureService;
use App\Services\Payments\RazorpayRouteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Retries bookings for payments that Razorpay captured but BookTKIT could not finalize
 * (closed browser, lost session, stock changed during payment). Optionally refunds orders that
 * still cannot be fulfilled after the grace period (BOOKTKIT_AUTO_REFUND_UNFULFILLED=true).
 */
class RecoverUnfinalizedPayments extends Command
{
    protected $signature = 'payments:recover-unfinalized {--refund : Refund orders that still fail after the grace period, regardless of config}';
    protected $description = 'Finalize or refund captured Razorpay payments that have no booking';

    public function handle(PaymentCaptureService $capture): int
    {
        $refundEnabled = $this->option('refund') || (bool) config('booktkit.auto_refund_unfulfilled', false);
        $graceMinutes = max(5, (int) config('booktkit.unfulfilled_refund_after_minutes', 30));
        $hasAttempts = Schema::hasColumn('payment_orders', 'finalization_attempts');
        $done = 0; $refunded = 0; $failed = 0;

        PaymentOrder::where('status', PaymentCaptureService::UNFINALIZED)
            ->whereNull('booking_id')->whereNotNull('gateway_payment_id')
            ->where('refunded_amount', 0)
            ->orderBy('id')->chunkById(50, function ($orders) use ($capture, $refundEnabled, $graceMinutes, $hasAttempts, &$done, &$refunded, &$failed) {
                foreach ($orders as $order) {
                    try {
                        $capture->complete($order, (string) $order->gateway_payment_id);
                        $done++;
                        continue;
                    } catch (\Throwable $e) {
                        $failed++;
                    }
                    $order->refresh();
                    $old = optional($order->updated_at)->lt(now()->subMinutes($graceMinutes)) || optional($order->created_at)->lt(now()->subMinutes($graceMinutes));
                    $tries = $hasAttempts ? (int) $order->finalization_attempts : 3;
                    if ($refundEnabled && $old && $tries >= 3) {
                        try {
                            app(RazorpayRouteService::class)->refund($order, (int) $order->customer_total, 'booking_could_not_be_fulfilled');
                            $order->update(['status' => 'refunded']);
                            $refunded++;
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                }
            });

        $this->info("Finalized: {$done}; still failing: {$failed}; refunded: {$refunded}");
        return self::SUCCESS;
    }
}
