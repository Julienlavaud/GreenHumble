<?php
/**
 * GreenHumble v1.1 — Purgeur OPcache /flush.php (OVH Mutualisé)
 * Réinitialise la mémoire tampon OPcache et force le rechargement des scripts PHP/CSS/JS.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$response = [
    'timestamp' => date('c'),
    'system' => 'GreenHumble v1.1 Cache Purger',
    'opcache_reset' => false,
    'message' => ''
];

if (function_exists('opcache_reset')) {
    $resetOk = @opcache_reset();
    $response['opcache_reset'] = $resetOk;
    $response['message'] = $resetOk 
        ? 'Mémoire OPcache vidée avec succès. Tous les fichiers PHP ont été rechargés.'
        : 'La fonction opcache_reset() a été exécutée.';
} else {
    $response['message'] = 'La fonction opcache_reset() n’est pas disponible sur cet hébergement PHP.';
}

$response['versioning_timestamp'] = floor(time() / 60) * 60;

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
