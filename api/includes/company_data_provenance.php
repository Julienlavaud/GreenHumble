<?php
declare(strict_types=1);

/** Decode the saved analysis payload used to retain per-audit company provenance. */
function gh_company_raw_audit_payload(array $audit): array
{
    $payload = $audit['raw_response'] ?? [];
    if (is_string($payload)) {
        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }
    return is_array($payload) ? $payload : [];
}

/** Turn stored provenance codes into labels suitable for the company profile UI. */
function gh_company_provenance_label($source, bool $hasValue): string
{
    $labels = [
        'registre_public_insee' => 'Registre public (INSEE)',
        'registre_public_etat' => 'Registre public (État)',
        'saisie_utilisateur' => 'Saisie utilisateur',
        'estimation_non_verifiee' => 'Estimation non vérifiée',
        'non_communique' => 'Non communiqué',
    ];
    $source = trim((string)$source);
    if ($source === 'non_communique') return $hasValue ? 'Source non renseignée' : 'Non communiqué';
    if ($source === '') return $hasValue ? 'Source non renseignée pour cet audit' : 'Non communiqué';
    return $labels[$source] ?? 'Source non renseignée pour cet audit';
}
