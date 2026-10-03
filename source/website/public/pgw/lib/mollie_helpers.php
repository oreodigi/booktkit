<?php
// Shared helper for all Mollie endpoints

function load_config_or_fail(): array {
  foreach ([__DIR__.'/../config.php', dirname(__DIR__).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  $key = getenv('MOLLIE_API_KEY');
  if (!$key) {
    http_response_code(500);
    echo json_encode(['error'=>'config missing']);
    exit;
  }
  return ['MOLLIE_API_KEY'=>$key];
}

function load_autoload_or_fail(): void {
  foreach ([__DIR__.'/../vendor/autoload.php', dirname(__DIR__).'/vendor/autoload.php'] as $p) {
    if (is_file($p)) { require $p; return; }
  }
  http_response_code(500);
  echo json_encode(['error'=>'composer autoload missing']);
  exit;
}

function url_for(string $file): string {
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
  return $scheme . '://' . $host . $base . '/' . ltrim($file, '/');
}
