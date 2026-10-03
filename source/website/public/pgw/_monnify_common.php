<?php
// Shared helpers for Monnify endpoints

function mf_cfg(): array {
  foreach ([__DIR__.'/config.php', dirname(__DIR__).'/config.php', dirname(__DIR__,2).'/config.php'] as $p) {
    if (is_file($p)) return require $p;
  }
  return [
    'MONNIFY_BASE'          => getenv('MONNIFY_BASE') ?: 'https://sandbox.monnify.com',
    'MONNIFY_API_KEY'       => getenv('MONNIFY_API_KEY') ?: '',
    'MONNIFY_SECRET_KEY'    => getenv('MONNIFY_SECRET_KEY') ?: '',
    'MONNIFY_CONTRACT_CODE' => getenv('MONNIFY_CONTRACT_CODE') ?: '',
    'PUBLIC_API_BASE'       => getenv('PUBLIC_API_BASE') ?: '',
  ];
}

// Simple file cache for access token
function mf_get_token(array $cfg): string {
  $base = rtrim((string)$cfg['MONNIFY_BASE'], '/');
  $api  = trim((string)$cfg['MONNIFY_API_KEY']);
  $sec  = trim((string)$cfg['MONNIFY_SECRET_KEY']);
  if ($api === '' || $sec === '') {
    throw new Exception('MONNIFY_API_KEY / MONNIFY_SECRET_KEY missing');
  }

  $cache = sys_get_temp_dir().'/monnify_token.json';
  if (is_file($cache)) {
    $js = json_decode(@file_get_contents($cache), true);
    if (is_array($js) && !empty($js['token']) && !empty($js['exp']) && $js['exp'] > time()+10) {
      return $js['token'];
    }
  }

  $ch = curl_init($base.'/api/v1/auth/login');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Basic '.base64_encode($api.':'.$sec),
      'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
  ]);
  $res = curl_exec($ch);
  if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new Exception('Auth cURL error: '.$err); }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $js = json_decode($res, true);
  if ($code >= 300 || !is_array($js) || ($js['requestSuccessful'] ?? false) !== true) {
    throw new Exception('Auth failed (HTTP '.$code.'): '.substr($res, 0, 500));
  }

  $token = $js['responseBody']['accessToken'] ?? '';
  $expiresIn = (int)($js['responseBody']['expiresIn'] ?? 0);
  if ($token === '') throw new Exception('Auth OK, but no accessToken in response');

  // cache with small buffer
  @file_put_contents($cache, json_encode(['token'=>$token, 'exp'=>time()+max(60, $expiresIn-30)]));
  return $token;
}

// Build a public base URL for return links
function mf_public_base(array $cfg): string {
  if (!empty($cfg['PUBLIC_API_BASE'])) return rtrim((string)$cfg['PUBLIC_API_BASE'], '/');
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https' : 'http';
  return rtrim($scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
}
