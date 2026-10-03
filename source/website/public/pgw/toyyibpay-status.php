<?php
header('Content-Type: application/json');

/**
 * GET /api/toyyibpay-status.php?billCode=XXXX
 * Response example:
 *  {"status":"1"}  // 1=paid, 2=pending, 3=failed
 */

function tp_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'TOYYIBPAY_BASE' => getenv('TOYYIBPAY_BASE') ?: 'https://dev.toyyibpay.com',
  ];
}

try {
  $cfg  = tp_cfg();
  $base = rtrim((string)$cfg['TOYYIBPAY_BASE'],'/');
  $bill = $_GET['billCode'] ?? null;
  if (!$bill) { http_response_code(400); echo json_encode(['error'=>'no billCode']); exit; }

  $url = $base.'/index.php/api/getBillTransactions?billCode='.urlencode($bill);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
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
    echo json_encode(['error'=>'Non-JSON / HTTP '.$code, 'raw'=>$resBody]);
    exit;
  }

  // Toyyib returns an array; take the first transaction if present
  if (count($res) === 0) { echo json_encode(['status'=>'2']); exit; } // treat as pending
  $tx = $res[0];

  $status = (string)($tx['billpaymentStatus'] ?? $tx['billPaymentStatus'] ?? '');
  echo json_encode(['status'=>$status ?: '2', 'raw'=>$tx]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
