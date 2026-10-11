<?php
// /api/admin/console.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode([
            'status' => 'OK',
            'app' => 'GreenHumble v1.1',
            'aiProvider' => getenv('AI_PROVIDER') ?: 'gemini',
            'aiModel' => getenv('AI_MODEL') ?: 'gemini-3.8-flash',
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $action = $body['action'] ?? 'ping';

        switch ($action) {
            case 'ping':
                echo json_encode(['success' => true, 'message' => 'Console active', 'timestamp' => date('c')], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
            case 'flush_cache':
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                    echo json_encode(['success' => true, 'message' => 'Cache flushé'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(501);
                    echo json_encode(['success' => false, 'error' => 'OPcache non disponible'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Action inconnue: $action"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
