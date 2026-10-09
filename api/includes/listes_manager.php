<?php
/**
 * GreenHumble v1.1 — Module de Gestion des Listes
 * Stockage JSON atomique pour éviter les fichiers partiellement écrits.
 */
function get_listes_file_path() {
    if (defined('GH_LISTES_FILE_PATH') && is_string(GH_LISTES_FILE_PATH) && GH_LISTES_FILE_PATH !== '') {
        return GH_LISTES_FILE_PATH;
    }
    $p1 = __DIR__ . '/../../data/listes.json';
    $p2 = __DIR__ . '/../data/listes.json';
    if (is_dir(dirname($p1)) && is_writable(dirname($p1))) {
        return $p1;
    }
    return $p2;
}

function load_all_listes() {
    $file = get_listes_file_path();
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    if (!is_string($raw)) {
        error_log('GreenHumble: impossible de lire le fichier des listes.');
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        error_log('GreenHumble: fichier des listes JSON invalide.');
        return [];
    }
    return $data;
}

/**
 * Enregistre les listes par remplacement atomique. Retourne false en cas d'échec.
 */
function save_all_listes($listes) {
    if (!is_array($listes)) return false;
    $file = get_listes_file_path();
    $directory = dirname($file);
    if (!is_dir($directory) || !is_writable($directory)) {
        error_log('GreenHumble: dossier des listes absent ou non inscriptible.');
        return false;
    }
    try {
        $json = json_encode(array_values($listes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        error_log('GreenHumble: encodage JSON des listes impossible: ' . $e->getMessage());
        return false;
    }
    $tmp = tempnam($directory, '.listes-');
    if ($tmp === false) {
        error_log('GreenHumble: impossible de créer un fichier temporaire pour les listes.');
        return false;
    }
    $ok = false;
    $handle = @fopen($tmp, 'wb');
    if ($handle !== false) {
        if (flock($handle, LOCK_EX)) {
            $length = strlen($json);
            $written = 0;
            while ($written < $length) {
                $n = fwrite($handle, substr($json, $written));
                if ($n === false || $n === 0) break;
                $written += $n;
            }
            $ok = ($written === $length) && fflush($handle);
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }
    if ($ok) {
        @chmod($tmp, 0640);
        $ok = @rename($tmp, $file);
    }
    if (!$ok) {
        @unlink($tmp);
        error_log('GreenHumble: échec d’écriture atomique du fichier des listes.');
        return false;
    }
    return true;
}

function create_liste($nom, $description = '') {
    $listes = load_all_listes();
    $newList = [
        'id' => 'lst_' . bin2hex(random_bytes(8)),
        'nom' => trim($nom) ?: 'Nouvelle Liste',
        'description' => trim($description),
        'items' => []
    ];
    array_unshift($listes, $newList);
    if (!save_all_listes($listes)) return null;
    return $newList;
}

function add_scan_to_liste($listId, $scanId) {
    $listes = load_all_listes();
    foreach ($listes as &$l) {
        if (($l['id'] ?? '') === $listId) {
            if (!isset($l['items']) || !is_array($l['items'])) $l['items'] = [];
            if (!in_array($scanId, $l['items'], true)) $l['items'][] = $scanId;
            $updated = $l;
            unset($l);
            return save_all_listes($listes) ? $updated : null;
        }
    }
    unset($l);
    return null;
}
