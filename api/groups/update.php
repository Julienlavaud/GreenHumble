<?php
/**
 * POST /api/groups/update
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/scanner_groups.php';

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$id = trim($input['id'] ?? '');
$nom = trim($input['nom'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($id) || empty($nom)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'L\'ID et le nom du groupe sont obligatoires.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$groups = load_scanner_groups();
$updated = false;

foreach ($groups as &$g) {
    if ($g['id'] === $id) {
        // If updating the default group, protect name if required or update
        $g['nom'] = $nom;
        $g['description'] = $description;
        $updated = true;
        break;
    }
}

if (!$updated) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Groupe non trouvé.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

save_scanner_groups($groups);

echo json_encode([
    'success' => true,
    'message' => 'Groupe mis à jour avec succès.',
    'groups' => $groups
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
