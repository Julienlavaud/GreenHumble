<?php
require_once __DIR__ . '/includes/auth.php';

$role = get_current_role();
$auditId = trim((string)($_GET['id'] ?? ''));
if (!in_array($role, ['admin', 'pipeline', 'collaborator', 'consultant'], true)) {
    $next = '/api/analyse.php';
    if ($auditId !== '') $next .= '?id=' . rawurlencode($auditId) . '&tab=diag';
    header('Location: /api/login.php?next=' . rawurlencode($next), true, 303);
    exit;
}

$target = '/api/analyse.php';
if ($auditId !== '') $target .= '?id=' . rawurlencode($auditId) . '&tab=diag';
header('Location: ' . $target, true, 301);
exit;
