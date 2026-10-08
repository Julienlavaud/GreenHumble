<?php
/**
 * GreenHumble v1.1 — Module de Gestion Centralisée des Scans
 * /api/includes/scans_manager.php
 */

function get_scans_file_path() {
    $p1 = __DIR__ . '/../../data/scans.json';
    $p2 = __DIR__ . '/../data/scans.json';
    $p3 = __DIR__ . '/../../scans.json';
    if (file_exists(dirname($p1)) && is_writable(dirname($p1))) {
        return $p1;
    }
    if (file_exists(dirname($p2)) && is_writable(dirname($p2))) {
        return $p2;
    }
    return $p1;
}

function load_all_scans() {
    $file = get_scans_file_path();
    $scans = [];
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $scans = json_decode($raw, true) ?: [];
    }
    return is_array($scans) ? $scans : [];
}

/**
 * appendScanToJson : vérifie permissions OVH, ajoute le scan en tête, sauvegarde proprement.
 */
function appendScanToJson($newScan) {
    $p1 = __DIR__ . '/../../data/scans.json';
    $p2 = __DIR__ . '/../data/scans.json';

    $scans = load_all_scans();
    array_unshift($scans, $newScan);
    $json = json_encode(array_values($scans), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $success = false;
    foreach ([$p1, $p2] as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                continue;
            }
        }
        @chmod($dir, 0755);

        $writeResult = @file_put_contents($path, $json);
        if ($writeResult !== false) {
            @chmod($path, 0644);
            $success = true;
        }
    }

    return $success;
}

function record_scan($url, $type_scan = 'diagnostic', $resultat_ia = null, $type_audit = 'full', $liste_pre_audit = [], $utilisateur = 'Julien Lavaud', $statut = 'ok', $enterpriseData = []) {
    $parsedResult = $resultat_ia;
    if (is_string($resultat_ia)) {
        $decoded = json_decode($resultat_ia, true);
        if ($decoded) {
            $parsedResult = $decoded;
        }
    }

    $scanId = 'scan_' . time() . '_' . substr(md5(uniqid()), 0, 5);

    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    $domain = strtolower(preg_replace('/^www\./i', '', trim($host)));
    if (empty($domain)) {
        $domain = 'domaine-inconnu.com';
    }

    // Gestion propre des Projets (/data/projets.json)
    $projetsFile = __DIR__ . '/../../data/projets.json';
    if (!file_exists(dirname($projetsFile))) {
        $projetsFile = __DIR__ . '/../data/projets.json';
    }
    $projets = [];
    if (file_exists($projetsFile)) {
        $projets = json_decode(@file_get_contents($projetsFile), true) ?: [];
    }

    $assignedProjetId = 'proj_' . time();
    $found = false;
    foreach ($projets as &$p) {
        if (strtolower($p['domaine'] ?? '') === $domain) {
            $assignedProjetId = $p['id'];
            if (!isset($p['scans']) || !is_array($p['scans'])) {
                $p['scans'] = [];
            }
            if (!in_array($scanId, $p['scans'])) {
                $p['scans'][] = $scanId;
            }
            if (!empty($enterpriseData['entreprise_secteur'])) $p['entreprise_secteur'] = $enterpriseData['entreprise_secteur'];
            if (!empty($enterpriseData['entreprise_ca'])) $p['entreprise_ca'] = $enterpriseData['entreprise_ca'];
            if (!empty($enterpriseData['entreprise_salaries'])) $p['entreprise_salaries'] = $enterpriseData['entreprise_salaries'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $projets[] = [
            'id' => $assignedProjetId,
            'domaine' => $domain,
            'date_creation' => date('c'),
            'scans' => [$scanId],
            'entreprise_secteur' => $enterpriseData['entreprise_secteur'] ?? 'Services & Industrie',
            'entreprise_ca' => $enterpriseData['entreprise_ca'] ?? '2M€ - 10M€',
            'entreprise_salaries' => $enterpriseData['entreprise_salaries'] ?? '11-50 salariés'
        ];
    }
    if (file_exists(dirname($projetsFile))) {
        @file_put_contents($projetsFile, json_encode(array_values($projets), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    $newScan = [
        'id' => $scanId,
        'url' => $url,
        'type_scan' => in_array($type_scan, ['diagnostic', 'pipeline', 'widget']) ? $type_scan : 'diagnostic',
        'projet_id' => $assignedProjetId,
        'date_scan' => date('c'),
        'entreprise_secteur' => $enterpriseData['entreprise_secteur'] ?? 'Services & Industrie',
        'entreprise_salaries' => $enterpriseData['entreprise_salaries'] ?? '11-50 salariés',
        'entreprise_ca' => $enterpriseData['entreprise_ca'] ?? '2M€ - 10M€',
        'resultat_ia' => $parsedResult ?: ['score' => 60, 'summary' => 'Scan enregistré'],
        'statut' => 'ok',
        'utilisateur' => $utilisateur ?: 'Julien Lavaud',
        'type_audit' => $type_audit ?: 'full',
        'liste_pre_audit' => is_array($liste_pre_audit) ? $liste_pre_audit : []
    ];

    $saved = appendScanToJson($newScan);
    if (!$saved) {
        throw new Exception("Impossible d'écrire dans les données");
    }

    // Gestion automatique des listes
    if (!empty($liste_pre_audit) && is_array($liste_pre_audit)) {
        $listesFile = __DIR__ . '/../../data/listes.json';
        if (!file_exists(dirname($listesFile))) {
            $listesFile = __DIR__ . '/../data/listes.json';
        }
        if (file_exists($listesFile)) {
            $listes = json_decode(@file_get_contents($listesFile), true) ?: [];
            foreach ($liste_pre_audit as $listId) {
                foreach ($listes as &$l) {
                    if (($l['id'] ?? '') === $listId) {
                        if (!isset($l['items']) || !is_array($l['items'])) $l['items'] = [];
                        if (!in_array($scanId, $l['items'])) $l['items'][] = $scanId;
                    }
                }
            }
            @file_put_contents($listesFile, json_encode(array_values($listes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    return $newScan;
}
