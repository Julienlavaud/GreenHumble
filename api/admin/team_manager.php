<?php
// /api/admin/team_manager.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/app_state.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Auth check
if (get_current_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès admin requis'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Same-origin + CSRF for mutations
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    gh_require_same_origin_mutation();
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$configFile = __DIR__ . '/../../data/admin_config.json';

function load_admin_json($filePath) {
    if (!file_exists($filePath)) return [];
    return json_decode(file_get_contents($filePath), true) ?: [];
}

function save_admin_json($filePath, $data) {
    $data['updated_at'] = date('c');
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$config = load_admin_json($configFile);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $teams = $config['teams'] ?? [];
        echo json_encode(['success' => true, 'total' => count($teams), 'teams' => $teams], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $nom = trim($body['nom'] ?? '');
        $region = trim($body['region'] ?? '');

        if (empty($nom)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nom requis']);
            exit;
        }

        $newTeam = [
            'id' => 'team_' . time(),
            'nom' => $nom,
            'region' => $region,
            'manager' => $body['manager'] ?? '',
            'created_at' => date('c')
        ];

        if (!isset($config['teams'])) $config['teams'] = [];
        $config['teams'][] = $newTeam;
        save_admin_json($configFile, $config);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Équipe créée', 'team' => $newTeam], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $teamId = $body['id'] ?? '';

        if (empty($teamId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis']);
            exit;
        }

        $updated = false;
        foreach ($config['teams'] ?? [] as &$team) {
            if ($team['id'] === $teamId) {
                $team = array_merge($team, $body);
                $team['updated_at'] = date('c');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            save_admin_json($configFile, $config);
            echo json_encode(['success' => true, 'message' => 'Équipe mise à jour'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Équipe introuvable']);
        }
        break;

    case 'DELETE':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $teamId = $_GET['id'] ?? $body['id'] ?? '';

        if (empty($teamId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis']);
            exit;
        }

        $config['teams'] = array_values(array_filter($config['teams'] ?? [], fn($t) => $t['id'] !== $teamId));
        save_admin_json($configFile, $config);

        echo json_encode(['success' => true, 'message' => 'Équipe supprimée'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
