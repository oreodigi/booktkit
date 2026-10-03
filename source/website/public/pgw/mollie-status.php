<?php
header('Content-Type: application/json');

// helpers
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

$config = load_config_or_fail();
load_autoload_or_fail();

$mollie = new \Mollie\Api\MollieApiClient();
$mollie->setApiKey($config['MOLLIE_API_KEY']);

$paymentId = $_GET['payment_id'] ?? null;
if (!$paymentId) {
  http_response_code(400);
  echo json_encode(['error' => 'payment_id required']);
  exit;
}

try {
  $payment = $mollie->payments->get($paymentId);

  // Return minimal status info; expand as needed
  echo json_encode([
    'id'         => $payment->id,
    'status'     => $payment->status, // paid, open, canceled, expired, failed, pending, authorized
    'amount'     => $payment->amount,
    'paidAt'     => $payment->paidAt,
    'description'=> $payment->description,
  ]);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
