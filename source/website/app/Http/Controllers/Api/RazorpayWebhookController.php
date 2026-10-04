<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\PaymentTransfer;
use App\Services\Payments\RazorpayWebhookService;
use App\Services\Payments\PaymentReconciliationService;
class RazorpayWebhookController extends Controller {
 public function handle(Request $r,RazorpayWebhookService $webhooks,PaymentReconciliationService $reconcile){
  try { $payload=$webhooks->verify($r->getContent(),$r->header('X-Razorpay-Signature')); }
  catch(\Throwable $e){ return response()->json(['success'=>false],400); }
  $event=$webhooks->remember($payload); if($event->status==='processed') return response()->json(['success'=>true,'idempotent'=>true]);
  $type=$payload['event']??''; $payment=$payload['payload']['payment']['entity']??null; $transfer=$payload['payload']['transfer']['entity']??null;
  if($payment && !empty($payment['id'])){
   $o=PaymentOrder::where('gateway_payment_id',$payment['id'])->orWhere('gateway_order_id',$payment['order_id']??'')->first();
   if($o){ if($type==='payment.captured' && $o->status!=='paid') $o->update(['gateway_payment_id'=>$payment['id'],'status'=>'captured_unfinalized']);
    if(in_array($type,['refund.processed','payment.refunded'],true)) $o->update(['refund_status'=>(($payment['amount_refunded']??0)>=$o->customer_total?'full':'partial'),'refunded_amount'=>$payment['amount_refunded']??$o->refunded_amount]);
    $reconcile->reconcile($o); }
  }
  if($transfer && !empty($transfer['id'])) PaymentTransfer::where('gateway_transfer_id',$transfer['id'])->update(['status'=>$transfer['status']??$type,'gateway_payload'=>$transfer,'processed_at'=>now()]);
  $event->update(['status'=>'processed','processed_at'=>now()]); return response()->json(['success'=>true]);
 }
}