<?php
/**
 * GET/POST /api/scans/by_group
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/scans_manager.php';

$groupId = $_GET['group_id'] ?? $_POST['group_id'] ?? '';
$inputRaw = file_get_contents('php://input');
if (empty($groupId) && $inputRaw) {
    $json = json_decode($inputRaw, true);
    $groupId = $json['group_id'] ?? '';
}

$scans = load_all_scans();
if (!empty($groupId)) {
    $scans = array_filter($scans, fn($s) => ($s['group_id'] ?? '') === $groupId);
}

echo json_encode([
    'success' => true,
    'group_id' => $groupId,
    'total' => count($scans),
    'scans' => array_values($scans),
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
