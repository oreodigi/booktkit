<?php
namespace App\Services\Payments;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\Payments\PaymentWebhookEvent;
class RazorpayWebhookService {
 public function verify(string $payload,?string $signature): array {
  $g=OnlineGateway::whereKeyword('razorpay')->firstOrFail(); $c=json_decode($g->information,true);
  $secret=$c['webhook_secret'] ?? env('RAZORPAY_WEBHOOK_SECRET');
  if(!$secret) throw new \RuntimeException('Razorpay webhook secret is not configured.');
  $expected=hash_hmac('sha256',$payload,$secret);
  if(!$signature || !hash_equals($expected,$signature)) throw new \RuntimeException('Invalid Razorpay webhook signature.');
  $data=json_decode($payload,true,512,JSON_THROW_ON_ERROR); return $data;
 }
 public function remember(array $payload, ?string $deliveryId=null): PaymentWebhookEvent {
  $id=$deliveryId ?: $payload['event'].'-'.($payload['payload']['payment']['entity']['id'] ?? $payload['payload']['transfer']['entity']['id'] ?? sha1(json_encode($payload)));
  return PaymentWebhookEvent::firstOrCreate(['event_id'=>$id],['gateway'=>'razorpay','event_type'=>$payload['event']??'unknown','payload'=>$payload,'status'=>'received']);
 }
}