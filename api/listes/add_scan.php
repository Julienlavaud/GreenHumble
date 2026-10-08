<?php
/**
 * POST /api/listes/add_scan
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/listes_manager.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

$listId = trim($input['list_id'] ?? $input['id'] ?? '');
$scanId = trim($input['scan_id'] ?? '');

if (empty($listId) || empty($scanId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'list_id et scan_id sont requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$updatedList = add_scan_to_liste($listId, $scanId);

if (!$updatedList) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Liste non trouvée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Scan ajouté à la liste avec succès.',
    'list' => $updatedList
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
