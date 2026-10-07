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
  $this->assertSellable($event,$channel);
  $perProduct=[];
  foreach($items as $item){
   $qty=(int)$item['quantity'];
   $passQuote=null;
   if(!empty($item['pass_product_id'])){
    $passQuote=app(EventPassService::class)->quote($eventId,['pass_product_id'=>$item['pass_product_id'],'event_date_ids'=>$item['event_date_ids']??[]],$qty,$channel);
    $item['ticket_id']=$passQuote['ticket_id'];
   }
   $ticket=Ticket::where('event_id',$eventId)->whereKey($item['ticket_id'])->firstOrFail(); if($qty<1) throw ValidationException::withMessages(['items'=>'Ticket quantity must be at least 1.']);
   if($ticket->ticket_available_type==='limited' && (int)$ticket->ticket_available<$qty) throw ValidationException::withMessages(['items'=>'Requested ticket quantity is no longer available.']);
   $seat=null;
   if(!$passQuote && !empty($item['seat_id'])){ $seat=$this->seatLine($ticket,$item); $qty=1; }
   if($passQuote) $unit=$passQuote['unit_price']/100;
   elseif($seat) $unit=$seat['price'];
   elseif($ticket->pricing_type==='free') $unit=0;
   elseif($ticket->pricing_type==='normal') $unit=(float)$ticket->price;
   else {
    $name=(string)($item['variation']??''); $vars=json_decode($ticket->variations,true)?:[];
    $variant=collect($vars)->first(fn($v)=>(string)($v['name']??'')===$name);
    if(!$variant) throw ValidationException::withMessages(['items'=>'Invalid ticket variation.']);
    if(($variant['ticket_available_type']??null)==='limited' && (int)($variant['ticket_available']??0)<$qty) throw ValidationException::withMessages(['items'=>'Requested ticket variation is no longer available.']);
    $unit=(float)$variant['price'];
   }
   // Max per order is enforced per ticket (or variation) across all lines of this order.
   $limitKey=$ticket->id.'|'.($passQuote ? 'pass:'.$passQuote['pass_product_id'] : (string)($item['variation']??''));
   $perProduct[$limitKey]=($perProduct[$limitKey]??0)+$qty;
   if(!$passQuote) $this->assertMaxPerOrder($ticket,$item['variation']??null,$perProduct[$limitKey]);
   $line=(int)round($unit*100)*$qty; $lineDiscount=0;
   if($ticket->early_bird_discount==='enable' && $ticket->early_bird_discount_date && $ticket->early_bird_discount_time){
    $deadline=Carbon::parse($ticket->early_bird_discount_date.' '.$ticket->early_bird_discount_time);
    if(now()->lte($deadline)){
     $d=(float)$ticket->early_bird_discount_amount;
     $lineDiscount=$ticket->early_bird_discount_type==='percentage' ? (int)round($line*$d/100) : (int)round($d*100)*$qty;
    }
   }
   $subtotal+=$line; $discount+=min($line,$lineDiscount); $quantity+=$qty;
   $snapshot[]=['ticket_id'=>$ticket->id,'variation'=>$item['variation']??null,'quantity'=>$qty,'unit_price'=>(int)round($unit*100),'line_total'=>$line,'discount'=>min($line,$lineDiscount),'pass_product_id'=>$passQuote['pass_product_id']??null,'pass_name'=>$passQuote['pass_name']??null,'pass_type'=>$passQuote['pass_type']??null,'event_date_ids'=>$passQuote['event_date_ids']??[],'admissions_per_holder'=>$passQuote['admissions_per_holder']??1]+($seat?['seat_id'=>$seat['seat_id'],'seat_name'=>$seat['seat_name'],'slot_id'=>$seat['slot_id'],'slot_name'=>$seat['slot_name'],'slot_unique_id'=>$seat['slot_unique_id']]:[]);
  }
  return ['event'=>$event,'items'=>$snapshot,'quantity'=>$quantity,'subtotal'=>$subtotal,'discount'=>$discount,'ticket_amount'=>max(0,$subtotal-$discount)];
 }
 /**
  * A seat-map line: the seat must belong to this ticket's map, be active and unsold.
  * Price comes from the map (per-seat price for seat slots, slot price for area slots).
  */
 public function seatLine(Ticket $ticket,array $item): array {
  $slot=\App\Models\Event\Slot::whereKey((int)($item['slot_id']??0))->where('event_id',$ticket->event_id)->where('ticket_id',$ticket->id)->first();
  $seat=$slot?\App\Models\Event\SlotSeats::whereKey((int)$item['seat_id'])->where('slot_id',$slot->id)->first():null;
  if(!$slot||!$seat||(int)$slot->is_deactive===1||(int)$seat->is_deactive===1) throw ValidationException::withMessages(['items'=>'The selected seat is not available.']);
  if(in_array((int)$seat->id,array_map('intval',app(\App\Services\BookingServices::class)->getBookedSlot($ticket->event_id)['seat_ids']??[]),true)) throw ValidationException::withMessages(['items'=>'Seat '.$seat->name.' has just been booked by someone else.']);
  $price=$ticket->pricing_type==='free'?0:(float)((int)$slot->type===1?$seat->price:$slot->price);
  return ['seat_id'=>(int)$seat->id,'seat_name'=>$seat->name,'slot_id'=>(int)$slot->id,'slot_name'=>$slot->name,'slot_unique_id'=>(int)$slot->slot_unique_id,'price'=>$price];
 }
 /** Events must be published (online channels) and not yet ended. POS may sell unpublished, counter-only events. */
 private function assertSellable(Event $event,string $channel): void {
  $online=!in_array($channel,['pos','box_office'],true);
  if($online && (string)$event->status!=='1') throw ValidationException::withMessages(['event'=>'This event is not available for booking.']);
  if($event->end_date_time && \App\Support\BusinessTime::parse((string)$event->end_date_time)->lt(\App\Support\BusinessTime::now())) throw ValidationException::withMessages(['event'=>'This event has ended.']);
 }
 private function assertMaxPerOrder(Ticket $ticket,?string $variation,int $qty): void {
  $max=null;
  if($ticket->pricing_type==='variation'){
   $v=collect(json_decode($ticket->variations,true)?:[])->first(fn($x)=>(string)($x['name']??'')===(string)$variation);
   if($v && ($v['max_ticket_buy_type']??null)==='limited' && (int)($v['v_max_ticket_buy']??0)>0) $max=(int)$v['v_max_ticket_buy'];
  } elseif($ticket->max_ticket_buy_type==='limited' && (int)$ticket->max_buy_ticket>0) $max=(int)$ticket->max_buy_ticket;
  if($max!==null && $qty>$max) throw ValidationException::withMessages(['items'=>"You can buy at most {$max} of this ticket per order."]);
 }
}
