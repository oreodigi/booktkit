<?php
/**
 * Create a MyFatoorah invoice and return a hosted checkout URL.
 * Response (on success): {"invoice_id":"6076191","redirect_url":"https://..."}
 */
header('Content-Type: application/json');

// ---------- tiny logger (optional for debugging) ----------
if (!function_exists('mf_log')) {
  function mf_log(string $msg): void {
    @file_put_contents(sys_get_temp_dir().'/myfatoorah_create.log',
      '['.date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
  }
}

// ---------- load config (file or env fallback) ----------
function load_config_or_fail(): array {
  $candidates = [
    __DIR__ . '/config.php',             // /api/config.php
    dirname(__DIR__) . '/config.php',    // /public_html/config.php
    dirname(__DIR__, 2) . '/config.php', // repo root/config.php
  ];
  foreach ($candidates as $p) {
    if (is_file($p)) return require $p;
  }
  // Fallback to environment variables if no config.php
  return [
    'MYFATOORAH_API_KEY' => getenv('MYFATOORAH_API_KEY') ?: '',
    'MYFATOORAH_BASE'    => getenv('MYFATOORAH_BASE') ?: '',
    'PUBLIC_API_BASE'    => getenv('PUBLIC_API_BASE') ?: '',
  ];
}

try {
  $config = load_config_or_fail();

  $base   = rtrim((string)($config['MYFATOORAH_BASE'] ?? ''), '/');
  $apiKey = trim((string)($config['MYFATOORAH_API_KEY'] ?? ''));

  if (!$base || stripos($base, 'http') !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'MYFATOORAH_BASE missing/invalid. Use https://apitest.myfatoorah.com (test) or https://api.myfatoorah.com (live).']);
    exit;
  }
  if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'MYFATOORAH_API_KEY missing. Put it in config.php or env.']);
    exit;
  }

  // ---------- read JSON body from Flutter ----------
  $raw = file_get_contents('php://input') ?: '';
  $js  = json_decode($raw, true) ?: [];

  $amountMinor     = (int)($js['amount_minor'] ?? 0);       // e.g. 2599 => 25.99
  $currency        = strtoupper(trim((string)($js['currency'] ?? 'KWD'))); // match your account
  $customerName    = trim((string)($js['name'] ?? 'Customer'));
  $customerEmail   = trim((string)($js['email'] ?? 'customer@example.com'));
  $customerPhone   = preg_replace('~[^0-9]~', '', (string)($js['phone'] ?? '')); // digits only
  $countryDialCode = trim((string)($js['country_dial_code'] ?? '+965'));         // e.g. +965
  $description     = trim((string)($js['description'] ?? 'Order'));

  if ($amountMinor <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount_minor']);
    exit;
  }

  // amount as float with 2 decimals
  $amount = (float) number_format($amountMinor/100, 2, '.', '');

  // ---------- build callback/error URLs (public if provided) ----------
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $localBase = rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']), '/');
  $publicApiBase = rtrim((string)($config['PUBLIC_API_BASE'] ?? ''), '/');
  $cbBase = $publicApiBase ?: $localBase;

  $callbackUrl = $cbBase . '/myfatoorah-return.php';
  $errorUrl    = $cbBase . '/myfatoorah-return.php?error=1';

  // ---------- call MyFatoorah SendPayment ----------
  $url = $base . '/v2/SendPayment';
  $payload = [
    'CustomerName'       => $customerName,
    'NotificationOption' => 'ALL',             // SMS,EMAIL,BOTH,ALL,NONE
    'InvoiceValue'       => $amount,           // numeric
    'DisplayCurrencyIso' => $currency,         // must be supported by your account
    'CallBackUrl'        => $callbackUrl,
    'ErrorUrl'           => $errorUrl,
    'Language'           => 'EN',
    'CustomerEmail'      => $customerEmail,
    'MobileCountryCode'  => $countryDialCode,  // e.g. +965
    'CustomerMobile'     => $customerPhone,    // digits only
    'UserDefinedField'   => $description,
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . $apiKey,
      'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
    // If you hit SSL CA issues on local Windows, you can TEMPORARILY relax:
    // CURLOPT_SSL_VERIFYPEER => false,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) {
    mf_log("curl error: $err");
    http_response_code(500);
    echo json_encode(['error' => $err]);
    exit;
  }

  $res = json_decode($resBody, true);
  if (!is_array($res)) {
    mf_log("non-json ($code): $resBody");
    http_response_code($code ?: 500);
    echo json_encode(['error' => 'Non-JSON response from MyFatoorah', 'raw' => $resBody]);
    exit;
  }

  if ($code >= 300 || empty($res['IsSuccess'])) {
    $message    = $res['Message'] ?? 'MyFatoorah error';
    $validation = $res['ValidationErrors'] ?? ($res['FieldsErrors'] ?? null);
    mf_log("api error ($code): ".json_encode($res));
    http_response_code(422);
    echo json_encode([
      'error'   => $message,
      'details' => $validation,
      'raw'     => $res,
      // 'payload' => $payload, // uncomment if you need to debug what you sent
    ]);
    exit;
  }

  // ---------- handle both PaymentURL and InvoiceURL ----------
  $invoiceId  = $res['Data']['InvoiceId'] ?? null;
  $paymentUrl = $res['Data']['PaymentURL'] ?? ($res['Data']['InvoiceURL'] ?? null);

  if (!$invoiceId || !$paymentUrl) {
    mf_log("success but missing URLs: ".json_encode($res));
    http_response_code(500);
    echo json_encode(['error' => 'Missing InvoiceId/PaymentURL', 'raw' => $res]);
    exit;
  }

  echo json_encode([
    'invoice_id'   => (string)$invoiceId,
    'redirect_url' => (string)$paymentUrl,
  ]);
} catch (Throwable $e) {
  mf_log('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
