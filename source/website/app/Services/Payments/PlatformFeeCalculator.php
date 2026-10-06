<?php
namespace App\Services\Payments;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\Payments\PaymentFeeRule;
class PlatformFeeCalculator {
 public function calculate(int $ticketAmountPaise,?OrganizerPaymentProfile $profile): array {
  $type=$profile->fee_type ?? 'percentage';$value=(float)($profile->fee_value ?? 0);$fixed=(int)round(((float)($profile->fee_fixed ?? 0))*100);
  $fee=$type==='fixed'?$fixed:($type==='hybrid'?(int)round($ticketAmountPaise*$value/100)+$fixed:(int)round($ticketAmountPaise*$value/100));
  return $this->totals($ticketAmountPaise,$fee,($profile->fee_bearer ?? 'included')==='additional'?'customer':'organizer',null);
 }
 public function calculateRule(int $ticketAmountPaise,?PaymentFeeRule $rule,?OrganizerPaymentProfile $legacyProfile=null): array {
  if(!$rule) return $this->calculate($ticketAmountPaise,$legacyProfile);
  $fee=(int)round($ticketAmountPaise*(float)$rule->percentage/100)+(int)$rule->fixed_amount;
  if($rule->minimum_fee!==null)$fee=max($fee,(int)$rule->minimum_fee);
  if($rule->maximum_fee!==null)$fee=min($fee,(int)$rule->maximum_fee);
  return $this->totals($ticketAmountPaise,max(0,$fee),$rule->fee_bearer,$rule);
 }
 private function totals(int $ticket,int $fee,string $bearer,?PaymentFeeRule $rule): array {
  $customerPays=$bearer==='customer';return ['ticket_amount'=>$ticket,'platform_fee'=>$fee,'customer_total'=>$ticket+($customerPays?$fee:0),'organizer_amount'=>max(0,$ticket-($customerPays?0:$fee)),'fee_bearer'=>$customerPays?'additional':'included','fee_bearer_v2'=>$bearer,'fee_rule_id'=>$rule?->id,'fee_rule_version'=>$rule?->version];
 }
}