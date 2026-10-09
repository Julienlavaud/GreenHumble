<?php
declare(strict_types=1);

function check(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
$state = file_get_contents(__DIR__ . '/../api/includes/app_state.php');
$config = file_get_contents(__DIR__ . '/../api/config/config.php');
$db = file_get_contents(__DIR__ . '/../api/includes/db.php');
$auth = file_get_contents(__DIR__ . '/../api/includes/auth.php');
$save = file_get_contents(__DIR__ . '/../api/admin/save_state.php');
$admin = file_get_contents(__DIR__ . '/../api/admin/index.php');
$workspace = file_get_contents(__DIR__ . '/../api/collaborateur/index.php');
$provision = file_get_contents(__DIR__ . '/../api/admin/set_collaborator_password.php');
$adminUi = file_get_contents(__DIR__ . '/../api/admin/index.php');

check(!str_contains($state, '"collab_1" => "Climatlocal31000"'), 'No plaintext default collaborator password');
check(!str_contains($state, '"collab_2" => "collab2026"'), 'No shared default collaborator password');
check(str_contains($state, 'mkdir($dir, 0750, true)'), 'Private data directory uses restrictive permissions');
check(str_contains($config, "define('DB_USER', '')") && !str_contains($config, "define('DB_USER', 'root')"), 'Database config fails closed instead of defaulting to root');
check(!str_contains($db, 'htmlspecialchars($e->getMessage())'), 'Database exception details are not rendered to users');
check(str_contains($auth, 'function gh_require_same_origin_mutation()'), 'Same-origin mutation guard exists');
check(str_contains($save, 'gh_require_same_origin_mutation();'), 'Admin state writes use same-origin guard');
check(str_contains($admin, 'gh_require_same_origin_mutation();'), 'Admin database writes use same-origin guard');
check(str_contains($workspace, 'Derniers audits'), 'Collaborator cockpit exposes recent audits');
check(str_contains($workspace, 'Signaux à vérifier'), 'Collaborator cockpit shows signals for human review');
check(str_contains($provision, 'get_current_role() !== \'admin\''), 'Collaborator password provisioning is admin-only');
check(str_contains($provision, 'password_hash($password, PASSWORD_DEFAULT)'), 'Collaborator password is stored as a hash');
check(str_contains($adminUi, 'setCollaboratorPassword'), 'Admin team UI provides secure password setup/reset');

echo "OK: OVH security and collaborator cockpit contract\n";
