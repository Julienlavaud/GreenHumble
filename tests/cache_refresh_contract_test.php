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
$auth = (string)file_get_contents(__DIR__ . '/../api/includes/auth.php');
$console = (string)file_get_contents(__DIR__ . '/../api/admin/console.php');
$admin = (string)file_get_contents(__DIR__ . '/../api/admin/index.php');
$workflow = (string)file_get_contents(__DIR__ . '/../.github/workflows/build-ovh-deployment.yml');
$rootIndex = (string)file_get_contents(__DIR__ . '/../index.php');

cache_assert(str_contains($header, "header('Cache-Control: no-store"), 'dynamic application pages disable browser caching');
cache_assert(
    str_contains($rootHtaccess, 'Header always set Cache-Control "no-cache, must-revalidate, max-age=0"')
        && str_contains($rootHtaccess, 'Header always set Cache-Control "no-store,no-cache,must-revalidate,max-age=0"'),
    'browser and proxy caches revalidate assets and never store PHP responses'
);
cache_assert(
    str_contains($rootIndex, "header('Location: /api/index.php', true, 302)")
        && !str_contains($rootIndex, '301'),
    'legacy root redirect is temporary so browsers do not pin an outdated destination'
);
cache_assert(
    str_contains($flush, "require_once __DIR__ . '/includes/auth.php';")
        && str_contains($auth, 'function gh_require_same_origin_mutation()')
        && str_contains($flush, "get_current_role() !== 'admin'")
        && str_contains($flush, 'gh_require_same_origin_mutation();')
        && str_contains($flush, "\$response['success'] = \$resetOk;")
        && str_contains($flush, "http_response_code(503);")
        && str_contains($flush, "\$response['error'] = 'OPcache est disponible"),
    'the canonical OPcache purge loads its auth/CSRF helper and requires admin authorization'
);
cache_assert(str_contains($console, "'flush_cache'") === false, 'OPcache purge is implemented through one endpoint');
cache_assert(
    str_contains($admin, 'id="admin-clear-cache"')
        && str_contains($admin, "fetch('/api/flush.php'")
        && str_contains($admin, '!response.ok || result.success !== true || result.opcache_reset !== true')
        && str_contains($admin, 'admin-clear-cache-status'),
    'administrators can trigger the protected cache purge from every console tab and see its result'
);
cache_assert(str_contains($workflow, 'release/ovh-deployment-clean') && str_contains($workflow, 'workflow_dispatch:') && !str_contains($workflow, 'paths:'), 'every release branch update builds a fresh deployable package');

echo "OK: automatic cache refresh and release package contract\n";
