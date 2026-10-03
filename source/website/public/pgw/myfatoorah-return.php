<?php
// This is the success/error callback page.
// Bounce back to the app (WebView close) using your custom scheme.
$invoiceId = $_GET['invoiceId'] ?? $_GET['invoice_id'] ?? '';
$error     = isset($_GET['error']) ? '1' : '';
$schemeUrl = 'myapp://myfatoorah-finish';
$qs = [];
if ($invoiceId) $qs['invoice_id'] = $invoiceId;
if ($error)     $qs['error'] = $error;
if (!empty($qs)) $schemeUrl .= '?' . http_build_query($qs);
?>
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>Returning…</title></head>
  <body>
    <script>location.replace('<?= htmlspecialchars($schemeUrl, ENT_QUOTES) ?>');</script>
    <noscript><a href="<?= htmlspecialchars($schemeUrl, ENT_QUOTES) ?>">Return to app</a></noscript>
  </body>
</html>
