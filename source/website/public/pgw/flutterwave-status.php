<?php
header('Content-Type: application/json');

/**
 * Verifies by reference:
 *   GET /api/flutterwave-status.php?tx_ref=fw_20250831_...
 * Response: {"status":"successful"} | {"status":"pending"} | {"status":"failed"}
 */

function fw_status_log($msg) {
  @file_put_contents(sys_get_temp_dir().'/flutterwave_status.log',
    '['.date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

// Load config
foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
  if (is_file($p)) { $cfg = require $p; break; }
}
if (empty($cfg)) {
  $cfg = [
    'FLW_SECRET_KEY' => getenv('FLW_SECRET_KEY') ?: '',
    'FLW_BASE'       => getenv('FLW_BASE') ?: 'https://api.flutterwave.com',
  ];
}

$base = rtrim((string)($cfg['FLW_BASE'] ?? 'https://api.flutterwave.com'), '/');
$key  = trim((string)($cfg['FLW_SECRET_KEY'] ?? ''));

if ($key === '') {
  http_response_code(500);
  echo json_encode(['error' => 'FLW_SECRET_KEY missing']);
  exit;
}

$txRef = $_GET['tx_ref'] ?? null;
if (!$txRef) {
  http_response_code(400);
  echo json_encode(['error' => 'no tx_ref']);
  exit;
}

// Verify by reference
$url = $base . '/v3/transactions/verify_by_reference?tx_ref=' . urlencode($txRef);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER     => [
    'Authorization: Bearer ' . $key,
    'Content-Type: application/json',
  ],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 30,
]);
$resBody = curl_exec($ch);
$err     = curl_error($ch);
$code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
  http_response_code(500);
  echo json_encode(['error' => $err]);
  exit;
}

$res = json_decode($resBody, true);
if (!is_array($res)) {
  http_response_code($code ?: 500);
  echo json_encode(['error' => 'Non-JSON response', 'raw' => $resBody]);
  exit;
}

if ($code >= 300 || empty($res['status']) || $res['status'] !== 'success') {
  http_response_code($code ?: 422);
  echo json_encode(['error' => $res['message'] ?? 'Verify error', 'raw' => $res]);
  exit;
}

// Normalize: look into data[0] or data when single object
$status = 'pending';
if (isset($res['data'][0]['status'])) {
  $status = strtolower((string)$res['data'][0]['status']);
} elseif (isset($res['data']['status'])) {
  $status = strtolower((string)$res['data']['status']);
}

echo json_encode(['status' => $status]); // "successful" | "pending" | "failed"
