<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/audit_response.php';
require_once __DIR__ . '/../api/includes/scans_manager.php';

function scan_storage_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'greenhumble-scan-test-' . bin2hex(random_bytes(6));
if (!mkdir($directory, 0700, true)) {
    fwrite(STDERR, "ÉCHEC : impossible de créer le dossier temporaire\n");
    exit(1);
}
$primary = $directory . DIRECTORY_SEPARATOR . 'scans.json';
$legacy = $directory . DIRECTORY_SEPARATOR . 'legacy-scans.json';

try {
    $sourceText = 'Nos produits sont neutres en carbone.';
    $audit = gh_validate_ia_response([
        'score' => 10,
        'claims' => [[
            'term' => 'neutres en carbone',
            'quoteInText' => $sourceText,
            'severity' => 'eleve',
            'riskRationale' => 'Allégation environnementale absolue à justifier.'
        ]]
    ], 'exemple.fr', $sourceText, 'https://exemple.fr/engagements');
    scan_storage_assert($audit['analysisComplete'] === true && is_numeric($audit['riskScore']), 'le résultat de référence passe la validation et le scoring');

    $audit['companyRegistry'] = [
        'provider' => 'recherche-entreprises.api.gouv.fr',
        'siren' => '123456789',
        'sectorSource' => 'registre_public_insee',
        'headcountSource' => 'registre_public_insee',
        'headcountYear' => '2024',
        'turnoverSource' => 'non_communique',
        'turnoverPeriod' => null,
    ];
    $scan = [
        'id' => 'scan-integration-1',
        'url' => 'https://exemple.fr',
        'company_name' => 'Exemple SAS',
        'company_siren' => '123456789',
        'entreprise_salaries' => '10 à 19 salariés',
        'entreprise_ca' => null,
        'resultat_ia' => [
            'score' => $audit['riskScore'],
            'riskLevel' => $audit['riskLevel'],
            'companyRegistry' => $audit['companyRegistry'],
        ],
    ];

    file_put_contents($primary, json_encode([['id' => 'scan-existing', 'score' => 35]], JSON_UNESCAPED_UNICODE));
    file_put_contents($legacy, json_encode([
        ['id' => 'scan-existing', 'score' => 99],
        ['id' => 'scan-legacy', 'score' => 20],
    ], JSON_UNESCAPED_UNICODE));

    scan_storage_assert(append_scan_to_json_path($primary, [$primary, $legacy], $scan), 'le scan est écrit à travers le chemin de persistance atomique');
    $stored = read_scans_from_paths([$primary], true);
    scan_storage_assert(is_array($stored) && count($stored) === 3, 'l’historique canonique et l’ancien emplacement sont fusionnés sans doublon');
    scan_storage_assert(($stored[0]['resultat_ia']['companyRegistry']['headcountSource'] ?? null) === 'registre_public_insee', 'la provenance entreprise survit à la persistance');
    scan_storage_assert(($stored[0]['resultat_ia']['riskLevel'] ?? null) === $audit['riskLevel'], 'le niveau de risque validé survit à la persistance');

    $mutationSaved = mutate_all_scans(static function (array $scans): array {
        foreach ($scans as &$item) {
            if (($item['id'] ?? '') === 'scan-legacy') $item['reviewed'] = true;
        }
        unset($item);
        return $scans;
    }, [$primary, $legacy]);
    $afterUpdate = read_scans_from_paths([$primary], true);
    $updatedLegacyScan = array_values(array_filter($afterUpdate ?: [], static fn($item) => ($item['id'] ?? '') === 'scan-legacy'));
    scan_storage_assert($mutationSaved, 'la mutation atomique met à jour l’historique sous verrou');
    scan_storage_assert(
        !empty($updatedLegacyScan[0]['reviewed']),
        'la mutation atomique synchronise les données du chemin historique'
    );

    scan_storage_assert(
        append_scan_to_json_path($primary, [$primary, $legacy], ['id' => 'scan-parallel-add', 'url' => 'https://nouveau.fr']),
        'un nouveau scan peut être ajouté après une mutation atomique'
    );
    $deletedDuringMutation = 0;
    $deleteSaved = mutate_all_scans(static function (array $scans) use (&$deletedDuringMutation): array {
        return array_values(array_filter($scans, static function ($item) use (&$deletedDuringMutation): bool {
            if (($item['id'] ?? '') === 'scan-existing') {
                $deletedDuringMutation++;
                return false;
            }
            return true;
        }));
    }, [$primary, $legacy]);
    $afterMutation = read_scans_from_paths([$primary, $legacy], true);
    scan_storage_assert($deleteSaved && $deletedDuringMutation === 1, 'la suppression atomique ne cible que les scans demandés');
    scan_storage_assert(
        is_array($afterMutation)
            && !in_array('scan-existing', array_column($afterMutation, 'id'), true)
            && in_array('scan-parallel-add', array_column($afterMutation, 'id'), true),
        'une suppression conserve les scans ajoutés pendant des opérations voisines et synchronise les anciennes copies'
    );

    $beforeRollbackPrimary = (string)file_get_contents($primary);
    $beforeRollbackLegacy = (string)file_get_contents($legacy);
    $rollbackSaved = mutate_all_scans(
        static fn(array $scans): array => array_values(array_filter($scans, static fn($item) => ($item['id'] ?? '') !== 'scan-legacy')),
        [$primary, $legacy],
        static function (string $temporary, string $path) use ($legacy): bool {
            return $path === $legacy ? false : rename($temporary, $path);
        }
    );
    scan_storage_assert(!$rollbackSaved, 'une erreur simulée lors de l’écriture historique échoue proprement');
    scan_storage_assert(
        (string)file_get_contents($primary) === $beforeRollbackPrimary
            && (string)file_get_contents($legacy) === $beforeRollbackLegacy,
        'une écriture canonique est annulée si une copie secondaire échoue'
    );

    $beforeCorruptWrite = (string)file_get_contents($primary);
    file_put_contents($legacy, '{json invalide');
    scan_storage_assert(!append_scan_to_json_path($primary, [$primary, $legacy], ['id' => 'must-not-write']), 'un historique invalide bloque une nouvelle écriture');
    scan_storage_assert((string)file_get_contents($primary) === $beforeCorruptWrite, 'un historique invalide ne corrompt pas le fichier canonique');

    $invalidScoreRejected = false;
    try {
        record_scan('https://exemple.fr', 'diagnostic', ['score' => 101]);
    } catch (InvalidArgumentException $e) {
        $invalidScoreRejected = true;
    }
    scan_storage_assert($invalidScoreRejected, 'un score hors plage est refusé avant toute écriture');

    echo "Tests d’intégration du stockage des scans réussis.\n";
} finally {
    foreach ([$primary, $legacy, $primary . '.lock', $legacy . '.lock'] as $file) {
        if (is_file($file)) unlink($file);
    }
    rmdir($directory);
}
