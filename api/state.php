<?php
// /api/state.php
// GET and POST endpoint for React frontend state synchronization on OVH
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/app_state.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$state = load_app_state();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        // Handle patch logic similar to server.ts
        if (isset($input['audits']) && is_array($input['audits'])) {
            // Audits are stored in DB, but the React app expects them in the state
            // For PHP version, we might want to sync them with cl_audits table instead?
            // However, the user specifically asked about the 'data' folder.
            // Let's stick to the JSON for settings and keep audits in SQL.
        }
        
        // Merge state with input
        foreach ($input as $key => $value) {
            if ($key !== 'audits') {
                $state[$key] = $value;
            }
        }
        
        save_app_state($state);
        echo json_encode(['success' => true, 'state' => $state]);
        exit;
    }
}

// GET request
echo json_encode(['success' => true, 'state' => $state]);
