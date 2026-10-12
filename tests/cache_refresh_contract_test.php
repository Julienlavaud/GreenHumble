<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/asset_version.php';

function cache_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$assetPath = __DIR__ . '/../api/assets/js/analyse.js';
$expectedVersion = dechex((int)filemtime($assetPath)) . '-' . dechex((int)filesize($assetPath));
cache_assert(
    gh_asset_version('assets/js/analyse.js') === $expectedVersion,
    'asset URLs use the current file timestamp and size'
);
cache_assert(gh_asset_version('../data/scans.json') === '1', 'asset versioning cannot expose files outside the assets directory');

$header = (string)file_get_contents(__DIR__ . '/../api/includes/header.php');
$rootHtaccess = (string)file_get_contents(__DIR__ . '/../.htaccess');
$flush = (string)file_get_contents(__DIR__ . '/../api/flush.php');
$console = (string)file_get_contents(__DIR__ . '/../api/admin/console.php');
$admin = (string)file_get_contents(__DIR__ . '/../api/admin/index.php');
$adminFooter = (string)file_get_contents(__DIR__ . '/../api/includes/footer.php');
$workflow = (string)file_get_contents(__DIR__ . '/../.github/workflows/build-ovh-deployment.yml');

cache_assert(str_contains($header, "header('Cache-Control: no-store"), 'dynamic application pages disable browser caching');
cache_assert(str_contains($rootHtaccess, 'Header always set Cache-Control "public,max-age=2592000"'), 'versioned static assets retain shared-cache efficiency');
cache_assert(str_contains($flush, "get_current_role() !== 'admin'") && str_contains($flush, 'gh_require_same_origin_mutation();'), 'the canonical OPcache purge requires admin authorization and CSRF');
cache_assert(str_contains($console, "'flush_cache'") === false, 'OPcache purge is implemented through one endpoint');
cache_assert(str_contains($admin, 'class="btn-flush-opcache') && str_contains($adminFooter, "assets/js/app.js"), 'administrators can trigger the protected OPcache purge from the Style tab');
cache_assert(str_contains($workflow, 'release/ovh-deployment-clean') && str_contains($workflow, 'workflow_dispatch:') && !str_contains($workflow, 'paths:'), 'every release branch update builds a fresh deployable package');

echo "OK: automatic cache refresh and release package contract\n";
