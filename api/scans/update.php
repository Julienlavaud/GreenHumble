<?php
/**
 * POST /api/scans/update
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

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$id = trim($input['id'] ?? '');

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'L\'ID du scan est obligatoire.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$scans = load_all_scans();
$updated = false;

foreach ($scans as &$s) {
    if ($s['id'] === $id) {
        if (isset($input['type_scan']) && in_array($input['type_scan'], ['diagnostic', 'pipeline', 'widget'])) {
            $s['type_scan'] = $input['type_scan'];
        }
        if (isset($input['group_id'])) {
            $s['group_id'] = $input['group_id'];
        }
        if (isset($input['statut'])) {
            $s['statut'] = $input['statut'];
        }
        if (isset($input['utilisateur'])) {
            $s['utilisateur'] = $input['utilisateur'];
        }
        $updated = true;
        break;
    }
}

if (!$updated) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Scan introuvable.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!save_all_scans($scans)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Enregistrement impossible (droits d’écriture du dossier data).'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Scan mis à jour avec succès.',
    'scans' => $scans
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
