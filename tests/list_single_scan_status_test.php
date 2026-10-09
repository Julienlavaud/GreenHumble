<?php
declare(strict_types=1);

$endpoint = file_get_contents(__DIR__ . '/../api/listes/update_site_status.php');
$manager = file_get_contents(__DIR__ . '/../api/includes/listes_manager.php');
if (!is_string($endpoint) || !is_string($manager)) {
    fwrite(STDERR, "FAIL: fichiers L02 absents\n");
    exit(1);
}
$checks = [
    'méthode POST uniquement' => strpos($endpoint, "!== 'POST'") !== false,
    'authentification avant mutation' => strpos($endpoint, "http_response_code(403)") !== false,
    'validation liste et site' => strpos($endpoint, "list_id") !== false && strpos($endpoint, "site_id") !== false,
    'statuts bornés à une liste autorisée' => strpos($endpoint, "'à_analyser', 'en_cours', 'termine', 'erreur'") !== false,
    'vérification d’appartenance du site à la liste' => strpos($endpoint, "($site['id'] ?? '')") !== false,
    'sauvegarde contrôlée' => strpos($endpoint, "if (!save_all_listes($lists))") !== false,
    'stockage atomique retourne un booléen' => strpos($manager, 'function save_all_listes($listes)') !== false && strpos($manager, 'return true;') !== false,
];
foreach ($checks as $label => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
    echo "PASS: {$label}\n";
}
echo "Contrat L02 validé.\n";
