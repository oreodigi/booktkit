<?php
/**
 * Query NOWPayments invoice status by invoice_id.
 * Usage: /api/nowpayments-invoice-status.php?invoice_id=12345
 */
header('Content-Type: application/json');

function np_status_log($m){ @file_put_contents(sys_get_temp_dir().'/nowpayments_invoice_status.log','['.date('c')."] $m\n",FILE_APPEND); }

try {
  $candidates=[__DIR__.'/config.php',dirname(__DIR__).'/config.php',dirname(__DIR__,2).'/config.php'];
  $cfg=null; foreach($candidates as $p){ if(is_file($p)){ $cfg=require $p; break; } }
  if(!$cfg) throw new RuntimeException('config.php not found');

  $apiKey = trim((string)($cfg['NOWPAYMENTS_API_KEY'] ?? ''));
  $base   = rtrim((string)($cfg['NOWPAYMENTS_BASE'] ?? ''), '/');
  if($apiKey==='' || $base==='') throw new RuntimeException('NOWPayments config missing');

  $invoiceId = $_GET['invoice_id'] ?? '';
  if(!$invoiceId){ http_response_code(400); echo json_encode(['error'=>'no invoice_id']); exit; }

  $ch = curl_init($base . '/invoice/' . rawurlencode((string)$invoiceId));
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['x-api-key: ' . $apiKey],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) { np_status_log("curl error: $err"); http_response_code(500); echo json_encode(['error'=>$err]); exit; }

  $js = json_decode($resBody, true);
  if (!is_array($js)) { http_response_code($code ?: 500); echo json_encode(['error'=>'Non-JSON','raw'=>$resBody]); exit; }
  if ($code >= 300)   { np_status_log("api error ($code): ".json_encode($js)); http_response_code($code); echo json_encode(['error'=>'NOWPayments query failed','raw'=>$js]); exit; }

  $status = $js['status'] ?? $js['payment_status'] ?? 'Unknown';
  echo json_encode(['status'=>$status,'raw'=>$js]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
