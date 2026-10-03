<?php
header('Content-Type: application/json');

// ---- CORS
$config = require dirname(__DIR__, 2) . '/config.php';
$allowed = $config['ALLOWED_ORIGINS'] ?? [];
$origin  = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin && in_array($origin, $allowed, true)) { header("Access-Control-Allow-Origin: $origin"); header('Vary: Origin'); }
else { header('Access-Control-Allow-Origin: *'); }
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
// ----

$serverKey = $config['MIDTRANS_SERVER_KEY'] ?? '';
if (!$serverKey) { http_response_code(500); echo json_encode(['error'=>'Missing MIDTRANS_SERVER_KEY']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$amount   = isset($input['amount']) ? (int)$input['amount'] : null; // (IDR) integer
$orderId  = $input['order_id'] ?? ('order-'.time().'-'.mt_rand(1000,9999));
$name     = $input['name'] ?? 'Guest';
$email    = $input['email'] ?? 'guest@example.com';
$phone    = $input['phone'] ?? '081234567890';

if (!$amount || $amount < 1000) { http_response_code(400); echo json_encode(['error'=>'amount (IDR integer) required']); exit; }

// Snap (SANDBOX) endpoint
$url = $config['MIDTRANS_BASE'] ?? 'https://app.sandbox.midtrans.com/snap/v1/transactions';

$payload = [
  'transaction_details' => [
    'order_id'      => $orderId,
    'gross_amount'  => $amount,      // e.g., 10000 (IDR 10,000)
  ],
  'customer_details' => [
    'first_name' => $name,
    'email'      => $email,
    'phone'      => $phone,
  ],
  'item_details' => [
    ['id'=>'sku-1','price'=>$amount,'quantity'=>1,'name'=>'Sample Order'],
  ],
  'callbacks' => [
    // Snap will redirect here after finish; we intercept this custom scheme in WebView
    'finish' => 'myapp://midtrans-finish'
  ],
  // Limit methods if you want; otherwise Snap chooses dynamically:
  // 'enabled_payments' => ['credit_card','gopay','qris','shopeepay','bank_transfer'],
  'credit_card' => ['secure' => true],
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Accept: application/json',
    'Content-Type: application/json',
    // Basic auth: base64("SERVER_KEY:")
    'Authorization: Basic ' . base64_encode($serverKey . ':'),
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) {
  http_response_code(500);
  echo json_encode(['error' => 'cURL error', 'detail' => $err]);
  exit;
}

http_response_code($code ?: 500);
$out = json_decode($resp, true);
if (!$out) { echo $resp; exit; }

$out['order_id'] = $orderId;
echo json_encode($out);
