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
$previous = 101;
foreach ([100, 75, 55, 35, 0] as $score) {
    $audit = gh_validate_ia_response(['score' => $score, 'claims' => []], 'example.org');
    ref_assert($audit['riskScore'] <= $previous, 'normalisation monotone des scores');
    $previous = $audit['riskScore'];
}
$bad = gh_validate_ia_response(['claims' => [['term' => 'allégation']]], 'example.org');
ref_assert($bad['analysisComplete'] === false && $bad['riskScore'] === null, 'absence de score = analyse incomplète');
echo "Tous les tests de référence du moteur IA sont réussis.\n";
