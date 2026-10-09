<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/green_claims_rules.php';

function gh_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
}

$result = gh_detect_green_claim_signals('Notre produit est neutre en carbone et 100% écologique.');
gh_assert($result['status'] === 'signals_detected', 'des termes de risque connus doivent être détectés');
gh_assert(count($result['findings']) >= 2, 'les familles carbone et absolu doivent être séparées');
gh_assert($result['findings'][0]['legal_conclusion'] === null, 'aucune infraction ne doit être conclue automatiquement');
gh_assert($result['findings'][0]['status'] === 'to_review', 'les signaux doivent rester à examiner');
gh_assert(count($result['findings'][0]['evidence_to_request']) > 0, 'une action de preuve doit être proposée');
gh_assert(count($result['findings'][0]['potential_references']) > 0, 'les références juridiques candidates doivent être listées');

$negative = gh_detect_green_claim_signals('Notre entreprise ouvre à 9 h et propose un service client.');
gh_assert($negative['status'] === 'no_dictionary_signal', 'un texte neutre ne doit pas déclencher le dictionnaire');
gh_assert(strpos($negative['disclaimer'], 'ne garantit pas la conformité') !== false, 'l’absence de signal ne doit pas être présentée comme une garantie');

$accent = gh_detect_green_claim_signals('Une offre éco-responsable avec matériaux naturels.');
gh_assert($accent['status'] === 'signals_detected', 'la normalisation des accents doit fonctionner');

echo "OK — pré-détection, actions correctives, références candidates et garde-fous juridiques.\n";
