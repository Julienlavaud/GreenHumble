<?php
/**
 * GET /api/export/full
 * Export Souverain Global — Tous les JSON en 1 fichier
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/api_guard.php';
gh_api_guard(false);
require_once __DIR__ . '/../includes/scans_manager.php';
require_once __DIR__ . '/../includes/dossiers_manager.php';
require_once __DIR__ . '/../includes/listes_manager.php';
require_once __DIR__ . '/../includes/scanner_groups.php';

function load_user_style_data() {
    $p = __DIR__ . '/../../data/user_style.json';
    if (file_exists($p)) {
        return json_decode(file_get_contents($p), true) ?: [];
    }
    return [];
}

$data = [
    'platform' => 'GreenHumble',
    'version' => '1.1.0',
    'exported_at' => date('c'),
    'data' => [
        'scans' => load_all_scans(),
        'dossiers' => load_all_dossiers(),
        'listes' => load_all_listes(),
        'scanner_groups' => load_scanner_groups(),
        'user_style' => load_user_style_data()
    ]
];

if (isset($_GET['download']) && $_GET['download'] === 'true') {
    header('Content-Disposition: attachment; filename=greenhumble-export-full-' . time() . '.json');
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
