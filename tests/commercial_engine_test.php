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
    $default['recommendations'] === [] && $default['qualification_status'] === 'no_suitable_offer',
    'Sans besoin documenté, le moteur ne force aucune offre'
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
    $unsupported['recommendations'] === [],
    'Un signal sans preuve correspondante ne déclenche aucune offre'
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

$linked = gh_recommend_services([
 'signals'=>[['type'=>'environmental_claim','verified'=>true,'evidence_id'=>'src','summary'=>'Allégation publique']],
 'evidence'=>['src'=>['url'=>'https://example.org/claim','quote'=>'Neutre en carbone']],
 'company_profile'=>['revenue'=>['value'=>120000,'source_type'=>'registry','source_url'=>'https://registry.example/record'],'employees'=>['value'=>8,'source_type'=>'estimate']]
]);
$first=$linked['recommendations'][0]??[];
gh_test_assert(($first['reasons'][0]['evidence_id']??null)==='src' && ($first['reasons'][0]['source_url']??null)==='https://example.org/claim','La recommandation est reliée au constat et à sa source');
gh_test_assert(($first['qualification']['confidence']??0)>=80 && isset($first['qualification']['urgency']),'Besoin, urgence et confiance sont qualifiés séparément');
gh_test_assert(($linked['company_profile']['revenue']['provenance']??null)==='registry' && ($linked['company_profile']['employees']['is_estimate']??false)===true,'Chiffre d’affaires sourcé et effectifs estimés sont distingués');
$disabled=gh_recommend_services([
 'service_catalog'=>['revue_allegations'=>['label'=>'Revue','active'=>false,'authorized'=>true]],
 'signals'=>[['type'=>'environmental_claim','verified'=>true,'evidence_id'=>'src']],
 'evidence'=>['src'=>['url'=>'https://example.org/claim']]
]);
gh_test_assert($disabled['recommendations']===[],'Un service inactif ne peut pas être recommandé');
