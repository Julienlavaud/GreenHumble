<?php
// Legacy redirect - use /api/index.php instead
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Location: /api/index.php', true, 302);
exit;
