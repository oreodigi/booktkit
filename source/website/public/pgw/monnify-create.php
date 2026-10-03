<?php
header('Content-Type: application/json');
require __DIR__.'/_monnify_common.php';

/**
 * Body JSON:
 * {
 *   "amount_minor": 250000,       // ₦2,500.00 => 250000 kobo
 *   "name": "Buyer Name",
 *   "email": "buyer@example.com",
 *   "phone": "08012345678",
 *   "description": "Order #123"
 * }
 *
 * Success:
 * {
 *   "transactionReference":"MNFY|....",
 *   "paymentReference":"ref_..._xxxx",
 *   "redirect_url":"https://sandbox.monnify.com/checkout/..."
 * }
 */

try {
  $cfg   = mf_cfg();
  $base  = rtrim((string)$cfg['MONNIFY_BASE'], '/');
  $ccode = trim((string)$cfg['MONNIFY_CONTRACT_CODE']);
  if ($ccode === '') { http_response_code(500); echo json_encode(['error'=>'MONNIFY_CONTRACT_CODE missing']); exit; }

  $in = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
  $amountMinor = (int)($in['amount_minor'] ?? 0);
  $name  = trim((string)($in['name']  ?? 'Customer'));
  $email = trim((string)($in['email'] ?? 'customer@example.com'));
  $phone = trim((string)($in['phone'] ?? ''));
  $desc  = trim((string)($in['description'] ?? 'Checkout'));

  if ($amountMinor <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid amount_minor']); exit; }

  // Monnify expects decimal NGN string
  $amount = number_format($amountMinor, 2, '.', '');

  // Return URL (deep-link close)
  $retBase  = mf_public_base($cfg);
  $redirect = $retBase.'/monnify-return.php';

  // Unique merchant reference
  $paymentRef = 'ref_'.date('YmdHis').'_'.bin2hex(random_bytes(4));

  // 1) Fetch token (cached)
  $token = mf_get_token($cfg);

  // 2) Init transaction
  $payload = [
    'amount'               => $amount,
    'currencyCode'         => 'NGN',
    'customerName'         => $name,
    'customerEmail'        => $email,
    'customerPhoneNumber'  => $phone,
    'paymentReference'     => $paymentRef,
    'paymentDescription'   => $desc,
    'contractCode'         => $ccode,
    'redirectUrl'          => $redirect,
    'paymentMethods'       => ['CARD','ACCOUNT_TRANSFER'], // optional
  ];

  $ch = curl_init($base.'/api/v1/merchant/transactions/init-transaction');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer '.$token,
      'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
  ]);
  $resBody = curl_exec($ch);
  if ($resBody === false) {
    $err = curl_error($ch);
    $meta = curl_getinfo($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['error'=>"cURL init-transaction: $err", 'info'=>$meta]);
    exit;
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $js = json_decode($resBody, true);
  if ($code >= 300 || !is_array($js)) {
    http_response_code($code ?: 422);
    echo json_encode(['error'=>'HTTP '.$code.' from Monnify', 'raw'=>$resBody]);
    exit;
  }
  if (($js['requestSuccessful'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(['error'=>'Monnify init failed', 'raw'=>$js]);
    exit;
  }

  $body   = $js['responseBody'] ?? [];
  $trxRef = $body['transactionReference'] ?? null;
  $payRef = $body['paymentReference'] ?? $paymentRef;
  $url    = $body['checkoutUrl'] ?? null;

  if (!$trxRef || !$url) {
    http_response_code(500);
    echo json_encode(['error'=>'Missing checkoutUrl/transactionReference', 'raw'=>$body]);
    exit;
  }

  echo json_encode([
    'transactionReference' => $trxRef,
    'paymentReference'     => $payRef,
    'redirect_url'         => $url,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
