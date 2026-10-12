<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/company_data_provenance.php';

function company_provenance_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

$payload = [
    'companyRegistry' => [
        'siren' => '123456789',
        'headcountSource' => 'registre_public_insee',
        'headcountYear' => '2024',
        'turnoverSource' => 'saisie_utilisateur',
        'sectorSource' => 'estimation_non_verifiee',
    ],
    'entreprise_salaries' => '10 à 19 salariés',
];
$audit = ['raw_response' => json_encode($payload, JSON_UNESCAPED_UNICODE)];
$decoded = gh_company_raw_audit_payload($audit);
company_provenance_assert(($decoded['companyRegistry']['siren'] ?? null) === '123456789', 'la provenance est relue depuis le JSON persistant');
company_provenance_assert(gh_company_provenance_label($decoded['companyRegistry']['headcountSource'], true) === 'Registre public (INSEE)', 'la source INSEE est libellée explicitement');
company_provenance_assert(gh_company_provenance_label($decoded['companyRegistry']['turnoverSource'], true) === 'Saisie utilisateur', 'une saisie est distinguée d’une donnée publique');
company_provenance_assert(gh_company_provenance_label($decoded['companyRegistry']['sectorSource'], true) === 'Estimation non vérifiée', 'une estimation reste signalée comme non vérifiée');
company_provenance_assert(gh_company_provenance_label('non_communique', false) === 'Non communiqué', 'une donnée absente n’est pas inventée');
company_provenance_assert(gh_company_provenance_label(null, true) === 'Source non renseignée pour cet audit', 'une donnée historique sans source n’est pas présentée comme officielle');

$report = (string)file_get_contents(__DIR__ . '/../api/audit-report.php');
$analysis = (string)file_get_contents(__DIR__ . '/../api/analyse.php');
$pipeline = (string)file_get_contents(__DIR__ . '/../api/pipeline.php');
foreach (['company-sector-source', 'company-headcount-source', 'company-turnover-source', 'company-siren-source', 'company-naf-source'] as $id) {
    company_provenance_assert(strpos($report, 'id="' . $id . '"') !== false, 'le rapport expose la provenance ' . $id);
}
company_provenance_assert(strpos($analysis, 'gh_company_provenance_label') !== false, 'le profil historique affiche les sources');
company_provenance_assert(strpos($pipeline, 'gh_company_provenance_label') !== false, 'le pipeline affiche les sources');
company_provenance_assert(strpos($analysis, ": '25 salariés'") === false && strpos($analysis, ": '4.5 M€'") === false, 'les valeurs fictives effectif/CA ont disparu');

echo "Tests de provenance des données d’entreprise réussis.\n";
