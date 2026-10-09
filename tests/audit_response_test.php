<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/includes/audit_response.php';

function response_assert(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "ÉCHEC : {$message}\n"); exit(1); }
    echo "OK : {$message}\n";
}

$valid = gh_validate_ia_response(['score' => 72, 'claims' => []], 'example.org');
response_assert($valid['analysisComplete'] === true, 'une réponse valide porte le marqueur explicite de complétude');
response_assert($valid['riskScore'] === 72, 'le score numérique est normalisé');
response_assert($valid['commercialProspectingKit'] === null, 'aucun kit commercial ni prix n’est inventé par défaut');

$explicitKit = gh_validate_ia_response([
    'riskScore' => 60,
    'commercialProspectingKit' => ['recommendedPackage' => 'À valider']
], 'example.org');
response_assert(is_array($explicitKit['commercialProspectingKit']), 'un kit explicitement fourni est conservé');

foreach ([
    ['status' => 'analysis_incomplete', 'score' => 80],
    ['analysisComplete' => false, 'score' => 80],
    ['claims' => []],
    ['score' => 'pas un score']
] as $bad) {
    $result = gh_validate_ia_response($bad, 'example.org');
    response_assert($result['analysisComplete'] === false, 'une réponse invalide ou incomplète est bloquée');
    response_assert($result['riskScore'] === null, 'aucun score n’est retenu pour une réponse invalide');
    response_assert($result['commercialProspectingKit'] === null, 'aucun kit commercial n’est fourni pour une réponse invalide');
}
echo "Tous les tests de validation d’audit sont réussis.\n";
