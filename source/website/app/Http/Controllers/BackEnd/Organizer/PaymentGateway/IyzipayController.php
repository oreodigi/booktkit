<?php

namespace App\Http\Controllers\BackEnd\Organizer\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\ShopManagement\ShippingCharge;
use App\Services\OrganizerAiTokenPurchaseService;
use Illuminate\Http\Request;
use Config\Iyzipay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class IyzipayController extends Controller
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
        if ($currencyInfo->text != 'TRY') {
            return redirect()->back()->with('warning', 'Invalid currency for toyyibpay payment.')->withInput();
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

            'method' => 'iyzipay',
            'gateway_type' => 'online',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'tnxid' => '',
        );
        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Booking End ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        $id_number = $request->identity_number;
        $basket_id = 'B'. uniqid(999, 99999);
        /*````````````````````````````````````````````
        ````````````Payment gateway info start`````````
        ---------------------------------------------*/
        $notifyURL = route('organizer.ai_token_purchase.iyzico.notify');
        $options = Iyzipay::options();
        $conversion_id = uniqid(9999, 999999);
        $arrData['conversation_id'] = $conversion_id;

        # create request class
        $request = new \Iyzipay\Request\CreatePayWithIyzicoInitializeRequest();
        $request->setLocale(\Iyzipay\Model\Locale::EN);
        $request->setConversationId($conversion_id);
        $request->setPrice($total);
        $request->setPaidPrice($grand_total);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setBasketId($basket_id);
        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
        $request->setCallbackUrl($notifyURL);
        $request->setEnabledInstallments(array(2, 3, 6, 9));

        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId(uniqid());
        $buyer->setName($arrData['fname']);
        $buyer->setSurname($arrData['lname']);
        $buyer->setGsmNumber($arrData['phone']);//live code
        // $buyer->setGsmNumber("+905350000000");//test code
        $buyer->setEmail($arrData['email']);
        $buyer->setIdentityNumber($id_number);//live code
        // $buyer->setIdentityNumber("74300864791");//test code
        $buyer->setLastLoginDate("");
        $buyer->setRegistrationDate("");
        $buyer->setRegistrationAddress($arrData['address']);
        $buyer->setIp("");
        $buyer->setCity($arrData['city']);
        $buyer->setCountry($arrData['country']);
        $buyer->setZipCode($arrData['zip_code']);
        $request->setBuyer($buyer);

        $shippingAddress = new \Iyzipay\Model\Address();
        $shippingAddress->setContactName($arrData['fname']);
        $shippingAddress->setCity($arrData['city']);
        $shippingAddress->setCountry($arrData['country']);
        $shippingAddress->setAddress($arrData['address']);
        $shippingAddress->setZipCode($arrData['zip_code']);
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new \Iyzipay\Model\Address();
        $billingAddress->setContactName($arrData['fname']);
        $billingAddress->setCity($arrData['city']);
        $billingAddress->setCountry($arrData['country']);
        $billingAddress->setAddress($arrData['address']);
        $billingAddress->setZipCode($arrData['zip_code']);
        $request->setBillingAddress($billingAddress);

        $q_id = uniqid(999, 99999);
        $basketItems = array();
        $firstBasketItem = new \Iyzipay\Model\BasketItem();
        $firstBasketItem->setId($q_id);
        $firstBasketItem->setName("Booking Id " . $q_id);
        $firstBasketItem->setCategory1("Purchase or Booking");
        $firstBasketItem->setCategory2("");
        $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
        $firstBasketItem->setPrice($total);
        $basketItems[0] = $firstBasketItem;
        $request->setBasketItems($basketItems);

        # make request
        $payWithIyzicoInitialize = \Iyzipay\Model\PayWithIyzicoInitialize::create($request, $options);

        $paymentResponse = (array)$payWithIyzicoInitialize;
        foreach ($paymentResponse as $key => $data) {
            $paymentInfo = json_decode($data, true);
            if ($paymentInfo['status'] == 'success') {
                if (!empty($paymentInfo['payWithIyzicoPageUrl'])) {
                    Cache::forget('organizer_ai_conversation_id');
                    Session::put('organizer_ai_iyzico_token', $paymentInfo['token']);
                    Session::put('organizer_ai_conversation_id', $conversion_id);
                    Cache::put('organizer_ai_conversation_id', $conversion_id, 60000);

                    // put some data in session before redirect to paypal url
                    Session::put('organizer_ai_arrData', $arrData);

                    return redirect($paymentInfo['payWithIyzicoPageUrl']);
                }
            }
            return redirect()->route('organizer.ai_token_purchase.checkout')->with(['alert-type' => 'error', 'message' => $paymentInfo['errorMessage']]);
        }
    }

    public function notify(Request $request)
    {
        $conversation_id = Cache::get('organizer_ai_conversation_id');
        // get the information from session
        $arrData = Session::get('organizer_ai_arrData');
        $arrData['conversation_id'] = $conversation_id ?? ($arrData['conversation_id'] ?? null);
        $arrData['payment_status'] = $this->resolvePaymentStatus($arrData['conversation_id']);
        $arrData['t_payment_status'] = $arrData['payment_status'] === 'completed' ? 1 : 0;
        $purchaseService = app(OrganizerAiTokenPurchaseService::class);
        $purchaseService->finalizeGatewayPurchase($arrData);

        // remove all session data
        Session::forget('organizer_ai_arrData');
        return redirect()->route('organizer.ai_token_purchase.complete');
    }

    private function resolvePaymentStatus(?string $conversationId): string
    {
        if (empty($conversationId)) {
            return 'pending';
        }

        $options = Iyzipay::options();
        $request = new \Iyzipay\Request\ReportingPaymentDetailRequest();
        $request->setPaymentConversationId($conversationId);

        $paymentResponse = \Iyzipay\Model\ReportingPaymentDetail::create($request, $options);
        $result = (array) $paymentResponse;

        foreach ($result as $data) {
            $data = json_decode($data, true);

            if (($data['status'] ?? null) !== 'success' || !is_array($data['payments'] ?? null) || empty($data['payments'][0])) {
                continue;
            }

            return ((int) ($data['payments'][0]['paymentStatus'] ?? 0) === 1) ? 'completed' : 'pending';
        }

        return 'pending';
    }
}



