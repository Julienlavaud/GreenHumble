<?php
/**
 * POST /api/listes/create
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

require_once __DIR__ . '/../includes/listes_manager.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

$nom = trim($input['nom'] ?? $input['name'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($nom)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le nom de la liste est requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$newList = create_liste($nom, $description);

if ($newList === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'La liste n’a pas pu être enregistrée (droits d’écriture du dossier data).'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Liste créée avec succès.',
    'list' => $newList
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
