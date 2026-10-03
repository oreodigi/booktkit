<?php
header('Content-Type: application/json');

/**
 * Creates a Xendit invoice and returns:
 *   {"invoice_id":"inv_...","redirect_url":"https://checkout.xendit.co/web/..."}
 *
 * Reads JSON body:
 *   { amount_minor, currency, name, email, description }
 */

function xdt_log($msg) {
  @file_put_contents(sys_get_temp_dir() . '/xendit_create.log',
    '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

// ---- load config (file or env) ----
function load_config(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'XENDIT_SECRET_KEY' => getenv('XENDIT_SECRET_KEY') ?: '',
    'XENDIT_BASE'       => getenv('XENDIT_BASE') ?: 'https://api.xendit.co',
    'PUBLIC_API_BASE'   => getenv('PUBLIC_API_BASE') ?: '',
  ];
}

try {
  $cfg   = load_config();
  $base  = rtrim((string)($cfg['XENDIT_BASE'] ?? 'https://api.xendit.co'), '/');
  $key   = trim((string)($cfg['XENDIT_SECRET_KEY'] ?? ''));

  if ($key === '') {
    http_response_code(500);
    echo json_encode(['error' => 'XENDIT_SECRET_KEY missing (config.php or env)']);
    exit;
  }

  // ---- read JSON from Flutter ----
  $raw = file_get_contents('php://input') ?: '';
  $in  = json_decode($raw, true) ?: [];

  $amountMinor = (int)($in['amount_minor'] ?? 0);     // e.g. 2500 => 25.00
  $currency    = strtoupper((string)($in['currency'] ?? 'IDR')); // Xendit supports IDR, PHP, USD
  $name        = (string)($in['name'] ?? 'Customer');
  $email       = (string)($in['email'] ?? 'customer@example.com');
  $desc        = (string)($in['description'] ?? 'Order');

  if ($amountMinor <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount_minor']);
    exit;
  }

  // Xendit Invoice expects numeric amount (not minor units)
  $amount = (float) number_format($amountMinor, 2, '.', '');

  // ---- build return URLs (use HTTPS if possible) ----
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $localBase = rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']), '/');
  $publicApiBase = rtrim((string)($cfg['PUBLIC_API_BASE'] ?? ''), '/');
  $retBase = $publicApiBase ?: $localBase;

  $successUrl = $retBase . '/xendit-return.php';
  $failureUrl = $retBase . '/xendit-return.php?error=1';

  // ---- create invoice ----
  $url = $base . '/v2/invoices';
  $payload = [
    'external_id'          => 'order-' . time(),
    'amount'               => $amount,       // number, not string
    'currency'             => $currency,     // IDR|PHP|USD
    'payer_email'          => $email,
    'description'          => $desc,
    'customer'             => ['given_names' => $name, 'email' => $email],
    // NOTE: if your success/failure URL is not HTTPS/public, temporarily omit these lines.
    'success_redirect_url' => $successUrl,
    'failure_redirect_url' => $failureUrl,
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_USERPWD        => $key . ':', // Basic auth
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
    // Dev-only if you ever hit CA issues:
    // CURLOPT_SSL_VERIFYPEER => false,
  ]);
  $resBody = curl_exec($ch);
  $curlErr = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($curlErr) {
    xdt_log("curl error: $curlErr");
    http_response_code(500);
    echo json_encode(['error' => $curlErr]);
    exit;
  }

  $res = json_decode($resBody, true);
  if (!is_array($res)) {
    xdt_log("non-json ($code): $resBody");
    http_response_code($code ?: 500);
    echo json_encode(['error' => 'Non-JSON response from Xendit', 'raw' => $resBody]);
    exit;
  }

  if ($code >= 300 || empty($res['id'])) {
    // surface Xendit’s message / errors
    $msg = $res['message'] ?? ($res['error_code'] ?? 'Xendit error');
    xdt_log("api error ($code): ".json_encode($res));
    http_response_code($code ?: 422);
    echo json_encode(['error' => $msg, 'raw' => $res, 'sent' => $payload]);
    exit;
  }

  echo json_encode([
    'invoice_id'   => $res['id'],
    'redirect_url' => $res['invoice_url'] ?? $res['checkout_url'] ?? null,
  ]);
} catch (Throwable $e) {
  xdt_log('exception: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
