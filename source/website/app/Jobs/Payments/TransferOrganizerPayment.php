<?php
namespace App\Jobs\Payments;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\Payments\PaymentOrder;
use App\Services\Payments\RazorpayRouteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class TransferOrganizerPayment implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public int $tries=5; public array $backoff=[60,300,900,3600];
 public function __construct(public int $paymentOrderId){}
 public function handle(RazorpayRouteService $route): void {
  $order=PaymentOrder::findOrFail($this->paymentOrderId);
  if($order->status!=='paid'||$order->settlement_mode!=='razorpay_split') return;
  $profile=OrganizerPaymentProfile::where('organizer_id',$order->organizer_id)->first();
  if(!$profile||!$profile->canSplit()) return;
  $route->transferToOrganizer($order,$profile->razorpay_account_id);
 }
}