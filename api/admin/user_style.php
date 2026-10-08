<?php
/**
 * GreenHumble v1.1 — API REST Gestionnaire de Style Utilisateur
 * Fichier : /api/admin/user_style.php
 * Lecture et sauvegarde de /data/user_style.json
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$possiblePaths = [
    __DIR__ . '/../../data/user_style.json',
    __DIR__ . '/../data/user_style.json'
];

$defaultStyle = [
    'fontFamily' => 'Inter',
    'fontSize' => 15,
    'textColor' => '#E0E0E0',
    'headingColor' => '#FFFFFF',
    'textSecondaryColor' => '#AAAAAA',
    'bgMain' => '#121212',
    'cardBg' => '#1E1E1E',
    'accentColor' => '#2A7AE2',
    'accentSecondary' => '#00A67E',
    'sampleText' => 'GreenHumble v1.1 — Détection et audit de conformité environnementale selon les directives EmpCO et Green Claims.',
    'updatedAt' => date('c')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    // Charger état actuel
    $current = $defaultStyle;
    foreach ($possiblePaths as $p) {
        if (file_exists($p)) {
            $parsed = json_decode(file_get_contents($p), true);
            if (is_array($parsed)) {
                $current = array_merge($current, $parsed);
                break;
            }
        }
    }

    $updated = array_merge($current, $data);
    $updated['updatedAt'] = date('c');

    $savedCount = 0;
    foreach ($possiblePaths as $p) {
        $dir = dirname($p);
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (file_put_contents($p, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $savedCount++;
        }
    }

    // Purge OPcache si disponible
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Préférences de style sauvegardées dans /data/user_style.json',
        'style' => $updated
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// GET Handler
$found = null;
foreach ($possiblePaths as $p) {
    if (file_exists($p)) {
        $content = file_get_contents($p);
        $parsed = json_decode($content, true);
        if (is_array($parsed)) {
            $found = $parsed;
            break;
        }
    }
}

if (!$found) {
    $found = $defaultStyle;
    // Initialiser les fichiers
    foreach ($possiblePaths as $p) {
        $dir = dirname($p);
        if (!file_exists($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($p, json_encode($found, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

echo json_encode([
    'success' => true,
    'style' => $found
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
