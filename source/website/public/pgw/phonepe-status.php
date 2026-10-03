<?php
header('Content-Type: application/json');

/**
 * GET /api/phonepe-status.php?merchant_txn_id=txn_...
 * Returns: {"status":"SUCCESS"} | "PENDING" | "FAILED" | ...
 */

function cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'PHONEPE_BASE'        => getenv('PHONEPE_BASE') ?: 'https://api-preprod.phonepe.com/apis/pg-sandbox',
    'PHONEPE_MERCHANT_ID' => getenv('PHONEPE_MERCHANT_ID') ?: '',
    'PHONEPE_SALT_KEY'    => getenv('PHONEPE_SALT_KEY') ?: '',
    'PHONEPE_SALT_INDEX'  => getenv('PHONEPE_SALT_INDEX') ?: '1',
  ];
}

try {
  $cfg = cfg();
  $base = rtrim((string)$cfg['PHONEPE_BASE'], '/');
  $mid  = trim((string)$cfg['PHONEPE_MERCHANT_ID']);
  $salt = (string)$cfg['PHONEPE_SALT_KEY'];
  $sIdx = (string)$cfg['PHONEPE_SALT_INDEX'];

  if (!$mid || !$salt) { http_response_code(500); echo json_encode(['error'=>'PHONEPE config missing']); exit; }

  $mtx = $_GET['merchant_txn_id'] ?? null;
  if (!$mtx) { http_response_code(400); echo json_encode(['error'=>'no merchant_txn_id']); exit; }

  // For status, X-VERIFY = sha256(path + saltKey) + '###' + saltIndex  (NO payload here)
  $path = '/pg/v1/status/'.$mid.'/'.$mtx;
  $xverify = hash('sha256', $path . $salt) . '###' . $sIdx;

  $url = $base . $path;

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
      'Content-Type: application/json',
      'X-VERIFY: '.$xverify,
      'X-MERCHANT-ID: '.$mid,
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
    echo json_encode(['error'=>'Non-JSON / HTTP '.$code, 'raw'=>$resBody]);
    exit;
  }

  if (($res['success'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(['error'=>$res['code'] ?? 'STATUS_ERROR', 'message'=>$res['message'] ?? null, 'raw'=>$res]);
    exit;
  }

  // Typical values: SUCCESS / PENDING / FAILED
  $status = $res['data']['state'] ?? 'UNKNOWN';
  echo json_encode(['status' => $status, 'raw' => ['code'=>$res['code'] ?? null]]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
