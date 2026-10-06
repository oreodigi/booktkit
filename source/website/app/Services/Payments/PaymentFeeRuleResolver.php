<?php
namespace App\Services\Payments;
use App\Models\Payments\PaymentFeeRule;
class PaymentFeeRuleResolver {
 public function resolve(?int $organizerId,int $eventId,?string $eventType,string $channel): ?PaymentFeeRule {
  $now=now();$rules=PaymentFeeRule::query()->where('enabled',1)->where(fn($q)=>$q->whereNull('effective_from')->orWhere('effective_from','<=',$now))->where(fn($q)=>$q->whereNull('effective_until')->orWhere('effective_until','>=',$now))->where(fn($q)=>$q->whereNull('event_type')->orWhere('event_type',$eventType))->where(fn($q)=>$q->whereNull('sales_channel')->orWhere('sales_channel',$channel))->where(function($q)use($organizerId,$eventId){$q->where('scope_type','global')->orWhere(fn($x)=>$x->where('scope_type','organizer')->where('scope_id',$organizerId))->orWhere(fn($x)=>$x->where('scope_type','event')->where('scope_id',$eventId));})->get();
  return $rules->sortByDesc(fn($r)=>($r->scope_type==='event'?3000000:($r->scope_type==='organizer'?2000000:1000000))+($r->event_type?100000:0)+($r->sales_channel?10000:0)+(int)$r->priority)->first();
 }
}