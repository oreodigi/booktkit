<?php
// Toyyib may redirect back with ?status_id=1&billcode=...
$bill = $_GET['billcode'] ?? $_GET['billCode'] ?? '';
$scheme = 'myapp://toyyibpay-finish';
if ($bill) $scheme .= '?billCode='.urlencode($bill);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Returning…</title></head>
<body>
<script>location.replace('<?= htmlspecialchars($scheme, ENT_QUOTES) ?>');</script>
<noscript><a href="<?= htmlspecialchars($scheme, ENT_QUOTES) ?>">Return to app</a></noscript>
</body>
</html>
