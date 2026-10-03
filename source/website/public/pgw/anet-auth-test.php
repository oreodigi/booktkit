<?php
header('Content-Type: application/json');
$config = require dirname(__DIR__, 2).'/config.php';

$login = trim($config['AUTHORIZE_LOGIN_ID'] ?? '');
$key   = trim($config['AUTHORIZE_TRANSACTION_KEY'] ?? '');
$env   = strtolower($config['AUTHORIZE_ENV'] ?? 'sandbox');
$api   = ($env === 'production')
  ? 'https://api2.authorize.net/xml/v1/request.api'
  : 'https://apitest.authorize.net/xml/v1/request.api';

$payload = [
  'authenticateTestRequest' => [
    'merchantAuthentication' => ['name' => $login, 'transactionKey' => $key]
  ]
];

$ch = curl_init($api);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($resp, true);
$result = $data['messages']['resultCode'] ?? 'Error';
$msg    = $data['messages']['message'][0]['text'] ?? 'Unknown';

echo json_encode(['http'=>$code, 'result'=>$result, 'message'=>$msg, 'env'=>$env]);
