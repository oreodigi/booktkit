<?php
namespace App\Services\Payments;
use App\Models\Event;
use App\Models\Event\Ticket;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
class AuthoritativeTicketPricingService {
 public function quote(int $eventId,array $items): array {
  $event=Event::findOrFail($eventId); $subtotal=0; $discount=0; $quantity=0; $snapshot=[];
  foreach($items as $item){
   $ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->firstOrFail();
   $qty=(int)$item['quantity']; if($qty<1) throw ValidationException::withMessages(['items'=>'Ticket quantity must be at least 1.']);
   if($ticket->ticket_available_type==='limited' && (int)$ticket->ticket_available<$qty) throw ValidationException::withMessages(['items'=>'Requested ticket quantity is no longer available.']);
   if($ticket->pricing_type==='free') $unit=0;
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
   $snapshot[]=['ticket_id'=>$ticket->id,'variation'=>$item['variation']??null,'quantity'=>$qty,'unit_price'=>(int)round($unit*100),'line_total'=>$line,'discount'=>min($line,$lineDiscount)];
  }
  return ['event'=>$event,'items'=>$snapshot,'quantity'=>$quantity,'subtotal'=>$subtotal,'discount'=>$discount,'ticket_amount'=>max(0,$subtotal-$discount)];
 }
}