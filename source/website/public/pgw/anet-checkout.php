<?php
// Auto POST the token to Accept Hosted page
$token = $_GET['token'] ?? '';
if ($token === '') { http_response_code(400); echo "Missing token"; exit; }
?>
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>Authorize.Net</title></head>
  <body onload="document.forms[0].submit()">
    <form method="post" action="https://accept.authorize.net/payment/payment">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
      <noscript><button type="submit">Continue</button></noscript>
    </form>
  </body>
</html>
