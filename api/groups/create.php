<?php
/**
 * POST /api/groups/create
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

$nom = trim($input['nom'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($nom)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Le nom du groupe est obligatoire.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$groups = load_scanner_groups();

$newGroup = [
    'id' => 'grp_' . uniqid(),
    'nom' => $nom,
    'description' => $description,
    'date_creation' => date('c'),
    'is_default' => false
];

$groups[] = $newGroup;
save_scanner_groups($groups);

echo json_encode([
    'success' => true,
    'message' => 'Groupe créé avec succès.',
    'group' => $newGroup,
    'groups' => $groups
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
