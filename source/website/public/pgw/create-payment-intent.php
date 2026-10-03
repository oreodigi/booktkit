<?php
header('Content-Type: application/json');

// ---- CORS (safe default for mobile; tighten for web origins if needed)
$config = require dirname(__DIR__, 2) . '/public/config.php';

$allowed = $config['ALLOWED_ORIGINS'] ?? [];
$origin  = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin && in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header('Vary: Origin');
} else {
  header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
// ----

$stripeSecret = $config['STRIPE_SECRET_KEY'] ?? '';
if (!$stripeSecret) {
  http_response_code(500);
  echo json_encode(['error' => 'Missing STRIPE_SECRET_KEY']);
  exit;
}

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?: [];

$amount   = $input['amount']   ?? null;
$currency = $input['currency'] ?? 'usd';

// Validate amount is an integer >= 1
if (!is_int($amount)) {
  // try coercion if numeric
  if (is_numeric($amount)) {
    $amount = (int) $amount;
  } else {
    http_response_code(400);
    echo json_encode(['error' => 'amount must be an integer in the smallest currency unit']);
    exit;
  }
}
if ($amount < 1) {
  http_response_code(400);
  echo json_encode(['error' => 'amount must be >= 1']);
  exit;
}

// Create PaymentIntent (card only for PaymentSheet)
$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecret,
    'Content-Type: application/x-www-form-urlencoded',
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query([
    'amount' => $amount,
    'currency' => $currency,
    'payment_method_types[]' => 'card',
  ]),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe did not return a response', 'curl_error' => $err]);
  exit;
}

http_response_code($code ?: 500);
echo $resp;
