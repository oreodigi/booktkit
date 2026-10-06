<?php
namespace App\Services\Payments;
use App\Models\Payments\EventAdditionalFee;
class AdditionalFeeCalculator{
 public function calculate(int $eventId,string $channel,int $ticket,int $quantity,array $selected=[]):array{$lines=[];$customer=0;$organizer=0;
  foreach(EventAdditionalFee::where('event_id',$eventId)->where('is_active',1)->get() as $f){$meta=$f->metadata?:[];$channels=$meta['sales_channels']??[];if($channels&&!in_array($channel,$channels,true))continue;if(!$f->is_mandatory&&!in_array($f->code,$selected,true))continue;$qty=$f->application==='per_ticket'?$quantity:1;$amount=$f->calculation_type==='percentage'?(int)round($ticket*(float)$f->percentage/100):(int)$f->fixed_amount*$qty;if($amount<=0)continue;$lines[]=['additional_fee_id'=>$f->id,'code'=>$f->code,'name'=>$f->name,'category'=>'additional','bearer'=>$f->fee_bearer,'quantity'=>$qty,'amount'=>$amount,'calculation_snapshot'=>$f->toArray()];if($f->fee_bearer==='customer')$customer+=$amount;else $organizer+=$amount;}
  return ['lines'=>$lines,'total'=>$customer+$organizer,'customer_total'=>$customer,'organizer_total'=>$organizer];}
}