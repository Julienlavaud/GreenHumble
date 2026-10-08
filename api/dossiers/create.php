<?php
/**
 * POST /api/dossiers/create
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/dossiers_manager.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

$nom = trim($input['nom'] ?? $input['name'] ?? '');
$domaine = trim($input['domaine'] ?? $input['domain'] ?? '');

if (empty($domaine)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le domaine est requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$newDossier = create_dossier($nom, $domaine);

echo json_encode([
    'success' => true,
    'message' => 'Dossier créé avec succès.',
    'dossier' => $newDossier
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
