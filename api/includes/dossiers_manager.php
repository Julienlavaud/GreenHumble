<?php
/**
 * GreenHumble v1.1 — Module de Gestion des Dossiers
 * /api/includes/dossiers_manager.php
 */

function get_dossiers_file_path() {
    $p1 = __DIR__ . '/../../data/dossiers.json';
    $p2 = __DIR__ . '/../data/dossiers.json';
    if (file_exists(dirname($p1)) && is_writable(dirname($p1))) {
        return $p1;
    }
    return $p2;
}

function load_all_dossiers() {
    $file = get_dossiers_file_path();
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function save_all_dossiers($dossiers) {
    $p1 = __DIR__ . '/../../data/dossiers.json';
    $p2 = __DIR__ . '/../data/dossiers.json';
    $json = json_encode(array_values($dossiers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    @file_put_contents($p1, $json);
    @file_put_contents($p2, $json);
}

function create_dossier($nom, $domaine) {
    $dossiers = load_all_dossiers();
    $cleanDomain = strtolower(preg_replace('/^www\./i', '', trim(parse_url($domaine, PHP_URL_HOST) ?: $domaine)));
    if (empty($cleanDomain)) {
        $cleanDomain = 'domaine-inconnu.com';
    }

    $newDossier = [
        'id' => 'dos_' . uniqid(),
        'nom' => trim($nom) ?: 'Dossier ' . ucfirst(explode('.', $cleanDomain)[0]),
        'domaine' => $cleanDomain,
        'date_creation' => date('c'),
        'scans' => []
    ];

    array_unshift($dossiers, $newDossier);
    save_all_dossiers($dossiers);
    return $newDossier;
}

function find_or_create_dossier_for_domain($url, $scanId = null) {
    $dossiers = load_all_dossiers();
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    $domain = strtolower(preg_replace('/^www\./i', '', trim($host)));

    if (empty($domain)) {
        $domain = 'domaine-inconnu.com';
    }

    $foundIndex = -1;
    foreach ($dossiers as $idx => $d) {
        if (strtolower($d['domaine'] ?? '') === $domain) {
            $foundIndex = $idx;
            break;
        }
    }

    if ($foundIndex >= 0) {
        if ($scanId && !in_array($scanId, $dossiers[$foundIndex]['scans'] ?? [])) {
            if (!isset($dossiers[$foundIndex]['scans']) || !is_array($dossiers[$foundIndex]['scans'])) {
                $dossiers[$foundIndex]['scans'] = [];
            }
            $dossiers[$foundIndex]['scans'][] = $scanId;
            save_all_dossiers($dossiers);
        }
        return $dossiers[$foundIndex];
    } else {
        $newDossier = [
            'id' => 'dos_' . uniqid(),
            'nom' => 'Dossier ' . ucfirst(explode('.', $domain)[0]),
            'domaine' => $domain,
            'date_creation' => date('c'),
            'scans' => $scanId ? [$scanId] : []
        ];
        array_unshift($dossiers, $newDossier);
        save_all_dossiers($dossiers);
        return $newDossier;
    }
}

function add_scan_to_dossier($dossierId, $scanId) {
    $dossiers = load_all_dossiers();
    foreach ($dossiers as &$d) {
        if (($d['id'] ?? '') === $dossierId) {
            if (!isset($d['scans']) || !is_array($d['scans'])) {
                $d['scans'] = [];
            }
            if (!in_array($scanId, $d['scans'])) {
                $d['scans'][] = $scanId;
            }
            save_all_dossiers($dossiers);
            return $d;
        }
    }
    return null;
}
