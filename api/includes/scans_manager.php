<?php
/**
 * GreenHumble — Gestion centralisée des scans.
 * Un seul fichier canonique est utilisé par lecture et écriture.
 */

const MAX_BULK_DELETE_SCANS = 500;

function get_scan_storage_paths() {
    return [
        dirname(__DIR__, 2) . '/data/scans.json',
        __DIR__ . '/../data/scans.json',
    ];
}

function get_scans_file_path() {
    $paths = get_scan_storage_paths();
    foreach ($paths as $path) {
        if (is_dir(dirname($path)) && is_writable(dirname($path))) return $path;
    }
    return $paths[0];
}

/**
 * Lit et fusionne les historiques connus. Le fichier canonique est lu en premier;
 * les identifiants de scans évitent de compter deux fois les copies historiques.
 * En mode strict, toute source illisible ou invalide bloque l'écriture.
 */
function read_scans_from_paths($paths, $strict = false) {
    $scans = [];
    $seen = [];

    foreach ($paths as $path) {
        if (!is_file($path)) continue;
        $raw = @file_get_contents($path);
        if ($raw === false) {
            if ($strict) return false;
            error_log('GreenHumble: impossible de lire ' . basename($path) . '.');
            continue;
        }
        if (trim($raw) === '') continue;

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            if ($strict) {
                error_log('GreenHumble: JSON de scans invalide; écriture interrompue pour préserver les données.');
                return false;
            }
            error_log('GreenHumble: JSON de scans invalide ignoré en lecture.');
            continue;
        }

        foreach ($decoded as $scan) {
            if (!is_array($scan)) continue;
            $id = trim((string)($scan['id'] ?? ''));
            $encoded = json_encode($scan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $key = $id !== '' ? 'id:' . $id : 'record:' . hash('sha256', is_string($encoded) ? $encoded : serialize($scan));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $scans[] = $scan;
        }
    }

    return $scans;
}

function load_all_scans() {
    $scans = read_scans_from_paths(get_scan_storage_paths());
    return is_array($scans) ? $scans : [];
}

/**
 * Ajoute un scan sous verrou inter-processus et remplace le JSON atomiquement.
 * Le verrou porte sur un fichier distinct pour rester stable lors du rename.
 */
function append_scan_to_json_path($path, $sourcePaths, $newScan) {
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) return false;
    if (!is_writable($dir)) return false;

    $lock = @fopen($path . '.lock', 'c');
    if ($lock === false) return false;

    try {
        if (!flock($lock, LOCK_EX)) return false;

        // Importer aussi l'ancien emplacement avant toute écriture afin de ne
        // jamais abandonner silencieusement les scans qui s'y trouvent.
        $scans = read_scans_from_paths($sourcePaths, true);
        if (!is_array($scans)) return false;

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
        return true;
    } catch (Throwable $e) {
        error_log('GreenHumble: échec appendScanToJson: ' . get_class($e) . '.');
        return false;
    } finally {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
}

/**
 * Applique une modification à l'historique courant sous le même verrou que les ajouts,
 * puis synchronise les emplacements historiques avant de libérer le verrou canonique.
 * Tous les écrivains multi-fichiers verrouillent le canonique avant les copies historiques;
 * append_scan_to_json_path ne verrouille que le canonique.
 */
function mutate_all_scans(callable $mutator, ?array $storagePaths = null, ?callable $replaceFile = null) {
    $storagePaths = $storagePaths ?? get_scan_storage_paths();
    if ($storagePaths === []) return false;
    $replaceFile = $replaceFile ?? static fn(string $temporary, string $path): bool => @rename($temporary, $path);
    // A custom path set always starts with its canonical storage file.
    $canonical = get_scan_storage_paths() === $storagePaths ? get_scans_file_path() : $storagePaths[0];
    $dir = dirname($canonical);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) return false;
    if (!is_writable($dir)) return false;

    $lock = @fopen($canonical . '.lock', 'c');
    if ($lock === false) return false;

    $targetLocks = [];
    $staged = [];
    $originalContents = [];
    $originalModes = [];
    try {
        if (!flock($lock, LOCK_EX)) return false;
        $paths = array_merge([$canonical], array_values(array_filter($storagePaths, static fn($path) => $path !== $canonical)));
        $scans = read_scans_from_paths($paths, true);
        if (!is_array($scans)) return false;

        $updatedScans = $mutator($scans);
        if (!is_array($updatedScans)) return false;
        $json = json_encode(array_values($updatedScans), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $targets = [$canonical];
        foreach ($paths as $path) {
            if ($path !== $canonical && is_file($path)) $targets[] = $path;
        }

        foreach ($targets as $path) {
            $targetDir = dirname($path);
            if (!is_dir($targetDir) || !is_writable($targetDir)) return false;
        }

        foreach ($targets as $path) {
            $targetDir = dirname($path);
            if ($path !== $canonical) {
                $targetLock = @fopen($path . '.lock', 'c');
                if ($targetLock === false) return false;
                if (!flock($targetLock, LOCK_EX)) {
                    fclose($targetLock);
                    return false;
                }
                $targetLocks[] = $targetLock;
            }

            $originalContents[$path] = null;
            if (is_file($path)) {
                $originalContents[$path] = @file_get_contents($path);
                if (!is_string($originalContents[$path])) return false;
                $mode = @fileperms($path);
                if ($mode !== false) $originalModes[$path] = $mode & 0777;
            }

            $tmp = tempnam($targetDir, '.gh-scans-');
            if ($tmp === false) return false;
            if (@file_put_contents($tmp, $json, LOCK_EX) !== strlen($json)) {
                @unlink($tmp);
                return false;
            }
            @chmod($tmp, 0640);
            $staged[$path] = $tmp;
        }

        $committed = [];
        foreach ($targets as $path) {
            if (!$replaceFile($staged[$path], $path)) {
                foreach (array_reverse($committed) as $rollbackPath) {
                    $previous = $originalContents[$rollbackPath];
                    if ($previous === null) {
                        $restored = !is_file($rollbackPath) || @unlink($rollbackPath);
                    } else {
                        $rollbackTmp = tempnam(dirname($rollbackPath), '.gh-scans-rollback-');
                        $restored = $rollbackTmp !== false
                            && @file_put_contents($rollbackTmp, $previous, LOCK_EX) === strlen($previous)
                            && @rename($rollbackTmp, $rollbackPath);
                        if (!$restored && $rollbackTmp !== false) @unlink($rollbackTmp);
                        if ($restored && isset($originalModes[$rollbackPath])) {
                            @chmod($rollbackPath, $originalModes[$rollbackPath]);
                        }
                    }
                    if (!$restored) {
                        error_log('GreenHumble: impossible de restaurer une copie de l’historique des scans.');
                    }
                }
                return false;
            }
            unset($staged[$path]);
            $committed[] = $path;
            @chmod($path, 0640);
        }
        return true;
    } catch (Throwable $e) {
        error_log('GreenHumble: échec de modification atomique des scans.');
        return false;
    } finally {
        foreach ($staged as $tmp) @unlink($tmp);
        foreach ($targetLocks as $targetLock) {
            @flock($targetLock, LOCK_UN);
            @fclose($targetLock);
        }
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
}

function appendScanToJson($newScan) {
    return append_scan_to_json_path(get_scans_file_path(), get_scan_storage_paths(), $newScan);
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
    $domain = strtolower(preg_replace('/^www\./i', '', trim($host)));
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
        'company_name' => trim((string)($enterpriseData['company_name'] ?? ($parsedResult['companyName'] ?? ''))) ?: null,
        'company_siren' => (static function () use ($enterpriseData, $parsedResult) {
            $siren = $enterpriseData['company_siren'] ?? (is_array($parsedResult) ? ($parsedResult['companyRegistry']['siren'] ?? '') : '');
            return preg_match('/^\\d{9}$/', (string)$siren) ? (string)$siren : null;
        })(),
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
