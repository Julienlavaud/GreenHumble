<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/commercial_adapter.php';

function adapter_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

$completeAudit = ['analysisComplete' => true, 'riskScore' => 20];
$lead = [
    'company_name' => 'Entreprise de test',
    'website' => 'https://example.org',
    'company_size' => 'tpe',
    'evidence' => ['src-1' => [
        'url' => 'https://example.org/engagements',
        'title' => 'Engagements',
        'excerpt' => 'Information publique à vérifier.',
    ]],
    'signals' => [[
        'type' => 'environmental_claim',
        'verified' => true,
        'evidence_id' => 'src-1',
    ]],
];

$incomplete = gh_prepare_commercial_result(['analysisComplete' => false, 'riskScore' => null], $lead, true);
adapter_assert($incomplete['status'] === 'blocked_incomplete_audit', 'bloque un audit incomplet');
adapter_assert($incomplete['qualification'] === null && $incomplete['dossier'] === null, 'ne produit aucun dossier si audit incomplet');

$unreviewed = gh_prepare_commercial_result($completeAudit, $lead, false);
adapter_assert($unreviewed['status'] === 'blocked_signals_not_reviewed', 'bloque les signaux non revus');

$ready = gh_prepare_commercial_result($completeAudit, $lead, true);
adapter_assert($ready['qualification']['recommendations'][0]['service_id'] === 'revue_allegations', 'recommande une prestation à partir du signal vérifié');
adapter_assert($ready['dossier']['email_draft']['status'] === 'draft_requires_human_review', 'génère un brouillon soumis à validation');
adapter_assert($ready['send_enabled'] === false && $ready['persistence_enabled'] === false, 'désactive envoi et persistance');

echo "OK : 6 contrôles de l’adaptateur commercial réussis.\n";
