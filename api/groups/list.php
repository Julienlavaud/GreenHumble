<?php
/**
 * GET /api/groups/list
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

require_once __DIR__ . '/../includes/scanner_groups.php';

$groups = load_scanner_groups();
$scanners = load_scanners();

echo json_encode([
    'success' => true,
    'groups' => $groups,
    'scanners' => $scanners,
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
