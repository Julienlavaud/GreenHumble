<?php
declare(strict_types=1);

/**
 * GreenHumble — qualification commerciale TPE (prototype isolé).
 * Entrée structurée issue d'une analyse revue. Aucun scraping, e-mail,
 * prix ou écriture en base dans cette fonction.
 */
function gh_recommend_services(array $lead): array
{
    $catalog = [
        'diagnostic_flash' => [
            'label' => 'Diagnostic environnemental initial',
            'purpose' => 'Prioriser les actions et clarifier la situation de départ.',
            'trigger' => 'Besoin général, maturité inconnue ou preuves insuffisantes.',
            'next_step' => 'Proposer un échange de cadrage court.',
        ],
        'revue_allegations' => [
            'label' => 'Revue des allégations environnementales',
            'purpose' => 'Examiner la précision, la portée et les justificatifs des allégations.',
            'trigger' => 'Allégation environnementale publique et source disponible.',
            'next_step' => 'Demander les justificatifs avant toute conclusion.',
        ],
        'structuration_rse' => [
            'label' => 'Structuration RSE pragmatique',
            'purpose' => 'Organiser actions, responsabilités, indicateurs et preuves.',
            'trigger' => 'Exigences clients/RSE ou démarche à structurer, documentées.',
            'next_step' => 'Proposer un atelier de cadrage des priorités.',
        ],
        'pre_diagnostic_carbone' => [
            'label' => 'Pré-diagnostic carbone et environnement',
            'purpose' => 'Cartographier les données disponibles et préparer la suite.',
            'trigger' => 'Besoin carbone explicite ou données carbone disponibles.',
            'next_step' => 'Vérifier le périmètre, les données et les compétences avant devis.',
        ],
    ];

    $signals = is_array($lead['signals'] ?? null) ? $lead['signals'] : [];
    $evidence = is_array($lead['evidence'] ?? null) ? $lead['evidence'] : [];
    $size = strtolower(trim((string)($lead['company_size'] ?? 'unknown')));
    $scores = array_fill_keys(array_keys($catalog), 0);
    $reasons = [];
    $verified = [];

    foreach ($signals as $signal) {
        if (!is_array($signal)) continue;
        $type = (string)($signal['type'] ?? '');
        $evidenceId = (string)($signal['evidence_id'] ?? '');
        if (($signal['verified'] ?? false) === true
            && $evidenceId !== ''
            && isset($evidence[$evidenceId])
            && is_array($evidence[$evidenceId])) {
            $verified[$type] = $signal;
        }
    }

    if (isset($verified['environmental_claim'])) {
        $scores['revue_allegations'] += 5;
        $reasons['revue_allegations'][] = 'Allégation publique associée à une source référencée.';
    }
    if (isset($verified['rse_request']) || isset($verified['customer_requirements'])) {
        $scores['structuration_rse'] += 4;
        $reasons['structuration_rse'][] = 'Besoin RSE ou exigences clients documentés.';
    }
    if (isset($verified['carbon_request']) || isset($verified['carbon_data'])) {
        $scores['pre_diagnostic_carbone'] += 5;
        $reasons['pre_diagnostic_carbone'][] = 'Besoin carbone explicite ou données carbone signalés.';
    }
    if (isset($verified['general_environmental_need'])) {
        $scores['diagnostic_flash'] += 4;
        $reasons['diagnostic_flash'][] = 'Besoin environnemental général documenté.';
    }
    if (in_array($size, ['tpe', 'micro', 'small', 'petite_entreprise'], true)) {
        $scores['diagnostic_flash'] += 2;
        $reasons['diagnostic_flash'][] = 'Point d’entrée proportionné à une petite structure.';
    }

    $specializedScore = $scores['revue_allegations']
        + $scores['structuration_rse'] + $scores['pre_diagnostic_carbone'];
    if ($specializedScore === 0) {
        $scores['diagnostic_flash'] += 3;
        $reasons['diagnostic_flash'][] = 'Aucun besoin spécialisé suffisamment étayé à ce stade.';
    }

    arsort($scores);
    $recommendations = [];
    foreach ($scores as $id => $score) {
        if ($score <= 0) continue;
        $recommendations[] = [
            'service_id' => $id,
            'label' => $catalog[$id]['label'],
            'score' => min(100, $score * 10),
            'purpose' => $catalog[$id]['purpose'],
            'trigger' => $catalog[$id]['trigger'],
            'next_step' => $catalog[$id]['next_step'],
            'reasons' => $reasons[$id] ?? [],
            'requires_human_review' => true,
        ];
    }

    return [
        'engine_version' => '0.1.0',
        'qualification_status' => count($recommendations) ? 'qualified_for_review' : 'insufficient_evidence',
        'company_name' => trim((string)($lead['company_name'] ?? '')),
        'website' => trim((string)($lead['website'] ?? '')),
        'company_size' => $size,
        'recommendations' => $recommendations,
        'evidence_count' => count($evidence),
        'sales_message_draft' => null,
        'pricing' => null,
        'auto_send' => false,
        'disclaimer' => 'Recommandations indicatives fondées sur les signaux vérifiés fournis. Validation humaine requise.',
    ];
}
