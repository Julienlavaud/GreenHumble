<?php
/**
 * Garde commune des endpoints JSON internes (listes, scans, groupes, dossiers, exports).
 * Exige une session ou un jeton API autorisé ; les écritures de session exigent en plus
 * l'origine identique et le jeton CSRF (envoyé automatiquement par le script de header.php).
 */
require_once __DIR__ . '/auth.php';

function gh_api_guard(bool $mutation = true, array $roles = ['admin', 'pipeline', 'collaborator', 'collaborateur', 'consultant']): void {
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    $role = get_current_role();
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Connexion requise.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $usesSession = !empty($_SESSION['role']) || !empty($_SESSION['admin_logged_in']);
    if ($mutation && $usesSession) {
        gh_require_same_origin_mutation();
    }
}
