<?php
header('Content-Type: application/json');

/**
 * Input JSON:
 *  { "amount_minor": 1000, "name":"Demo", "email":"demo@example.com", "phone":"0100000000", "description":"Order #1" }
 *
 * Output JSON on success:
 *  { "billCode":"abcd1234", "redirect_url":"https://dev.toyyibpay.com/abcd1234" }
 */

function tp_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'TOYYIBPAY_BASE'          => getenv('TOYYIBPAY_BASE') ?: 'https://dev.toyyibpay.com',
    'TOYYIBPAY_SECRET_KEY'    => getenv('TOYYIBPAY_SECRET_KEY') ?: '',
    'TOYYIBPAY_CATEGORY_CODE' => getenv('TOYYIBPAY_CATEGORY_CODE') ?: '',
    'PUBLIC_API_BASE'         => getenv('PUBLIC_API_BASE') ?: '',
  ];
}
function tp_log($m){ @file_put_contents(sys_get_temp_dir().'/toyyib_create.log','['.date('Y-m-d H:i:s')."] $m\n", FILE_APPEND); }

try {
  $cfg  = tp_cfg();
  $base = rtrim((string)$cfg['TOYYIBPAY_BASE'], '/');
  $key  = trim((string)$cfg['TOYYIBPAY_SECRET_KEY']);
  $cat  = trim((string)$cfg['TOYYIBPAY_CATEGORY_CODE']);

  if ($key === '' || $cat === '') {
    http_response_code(500);
    echo json_encode(['error' => 'TOYYIBPAY_SECRET_KEY / TOYYIBPAY_CATEGORY_CODE missing in config']);
    exit;
  }

  $in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
  $amountMinor = (int)($in['amount_minor'] ?? 0);  // sen
  $name  = trim((string)($in['name']  ?? 'Customer'));
  $email = trim((string)($in['email'] ?? 'customer@example.com'));
  $phone = trim((string)($in['phone'] ?? ''));
  $desc  = trim((string)($in['description'] ?? 'Checkout'));

  if ($amountMinor <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid amount_minor']); exit; }

  // Compute URLs (use PUBLIC_API_BASE if you have one)
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $local  = rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
  $retBase = rtrim((string)($cfg['PUBLIC_API_BASE'] ?: $local), '/');

  // Toyyib expects string with 2 decimals for amount
  $amountStr = number_format($amountMinor, 2, '.', '');

  $payload = [
    'userSecretKey'            => $key,
    'categoryCode'             => $cat,
    'billName'                 => 'Order',
    'billDescription'          => $desc,
    'billPriceSetting'         => 1,
    'billPayorInfo'            => 1,
    'billAmount'               => $amountStr,
    'billReturnUrl'            => $retBase . '/toyyibpay-return.php',
    'billCallbackUrl'          => $retBase . '/toyyibpay-callback.php',
    'billExternalReferenceNo'  => 'ref_'.date('YmdHis').'_'.bin2hex(random_bytes(3)),
    'billTo'                   => $name,
    'billEmail'                => $email,
    'billPhone'                => $phone,
  ];

  $ch = curl_init($base.'/index.php/api/createBill');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query($payload),
    CURLOPT_TIMEOUT        => 45,
    CURLOPT_SSL_VERIFYPEER => true,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) { http_response_code(500); echo json_encode(['error'=>$err]); exit; }
  if ($code >= 300) { http_response_code($code); echo json_encode(['error'=>'HTTP '.$code, 'raw'=>$resBody]); exit; }

  // Toyyib usually returns a JSON array: [{"BillCode":"xxxx","Billcode":"xxxx"}] or [{"msg":"error message"}]
  $js = json_decode($resBody, true);

  $billCode = null;
  if (is_array($js)) {
    // Array with first object
    $first = $js[0] ?? null;
    if (is_array($first)) {
      $billCode = $first['BillCode'] ?? $first['Billcode'] ?? $first['billcode'] ?? null;
      if (!$billCode && isset($first['msg'])) {
        http_response_code(422);
        echo json_encode(['error' => $first['msg'], 'raw' => $js]);
        exit;
      }
    }
  }

  // Some hosts return plain BillCode string; try regex fallback
  if (!$billCode && is_string($resBody)) {
    if (preg_match('~"BillCode"\s*:\s*"([^"]+)"~i', $resBody, $m)) $billCode = $m[1];
  }

  if (!$billCode) {
    tp_log('Toyyib unknown response: '.$resBody);
    http_response_code(422);
    echo json_encode(['error'=>'Toyyib createBill returned no BillCode', 'raw'=>$resBody]);
    exit;
  }

  $redirect = $base . '/' . $billCode; // dev.toyyibpay.com/<BillCode> or toyyibpay.com/<BillCode>

  echo json_encode([
    'billCode'     => $billCode,
    'redirect_url' => $redirect,
  ]);
} catch (Throwable $e) {
  tp_log('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
