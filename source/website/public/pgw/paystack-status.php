<?php
header('Content-Type: application/json');

/**
 * GET /api/paystack-status.php?reference=ref_...
 * Returns: {"status":"success"} or "failed" / "abandoned"
 */

function ps_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'PAYSTACK_SECRET_KEY' => getenv('PAYSTACK_SECRET_KEY') ?: '',
    'PAYSTACK_BASE'       => getenv('PAYSTACK_BASE') ?: 'https://api.paystack.co',
  ];
}

try {
  $cfg  = ps_cfg();
  $base = rtrim((string)($cfg['PAYSTACK_BASE'] ?? 'https://api.paystack.co'), '/');
  $key  = trim((string)($cfg['PAYSTACK_SECRET_KEY'] ?? ''));
  if ($key === '') { http_response_code(500); echo json_encode(['error'=>'PAYSTACK_SECRET_KEY missing']); exit; }

  $reference = $_GET['reference'] ?? null;
  if (!$reference) { http_response_code(400); echo json_encode(['error'=>'no reference']); exit; }

  $ch = curl_init($base.'/transaction/verify/'.urlencode($reference));
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer '.$key,
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
    echo json_encode(['error'=>'Non-JSON/HTTP '.$code, 'raw'=>$resBody]);
    exit;
  }
  if (($res['status'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(['error'=>$res['message'] ?? 'verify error', 'raw'=>$res]);
    exit;
  }

  // Typical: "success" | "failed" | "abandoned"
  $status = strtolower((string)($res['data']['status'] ?? 'unknown'));
  $amount = $res['data']['amount'] ?? null;
  $paidAt = $res['data']['paid_at'] ?? null;

  echo json_encode(['status'=>$status, 'amount'=>$amount, 'paid_at'=>$paidAt]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
