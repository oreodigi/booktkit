<?php

namespace App\Http\Controllers\BackEnd\Organizer\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\ShopManagement\ShippingCharge;
use App\Services\OrganizerAiTokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class XenditController extends Controller
{
  public function purchaseProcess(Request $request)
  {
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Purchase Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
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
    $allowed_currency = array('IDR', 'PHP', 'USD', 'SGD', 'MYR');
    if (!in_array($currencyInfo->text, $allowed_currency)) {
      return redirect()->back()->with('warning', 'Invalid currency for xendit payment.')->withInput();
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

      'method' => 'Xendit',
      'gateway_type' => 'online',
      'payment_status' => 'completed',
      'order_status' => 'pending',
      'tnxid' => '',
    );
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Booking End ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Payment Gateway Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    $external_id = Str::random(10);
    $secret_key = config('xendit.key_auth');

    $data_request = Http::withHeaders([
      'Authorization' => 'Basic ' . $secret_key,
    ])->post('https://api.xendit.co/v2/invoices', [
      'external_id' => $external_id,
      'amount' => $grand_total,
      'currency' => $currencyInfo->text,
      'success_redirect_url' => route('organizer.ai_token_purchase.xendit.notify')
    ]);
    $response = $data_request->object();


    $response = json_decode(json_encode($response), true);
    if (!empty($response['success_redirect_url'])) {
      $request->session()->put('organizer_ai_arrData', $arrData);
      $request->session()->put('organizer_ai_xendit_id', $response['id']);
      $request->session()->put('organizer_ai_xendit_secret_key', config('xendit.key_auth'));
      $request->session()->put('organizer_ai_xendit_payment_type', 'organizer_ai_token_purchase');

      return redirect($response['invoice_url']);
    } else {
      return redirect()->route('organizer.ai_token_purchase.checkout')->with(['alert-type' => 'error', 'message' => $response['message']]);
    }
  }

  // return to success page
  public function notify(Request $request)
  {
    $xendit_id = Session::get('organizer_ai_xendit_id');
    $secret_key = Session::get('organizer_ai_xendit_secret_key');

    $response = Http::withHeaders([
      'Authorization' => 'Basic ' . $secret_key,
    ])->get("https://api.xendit.co/v2/invoices/{$xendit_id}");

    if ($response->failed()) {
      return redirect()->route('organizer.ai_token_purchase.checkout')->with(['alert-type' => 'error', 'message' => 'Failed to verify payment.']);
    }

    $payment = $response->object();
    if (isset($payment->status) && in_array($payment->status, ['PAID', 'SETTLED'])) {
      $arrData = Session::get('organizer_ai_arrData');
      $purchaseService = app(OrganizerAiTokenPurchaseService::class);
      $purchaseService->finalizeGatewayPurchase($arrData);

      // remove all session data
      Session::forget('organizer_ai_arrData');

      return redirect()->route('organizer.ai_token_purchase.complete');
    }
    
    return redirect()->route('organizer.ai_token_purchase.checkout')->with(['alert-type' => 'error', 'message' => 'Payment failed']);

  }

}




