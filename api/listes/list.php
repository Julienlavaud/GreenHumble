<?php
/**
 * GET/POST /api/listes/list
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/api_guard.php';
gh_api_guard(false);

require_once __DIR__ . '/../includes/listes_manager.php';

$listes = load_all_listes();

echo json_encode([
    'success' => true,
    'total' => count($listes),
    'listes' => array_values($listes),
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
