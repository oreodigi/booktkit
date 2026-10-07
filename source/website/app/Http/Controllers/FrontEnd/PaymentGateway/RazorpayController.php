<?php

namespace App\Http\Controllers\FrontEnd\PaymentGateway;

use Exception;
use Razorpay\Api\Api;
use App\Models\Earning;
use Illuminate\Http\Request;
use App\Jobs\BookingInvoiceJob;
use Illuminate\Support\Facades\DB;
use App\Models\BasicSettings\Basic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\PaymentGateway\OnlineGateway;
use Razorpay\Api\Errors\SignatureVerificationError;
use App\Http\Controllers\FrontEnd\Event\BookingController;
use App\Services\Payments\AuthoritativeTicketPricingService;
use App\Services\Payments\PaymentOrderService;
use App\Services\Payments\RazorpayRouteService;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Payments\BookingFinalizationService;
use App\Services\Payments\PaymentCaptureService;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\OrganizerPaymentProfile;


class RazorpayController extends Controller
{
  private $key, $secret, $api;

  public function __construct()
  {
    $data = OnlineGateway::whereKeyword('razorpay')->first();
    $razorpayData = json_decode($data->information, true);

    $this->key = $razorpayData['key'];
    $this->secret = $razorpayData['secret'];

    $this->api = new Api($this->key, $this->secret);
  }

  public function bookingProcess(Request $request, $event_id)
  {
    $request->validate(['fname'=>'required','lname'=>'required','email'=>'required|email','phone'=>'required','country'=>'required','address'=>'required','gateway'=>'required']);
    $sel=collect(Session::get('selTickets',[]))->map(function($x){
      return ['ticket_id'=>(int)$x['ticket_id'],'quantity'=>(int)$x['qty'],'variation'=>($x['pass_product_id']??null)?null:($x['name']??null),'pass_product_id'=>$x['pass_product_id']??null,'event_date_ids'=>$x['event_date_ids']??[],'seat_id'=>$x['seat_id']??null,'slot_id'=>$x['slot_id']??null];
    })->values()->all();
    if(empty($sel)) return back()->with('error','Please select at least one ticket.')->withInput();
    try { $pricing=app(AuthoritativeTicketPricingService::class)->quote((int)$event_id,$sel); }
    catch(\Illuminate\Validation\ValidationException $e){ return back()->with(['alert-type'=>'error','message'=>collect($e->errors())->flatten()->first()]); }
    // A coupon applied at checkout is re-validated and priced on the server so the charge matches the page.
    $coupon=app(\App\Services\Payments\CouponService::class)->discount(Session::get('coupon_code'),(int)$event_id,(int)$pricing['ticket_amount']);
    $ticketAmount=max(0,(int)$pricing['ticket_amount']-$coupon['amount']);
    $event=$pricing['event']; $basic=Basic::first(); $taxRate=(float)($basic->tax??0); $tax=(int)round($ticketAmount*$taxRate/100);
    $snapshot=['items'=>$pricing['items'],'quantity'=>$pricing['quantity'],'subtotal'=>$pricing['subtotal'],'discount'=>$pricing['discount']+$coupon['amount'],'early_bird_discount'=>$pricing['discount'],'coupon'=>$coupon,'tax_rate'=>$taxRate,'sales_channel'=>'web'];
    $idem='web-'.hash('sha256',session()->getId().'|'.$event_id.'|'.microtime(true));
    $order=app(PaymentOrderService::class)->createFromPricing($event->id,$event->organizer_id,$ticketAmount,$tax,$idem,$snapshot);
    $order->customer_snapshot=['customer_id'=>(\Illuminate\Support\Facades\Auth::guard('customer')->id() ?: 'guest'),'fname'=>$request->fname,'lname'=>$request->lname,'email'=>$request->email,'phone'=>$request->phone,
      'country'=>$request->country,'state'=>$request->state,'city'=>$request->city,'zip_code'=>$request->zip_code,'address'=>$request->address,'event_date'=>$request->event_date ?: Session::get('event_date')];
    $order->save(); $gateway=app(RazorpayRouteService::class)->createOrder($order);
    $notifyURL=route('event_booking.razorpay.notify');
    $webInfo=DB::table('basic_settings')->select('website_title')->first();
    $checkoutData=['key'=>$this->key,'amount'=>$order->customer_total,'currency'=>$order->currency,'name'=>$webInfo->website_title,
      'description'=>'Event Booking Via Razorpay','prefill'=>['name'=>$request->fname.' '.$request->lname,'email'=>$request->email,'contact'=>$request->phone],'order_id'=>$gateway['id']];
    $jsonData=json_encode($checkoutData); session(['event_id'=>$event_id,'booktkitPaymentOrder'=>$order->uuid]);
    $paymentOrderUuid=$order->uuid;
    return view('frontend.payment.razorpay',compact('jsonData','notifyURL','paymentOrderUuid'));
  }

  public function notify(Request $request)
  {
    // Identify the order from the signed checkout response, falling back to the session. The Razorpay
    // signature binds payment id to gateway order id, so a forged order reference cannot verify.
    $uuid = (string) ($request->input('payment_order') ?: session('booktkitPaymentOrder'));
    $order = PaymentOrder::where('uuid', $uuid)->first();
    if (!$order && $request->filled('razorpayOrderId')) {
      $order = PaymentOrder::where('gateway_order_id', (string) $request->input('razorpayOrderId'))->first();
    }
    if (!$order) {
      return redirect()->route('index')->with(['alert-type' => 'error', 'message' => __('We could not find this payment. If you were charged, your tickets will be emailed once the payment is confirmed.')]);
    }
    $eventId = $order->event_id;
    try {
      app(RazorpayRouteService::class)->verifyCheckout($order, (string) $request->razorpayPaymentId, (string) $request->razorpaySignature);
    } catch (\Throwable $e) {
      report($e); session()->forget(['booktkitPaymentOrder']);
      return redirect()->route('event_booking.cancel', ['id' => $eventId])->with('error', 'Payment verification failed.');
    }
    try {
      $booking = app(PaymentCaptureService::class)->complete($order, (string) $request->razorpayPaymentId);
    } catch (\Throwable $e) {
      report($e); session()->forget(['booktkitPaymentOrder']);
      return redirect()->route('index')->with(['alert-type' => 'error', 'message' => __('Your payment was received but the booking could not be completed automatically. Our team has been alerted and will confirm your tickets or refund you.')]);
    }
    \App\Support\BookingConfirmationAccess::grant($booking);
    session()->forget(['event_id','selTickets','arrData','paymentId','discount','razorpayOrderId','booktkitPaymentOrder']);
    return redirect()->route('event_booking.complete', ['id' => $eventId, 'booking_id' => $booking->id]);
  }

}