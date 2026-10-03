<?php
header('Content-Type: application/json');

// ---- CORS
$config = require dirname(__DIR__, 2) . '/config.php';
$allowed = $config['ALLOWED_ORIGINS'] ?? [];
$origin  = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin && in_array($origin, $allowed, true)) { header("Access-Control-Allow-Origin: $origin"); header('Vary: Origin'); }
else { header('Access-Control-Allow-Origin: *'); }
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
// ----

$serverKey = $config['MIDTRANS_SERVER_KEY'] ?? '';
if (!$serverKey) { http_response_code(500); echo json_encode(['error'=>'Missing MIDTRANS_SERVER_KEY']); exit; }

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) { http_response_code(400); echo json_encode(['error'=>'order_id is required']); exit; }

$url = "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($serverKey . ':'),
  ],
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

http_response_code($code ?: 500);
echo $resp;
