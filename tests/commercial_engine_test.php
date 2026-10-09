<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/commercial_engine.php';

function gh_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

$default = gh_recommend_services(['company_size' => 'tpe']);
gh_test_assert(
    ($default['recommendations'][0]['service_id'] ?? null) === 'diagnostic_flash',
    'Sans preuve spécialisée, la TPE reçoit un diagnostic initial'
);

$claim = gh_recommend_services([
    'company_size' => 'tpe',
    'evidence' => ['src-1' => ['url' => 'https://example.org/page', 'quote' => 'Extrait de test']],
    'signals' => [[
        'type' => 'environmental_claim',
        'verified' => true,
        'evidence_id' => 'src-1',
    ]],
]);
gh_test_assert(
    ($claim['recommendations'][0]['service_id'] ?? null) === 'revue_allegations',
    'Une allégation liée à une preuve priorise la revue des allégations'
);
gh_test_assert(
    ($claim['auto_send'] ?? true) === false
        && array_key_exists('pricing', $claim)
        && $claim['pricing'] === null,
    'Aucun envoi automatique et aucun prix inventé'
);

$unsupported = gh_recommend_services([
    'company_size' => 'tpe',
    'signals' => [[
        'type' => 'environmental_claim',
        'verified' => true,
        'evidence_id' => 'preuve-inexistante',
    ]],
]);
gh_test_assert(
    ($unsupported['recommendations'][0]['service_id'] ?? null) === 'diagnostic_flash',
    'Un signal sans preuve correspondante ne déclenche pas une offre spécialisée'
);

$carbon = gh_recommend_services([
    'evidence' => ['src-carbon' => ['url' => 'https://example.org/carbone']],
    'signals' => [[
        'type' => 'carbon_request',
        'verified' => true,
        'evidence_id' => 'src-carbon',
    ]],
]);
gh_test_assert(
    ($carbon['recommendations'][0]['service_id'] ?? null) === 'pre_diagnostic_carbone',
    'Un besoin carbone étayé priorise le pré-diagnostic carbone'
);
