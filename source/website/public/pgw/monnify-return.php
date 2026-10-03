<?php
// Monnify appends query params: ?paymentReference=&transactionReference=&paymentStatus=
$trxRef = $_GET['transactionReference'] ?? '';
$payRef = $_GET['paymentReference'] ?? '';
$status = $_GET['paymentStatus'] ?? '';
$scheme = 'myapp://monnify-finish';
$q = [];
if ($trxRef) $q[] = 'transactionReference='.rawurlencode($trxRef);
if ($payRef) $q[] = 'paymentReference='.rawurlencode($payRef);
if ($status) $q[] = 'status='.rawurlencode($status);
if ($q) $scheme .= '?'.implode('&', $q);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Returning…</title></head>
<body>
<script>location.replace('<?= htmlspecialchars($scheme, ENT_QUOTES) ?>');</script>
<noscript><a href="<?= htmlspecialchars($scheme, ENT_QUOTES) ?>">Return to app</a></noscript>
</body></html>
