<?php
// Legacy redirect - use /api/admin/index.php instead
http_response_code(301);
header('Location: /api/admin/index.php', true, 301);
exit;
