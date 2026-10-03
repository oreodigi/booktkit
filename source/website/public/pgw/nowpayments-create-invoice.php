<?php
/**
 * Create a NOWPayments invoice and return {invoice_id, redirect_url}.
 */
header('Content-Type: application/json');

function np_log($m){ @file_put_contents(sys_get_temp_dir().'/nowpayments_create_invoice.log','['.date('c')."] $m\n",FILE_APPEND); }

try {
  // Load config
  $candidates=[__DIR__.'/config.php',dirname(__DIR__).'/config.php',dirname(__DIR__,2).'/config.php'];
  $cfg=null; foreach($candidates as $p){ if(is_file($p)){ $cfg=require $p; break; } }
  if(!$cfg) throw new RuntimeException('config.php not found');

  $apiKey = trim((string)($cfg['NOWPAYMENTS_API_KEY'] ?? ''));
  $base   = rtrim((string)($cfg['NOWPAYMENTS_BASE'] ?? ''), '/');
  if($apiKey==='' || $base==='') throw new RuntimeException('NOWPayments config missing');

  // Parse JSON body
  $raw = file_get_contents('php://input') ?: '';
  $js  = json_decode($raw, true) ?: [];

  $amountMinor = (int)($js['amount_minor'] ?? 0);
  $currency    = strtoupper(trim((string)($js['currency'] ?? 'USD'))); // fiat currency
  $orderId     = 'ORDER-'.time();

  if ($amountMinor <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid amount_minor']); exit; }
  $amount = number_format($amountMinor/100, 2, '.', '');

  // Return / callback URLs
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https' : 'http';
  $localBase = rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
  $publicApiBase = rtrim((string)($cfg['PUBLIC_API_BASE'] ?? ''), '/');
  $baseUrl = $publicApiBase ?: $localBase;

  $successUrl = $baseUrl . '/nowpayments-return.php?success=1';
  $cancelUrl  = $baseUrl . '/nowpayments-return.php?cancel=1';
  $ipnUrl     = $baseUrl . '/nowpayments-return.php';

  // Create Invoice
  $payload = [
    'price_amount'      => $amount,
    'price_currency'    => $currency,
    'order_id'          => $orderId,
    'order_description' => 'NOWPayments invoice',
    'ipn_callback_url'  => $ipnUrl,
    'success_url'       => $successUrl,
    'cancel_url'        => $cancelUrl,
  ];

  $ch = curl_init($base . '/invoice');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'x-api-key: ' . $apiKey,
      'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) { np_log("curl error: $err"); http_response_code(500); echo json_encode(['error'=>$err]); exit; }

  $res = json_decode($resBody, true);
  if (!is_array($res)) { http_response_code($code ?: 500); echo json_encode(['error'=>'Non-JSON','raw'=>$resBody]); exit; }

  $invoiceId = $res['id'] ?? $res['invoice_id'] ?? null;
  $invoiceUrl = $res['invoice_url'] ?? $res['url'] ?? null;

  if ($code >= 300 || !$invoiceId || !$invoiceUrl) {
    np_log("create-invoice error ($code): ".json_encode($res));
    http_response_code($code ?: 422);
    echo json_encode(['error'=>'NOWPayments invoice create failed','raw'=>$res]);
    exit;
  }

  echo json_encode([
    'invoice_id'   => (string)$invoiceId,
    'redirect_url' => (string)$invoiceUrl,
  ]);
} catch (Throwable $e) {
  np_log('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
