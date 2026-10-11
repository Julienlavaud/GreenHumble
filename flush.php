<?php
// Legacy redirect - use /api/flush.php instead
http_response_code(301);
header('Location: /api/flush.php', true, 301);
exit;
