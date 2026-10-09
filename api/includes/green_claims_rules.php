<?php
declare(strict_types=1);

/**
 * GreenHumble — règles de pré-détection des allégations environnementales.
 * Signaux de tri uniquement : ne conclut jamais à une infraction juridique.
 */

function gh_green_claims_dictionary(): array
{
    return [
        'carbon_neutrality' => [
            'label' => 'Neutralité / compensation carbone',
            'patterns' => ['neutre en carbone', 'neutralité carbone', 'empreinte carbone nulle', 'compensation carbone totale', 'compense ses émissions', 'compensation carbone'],
            'review' => 'Vérifier le périmètre, la méthode de calcul, les réductions réelles et la présentation de la compensation.',
            'evidence' => ['Périmètre et période de l’allégation', 'Bilan GES et méthode de calcul', 'Éléments documentant les réductions et la compensation'],
            'references' => ['Directive (UE) 2024/825 — dispositions applicables aux allégations liées au climat', 'Code de la consommation, articles L.121-2 et L.121-3'],
        ],
        'absolute_claim' => [
            'label' => 'Allégation absolue ou universelle',
            'patterns' => ['100% écologique', '100 % écologique', '100% vert', '100 % vert', 'zéro émission', 'zero emission', 'zéro impact', 'zero impact', 'sans impact', 'zéro déchet', 'zero dechet', 'totalement neutre', 'le plus vert'],
            'review' => 'Vérifier si la portée absolue est précise, mesurable et justifiée pour l’ensemble du périmètre annoncé.',
            'evidence' => ['Définition du périmètre', 'Méthodologie et données vérifiables', 'Limites et exceptions de l’allégation'],
            'references' => ['Directive (UE) 2024/825 — règles applicables aux allégations environnementales génériques', 'Code de la consommation, articles L.121-2 et L.121-3'],
        ],
        'vague_claim' => [
            'label' => 'Vocabulaire environnemental vague',
            'patterns' => ['éco-responsable', 'eco-responsable', 'respectueux de l’environnement', 'respectueux de l environnement', 'respectueux de la planète', 'bon pour la planète', 'solution verte', 'transition verte', 'impact positif', 'écologique', 'ecologique', 'plus durable'],
            'review' => 'Demander une précision concrète sur l’impact, le périmètre et la comparaison revendiqués.',
            'evidence' => ['Indicateur environnemental précis', 'Périmètre produit/service/entreprise', 'Données et méthode justificatives'],
            'references' => ['Code de la consommation, articles L.121-2 et L.121-3', 'Directive (UE) 2024/825 — transposition et applicabilité à vérifier selon la date et le contexte'],
        ],
        'unsubstantiated_attribute' => [
            'label' => 'Caractéristique à justifier',
            'patterns' => ['biodégradable', 'recyclable', 'matériaux naturels', 'matières naturelles', 'durable'],
            'review' => 'Vérifier la définition utilisée, les conditions réelles et les preuves propres au produit ou service.',
            'evidence' => ['Spécification produit ou composition', 'Essai, certification ou méthode reconnue selon le cas', 'Conditions et limites d’utilisation'],
            'references' => ['Code de la consommation, article L.121-2', 'Réglementation sectorielle applicable à vérifier'],
        ],
        'unsupported_comparison' => [
            'label' => 'Comparaison environnementale à vérifier',
            'patterns' => ['moins polluant que', 'plus écologique que', 'plus durable que', 'le plus durable', 'réduit son impact'],
            'review' => 'Identifier le comparateur, la période, les indicateurs et une méthode de comparaison équivalente.',
            'evidence' => ['Produit ou référence comparée', 'Indicateurs et période comparables', 'Source et méthode de calcul'],
            'references' => ['Code de la consommation, articles L.121-2 et L.121-3'],
        ],
    ];
}

/** Normalise les accents et la casse pour la détection lexicale. */
function gh_normalize_claim_text(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    if (function_exists('iconv')) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($ascii)) $text = $ascii;
    }
    return preg_replace('/[^a-z0-9%]+/u', ' ', $text) ?? $text;
}

/**
 * @return array{status:string, disclaimer:string, findings:array<int,array<string,mixed>>}
 */
function gh_detect_green_claim_signals(string $text): array
{
    $normalized = gh_normalize_claim_text($text);
    $dictionary = gh_green_claims_dictionary();
    $findings = [];

    foreach ($dictionary as $type => $rule) {
        $matches = [];
        foreach ($rule['patterns'] as $pattern) {
            $needle = gh_normalize_claim_text($pattern);
            if ($needle !== '' && strpos(' ' . $normalized . ' ', ' ' . $needle . ' ') !== false) {
                $matches[] = $pattern;
            }
        }
        if (!$matches) continue;
        $findings[] = [
            'type' => $type,
            'label' => $rule['label'],
            'matched_terms' => array_values(array_unique($matches)),
            'status' => 'to_review',
            'legal_conclusion' => null,
            'review_action' => $rule['review'],
            'evidence_to_request' => $rule['evidence'],
            'potential_references' => $rule['references'],
        ];
    }

    return [
        'status' => $findings ? 'signals_detected' : 'no_dictionary_signal',
        'disclaimer' => 'Pré-détection lexicale uniquement. Un signal n’établit pas une infraction : vérifier le contexte, le périmètre, les preuves et le droit applicable. L’absence de signal ne garantit pas la conformité.',
        'findings' => $findings,
    ];
}
