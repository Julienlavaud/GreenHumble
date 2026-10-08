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

$scans = load_all_scans();
$updatedScan = null;

foreach ($scans as &$s) {
    if (($s['id'] ?? '') === $scanId) {
        $s['group_id'] = $groupId;
        $updatedScan = $s;
        break;
    }
}

if (!$updatedScan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Scan non trouvé.'], JSON_UNESCAPED_UNICODE);
    exit;
}

save_all_scans($scans);

echo json_encode([
    'success' => true,
    'message' => 'Scanner assigné au groupe avec succès.',
    'scan' => $updatedScan
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
