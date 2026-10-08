<?php
/**
 * POST /api/scans/delete
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/scans_manager.php';

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$id = trim($input['id'] ?? '');

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'L\'identifiant du scan est obligatoire.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$scans = load_all_scans();
$newScans = [];
$found = false;

foreach ($scans as $s) {
    if ($s['id'] === $id) {
        $found = true;
        continue;
    }
    $newScans[] = $s;
}

if (!$found) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Scan introuvable.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

save_all_scans($newScans);

echo json_encode([
    'success' => true,
    'message' => 'Scan supprimé avec succès.',
    'scans' => $newScans
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
