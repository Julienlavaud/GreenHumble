<?php
putenv('GREENHUMBLE_ADMIN_USERNAME=admin-ci');
putenv('GREENHUMBLE_ADMIN_PASSWORD_HASH=' . password_hash('ci-password', PASSWORD_DEFAULT));
putenv('GREENHUMBLE_PIPELINE_USERNAME');
putenv('GREENHUMBLE_PIPELINE_PASSWORD_HASH');

require_once __DIR__ . '/../api/includes/auth.php';

function auth_check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : " . $message . PHP_EOL);
        exit(1);
    }
}

$accounts = get_system_accounts();
auth_check(isset($accounts['admin']), 'un compte admin est chargé si configuré');
auth_check(!isset($accounts['pipeline']), 'un compte sans configuration ne doit pas être activé');
auth_check(password_verify('ci-password', $accounts['admin']['password_hash']), 'le hash configuré est conservé');
auth_check(!isset($accounts['admin']['password']), 'aucun mot de passe en clair ne doit être retourné');
echo "OK — comptes système configurés par environnement" . PHP_EOL;
