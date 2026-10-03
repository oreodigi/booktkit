<?php
header('Content-Type: application/json');

// simple logger (optional)
function mollie_log(string $msg): void {
  $line = '[' . date('Y-m-d H:i:s') . "] $msg\n";
  @file_put_contents(sys_get_temp_dir() . '/mollie_webhook.log', $line, FILE_APPEND);
}

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

try {
  $config = load_config_or_fail();
  load_autoload_or_fail();

  $mollie = new \Mollie\Api\MollieApiClient();
  $mollie->setApiKey($config['MOLLIE_API_KEY']);

  // Mollie usually posts 'id' form field; be tolerant
  $paymentId = $_POST['id'] ?? $_GET['id'] ?? null;
  if (!$paymentId) {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw) {
      $json = json_decode($raw, true);
      if (is_array($json) && isset($json['id'])) $paymentId = $json['id'];
    }
  }
  if (!$paymentId) {
    mollie_log('webhook missing id; POST=' . json_encode($_POST) . ' GET=' . json_encode($_GET));
    http_response_code(400);
    echo json_encode(['error' => 'no id']);
    return;
  }

  $payment = $mollie->payments->get($paymentId);

  // TODO: update your DB here…
  // demo: cache to a temp file
  $cache = sys_get_temp_dir() . "/mollie_$paymentId.json";
  @file_put_contents($cache, json_encode([
    'status'     => $payment->status,
    'updated_at' => date('c'),
  ]));

  mollie_log("webhook ok id=$paymentId status={$payment->status}");
  echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
  mollie_log('webhook error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
