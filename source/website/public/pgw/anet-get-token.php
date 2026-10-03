<?php
header('Content-Type: application/json');

// CORS (safe default)
$config = require dirname(__DIR__, 2).'/config.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin && in_array($config['ALLOWED_ORIGINS'] ?? [], true)) { header("Access-Control-Allow-Origin: $origin"); header('Vary: Origin'); }
else { header('Access-Control-Allow-Origin: *'); }
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$loginId = trim($config['AUTHORIZE_LOGIN_ID'] ?? '');
$tranKey = trim($config['AUTHORIZE_TRANSACTION_KEY'] ?? '');
$env     = strtolower($config['AUTHORIZE_ENV'] ?? 'sandbox');
if ($loginId === '' || $tranKey === '') {
  http_response_code(500);
  echo json_encode(['error' => 'Missing Authorize.Net login/transaction key']);
  exit;
}
$apiUrl = ($env === 'production')
  ? 'https://api2.authorize.net/xml/v1/request.api'
  : 'https://apitest.authorize.net/xml/v1/request.api';

// Read amount (as decimal string, e.g. "100.00")
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$amount = isset($in['amount']) ? (string)$in['amount'] : null; // "100.00"
if (!$amount) { http_response_code(400); echo json_encode(['error' => 'amount (like "100.00") required']); exit; }

// Build absolute URLs for return/cancel/checkout helper
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = $scheme.'://'.$host.'/api';
$returnUrl = $base.'/anet-return.php';
$cancelUrl = $base.'/anet-cancel.php';

$payload = [
  "getHostedPaymentPageRequest" => [
    "merchantAuthentication" => [
      "name"           => $loginId,
      "transactionKey" => $tranKey
    ],
    // optional: for your own tracking
    // "refId" => "ref-".time(),
    "transactionRequest" => [
      "transactionType" => "authCaptureTransaction",
      "amount"          => $amount,
      "currencyCode"    => "USD",
      // You can add "order": {"invoiceNumber":"INV-123","description":"..."}
    ],
    "hostedPaymentSettings" => [
      "setting" => [
        [
          "settingName"  => "hostedPaymentReturnOptions",
          "settingValue" => json_encode([
            "showReceipt"   => true,
            "url"           => $returnUrl,
            "urlText"       => "Continue",
            "cancelUrl"     => $cancelUrl,
            "cancelUrlText" => "Cancel"
          ])
        ],
        [
          "settingName"  => "hostedPaymentPaymentOptions",
          "settingValue" => json_encode(["cardCodeRequired" => true])
        ],
        [
          "settingName"  => "hostedPaymentBillingAddressOptions",
          "settingValue" => json_encode(["show" => true])
        ],
        [
          "settingName"  => "hostedPaymentButtonOptions",
          "settingValue" => json_encode(["text" => "Pay"])
        ]
      ]
    ]
  ]
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => json_encode($payload),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) {
  http_response_code(500);
  echo json_encode(['error'=>'cURL error','detail'=>$err]);
  exit;
}
$data = json_decode($resp, true);
if (!$data) { http_response_code($code ?: 500); echo $resp; exit; }

$token = $data['token'] ?? null;
if (!$token) {
  http_response_code($code ?: 500);
  echo json_encode(['error'=>'No token from Authorize.Net','raw'=>$data]);
  exit;
}

// Build a helper URL that auto-posts token to Accept Hosted
$checkoutUrl = $base . '/anet-checkout.php?token=' . urlencode($token);

http_response_code(200);
echo json_encode([
  'token'        => $token,
  'checkout_url' => $checkoutUrl,
  'env'          => $env
]);
