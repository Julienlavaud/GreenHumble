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
        if ($quoteFound && strpos($sourceText, '[SOURCE_URL: ') !== false) {
            $sourcePages = explode('[SOURCE_URL: ', $sourceText);
            foreach ($sourcePages as $sourcePage) {
                $markerEnd = strpos($sourcePage, ']');
                if ($markerEnd === false) continue;
                $pageUrl = trim(substr($sourcePage, 0, $markerEnd));
                $pageText = substr($sourcePage, $markerEnd + 1);
                if (strpos($normalise($pageText), $normalise($quote)) !== false) {
                    $claim['sourceUrl'] = $pageUrl;
                    break;
                }
            }
        }
        if ($quoteFound) {
            // La citation vérifie la présence de l'allégation, pas son bien-fondé.
            $claim['evidenceStatus'] = in_array(($claim['evidenceStatus'] ?? ''), ['signal', 'to_review'], true)
                ? $claim['evidenceStatus'] : 'signal';
            $claim['evidenceVerified'] = true;
            $claim['evidenceMissing'] = ['Justificatif de l’allégation non vérifié par la seule page citée.'];
            $severity = strtolower((string)($claim['severity'] ?? ''));
            $allowedSeverity = ['faible', 'modere', 'modéré', 'eleve', 'élevé', 'critique', 'a_verifier', 'à vérifier', 'signal'];
            if (!in_array($severity, $allowedSeverity, true) || trim((string)($claim['riskRationale'] ?? '')) === '') {
                $claim['severity'] = 'a_verifier';
                $claim['evidenceStatus'] = 'to_review';
                if (trim((string)($claim['riskRationale'] ?? '')) === '') {
                    $claim['evidenceMissing'][] = 'Justification du niveau de risque absente.';
                }
            }
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
        // Le scan documente un signal ; il ne peut pas établir à lui seul une infraction.
        $claim['legalConclusion'] = 'À vérifier — aucune infraction n’est établie par le seul scan.';
        $claim['nonComplianceEstablished'] = false;
        $result[] = $claim;
    }
    return $result;
}

function gh_normalize_commercial_kit($kit, array $claims, int $score, array $serviceCatalog = []): ?array {
    if (!is_array($kit)) return null;
    $priorityRaw = strtolower(trim((string)($kit['priority'] ?? $kit['priorite'] ?? $kit['priorité'] ?? '')));
    $priorityMap = ['high' => 'haute', 'urgent' => 'haute', 'haute' => 'haute', 'medium' => 'moyenne', 'moderate' => 'moyenne', 'moyenne' => 'moyenne', 'low' => 'basse', 'basse' => 'basse'];
    $priority = $priorityMap[$priorityRaw] ?? ($score < 55 ? 'haute' : ($score < 75 ? 'moyenne' : 'basse'));
    $kit['priority'] = $priority;
    $kit['priorityRationale'] = trim((string)($kit['priorityRationale'] ?? '')) ?: 'Priorité indicative calculée à partir du score de risque ; à confirmer par un consultant.';
    $kit['nextAction'] = trim((string)($kit['nextAction'] ?? $kit['recommandation'] ?? '')) ?: 'Faire vérifier les signaux et les justificatifs avant toute prise de contact.';

    $candidate = $kit['recommendedOffer'] ?? $kit['offer'] ?? ($kit['climatLocalProposal']['recommendedPackage'] ?? null);
    $candidateKey = is_array($candidate) ? trim((string)($candidate['serviceKey'] ?? $candidate['id'] ?? '')) : '';
    $candidateTitle = is_array($candidate)
        ? trim((string)($candidate['serviceTitle'] ?? $candidate['title'] ?? $candidate['name'] ?? $candidate['recommendedPackage'] ?? ''))
        : trim((string)$candidate);
    $approved = null;
    foreach ($serviceCatalog as $service) {
        if (!is_array($service) || !($service['isActive'] ?? true)) continue;
        $key = trim((string)($service['serviceKey'] ?? $service['id'] ?? ''));
        $title = trim((string)($service['serviceTitle'] ?? ''));
        $keyMatch = $candidateKey !== '' && $key !== '' && hash_equals($key, $candidateKey);
        $titleMatch = $candidateTitle !== '' && strtolower($title) === strtolower($candidateTitle);
        if (!$keyMatch && !$titleMatch) continue;
        $approved = [
            'serviceKey' => $key,
            'serviceTitle' => $title,
            'reason' => is_array($candidate) ? trim((string)($candidate['reason'] ?? '')) : trim((string)($kit['offerRationale'] ?? '')),
        ];
        if (trim((string)($service['defaultBudget'] ?? '')) !== '') $approved['budget'] = (string)$service['defaultBudget'];
        break;
    }
    $kit['recommendedOffer'] = $approved;
    if (isset($kit['climatLocalProposal']) && is_array($kit['climatLocalProposal'])) {
        $kit['climatLocalProposal']['recommendedPackage'] = $approved['serviceTitle'] ?? null;
        if ($approved === null) unset($kit['climatLocalProposal']['pricingEstimate']);
        elseif (isset($approved['budget'])) $kit['climatLocalProposal']['pricingEstimate'] = $approved['budget'];
    }

    $verifiedClaims = [];
    foreach ($claims as $claim) {
        if (!is_array($claim) || empty($claim['evidenceVerified'])) continue;
        $verifiedClaims[strtolower(trim((string)($claim['term'] ?? '')))] = $claim;
    }
    $reasons = [];
    foreach (($kit['evidenceBasedReasons'] ?? []) as $reason) {
        if (!is_array($reason)) continue;
        $term = strtolower(trim((string)($reason['claimTerm'] ?? $reason['term'] ?? '')));
        if ($term === '' || !isset($verifiedClaims[$term])) continue;
        $quote = trim((string)($reason['quoteInText'] ?? ''));
        $verifiedQuote = trim((string)($verifiedClaims[$term]['quoteInText'] ?? ''));
        if ($quote !== '' && strtolower($quote) !== strtolower($verifiedQuote)) continue;
        $reason['claimTerm'] = $verifiedClaims[$term]['term'];
        $reason['quoteInText'] = $verifiedQuote;
        $reasons[] = $reason;
    }
    $kit['evidenceBasedReasons'] = $reasons;
    return $kit;
}

/** Validation isolée pour les tests de non-régression. */
function gh_validate_ia_response($parsed, string $domain, string $sourceText = '', string $sourceUrl = '', array $serviceCatalog = []): array {
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
    $kit = gh_normalize_commercial_kit($kit, $claims, $score, $serviceCatalog);

    return array_merge($parsed, [
        'analysisComplete' => true, 'riskScore' => $score, 'riskLevel' => $riskLevel,
        'detectedClaims' => $claims, 'clientSummary' => $clientSummary,
        'commercialProspectingKit' => $kit
    ]);
}
