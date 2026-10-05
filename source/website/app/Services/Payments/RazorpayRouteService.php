<?php
namespace App\Services\Payments;
use Razorpay\Api\Api;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\PaymentTransfer;
use App\Models\Payments\PaymentRefund;
use Illuminate\Support\Facades\DB;
class RazorpayRouteService {
 private Api $api;
 public function __construct(){
  $g=OnlineGateway::whereKeyword('razorpay')->firstOrFail();
  $c=json_decode($g->information,true);
  $this->api=new Api($c['key'],$c['secret']);
 }
 public function createOrder(PaymentOrder $order): array {
  $payload=['receipt'=>'BTK-'.$order->uuid,'amount'=>$order->customer_total,'currency'=>$order->currency,'payment_capture'=>1,
   'notes'=>['payment_order_uuid'=>$order->uuid,'event_id'=>(string)$order->event_id,'organizer_id'=>(string)($order->organizer_id ?? '')]];
  $rz=$this->api->order->create($payload);
  $order->update(['gateway_order_id'=>$rz->id,'gateway_payload'=>$rz->toArray(),'status'=>'pending']);
  return $rz->toArray();
 }
 public function verifyCheckout(PaymentOrder $order,string $paymentId,string $signature): void {
  $this->api->utility->verifyPaymentSignature(['razorpay_order_id'=>$order->gateway_order_id,'razorpay_payment_id'=>$paymentId,'razorpay_signature'=>$signature]);
  $payment=$this->api->payment->fetch($paymentId);
  if((string)$payment->order_id !== (string)$order->gateway_order_id || (int)$payment->amount !== (int)$order->customer_total || strtoupper((string)$payment->currency)!==strtoupper($order->currency)){
   throw new \RuntimeException('Razorpay payment amount, currency or order does not match BookTKIT payment order.');
  }
  if(!in_array((string)$payment->status,['captured','authorized'],true)) throw new \RuntimeException('Razorpay payment is not captured or authorized.');
 }
 public function refund(PaymentOrder $order,int $amountPaise,string $reason='requested'): PaymentRefund {
  if(!$order->gateway_payment_id) throw new \RuntimeException('Payment has no Razorpay payment id.');
  $remaining=(int)$order->customer_total-(int)$order->refunded_amount;
  if($amountPaise<1 || $amountPaise>$remaining) throw new \InvalidArgumentException('Invalid refund amount.');
  $rz=$this->api->payment->fetch($order->gateway_payment_id)->refund(['amount'=>$amountPaise,'notes'=>['payment_order_uuid'=>$order->uuid,'reason'=>$reason]]);
  $data=$rz->toArray();
  $refund=PaymentRefund::create(['payment_order_id'=>$order->id,'gateway_refund_id'=>$data['id']??null,'amount'=>$amountPaise,'currency'=>$order->currency,
   'status'=>$data['status']??'processed','reason'=>$reason,'gateway_payload'=>$data,'processed_at'=>now()]);
  $order->increment('refunded_amount',$amountPaise); $order->refresh();
  $order->refund_status=$order->refunded_amount >= $order->customer_total ? 'full' : 'partial'; $order->save();
  return $refund;
 }
 public function transferToOrganizer(PaymentOrder $order,string $accountId): ?PaymentTransfer {
  if($order->organizer_amount<=0) return null;
  if($existing=$order->transfers()->whereNotIn('status',['failed','reversed'])->first()) return $existing;
  $row=PaymentTransfer::create(['payment_order_id'=>$order->id,'organizer_id'=>$order->organizer_id,'linked_account_id'=>$accountId,
   'amount'=>$order->organizer_amount,'currency'=>$order->currency,'status'=>'pending','attempts'=>1]);
  try {
   $holdDays=max(0,(int)(DB::table('payment_settings')->where('key','transfer_hold_days')->value('value') ?? 2));
   $eventEnd=DB::table('events')->where('id',$order->event_id)->value('end_date_time');
   $releaseAt=\Carbon\Carbon::parse($eventEnd ?: now())->addDays($holdDays);
   $payment=$this->api->payment->fetch($order->gateway_payment_id);
   $result=$payment->transfer(['transfers'=>[['account'=>$accountId,'amount'=>$order->organizer_amount,'currency'=>$order->currency,
    'on_hold'=>true,'on_hold_until'=>$releaseAt->timestamp,'notes'=>['payment_order_uuid'=>$order->uuid]]]]);
   $data=$result->toArray(); $first=$data['items'][0] ?? $data[0] ?? null;
   $row->update(['gateway_transfer_id'=>$first['id']??null,'status'=>$first['status']??'created','gateway_payload'=>$data,
    'on_hold'=>true,'hold_release_at'=>$releaseAt,'processed_at'=>now()]);
  } catch(\Throwable $e) { $row->update(['status'=>'failed','last_error'=>$e->getMessage(),'processed_at'=>now()]); throw $e; }
  return $row;
 }
}