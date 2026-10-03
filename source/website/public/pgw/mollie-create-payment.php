<?php
header('Content-Type: application/json');

// ===== helpers =====
function load_config_or_fail(): array {
  $candidates = [__DIR__ . '/config.php', dirname(__DIR__) . '/config.php'];
  foreach ($candidates as $p) if (is_file($p)) return require $p;
  $cfg = ['MOLLIE_API_KEY' => getenv('MOLLIE_API_KEY') ?: null];
  if (!$cfg['MOLLIE_API_KEY']) { http_response_code(500); echo json_encode(['error'=>'config missing']); exit; }
  return $cfg;
}
function load_autoload_or_fail(): void {
  $candidates = [__DIR__ . '/vendor/autoload.php', dirname(__DIR__) . '/vendor/autoload.php'];
  foreach ($candidates as $p) if (is_file($p)) { require $p; return; }
  http_response_code(500); echo json_encode(['error'=>'composer autoload missing']); exit;
}
function url_for(string $file): string {
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
  return $scheme . '://' . $host . $base . '/' . ltrim($file, '/');
}
// ===================

$config = load_config_or_fail();
load_autoload_or_fail();

$mollie = new \Mollie\Api\MollieApiClient();
$mollie->setApiKey($config['MOLLIE_API_KEY']);

// read input
$raw = file_get_contents('php://input') ?: '{}';
$js  = json_decode($raw, true) ?: [];

$amountMinor = (int)($js['amount_minor'] ?? 0); // cents
$currency    = strtoupper((string)($js['currency'] ?? 'EUR'));
$name        = (string)($js['name'] ?? 'Customer');
$email       = (string)($js['email'] ?? 'customer@example.com');
$description = (string)($js['description'] ?? 'Order');

if ($amountMinor <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid amount_minor']);
  exit;
}

// Mollie expects a string with 2 decimals (major units)
$amountValue = number_format($amountMinor / 100, 2, '.', '');

$redirectUrl = url_for('mollie-return.php');
$webhookUrl  = url_for('mollie-webhook.php');

try {
  $payment = $mollie->payments->create([
    'amount'      => ['currency' => $currency, 'value' => $amountValue],
    'description' => $description,
    'redirectUrl' => $redirectUrl,
    'webhookUrl'  => $webhookUrl,
    'metadata'    => ['email' => $email, 'name' => $name],
  ]);

  echo json_encode([
    'payment_id'   => $payment->id,
    'checkout_url' => $payment->getCheckoutUrl(),
  ]);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
