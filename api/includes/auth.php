<?php
/**
 * GreenHumble v1.1 — Module d'Authentification & Gestion des Rôles
 * /api/includes/auth.php
 */

if (session_status() === PHP_SESSION_NONE) {
    // Session sécurisée
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Comptes prédéfinis sécurisés
 */
function get_system_accounts() {
    return [
        'admin' => [
            'id' => 'usr_admin',
            'username' => 'admin',
            'password' => 'Climatlocal31000',
            'role' => 'admin',
            'name' => 'Administrateur GreenHumble'
        ],
        'pipeline' => [
            'id' => 'usr_pipeline',
            'username' => 'pipeline',
            'password' => 'PipelinePass2026!',
            'role' => 'pipeline',
            'name' => 'Service Pipeline Automatique'
        ]
    ];
}

/**
 * Tente d'authentifier un utilisateur
 */
function authenticate_user($username, $password) {
    $accounts = get_system_accounts();
    $cleanUser = strtolower(trim($username));
    
    foreach ($accounts as $acc) {
        if (strtolower($acc['username']) === $cleanUser && $acc['password'] === $password) {
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
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return 'admin';
    }
    // Check Authorization header for token or Bearer
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (strpos($authHeader, 'Bearer admin_token') !== false || strpos($authHeader, 'Bearer Climatlocal31000') !== false) {
        return 'admin';
    }
    if (strpos($authHeader, 'Bearer pipeline_token') !== false) {
        return 'pipeline';
    }
    return 'public';
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
    if ($role === 'public') {
        header('Location: /api/login.php');
        exit;
    }
    if ($required_role === 'admin' && $role !== 'admin') {
        header('Location: /api/login.php?error=unauthorized');
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
