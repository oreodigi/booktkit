<?php
// MP appends various params, e.g. collection_status/ payment_id / external_reference / preference_id
$extRef = $_GET['external_reference'] ?? '';
$scheme = 'myapp://mp-finish';
if ($extRef) $scheme .= '?external_reference='.urlencode($extRef);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Returning…</title></head>
<body>
<script>location.replace('<?= htmlspecialchars($scheme, ENT_QUOTES) ?>');</script>
<noscript><a href="<?= htmlspecialchars($scheme, ENT_QUOTES) ?>">Return to app</a></noscript>
</body>
</html>
