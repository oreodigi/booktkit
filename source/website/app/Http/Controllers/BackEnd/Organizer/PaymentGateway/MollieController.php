<?php

namespace App\Http\Controllers\BackEnd\Organizer\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\ShopManagement\ShippingCharge;
use App\Services\OrganizerAiTokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Mollie\Laravel\Facades\Mollie;

class MollieController extends Controller
{
  public function enrolmentProcess(Request $request)
  {

    $allowedCurrencies = array('AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR');
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

    $currencyInfo = $this->getCurrencyInfo();

    // checking whether the base currency is allowed or not
    if (!in_array($currencyInfo->text, $allowedCurrencies)) {
      return redirect()->back()->with('warning', 'Invalid currency for mollie payment.')->withInput();
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

      'method' => 'Mollie',
      'gateway_type' => 'online',
      'payment_status' => 'completed',
      'order_status' => 'pending',
      'tnxid' => '',
    );

    $notifyURL = route('organizer.ai_token_purchase.mollie.notify');

    /**
     * we must send the correct number of decimals.
     * thus, we have used sprintf() function for format.
     */
    try {
      $payment = Mollie::api()->payments->create([
        'amount' => [
          'currency' => $currencyInfo->text,
          'value' => sprintf('%0.2f', $total)
        ],
        'description' => 'Product Order Via Mollie',
        'redirectUrl' => $notifyURL
      ]);
    } catch (\Exception $th) {
      Session::flash('error', 'Something went wrong or invalid api key');
      return redirect()->route('organizer.ai_token_purchase.checkout');
    }

    // put some data in session before redirect to mollie url
    $request->session()->put('organizer_ai_arrData', $arrData);
    $request->session()->put('organizer_ai_payment_id', $payment->id);

    return redirect($payment->getCheckoutUrl(), 303);
  }

  public function notify(Request $request)
  {
    // get the information from session
    $arrData = $request->session()->get('organizer_ai_arrData');
    $paymentId = $request->session()->get('organizer_ai_payment_id');

    $paymentInfo = Mollie::api()->payments->get($paymentId);

    if ($paymentInfo->isPaid() == true) {
      $purchaseService = app(OrganizerAiTokenPurchaseService::class);
      $purchaseService->finalizeGatewayPurchase($arrData);

      // remove all session data
      $request->session()->forget('organizer_ai_arrData');
      $request->session()->forget('organizer_ai_payment_id');

      return redirect()->route('organizer.ai_token_purchase.complete');
    } else {
      // remove all session data
      $request->session()->forget('organizer_ai_arrData');
      $request->session()->forget('organizer_ai_payment_id');

      return redirect()->route('organizer.ai_token_purchase.checkout');
    }
  }
}




