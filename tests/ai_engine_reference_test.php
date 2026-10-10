<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/includes/audit_response.php';
function ref_assert(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "ÉCHEC : {$message}\n"); exit(1); }
    echo "OK : {$message}\n";
}
$path = __DIR__ . '/fixtures/ai-engine-reference.json';
$data = json_decode((string)file_get_contents($path), true);
ref_assert(is_array($data) && count($data['cases'] ?? []) >= 3, 'le jeu de référence couvre trois types de cas');
$results = [];
foreach ($data['cases'] as $case) {
    $audit = gh_validate_ia_response(['score' => $case['score'], 'claims' => $case['claims']], 'example.org', $data['sourceText'], 'https://example.org/');
    $results[$case['kind']] = $audit;
    ref_assert(count($audit['detectedClaims']) === $case['expectedClaims'], $case['name'] . ' : nombre d’allégations attendu');
    ref_assert($audit['riskLevel'] === $case['expectedRiskLevel'], $case['name'] . ' : cohérence entre score et niveau de risque');
    if (isset($case['expectedEvidenceStatus'])) {
        ref_assert($audit['detectedClaims'][0]['evidenceStatus'] === $case['expectedEvidenceStatus'], $case['name'] . ' : la preuve manquante est signalée');
        ref_assert($audit['detectedClaims'][0]['severity'] === 'a_verifier', $case['name'] . ' : le niveau ne présente pas une suspicion comme infraction');
    }
    $expected = array_map('strtolower', $case['expectedTerms'] ?? []);
    $found = array_map(static function ($claim) { return strtolower((string)($claim['term'] ?? '')); }, $audit['detectedClaims']);
    $truePositive = count(array_intersect($found, $expected));
    $falsePositive = count(array_diff($found, $expected));
    $missed = count(array_diff($expected, $found));
    ref_assert($falsePositive === ($case['expectedFalsePositives'] ?? 0), $case['name'] . ' : taux de faux positifs de référence');
    ref_assert($missed === ($case['expectedMisses'] ?? 0), $case['name'] . ' : taux d’oublis de référence');
    if ($falsePositive > 0) {
        foreach ($audit['detectedClaims'] as $claim) {
            if (!in_array(strtolower((string)($claim['term'] ?? '')), $expected, true)) {
                ref_assert($claim['evidenceStatus'] === 'evidence_missing', $case['name'] . ' : le faux positif est signalé comme non vérifié');
            }
        }
    }
}
ref_assert($results['substantiated']['detectedClaims'][0]['evidenceVerified'] === true, 'citation exacte vérifiée');
ref_assert($results['substantiated']['detectedClaims'][0]['sourceUrl'] === 'https://example.org/', 'citation rattachée à sa page source');
ref_assert($results['substantiated']['detectedClaims'][1]['sourceUrl'] === 'https://example.org/engagements', 'citation multi-page rattachée à la bonne URL');
$scoreA = gh_validate_ia_response(['score' => 3, 'claims' => [['term' => 'écologique']]], 'example.org')['riskScore'];
$scoreB = gh_validate_ia_response(['score' => 98, 'claims' => [['term' => 'écologique']]], 'example.org')['riskScore'];
ref_assert($scoreA === $scoreB, 'le score libre du fournisseur est ignoré');
$bad = gh_validate_ia_response(['claims' => [['term' => 'allégation']]], 'example.org');
ref_assert($bad['analysisComplete'] === false && $bad['riskScore'] === null, 'absence de score = analyse incomplète');

$catalog = [[
    'serviceKey' => 'acv-audit', 'serviceTitle' => 'Analyse de cycle de vie',
    'description' => 'Mesurer les impacts', 'defaultBudget' => '1 200 €', 'isActive' => true
]];
$commercialSource = '[SOURCE_URL: https://example.org/] Nos produits sont neutres en carbone.';
$commercial = gh_validate_ia_response([
    'score' => 40,
    'claims' => [[
        'term' => 'neutres en carbone',
        'quoteInText' => 'Nos produits sont neutres en carbone.',
        'riskRationale' => 'Allégation absolue sans preuve publique de périmètre.'
    ]],
    'prospection' => [
        'priority' => 'high',
        'recommendedOffer' => ['serviceKey' => 'acv-audit', 'serviceTitle' => 'Analyse de cycle de vie', 'budget' => '100 €'],
        'evidenceBasedReasons' => [[
            'claimTerm' => 'neutres en carbone',
            'quoteInText' => 'Nos produits sont neutres en carbone.',
            'reason' => 'Revue des justificatifs de l’allégation.'
        ]]
    ]
], 'example.org', $commercialSource, 'https://example.org/', $catalog);
ref_assert($commercial['commercialProspectingKit']['priority'] === 'haute', 'priorité IA normalisée');
ref_assert($commercial['commercialProspectingKit']['recommendedOffer']['serviceTitle'] === 'Analyse de cycle de vie', 'offre limitée au catalogue actif');
ref_assert($commercial['commercialProspectingKit']['recommendedOffer']['budget'] === '1 200 €', 'budget repris du catalogue plutôt que du texte IA');
ref_assert(count($commercial['commercialProspectingKit']['evidenceBasedReasons']) === 1, 'argumentaire lié à une citation vérifiée');
$inventedOffer = gh_validate_ia_response([
    'score' => 40,
    'claims' => [],
    'prospection' => ['recommendedOffer' => ['serviceKey' => 'inconnu', 'serviceTitle' => 'Offre inventée']]
], 'example.org', '', '', $catalog);
ref_assert($inventedOffer['commercialProspectingKit']['recommendedOffer'] === null, 'offre hors catalogue rejetée');
echo "Tous les tests de référence du moteur IA sont réussis.\n";
