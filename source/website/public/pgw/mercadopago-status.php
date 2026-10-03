<?php
header('Content-Type: application/json');

/**
 * GET /api/mercadopago-status.php?external_reference=order-...
 * Response:
 *  { "status":"approved", "payment_id":"1234567890" }
 *  or pending / rejected / in_process / cancelled / refunded / charged_back
 */

function mp_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'MP_ACCESS_TOKEN' => getenv('MP_ACCESS_TOKEN') ?: '',
    'MP_BASE'         => getenv('MP_BASE') ?: 'https://api.mercadopago.com',
  ];
}

try {
  $cfg  = mp_cfg();
  $base = rtrim((string)$cfg['MP_BASE'], '/');
  $tok  = trim((string)$cfg['MP_ACCESS_TOKEN']);
  if ($tok === '') { http_response_code(500); echo json_encode(['error'=>'MP_ACCESS_TOKEN missing']); exit; }

  $extRef = $_GET['external_reference'] ?? null;
  if (!$extRef) { http_response_code(400); echo json_encode(['error'=>'no external_reference']); exit; }

  // Search payments by our external_reference
  $url = $base.'/v1/payments/search?sort=date_created&criteria=desc&external_reference='.urlencode($extRef);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer '.$tok,
      'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) { http_response_code(500); echo json_encode(['error'=>$err]); exit; }

  $res = json_decode($resBody, true);
  if ($code >= 300 || !is_array($res)) {
    http_response_code($code ?: 422);
    echo json_encode(['error'=>$res['message'] ?? 'search error', 'raw'=>$resBody]);
    exit;
  }

  $results = $res['results'] ?? [];
  if (!is_array($results) || count($results) === 0) {
    echo json_encode(['status'=>'pending', 'payment_id'=>null]); // created but not paid yet
    exit;
  }

  $latest = $results[0];
  $status = strtolower((string)($latest['status'] ?? 'unknown'));
  $pid    = $latest['id'] ?? null;

  echo json_encode(['status'=>$status, 'payment_id'=>$pid]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
