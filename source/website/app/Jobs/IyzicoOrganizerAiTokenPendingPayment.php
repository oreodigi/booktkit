<?php

namespace App\Jobs;

use App\Models\OrganizerTokenPurchase;
use Config\Iyzipay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IyzicoOrganizerAiTokenPendingPayment implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $purchaseId;

  public function __construct($purchaseId)
  {
    $this->purchaseId = $purchaseId;
  }

  public function handle()
  {
    $purchase = OrganizerTokenPurchase::where('id', $this->purchaseId)
      ->where('payment_method', 'iyzipay')
      ->where('payment_status', 'pending')
      ->first();

    if (empty($purchase) || empty($purchase->conversation_id)) {
      return;
    }

    $options = Iyzipay::options();

    $request = new \Iyzipay\Request\ReportingPaymentDetailRequest();
    $request->setPaymentConversationId($purchase->conversation_id);

    $paymentResponse = \Iyzipay\Model\ReportingPaymentDetail::create($request, $options);
    $result = (array) $paymentResponse;

    foreach ($result as $data) {
      $data = json_decode($data, true);

      if ($data['status'] == 'success' && !is_null($data['payments']) && is_array($data['payments'])) {
        if (($data['payments'][0]['paymentStatus'] ?? null) == 1) {
          $purchase->update([
            'payment_status' => 'paid',
          ]);
        }
      }
    }
  }
}
