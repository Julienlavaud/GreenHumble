<?php
// Route les anciennes demandes de prospection vers le bon dossier.
$id = trim((string)($_GET['id'] ?? ''));
if (preg_match('/^aud_[A-Za-z0-9_-]{8,80}$/', $id)) {
    header('Location: /api/audit-report.php?id=' . rawurlencode($id), true, 303);
    exit;
}
if (ctype_digit($id) && (int)$id > 0) {
    header('Location: /api/analyse.php?id=' . rawurlencode((string)(int)$id) . '&tab=kit', true, 303);
    exit;
}
header('Location: /api/pipeline.php', true, 303);
exit;
