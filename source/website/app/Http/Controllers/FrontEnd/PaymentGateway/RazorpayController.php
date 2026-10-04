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
      return ['ticket_id'=>(int)$x['ticket_id'],'quantity'=>(int)$x['qty'],'variation'=>($x['name']??null)];
    })->values()->all();
    if(empty($sel)) return back()->with('error','Please select at least one ticket.')->withInput();
    $pricing=app(AuthoritativeTicketPricingService::class)->quote((int)$event_id,$sel);
    $event=$pricing['event']; $basic=Basic::first(); $taxRate=(float)($basic->tax??0); $tax=(int)round($pricing['ticket_amount']*$taxRate/100);
    $snapshot=['items'=>$pricing['items'],'quantity'=>$pricing['quantity'],'subtotal'=>$pricing['subtotal'],'discount'=>$pricing['discount'],'tax_rate'=>$taxRate];
    $idem='web-'.hash('sha256',session()->getId().'|'.$event_id.'|'.microtime(true));
    $order=app(PaymentOrderService::class)->createFromPricing($event->id,$event->organizer_id,$pricing['ticket_amount'],$tax,$idem,$snapshot);
    $order->customer_snapshot=['customer_id'=>auth()->id()?:'guest','fname'=>$request->fname,'lname'=>$request->lname,'email'=>$request->email,'phone'=>$request->phone,
      'country'=>$request->country,'state'=>$request->state,'city'=>$request->city,'zip_code'=>$request->zip_code,'address'=>$request->address,'event_date'=>$request->event_date];
    $order->save(); $gateway=app(RazorpayRouteService::class)->createOrder($order);
    $notifyURL=route('event_booking.razorpay.notify');
    $webInfo=DB::table('basic_settings')->select('website_title')->first();
    $checkoutData=['key'=>$this->key,'amount'=>$order->customer_total,'currency'=>$order->currency,'name'=>$webInfo->website_title,
      'description'=>'Event Booking Via Razorpay','prefill'=>['name'=>$request->fname.' '.$request->lname,'email'=>$request->email,'contact'=>$request->phone],'order_id'=>$gateway['id']];
    $jsonData=json_encode($checkoutData); session(['event_id'=>$event_id,'booktkitPaymentOrder'=>$order->uuid]);
    return view('frontend.payment.razorpay',compact('jsonData','notifyURL'));
  }

  public function notify(Request $request)
  {
    $eventId=session('event_id'); $uuid=session('booktkitPaymentOrder');
    $order=PaymentOrder::where('uuid',$uuid)->firstOrFail();
    try {
      app(RazorpayRouteService::class)->verifyCheckout($order,$request->razorpayPaymentId,$request->razorpaySignature);
      $booking=DB::transaction(function() use($order,$request){
        $locked=PaymentOrder::whereKey($order->id)->lockForUpdate()->first();
        if($locked->status==='paid' && $locked->booking_id) return AppModelsEventBooking::findOrFail($locked->booking_id);
        $locked->update(['gateway_payment_id'=>$request->razorpayPaymentId,'status'=>'paid','paid_at'=>now()]);
        $booking=app(BookingFinalizationService::class)->finalize($locked);
        app(PaymentLedgerService::class)->recordPaid($locked);
        return $booking;
      });
      $profile=OrganizerPaymentProfile::where('organizer_id',$order->organizer_id)->first();
      if($order->settlement_mode==='razorpay_split' && $profile && $profile->canSplit()){
        try { app(RazorpayRouteService::class)->transferToOrganizer($order->fresh(),$profile->razorpay_account_id); } catch(Throwable $e) { report($e); }
      }
      session()->forget(['event_id','selTickets','arrData','paymentId','discount','razorpayOrderId','booktkitPaymentOrder']);
      return redirect()->route('event_booking.complete',['id'=>$eventId,'booking_id'=>$booking->id]);
    } catch(Throwable $e) {
      report($e); session()->forget(['booktkitPaymentOrder']);
      return redirect()->route('event_booking.cancel',['id'=>$eventId])->with('error','Payment verification failed.');
    }
  }

}