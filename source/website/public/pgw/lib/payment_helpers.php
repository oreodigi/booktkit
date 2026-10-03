<?php
// Shared helpers (paths + safe JSON output)

function pay_load_config(): array {
  $candidates = [
    __DIR__ . '/../config.php',
    dirname(__DIR__) . '/config.php',
  ];
  foreach ($candidates as $p) {
    if (is_file($p)) return require $p;
  }
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'config.php not found']);
  exit;
}

function pay_url_for(string $file): string {
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // usually /api
  return $scheme . '://' . $host . $base . '/' . ltrim($file, '/');
}

function pay_json(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function pay_http_post_json(string $url, array $payload, array $headers = [], int $timeout = 30): array {
  $ch = curl_init($url);
  $json = json_encode($payload);
  $baseHeaders = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Content-Length: ' . strlen($json),
  ];
  $allHeaders = array_merge($baseHeaders, $headers);

  curl_setopt_array($ch, [
    CURLOPT_POST            => true,
    CURLOPT_POSTFIELDS      => $json,
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_CONNECTTIMEOUT  => 10,
    CURLOPT_TIMEOUT         => $timeout,
    CURLOPT_HTTPHEADER      => $allHeaders,
  ]);

  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($body === false) {
    throw new RuntimeException("HTTP error: $err");
  }
  $decoded = json_decode($body, true);
  if (!is_array($decoded)) {
    throw new RuntimeException("Bad JSON (HTTP $code): $body");
  }
  return ['code' => $code, 'json' => $decoded, 'raw' => $body];
}
