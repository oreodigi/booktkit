<?php
namespace App\Services\Payments;use App\Models\Payments\OrganizerPaymentProfile;use App\Models\Payments\PaymentFeeRule;
class PlatformFeeCalculator{
 public function calculate(int $ticket,?OrganizerPaymentProfile $p):array{$type=$p->fee_type??'percentage';$pct=(float)($p->fee_value??0);$fixed=(int)round(((float)($p->fee_fixed??0))*100);$fee=$type==='fixed'?$fixed:($type==='hybrid'?(int)round($ticket*$pct/100)+$fixed:(int)round($ticket*$pct/100));return $this->totals($ticket,$fee,($p->fee_bearer??'included')==='additional'?'customer':'organizer',null);}
 /**
  * Fee = percentage of ticket amount + fixed amount per order + per-ticket amount x quantity,
  * bounded by the rule's minimum/maximum. Free (zero-amount) orders carry no platform fee.
  */
 public function calculateRule(int $ticket,?PaymentFeeRule $r,?OrganizerPaymentProfile $legacy=null,int $quantity=1):array{if($ticket<=0)return $this->totals(0,0,($r?->fee_bearer??$legacy?->fee_bearer??'included')==='additional'?'customer':'organizer',$r);if(!$r)return $this->calculate($ticket,$legacy);$fee=(int)round($ticket*(float)$r->percentage/100)+(int)$r->fixed_amount+(int)$r->per_ticket_amount*max(1,$quantity);if($r->minimum_amount!==null)$fee=max($fee,(int)$r->minimum_amount);if($r->maximum_amount!==null)$fee=min($fee,(int)$r->maximum_amount);return $this->totals($ticket,max(0,$fee),$r->fee_bearer==='additional'?'customer':'organizer',$r);}
 private function totals(int $ticket,int $fee,string $bearer,?PaymentFeeRule $r):array{$customer=$bearer==='customer';return ['ticket_amount'=>$ticket,'platform_fee'=>$fee,'customer_total'=>$ticket+($customer?$fee:0),'organizer_amount'=>max(0,$ticket-($customer?0:$fee)),'fee_bearer'=>$customer?'additional':'included','fee_bearer_v2'=>$bearer,'fee_rule_id'=>$r?->id];}
}