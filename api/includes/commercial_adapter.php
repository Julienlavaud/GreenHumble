<?php
declare(strict_types=1);

require_once __DIR__ . '/commercial_engine.php';
require_once __DIR__ . '/commercial_dossier.php';

/**
 * Prépare une recommandation commerciale uniquement à partir d'un audit explicitement complet.
 * Les signaux doivent être fournis par une étape de revue distincte : cet adaptateur ne les invente pas.
 * Aucun stockage, envoi, scraping ou accès réseau.
 */
function gh_prepare_commercial_result(array $audit, array $lead, bool $signalsReviewed = false): array
{
    if (($audit['analysisComplete'] ?? false) !== true
        || !isset($audit['riskScore'])
        || !is_numeric($audit['riskScore'])) {
        return [
            'status' => 'blocked_incomplete_audit',
            'qualification' => null,
            'dossier' => null,
            'reason' => 'Audit absent, incomplet ou sans score validé.',
            'send_enabled' => false,
            'persistence_enabled' => false,
        ];
    }

    if (!$signalsReviewed) {
        return [
            'status' => 'blocked_signals_not_reviewed',
            'qualification' => null,
            'dossier' => null,
            'reason' => 'Les signaux commerciaux doivent être vérifiés explicitement avant qualification.',
            'send_enabled' => false,
            'persistence_enabled' => false,
        ];
    }

    $qualification = gh_recommend_services($lead);
    $dossier = gh_build_sales_dossier($lead, $qualification);

    return [
        'status' => $qualification['qualification_status'],
        'qualification' => $qualification,
        'dossier' => $dossier,
        'reason' => null,
        'send_enabled' => false,
        'persistence_enabled' => false,
    ];
}
