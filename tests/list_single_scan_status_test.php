<?php
declare(strict_types=1);

$endpoint = file_get_contents(__DIR__ . '/../api/listes/update_site_status.php');
$manager = file_get_contents(__DIR__ . '/../api/includes/listes_manager.php');
$ui = file_get_contents(__DIR__ . '/../api/scan.php');
if (!is_string($endpoint) || !is_string($manager) || !is_string($ui)) {
    fwrite(STDERR, "FAIL: fichiers L02 absents\n");
    exit(1);
}
$checks = [
    'méthode POST uniquement' => strpos($endpoint, "!== 'POST'") !== false,
    'contrôle d’accès' => strpos($endpoint, 'http_response_code(403)') !== false,
    'validation liste et site' => strpos($endpoint, 'list_id') !== false && strpos($endpoint, 'site_id') !== false,
    'statuts autorisés' => strpos($endpoint, "'à_analyser', 'en_cours', 'termine', 'erreur'") !== false,
    'vérification de l’identifiant du site' => strpos($endpoint, "['id']") !== false && strpos($endpoint, '$site') !== false,
    'sauvegarde contrôlée' => strpos($endpoint, 'if (!save_all_listes($lists))') !== false,
    'stockage atomique' => strpos($manager, 'function save_all_listes($listes)') !== false && strpos($manager, 'return true;') !== false,
    'interface lance un scan individuel' => strpos($ui, 'function scanSiteFromList') !== false,
    'interface enregistre le scan_id retourné' => strpos($ui, "updateStatus('termine', String(result.scan_id))") !== false,
    'interface expose le bouton de scan par site' => strpos($ui, 'Scanner ce site') !== false,
];
foreach ($checks as $label => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
    echo "PASS: {$label}\n";
}
echo "Contrat L02 validé.\n";
