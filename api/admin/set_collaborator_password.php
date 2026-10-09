<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/app_state.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}
if (get_current_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès administrateur requis.']);
    exit;
}
gh_require_same_origin_mutation();

$input = json_decode((string)file_get_contents('php://input'), true);
$id = is_array($input) ? trim((string)($input['id'] ?? '')) : '';
$password = is_array($input) ? (string)($input['password'] ?? '') : '';
if ($id === '' || strlen($password) < 12) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Identifiant collaborateur requis et mot de passe de 12 caractères minimum.']);
    exit;
}
$state = load_app_state();
$found = false;
foreach (($state['collaborators'] ?? []) as $collaborator) {
    if (is_array($collaborator) && hash_equals((string)($collaborator['id'] ?? ''), $id)) {
        if (trim((string)($collaborator['email'] ?? '')) === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Renseignez et sauvegardez l’email avant de créer l’accès.']);
            exit;
        }
        $found = true;
        break;
    }
}
if (!$found) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Collaborateur introuvable.']);
    exit;
}
if (!isset($state['passwordsMap']) || !is_array($state['passwordsMap'])) $state['passwordsMap'] = [];
$state['passwordsMap'][$id] = password_hash($password, PASSWORD_DEFAULT);
if (!save_app_state($state)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossible d’enregistrer l’accès.']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Accès créé ou réinitialisé. Communiquez le mot de passe au collaborateur par un canal sûr.']);
