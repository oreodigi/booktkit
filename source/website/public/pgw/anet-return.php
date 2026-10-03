<?php
// Shown after successful payment (Authorize.Net hosted receipt "Continue")
?>
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>Payment Complete</title></head>
  <body>
    <h3>Payment complete</h3>
    <p>You can close this window.</p>
    <script>
      // If opened in a Flutter WebView, try to signal completion
      try { window.location = 'myapp://anet-finish?status=success'; } catch(e){}
    </script>
  </body>
</html>
