<?php
// PhonePe will redirect/POST here.
// Bounce back into the app to close the WebView.

$mtx = $_REQUEST['merchantTransactionId'] ?? $_REQUEST['transactionId'] ?? '';
$scheme = 'myapp://phonepe-return';
if (!empty($mtx)) {
  $scheme .= '?merchant_txn_id=' . urlencode($mtx);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Returning…</title>
  <!-- Prevent the browser from requesting /favicon.ico -->
  <link rel="icon" href="data:,">
</head>
<body>
  <script>
    // Redirect immediately to the app (custom scheme).
    location.replace('<?= htmlspecialchars($scheme, ENT_QUOTES, "UTF-8") ?>');
    // Tiny fallback in case replace is blocked by the environment.
    setTimeout(function () {
      window.location.href = '<?= htmlspecialchars($scheme, ENT_QUOTES, "UTF-8") ?>';
    }, 120);
  </script>
  <noscript>
    <a href="<?= htmlspecialchars($scheme, ENT_QUOTES, "UTF-8") ?>">Return to app</a>
  </noscript>
</body>
</html>
