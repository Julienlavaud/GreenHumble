<?php
/**
 * Authentification des comptes gérés dans l'espace équipe.
 * Les anciens mots de passe sont hachés après une connexion valide.
 */
require_once __DIR__ . '/app_state.php';

function gh_is_password_hash($stored) {
    if (!is_string($stored) || $stored === '') return false;
    return preg_match('/^\\$(?:2[ayb]|argon2(?:id|i))\\$/', $stored) === 1;
}

function gh_verify_stored_password($provided, $stored) {
    if (!is_string($provided) || !is_string($stored) || $provided === '' || $stored === '') return false;
    return gh_is_password_hash($stored) ? password_verify($provided, $stored) : hash_equals($stored, $provided);
}

function gh_authenticate_collaborator($identifier, $password) {
    if (!is_string($identifier) || !is_string($password) || trim($identifier) === '' || $password === '') return false;
    $identifier = function_exists('mb_strtolower') ? mb_strtolower(trim($identifier), 'UTF-8') : strtolower(trim($identifier));
    $state = load_app_state();

    foreach (($state['collaborators'] ?? []) as $collaborator) {
        $email = trim((string) ($collaborator['email'] ?? ''));
        $id = (string) ($collaborator['id'] ?? '');
        if ($email === '' || $id === '') continue;
        $normalizedEmail = function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
        if ($normalizedEmail !== $identifier) continue;

        $stored = $state['passwordsMap'][$id] ?? null;
        if (!is_string($stored) || $stored === '') return false;
        $isHash = preg_match('/^\\$2[ayb]\\$|^\\$argon2(id|i)\\$/', $stored) === 1;
        $valid = gh_verify_stored_password($password, $stored);
        if (!$valid) return false;

        if (!$isHash) {
            $state['passwordsMap'][$id] = password_hash($password, PASSWORD_DEFAULT);
            save_app_state($state);
        }

        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $email;
        $_SESSION['role'] = 'collaborator';
        $_SESSION['user_name'] = (string) ($collaborator['name'] ?? 'Collaborateur');
        $_SESSION['admin_logged_in'] = false;
        $_SESSION['login_time'] = time();

        return [
            'id' => $id,
            'username' => $email,
            'role' => 'collaborator',
            'name' => (string) ($collaborator['name'] ?? 'Collaborateur')
        ];
    }
    return false;
}
