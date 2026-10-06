<?php
namespace App\Services\Payments;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\OrganizerPaymentProfile;
class PaymentOrderService {
 public function createFromPricing(int $eventId,?int $organizerId,int $ticketAmountPaise,int $taxPaise,string $idempotencyKey,array $snapshot=[]): PaymentOrder {
  if($existing=PaymentOrder::where('idempotency_key',$idempotencyKey)->first())return $existing;
  return DB::transaction(function()use($eventId,$organizerId,$ticketAmountPaise,$taxPaise,$idempotencyKey,$snapshot){
   if($existing=PaymentOrder::where('idempotency_key',$idempotencyKey)->lockForUpdate()->first())return $existing;
   $event=Event::findOrFail($eventId);$profile=$organizerId?OrganizerPaymentProfile::where('organizer_id',$organizerId)->first():null;
   $channel=$snapshot['sales_channel']??'web';$rule=(new PaymentFeeRuleResolver)->resolve($organizerId,$eventId,$event->event_type,$channel);
   $pricing=(new PlatformFeeCalculator)->calculateRule($ticketAmountPaise,$rule,$profile);$additional=(new AdditionalFeeCalculator)->calculate($eventId,$channel,$ticketAmountPaise,(int)($snapshot['quantity']??1),(array)($snapshot['additional_fees']??[]));$routing=(new SettlementRoutingService)->decide($profile,$channel);
   $order=PaymentOrder::create(['uuid'=>(string)Str::uuid(),'event_id'=>$eventId,'event_type'=>$event->event_type,'organizer_id'=>$organizerId,'sales_channel'=>$channel,'currency'=>'INR','ticket_amount'=>$pricing['ticket_amount'],'platform_fee'=>$pricing['platform_fee'],'fee_rule_id'=>$pricing['fee_rule_id'],'fee_rule_version'=>$pricing['fee_rule_version'],'additional_fee_total'=>$additional['total'],'tax_amount'=>$taxPaise,'customer_total'=>$pricing['customer_total']+$additional['customer_total']+$taxPaise,'organizer_amount'=>max(0,$pricing['organizer_amount']-$additional['organizer_total']),'booktkit_revenue'=>$pricing['platform_fee']+$additional['total'],'fee_bearer'=>$pricing['fee_bearer'],'settlement_mode'=>$routing['mode'],'settlement_reason'=>$routing['reason'],'status'=>'created','idempotency_key'=>$idempotencyKey,'pricing_snapshot'=>$snapshot+['event_type'=>$event->event_type,'sales_channel'=>$channel,'settlement_mode'=>$routing['mode'],'settlement_reason'=>$routing['reason']]+$pricing]);
   foreach($additional['lines'] as $line)$order->feeLines()->create($line);
   if($pricing['platform_fee']>0)$order->feeLines()->create(['fee_rule_id'=>$pricing['fee_rule_id'],'code'=>'platform_fee','name'=>'BookTKIT Platform Fee','category'=>'platform','calculation_type'=>$rule?((float)$rule->percentage>0&&$rule->fixed_amount>0?'hybrid':((float)$rule->percentage>0?'percentage':'fixed')):'legacy','bearer'=>$pricing['fee_bearer_v2'],'base_amount'=>$ticketAmountPaise,'amount'=>$pricing['platform_fee'],'snapshot'=>$rule?$rule->toArray():['legacy_profile'=>true]]);
   return $order;
  });
 }
}