<?php
header('Content-Type: application/json');

/**
 * Creates a PhonePe hosted Pay Page and returns:
 *  {"merchant_txn_id":"txn_...","redirect_url":"https://...phonepe..."}
 *
 * Body JSON from client:
 *  {
 *    "amount_minor": 2599,           // in paise for INR (₹25.99 = 2599)
 *    "merchant_user_id": "user_123", // any stable id for the user
 *    "mobile": "9999999999",         // optional
 *    "name": "Demo User",
 *    "email": "demo@example.com",
 *    "description": "Order #123"
 *  }
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
    'PUBLIC_API_BASE'     => getenv('PUBLIC_API_BASE') ?: '',
  ];
}
function log_pp($m){ @file_put_contents(sys_get_temp_dir().'/phonepe_create.log','['.date('Y-m-d H:i:s')."] $m\n",FILE_APPEND); }

try {
  $cfg = cfg();
  $base   = rtrim((string)$cfg['PHONEPE_BASE'], '/');
  $mid    = trim((string)$cfg['PHONEPE_MERCHANT_ID']);
  $salt   = (string)$cfg['PHONEPE_SALT_KEY'];
  $sIndex = (string)$cfg['PHONEPE_SALT_INDEX'];

  if (!$mid || !$salt) {
    http_response_code(500);
    echo json_encode(['error'=>'PHONEPE_MERCHANT_ID / PHONEPE_SALT_KEY missing in config']);
    exit;
  }

  $in  = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
  $amountMinor = (int)($in['amount_minor'] / 100 ?? 0);
  $muid        = (string)($in['merchant_user_id'] ?? 'user_'.substr(bin2hex(random_bytes(4)),0,6));
  $mobile      = preg_replace('~[^0-9]~','', (string)($in['mobile'] ?? ''));
  $name        = trim((string)($in['name'] ?? 'Customer'));
  $email       = trim((string)($in['email'] ?? 'customer@example.com'));
  $desc        = trim((string)($in['description'] ?? 'Order'));

  if ($amountMinor <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid amount_minor']);
    exit;
  }

  // build public return/callback base
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $local  = rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
  $retBase = rtrim((string)($cfg['PUBLIC_API_BASE'] ?: $local), '/');

  // merchant transaction id must be unique
  $mtx = 'txn_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));

  // Pay Page payload
  $payload = [
    'merchantId'            => $mid,
    'merchantTransactionId' => $mtx,
    'merchantUserId'        => $muid,
    'amount'                => $amountMinor,     // in paise
    'redirectUrl'           => $retBase . '/phonepe-return.php',
    'redirectMode'          => 'POST',           // or 'GET'
    'callbackUrl'           => $retBase . '/phonepe-return.php',
    'mobileNumber'          => $mobile ?: null,
    'paymentInstrument'     => ['type' => 'PAY_PAGE'],
    'message'               => $desc,
    // Optional customer info
    'deviceContext'         => ['deviceOS' => 'ANDROID'],
  ];

  // cleanup nulls
  $payload = array_filter($payload, fn($v) => !is_null($v));

  // PhonePe wants base64 of JSON in the "request" field
  $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
  $base64 = base64_encode($json);

  // Build X-VERIFY for pay call: sha256(base64 + path + saltKey) + '###' + saltIndex
  $path = '/pg/v1/pay';
  $xverify = hash('sha256', $base64 . $path . $salt) . '###' . $sIndex;

  $url = $base . $path;
  $post = json_encode(['request' => $base64]);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'Content-Type: application/json',
      'X-VERIFY: '.$xverify,
      'X-MERCHANT-ID: '.$mid,
    ],
    CURLOPT_POSTFIELDS     => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) {
    http_response_code(500);
    echo json_encode(['error'=>$err]);
    exit;
  }

  $res = json_decode($resBody, true);
  if ($code >= 300 || !is_array($res)) {
    http_response_code($code ?: 422);
    echo json_encode(['error'=>'Non-JSON / HTTP '.$code, 'raw'=>$resBody]);
    exit;
  }

  if (($res['success'] ?? false) !== true) {
    // Return useful error to app
    http_response_code(422);
    echo json_encode(['error'=>$res['code'] ?? 'PHONEPE_ERROR', 'message'=>$res['message'] ?? null, 'raw'=>$res]);
    exit;
  }

  // Redirect URL is nested under data.instrumentResponse.redirectInfo.url
  $redirect = $res['data']['instrumentResponse']['redirectInfo']['url'] ?? null;
  if (!$redirect) {
    http_response_code(500);
    echo json_encode(['error'=>'Missing redirect url from PhonePe', 'raw'=>$res]);
    exit;
  }

  echo json_encode([
    'merchant_txn_id' => $mtx,
    'redirect_url'    => $redirect,
  ]);
} catch (Throwable $e) {
  log_pp('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
