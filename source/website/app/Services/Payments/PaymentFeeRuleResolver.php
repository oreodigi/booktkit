<?php
namespace App\Services\Payments;
use App\Models\Payments\PaymentFeeRule;
class PaymentFeeRuleResolver{
 public function resolve(?int $organizerId,int $eventId,?string $eventType,string $channel):?PaymentFeeRule{
  $rules=PaymentFeeRule::query()->where('is_active',1)->where(fn($q)=>$q->where('sales_channel','any')->orWhere('sales_channel',$channel))->where(function($q)use($organizerId,$eventId,$eventType){$q->where('scope','global')->orWhere(fn($x)=>$x->where('scope','event_type')->where('event_type',$eventType))->orWhere(fn($x)=>$x->where('scope','organizer')->where('organizer_id',$organizerId))->orWhere(fn($x)=>$x->where('scope','event')->where('event_id',$eventId));})->get();
  return $rules->sortByDesc(fn($r)=>($r->scope==='event'?4000000:($r->scope==='organizer'?3000000:($r->scope==='event_type'?2000000:1000000)))+($r->sales_channel!=='any'?10000:0)+(int)$r->priority)->first();
 }
}