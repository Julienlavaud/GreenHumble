<?php
/**
 * POST /api/scans/assign_group
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/api_guard.php';
gh_api_guard(true);

require_once __DIR__ . '/../includes/scans_manager.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

$scanId = trim($input['scan_id'] ?? $input['id'] ?? '');
$groupId = trim($input['group_id'] ?? '');

if (empty($scanId) || empty($groupId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'scan_id et group_id sont requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$updatedScan = null;
$saved = mutate_all_scans(static function (array $scans) use ($scanId, $groupId, &$updatedScan): array {
    foreach ($scans as &$scan) {
        if ((string)($scan['id'] ?? '') !== $scanId) continue;
        $scan['group_id'] = $groupId;
        $updatedScan = $scan;
        break;
    }
    unset($scan);
    return $scans;
});
if (!$saved) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Enregistrement impossible (droits d’écriture du dossier data).'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$updatedScan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Scan non trouvé.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Scanner assigné au groupe avec succès.',
    'scan' => $updatedScan
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
