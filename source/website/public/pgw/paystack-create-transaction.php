<?php
header('Content-Type: application/json');

/**
 * Initializes a Paystack transaction and returns:
 *  {
 *    "reference": "ref_20250901_ab12cd",
 *    "authorization_url": "https://checkout.paystack.com/xxxxx"
 *  }
 *
 * Body JSON:
 *  {
 *    "amount_minor": 250000,           // NGN 2500.00 => 250000 kobo
 *    "currency": "NGN",
 *    "email": "buyer@example.com",
 *    "name": "Buyer Name",             // optional (metadata only)
 *    "description": "Order #123"       // optional (metadata only)
 *  }
 */

function ps_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'PAYSTACK_SECRET_KEY' => getenv('PAYSTACK_SECRET_KEY') ?: '',
    'PAYSTACK_BASE'       => getenv('PAYSTACK_BASE') ?: 'https://api.paystack.co',
    'PUBLIC_API_BASE'     => getenv('PUBLIC_API_BASE') ?: '',
  ];
}
function ps_log($m){ @file_put_contents(sys_get_temp_dir().'/paystack_create.log','['.date('Y-m-d H:i:s')."] $m\n", FILE_APPEND); }

try {
  $cfg  = ps_cfg();
  $base = rtrim((string)($cfg['PAYSTACK_BASE'] ?? 'https://api.paystack.co'), '/');
  $key  = trim((string)($cfg['PAYSTACK_SECRET_KEY'] ?? ''));
  if ($key === '') { http_response_code(500); echo json_encode(['error' => 'PAYSTACK_SECRET_KEY missing']); exit; }

  $in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
  $amountMinor = (int)($in['amount_minor'] ?? 0);      // kobo
  $currency    = strtoupper((string)($in['currency'] ?? 'NGN'));
  $email       = trim((string)($in['email'] ?? 'customer@example.com'));
  $name        = trim((string)($in['name']  ?? 'Customer'));
  $desc        = trim((string)($in['description'] ?? 'Checkout'));

  if ($amountMinor <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid amount_minor']); exit; }
  if ($currency !== 'NGN') {
    // Paystack supports more currencies if enabled; keep NGN by default
    // Not failing hard—send whatever was given:
  }

  // Build return url to bounce the WebView back (close)
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $local  = rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
  $retBase = rtrim((string)($cfg['PUBLIC_API_BASE'] ?: $local), '/');
  $callbackUrl = $retBase . '/paystack-return.php';

  // Our own reference (you can also let Paystack generate one)
  $reference = 'ref_'.date('YmdHis').'_'.bin2hex(random_bytes(4));

  $payload = [
    'amount'       => $amountMinor,     // kobo
    'email'        => $email,
    'currency'     => $currency,        // typically NGN
    'reference'    => $reference,
    'callback_url' => $callbackUrl,
    // Useful metadata
    'metadata'     => [
      'custom_fields' => [[
        'display_name'  => 'Customer Name',
        'variable_name' => 'customer_name',
        'value'         => $name,
      ]],
      'description' => $desc,
    ],
  ];

  $ch = curl_init($base.'/transaction/initialize');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer '.$key,
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
  if ($code >= 300 || !is_array($res)) {
    http_response_code($code ?: 422);
    echo json_encode(['error'=>'Non-JSON/HTTP '.$code, 'raw'=>$resBody]);
    exit;
  }
  if (($res['status'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(['error'=>$res['message'] ?? 'Paystack error', 'raw'=>$res]);
    exit;
  }

  $authUrl = $res['data']['authorization_url'] ?? null;
  if (!$authUrl) {
    http_response_code(500);
    echo json_encode(['error'=>'Missing authorization_url', 'raw'=>$res]);
    exit;
  }

  echo json_encode([
    'reference'        => $reference,
    'authorization_url'=> $authUrl,
  ]);
} catch (Throwable $e) {
  ps_log('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
