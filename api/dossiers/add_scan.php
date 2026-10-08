<?php
/**
 * POST /api/dossiers/add_scan
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/dossiers_manager.php';
require_once __DIR__ . '/../includes/scans_manager.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

$dossierId = trim($input['dossier_id'] ?? $input['id'] ?? '');
$scanId = trim($input['scan_id'] ?? '');

if (empty($dossierId) || empty($scanId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'dossier_id et scan_id sont requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$updatedDossier = add_scan_to_dossier($dossierId, $scanId);

if (!$updatedDossier) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Dossier non trouvé.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Mise à jour de project_id dans le scan
$scans = load_all_scans();
foreach ($scans as &$s) {
    if (($s['id'] ?? '') === $scanId) {
        $s['project_id'] = $dossierId;
        break;
    }
}
save_all_scans($scans);

echo json_encode([
    'success' => true,
    'message' => 'Scan associé au dossier avec succès.',
    'dossier' => $updatedDossier
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
