<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Event\EventContent;
use App\Models\Event\Ticket;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Event\TicketContent;
use App\Services\Events\EventPassService;
use App\Models\Language;
use Carbon\Carbon;

class CheckOutController extends Controller
{
  //checkout
  public function checkout2(Request $request)
  {

    if ($request->filled('pass_product_id')) {
      $basic = Basic::select('event_guest_checkout_status')->first();
      if ((int) optional($basic)->event_guest_checkout_status !== 1 && !Auth::guard('customer')->check()) return redirect()->route('customer.login',['redirectPath'=>'event_checkout']);
      $qty=(int)$request->input('pass_quantity',1);
      if($qty<1) return back()->with(['alert-type'=>'error','message'=>'Please select at least one pass.']);
      $quote=app(EventPassService::class)->quote((int)$request->event_id,['pass_product_id'=>(int)$request->pass_product_id,'event_date_ids'=>(array)$request->input('pass_event_date_ids',[])],$qty,'web');
      $request->merge(['quantity'=>[$qty]]);
      Session::put('selTickets',[['ticket_id'=>$quote['ticket_id'],'name'=>$quote['pass_name'],'qty'=>$qty,'price'=>$quote['unit_price']/100,'early_bird_dicount'=>0,'pass_product_id'=>$quote['pass_product_id'],'event_date_ids'=>$quote['event_date_ids'],'pass_type'=>$quote['pass_type']]]);
      Session::put('total',$quote['line_total']/100);Session::put('sub_total',$quote['line_total']/100);Session::put('quantity',$qty);Session::put('total_early_bird_dicount',0);Session::put('discount',null);
      $event=EventContent::join('events','events.id','event_contents.event_id')->where('events.id',$request->event_id)->select('events.*','event_contents.title','event_contents.slug','event_contents.city','event_contents.address','event_contents.country')->firstOrFail();Session::put('event',$event);Session::put('event_date',implode(',',$quote['event_date_ids']));Session::put('online_gateways',OnlineGateway::where('status',1)->where('keyword','razorpay')->get());Session::put('offline_gateways',OfflineGateway::where('status',1)->orderBy('serial_number','asc')->get());
      if(!Auth::guard('customer')->check() && (int) optional($basic)->event_guest_checkout_status !== 1) return redirect()->route('customer.login',['redirectPath'=>'event_checkout']);
      return redirect()->route('check-out');
    }

    $basic = Basic::select('event_guest_checkout_status')->first();
    $event_guest_checkout_status = $basic->event_guest_checkout_status;
    if ($event_guest_checkout_status != 1) {
      if (!Auth::guard('customer')->user()) {
        return redirect()->route('customer.login', ['redirectPath' => 'event_checkout']);
      }
    }
    //selected seats
    $selected_seats = !empty($request->seatData) ? json_decode($request->seatData, true) : [];


    $selected_slot_seat = collect($selected_seats)
      ->groupBy('slot_id')
      ->map(function ($group) {
        $first = $group->first();
        return [
          'slot_id' => $first['slot_id'],
          'slot_name' => $first['slot_name'],
          'event_id' => $first['event_id'],
          'ticket_id' => $first['ticket_id'],
          'slot_unique_id' => $first['slot_unique_id'],
          'slot_type' => $first['s_type'],
          'seats' => collect($group)->map(function ($seat) {
            return [
              'seat_id' => $seat['id'],
              'seat_name' => $seat['name'],
              'discount' => $seat['discount'],
              'price' => $seat['price'],
              'payable_price' => $seat['payable_price'],
            ];
          })->values()->toArray(),
        ];
      })
      ->map(function ($slot) {
        // count and sum for each slot
        $slot['seat_count'] = count($slot['seats']);
        $slot['seats_price'] = collect($slot['seats'])->sum('payable_price');
        return $slot;
      })
      ->values()
      ->toArray();

    $select = false;
    $event_type = Event::where('id', $request->event_id)->select('event_type')->first();

    if ($event_type->event_type == 'venue') {
      foreach ($request->quantity as $qty) {
        if ($qty > 0) {
          $select = true;
          break;
        }
        continue;
      }

      //slot validation for variations wise seats
      if (count($selected_slot_seat) > 0) {
        $select = true;
      }
    } else {
      if ($request->pricing_type == 'free') {
        $select = true;
        //free ticket validation
        if (count($selected_slot_seat) > 0) {
          $select = true;
        }
      } elseif ($request->pricing_type == 'normal') {
        if ($request->quantity == 0) {
          $select = false;
        } else {
          $select = true;
        }

        //when selected slot & seat pricing type normal
        if (count($selected_slot_seat) > 0) {
          $select = true;
        }
      } else {
        foreach ($request->quantity as $qty) {
          if ($qty > 0) {
            $select = true;
            break;
          }
          continue;
        }
      }
    }


    if ($select == false) {
      return back()->with(['alert-type' => 'error', 'message' => 'Please Select at least one ticket']);
    }
    $information = [];
    $information['selTickets'] = '';
    $event = Event::where('id', $request->event_id)->select('event_type', 'id')->first();

    $check = false;


    if ($event->event_type == 'online') {
      // Online events sell exactly one server-managed ticket. Price, free/paid status and
      // early-bird come from the database, never from the posted form.
      $onlineTicket = Ticket::where('event_id', $request->event_id)->orderBy('id')->first();
      $qty = (int) (is_array($request->quantity) ? array_sum(array_map('intval', $request->quantity)) : $request->quantity);
      if (!$onlineTicket || $qty < 1) {
        return back()->with(['alert-type' => 'error', 'message' => 'Please Select at least one ticket']);
      }
      if (StockCheck($request->event_id, $qty) == 'error') {
        return back()->with(['alert-type' => 'error', 'message' => __('Tickets are not available in the requested quantity.')]);
      }
      if ($event_guest_checkout_status != 1 && Auth::guard('customer')->check()) {
        $max_buy = isTicketPurchaseOnline($request->event_id, $onlineTicket->max_buy_ticket);
        if ($max_buy['status'] == 'true') $check = true;
      }
      $isFree = $onlineTicket->pricing_type === 'free';
      $unit = $isFree ? 0 : (float) $onlineTicket->price;
      $early_bird_dicount = 0;
      if (!$isFree && $onlineTicket->early_bird_discount == 'enable' && Carbon::now() <= Carbon::parse($onlineTicket->early_bird_discount_date . $onlineTicket->early_bird_discount_time)) {
        $early_bird_dicount = $onlineTicket->early_bird_discount_type == 'fixed'
          ? (float) $onlineTicket->early_bird_discount_amount
          : ($unit * (float) $onlineTicket->early_bird_discount_amount) / 100;
      }
      $total = $unit * $qty;
      Session::put('selTickets', [[
        'ticket_id' => $onlineTicket->id, 'name' => $onlineTicket->title ?: 'Ticket', 'qty' => $qty,
        'price' => $unit, 'early_bird_dicount' => $early_bird_dicount, 'type' => $isFree ? 'free' : 'normal',
      ]]);
      Session::put('total_early_bird_dicount', min($total, $early_bird_dicount * $qty));
      $information['quantity'] = $qty;
      $information['total'] = $total;
      Session::put('total', $total);
      Session::put('sub_total', $total);
      Session::put('quantity', $qty);
    } else {
      $tickets = Ticket::where('event_id', $request->event_id)->select('id', 'title', 'pricing_type', 'price', 'variations', 'early_bird_discount', 'early_bird_discount_amount', 'early_bird_discount_type', 'early_bird_discount_date', 'early_bird_discount_time', 'normal_ticket_slot_unique_id', 'normal_ticket_slot_enable', 'free_tickete_slot_enable', 'free_tickete_slot_unique_id')->get();
      $ticketArr = [];

      foreach ($tickets as $key => $ticket) {
        if ($ticket->pricing_type == 'variation') {
          $varArr1 = json_decode($ticket->variations, true);
          foreach ($varArr1 as $key => $var1) {
            $stock[] = [
              'name' => $var1['name'],
              'price' => $var1['price'],
              'ticket_available' => $var1['ticket_available'] - $request->quantity[$key],
            ];
            //check early_bird discount
            if ($ticket->early_bird_discount == 'enable') {
              $start = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              $end = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              $today = Carbon::now();
              if ($today <= ($end)) {
                if ($ticket->early_bird_discount_type == 'fixed') {
                  $early_bird_dicount = $ticket->early_bird_discount_amount;
                } else {
                  $early_bird_dicount = ($var1['price'] * $ticket->early_bird_discount_amount) / 100;
                }
              } else {
                $early_bird_dicount = 0;
              }
            } else {
              $early_bird_dicount = 0;
            }

            $var1['type'] = $ticket->pricing_type;
            $var1['early_bird_dicount'] = $early_bird_dicount;
            $var1['ticket_id'] = $ticket->id;
            $ticketArr[] = $var1;
          }
          Session::put('stock', $stock);
        } elseif ($ticket->pricing_type == 'normal') {

          //check early_bird discount
          if ($ticket->early_bird_discount == 'enable') {

            $start = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
            $end = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
            $today = Carbon::now();
            if ($today <= ($end)) {
              if ($ticket->early_bird_discount_type == 'fixed') {
                $early_bird_dicount = $ticket->early_bird_discount_amount;
              } else {
                $early_bird_dicount = ($ticket->price * $ticket->early_bird_discount_amount) / 100;
              }
            } else {
              $early_bird_dicount = 0;
            }
          } else {
            $early_bird_dicount = 0;
          }

          $language = Language::where('is_default', 1)->first();

          $ticketContent = TicketContent::where([['ticket_id', $ticket->id], ['language_id', $language->id]])->first();
          if (empty($ticketContent)) {
            $ticketContent = TicketContent::where('ticket_id', $ticket->id)->first();
          }

          $ticketArr[] = [
            'ticket_id' => $ticket->id,
            'early_bird_dicount' => $early_bird_dicount,
            'name' => optional($ticketContent)->title ?: ($ticket->title ?: 'Ticket'),
            'price' => $ticket->price,
            'type' => $ticket->pricing_type,
            'slot_unique_id' => (int) $ticket->normal_ticket_slot_unique_id
          ];
        } elseif ($ticket->pricing_type == 'free') {
          $language = Language::where('is_default', 1)->first();
          $ticketContent = TicketContent::where([['ticket_id', $ticket->id], ['language_id', $language->id]])->first();
          if (empty($ticketContent)) {
            $ticketContent = TicketContent::where('ticket_id', $ticket->id)->first();
          }
          $ticketArr[] = [
            'ticket_id' => $ticket->id,
            'early_bird_dicount' => 0,
            'name' => optional($ticketContent)->title ?: ($ticket->title ?: 'Ticket'),
            'price' => 0,
            'type' => $ticket->pricing_type,
            'slot_unique_id' => (int) $ticket->free_tickete_slot_unique_id
          ];
        }
      }

      $selTickets = [];
      foreach ($request->quantity as $key => $qty) {
        if ($qty > 0) {
          $selTickets[] = [
            'ticket_id' => $ticketArr[$key]['ticket_id'],
            'early_bird_dicount' => $qty * $ticketArr[$key]['early_bird_dicount'],
            'name' => $ticketArr[$key]['name'],
            'qty' => $qty,
            'price' => $ticketArr[$key]['price'],
          ];
        }
      }

      $sub_total = 0;
      $total_ticket = 0;
      $total_early_bird_dicount = 0;
      foreach ($selTickets as $key => $var) {
        $sub_total += $var['price'] * $var['qty'];
        $total_ticket += $var['qty'];
        $total_early_bird_dicount += $var['early_bird_dicount'];
      }


      //stock check
      foreach ($selTickets as $selTicket) {
        $stock = TicketStockCheck($selTicket['ticket_id'], $selTicket['qty'], $selTicket['name']);
        if ($stock == 'error') {
          $check = true;
          break;
        }
        //check guest checkout status enable or not
        if ($event_guest_checkout_status != 1) {
          $check_v = isTicketPurchaseVenueBackend($request->event_id, $selTicket['ticket_id'], $selTicket['name']);
          if ($check_v['status'] == 'true') {
            $check = true;
            break;
          }
        }
      }


      //check existins bookings seat or slot ids
      if (count($selected_slot_seat) > 0) {
        $check = $this->slotBookedDeactiveCheck($selected_slot_seat, $request->event_id);
      }


      $ticketArr =  collect($ticketArr)->map(function ($ticket) {
        $ticket['slot_unique_id'] = (int) $ticket['slot_unique_id'];
        return $ticket;
      })->toArray();


      $slotGroups = collect($ticketArr)
        ->groupBy('slot_unique_id')
        ->map(fn($items) => $items[0])
        ->toArray();



      $seat_total_amount = 0;
      $seat_early_bird_discount = 0;
      $seat_sub_total = 0;
      $seat_total_ticket = 0;
      foreach ($selected_seats as $seat) {
        $seat_total_ticket += 1;
        $slot_unique_id = (int) $seat['slot_unique_id'];
        $seat_total_amount += $seat['price'];
        $seat_sub_total += $seat['price'];
        $seat_early_bird_discount += $seat['discount'];
        $selTickets[] = [
          'ticket_id' => $seat['ticket_id'],
          'early_bird_dicount' => $seat['discount'],
          'name' => array_key_exists($slot_unique_id, $slotGroups) ? $slotGroups[$slot_unique_id]['name'] : "",
          'qty' => 1,
          'price' => $seat['price'],
          'discount' => $seat['discount'],
          'payable_price' => $seat['payable_price'],
          'seat_id' => $seat['id'],
          'seat_name' => $seat['name'],
          'slot_id' => $seat['slot_id'],
          'slot_name' => $seat['slot_name'],
          'slot_unique_id' => $slot_unique_id,
          'ticket_id' => $seat['ticket_id'],
          'event_id' => $seat['event_id'],
          's_type' => $seat['s_type'],
        ];
      }

      $total_early_bird_dicount +=  $seat_early_bird_discount;
      $sub_total += $seat_sub_total;

      $total = $sub_total - $total_early_bird_dicount;
      $total_ticket += $seat_total_ticket;

      Session::put('total', round($total, 2));
      Session::put('sub_total', round($sub_total, 2));
      Session::put('quantity', $total_ticket);
      Session::put('selTickets', $selTickets);
      Session::put('discount', NULL);
      Session::put('total_early_bird_dicount', NULL);
      Session::put('total_early_bird_dicount', round($total_early_bird_dicount, 2));
    }

    if ($check == true) {
      $notification = array('message' => 'Something went wrong..!', 'alert-type' => 'error');
      return back()->with($notification);
    }

    $event =  EventContent::join('events', 'events.id', 'event_contents.event_id')
      ->where('events.id', $request->event_id)
      ->select('events.*', 'event_contents.title', 'event_contents.slug', 'event_contents.city', 'event_contents.address', 'event_contents.country')
      ->first();

    Session::put('event', $event);
    // Event checkout runs on Payments V2 (Razorpay) only; other legacy gateways are not offered.
    $online_gateways = OnlineGateway::where('status', 1)->where('keyword', 'razorpay')->get();
    $offline_gateways = OfflineGateway::where('status', 1)->orderBy('serial_number', 'asc')->get();
    Session::put('online_gateways', $online_gateways);
    Session::put('offline_gateways', $offline_gateways);
    Session::put('event_date', $request->event_date);
    //check customer logged in or not ?
    if (Auth::guard('customer')->check() == false) {
      return redirect()->route('customer.login', ['redirectPath' => 'event_checkout']);
    }
    return redirect()->route('check-out');
  }
  public function checkout()
  {
    if (!Session::has('event')) {
      return redirect()->route('index');
    }
    $information['selTickets'] = Session::get('selTickets');
    $information['total'] = Session::get('total');
    $information['quantity'] = Session::get('quantity');
    $information['total_early_bird_dicount'] = Session::get('total_early_bird_dicount');
    $information['event'] = Session::get('event');
    $information['online_gateways'] = Session::get('online_gateways');
    $information['offline_gateways'] = Session::get('offline_gateways');
    $information['basicData'] = Basic::select('tax')->first();
    $stripe = OnlineGateway::where('keyword', 'stripe')->first();
    $stripe_info = json_decode($stripe->information, true);
    $information['stripe_key'] = $stripe_info['key'];
    return view('frontend.check-out', $information);
  }

  public function slotBookedDeactiveCheck($selectedSlotSeat, $event_id): bool
  {
    $check =  app(\App\Services\BookingServices::class)->checkBookingAndDeactiveSlotSeat($selectedSlotSeat, $event_id);
    return $check;
  }
}
