<?php
namespace App\Services\Payments;
use App\Models\Payments\EventAdditionalFee;
class AdditionalFeeCalculator{
 public function calculate(int $eventId,string $channel,int $ticketAmountPaise,int $quantity,array $selected=[]):array{
  $lines=[];$customer=0;$organizer=0;
  $fees=EventAdditionalFee::where('event_id',$eventId)->where('enabled',1)->get();
  foreach($fees as $fee){$channels=$fee->sales_channels?:[];if($channels&&!in_array($channel,$channels,true))continue;if(!$fee->mandatory&&!in_array($fee->code,$selected,true))continue;
   $qty=$fee->calculation_type==='fixed_per_ticket'?$quantity:1;
   $amount=$fee->calculation_type==='percentage'?(int)round($ticketAmountPaise*(float)$fee->percentage/100):(int)$fee->fixed_amount*$qty;
   if($amount<=0)continue;$lines[]=['additional_fee_id'=>$fee->id,'code'=>$fee->code,'name'=>$fee->name,'category'=>'additional','calculation_type'=>$fee->calculation_type,'bearer'=>$fee->fee_bearer,'quantity'=>$qty,'base_amount'=>$ticketAmountPaise,'amount'=>$amount,'snapshot'=>$fee->toArray()];
   if($fee->fee_bearer==='customer')$customer+=$amount;else $organizer+=$amount;
  }return ['lines'=>$lines,'total'=>$customer+$organizer,'customer_total'=>$customer,'organizer_total'=>$organizer];
 }
}