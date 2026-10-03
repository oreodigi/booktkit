<?php

namespace App\Services;

use App\Models\AiTokenPackage;
use App\Models\Organizer;
use App\Models\OrganizerAiBalance;
use App\Models\OrganizerInfo;
use App\Models\OrganizerTokenPurchase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PDF;
use PHPMailer\PHPMailer\PHPMailer;

class OrganizerAiTokenPurchaseService
{
  public const SESSION_KEYS = [
    'organizer_ai_selected_package_id',
    'organizer_ai_purchase_invoice_no',
    'organizer_ai_purchase_id',
    'organizer_ai_last_purchase_id',
    'organizer_ai_token_cart',
    'organizer_ai_token_discount',
    'organizer_ai_arrData',
    'organizer_ai_payment_id',
    'organizer_ai_payment_reference',
    'organizer_ai_gateway_id',
    'organizer_ai_txref',
    'organizer_ai_iyzico_token',
    'organizer_ai_conversation_id',
    'organizer_ai_toyyibpay_ref_id',
    'organizer_ai_yoco_id',
    'organizer_ai_yoco_secret_key',
    'organizer_ai_razorpay_order_id',
    'organizer_ai_xendit_id',
    'organizer_ai_xendit_secret_key',
    'organizer_ai_xendit_payment_type',
    'organizer_ai_myfatoorah_payment_type',
    'organizer_ai_midtrans_token',
  ];

  public function createPendingPurchase(int $organizerId, AiTokenPackage $package, array $attributes = []): OrganizerTokenPurchase
  {
    return OrganizerTokenPurchase::create([
      'organizer_id' => $organizerId,
      'ai_token_package_id' => $package->id,
      'invoice_no' => $attributes['invoice_no'] ?? $this->generateInvoiceNo(),
      'ai_engine' => $package->ai_engine,
      'ai_token_limit' => $package->ai_token_limit,
      'ai_image_limit' => $package->ai_image_limit,
      'status' => $attributes['status'] ?? 'pending',
      'price' => $package->price,
      'payment_method' => $attributes['payment_method'] ?? null,
      'payment_status' => $attributes['payment_status'] ?? 'pending',
      'image' => $attributes['image'] ?? null,
      'conversation_id' => $attributes['conversation_id'] ?? null,
    ]);
  }

  public function prepareCheckoutSession(AiTokenPackage $package, ?string $invoiceNo = null): void
  {
    $discount = $this->getNeutralizedDiscount((float) $package->price);
    $invoiceNo = $invoiceNo ?: $this->generateInvoiceNo();

    Session::forget('organizer_ai_purchase_id');
    Session::forget('organizer_ai_last_purchase_id');
    Session::put('organizer_ai_token_cart', [
      $package->id => [
        'name' => $package->title,
        'price' => (float) $package->price,
        'qty' => 1,
        'photo' => null,
      ],
    ]);
    Session::put('organizer_ai_selected_package_id', $package->id);
    Session::put('organizer_ai_purchase_invoice_no', $invoiceNo);
    Session::put('organizer_ai_token_discount', $discount);
  }

  public function getSelectedPackage(): ?AiTokenPackage
  {
    $packageId = Session::get('organizer_ai_selected_package_id');

    if (empty($packageId)) {
      return null;
    }

    return AiTokenPackage::where('status', 1)->find($packageId);
  }

  public function getCheckoutInvoiceNo(): ?string
  {
    return Session::get('organizer_ai_purchase_invoice_no');
  }

  public function getCurrentPurchase(?int $organizerId = null): ?OrganizerTokenPurchase
  {
    $purchaseId = Session::get('organizer_ai_purchase_id') ?? Session::get('organizer_ai_last_purchase_id');

    if (empty($purchaseId)) {
      return null;
    }

    $query = OrganizerTokenPurchase::query()->where('id', $purchaseId);

    if (!is_null($organizerId)) {
      $query->where('organizer_id', $organizerId);
    }

    return $query->first();
  }

  public function updatePaymentMethod(OrganizerTokenPurchase $purchase, string $paymentMethod): OrganizerTokenPurchase
  {
    $purchase->update([
      'payment_method' => $paymentMethod,
      'payment_status' => $purchase->payment_status === 'paid' ? 'paid' : 'pending',
    ]);

    return $purchase->fresh();
  }

  public function storeGatewayResult(array $info): OrganizerTokenPurchase
  {
    $purchase = $this->getCurrentPurchase();

    if (empty($purchase)) {
      $purchase = $this->createPurchaseFromCheckoutSession($info);
    }

    $paymentStatus = $this->resolvePaymentStatus($info);

    $purchase->update([
      'payment_method' => $info['method'] ?? $purchase->payment_method,
      'payment_status' => $paymentStatus,
      'image' => $info['image'] ?? $purchase->image,
      'conversation_id' => $info['conversation_id'] ?? $purchase->conversation_id,
    ]);

    Session::put('organizer_ai_purchase_id', $purchase->id);
    Session::put('organizer_ai_last_purchase_id', $purchase->id);

    return $purchase->fresh();
  }

  public function storeProofImage(UploadedFile $file): string
  {
    $fileName = uniqid('ai-token-proof-') . '.' . $file->getClientOriginalExtension();
    $directory = public_path('assets/admin/file/organizer-ai-token/proofs/');

    @mkdir($directory, 0775, true);
    $file->move($directory, $fileName);

    return $fileName;
  }

  public function updateConversationId(OrganizerTokenPurchase $purchase, string $conversationId, ?string $paymentMethod = null): OrganizerTokenPurchase
  {
    $purchase->update([
      'conversation_id' => $conversationId,
      'payment_method' => $paymentMethod ?? $purchase->payment_method,
    ]);

    return $purchase->fresh();
  }

  public function finalizeGatewayPurchase(array $info, bool $sendMail = true): OrganizerTokenPurchase
  {
    $purchase = $this->storeGatewayResult($info);
    $this->generateInvoice($purchase);

    if ($sendMail === true) {
      $this->sendPurchaseMail($purchase);
    }

    return $purchase;
  }

  public function markPaymentFailed(?OrganizerTokenPurchase $purchase = null): ?OrganizerTokenPurchase
  {
    $purchase = $purchase ?: $this->getCurrentPurchase();

    if (empty($purchase) || $purchase->payment_status === 'paid') {
      return $purchase;
    }

    $purchase->update([
      'payment_status' => 'failed',
    ]);

    return $purchase->fresh();
  }

  public function clearCheckoutSession(): void
  {
    Session::forget(self::SESSION_KEYS);
  }

  public function generateInvoice(OrganizerTokenPurchase $purchase): string
  {
    $fileName = $purchase->invoice_no . '.pdf';
    $directory = public_path('assets/admin/file/organizer-ai-token/invoices/');

    @mkdir($directory, 0775, true);

    $organizer = Organizer::find($purchase->organizer_id);
    $organizerInfo = OrganizerInfo::where('organizer_id', $purchase->organizer_id)->first();
    $packageTitle = $this->getPackageTitle($purchase);
    $fileLocated = $directory . $fileName;

    PDF::loadView('organizer.ai-tokens.pdf.invoice', compact('purchase', 'organizer', 'organizerInfo', 'packageTitle'))->save($fileLocated);

    return $fileName;
  }

  public function sendPurchaseMail(OrganizerTokenPurchase $purchase): void
  {
    $subject = $purchase->payment_status === 'paid' ? 'AI token package payment confirmation' : 'AI token package purchase submitted';
    $this->sendMailView($purchase, $subject, 'organizer.ai-tokens.mail.purchase-success');
  }

  public function sendApprovalMail(OrganizerTokenPurchase $purchase): void
  {
    $this->sendMailView($purchase, 'AI token package approved', 'organizer.ai-tokens.mail.approved');
  }

  public function syncBalanceOnApproval(OrganizerTokenPurchase $purchase): OrganizerAiBalance
  {
    $balance = OrganizerAiBalance::where('organizer_id', $purchase->organizer_id)
      ->where('ai_engine', $purchase->ai_engine)
      ->first();

    if (empty($balance)) {
      return OrganizerAiBalance::create([
        'organizer_id' => $purchase->organizer_id,
        'ai_engine' => $purchase->ai_engine,
        'ai_token_balance' => $purchase->ai_token_limit,
        'ai_image_balance' => $purchase->ai_image_limit,
        'total_ai_token_purchased' => $purchase->ai_token_limit,
        'total_ai_image_purchased' => $purchase->ai_image_limit,
        'total_ai_token_used' => 0,
      ]);
    }

    $balance->update([
      'ai_token_balance' => $balance->ai_token_balance + $purchase->ai_token_limit,
      'ai_image_balance' => $balance->ai_image_balance + $purchase->ai_image_limit,
      'total_ai_token_purchased' => $balance->total_ai_token_purchased + $purchase->ai_token_limit,
      'total_ai_image_purchased' => $balance->total_ai_image_purchased + $purchase->ai_image_limit,
    ]);

    return $balance->fresh();
  }

  public function getPackageTitle(OrganizerTokenPurchase $purchase): string
  {
    $package = AiTokenPackage::find($purchase->ai_token_package_id);

    return $package->title ?? ('Package #' . $purchase->ai_token_package_id);
  }

  public function getOrganizerName(?Organizer $organizer, ?OrganizerInfo $organizerInfo = null): string
  {
    if (!empty($organizerInfo) && !empty($organizerInfo->name)) {
      return $organizerInfo->name;
    }

    if (!empty($organizer) && !empty($organizer->username)) {
      return $organizer->username;
    }

    return 'Organizer';
  }

  protected function sendMailView(OrganizerTokenPurchase $purchase, string $subject, string $viewName): void
  {
    $organizer = Organizer::find($purchase->organizer_id);

    if (empty($organizer) || empty($organizer->email)) {
      return;
    }

    $organizerInfo = OrganizerInfo::where('organizer_id', $purchase->organizer_id)->first();
    $packageTitle = $this->getPackageTitle($purchase);
    $invoiceFile = $this->generateInvoice($purchase);
    $invoicePath = public_path('assets/admin/file/organizer-ai-token/invoices/') . $invoiceFile;
    $info = DB::table('basic_settings')
      ->select('website_title', 'smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')
      ->first();

    $mailBody = view($viewName, [
      'purchase' => $purchase,
      'organizer' => $organizer,
      'organizerInfo' => $organizerInfo,
      'organizerName' => $this->getOrganizerName($organizer, $organizerInfo),
      'packageTitle' => $packageTitle,
      'websiteTitle' => $info->website_title ?? config('app.name'),
    ])->render();

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    if (!empty($info) && (int) $info->smtp_status === 1) {
      $mail->isSMTP();
      $mail->Host = $info->smtp_host;
      $mail->SMTPAuth = true;
      $mail->Username = $info->smtp_username;
      $mail->Password = $info->smtp_password;

      if ($info->encryption === 'TLS') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      }

      $mail->Port = $info->smtp_port;
    }

    try {
      $mail->setFrom($info->from_mail, $info->from_name);
      $mail->addAddress($organizer->email);
      $mail->addAttachment($invoicePath);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $mailBody;
      $mail->send();
      if (file_exists($invoicePath)) {
        @unlink($invoicePath);
      }
    } catch (\Exception $exception) {
    }
  }

  protected function generateInvoiceNo(): string
  {
    do {
      $invoiceNo = 'AITP-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    } while (OrganizerTokenPurchase::where('invoice_no', $invoiceNo)->exists());

    return $invoiceNo;
  }

  protected function getNeutralizedDiscount(float $price): float
  {
    $taxPercentage = (float) DB::table('basic_settings')->value('shop_tax');

    if ($taxPercentage <= 0) {
      return 0.00;
    }

    $rate = $taxPercentage / 100;

    return round(($price * $rate) / (1 + $rate), 2);
  }

  protected function createPurchaseFromCheckoutSession(array $info): OrganizerTokenPurchase
  {
    $package = $this->getSelectedPackage();

    if (empty($package)) {
      abort(404, 'Organizer AI token package not found.');
    }

    $organizerId = (int) ($info['user_id'] ?? 0);

    if ($organizerId <= 0) {
      abort(404, 'Organizer not found for AI token purchase.');
    }

    return $this->createPendingPurchase($organizerId, $package, [
      'invoice_no' => $this->getCheckoutInvoiceNo(),
      'payment_method' => $info['method'] ?? null,
      'payment_status' => $this->resolvePaymentStatus($info),
      'image' => $info['image'] ?? null,
      'conversation_id' => $info['conversation_id'] ?? null,
    ]);
  }

  protected function resolvePaymentStatus(array $info): string
  {
    $gatewayStatus = strtolower((string) ($info['payment_status'] ?? 'pending'));
    $gatewayType = strtolower((string) ($info['gateway_type'] ?? ''));

    if ($gatewayStatus === 'failed') {
      return 'failed';
    }

    if ($gatewayType === 'online' && in_array($gatewayStatus, ['completed', 'paid'])) {
      return 'paid';
    }

    return 'pending';
  }
}
