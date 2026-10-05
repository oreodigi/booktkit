<?php
namespace App\Services\Payments;
use Illuminate\Support\Str;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\OrganizerPaymentProfile;
class PaymentOrderService {
 public function createFromPricing(int $eventId,?int $organizerId,int $ticketAmountPaise,int $taxPaise,string $idempotencyKey,array $snapshot=[]): PaymentOrder {
  if($existing=PaymentOrder::where('idempotency_key',$idempotencyKey)->first()) return $existing;
  $profile=$organizerId ? OrganizerPaymentProfile::where('organizer_id',$organizerId)->first() : null;
  $pricing=(new PlatformFeeCalculator)->calculate($ticketAmountPaise,$profile);
  $routing=(new SettlementRoutingService)->resolve($profile);\n  $settlement=$routing['mode'];
  return PaymentOrder::create(['uuid'=>(string)Str::uuid(),'event_id'=>$eventId,'organizer_id'=>$organizerId,'currency'=>'INR',
   'ticket_amount'=>$pricing['ticket_amount'],'platform_fee'=>$pricing['platform_fee'],'tax_amount'=>$taxPaise,
   'customer_total'=>$pricing['customer_total']+$taxPaise,'organizer_amount'=>$pricing['organizer_amount'],
   'fee_bearer'=>$pricing['fee_bearer'],'settlement_mode'=>$settlement,'settlement_reason'=>$routing['reason'],'status'=>'created','idempotency_key'=>$idempotencyKey,
   'pricing_snapshot'=>$snapshot+$pricing+['settlement_reason'=>$routing['reason']]]);
 }
}