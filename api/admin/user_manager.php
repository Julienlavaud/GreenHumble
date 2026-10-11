<?php
// /api/admin/user_manager.php
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
        $users = $config['users'] ?? [];
        echo json_encode(['success' => true, 'total' => count($users), 'users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $role = $body['role'] ?? 'user';

        if (empty($username) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username et email requis']);
            exit;
        }

        $newUser = [
            'id' => 'usr_' . time(),
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'created_at' => date('c')
        ];

        if (!isset($config['users'])) $config['users'] = [];
        $config['users'][] = $newUser;
        save_admin_json($configFile, $config);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Utilisateur créé', 'user' => $newUser], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $userId = $body['id'] ?? '';

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis']);
            exit;
        }

        $updated = false;
        foreach ($config['users'] ?? [] as &$user) {
            if ($user['id'] === $userId) {
                $user = array_merge($user, $body);
                $user['updated_at'] = date('c');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            save_admin_json($configFile, $config);
            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
        }
        break;

    case 'DELETE':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $userId = $_GET['id'] ?? $body['id'] ?? '';

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis']);
            exit;
        }

        $config['users'] = array_values(array_filter($config['users'] ?? [], fn($u) => $u['id'] !== $userId));
        save_admin_json($configFile, $config);

        echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
