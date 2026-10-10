<?php
/**
 * GreenHumble — Gestion centralisée des scans.
 * Un seul fichier canonique est utilisé par lecture et écriture.
 */

function get_scans_file_path() {
    $primary = dirname(__DIR__, 2) . '/data/scans.json';
    $legacy = __DIR__ . '/../data/scans.json';

    if (is_dir(dirname($primary)) && is_writable(dirname($primary))) {
        return $primary;
    }
    if (is_dir(dirname($legacy)) && is_writable(dirname($legacy))) {
        return $legacy;
    }
    // Garder le chemin canonique pour rendre l'échec explicite.
    return $primary;
}

function load_all_scans() {
    $file = get_scans_file_path();
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $scans = json_decode($raw, true);
    return is_array($scans) ? $scans : [];
}

/**
 * Ajoute un scan sous verrou inter-processus et remplace le JSON atomiquement.
 * Le verrou porte sur un fichier distinct pour rester stable lors du rename.
 */
function appendScanToJson($newScan) {
    $path = get_scans_file_path();
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) return false;

    $lockPath = $path . '.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock === false) return false;

    $success = false;
    try {
        if (!flock($lock, LOCK_EX)) return false;

        $scans = [];
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if ($raw === false) return false;
            if (trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                    error_log('GreenHumble: scans.json invalide; écriture interrompue pour préserver les données.');
                    return false;
                }
                $scans = $decoded;
            }
        }

        array_unshift($scans, $newScan);
        $json = json_encode(array_values($scans), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $tmp = tempnam($dir, '.gh-scans-');
        if ($tmp === false) return false;

        $written = @file_put_contents($tmp, $json, LOCK_EX);
        if ($written !== strlen($json)) {
            @unlink($tmp);
            return false;
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return false;
        }
        @chmod($path, 0640);
        $success = true;
    } catch (Throwable $e) {
        error_log('GreenHumble: échec appendScanToJson: ' . $e->getMessage());
        return false;
    } finally {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }

    return $success;
}

function record_scan($url, $type_scan = 'diagnostic', $resultat_ia = null, $type_audit = 'full', $liste_pre_audit = [], $utilisateur = 'Julien Lavaud', $statut = 'ok', $enterpriseData = []) {
    $parsedResult = $resultat_ia;
    if (is_string($resultat_ia)) {
        $decoded = json_decode($resultat_ia, true);
        if (is_array($decoded)) $parsedResult = $decoded;
    }

    // Ne jamais fabriquer une note de secours : un scan sans score valide est rejeté.
    $score = is_array($parsedResult) ? ($parsedResult['score'] ?? ($parsedResult['riskScore'] ?? null)) : null;
    if (!is_numeric($score) || !is_finite((float)$score) || (float)$score < 0 || (float)$score > 100) {
        throw new InvalidArgumentException('Résultat IA invalide : score numérique compris entre 0 et 100 requis.');
    }

    $scanId = 'scan_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8));
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    $domain = strtolower(preg_replace('/^www\\./i', '', trim($host)));
    if ($domain === '') $domain = 'domaine-inconnu.com';

    $projetsFile = dirname(__DIR__, 2) . '/data/projets.json';
    if (!is_dir(dirname($projetsFile)) || !is_writable(dirname($projetsFile))) {
        $projetsFile = __DIR__ . '/../data/projets.json';
    }
    $projets = [];
    if (is_file($projetsFile)) {
        $raw = @file_get_contents($projetsFile);
        $decoded = $raw === false ? null : json_decode($raw, true);
        if (is_array($decoded)) $projets = $decoded;
    }

    $assignedProjetId = 'proj_' . bin2hex(random_bytes(8));
    $found = false;
    foreach ($projets as &$p) {
        if (strtolower($p['domaine'] ?? '') !== $domain) continue;
        $assignedProjetId = (string)($p['id'] ?? $assignedProjetId);
        if (!isset($p['scans']) || !is_array($p['scans'])) $p['scans'] = [];
        if (!in_array($scanId, $p['scans'], true)) $p['scans'][] = $scanId;
        foreach (['entreprise_secteur', 'entreprise_ca', 'entreprise_salaries'] as $field) {
            if (!empty($enterpriseData[$field])) $p[$field] = $enterpriseData[$field];
        }
        $found = true;
        break;
    }
    unset($p);

    if (!$found) {
        $projets[] = [
            'id' => $assignedProjetId, 'domaine' => $domain, 'date_creation' => date('c'),
            'scans' => [$scanId],
            'entreprise_secteur' => $enterpriseData['entreprise_secteur'] ?? null,
            'entreprise_ca' => $enterpriseData['entreprise_ca'] ?? null,
            'entreprise_salaries' => $enterpriseData['entreprise_salaries'] ?? null
        ];
    }
    if (is_dir(dirname($projetsFile)) && is_writable(dirname($projetsFile))) {
        $projectJson = json_encode(array_values($projets), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (is_string($projectJson)) @file_put_contents($projetsFile, $projectJson, LOCK_EX);
    }

    $newScan = [
        'id' => $scanId, 'url' => $url,
        'type_scan' => in_array($type_scan, ['diagnostic', 'pipeline', 'widget'], true) ? $type_scan : 'diagnostic',
        'projet_id' => $assignedProjetId, 'date_scan' => date('c'),
        'entreprise_secteur' => $enterpriseData['entreprise_secteur'] ?? null,
        'entreprise_salaries' => $enterpriseData['entreprise_salaries'] ?? null,
        'entreprise_ca' => $enterpriseData['entreprise_ca'] ?? null,
        'resultat_ia' => $parsedResult,
        'statut' => $statut === 'ok' ? 'ok' : 'error',
        'utilisateur' => $utilisateur ?: 'Utilisateur inconnu',
        'type_audit' => $type_audit ?: 'full',
        'liste_pre_audit' => is_array($liste_pre_audit) ? $liste_pre_audit : []
    ];

    if (!appendScanToJson($newScan)) {
        throw new RuntimeException("Impossible d'écrire dans le fichier canonique des scans.");
    }

    if (!empty($liste_pre_audit) && is_array($liste_pre_audit)) {
        $listesFile = dirname(__DIR__, 2) . '/data/listes.json';
        if (!is_file($listesFile)) $listesFile = __DIR__ . '/../data/listes.json';
        if (is_file($listesFile)) {
            $raw = @file_get_contents($listesFile);
            $listes = $raw === false ? [] : (json_decode($raw, true) ?: []);
            foreach ($listes as &$liste) {
                if (!in_array($liste['id'] ?? '', $liste_pre_audit, true)) continue;
                if (!isset($liste['items']) || !is_array($liste['items'])) $liste['items'] = [];
                if (!in_array($scanId, $liste['items'], true)) $liste['items'][] = $scanId;
            }
            unset($liste);
            $json = json_encode(array_values($listes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (is_string($json)) @file_put_contents($listesFile, $json, LOCK_EX);
        }
    }

    return $newScan;
}
