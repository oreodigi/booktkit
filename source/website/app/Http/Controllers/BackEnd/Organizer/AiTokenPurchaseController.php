<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\FlutterwaveController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\InstamojoController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\IyzipayController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\MercadoPagoController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\MidtransController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\MollieController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\MyFatoorahController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\OfflineController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\PaypalController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\PaystackController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\PaytabsController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\PaytmController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\PerfectMoneyController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\PhonepeController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\RazorpayController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\StripeController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\ToyyibpayController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\XenditController;
use App\Http\Controllers\BackEnd\Organizer\PaymentGateway\YocoController;
use App\Http\Controllers\Controller;
use App\Models\AiTokenPackage;
use App\Models\OrganizerInfo;
use App\Models\OrganizerTokenPurchase;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\PaymentGateway\OnlineGateway;
use App\Services\OrganizerAiTokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AiTokenPurchaseController extends Controller
{
  protected OrganizerAiTokenPurchaseService $service;

  public function __construct(OrganizerAiTokenPurchaseService $service)
  {
    $this->service = $service;
  }

  public function packages()
  {
    $packages = AiTokenPackage::where('status', 1)->orderBy('id', 'desc')->get();

    return view('organizer.ai-tokens.packages', compact('packages'));
  }

  public function startCheckout($id)
  {
    $package = AiTokenPackage::where('status', 1)->findOrFail($id);
    $this->service->prepareCheckoutSession($package);

    if ((float) $package->price <= 0) {
      $organizer = Auth::guard('organizer')->user();

      $purchase = DB::transaction(function () use ($organizer, $package) {
        $purchase = $this->service->createPendingPurchase($organizer->id, $package, [
          'invoice_no' => $this->service->getCheckoutInvoiceNo(),
          'payment_method' => 'free',
          'payment_status' => 'paid',
          'status' => 'approved',
        ]);

        $this->service->syncBalanceOnApproval($purchase);

        return $purchase;
      });

      Session::put('organizer_ai_purchase_id', $purchase->id);
      Session::put('organizer_ai_last_purchase_id', $purchase->id);

      $this->service->generateInvoice($purchase);
      $this->service->sendPurchaseMail($purchase);

      return redirect()->route('organizer.ai_token_purchase.complete');
    }

    return redirect()->route('organizer.ai_token_purchase.checkout');
  }

  public function checkout()
  {
    $organizer = Auth::guard('organizer')->user();
    $package = $this->service->getSelectedPackage();

    if (empty($package)) {
      return redirect()->route('organizer.ai_token_purchase.packages');
    }

    $organizerInfo = OrganizerInfo::where('organizer_id', $organizer->id)->first();
    $onlineGateways = OnlineGateway::where('status', 1)->get();
    $offlineGateways = OfflineGateway::where('status', 1)->orderBy('serial_number', 'asc')->get();
    $stripeGateway = $onlineGateways->where('keyword', 'stripe')->first();
    $stripeInfo = !empty($stripeGateway) ? json_decode($stripeGateway->information, true) : [];
    $stripeKey = $stripeInfo['key'] ?? null;
    $packageTitle = $package->title;
    $checkoutInvoiceNo = $this->service->getCheckoutInvoiceNo();

    return view('organizer.ai-tokens.checkout', compact(
      'package',
      'organizer',
      'organizerInfo',
      'onlineGateways',
      'offlineGateways',
      'stripeKey',
      'packageTitle',
      'checkoutInvoiceNo'
    ));
  }

  public function pay(Request $request)
  {
    $organizer = Auth::guard('organizer')->user();
    $package = $this->service->getSelectedPackage();

    if (empty($package)) {
      return redirect()->route('organizer.ai_token_purchase.packages');
    }

    if ((float) $package->price <= 0) {
      return redirect()->route('organizer.ai_token_purchase.packages')->with([
        'alert-type' => 'warning',
        'message' => 'Free AI token packages are activated directly from the package list.',
      ]);
    }

    $request->validate([
      'fname' => 'required|string|max:255',
      'lname' => 'required|string|max:255',
      'email' => 'required|email',
      'phone' => 'required|string|max:50',
      'country' => 'required|string|max:255',
      'city' => 'required|string|max:255',
      'zip_code' => 'required|string|max:100',
      'address' => 'required|string',
      'gateway' => 'required',
      'identity_number' => $request->gateway == 'iyzico' ? 'required|string|max:255' : 'nullable',
    ], [
      'gateway.required' => 'The payment gateway field is required.',
    ]);

    $this->service->prepareCheckoutSession($package, $this->service->getCheckoutInvoiceNo());

    if (is_numeric($request->gateway)) {
      $offline = app(OfflineController::class);

      return $offline->enrolmentProcess($request);
    }

    if ($request->gateway == 'paypal') {

      return app(PaypalController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'razorpay') {
      return app(RazorpayController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'instamojo') {
      return app(InstamojoController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'paystack') {
      return app(PaystackController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'flutterwave') {
      return app(FlutterwaveController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'mercadopago') {
      return app(MercadoPagoController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'mollie') {
      return app(MollieController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'stripe') {
      return app(StripeController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'paytm') {
      return app(PaytmController::class)->enrolmentProcess($request);
    } elseif ($request->gateway == 'midtrans') {
      return app(MidtransController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'iyzico') {
      return app(IyzipayController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'paytabs') {
      return app(PaytabsController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'toyyibpay') {
      return app(ToyyibpayController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'phonepe') {
      return app(PhonepeController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'yoco') {
      return app(YocoController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'xendit') {
      return app(XenditController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'myfatoorah') {
      return app(MyFatoorahController::class)->purchaseProcess($request);
    } elseif ($request->gateway == 'perfect_money') {
      return app(PerfectMoneyController::class)->purchaseProcess($request);
    }

    return redirect()->route('organizer.ai_token_purchase.checkout')->with([
      'alert-type' => 'error',
      'message' => 'Please select a valid payment gateway.',
    ]);
  }

  public function history()
  {
    $purchases = OrganizerTokenPurchase::leftJoin('ai_token_packages', 'organizer_token_purchases.ai_token_package_id', '=', 'ai_token_packages.id')
      ->where('organizer_token_purchases.organizer_id', Auth::guard('organizer')->user()->id)
      ->select('organizer_token_purchases.*', 'ai_token_packages.title as package_title')
      ->orderByDesc('organizer_token_purchases.id')
      ->paginate(10);

    return view('organizer.ai-tokens.history', compact('purchases'));
  }

  public function details($id)
  {
    $purchase = OrganizerTokenPurchase::where('organizer_id', Auth::guard('organizer')->user()->id)->findOrFail($id);
    $packageTitle = $this->service->getPackageTitle($purchase);

    return view('organizer.ai-tokens.details', compact('purchase', 'packageTitle'));
  }

  public function complete()
  {
    $purchase = $this->service->getCurrentPurchase(Auth::guard('organizer')->user()->id);

    if (empty($purchase)) {
      $purchase = OrganizerTokenPurchase::where('organizer_id', Auth::guard('organizer')->user()->id)
        ->find(Session::get('organizer_ai_last_purchase_id'));
    }

    if (empty($purchase)) {
      return redirect()->route('organizer.ai_token_purchase.history');
    }

    $packageTitle = $this->service->getPackageTitle($purchase);
    $this->service->clearCheckoutSession();

    return view('organizer.ai-tokens.success', compact('purchase', 'packageTitle'));
  }

  public function cancel()
  {
    $this->service->clearCheckoutSession();

    return redirect()->route('organizer.ai_token_purchase.history')->with([
      'alert-type' => 'error',
      'message' => 'Payment canceled.',
    ]);
  }

  public function storeData($info)
  {
    return $this->service->storeGatewayResult($info);
  }

  public function storeOders($info)
  {
    return true;
  }

  public function generateInvoice($purchase)
  {
    return $this->service->generateInvoice($purchase);
  }

  public function sendMail($purchase)
  {
    $this->service->sendPurchaseMail($purchase);
  }

  protected function resolvePaymentMethod($gateway): string
  {
    if (is_numeric($gateway)) {
      return 'offline';
    }

    $onlineGateway = OnlineGateway::where('keyword', $gateway)->first();

    return $onlineGateway->name ?? ucfirst(str_replace('_', ' ', $gateway));
  }
}
