<?php
// /api/state.php - State synchronization endpoint
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/app_state.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$state = load_app_state();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST requires admin role for state modification
    if (get_current_role() !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'État modifiable par admin uniquement'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Apply same-origin + CSRF for mutations
    gh_require_same_origin_mutation();

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if ($input && is_array($input)) {
        // Merge state with input (skip audits - they're in DB)
        foreach ($input as $key => $value) {
            if ($key !== 'audits') {
                $state[$key] = $value;
            }
        }
        save_app_state($state);
        echo json_encode(['success' => true, 'state' => $state], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// GET request (public)
echo json_encode(['success' => true, 'state' => $state], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
