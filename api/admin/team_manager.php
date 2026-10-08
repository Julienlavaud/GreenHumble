<?php
// /api/admin/team_manager.php
// Contrôleur de gestion des équipes, cabinets régionaux et groupes métiers GreenHumble

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

function load_admin_json($filePath) {
    if (!file_exists($filePath)) return [];
    return json_decode(file_get_contents($filePath), true) ?: [];
}

function save_admin_json($filePath, $data) {
    $data['updated_at'] = date('c');
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$config = load_admin_json($configFile);
$appState = load_app_state();

switch ($method) {
    case 'GET':
        $type = $_GET['type'] ?? 'all';
        $equipes = [];
        $groupes = [];

        if (isset($config['gestion_equipes'])) {
            foreach ($config['gestion_equipes'] as $item) {
                if ($item['nom'] === 'equipes') $equipes = $item['valeur'];
                if ($item['nom'] === 'groupes_metiers') $groupes = $item['valeur'];
            }
        }

        // Fallback avec les cabinets d'app_state si vide
        if (empty($equipes) && !empty($appState['cabinets'])) {
            $equipes = $appState['cabinets'];
        }

        if ($type === 'equipes') {
            echo json_encode(['success' => true, 'equipes' => $equipes], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'groupes') {
            echo json_encode(['success' => true, 'groupes_metiers' => $groupes], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'equipes' => $equipes,
                'groupes_metiers' => $groupes,
                'total_cabinets' => count($equipes),
                'total_groupes' => count($groupes)
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

        $type = $payload['type'] ?? 'equipe'; // 'equipe' ou 'groupe_metier'
        $id = $payload['id'] ?? ('cab_' . time());

        if (!isset($config['gestion_equipes'])) {
            $config['gestion_equipes'] = [];
        }

        // 1. Mise à jour de l'équipe / cabinet
        if ($type === 'equipe' || $type === 'cabinet') {
            $equipeEntry = null;
            $entryIndex = -1;
            foreach ($config['gestion_equipes'] as $idx => $item) {
                if ($item['nom'] === 'equipes') {
                    $equipeEntry = $item;
                    $entryIndex = $idx;
                    break;
                }
            }

            $currentList = $equipeEntry['valeur'] ?? [];
            $found = false;
            foreach ($currentList as &$c) {
                if ($c['id'] === $id) {
                    $c['nom'] = $payload['nom'] ?? $c['nom'];
                    $c['cabinetName'] = $payload['nom'] ?? ($c['cabinetName'] ?? $c['nom']);
                    $c['region'] = $payload['region'] ?? $c['region'];
                    $c['email'] = $payload['email'] ?? $c['email'];
                    $c['actif'] = $payload['actif'] ?? true;
                    $found = true;
                    break;
                }
            }
            unset($c);

            if (!$found) {
                $currentList[] = [
                    'id' => $id,
                    'nom' => $payload['nom'] ?? 'Nouveau Cabinet',
                    'cabinetName' => $payload['nom'] ?? 'Nouveau Cabinet',
                    'region' => $payload['region'] ?? 'France',
                    'email' => $payload['email'] ?? 'contact@climatlocal.com',
                    'actif' => true
                ];
            }

            if ($entryIndex >= 0) {
                $config['gestion_equipes'][$entryIndex]['valeur'] = $currentList;
            } else {
                $config['gestion_equipes'][] = [
                    'id' => 'equipe_siege',
                    'nom' => 'equipes',
                    'description': 'Liste des cabinets et agences territoriales',
                    'categorie' => 'gestion_equipes',
                    'valeur' => $currentList,
                    'modifiable' => true,
                    'exportable' => true,
                    'importable' => true,
                    'supprimable' => true
                ];
            }

            // Sync vers app_state.json
            $appState['cabinets'] = $currentList;
            save_app_state($appState);
            save_admin_json($configFile, $config);

            echo json_encode(['success' => true, 'message' => 'Cabinet / Équipe enregistré avec succès.', 'equipes' => $currentList], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. Mise à jour groupe métier
        if ($type === 'groupe' || $type === 'groupe_metier') {
            $groupeEntry = null;
            $entryIndex = -1;
            foreach ($config['gestion_equipes'] as $idx => $item) {
                if ($item['nom'] === 'groupes_metiers') {
                    $groupeEntry = $item;
                    $entryIndex = $idx;
                    break;
                }
            }

            $currentList = $groupeEntry['valeur'] ?? [];
            $groupeNom = $payload['groupe'] ?? 'Nouveau Pôle';
            $specialite = $payload['specialite'] ?? '';

            $currentList[] = [
                'groupe' => $groupeNom,
                'specialite' => $specialite
            ];

            if ($entryIndex >= 0) {
                $config['gestion_equipes'][$entryIndex]['valeur'] = $currentList;
            } else {
                $config['gestion_equipes'][] = [
                    'id' => 'equipe_groupes_metiers',
                    'nom' => 'groupes_metiers',
                    'description' => 'Groupes d\'expertises pour l\'assignation des dossiers',
                    'categorie' => 'gestion_equipes',
                    'valeur' => $currentList,
                    'modifiable' => true,
                    'exportable' => true,
                    'importable' => true,
                    'supprimable' => true
                ];
            }

            save_admin_json($configFile, $config);
            echo json_encode(['success' => true, 'message' => 'Groupe métier enregistré.', 'groupes_metiers' => $currentList], JSON_UNESCAPED_UNICODE);
            exit;
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis pour la suppression.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $deleted = false;
        if (isset($config['gestion_equipes'])) {
            foreach ($config['gestion_equipes'] as &$item) {
                if ($item['nom'] === 'equipes' && is_array($item['valeur'])) {
                    foreach ($item['valeur'] as $idx => $cab) {
                        if ($cab['id'] === $id) {
                            array_splice($item['valeur'], $idx, 1);
                            $appState['cabinets'] = $item['valeur'];
                            $deleted = true;
                            break 2;
                        }
                    }
                }
            }
            unset($item);
        }

        if ($deleted) {
            save_admin_json($configFile, $config);
            save_app_state($appState);
            echo json_encode(['success' => true, 'message' => "Cabinet / Équipe $id supprimé."], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Cabinet $id introuvable."], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "Méthode non autorisée : $method"], JSON_UNESCAPED_UNICODE);
        break;
}
