<?php
// /api/admin/teams.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
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

$appState = load_app_state();
$cabinets = $appState['cabinets'] ?? [];
$collaborateurs = $appState['collaborators'] ?? [];
$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $dbTeams = [];
        $dbUsers = [];
        try {
            if ($db) {
                $dbTeams = $db->query("SELECT * FROM cl_teams ORDER BY name ASC")->fetchAll();
                $dbUsers = $db->query("SELECT u.id, u.username, u.email, u.role, u.team_id, t.name as team_name FROM cl_users u LEFT JOIN cl_teams t ON u.team_id = t.id")->fetchAll();
            }
        } catch (Exception $e) {}

        $equipes = [];
        foreach ($dbTeams as $t) {
            $equipes[] = ['id' => 'team_' . $t['id'], 'nom' => $t['name'], 'region' => $t['region'] ?? '', 'chef' => $t['manager_name'] ?? '', 'actif' => true];
        }

        $membres = [];
        foreach ($dbUsers as $u) {
            $membres[] = ['id' => 'usr_' . $u['id'], 'nom' => $u['username'], 'email' => $u['email'], 'role' => $u['role'] ?? 'Auditeur', 'equipe' => $u['team_name'] ?? 'Défaut', 'statut' => 'actif'];
        }

        echo json_encode([
            'success' => true,
            'endpoint' => '/api/admin/teams',
            'equipes' => $equipes,
            'membres' => $membres
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: $_POST;
        $type = $body['type'] ?? 'membre';
        $nom = trim($body['nom'] ?? '');
        $email = trim($body['email'] ?? '');
        $role = trim($body['role'] ?? 'Auditeur');

        if (empty($nom)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nom obligatoire'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($type === 'membre') {
            $collaborateurs[] = [
                'id' => 'usr_' . time(),
                'name' => $nom,
                'email' => $email ?: strtolower(preg_replace('/\s+/', '.', $nom)) . '@greenhumble.local',
                'role' => $role,
                'avatarColor' => 'bg-emerald-600'
            ];
            $appState['collaborators'] = $collaborateurs;
            save_app_state($appState);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Collaborateur ajouté'], JSON_UNESCAPED_UNICODE);
        } else {
            $cabinets[] = ['id' => 'cab_' . time(), 'cabinetName' => $nom, 'region' => $body['region'] ?? 'France', 'email' => $email, 'managerName' => $nom];
            $appState['cabinets'] = $cabinets;
            save_app_state($appState);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Équipe créée'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $membreId = $body['id'] ?? '';
        $newRole = $body['role'] ?? null;

        if (empty($membreId) || !$newRole) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID et role requis'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $updated = false;
        foreach ($collaborateurs as &$col) {
            if ($col['id'] === $membreId) {
                $col['role'] = $newRole;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $appState['collaborators'] = $collaborateurs;
            save_app_state($appState);
            echo json_encode(['success' => true, 'message' => 'Rôle mis à jour'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Membre introuvable'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $delId = $_GET['id'] ?? $body['id'] ?? '';

        if (empty($delId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $appState['collaborators'] = array_values(array_filter($collaborateurs, fn($c) => $c['id'] !== $delId));
        $appState['cabinets'] = array_values(array_filter($cabinets, fn($c) => $c['id'] !== $delId));
        save_app_state($appState);

        echo json_encode(['success' => true, 'message' => 'Membre supprimé'], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);
}
