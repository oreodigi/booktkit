<?php
namespace App\Services\BoxOffice;
use App\Models\Event\Ticket;
use Illuminate\Validation\ValidationException;
class LockedTicketInventoryService{
 public function reserve(int $eventId,array $items):array{
  $locked=[];
  foreach($items as $item){
   $ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->lockForUpdate()->firstOrFail();
   $qty=(int)$item['quantity']; if($qty<1)throw ValidationException::withMessages(['items'=>'Quantity must be at least 1.']);
   $variation=(string)($item['variation']??'');
   if($ticket->pricing_type==='variation'){
    $vars=json_decode($ticket->variations,true)?:[];$found=false;
    foreach($vars as &$v){if((string)($v['name']??'')!==$variation)continue;$found=true;if(($v['ticket_available_type']??null)==='limited'){if((int)($v['ticket_available']??0)<$qty)throw ValidationException::withMessages(['items'=>'Ticket variation inventory changed. Please refresh.']);$v['ticket_available']=(int)$v['ticket_available']-$qty;}break;}unset($v);
    if(!$found)throw ValidationException::withMessages(['items'=>'Invalid ticket variation.']);
    $ticket->variations=json_encode($vars);$ticket->save();
   }elseif($ticket->ticket_available_type==='limited'){
    if((int)$ticket->ticket_available<$qty)throw ValidationException::withMessages(['items'=>'Ticket inventory changed. Please refresh.']);$ticket->decrement('ticket_available',$qty);
   }
   $locked[]=$ticket;
  }return $locked;
 }
 public function restore(int $eventId,array $items):void{
  foreach($items as $item){$ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->lockForUpdate()->first();if(!$ticket)continue;$qty=(int)$item['quantity'];$variation=(string)($item['variation']??'');
   if($ticket->pricing_type==='variation'){$vars=json_decode($ticket->variations,true)?:[];foreach($vars as &$v){if((string)($v['name']??'')===$variation&&($v['ticket_available_type']??null)==='limited'){$v['ticket_available']=(int)($v['ticket_available']??0)+$qty;break;}}unset($v);$ticket->variations=json_encode($vars);$ticket->save();}
   elseif($ticket->ticket_available_type==='limited')$ticket->increment('ticket_available',$qty);
  }
 }
}