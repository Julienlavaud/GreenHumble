<?php
declare(strict_types=1);

function check(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    echo "OK: {$message}\n";
}

$api = __DIR__ . '/../api';
$header = (string)file_get_contents($api . '/includes/header.php');
check(str_contains($header, 'name="csrf-token"') && str_contains($header, 'X-CSRF-Token'), 'header.php envoie le jeton CSRF avec chaque fetch same-origin non GET');

foreach (['groups/create', 'groups/update', 'groups/delete', 'groups/assign_scanner', 'scans/update', 'scans/delete', 'scans/assign_group', 'dossiers/create', 'dossiers/add_scan', 'listes/create', 'listes/add_scan'] as $endpoint) {
    check(str_contains((string)file_get_contents("$api/$endpoint.php"), 'gh_api_guard(true)'), "$endpoint exige une session autorisée et le jeton CSRF");
}
foreach (['groups/list', 'scans/list', 'scans/by_group', 'dossiers/list', 'listes/list', 'export/full', 'export/raw', 'export/structure'] as $endpoint) {
    check(str_contains((string)file_get_contents("$api/$endpoint.php"), 'gh_api_guard(false)'), "$endpoint exige une session autorisée");
}

require_once $api . '/includes/scans_manager.php';
check(function_exists('save_all_scans'), 'save_all_scans() existe (modification/suppression de scans)');

$admin = (string)file_get_contents($api . '/admin/index.php');
check(!str_contains($admin, '/admin/analyse.php?domain='), 'Dossiers : le lien ouvre le dernier audit par identifiant, pas par domaine');

echo "OK: contrat des boutons et endpoints JSON\n";
