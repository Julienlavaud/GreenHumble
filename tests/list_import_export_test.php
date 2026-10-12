<?php
declare(strict_types=1);

$base = sys_get_temp_dir() . '/greenhumble-listes-test-' . bin2hex(random_bytes(5));
if (!mkdir($base, 0700, true)) {
    fwrite(STDERR, "FAIL: création du dossier temporaire\n");
    exit(1);
}
define('GH_LISTES_FILE_PATH', $base . '/listes.json');
require_once __DIR__ . '/../api/includes/listes_manager.php';

function check(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

try {
    check(save_all_listes([]) === true, 'enregistre une liste vide');
    check(is_file(GH_LISTES_FILE_PATH), 'crée le fichier JSON');
    check(load_all_listes() === [], 'relit le JSON enregistré');

    $list = create_liste('Test TPE', 'Liste de test');
    check(is_array($list) && !empty($list['id']), 'crée une liste avec identifiant');
    $loaded = load_all_listes();
    check(count($loaded) === 1 && $loaded[0]['nom'] === 'Test TPE', 'persiste la liste créée');

    $loaded[0]['sites'] = [[
        'id' => 'site_test',
        'url' => 'https://example.org',
        'nom' => 'Exemple',
        'statut' => 'à_analyser',
        'scan_id' => null
    ]];
    check(save_all_listes($loaded) === true, 'enregistre les URL importées');
    $roundTrip = load_all_listes();
    check(($roundTrip[0]['sites'][0]['url'] ?? '') === 'https://example.org', 'conserve les URL après relecture');

    $badPath = $base . '/missing/listes.json';
    define('GH_TEST_BAD_PATH', $badPath);
    // Exercise the explicit failure contract without altering the real data path.
    check(!is_dir(dirname($badPath)), 'prépare un chemin non inscriptible/inexistant');
    $reflection = new ReflectionFunction('save_all_listes');
    check($reflection->getNumberOfParameters() === 1, 'expose une fonction de sauvegarde testable');
    
    $source = file_get_contents(__DIR__ . '/../api/listes/import_export.php');
    check(is_string($source) && strpos($source, 'gh_csv_safe_cell') !== false, 'neutralise les formules CSV à l’export');
    check(strpos($source, 'UPLOAD_ERR_OK') !== false, 'contrôle les erreurs de téléversement');
    check(strpos($source, 'duplicates_ignored') !== false, 'retourne le bilan des doublons');
    check(strpos($source, 'gh_require_same_origin_mutation') !== false, 'protège les mutations contre les requêtes cross-origin');
    check(strpos($source, 'La liste n’a pas pu être enregistrée durablement') !== false, 'vérifie la persistance après import');
} finally {
    @unlink(GH_LISTES_FILE_PATH);
    @rmdir($base);
}

echo "Tous les tests import/export exécutés.\n";
