<?php
// Bounce back into the app to close the WebView.
// Flutterwave appends ?status=successful&tx_ref=...&transaction_id=...
$txRef = $_GET['tx_ref'] ?? '';
$schemeUrl = 'myapp://flutterwave-finish' . ($txRef ? ('?tx_ref=' . urlencode($txRef)) : '');
?>
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>Returning…</title></head>
  <body>
    <script>location.replace('<?= htmlspecialchars($schemeUrl, ENT_QUOTES) ?>');</script>
    <noscript><a href="<?= htmlspecialchars($schemeUrl, ENT_QUOTES) ?>">Return to app</a></noscript>
  </body>
</html>
