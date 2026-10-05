<?php
namespace App\Services\BoxOffice;
use App\Models\Event\Ticket;use Illuminate\Validation\ValidationException;
class LockedTicketInventoryService{
 public function reserve(int $eventId,array $items):array{$locked=[];foreach($items as $item){$ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->lockForUpdate()->firstOrFail();$qty=(int)$item['quantity'];if($qty<1)throw ValidationException::withMessages(['items'=>'Quantity must be at least 1.']);if($ticket->ticket_available_type==='limited'){if((int)$ticket->ticket_available<$qty)throw ValidationException::withMessages(['items'=>'Ticket inventory changed. Please refresh.']);$ticket->decrement('ticket_available',$qty);}$locked[]=$ticket;}return $locked;}
 public function restore(int $eventId,array $items):void{foreach($items as $item){$ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->lockForUpdate()->first();if($ticket&&$ticket->ticket_available_type==='limited')$ticket->increment('ticket_available',(int)$item['quantity']);}}
}