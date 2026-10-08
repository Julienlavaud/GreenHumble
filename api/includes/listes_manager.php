<?php
/**
 * GreenHumble v1.1 — Module de Gestion des Listes
 * /api/includes/listes_manager.php
 */

function get_listes_file_path() {
    $p1 = __DIR__ . '/../../data/listes.json';
    $p2 = __DIR__ . '/../data/listes.json';
    if (file_exists(dirname($p1)) && is_writable(dirname($p1))) {
        return $p1;
    }
    return $p2;
}

function load_all_listes() {
    $file = get_listes_file_path();
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function save_all_listes($listes) {
    $p1 = __DIR__ . '/../../data/listes.json';
    $p2 = __DIR__ . '/../data/listes.json';
    $json = json_encode(array_values($listes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    @file_put_contents($p1, $json);
    @file_put_contents($p2, $json);
}

function create_liste($nom, $description = '') {
    $listes = load_all_listes();
    $newList = [
        'id' => 'lst_' . uniqid(),
        'nom' => trim($nom) ?: 'Nouvelle Liste',
        'description' => trim($description),
        'items' => []
    ];
    array_unshift($listes, $newList);
    save_all_listes($listes);
    return $newList;
}

function add_scan_to_liste($listId, $scanId) {
    $listes = load_all_listes();
    foreach ($listes as &$l) {
        if (($l['id'] ?? '') === $listId) {
            if (!isset($l['items']) || !is_array($l['items'])) {
                $l['items'] = [];
            }
            if (!in_array($scanId, $l['items'])) {
                $l['items'][] = $scanId;
            }
            save_all_listes($listes);
            return $l;
        }
    }
    return null;
}
