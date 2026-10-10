<?php
declare(strict_types=1);

/**
 * Méthode GH-RISK-1.0
 * Observed risk (0–100) mesure la sévérité du signal étayé, jamais la probabilité d'une infraction.
 * Preuve absente réduit la confiance; elle n'ajoute aucun point de risque.
 */
function gh_calculate_risk_assessment(array $claims): array {
    $items = [];
    foreach ($claims as $claim) {
        if (!is_array($claim)) continue;
        $term = strtolower(trim((string)($claim['term'] ?? '')));
        if ($term === '') continue;
        $quoteFound = !empty($claim['evidenceVerified']);
        $status = (string)($claim['evidenceStatus'] ?? 'evidence_missing');
        $missing = !$quoteFound || $status === 'evidence_missing';
        $rationale = trim((string)($claim['riskRationale'] ?? ''));
        $uncertain = $missing || $status === 'to_review' || $rationale === '' || ($claim['severity'] ?? '') === 'a_verifier';

        $nature = 8;
        if (preg_match('/(neutre|neutralit|z[eé]ro carbone|100\s*%|sans impact|totalement|garanti)/u', $term)) $nature = 24;
        elseif (preg_match('/(durable|[eé]cologique|responsable|naturel|vert|biod[eé]gradable)/u', $term)) $nature = 16;
        $precision = preg_match('/(100\s*%|z[eé]ro|sans |totalement|garanti|toujours|jamais)/u', $term) ? 20 : (mb_strlen($term) > 24 ? 12 : 6);
        $quote = strtolower((string)($claim['quoteInText'] ?? ''));
        $reach = preg_match('/(tous nos|toute la gamme|tous les produits|l’ensemble de|100\s*%)/u', $quote) ? 18 : 8;
        $evidence = $missing ? 0 : ($status === 'substantiated' ? 0 : 14);
        $points = min(100, $nature + $precision + $reach + $evidence);
        $confidence = $missing ? 20 : ($uncertain ? 50 : 80);
        if ($rationale !== '') $confidence = min(95, $confidence + 10);
        $items[] = ['term' => $claim['term'], 'riskPoints' => $points, 'confidence' => $confidence,
            'factors' => ['nature' => $nature, 'precision' => $precision, 'scope' => $reach, 'evidence' => $evidence],
            'evidenceStatus' => $status, 'uncertainty' => $uncertain];
    }
    if (!$items) return ['method'=>'GH-RISK-1.0','riskScore'=>null,'riskLevel'=>'a_evaluer','confidence'=>0,'evidenceStrength'=>0,'reasons'=>[],'limitations'=>['Aucun signal vérifié à scorer; cela ne prouve pas l’absence de risque.']];
    $sum = array_sum(array_column($items, 'riskPoints'));
    $risk = (int)round($sum / count($items));
    $confidence = (int)round(array_sum(array_column($items, 'confidence')) / count($items));
    $evidenceStrength = (int)round(count(array_filter($items, static fn($i)=>$i['evidenceStatus']!=='evidence_missing')) / count($items) * 100);
    $level = $risk >= 70 ? 'eleve' : ($risk >= 45 ? 'modere' : 'faible');
    return ['method'=>'GH-RISK-1.0','riskScore'=>$risk,'riskLevel'=>$level,'confidence'=>$confidence,
        'evidenceStrength'=>$evidenceStrength,'reasons'=>$items,
        'limitations'=>['Score indicatif de signaux observés; ni probabilité ni constat d’infraction.','L’absence de citation ou de justificatif réduit la confiance, pas la présomption de conformité ou de non-conformité.']];
}
