<?php
header('Content-Type: application/json');

function xdt_status_log($msg) {
  @file_put_contents(sys_get_temp_dir() . '/xendit_status.log',
    '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

try {
  // load config
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) { $config = require $p; break; }
  }
  if (empty($config)) {
    $config = [
      'XENDIT_SECRET_KEY' => getenv('XENDIT_SECRET_KEY') ?: '',
      'XENDIT_BASE'       => getenv('XENDIT_BASE') ?: 'https://api.xendit.co',
    ];
  }

  $base = rtrim($config['XENDIT_BASE'] ?? 'https://api.xendit.co', '/');
  $key  = trim($config['XENDIT_SECRET_KEY'] ?? '');
  if ($key === '') {
    http_response_code(500);
    echo json_encode(['error' => 'XENDIT_SECRET_KEY missing']);
    exit;
  }

  $invoiceId = $_GET['invoice_id'] ?? null;
  if (!$invoiceId) {
    http_response_code(400);
    echo json_encode(['error' => 'no invoice_id']);
    exit;
  }

  $url = $base . '/v2/invoices/' . urlencode($invoiceId);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_USERPWD        => $key . ':',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
  ]);
  $resBody = curl_exec($ch);
  $err     = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($err) {
    http_response_code(500);
    echo json_encode(['error' => $err]);
    exit;
  }

  $res = json_decode($resBody, true);
  if (!is_array($res)) {
    http_response_code($code ?: 500);
    echo json_encode(['error' => 'Non-JSON response from Xendit', 'raw' => $resBody]);
    exit;
  }

  if ($code >= 300 || !isset($res['status'])) {
    http_response_code($code ?: 422);
    echo json_encode([
      'error' => $res['message'] ?? 'Xendit status error',
      'raw'   => $res,
    ]);
    exit;
  }

  // Normalize status
  $status = $res['status']; // "PENDING","PAID","EXPIRED","FAILED"
  echo json_encode(['status' => $status]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
