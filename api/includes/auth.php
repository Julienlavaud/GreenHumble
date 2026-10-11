<?php
/**
 * GreenHumble v1.1 — Module d'Authentification & Gestion des Rôles
 * /api/includes/auth.php
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session defaults must be applied before session_start().
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    session_start();
}

/**
 * Comptes prédéfinis sécurisés
 */
function get_system_accounts() {
    $accounts = [];
    $definitions = [
        'admin' => [
            'username_env' => 'GREENHUMBLE_ADMIN_USERNAME',
            'hash_env' => 'GREENHUMBLE_ADMIN_PASSWORD_HASH',
            'role' => 'admin',
            'id' => 'usr_admin',
            'name' => 'Administrateur GreenHumble'
        ],
        'pipeline' => [
            'username_env' => 'GREENHUMBLE_PIPELINE_USERNAME',
            'hash_env' => 'GREENHUMBLE_PIPELINE_PASSWORD_HASH',
            'role' => 'pipeline',
            'id' => 'usr_pipeline',
            'name' => 'Service Pipeline Automatique'
        ]
    ];

    foreach ($definitions as $key => $definition) {
        $username = getenv($definition['username_env']);
        $passwordHash = getenv($definition['hash_env']);
        if (!is_string($username) || trim($username) === '' || !is_string($passwordHash) || $passwordHash === '') {
            continue;
        }
        $definition['username'] = trim($username);
        $definition['password_hash'] = $passwordHash;
        $accounts[$key] = $definition;
    }

    return $accounts;
}

/**
 * Tente d'authentifier un utilisateur
 */
function authenticate_user($username, $password) {
    $accounts = get_system_accounts();
    $cleanUser = strtolower(trim($username));

    foreach ($accounts as $acc) {
        $matchesUsername = strtolower($acc['username']) === $cleanUser;
        $matchesPassword = isset($acc['password_hash']) && is_string($acc['password_hash'])
            && password_verify($password, $acc['password_hash']);
        if ($matchesUsername && $matchesPassword) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = $acc['id'];
            $_SESSION['username'] = $acc['username'];
            $_SESSION['role'] = $acc['role'];
            $_SESSION['user_name'] = $acc['name'];
            $_SESSION['admin_logged_in'] = ($acc['role'] === 'admin');
            $_SESSION['login_time'] = time();
            return $acc;
        }
    }
    return false;
}

/**
 * Retourne le rôle courant
 */
function get_current_role() {
    if (isset($_SESSION['role']) && is_string($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return 'admin';
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (!is_string($authHeader) || preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $matches) !== 1) {
        return 'public';
    }
    $providedToken = $matches[1];
    $adminToken = getenv('GREENHUMBLE_ADMIN_API_TOKEN');
    $pipelineToken = getenv('GREENHUMBLE_PIPELINE_API_TOKEN');

    if (is_string($adminToken) && $adminToken !== '' && hash_equals($adminToken, $providedToken)) {
        return 'admin';
    }
    if (is_string($pipelineToken) && $pipelineToken !== '' && hash_equals($pipelineToken, $providedToken)) {
        return 'pipeline';
    }
    return 'public';
}

function generate_csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function validate_csrf_token($token) {
    if (!is_string($token)) {
        return false;
    }
    $expected = $_SESSION['_csrf_token'] ?? null;
    return is_string($expected) && hash_equals($expected, trim($token));
}

function extract_request_csrf_token() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    }
    if ($token === null) {
        $rawInput = file_get_contents('php://input');
        if (is_string($rawInput) && $rawInput !== '') {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $token = $decoded['_csrf_token'] ?? $decoded['csrf_token'] ?? null;
            }
        }
    }
    return is_string($token) ? trim($token) : null;
}

/**
 * Bloque les mutations de session admin déclenchées depuis une autre origine.
 * Les requêtes POST admin du navigateur doivent envoyer Origin ou Referer same-origin.
 * Les mutations sans Same-Origin doivent aussi fournir un token CSRF valide.
 */
function gh_require_same_origin_mutation() {
    $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        return true;
    }

    $expectedHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $expectedHost = preg_replace('/:\d+$/', '', $expectedHost) ?: $expectedHost;

    $source = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($source === '' && !empty($_SERVER['HTTP_REFERER'])) {
        $parts = parse_url((string)$_SERVER['HTTP_REFERER']);
        if (is_array($parts) && !empty($parts['host'])) {
            $scheme = $parts['scheme'] ?? 'https';
            $source = $scheme . '://' . $parts['host'];
            if (!empty($parts['port'])) {
                $source .= ':' . $parts['port'];
            }
        }
    }

    $sourceHost = '';
    if ($source !== '') {
        $parts = parse_url($source);
        if (is_array($parts) && !empty($parts['host'])) {
            $sourceHost = strtolower((string)$parts['host']);
            if (!empty($parts['port'])) {
                $sourceHost .= ':' . (string)$parts['port'];
            }
        }
    }

    $csrfToken = extract_request_csrf_token();
    $hasValidCsrf = $csrfToken !== null && validate_csrf_token($csrfToken);

    if ($sourceHost !== '' && $sourceHost !== $expectedHost) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Origine de requête refusée.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($sourceHost === '' && !$hasValidCsrf) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF absent ou invalide.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return true;
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_logged_in() {
    return get_current_role() !== 'public';
}

/**
 * Exige un rôle spécifique pour les pages HTML
 */
function require_login($required_role = 'admin') {
    $role = get_current_role();
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $next = (strpos($requestUri, '/api/') === 0 || strpos($requestUri, '/admin/') === 0) ? $requestUri : '';
    $loginUrl = '/api/login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : '');
    if ($role === 'public') {
        header('Location: ' . $loginUrl, true, 303);
        exit;
    }
    if ($required_role === 'admin' && $role !== 'admin') {
        header('Location: ' . $loginUrl, true, 303);
        exit;
    }
}

/**
 * Exige un rôle spécifique pour les API JSON
 */
function require_api_role($allowed_roles = ['admin']) {
    $role = get_current_role();
    if (!in_array($role, (array)$allowed_roles)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Accès refusé. Rôle insuffisant pour exécuter cette action.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
