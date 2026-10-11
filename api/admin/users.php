<?php
// /api/admin/users.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

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

$db = get_db_connection();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connexion base de données impossible'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $users = $db->query("SELECT u.id, u.username, u.email, u.role, u.team_id, t.name as team_name FROM cl_users u LEFT JOIN cl_teams t ON u.team_id = t.id ORDER BY u.id DESC")->fetchAll();
            echo json_encode(['success' => true, 'total' => count($users), 'users' => $users], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lecture utilisateurs'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: $_POST;
        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'collaborateur';
        $teamId = !empty($body['team_id']) ? intval($body['team_id']) : null;

        if (empty($username) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO cl_users (username, email, password_hash, role, team_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $teamId]);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => "Collaborateur '$username' créé"], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Email ou pseudo existe déjà'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $userId = intval($body['user_id'] ?? $body['id'] ?? 0);
        $username = trim($body['username'] ?? '');
        $role = $body['role'] ?? 'collaborateur';
        $teamId = !empty($body['team_id']) ? intval($body['team_id']) : null;

        if (!$userId || empty($username)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID et username requis'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE cl_users SET username = ?, role = ?, team_id = ? WHERE id = ?");
            $stmt->execute([$username, $role, $teamId, $userId]);
            echo json_encode(['success' => true, 'message' => 'Profil mis à jour'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $userId = intval($_GET['id'] ?? $body['id'] ?? 0);

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $db->prepare("DELETE FROM cl_users WHERE id = ?");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur suppression'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);
}
