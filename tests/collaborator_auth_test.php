<?php
require_once __DIR__ . '/../api/includes/collaborator_auth.php';

function check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : " . $message . PHP_EOL);
        exit(1);
    }
}

$hash = password_hash('mot-de-passe-test', PASSWORD_DEFAULT);
check(gh_verify_stored_password('mot-de-passe-test', $hash), 'un hash valide doit être accepté');
check(!gh_verify_stored_password('mauvais-mot-de-passe', $hash), 'un mauvais mot de passe doit être refusé');
check(gh_verify_stored_password('ancien-test', 'ancien-test'), 'compatibilité temporaire avec les anciens mots de passe');
check(!gh_verify_stored_password('autre-test', 'ancien-test'), 'un ancien mot de passe incorrect doit être refusé');
check(!gh_verify_stored_password('', $hash), 'un mot de passe vide doit être refusé');
check(!gh_verify_stored_password('test', ''), 'un secret stocké vide doit être refusé');

echo "OK — vérifications du mot de passe collaborateur" . PHP_EOL;
