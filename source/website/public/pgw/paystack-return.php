<?php
// Paystack redirects to ?reference=ref_... (or to callback_url you set)
// We deep-link back to the app to close WebView.
$ref    = $_GET['reference'] ?? '';
$scheme = 'myapp://paystack-finish';
if ($ref) $scheme .= '?reference='.urlencode($ref);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Returning…</title></head>
<body>
<script>location.replace('<?= htmlspecialchars($scheme, ENT_QUOTES) ?>');</script>
<noscript><a href="<?= htmlspecialchars($scheme, ENT_QUOTES) ?>">Return to app</a></noscript>
</body>
</html>
