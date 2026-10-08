<?php
// /api/admin/user_manager.php
// Contrôleur centralisé Utilisateurs, Mots de passe, Rôles et Permissions RBAC GreenHumble

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/app_state.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$configFile = __DIR__ . '/../../data/admin_config.json';

function get_config($filePath) {
    if (!file_exists($filePath)) return [];
    return json_decode(file_get_contents($filePath), true) ?: [];
}

function save_config($filePath, $data) {
    $data['updated_at'] = date('c');
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$config = get_config($configFile);
$appState = load_app_state();

switch ($method) {
    case 'GET':
        $section = $_GET['section'] ?? 'all'; // 'users', 'roles', 'permissions', 'all'
        
        $collabs = $appState['collaborators'] ?? [];
        $passwordsMap = $appState['passwordsMap'] ?? [];

        $roles = [];
        $permissions = [];
        if (isset($config['gestion_equipes'])) {
            foreach ($config['gestion_equipes'] as $item) {
                if ($item['nom'] === 'roles') $roles = $item['valeur'];
                if ($item['nom'] === 'permissions') $permissions = $item['valeur'];
            }
        }

        // Format sécurisé des utilisateurs
        $safeUsers = array_map(function($c) use ($passwordsMap) {
            return [
                'id' => $c['id'],
                'name' => $c['name'],
                'email' => $c['email'],
                'role' => $c['role'],
                'cabinet' => $c['cabinet'] ?? 'Cabinet Principal',
                'has_password' => !empty($passwordsMap[$c['id']]),
                'avatarColor' => $c['avatarColor'] ?? 'bg-indigo-700'
            ];
        }, $collabs);

        if ($section === 'users') {
            echo json_encode(['success' => true, 'users' => $safeUsers, 'count' => count($safeUsers)], JSON_UNESCAPED_UNICODE);
        } elseif ($section === 'roles') {
            echo json_encode(['success' => true, 'roles' => $roles], JSON_UNESCAPED_UNICODE);
        } elseif ($section === 'permissions') {
            echo json_encode(['success' => true, 'permissions' => $permissions], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'users' => $safeUsers,
                'roles' => $roles,
                'permissions' => $permissions,
                'total_users' => count($safeUsers)
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
    case 'PUT':
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Données JSON invalides.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 1. Action : Génération ou régénération d'un mot de passe sécurisé
        if ($action === 'generate_password') {
            $userId = $payload['user_id'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Identifiant utilisateur requis.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Générateur de mot de passe sécurisé (8 caractères lisibles et robustes)
            $chars = 'abcdefghjkmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ!@#$';
            $plainPassword = '';
            for ($i = 0; $i < 9; $i++) {
                $plainPassword .= $chars[random_int(0, strlen($chars) - 1)];
            }

            // Synchronisation dans appState passwordsMap
            $appState['passwordsMap'][$userId] = $plainPassword;
            save_app_state($appState);

            // Synchronisation hash dans admin_config.json
            if (isset($config['gestion_connexion'])) {
                foreach ($config['gestion_connexion'] as &$item) {
                    if ($item['nom'] === 'mots_de_passe_hashes') {
                        $item['valeur'][$userId] = password_hash($plainPassword, PASSWORD_DEFAULT);
                        break;
                    }
                }
                unset($item);
                save_config($configFile, $config);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Nouveau mot de passe généré.',
                'user_id' => $userId,
                'password' => $plainPassword
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. Action : Mise à jour des rôles ou des permissions
        if ($action === 'save_permissions' && isset($payload['permissions'])) {
            if (isset($config['gestion_equipes'])) {
                foreach ($config['gestion_equipes'] as &$item) {
                    if ($item['nom'] === 'permissions') {
                        $item['valeur'] = $payload['permissions'];
                        break;
                    }
                }
                unset($item);
                save_config($configFile, $config);
            }
            echo json_encode(['success' => true, 'message' => 'Matrice des permissions mise à jour.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. Sauvegarde / Création d'un utilisateur
        $userId = $payload['id'] ?? ('collab_' . time());
        $name = trim($payload['name'] ?? 'Nouveau Collaborateur');
        $email = trim($payload['email'] ?? '');
        $role = $payload['role'] ?? 'Consultant Climat & Carbone';
        $password = trim($payload['password'] ?? '');

        // Mise à jour de la liste des collaborateurs dans appState
        $collabs = $appState['collaborators'] ?? [];
        $found = false;
        foreach ($collabs as &$c) {
            if ($c['id'] === $userId) {
                $c['name'] = $name;
                $c['email'] = $email;
                $c['role'] = $role;
                $found = true;
                break;
            }
        }
        unset($c);

        if (!$found) {
            $collabs[] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'avatarColor' => 'bg-emerald-700'
            ];
        }
        $appState['collaborators'] = $collabs;

        // Mise à jour du mot de passe si fourni
        if (!empty($password)) {
            $appState['passwordsMap'][$userId] = $password;
            if (isset($config['gestion_connexion'])) {
                foreach ($config['gestion_connexion'] as &$item) {
                    if ($item['nom'] === 'mots_de_passe_hashes') {
                        $item['valeur'][$userId] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    if ($item['nom'] === 'emails') {
                        $item['valeur'][$userId] = $email;
                    }
                }
                unset($item);
            }
        }

        save_app_state($appState);
        save_config($configFile, $config);

        echo json_encode([
            'success' => true,
            'message' => $found ? "Utilisateur $name mis à jour." : "Utilisateur $name créé.",
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'DELETE':
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            $body = json_decode(file_get_contents('php://input'), true);
            $userId = $body['id'] ?? null;
        }

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Identifiant utilisateur manquant.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $collabs = $appState['collaborators'] ?? [];
        $deleted = false;
        foreach ($collabs as $idx => $c) {
            if ($c['id'] === $userId) {
                array_splice($collabs, $idx, 1);
                $deleted = true;
                break;
            }
        }

        if ($deleted) {
            $appState['collaborators'] = $collabs;
            unset($appState['passwordsMap'][$userId]);
            save_app_state($appState);

            // Nettoyage dans admin_config.json
            if (isset($config['gestion_connexion'])) {
                foreach ($config['gestion_connexion'] as &$item) {
                    if (is_array($item['valeur']) && isset($item['valeur'][$userId])) {
                        unset($item['valeur'][$userId]);
                    }
                }
                unset($item);
                save_config($configFile, $config);
            }

            echo json_encode(['success' => true, 'message' => "Utilisateur $userId supprimé avec succès."], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Utilisateur $userId introuvable."], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "Méthode non autorisée : $method"], JSON_UNESCAPED_UNICODE);
        break;
}
