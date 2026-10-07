<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\PaymentTransfer;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Services\Payments\RazorpayWebhookService;
use App\Services\Payments\PaymentReconciliationService;
use App\Services\Payments\PaymentCaptureService;
class RazorpayWebhookController extends Controller {
 public function handle(Request $r,RazorpayWebhookService $webhooks,PaymentReconciliationService $reconcile){
  try { $payload=$webhooks->verify($r->getContent(),$r->header('X-Razorpay-Signature')); }
  catch(\Throwable $e){ return response()->json(['success'=>false],400); }
  $event=$webhooks->remember($payload,$r->header('X-Razorpay-Event-Id')); if($event->status==='processed') return response()->json(['success'=>true,'idempotent'=>true]);
  $type=$payload['event']??''; $payment=$payload['payload']['payment']['entity']??null; $transfer=$payload['payload']['transfer']['entity']??null;
  if($payment && !empty($payment['id'])){
   $o=PaymentOrder::where('gateway_payment_id',$payment['id'])->orWhere('gateway_order_id',$payment['order_id']??'')->first();
   if($o){
    if(in_array($type,['payment.captured','order.paid'],true) && !$o->booking_id){
     // Signed webhook: complete the booking even if the customer closed the browser after paying.
     $matches=(string)($payment['order_id']??'')===(string)$o->gateway_order_id && (int)($payment['amount']??-1)===(int)$o->customer_total
      && strtoupper((string)($payment['currency']??''))===strtoupper((string)$o->currency) && ($payment['status']??'')==='captured';
     if($matches){ try { app(PaymentCaptureService::class)->complete($o,(string)$payment['id']); } catch(\Throwable $e){ report($e); } }
     else app(PaymentCaptureService::class)->markUnfinalized($o,(string)$payment['id'],new \RuntimeException('Captured payment does not match the BookTKIT payment order.'));
     $o->refresh();
    }
    if(in_array($type,['refund.processed','payment.refunded'],true)) $o->update(['refund_status'=>(($payment['amount_refunded']??0)>=$o->customer_total?'full':'partial'),'refunded_amount'=>$payment['amount_refunded']??$o->refunded_amount]);
    $reconcile->reconcile($o); }
  }
  if($transfer && !empty($transfer['id'])) PaymentTransfer::where('gateway_transfer_id',$transfer['id'])->update(['status'=>$transfer['status']??$type,'gateway_payload'=>$transfer,'processed_at'=>now()]);
  if(str_starts_with($type,'product.route.') || str_starts_with($type,'account.')){
   $product=$payload['payload']['merchant_product']['entity']??[];
   $accountId=$product['merchant_id']??$payload['account_id']??null;
   $profile=$accountId ? OrganizerPaymentProfile::where('razorpay_account_id',$accountId)->first() : null;
   if($profile){
    $status=$product['activation_status']??str_replace(['product.route.','account.'],'',$type);
    $map=['activated'=>'activated','active'=>'activated','under_review'=>'under_review','needs_clarification'=>'needs_clarification','rejected'=>'rejected','suspended'=>'suspended'];
    if(isset($map[$status])) $profile->kyc_status=$map[$status];
    $profile->razorpay_status=$status;
    $profile->split_enabled=$profile->kyc_status==='activated';
    if($profile->split_enabled&&!$profile->activated_at)$profile->activated_at=now();
    $requirements=$payload['payload']['merchant_product']['data']['requirements']??null;
    if($requirements!==null)$profile->kyc_remarks=$requirements;
    $profile->save();
   }
  }
  $event->update(['status'=>'processed','processed_at'=>now()]); return response()->json(['success'=>true]);
 }
}