<?php
declare(strict_types=1);

/** Validation isolée pour les tests de non-régression. */
function gh_validate_ia_response($parsed, string $domain): array {
    // Fail closed : une réponse absente/incomplète ne doit jamais devenir un score artificiel.
    $hasScore = is_array($parsed) &&
        (array_key_exists('riskScore', $parsed) || array_key_exists('score', $parsed));
    $rawScore = is_array($parsed) ? ($parsed['riskScore'] ?? ($parsed['score'] ?? null)) : null;
    $isIncomplete = !is_array($parsed) ||
        empty($parsed) ||
        (($parsed['status'] ?? '') === 'analysis_incomplete') ||
        (($parsed['analysisComplete'] ?? true) === false) ||
        !$hasScore ||
        !is_numeric($rawScore);

    if ($isIncomplete) {
        $claims = is_array($parsed['detectedClaims'] ?? null)
            ? $parsed['detectedClaims']
            : (is_array($parsed['claims'] ?? null) ? $parsed['claims'] : []);
        $summary = is_array($parsed) && is_string($parsed['summary'] ?? null)
            ? $parsed['summary']
            : "Analyse incomplète : aucune conclusion fiable n’a pu être produite.";
        return array_merge(is_array($parsed) ? $parsed : [], [
            'status' => 'analysis_incomplete',
            'analysisComplete' => false,
            'riskScore' => null,
            'riskLevel' => 'a_evaluer',
            'detectedClaims' => $claims,
            'clientSummary' => [
                'headline' => "Analyse incomplète : {$domain}",
                'riskVerdict' => $summary,
                'keyUrgentAction' => "Relancer l’analyse après vérification de la configuration du fournisseur IA.",
                'immediateRisks' => [],
                'stepsToCompliance' => []
            ],
            'commercialProspectingKit' => null
        ]);
    }

    $score = intval($rawScore);
    $score = max(0, min(100, $score));

    $riskLevel = 'eleve';
    if ($score >= 75) $riskLevel = 'faible';
    elseif ($score >= 55) $riskLevel = 'modere';
    elseif ($score >= 35) $riskLevel = 'eleve';
    else $riskLevel = 'critique';

    $claims = $parsed['detectedClaims'] ?? ($parsed['claims'] ?? []);
    if (!is_array($claims)) $claims = [];

    // Ne jamais fabriquer de conclusions juridiques ou de recommandations non fournies par l'analyse.
    $clientSummary = is_array($parsed['clientSummary'] ?? null)
        ? $parsed['clientSummary']
        : [
            'headline' => "Résultat d'analyse à examiner : {$domain}",
            'riskVerdict' => "Score calculé : {$score}/100. Ce score ne constitue pas, à lui seul, une conclusion juridique.",
            'keyUrgentAction' => "Vérifier les allégations et leurs justificatifs avant toute conclusion.",
            'immediateRisks' => [],
            'stepsToCompliance' => []
        ];

    // Le kit commercial doit provenir explicitement de la réponse validée ou du catalogue approuvé.
    // Aucun prix, subside, forfait ou argumentaire juridique n'est inventé ici.
    $commercialProspectingKit = $parsed['commercialProspectingKit'] ?? ($parsed['prospection'] ?? null);
    if (!is_array($commercialProspectingKit)) {
        $commercialProspectingKit = null;
    }

    return array_merge($parsed, [
        'analysisComplete' => true,
        'riskScore' => $score,
        'riskLevel' => $riskLevel,
        'detectedClaims' => $claims,
        'clientSummary' => $clientSummary,
        'commercialProspectingKit' => $commercialProspectingKit
    ]);
}
