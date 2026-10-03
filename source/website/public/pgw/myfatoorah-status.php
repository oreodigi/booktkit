<?php
/**
 * Check MyFatoorah invoice status by invoice_id.
 * Usage: GET /api/myfatoorah-status.php?invoice_id=6076191
 * Response (success): {"status":"Paid"} | {"status":"Pending"} | {"status":"Failed"} | {"status":"Expired"} | ...
 */
header('Content-Type: application/json');

// ---- tiny logger (optional) ----
if (!function_exists('mf_status_log')) {
  function mf_status_log(string $msg): void {
    @file_put_contents(
      sys_get_temp_dir() . '/myfatoorah_status.log',
      '[' . date('Y-m-d H:i:s') . "] $msg\n",
      FILE_APPEND
    );
  }
}

try {
  // --- load config (same pattern as your other scripts) ---
  $candidates = [
    __DIR__ . '/config.php',             // /api/config.php
    dirname(__DIR__) . '/config.php',    // /public_html/config.php
    dirname(__DIR__, 2) . '/config.php', // repo root/config.php
  ];
  $config = null;
  foreach ($candidates as $p) {
    if (is_file($p)) { $config = require $p; break; }
  }
  if (!$config) $config = [
    'MYFATOORAH_API_KEY' => getenv('MYFATOORAH_API_KEY') ?: '',
    'MYFATOORAH_BASE'    => getenv('MYFATOORAH_BASE') ?: '',
  ];

  $base   = rtrim((string)($config['MYFATOORAH_BASE'] ?? ''), '/');
  $apiKey = trim((string)($config['MYFATOORAH_API_KEY'] ?? ''));

  if (!$base || stripos($base, 'http') !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'MYFATOORAH_BASE missing/invalid in config']);
    exit;
  }
  if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'MYFATOORAH_API_KEY missing in config']);
    exit;
  }

  // --- read invoice_id from query (as your Flutter code does) ---
  $invoiceId = $_GET['invoice_id'] ?? null;
  if (!$invoiceId) {
    http_response_code(400);
    echo json_encode(['error' => 'no invoice_id']);
    exit;
  }
  $invoiceId = (string)$invoiceId;

  // --- call MyFatoorah GetPaymentStatus ---
  $url = $base . '/v2/GetPaymentStatus';
  $payload = ['Key' => $invoiceId, 'KeyType' => 'InvoiceId'];

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

    // ⚠️ Dev-only: uncomment the next line if you hit curl SSL errors on Windows/local.
    // NEVER leave disabled in production.
    // CURLOPT_SSL_VERIFYPEER => false,
  ]);

  $resBody = curl_exec($ch);
  $curlErr = curl_error($ch);
  $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($curlErr) {
    mf_status_log("curl error: $curlErr");
    http_response_code(500);
    echo json_encode(['error' => $curlErr]);
    exit;
  }

  $js = json_decode($resBody, true);
  if (!is_array($js)) {
    mf_status_log("non-json ($code): $resBody");
    http_response_code($code ?: 500);
    echo json_encode(['error' => 'Non-JSON response from MyFatoorah', 'raw' => $resBody]);
    exit;
  }

  if ($code >= 300 || empty($js['IsSuccess'])) {
    // Surface MyFatoorah’s message & validation errors so you can see the exact reason.
    $message = $js['Message'] ?? 'MyFatoorah status error';
    $details = $js['ValidationErrors'] ?? ($js['FieldsErrors'] ?? null);
    mf_status_log("api error ($code): " . json_encode($js));
    http_response_code(422);
    echo json_encode(['error' => $message, 'details' => $details, 'raw' => $js]);
    exit;
  }

  // Normalize a friendly status
  $status = 'Unknown';
  if (!empty($js['Data']['InvoiceStatus'])) {
    $status = (string)$js['Data']['InvoiceStatus']; // e.g. Paid, Pending, Failed, Expired
  } elseif (!empty($js['Data']['InvoiceTransactions'][0]['TransactionStatus'])) {
    // fallback: sometimes appears here
    $status = (string)$js['Data']['InvoiceTransactions'][0]['TransactionStatus'];
  }

  echo json_encode(['status' => $status]);
} catch (Throwable $e) {
  mf_status_log('exception: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
