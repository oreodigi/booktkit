<?php
header('Content-Type: application/json');
require __DIR__ . '/paypal-lib.php';

try {
  $cfg = pp_cfg();
  $base = rtrim($cfg['PAYPAL_BASE'],'/');
  $clientId = $cfg['PAYPAL_CLIENT_ID'];
  $secret   = $cfg['PAYPAL_SECRET'];
  if (!$clientId || !$secret) { http_response_code(500); echo json_encode(['error'=>'PAYPAL_CLIENT_ID/SECRET missing']); exit; }

  $raw = file_get_contents('php://input') ?: '';
  $in  = json_decode($raw, true) ?: [];

  $amountMinor = (int)($in['amount_minor'] ?? 0);   // e.g. 2599 => 25.99
  $currency    = strtoupper((string)($in['currency'] ?? 'USD'));
  $name        = (string)($in['name'] ?? 'Customer');
  $email       = (string)($in['email'] ?? 'customer@example.com');
  $desc        = (string)($in['description'] ?? 'Order');

  if ($amountMinor <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid amount_minor']); exit; }
  $amountVal = number_format($amountMinor/100, 2, '.', '');

  // Build return/cancel URLs (prefer public https base if provided)
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $localBase = rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
  $retBase = rtrim($cfg['PUBLIC_API_BASE'] ?: $localBase, '/');
  $returnUrl = $retBase . '/paypal-return.php';
  $cancelUrl = $retBase . '/paypal-return.php?cancel=1';

  $token = pp_access_token($base, $clientId, $secret);
  $url   = $base . '/v2/checkout/orders';

  $payload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
      'amount' => ['currency_code'=>$currency, 'value'=>$amountVal],
      'description' => $desc,
    ]],
    'application_context' => [
      'brand_name'          => 'Checkout',
      'landing_page'        => 'LOGIN',
      'user_action'         => 'PAY_NOW',
      'return_url'          => $returnUrl,
      'cancel_url'          => $cancelUrl,
    ],
    // Optional payer data helps prefill
    'payer' => [
      'name'  => ['given_name'=>$name],
      'email_address' => $email,
    ],
  ];

  [$code, $res] = pp_request('POST', $url, [
    'Authorization: Bearer '.$token,
    'Content-Type: application/json',
    'PayPal-Request-Id: '.('pp-'.time().'-'.bin2hex(random_bytes(4))), // idempotency
  ], $payload);

  if ($code >= 300 || empty($res['id'])) {
    http_response_code($code ?: 422);
    echo json_encode(['error'=>$res['message'] ?? 'PayPal create error', 'raw'=>$res]);
    exit;
  }

  $orderId = $res['id'];
  $approve = null;
  foreach (($res['links'] ?? []) as $lnk) {
    if (($lnk['rel'] ?? '') === 'approve') { $approve = $lnk['href']; break; }
  }
  if (!$approve) { http_response_code(500); echo json_encode(['error'=>'No approve link from PayPal', 'raw'=>$res]); exit; }

  echo json_encode(['order_id'=>$orderId, 'redirect_url'=>$approve]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
