<?php
declare(strict_types=1);

/** Normalise les preuves sans transformer une suspicion en infraction établie. */
function gh_normalize_claims($claims, string $sourceText = '', string $sourceUrl = ''): array {
    if (!is_array($claims)) return [];
    $normalise = static function (string $value): string {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };
    $source = $normalise($sourceText);
    $result = [];
    $seen = [];
    foreach ($claims as $claim) {
        if (!is_array($claim)) continue;
        $term = trim((string)($claim['term'] ?? $claim['claim'] ?? $claim['allegation'] ?? ''));
        $quote = trim((string)($claim['quoteInText'] ?? $claim['quote'] ?? $claim['evidence'] ?? ''));
        if ($term === '' && $quote === '') continue;
        $quoteFound = $quote !== '' && $source !== '' && strpos($source, $normalise($quote)) !== false;
        $key = $normalise($term) . '|' . $normalise($quote);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $claim['term'] = $term !== '' ? $term : $quote;
        $claim['quoteInText'] = $quote;
        $claim['sourceUrl'] = $sourceUrl;
        if ($quoteFound) {
            $claim['evidenceStatus'] = in_array(($claim['evidenceStatus'] ?? ''), ['substantiated', 'signal', 'to_review'], true)
                ? $claim['evidenceStatus'] : 'signal';
            $claim['evidenceVerified'] = true;
            $claim['evidenceMissing'] = [];
        } else {
            $claim['evidenceStatus'] = 'evidence_missing';
            $claim['evidenceVerified'] = false;
            $claim['evidenceMissing'] = array_values(array_filter([
                $quote === '' ? 'Citation exacte absente.' : 'Citation non retrouvée dans le contenu récupéré.',
                $sourceText === '' ? 'Contenu source inaccessible ou non fourni.' : null
            ]));
            $claim['requiredProof'] = trim((string)($claim['requiredProof'] ?? '')) ?: 'Fournir une citation vérifiable et le justificatif correspondant.';
            $claim['severity'] = 'a_verifier';
        }
        // Une réponse IA ne suffit pas à établir une infraction.
        if (!isset($claim['legalConclusion'])) $claim['legalConclusion'] = 'À vérifier';
        $result[] = $claim;
    }
    return $result;
}

/** Validation isolée pour les tests de non-régression. */
function gh_validate_ia_response($parsed, string $domain, string $sourceText = '', string $sourceUrl = ''): array {
    $hasScore = is_array($parsed) && (array_key_exists('riskScore', $parsed) || array_key_exists('score', $parsed));
    $rawScore = is_array($parsed) ? ($parsed['riskScore'] ?? ($parsed['score'] ?? null)) : null;
    $isIncomplete = !is_array($parsed) || empty($parsed) ||
        (($parsed['status'] ?? '') === 'analysis_incomplete') ||
        (($parsed['analysisComplete'] ?? true) === false) || !$hasScore || !is_numeric($rawScore);

    $rawClaims = is_array($parsed)
        ? (is_array($parsed['detectedClaims'] ?? null) ? $parsed['detectedClaims'] : ($parsed['claims'] ?? []))
        : [];
    $claims = gh_normalize_claims($rawClaims, $sourceText, $sourceUrl);

    if ($isIncomplete) {
        $summary = is_array($parsed) && is_string($parsed['summary'] ?? null)
            ? $parsed['summary']
            : "Analyse incomplète : aucune conclusion fiable n’a pu être produite.";
        return array_merge(is_array($parsed) ? $parsed : [], [
            'status' => 'analysis_incomplete', 'analysisComplete' => false,
            'riskScore' => null, 'riskLevel' => 'a_evaluer', 'detectedClaims' => $claims,
            'clientSummary' => [
                'headline' => "Analyse incomplète : {$domain}", 'riskVerdict' => $summary,
                'keyUrgentAction' => "Relancer l’analyse après vérification de la configuration du fournisseur IA.",
                'immediateRisks' => [], 'stepsToCompliance' => []
            ],
            'commercialProspectingKit' => null
        ]);
    }

    $score = max(0, min(100, (int)round((float)$rawScore)));
    if ($score >= 75) $riskLevel = 'faible';
    elseif ($score >= 55) $riskLevel = 'modere';
    elseif ($score >= 35) $riskLevel = 'eleve';
    else $riskLevel = 'critique';

    $clientSummary = is_array($parsed['clientSummary'] ?? null) ? $parsed['clientSummary'] : [
        'headline' => "Résultat d'analyse à examiner : {$domain}",
        'riskVerdict' => "Score calculé : {$score}/100. Ce score ne constitue pas, à lui seul, une conclusion juridique.",
        'keyUrgentAction' => "Vérifier les allégations et leurs justificatifs avant toute conclusion.",
        'immediateRisks' => [], 'stepsToCompliance' => []
    ];
    $kit = $parsed['commercialProspectingKit'] ?? ($parsed['prospection'] ?? null);
    if (!is_array($kit)) $kit = null;

    return array_merge($parsed, [
        'analysisComplete' => true, 'riskScore' => $score, 'riskLevel' => $riskLevel,
        'detectedClaims' => $claims, 'clientSummary' => $clientSummary,
        'commercialProspectingKit' => $kit
    ]);
}
