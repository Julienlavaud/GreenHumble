<?php
/**
 * POST /api/groups/assign_scanner
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

require_once __DIR__ . '/../includes/scanner_groups.php';

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$scannerId = trim($input['scanner_id'] ?? '');
$groupId = trim($input['group_id'] ?? '');

if (empty($scannerId) || empty($groupId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Les identifiants scanner_id et group_id sont obligatoires.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$groups = load_scanner_groups();
$groupExists = false;
foreach ($groups as $g) {
    if ($g['id'] === $groupId) {
        $groupExists = true;
        break;
    }
}

if (!$groupExists) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Groupe introuvable.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$scanners = load_scanners();
$updated = false;

foreach ($scanners as &$s) {
    if ($s['id'] === $scannerId) {
        $s['group_id'] = $groupId;
        $updated = true;
        break;
    }
}

if (!$updated) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Scanner introuvable.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

save_scanners($scanners);

echo json_encode([
    'success' => true,
    'message' => 'Scanner réassigné avec succès.',
    'scanners' => $scanners
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
