<?php
// /api/admin/ai_console.php
// Contrôleur centralisé Moteur IA & Allégations GreenHumble
// Gère : variables_ia, dictionnaires, regles_metier, prompts, templates_json, scoring, conformite

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

function get_full_config($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $raw = file_get_contents($filePath);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_full_config($filePath, $data) {
    $data['updated_at'] = date('c');
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Synchronisation automatique vers app_state.json pour l'IA et le scanner
    if (isset($data['variables_ia'])) {
        $appState = load_app_state();
        if (!isset($appState['scanVars'])) {
            $appState['scanVars'] = [];
        }
        foreach ($data['variables_ia'] as $v) {
            if ($v['nom'] === 'aiProvider') $appState['scanVars']['aiProvider'] = $v['valeur'];
            if ($v['nom'] === 'aiModel') $appState['scanVars']['aiModel'] = $v['valeur'];
        }
        save_app_state($appState);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$config = get_full_config($configFile);

switch ($method) {
    case 'GET':
        if ($action === 'export') {
            header('Content-Disposition: attachment; filename="greenhumble_admin_config_' . date('Ymd_His') . '.json"');
            echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $category = $_GET['category'] ?? null;
        $id = $_GET['id'] ?? null;

        if ($id) {
            foreach ($config as $catKey => $items) {
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (isset($item['id']) && $item['id'] === $id) {
                            echo json_encode(['success' => true, 'item' => $item, 'category' => $catKey], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }
                }
            }
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Élément introuvable : $id"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($category && isset($config[$category])) {
            echo json_encode(['success' => true, 'category' => $category, 'items' => $config[$category]], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => true, 'config' => $config], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
    case 'PUT':
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);

        if ($action === 'import') {
            if (!is_array($payload) || empty($payload)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Fichier JSON invalide pour importation.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            save_full_config($configFile, $payload);
            echo json_encode(['success' => true, 'message' => 'Configuration importée et synchronisée avec succès.', 'config' => $payload], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!isset($payload['id']) || !isset($payload['categorie'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Format incomplet. Les champs id et categorie sont obligatoires.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $category = $payload['categorie'];
        if (!isset($config[$category])) {
            $config[$category] = [];
        }

        $updated = false;
        foreach ($config[$category] as &$existingItem) {
            if (isset($existingItem['id']) && $existingItem['id'] === $payload['id']) {
                $existingItem = array_merge($existingItem, $payload);
                $updated = true;
                break;
            }
        }
        unset($existingItem);

        if (!$updated) {
            $config[$category][] = [
                'id' => $payload['id'],
                'nom' => $payload['nom'] ?? $payload['id'],
                'description' => $payload['description'] ?? '',
                'categorie' => $category,
                'valeur' => $payload['valeur'] ?? '',
                'modifiable' => $payload['modifiable'] ?? true,
                'exportable' => $payload['exportable'] ?? true,
                'importable' => $payload['importable'] ?? true,
                'supprimable' => $payload['supprimable'] ?? true,
            ];
        }

        save_full_config($configFile, $config);
        echo json_encode(['success' => true, 'message' => $updated ? 'Élément mis à jour.' : 'Nouvel élément ajouté.', 'item' => $payload], JSON_UNESCAPED_UNICODE);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $rawInput = file_get_contents('php://input');
            $body = json_decode($rawInput, true);
            $id = $body['id'] ?? null;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requis pour la suppression.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $deleted = false;
        foreach ($config as $catKey => &$items) {
            if (is_array($items)) {
                foreach ($items as $idx => $item) {
                    if (isset($item['id']) && $item['id'] === $id) {
                        if (isset($item['supprimable']) && $item['supprimable'] === false) {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'error' => 'Cet élément système est protégé contre la suppression.'], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                        array_splice($items, $idx, 1);
                        $deleted = true;
                        break 2;
                    }
                }
            }
        }
        unset($items);

        if ($deleted) {
            save_full_config($configFile, $config);
            echo json_encode(['success' => true, 'message' => "Élément $id supprimé avec succès."], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Élément $id introuvable."], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "Méthode non autorisée : $method"], JSON_UNESCAPED_UNICODE);
        break;
}
