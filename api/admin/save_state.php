<?php
// /api/admin/save_state.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/app_state.php';

if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gh_require_same_origin_mutation();
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $type = $input['type'] ?? '';
        $data = $input['data'] ?? [];

        $state = load_app_state();

        if (isset($input['batch'])) {
            foreach ($input['batch'] as $item) {
                $type = $item['type'] ?? '';
                $data = $item['data'] ?? [];
                $state = update_state_by_type($state, $type, $data);
            }
        } else {
            $type = $input['type'] ?? '';
            $data = $input['data'] ?? [];
            $state = update_state_by_type($state, $type, $data);
        }

        save_app_state($state);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

function update_state_by_type($state, $type, $data) {
    switch ($type) {
        case 'cabinets':
            $state['cabinets'] = $data;
            break;
        case 'collaborators':
            $state['collaborators'] = $data;
            break;
        case 'serviceOpps':
            $state['serviceOpps'] = $data;
            break;
        case 'legalTexts':
            $state['legalTexts'] = $data;
            break;
        case 'scanVars':
            $state['scanVars'] = array_merge($state['scanVars'] ?? [], $data);
            break;
        case 'agency':
            $state['agency'] = array_merge($state['agency'] ?? [], $data);
            break;
        case 'pageTexts':
            $state['pageTexts'] = array_merge($state['pageTexts'] ?? [], $data);
            break;
    }
    return $state;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
