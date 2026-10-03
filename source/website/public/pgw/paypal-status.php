<?php
header('Content-Type: application/json');
require __DIR__ . '/paypal-lib.php';

try {
  $cfg = pp_cfg();
  $base = rtrim($cfg['PAYPAL_BASE'],'/');
  $clientId = $cfg['PAYPAL_CLIENT_ID'];
  $secret   = $cfg['PAYPAL_SECRET'];
  if (!$clientId || !$secret) { http_response_code(500); echo json_encode(['error'=>'PAYPAL_CLIENT_ID/SECRET missing']); exit; }

  $orderId = $_GET['order_id'] ?? null;
  if (!$orderId) { http_response_code(400); echo json_encode(['error'=>'no order_id']); exit; }

  $token = pp_access_token($base, $clientId, $secret);
  $url   = $base . '/v2/checkout/orders/' . urlencode($orderId);

  [$code, $res] = pp_request('GET', $url, [
    'Authorization: Bearer '.$token,
    'Content-Type: application/json',
  ]);

  if ($code >= 300) {
    http_response_code($code);
    echo json_encode(['error'=>$res['message'] ?? 'Status error', 'raw'=>$res]);
    exit;
  }

  echo json_encode(['status'=>$res['status'] ?? 'UNKNOWN', 'raw'=>$res]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
