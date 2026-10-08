<?php
/**
 * GreenHumble v1.1 — Module de Gestion des Groupes de Scanners
 * /api/includes/scanner_groups.php
 */

define('DEFAULT_GROUP_ID', 'grp_widget_default');
define('DEFAULT_GROUP_NAME', 'scanner widget');

function get_groups_file_path() {
    $p1 = __DIR__ . '/../../data/scanner_groups.json';
    $p2 = __DIR__ . '/../data/scanner_groups.json';
    if (file_exists(dirname($p1)) && is_writable(dirname($p1))) {
        return $p1;
    }
    return $p2;
}

function get_scanners_file_path() {
    $p1 = __DIR__ . '/../../data/scanners.json';
    $p2 = __DIR__ . '/../data/scanners.json';
    if (file_exists(dirname($p1)) && is_writable(dirname($p1))) {
        return $p1;
    }
    return $p2;
}

function load_scanner_groups() {
    $file = get_groups_file_path();
    $groups = [];
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $groups = json_decode($raw, true) ?: [];
    }

    return $groups;
}

function save_scanner_groups($groups) {
    $p1 = __DIR__ . '/../../data/scanner_groups.json';
    $p2 = __DIR__ . '/../data/scanner_groups.json';
    $json = json_encode(array_values($groups), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    @file_put_contents($p1, $json);
    @file_put_contents($p2, $json);
}

function load_scanners() {
    $file = get_scanners_file_path();
    $scanners = [];
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $scanners = json_decode($raw, true) ?: [];
    }

    return $scanners;
}

function save_scanners($scanners) {
    $p1 = __DIR__ . '/../../data/scanners.json';
    $p2 = __DIR__ . '/../data/scanners.json';
    $json = json_encode(array_values($scanners), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    @file_put_contents($p1, $json);
    @file_put_contents($p2, $json);
}
