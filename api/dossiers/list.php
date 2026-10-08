<?php
/**
 * GET/POST /api/dossiers/list
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/dossiers_manager.php';

$dossiers = load_all_dossiers();

echo json_encode([
    'success' => true,
    'total' => count($dossiers),
    'dossiers' => array_values($dossiers),
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
