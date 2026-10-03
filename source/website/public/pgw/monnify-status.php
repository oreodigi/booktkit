<?php
header('Content-Type: application/json');
require __DIR__.'/_monnify_common.php';

/**
 * Check with either:
 *   GET /api/monnify-status.php?transactionReference=MNFY|...
 *   GET /api/monnify-status.php?paymentReference=ref_...
 *
 * Returns:
 *   { "status":"PAID", "transactionReference":"...", "paymentReference":"..." }
 */

try {
  $cfg   = mf_cfg();
  $base  = rtrim((string)$cfg['MONNIFY_BASE'], '/');
  $token = mf_get_token($cfg);

  $trxRef = $_GET['transactionReference'] ?? null;
  $payRef = $_GET['paymentReference'] ?? null;
  if (!$trxRef && !$payRef) {
    http_response_code(400);
    echo json_encode(['error'=>'Provide transactionReference or paymentReference']);
    exit;
  }

  $url = $trxRef
    ? $base.'/api/v2/transactions/'.rawurlencode($trxRef)
    : $base.'/api/v1/merchant/transactions/query?paymentReference='.rawurlencode($payRef);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$token],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
  ]);
  $resBody = curl_exec($ch);
  if ($resBody === false) {
    $err = curl_error($ch);
    $meta = curl_getinfo($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['error'=>"cURL status: $err", 'info'=>$meta]);
    exit;
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $js = json_decode($resBody, true);
  if ($code >= 300 || !is_array($js)) {
    http_response_code($code ?: 422);
    echo json_encode(['error'=>'HTTP '.$code.' from Monnify', 'raw'=>$resBody]);
    exit;
  }
  if (($js['requestSuccessful'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(['error'=>'Monnify status failed', 'raw'=>$js]);
    exit;
  }

  $body   = $js['responseBody'] ?? [];
  $status = strtoupper((string)($body['paymentStatus'] ?? $body['transactionStatus'] ?? 'UNKNOWN'));

  echo json_encode([
    'status'               => $status, // PAID, PENDING_PAYMENT, FAILED, REVERSED, etc.
    'transactionReference' => $body['transactionReference'] ?? null,
    'paymentReference'     => $body['paymentReference'] ?? null,
    'amountPaid'           => $body['amountPaid'] ?? null,
    'raw'                  => $body, // helpful while testing; remove later if you prefer
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
