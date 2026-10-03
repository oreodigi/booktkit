<?php

namespace App\Http\Controllers\FrontEnd\PaymentGateway;

use App\Models\Earning;
use Illuminate\Http\Request;
use App\Jobs\BookingInvoiceJob;
use Illuminate\Support\Facades\DB;
use App\Models\BasicSettings\Basic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\PaymentGateway\OnlineGateway;
use App\Http\Controllers\FrontEnd\Event\BookingController;

class PaystackController extends Controller
{
  private $api_key;

  public function __construct()
  {
    $data = OnlineGateway::whereKeyword('paystack')->first();
    $paystackData = json_decode($data->information, true);

    $this->api_key = $paystackData['key'];
  }

  public function bookingProcess(Request $request, $eventId)
  {
    $rules = [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required',
      'phone' => 'required',
      'country' => 'required',
      'address' => 'required',
      'gateway' => 'required',
    ];

    $message = [];

    $message['fname.required'] = 'The first name feild is required';
    $message['lname.required'] = 'The last name feild is required';
    $message['gateway.required'] = 'The payment gateway feild is required';
    $request->validate($rules, $message);

    $booking = new BookingController();

    $currencyInfo = $this->getCurrencyInfo();

    $baseTotal = floatval(Session::get('grand_total'));
    $baseTaxAmount = floatval(Session::get('tax'));
    $baseDiscount = floatval(Session::get('discount'));
    $baseTotalEarlyBirdDiscount = floatval(Session::get('total_early_bird_dicount'));
    $quantity = Session::get('quantity');

    // tax and commission end
    $basicSetting = Basic::select('commission')->first();
    $baseCommissionAmount = ($baseTotal * $basicSetting->commission) / 100;

    // Keep all payable and booking amounts in the selected currency.
    $total = price_format($baseTotal);
    $tax_amount = price_format($baseTaxAmount);
    $discount = price_format($baseDiscount);
    $total_early_bird_dicount = price_format($baseTotalEarlyBirdDiscount);
    $commission_amount = price_format($baseCommissionAmount);

    // checking whether the currency is set to 'NGN' or not
    if ($currencyInfo->text !== 'NGN') {
      return redirect()->back()->with('currency_error', 'Invalid currency for paystack payment.')->withInput();
    }

    try {
      $arrData = array(
        'event_id' => $eventId,
        'price' => $total,
        'tax' => $tax_amount,
        'commission' => $commission_amount,
        'quantity' => $quantity,
        'discount' => $discount,
        'total_early_bird_dicount' => $total_early_bird_dicount,
        'currencyText' => $currencyInfo->text,
        'currencyTextPosition' => $currencyInfo->text_position,
        'currencySymbol' => $currencyInfo->symbol,
        'currencySymbolPosition' => $currencyInfo->symbol_position,
        'fname' => $request->fname,
        'lname' => $request->lname,
        'email' => $request->email,
        'phone' => $request->phone,
        'country' => $request->country,
        'state' => $request->state,
        'city' => $request->city,
        'zip_code' => $request->city,
        'address' => $request->address,
        'paymentMethod' => 'Paystack',
        'gatewayType' => 'online',
        'paymentStatus' => 'completed',
      );

      $notifyURL = route('event_booking.paystack.notify');

      $curl = curl_init();

      $payableAmount = round($total + $tax_amount, 2);

      curl_setopt_array($curl, array(
        CURLOPT_URL            => 'https://api.paystack.co/transaction/initialize',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode([
          'amount'       => intval($payableAmount * 100),
          'email'        => $request->email,
          'callback_url' => $notifyURL
        ]),
        CURLOPT_HTTPHEADER     => [
          'authorization: Bearer ' . $this->api_key,
          'content-type: application/json',
          'cache-control: no-cache'
        ]
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      $transaction = json_decode($response, true);

      // put some data in session before redirect to paystack url
      $request->session()->put('eventId', $eventId);
      $request->session()->put('arrData', $arrData);

      if ($transaction['status'] == true) {
        return redirect($transaction['data']['authorization_url']);
      } else {
        return redirect()->back()->with('error', 'Error: ' . $transaction['message'])->withInput();
      }
    } catch (\Exception $e) {
      return redirect()->back()->with('error', 'Something went wrong');
    }
  }

  public function notify(Request $request)
  {
    // get the information from session
    $eventId = $request->session()->get('eventId');
    $arrData = $request->session()->get('arrData');

    $urlInfo = $request->all();

    if ($urlInfo['trxref'] === $urlInfo['reference']) {
      $enrol = new BookingController();

      $bookingInfo['transcation_type'] = 1;

      // store the course enrolment information in database
      $bookingInfo = $enrol->storeData($arrData);

      $ticket = DB::table('basic_settings')->select('how_ticket_will_be_send')->first();

      if ($ticket->how_ticket_will_be_send == 'instant') {
        // generate an invoice in pdf format
        $invoice = $enrol->generateInvoice($bookingInfo, $bookingInfo->event_id);

        //unlink qr code
        if ($bookingInfo->variation != null) {
          //generate qr code for without wise ticket
          $variations = json_decode($bookingInfo->variation, true);
          foreach ($variations as $variation) {

            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '__' . $variation['unique_id'] . '.svg');
          }
        } else {
          //generate qr code for without wise ticket
          for ($i = 1; $i <= $bookingInfo->quantity; $i++) {
            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '__' . $i .  '.svg');
          }
        }

        // then, update the invoice field info in database
        $bookingInfo->invoice = $invoice;
        $bookingInfo->save();

        // send a mail to the customer with the invoice
        $enrol->sendMail($bookingInfo);
      } else {
        BookingInvoiceJob::dispatch($bookingInfo->id)->delay(now()->addSeconds(10));
      }

      //add blance to admin revinue
      $earning = Earning::first();
      $earning->total_revenue = $earning->total_revenue + $arrData['price'] + $bookingInfo->tax;
      if ($bookingInfo['organizer_id'] != null) {
        $earning->total_earning = $earning->total_earning + ($bookingInfo->tax + $bookingInfo->commission);
      } else {
        $earning->total_earning = $earning->total_earning + $arrData['price'] + $bookingInfo->tax;
      }
      $earning->save();

      //storeTransaction
      $bookingInfo['paymentStatus'] = 1;
      $bookingInfo['transcation_type'] = 1;

      storeTranscation($bookingInfo);

      //store amount to organizer
      $organizerData['organizer_id'] = $bookingInfo['organizer_id'];
      $organizerData['price'] = $arrData['price'];
      $organizerData['tax'] = $bookingInfo->tax;
      $organizerData['commission'] = $bookingInfo->commission;
      storeOrganizer($organizerData);

      // remove all session data
      $request->session()->forget('event_id');
      $request->session()->forget('selTickets');
      $request->session()->forget('arrData');
      $request->session()->forget('paymentId');
      $request->session()->forget('discount');
      return redirect()->route('event_booking.complete', ['id' => $eventId, 'booking_id' => $bookingInfo->id]);
    } else {
      // remove all session data
      $request->session()->forget('eventId');
      $request->session()->forget('arrData');
      $request->session()->forget('discount');

      return redirect()->route('event_booking.cancel', ['id' => $eventId]);
    }
  }
}
