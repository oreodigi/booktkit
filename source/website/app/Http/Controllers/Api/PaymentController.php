<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Services\Payments\PaymentOrderService;
use App\Services\Payments\RazorpayRouteService;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Payments\AuthoritativeTicketPricingService;
use App\Services\Payments\BookingFinalizationService;
use App\Jobs\Payments\TransferOrganizerPayment;
use App\Services\Payments\PaymentCaptureService;
class PaymentController extends Controller {
 public function createRazorpayOrder(Request $r, PaymentOrderService $orders, RazorpayRouteService $razorpay, AuthoritativeTicketPricingService $pricing){
  $data=$r->validate([
   'event_id'=>'required|integer|exists:events,id',
   'items'=>'required|array|min:1',
   'items.*.ticket_id'=>'required|integer',
   'items.*.quantity'=>'required|integer|min:1|max:50',
   'items.*.variation'=>'nullable|string|max:255',
   'items.*.pass_product_id'=>'nullable|integer',
   'items.*.event_date_ids'=>'nullable|array', 'items.*.event_date_ids.*'=>'integer',
   'idempotency_key'=>'required|string|max:100',
   'customer'=>'required|array', 'customer.fname'=>'required|string|max:100', 'customer.lname'=>'required|string|max:100',
   'customer.email'=>'required|email|max:190', 'customer.phone'=>'required|string|max:30', 'customer.country'=>'required|string|max:100',
   'customer.address'=>'required|string|max:500', 'customer.state'=>'nullable|string|max:100', 'customer.city'=>'nullable|string|max:100',
   'customer.zip_code'=>'nullable|string|max:30', 'customer.customer_id'=>'nullable', 'customer.event_date'=>'nullable|date', 'customer.fcm_token'=>'nullable|string|max:500'
  ]);
  $channel=$r->user() ? 'mobile' : 'web';
  $quote=$pricing->quote((int)$data['event_id'],$data['items'],$channel);
  $event=$quote['event'];
  $basic=\App\Models\BasicSettings\Basic::select('tax')->first();
  $taxRate=(float)($basic->tax ?? 0);
  $taxPaise=(int)round($quote['ticket_amount']*$taxRate/100);
  $snapshot=['items'=>$quote['items'],'quantity'=>$quote['quantity'],'subtotal'=>$quote['subtotal'],'discount'=>$quote['discount'],'tax_rate'=>$taxRate,'sales_channel'=>$channel];
  $order=$orders->createFromPricing($event->id,$event->organizer_id,$quote['ticket_amount'],$taxPaise,$data['idempotency_key'],$snapshot);
  if(!$order->customer_snapshot){
   // Booking ownership comes from authentication only, never from the request body.
   $customer=$data['customer']; $customer['customer_id']=self::authenticatedCustomerId($r) ?: 'guest';
   $order->customer_snapshot=$customer; $order->save();
  }
  $gateway=$razorpay->createOrder($order);
  return response()->json(['success'=>true,'payment_order'=>$order->uuid,'gateway_order_id'=>$gateway['id'],'amount'=>$order->customer_total,'currency'=>$order->currency,
   'breakdown'=>['ticket_amount'=>$order->ticket_amount,'platform_fee'=>$order->platform_fee,'additional_fees'=>$order->additional_fee_amount,'tax'=>$order->tax_amount,'customer_total'=>$order->customer_total,'organizer_amount'=>$order->organizer_amount,'fee_bearer'=>$order->fee_bearer,'sales_channel'=>$order->sales_channel,'settlement_mode'=>$order->settlement_mode]]);
 }
 public function verifyRazorpay(Request $r,RazorpayRouteService $razorpay,PaymentCaptureService $capture){
  $data=$r->validate(['payment_order'=>'required|uuid','razorpay_payment_id'=>'required|string','razorpay_signature'=>'required|string']);
  $order=PaymentOrder::where('uuid',$data['payment_order'])->firstOrFail();
  if($order->booking_id){
   $booking=\App\Models\Event\Booking::find($order->booking_id);
   return response()->json(['success'=>true,'status'=>'paid','idempotent'=>true,'booking_id'=>optional($booking)->booking_id]);
  }
  try { $razorpay->verifyCheckout($order,$data['razorpay_payment_id'],$data['razorpay_signature']); }
  catch(\Razorpay\Api\Errors\SignatureVerificationError $e){ return response()->json(['success'=>false,'message'=>'Invalid payment signature.'],422); }
  catch(\Throwable $e){ report($e); return response()->json(['success'=>false,'message'=>'Payment verification failed.'],422); }
  try { $booking=$capture->complete($order,$data['razorpay_payment_id']); }
  catch(\Throwable $e){
   report($e);
   return response()->json(['success'=>false,'status'=>PaymentCaptureService::UNFINALIZED,'message'=>'Payment received but the booking could not be completed. It will be completed or refunded automatically.'],409);
  }
  return response()->json(['success'=>true,'status'=>'paid','booking_id'=>$booking->booking_id]);
 }

 private static function authenticatedCustomerId(Request $r): ?int {
  foreach(['sanctum','customer'] as $guard){
   try { $u=\Illuminate\Support\Facades\Auth::guard($guard)->user(); } catch(\Throwable $e){ $u=null; }
   if($u instanceof \App\Models\Customer) return (int)$u->id;
  }
  return null;
 }
}