<?php
/**
 * GET /api/export/raw
 * Export Données Brutes Sans Transformation
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/api_guard.php';
gh_api_guard(false);

require_once __DIR__ . '/../includes/scans_manager.php';
require_once __DIR__ . '/../includes/dossiers_manager.php';
require_once __DIR__ . '/../includes/listes_manager.php';
require_once __DIR__ . '/../includes/scanner_groups.php';

function load_user_style_raw() {
    $p = __DIR__ . '/../../data/user_style.json';
    if (file_exists($p)) {
        return json_decode(file_get_contents($p), true) ?: [];
    }
    return [];
}

echo json_encode([
    'scans' => load_all_scans(),
    'dossiers' => load_all_dossiers(),
    'listes' => load_all_listes(),
    'scanner_groups' => load_scanner_groups(),
    'user_style' => load_user_style_raw()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
