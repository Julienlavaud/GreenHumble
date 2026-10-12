<?php
/**
 * POST /api/scans/create
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

$url = trim($input['url'] ?? '');
$type_scan = trim($input['type_scan'] ?? 'diagnostic');
$group_id = trim($input['group_id'] ?? 'grp_widget_default');
$resultat_ia = $input['resultat_ia'] ?? null;
$utilisateur = trim($input['utilisateur'] ?? 'admin');
$statut = trim($input['statut'] ?? 'termine');

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'L\'URL est requise pour enregistrer un scan.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $newScan = record_scan($url, $type_scan, $resultat_ia, 'full', [], $utilisateur, $statut);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Scan créé et enregistré avec succès dans scans.json.',
    'scan' => $newScan
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
