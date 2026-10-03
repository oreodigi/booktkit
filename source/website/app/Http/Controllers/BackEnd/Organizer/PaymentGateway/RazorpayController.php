<?php

namespace App\Http\Controllers\BackEnd\Organizer\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\ShopManagement\ShippingCharge;
use App\Services\OrganizerAiTokenPurchaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

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

  public function enrolmentProcess(Request $request)
  {

    $currencyInfo = $this->getCurrencyInfo();
    $cart_items = Session::get('organizer_ai_token_cart');

    $total = 0;
    $quantity = 0;
    foreach ($cart_items as $p) {
      $total += $p['price'] * $p['qty'];
      $quantity += $p['price'] * $p['qty'];
    }
    if ($request->shipping_method) {
      $shipping_cost = ShippingCharge::where('id', $request->shipping_method)->first();
      $shipping_charge = $shipping_cost->charge;
      $shipping_method = $shipping_cost->title;
    } else {
      $shipping_charge = 0;
      $shipping_method = NULL;
    }

    $discount = Session::get('organizer_ai_token_discount');
    $tax = Basic::select('shop_tax')->first();
    $tax_percentage = $tax->shop_tax;
    $total_tax_amount = ($tax_percentage / 100) * ($total - $discount);
    $grand_total = ($shipping_charge + $total + $total_tax_amount) - $discount;
    // checking whether the currency is set to 'INR' or not
    if ($currencyInfo->text !== 'INR') {
      return redirect()->back()->with('warning', 'Invalid currency for razorpay payment.')->withInput();
    }

    if (Auth::guard('organizer')->user()) {
      $user_id = Auth::guard('organizer')->user()->id;
    } else {
      $user_id = 0;
    }
    $arrData = array(
      'user_id' => $user_id,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'email' => $request->email,
      'phone' => $request->phone,
      'country' => $request->country,
      'state' => $request->state,
      'city' => $request->city,
      'zip_code' => $request->zip_code,
      'address' => $request->address,

      's_fname' => $request->sameas_shipping == NULL ? $request->s_fname : $request->fname,
      's_lname' => $request->sameas_shipping == NULL ? $request->s_lname : $request->lname,
      's_email' => $request->sameas_shipping == NULL ? $request->s_email : $request->email,
      's_phone' => $request->sameas_shipping == NULL ? $request->s_phone : $request->phone,
      's_country' => $request->sameas_shipping == NULL ? $request->s_country : $request->country,
      's_state' => $request->sameas_shipping == NULL ? $request->s_state : $request->state,
      's_city' => $request->sameas_shipping == NULL ? $request->s_city : $request->city,
      's_zip_code' => $request->sameas_shipping == NULL ? $request->s_city : $request->city,
      's_address' => $request->sameas_shipping == NULL ? $request->s_address : $request->address,

      'cart_total' => $total,
      'discount' => $discount,
      'tax_percentage' => $tax_percentage,
      'tax' => $total_tax_amount,
      'grand_total' => $grand_total,
      'currency_code' => '',

      'shipping_charge' => $shipping_charge,
      'shipping_method' => $shipping_method,
      'order_number' => uniqid(),
      'charge_id' => $request->shipping_method,

      'method' => 'Razorpay',
      'gateway_type' => 'online',
      'payment_status' => 'completed',
      'order_status' => 'pending',
      'tnxid' => '',
    );

    $notifyURL = route('organizer.ai_token_purchase.razorpay.notify');

    // create order data
    $orderData = [
      'receipt'         => 'AI Token Purchase',
      'amount'          => $grand_total * 100,
      'currency'        => 'INR',
      'payment_capture' => 1 // auto capture
    ];

    try {
      $razorpayOrder = $this->api->order->create($orderData);
    } catch (Exception $e) {
      return redirect()->back()->with('error', 'Something went wrong or invalid api key.!')->withInput();
    }

    $webInfo = DB::table('basic_settings')->select('website_title')->first();
    $buyerName = $request->fname . ' ' . $request->lname;
    $buyerEmail = $request->email;
    $buyerContact = $request->phone;

    // create checkout data
    $checkoutData = [
      'key'               => $this->key,
      'amount'            => $orderData['amount'],
      'name'              => $webInfo->website_title,
      'description'       => 'Product Order Payment Via Razorpay',
      'prefill'           => [
        'name'              => $buyerName,
        'email'             => $buyerEmail,
        'contact'           => $buyerContact
      ],
      'order_id'          => $razorpayOrder->id
    ];

    $jsonData = json_encode($checkoutData);

    // put some data in session before redirect to razorpay url
    $request->session()->put('organizer_ai_arrData', $arrData);
    $request->session()->put('organizer_ai_razorpay_order_id', $razorpayOrder->id);

    return view('frontend.payment.razorpay', compact('jsonData', 'notifyURL'));
  }

  public function notify(Request $request)
  {
    // get the information from session
    $arrData = $request->session()->get('organizer_ai_arrData');
    $razorpayOrderId = $request->session()->get('organizer_ai_razorpay_order_id');

    $urlInfo = $request->all();

    // assume that the transaction was successful
    $success = true;

    /**
     * either razorpay_order_id or razorpay_subscription_id must be present.
     * the keys of $attributes array must be follow razorpay convention.
     */
    try {
      $attributes = [
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_payment_id' => $urlInfo['razorpayPaymentId'],
        'razorpay_signature' => $urlInfo['razorpaySignature']
      ];

      $this->api->utility->verifyPaymentSignature($attributes);
    } catch (SignatureVerificationError $e) {
      $success = false;
    }

    if ($success === true) {
      $purchaseService = app(OrganizerAiTokenPurchaseService::class);
      $purchaseService->finalizeGatewayPurchase($arrData);

      // remove all session data
      $request->session()->forget('organizer_ai_arrData');
      $request->session()->forget('organizer_ai_razorpay_order_id');

      return redirect()->route('organizer.ai_token_purchase.complete');
    } else {
      // remove all session data
      $request->session()->forget('organizer_ai_arrData');
      $request->session()->forget('organizer_ai_razorpay_order_id');

      return redirect()->route('organizer.ai_token_purchase.checkout');
    }
  }
}




