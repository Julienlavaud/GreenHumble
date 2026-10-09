<?php
/**
 * Met à jour l'état d'un site appartenant à une liste après un scan individuel.
 * POST JSON: {list_id, site_id, statut, scan_id?}
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/listes_manager.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!(($_SESSION['admin_logged_in'] ?? false) === true && $role === 'admin') && !in_array($role, ['collaborateur'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Connexion requise pour modifier une liste.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (function_exists('gh_require_same_origin_mutation')) {
    gh_require_same_origin_mutation();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
$listId = trim((string)($input['list_id'] ?? ''));
$siteId = trim((string)($input['site_id'] ?? ''));
$status = trim((string)($input['statut'] ?? ''));
$scanId = trim((string)($input['scan_id'] ?? ''));

$allowed = ['à_analyser', 'en_cours', 'termine', 'erreur'];
if ($listId === '' || $siteId === '' || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'list_id, site_id et un statut autorisé sont requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$lists = load_all_listes();
$listFound = false;
$siteFound = false;
foreach ($lists as &$list) {
    if (($list['id'] ?? '') !== $listId) continue;
    $listFound = true;
    if (!isset($list['sites']) || !is_array($list['sites'])) break;
    foreach ($list['sites'] as &$site) {
        if (($site['id'] ?? '') !== $siteId) continue;
        $siteFound = true;
        $site['statut'] = $status;
        $site['updated_at'] = date('c');
        if ($scanId !== '') $site['scan_id'] = $scanId;
        if ($status === 'en_cours') $site['started_at'] = date('c');
        if (in_array($status, ['termine', 'erreur'], true)) $site['finished_at'] = date('c');
        break;
    }
    unset($site);
    break;
}
unset($list);

if (!$listFound || !$siteFound) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Liste ou site introuvable.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!save_all_listes($lists)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossible d’enregistrer le nouvel état du site.'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['success' => true, 'list_id' => $listId, 'site_id' => $siteId, 'statut' => $status, 'scan_id' => $scanId ?: null], JSON_UNESCAPED_UNICODE);
