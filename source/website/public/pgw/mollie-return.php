<?php
// Redirect target after checkout; returns user to your app
$paymentId = $_GET['id'] ?? '';
$appUrl    = 'myapp://mollie-finish' . ($paymentId ? ('?payment_id=' . urlencode($paymentId)) : '');
?><!doctype html>
<html>
  <head><meta charset="utf-8"><title>Returning…</title></head>
  <body>
    <script>location.replace('<?= htmlspecialchars($appUrl, ENT_QUOTES) ?>');</script>
    <noscript>
      <a href="<?= htmlspecialchars($appUrl, ENT_QUOTES) ?>">Tap to return to the app</a>
    </noscript>
  </body>
</html>
