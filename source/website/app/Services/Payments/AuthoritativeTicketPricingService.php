<?php
namespace App\Services\Payments;
use App\Models\Event;
use App\Models\Event\Ticket;
use Carbon\Carbon;
use App\Services\Events\EventPassService;
use Illuminate\Validation\ValidationException;
class AuthoritativeTicketPricingService {
 public function quote(int $eventId,array $items,string $channel='web'): array {
  $event=Event::findOrFail($eventId); $subtotal=0; $discount=0; $quantity=0; $snapshot=[];
  foreach($items as $item){
   $qty=(int)$item['quantity'];
   $passQuote=null;
   if(!empty($item['pass_product_id'])){
    $passQuote=app(EventPassService::class)->quote($eventId,['pass_product_id'=>$item['pass_product_id'],'event_date_ids'=>$item['event_date_ids']??[]],$qty,$channel);
    $item['ticket_id']=$passQuote['ticket_id'];
   }
   $ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->firstOrFail(); if($qty<1) throw ValidationException::withMessages(['items'=>'Ticket quantity must be at least 1.']);
   if($ticket->ticket_available_type==='limited' && (int)$ticket->ticket_available<$qty) throw ValidationException::withMessages(['items'=>'Requested ticket quantity is no longer available.']);
   if($passQuote) $unit=$passQuote['unit_price']/100;
   elseif($ticket->pricing_type==='free') $unit=0;
   elseif($ticket->pricing_type==='normal') $unit=(float)$ticket->price;
   else {
    $name=(string)($item['variation']??''); $vars=json_decode($ticket->variations,true)?:[];
    $variant=collect($vars)->first(fn($v)=>(string)($v['name']??'')===$name);
    if(!$variant) throw ValidationException::withMessages(['items'=>'Invalid ticket variation.']);
    if(($variant['ticket_available_type']??null)==='limited' && (int)($variant['ticket_available']??0)<$qty) throw ValidationException::withMessages(['items'=>'Requested ticket variation is no longer available.']);
    $unit=(float)$variant['price'];
   }
   $line=(int)round($unit*100)*$qty; $lineDiscount=0;
   if($ticket->early_bird_discount==='enable' && $ticket->early_bird_discount_date && $ticket->early_bird_discount_time){
    $deadline=Carbon::parse($ticket->early_bird_discount_date.' '.$ticket->early_bird_discount_time);
    if(now()->lte($deadline)){
     $d=(float)$ticket->early_bird_discount_amount;
     $lineDiscount=$ticket->early_bird_discount_type==='percentage' ? (int)round($line*$d/100) : (int)round($d*100)*$qty;
    }
   }
   $subtotal+=$line; $discount+=min($line,$lineDiscount); $quantity+=$qty;
   $snapshot[]=['ticket_id'=>$ticket->id,'variation'=>$item['variation']??null,'quantity'=>$qty,'unit_price'=>(int)round($unit*100),'line_total'=>$line,'discount'=>min($line,$lineDiscount),'pass_product_id'=>$passQuote['pass_product_id']??null,'pass_name'=>$passQuote['pass_name']??null,'pass_type'=>$passQuote['pass_type']??null,'event_date_ids'=>$passQuote['event_date_ids']??[],'admissions_per_holder'=>$passQuote['admissions_per_holder']??1];
  }
  return ['event'=>$event,'items'=>$snapshot,'quantity'=>$quantity,'subtotal'=>$subtotal,'discount'=>$discount,'ticket_amount'=>max(0,$subtotal-$discount)];
 }
}