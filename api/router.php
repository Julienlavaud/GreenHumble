<?php

declare(strict_types=1);

/**
 * GreenHumble Router
 * Compatible OVH mutualisé
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

$path = parse_url($*equestUri, PHP_URL_PATH) ?? '/';
$*ath = trim($path, '/');

/**
 * Co*patibilité avec :
 * /api/scan
 * *api/admin
 * /api/export/raw
 */
i* (strpos($path, 'api/') === 0) {
 *  $path = substr($path, 4);
}

$ro*tes = [

    '' => 'index.php',
    'index' => 'index.php',

    'login' => 'login.php',
    'logout' => 'logout.php',

    'audit' => 'audit.php',
    'prospec' => 'prospec.php',

    'scan' => 'scan.php',
    'scan/process' => 'scan_process.php',

    'analyse' => 'analyse.php',
    'pipeline' => 'pipeline.php',

    'dashboard' => 'dashboard.php',
    'state' => 'state.php',

    'flush' => 'flush.php',

    'user-style' => 'admin/user_style.php',
    'admin/user-style' => 'admin/user_style.php',

    'groups/list' => 'groups/list.php',
    'groups/create' => 'groups/create.php',
    'groups/update' => 'groups/update.php',
    'groups/delete' => 'groups/delete.php',
    'groups/assign_scanner' => 'groups/assign_scanner.php',

    'scans/list' => 'scans/list.php',
    'scans/create' => 'scans/create.php',
    'scans/assign_group' => 'scans/assign_group.php',
    'scans/by_group' => 'scans/by_group.php',
    'scans/delete' => 'scans/delete.php',
    'scans/update' => 'scans/update.php',

    'listes/list' => 'listes/list.php',
    'listes/create' => 'listes/create.php',
    'listes/add_scan' => 'listes/add_scan.php',

    'dossiers/list' => 'dossiers/list.php',
    'dossiers/create' => 'dossiers/create.php',
    'dossiers/add_scan' => 'dossiers/add_scan.php',

    'export/full' => 'export/full.php',
    'export/structure' => 'export/structure.php',
    'export/raw' => 'export/raw.php',

    'admin' => 'admin/index.php',
    'admin/index' => 'admin/index.php',
    'admin/companies' => 'admin/companies.php',
    'admin/texts' => 'admin/texts.php',
    'admin/widget' => 'admin/widget.php',
];

if (isset($routes[$path])) {

    $targetFile = __DIR__ . '/' . $routes[$path];

    if (is_file($targetFile)) {
        require $targetFile;
        exit;
    }
}

/**
 * Fallback : /truc => truc.php
 */
if ($path !== '') {

    $directFile = __DIR__ . '/' . $path . '.php';

    if (is_file($directFile)) {
        require $directFile;
        exit;
    }
}

/**
 * 404 réelle
 */
http_response_code(404);

if (is_file(__DIR__ . '/404.php')) {
    require __DIR__ . '/404.php';
} else {
    echo '404 - Page introuvable';
}

exit;
