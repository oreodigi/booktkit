<?php
header('Content-Type: application/json');

// ---- CORS
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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
// ----

$stripeSecret = $config['STRIPE_SECRET_KEY'] ?? '';
if (!$stripeSecret) { http_response_code(500); echo json_encode(['error'=>'Missing STRIPE_SECRET_KEY']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$piId  = isset($input['payment_intent_id']) ? (string)$input['payment_intent_id'] : null;
$token = isset($input['token']) ? (string)$input['token'] : null;

function starts_with($h, $n) { return substr($h, 0, strlen($n)) === $n; }

// Try to extract tok_... from googlepay_token blob if token not provided
if (!$token && isset($input['googlepay_token'])) {
  $blob = $input['googlepay_token'];
  if (is_string($blob)) { $tmp = json_decode($blob, true); if (json_last_error() === JSON_ERROR_NONE) { $blob = $tmp; } }
  if (is_array($blob) && !empty($blob['id']) && starts_with($blob['id'], 'tok_')) {
    $token = $blob['id'];
  }
}

if (!$piId) { http_response_code(400); echo json_encode(['error'=>'payment_intent_id is required']); exit; }
if (!$token || !starts_with($token, 'tok_')) {
  http_response_code(400);
  echo json_encode(['error'=>'No Stripe card token (tok_...). Ensure Google Pay tokenization uses the Stripe gateway.']);
  exit;
}

$ch = curl_init("https://api.stripe.com/v1/payment_intents/$piId/confirm");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecret,
    'Content-Type: application/x-www-form-urlencoded',
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query([
    'payment_method_data[type]' => 'card',
    'payment_method_data[card][token]' => $token,
  ]),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code ?: 500);
echo $resp ?: json_encode(['error' => 'Stripe did not return a response']);
