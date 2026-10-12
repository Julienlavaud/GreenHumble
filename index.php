<?php
// Legacy redirect - use /api/index.php instead
http_response_code(301);
header('Location: /api/index.php', true, 301);
exit;
