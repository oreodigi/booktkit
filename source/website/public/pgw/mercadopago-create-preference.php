<?php
header('Content-Type: application/json');

/**
 * Creates a Mercado Pago Checkout Pro preference and returns:
 *  {
 *    "preference_id":"12345-abc",
 *    "external_reference":"order-20250901-xxxxxx",
 *    "redirect_url":"https://sandbox.mercadopago.com/checkout/v1/redirect?pref_id=12345-abc"
 *  }
 *
 * Body JSON:
 *  {
 *    "amount_minor": 2599,
 *    "currency": "MXN",
 *    "name": "Demo User",
 *    "email": "demo@example.com",
 *    "description": "Order #123"
 *  }
 */

function mp_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'MP_ACCESS_TOKEN' => getenv('MP_ACCESS_TOKEN') ?: '',
    'MP_BASE'         => getenv('MP_BASE') ?: 'https://api.mercadopago.com',
    'PUBLIC_API_BASE' => getenv('PUBLIC_API_BASE') ?: '',
  ];
}
function mp_log($m){ @file_put_contents(sys_get_temp_dir().'/mp_create.log','['.date('Y-m-d H:i:s')."] $m\n",FILE_APPEND); }

try {
  $cfg  = mp_cfg();
  $base = rtrim((string)$cfg['MP_BASE'], '/');
  $tok  = trim((string)$cfg['MP_ACCESS_TOKEN']);
  if ($tok === '') { http_response_code(500); echo json_encode(['error'=>'MP_ACCESS_TOKEN missing']); exit; }

  $in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
  $amountMinor = (int)($in['amount_minor'] ?? 0);
  $currency    = strtoupper((string)($in['currency'] ?? 'MXN'));
  $name        = trim((string)($in['name'] ?? 'Customer'));
  $email       = trim((string)($in['email'] ?? 'customer@example.com'));
  $desc        = trim((string)($in['description'] ?? 'Order'));

  if ($amountMinor <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid amount_minor']); exit; }

  $amount = (float) number_format($amountMinor / 100, 2, '.', '');

  // Build back URLs (must be public https in production)
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $local  = rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
  $retBase = rtrim((string)($cfg['PUBLIC_API_BASE'] ?: $local), '/');

  // Our own reference for later status lookups
  $extRef = 'order-'.date('YmdHis').'-'.bin2hex(random_bytes(4));

  $payload = [
    'items' => [[
      'title'       => $desc ?: 'Checkout',
      'quantity'    => 1,
      'currency_id' => $currency,   // e.g. MXN, BRL, ARS...
      'unit_price'  => $amount,     // decimal
    ]],
    'payer' => [
      'name'  => $name,
      'email' => $email,
    ],
    'external_reference' => $extRef,
    'back_urls' => [
      'success' => $retBase.'/mercadopago-return.php',
      'pending' => $retBase.'/mercadopago-return.php',
      'failure' => $retBase.'/mercadopago-return.php',
    ],
    'auto_return' => 'approved',
    // Optional: notification_url => $retBase.'/mercadopago-webhook.php',
  ];

  $ch = curl_init($base.'/checkout/preferences');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer '.$tok,
      'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) { http_response_code(500); echo json_encode(['error'=>$err]); exit; }

  $res = json_decode($resBody, true);
  if ($code >= 300 || !is_array($res) || empty($res['id'])) {
    mp_log("create error ($code): ".$resBody);
    http_response_code($code ?: 422);
    echo json_encode(['error'=>$res['message'] ?? 'Mercado Pago error', 'raw'=>$res]);
    exit;
  }

  $prefId = $res['id'];
  // In test, prefer sandbox_init_point; in live, init_point
  $redirect = $res['sandbox_init_point'] ?? $res['init_point'] ?? null;
  if (!$redirect) { http_response_code(500); echo json_encode(['error'=>'Missing init_point']); exit; }

  echo json_encode([
    'preference_id'      => $prefId,
    'external_reference' => $extRef,
    'redirect_url'       => $redirect,
  ]);
} catch (Throwable $e) {
  mp_log('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
