<?php
/**
 * API de lecture et de mise à jour des préférences visuelles.
 * Le fichier JSON reste privé : lecture et écriture passent par ce contrôleur.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

$stylePath = dirname(__DIR__, 2) . '/data/user_style.json';
$defaultStyle = [
    'fontFamily' => 'Inter',
    'fontSize' => 15,
    'textColor' => '#17352c',
    'headingColor' => '#17352c',
    'textSecondaryColor' => '#64766e',
    'bgMain' => '#f5f8f5',
    'cardBg' => '#ffffff',
    'accentColor' => '#176b4c',
    'accentSecondary' => '#00a67e',
    'sampleText' => 'GreenHumble — Diagnostic de conformité environnementale.',
];

if ($method === 'POST') {
    if (get_current_role() !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès administrateur requis.']);
        exit;
    }
    gh_require_same_origin_mutation();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données de style invalides.']);
        exit;
    }
    $allowed = array_fill_keys(array_keys($defaultStyle), true);
    $current = $defaultStyle;
    if (is_file($stylePath)) {
        $stored = json_decode((string)file_get_contents($stylePath), true);
        if (is_array($stored)) $current = array_merge($current, array_intersect_key($stored, $allowed));
    }
    $updated = array_merge($current, array_intersect_key($input, $allowed));
    $updated['fontSize'] = max(12, min(22, (int)($updated['fontSize'] ?? 15)));
    if (!is_dir(dirname($stylePath)) && !@mkdir(dirname($stylePath), 0750, true) && !is_dir(dirname($stylePath))) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Dossier de préférences indisponible.']);
        exit;
    }
    $encoded = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($encoded) || file_put_contents($stylePath, $encoded, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Enregistrement des préférences impossible.']);
        exit;
    }
    @chmod($stylePath, 0640);
    if (function_exists('opcache_reset')) @opcache_reset();
    echo json_encode(['success' => true, 'message' => 'Préférences enregistrées.', 'style' => $updated], JSON_UNESCAPED_UNICODE);
    exit;
}

$style = $defaultStyle;
if (is_file($stylePath)) {
    $stored = json_decode((string)file_get_contents($stylePath), true);
    if (is_array($stored)) $style = array_merge($style, array_intersect_key($stored, array_fill_keys(array_keys($defaultStyle), true)));
}
echo json_encode(['success' => true, 'style' => $style], JSON_UNESCAPED_UNICODE);
