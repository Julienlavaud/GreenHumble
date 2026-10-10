<?php
declare(strict_types=1);

/**
 * Qualification commerciale pure : aucune collecte, écriture ou prise de contact.
 * Les constats techniques restent séparés des besoins et recommandations commerciales.
 */
function gh_recommend_services(array $lead): array
{
    $defaults = [
        'diagnostic_flash' => ['label'=>'Diagnostic environnemental initial','purpose'=>'Clarifier les priorités et les informations manquantes.','allowed'=>true],
        'revue_allegations' => ['label'=>'Revue des allégations environnementales','purpose'=>'Examiner formulation, portée et justificatifs.','allowed'=>true],
        'structuration_rse' => ['label'=>'Structuration RSE pragmatique','purpose'=>'Organiser actions, responsabilités et preuves.','allowed'=>true],
        'pre_diagnostic_carbone' => ['label'=>'Pré-diagnostic carbone et environnement','purpose'=>'Qualifier le périmètre et les données disponibles.','allowed'=>true],
    ];
    $configured = is_array($lead['service_catalog'] ?? null) ? $lead['service_catalog'] : null;
    $catalog = [];
    foreach (($configured ?? $defaults) as $id => $service) {
        if (!is_array($service) || ($service['active'] ?? true) !== true || ($service['authorized'] ?? $service['allowed'] ?? true) !== true) continue;
        $catalog[(string)$id] = [
            'label' => trim((string)($service['label'] ?? '')),
            'purpose' => trim((string)($service['purpose'] ?? '')),
        ];
        if ($catalog[(string)$id]['label'] === '') unset($catalog[(string)$id]);
    }

    $signals = is_array($lead['signals'] ?? null) ? $lead['signals'] : [];
    $evidence = is_array($lead['evidence'] ?? null) ? $lead['evidence'] : [];
    $technicalFindings = [];
    $verified = [];
    foreach ($signals as $signal) {
        if (!is_array($signal) || ($signal['verified'] ?? false) !== true) continue;
        $id = trim((string)($signal['evidence_id'] ?? ''));
        $source = $evidence[$id] ?? null;
        if ($id === '' || !is_array($source)) continue;
        $url = trim((string)($source['url'] ?? ''));
        $quote = trim((string)($source['quote'] ?? $source['excerpt'] ?? ''));
        if ((!preg_match('~^https?://~i', $url)) && $quote === '') continue;
        $type = trim((string)($signal['type'] ?? ''));
        if ($type === '') continue;
        $finding = [
            'finding_id' => trim((string)($signal['finding_id'] ?? $id)),
            'type' => $type,
            'summary' => trim((string)($signal['summary'] ?? $signal['term'] ?? $type)),
            'evidence_id' => $id,
            'source_url' => $url !== '' ? $url : null,
            'quote' => $quote !== '' ? $quote : null,
        ];
        $technicalFindings[] = $finding;
        $verified[$type][] = $finding;
    }

    $mapping = [
        'environmental_claim' => ['revue_allegations', 5, 'Vérifier les justificatifs et le périmètre de l’allégation.'],
        'rse_request' => ['structuration_rse', 4, 'Cadrer les attentes, les échéances et les preuves disponibles.'],
        'customer_requirements' => ['structuration_rse', 4, 'Clarifier les exigences clients et leurs échéances.'],
        'carbon_request' => ['pre_diagnostic_carbone', 5, 'Qualifier périmètre, données et échéance du besoin carbone.'],
        'carbon_data' => ['pre_diagnostic_carbone', 4, 'Vérifier la couverture et la qualité des données carbone.'],
        'general_environmental_need' => ['diagnostic_flash', 3, 'Préciser le sujet prioritaire avant de proposer une prestation.'],
    ];
    $qualification = [];
    foreach ($mapping as $type => [$serviceId, $relevance, $action]) {
        if (empty($verified[$type]) || !isset($catalog[$serviceId])) continue;
        $urgency = 0;
        foreach ($verified[$type] as $finding) {
            $signal = $signals[array_search($finding['evidence_id'], array_column($signals, 'evidence_id'), true)] ?? [];
            if (!empty($signal['deadline']) || !empty($signal['urgent'])) $urgency = 2;
        }
        $confidence = 70;
        if (count(array_filter($verified[$type], static fn($f) => $f['quote'] !== null)) > 0) $confidence = 85;
        $qualification[$serviceId] = [
            'need' => $type,
            'urgency' => $urgency,
            'relevance' => $relevance,
            'confidence' => $confidence,
            'action' => $action,
            'findings' => $verified[$type],
        ];
    }

    $recommendations = [];
    foreach ($qualification as $serviceId => $item) {
        $recommendations[] = [
            'service_id' => $serviceId,
            'label' => $catalog[$serviceId]['label'],
            'purpose' => $catalog[$serviceId]['purpose'],
            'qualification' => ['need'=>$item['need'],'urgency'=>$item['urgency'],'relevance'=>$item['relevance'],'confidence'=>$item['confidence']],
            'next_step' => $item['action'],
            'reasons' => array_map(static fn($f) => ['finding_id'=>$f['finding_id'],'evidence_id'=>$f['evidence_id'],'summary'=>$f['summary'],'source_url'=>$f['source_url'],'quote'=>$f['quote']], $item['findings']),
            'requires_human_review' => true,
        ];
    }
    usort($recommendations, static function ($a, $b) {
        $aScore = $a['qualification']['urgency'] * 10 + $a['qualification']['relevance'];
        $bScore = $b['qualification']['urgency'] * 10 + $b['qualification']['relevance'];
        return $bScore <=> $aScore;
    });

    $profile = [];
    foreach (['revenue'=>'chiffre_affaires','employees'=>'effectifs','sector'=>'secteur'] as $key => $label) {
        $field = $lead['company_profile'][$key] ?? null;
        if (!is_array($field)) {
            $profile[$key] = ['label'=>$label,'value'=>null,'provenance'=>'unknown','is_estimate'=>false];
            continue;
        }
        $sourceType = (string)($field['source_type'] ?? 'unknown');
        $profile[$key] = [
            'label'=>$label,
            'value'=>$field['value'] ?? null,
            'provenance'=>in_array($sourceType,['registry','company_provided','public_source','estimate'],true)?$sourceType:'unknown',
            'source_url'=>filter_var($field['source_url'] ?? '', FILTER_VALIDATE_URL) ? $field['source_url'] : null,
            'is_estimate'=>$sourceType==='estimate',
        ];
    }
    $size = strtolower(trim((string)($lead['company_size'] ?? 'unknown')));
    return [
        'engine_version'=>'1.0.0',
        'qualification_status'=>$recommendations ? 'qualified_for_review' : 'no_suitable_offer',
        'company_name'=>trim((string)($lead['company_name'] ?? '')),
        'website'=>trim((string)($lead['website'] ?? '')),
        'company_size'=>$size,
        'technical_findings'=>$technicalFindings,
        'company_profile'=>$profile,
        'recommendations'=>$recommendations,
        'evidence_count'=>count($technicalFindings),
        'sales_message_draft'=>null,
        'pricing'=>null,
        'auto_send'=>false,
        'disclaimer'=>'La qualification commerciale est distincte des constats techniques. Toute proposition requiert une validation humaine.',
    ];
}
