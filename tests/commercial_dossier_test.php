<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/commercial_dossier.php';

function check(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
}

$lead = [
    'company_name' => 'Atelier Exemple',
    'website' => 'https://example.com',
    'evidence' => [
        'ev-1' => [
            'title' => 'Page produit',
            'url' => 'https://example.com/produit',
            'excerpt' => 'Information publique à vérifier.',
        ],
        'ev-invalid' => [
            'title' => 'URL non sûre',
            'url' => 'javascript:alert(1)',
            'excerpt' => 'Ne doit pas être conservée.',
        ],
    ],
];
$qualification = [
    'qualification_status' => 'qualified_for_review',
    'recommendations' => [[
        'service_id' => 'revue_allegations',
        'label' => 'Revue des allégations environnementales',
        'requires_human_review' => true,
    ]],
];

$dossier = gh_build_sales_dossier($lead, $qualification);
check($dossier['recommended_service']['service_id'] === 'revue_allegations', 'reprend le service prioritaire');
check(count($dossier['evidence_sources']) === 1, 'filtre les URL non HTTP(S)');
check($dossier['email_draft']['status'] === 'draft_requires_human_review', 'brouillon soumis à validation');
check($dossier['send_enabled'] === false, 'aucun envoi automatique');
check($dossier['persistence_enabled'] === false, 'aucune écriture en base');
check($dossier['pricing'] === null, 'aucun prix inventé');
check(str_contains($dossier['email_draft']['body'], 'ne s\'agit pas d\'une conclusion'), 'évite les accusations non étayées');

$empty = gh_build_sales_dossier([], []);
check($empty['recommended_service'] === null, 'fonctionne sans recommandation');
check($empty['qualification_status'] === 'insufficient_evidence', 'statut prudent par défaut');

echo "OK : 9 contrôles du dossier commercial réussis.\n";
