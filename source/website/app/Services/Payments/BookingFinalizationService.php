<?php
namespace App\Services\Payments;
use App\Models\BasicSettings\Basic;
use App\Models\Event\Booking;
use App\Models\Event\Ticket;
use App\Models\Payments\PaymentOrder;
use App\Services\Tickets\TicketIssuanceService;
use App\Services\Tickets\TicketDeliveryService;
use App\Services\Events\EventPassService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class BookingFinalizationService {
 public function finalize(PaymentOrder $order): Booking {
  if($order->booking_id) return Booking::findOrFail($order->booking_id);
  $customer=$order->customer_snapshot ?: [];
  $pricing=$order->pricing_snapshot ?: [];
  $event=$order->event_id;
  $variations=[]; $quantity=0;
  foreach(($pricing['items']??[]) as $item){
   $ticket=Ticket::where('event_id',$event)->lockForUpdate()->findOrFail($item['ticket_id']);
   $qty=(int)$item['quantity']; $quantity+=$qty;
   if(!empty($item['pass_product_id'])) app(EventPassService::class)->consume((int)$item['pass_product_id'],$qty);
   if($ticket->pricing_type==='variation'){
    $all=json_decode($ticket->variations,true)?:[]; $found=false;
    foreach($all as &$v) if((string)($v['name']??'')===(string)($item['variation']??'')){
     $found=true;
     if(($v['ticket_available_type']??null)==='limited'){
      if((int)$v['ticket_available']<$qty) throw ValidationException::withMessages(['tickets'=>'Ticket stock changed before payment completion.']);
      $v['ticket_available']=(int)$v['ticket_available']-$qty;
     }
    }
    unset($v); if(!$found) throw ValidationException::withMessages(['tickets'=>'Ticket variation no longer exists.']);
    $ticket->variations=json_encode($all); $ticket->save();
   } elseif($ticket->ticket_available_type==='limited'){
    if((int)$ticket->ticket_available<$qty) throw ValidationException::withMessages(['tickets'=>'Ticket stock changed before payment completion.']);
    $ticket->ticket_available=(int)$ticket->ticket_available-$qty; $ticket->save();
   }
   for($i=0;$i<$qty;$i++) $variations[]=['ticket_id'=>$ticket->id,'early_bird_dicount'=>($item['discount']??0)/100/max(1,$qty),
    'name'=>$item['pass_name'] ?: ($item['variation'] ?: ($ticket->title ?: 'Ticket')),'qty'=>1,'price'=>($item['unit_price']??0)/100,'scan_status'=>0,'unique_id'=>uniqid(),'pass_product_id'=>$item['pass_product_id']??null,'pass_type'=>$item['pass_type']??null,'event_date_ids'=>$item['event_date_ids']??[],'admissions_per_holder'=>$item['admissions_per_holder']??1];
  }
  $basic=Basic::where('uniqid',12345)->first() ?: Basic::first();
  $booking=Booking::create([
   'customer_id'=>$customer['customer_id']??'guest','booking_id'=>uniqid(),'fname'=>$customer['fname']??'','lname'=>$customer['lname']??'',
   'email'=>$customer['email']??'','phone'=>$customer['phone']??'','country'=>$customer['country']??'','state'=>$customer['state']??null,
   'city'=>$customer['city']??null,'zip_code'=>$customer['zip_code']??null,'address'=>$customer['address']??'','event_id'=>$event,
   'organizer_id'=>$order->organizer_id,'variation'=>json_encode($variations),'price'=>$order->ticket_amount/100,'tax'=>$order->tax_amount/100,
   'commission'=>$order->platform_fee/100,'tax_percentage'=>$pricing['tax_rate']??0,'commission_percentage'=>0,'quantity'=>$quantity,
   'discount'=>($pricing['discount']??0)/100,'early_bird_discount'=>($pricing['discount']??0)/100,'currencyText'=>$order->currency,
   'currencyTextPosition'=>'right','currencySymbol'=>'₹','currencySymbolPosition'=>'left','paymentMethod'=>'Razorpay','gatewayType'=>'online',
   'paymentStatus'=>'completed','event_date'=>$customer['event_date']??now()->toDateString(),'fcm_token'=>$customer['fcm_token']??null
  ]);
  $order->booking_id=$booking->id; $order->save();
  app(TicketIssuanceService::class)->ensureForBooking($booking);
  DB::afterCommit(function () use ($booking) {
   app(TicketDeliveryService::class)->deliver($booking->fresh());
  });
  return $booking;
 }
}