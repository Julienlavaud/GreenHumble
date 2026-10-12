<?php
declare(strict_types=1);

/**
 * GreenHumble — préparation d'un dossier commercial à valider.
 * Produit uniquement un brouillon. Ne contacte personne et ne persiste aucune donnée.
 */
function gh_build_sales_dossier(array $lead, array $qualification): array
{
    $company = trim((string)($lead['company_name'] ?? ''));
    $company = $company !== '' ? $company : 'votre entreprise';
    $website = trim((string)($lead['website'] ?? ''));
    $recommendations = is_array($qualification['recommendations'] ?? null)
        ? $qualification['recommendations'] : [];
    $evidence = is_array($lead['evidence'] ?? null) ? $lead['evidence'] : [];

    $top = null;
    foreach ($recommendations as $recommendation) {
        if (is_array($recommendation)
            && !empty($recommendation['service_id'])
            && !empty($recommendation['label'])) {
            $top = $recommendation;
            break;
        }
    }

    $sources = [];
    foreach ($evidence as $id => $item) {
        if (!is_array($item)) continue;
        $url = trim((string)($item['url'] ?? ''));
        $title = trim((string)($item['title'] ?? ''));
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false
            || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            continue;
        }
        $sources[] = [
            'evidence_id' => (string)$id,
            'title' => $title !== '' ? $title : 'Source à examiner',
            'url' => $url,
            'excerpt' => mb_substr(trim((string)($item['excerpt'] ?? '')), 0, 500),
        ];
    }

    $service = $top['label'] ?? 'un diagnostic environnemental initial';
    $subject = 'Échange exploratoire — ' . $service;
    $body = "Bonjour,\n\n"
        . "Je me permets de vous contacter pour vous proposer un échange exploratoire autour de vos priorités environnementales. "
        . "GreenHumble accompagne les petites structures pour clarifier leurs besoins et identifier des prochaines étapes proportionnées.\n\n"
        . "Au vu des éléments disponibles, un premier échange pourrait permettre d'évaluer si « " . $service
        . " » est pertinent pour " . $company . ". Il ne s'agit pas d'une conclusion sur vos pratiques : les éléments doivent être vérifiés et complétés avec vous.\n\n"
        . "Seriez-vous disponible pour un court échange, sans engagement ?\n\n"
        . "Bien cordialement,\nGreenHumble";

    return [
        'company_name' => $company,
        'website' => $website,
        'qualification_status' => (string)($qualification['qualification_status'] ?? 'insufficient_evidence'),
        'recommended_service' => $top,
        'evidence_sources' => $sources,
        'email_draft' => [
            'subject' => $subject,
            'body' => $body,
            'status' => 'draft_requires_human_review',
        ],
        'checks_before_contact' => [
            'Vérifier l’identité de l’entreprise et le contexte du contact.',
            'Relire les sources et confirmer qu’elles concernent bien cette entreprise.',
            'Vérifier la base légale, les règles applicables à la prospection et les préférences de contact.',
            'Adapter le message et valider l’offre, le périmètre et le prix avant envoi.',
        ],
        'pricing' => null,
        'send_enabled' => false,
        'persistence_enabled' => false,
        'disclaimer' => 'Dossier préparatoire uniquement : aucune prise de contact ni conclusion automatique.',
    ];
}
