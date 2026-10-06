<?php
namespace App\Services\Payments;use App\Models\Payments\PaymentFeeRule;
class PaymentFeeRuleResolver{public function resolve(?int $organizerId,int $eventId,?string $eventType,string $channel):?PaymentFeeRule{
 $rules=PaymentFeeRule::query()->where('is_active',1)->where(fn($q)=>$q->where('sales_channel','any')->orWhere('sales_channel',$channel))->where(fn($q)=>$q->whereNull('event_type')->orWhere('event_type',$eventType))->where(function($q)use($organizerId,$eventId){$q->where('scope','global')->orWhere('scope','event_type')->orWhere(fn($x)=>$x->where('scope','organizer')->where('organizer_id',$organizerId))->orWhere(fn($x)=>$x->where('scope','event')->where('event_id',$eventId));})->get();
 return $rules->sortByDesc(fn($r)=>($r->scope==='event'?4000000:($r->scope==='organizer'?3000000:($r->scope==='event_type'?2000000:1000000)))+($r->event_type?100000:0)+($r->sales_channel!=='any'?10000:0)+(int)$r->priority)->first();
}}