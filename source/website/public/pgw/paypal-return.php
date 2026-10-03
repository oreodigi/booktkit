<?php
// PayPal redirects here with ?token=<order_id>&PayerID=<id> (for CAPTURE flow).
$orderId  = $_GET['token'] ?? '';       // this is the PayPal order id in v2
$cancel   = isset($_GET['cancel']) ? '1' : '';
$scheme   = 'myapp://paypal-finish';
$q = [];
if ($orderId) $q['order_id'] = $orderId;
if ($cancel)  $q['cancel']   = '1';
if ($q) $scheme .= '?' . http_build_query($q);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Returning…</title></head>
<body>
<script>location.replace('<?= htmlspecialchars($scheme, ENT_QUOTES) ?>');</script>
<noscript><a href="<?= htmlspecialchars($scheme, ENT_QUOTES) ?>">Return to app</a></noscript>
</body></html>
