<?php
/**
 * GreenHumble v1.1 — Purgeur OPcache /api/flush.php (OVH Mutualisé)
 * Réinitialise la mémoire tampon OPcache et force le rechargement des scripts PHP/CSS/JS.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (get_current_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès administrateur requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

gh_require_same_origin_mutation();

$response = [
    'timestamp' => date('c'),
    'system' => 'GreenHumble v1.1 Cache Purger',
    'success' => false,
    'opcache_reset' => false,
    'message' => ''
];

if (function_exists('opcache_reset')) {
    $resetOk = @opcache_reset();
    $response['opcache_reset'] = $resetOk;
    $response['success'] = $resetOk;
    if ($resetOk) {
        $response['message'] = 'Mémoire OPcache vidée avec succès. Tous les fichiers PHP ont été rechargés.';
    } else {
        http_response_code(503);
        $response['error'] = 'OPcache est disponible, mais le serveur a refusé sa réinitialisation.';
        $response['message'] = $response['error'];
    }
} else {
    http_response_code(503);
    $response['error'] = 'La fonction opcache_reset() n’est pas disponible sur cet hébergement PHP.';
    $response['message'] = $response['error'];
}

$response['versioning_timestamp'] = floor(time() / 60) * 60;

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
