<?php

namespace App\Http\Controllers\BackEnd\Organizer\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\ShopManagement\ShippingCharge;
use App\Services\OrganizerAiTokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class MercadoPagoController extends Controller
{
  private $token, $sandbox_status;

  public function __construct()
  {
    $data = OnlineGateway::whereKeyword('mercadopago')->first();
    $mercadopagoData = json_decode($data->information, true);

    $this->token = $mercadopagoData['token'];
    $this->sandbox_status = $mercadopagoData['sandbox_status'];
  }

  public function enrolmentProcess(Request $request)
  {
    $allowedCurrencies = array('ARS', 'BOB', 'BRL', 'CLF', 'CLP', 'COP', 'CRC', 'CUC', 'CUP', 'DOP', 'EUR', 'GTQ', 'HNL', 'MXN', 'NIO', 'PAB', 'PEN', 'PYG', 'USD', 'UYU', 'VEF', 'VES');
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
      return redirect()->back()->with('warning', 'Invalid currency for mercadopago payment.');
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

      'method' => 'MercadoPago',
      'gateway_type' => 'online',
      'payment_status' => 'completed',
      'order_status' => 'pending',
      'tnxid' => '',
    );
    $title = 'AI Token Purchase';
    $notifyURL = route('organizer.ai_token_purchase.mercadopago.notify');
    $completeURL = route('organizer.ai_token_purchase.complete');
    $cancelURL = route('organizer.ai_token_purchase.checkout');
    $total = (float)$grand_total;

    $curl = curl_init();
    $preferenceData = [
      'items' => [
        [
          'id' => uniqid(),
          'title' => $title,
          'description' => 'Product Order via MercadoPago',
          'quantity' => 1,
          'currency' => $currencyInfo->text,
          'unit_price' => $total
        ]
      ],
      'payer' => [
        'email' => $request->email
      ],
      'back_urls' => [
        'success' => $completeURL,
        'pending' => '',
        'failure' => $cancelURL
      ],
      'notification_url' => $notifyURL,
      'auto_return' => 'approved'
    ];

    $httpHeader = ['Content-Type: application/json'];

    $url = 'https://api.mercadopago.com/checkout/preferences?access_token=' . $this->token;

    $curlOPT = [
      CURLOPT_URL             => $url,
      CURLOPT_CUSTOMREQUEST   => 'POST',
      CURLOPT_POSTFIELDS      => json_encode($preferenceData, true),
      CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_TIMEOUT         => 30,
      CURLOPT_HTTPHEADER      => $httpHeader
    ];

    curl_setopt_array($curl, $curlOPT);

    $response = curl_exec($curl);
    $responseInfo = json_decode($response, true);
    if (array_key_exists('error', $responseInfo)) {
      Session::flash('error', $responseInfo['message']);
      return redirect()->route('organizer.ai_token_purchase.checkout');
    }

    curl_close($curl);

    // put some data in session before redirect to mercadopago url
    $request->session()->put('organizer_ai_arrData', $arrData);

    if ($this->sandbox_status == 1) {
      return redirect($responseInfo['sandbox_init_point']);
    } else {
      return redirect($responseInfo['init_point']);
    }
  }

  public function notify(Request $request)
  {
    // get the information from session
    $arrData = $request->session()->get('organizer_ai_arrData');

    $paymentURL = 'https://api.mercadopago.com/v1/payments/' . $request['data']['id'] . '?access_token=' . $this->token;

    $paymentData = $this->curlCalls($paymentURL);
    $paymentInfo = json_decode($paymentData, true);

    if ($paymentInfo['status'] == 'approved') {
      $purchaseService = app(OrganizerAiTokenPurchaseService::class);
      $purchaseService->finalizeGatewayPurchase($arrData);

      // remove all session data
      $request->session()->forget('organizer_ai_arrData');

      return redirect()->route('organizer.ai_token_purchase.complete');
    } else {
      // remove all session data
      $request->session()->forget('organizer_ai_arrData');

      return redirect()->route('organizer.ai_token_purchase.checkout');
    }
  }

  public function curlCalls($url)
  {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $curlData = curl_exec($curl);

    curl_close($curl);

    return $curlData;
  }
}




