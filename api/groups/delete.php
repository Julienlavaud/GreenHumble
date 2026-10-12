<?php
/**
 * POST /api/groups/delete
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

$id = trim($input['id'] ?? '');

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'L\'ID du groupe est obligatoire.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Special check: cannot delete default group "scanner widget"
if ($id === DEFAULT_GROUP_ID) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Le groupe spécial "scanner widget" ne peut pas être supprimé.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$groups = load_scanner_groups();
$newGroups = [];
$found = false;

foreach ($groups as $g) {
    if ($g['id'] === $id) {
        if (!empty($g['is_default']) || strtolower($g['nom'] ?? '') === strtolower(DEFAULT_GROUP_NAME)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Le groupe spécial "scanner widget" ne peut pas être supprimé.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $found = true;
        continue;
    }
    $newGroups[] = $g;
}

if (!$found) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Groupe introuvable.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

save_scanner_groups($newGroups);

// Reassign any scanners in the deleted group to DEFAULT_GROUP_ID
$scanners = load_scanners();
$scannersChanged = false;
foreach ($scanners as &$s) {
    if (($s['group_id'] ?? '') === $id) {
        $s['group_id'] = DEFAULT_GROUP_ID;
        $scannersChanged = true;
    }
}
if ($scannersChanged) {
    save_scanners($scanners);
}

echo json_encode([
    'success' => true,
    'message' => 'Groupe supprimé avec succès. Les scanners ont été réassignés au groupe par défaut.',
    'groups' => $newGroups,
    'scanners' => $scanners
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
