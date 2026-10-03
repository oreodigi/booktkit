<?php
// Toyyib server-to-server callback. Log and/or store in DB as needed.
file_put_contents(sys_get_temp_dir().'/toyyib_callback.log',
  '['.date('Y-m-d H:i:s')."] ".json_encode($_REQUEST)."\n", FILE_APPEND);

// Always 200 OK
header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
