<?php
// /api/admin/config.php
// REST API Modulaire - Endpoint /api/admin/config
// Gère : variables_ia, prompts, templates_json, dictionnaires, regles_metier, opportunites_metiers, scoring, conformite
// Méthodes : GET, POST, PUT, DELETE

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/app_state.php';
require_once __DIR__ . '/../includes/db.php';

$structureFile = __DIR__ . '/rest_structure.json';

// Helper pour charger la structure de configuration
function get_config_structure() {
    global $structureFile;
    if (file_exists($structureFile)) {
        $content = file_get_contents($structureFile);
        $data = json_decode($content, true);
        if (is_array($data)) {
            foreach ($data as $ep) {
                if (isset($ep['endpoint']) && $ep['endpoint'] === '/api/admin/config') {
                    return $ep['objects'] ?? [];
                }
            }
        }
    }
    return [];
}

// Helper pour sauvegarder la structure
function save_config_structure($objects) {
    global $structureFile;
    $all = [];
    if (file_exists($structureFile)) {
        $all = json_decode(file_get_contents($structureFile), true) ?: [];
    }
    $found = false;
    foreach ($all as &$ep) {
        if (isset($ep['endpoint']) && $ep['endpoint'] === '/api/admin/config') {
            $ep['objects'] = $objects;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $all[] = [
            "endpoint" => "/api/admin/config",
            "description" => "Gère toute la configuration IA.",
            "methods" => ["GET", "POST", "PUT", "DELETE"],
            "objects" => $objects
        ];
    }
    file_put_contents($structureFile, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // GET_config : liste des configurations
        $categoryFilter = $_GET['categorie'] ?? null;
        $items = get_config_structure();

        // Synchronisation avec app_state et cl_texts si présents
        $appState = load_app_state();
        $scanVars = $appState['scanVars'] ?? [];

        foreach ($items as &$item) {
            if ($item['id'] === 'ai_provider' && !empty($scanVars['aiProvider'])) {
                $item['valeur'] = $scanVars['aiProvider'];
            } elseif ($item['id'] === 'ai_model' && !empty($scanVars['aiModel'])) {
                $item['valeur'] = $scanVars['aiModel'];
            }
        }

        if ($categoryFilter) {
            $items = array_values(array_filter($items, function($item) use ($categoryFilter) {
                return ($item['categorie'] ?? '') === $categoryFilter;
            }));
        }

        echo json_encode([
            "success" => true,
            "endpoint" => "/api/admin/config",
            "total" => count($items),
            "data" => $items
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // POST_config : ajout d'un élément
        $raw = file_get_contents("php://input");
        $body = json_decode($raw, true) ?: $_POST;

        $nom = trim($body['nom'] ?? '');
        $description = trim($body['description'] ?? '');
        $categorie = trim($body['categorie'] ?? 'variables_ia');
        $valeur = is_array($body['valeur'] ?? '') ? json_encode($body['valeur']) : trim($body['valeur'] ?? '');

        if (empty($nom) || empty($valeur)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Les champs 'nom' et 'valeur' sont requis."
            ]);
            exit;
        }

        $items = get_config_structure();
        $id = $body['id'] ?? 'cfg_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($nom));

        // Vérification conflit
        foreach ($items as $existing) {
            if ($existing['id'] === $id || $existing['nom'] === $nom) {
                http_response_code(409);
                echo json_encode([
                    "success" => false,
                    "error" => "Un élément avec ce nom ou identifiant existe déjà."
                ]);
                exit;
            }
        }

        $newItem = [
            "id" => $id,
            "nom" => $nom,
            "description" => $description,
            "categorie" => $categorie,
            "valeur" => $valeur,
            "modifiable" => isset($body['modifiable']) ? (bool)$body['modifiable'] : true,
            "exportable" => isset($body['exportable']) ? (bool)$body['exportable'] : true,
            "importable" => isset($body['importable']) ? (bool)$body['importable'] : true,
            "supprimable" => isset($body['supprimable']) ? (bool)$body['supprimable'] : true
        ];

        $items[] = $newItem;
        save_config_structure($items);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Élément de configuration ajouté avec succès",
            "item" => $newItem
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        // PUT_config : modification
        $raw = file_get_contents("php://input");
        $body = json_decode($raw, true) ?: [];

        $id = trim($body['id'] ?? '');
        $valeur = isset($body['valeur']) ? (is_array($body['valeur']) ? json_encode($body['valeur']) : trim($body['valeur'])) : null;
        $description = isset($body['description']) ? trim($body['description']) : null;

        if (empty($id) || $valeur === null) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Identifiant 'id' et 'valeur' requis."
            ]);
            exit;
        }

        $items = get_config_structure();
        $updatedItem = null;

        foreach ($items as &$item) {
            if ($item['id'] === $id) {
                if (isset($item['modifiable']) && $item['modifiable'] === false) {
                    http_response_code(403);
                    echo json_encode([
                        "success" => false,
                        "error" => "Cet élément est verrouillé en modification."
                    ]);
                    exit;
                }
                $item['valeur'] = $valeur;
                if ($description !== null) {
                    $item['description'] = $description;
                }
                $updatedItem = $item;
                break;
            }
        }

        if (!$updatedItem) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Élément introuvable avec l'id '$id'."
            ]);
            exit;
        }

        save_config_structure($items);

        // Mise à jour de l'app_state si variable IA clé
        $appState = load_app_state();
        if ($id === 'ai_provider') {
            $appState['scanVars']['aiProvider'] = $valeur;
            save_app_state($appState);
        } elseif ($id === 'ai_model') {
            $appState['scanVars']['aiModel'] = $valeur;
            save_app_state($appState);
        }

        echo json_encode([
            "success" => true,
            "message" => "Configuration mise à jour avec succès",
            "updated" => $updatedItem
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'DELETE':
        // DELETE_config : suppression
        $raw = file_get_contents("php://input");
        $body = json_decode($raw, true) ?: [];
        $id = trim($_GET['id'] ?? $body['id'] ?? '');

        if (empty($id)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Paramètre 'id' requis."
            ]);
            exit;
        }

        $items = get_config_structure();
        $newItems = [];
        $found = false;

        foreach ($items as $item) {
            if ($item['id'] === $id) {
                $found = true;
                if (isset($item['supprimable']) && $item['supprimable'] === false) {
                    http_response_code(403);
                    echo json_encode([
                        "success" => false,
                        "error" => "Cet élément système est protégé contre la suppression."
                    ]);
                    exit;
                }
                continue; // Supprimé
            }
            $newItems[] = $item;
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Élément introuvable."
            ]);
            exit;
        }

        save_config_structure($newItems);

        echo json_encode([
            "success" => true,
            "message" => "Configuration supprimée",
            "deleted_id" => $id
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "error" => "Méthode non autorisée"
        ]);
        break;
}
