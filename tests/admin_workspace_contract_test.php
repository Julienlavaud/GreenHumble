<?php
declare(strict_types=1);

function admin_workspace_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$admin = (string)file_get_contents(__DIR__ . '/../api/admin/index.php');
$header = (string)file_get_contents(__DIR__ . '/../api/includes/header.php');
$apiGuard = (string)file_get_contents(__DIR__ . '/../api/includes/api_guard.php');
$theme = (string)file_get_contents(__DIR__ . '/../api/assets/css/greenhumble-app.css');
$router = (string)file_get_contents(__DIR__ . '/../api/router.php');
$bulkDelete = (string)file_get_contents(__DIR__ . '/../api/scans/bulk_delete.php');
$analyse = (string)file_get_contents(__DIR__ . '/../api/analyse.php');
$analyseJs = (string)file_get_contents(__DIR__ . '/../api/assets/js/analyse.js');
$workflow = (string)file_get_contents(__DIR__ . '/../.github/workflows/php-quality.yml');

admin_workspace_assert(
    str_contains($admin, "if (\$activeTab === 'dossiers')") && str_contains($admin, '?tab=scans'),
    'the Dossiers navigation opens the full scan inventory rather than Lists'
);
admin_workspace_assert(
    str_contains($header, "'scans' => 'Scans & dossiers'")
        && !str_contains($header, "'dossiers' => 'Dossiers'")
        && !str_contains($header, "'scanner_groups' => 'Groupes de scanners'"),
    'the sidebar labels the real scan inventory and omits redirect-only menu entries'
);
admin_workspace_assert(
    str_contains($header, "'ai_providers.php' => 'ai_providers'")
        && str_contains($header, "'agency' => '/api/admin/index.php?tab=agency#organisation-equipe'"),
    'direct admin pages and the team anchor map to the correct active menu section'
);
admin_workspace_assert(
    str_contains($admin, 'id="admin-clear-cache"')
        && str_contains($admin, "fetch('/api/flush.php'")
        && str_contains($admin, 'id="admin-clear-cache-status"'),
    'the protected cache purge is reachable from every admin console tab with status feedback'
);
admin_workspace_assert(str_contains($admin, 'load_all_scans()'), 'the inventory loads all persisted scans');
admin_workspace_assert(str_contains($admin, 'rescanExistingCard') && str_contains($admin, "fetch('/api/audit'"), 'individual and bulk rescans use the official audit endpoint');
admin_workspace_assert(str_contains($admin, 'Le scan d’origine est conservé') && str_contains($admin, 'Les originaux sont conservés'), 'rescans preserve the original scan history');
admin_workspace_assert(str_contains($admin, '/api/scans/bulk_delete') && str_contains($router, "'scans/bulk_delete'"), 'bulk deletion is routed through a dedicated endpoint');
admin_workspace_assert(str_contains($bulkDelete, "gh_api_guard(true, ['admin'])") && str_contains($bulkDelete, 'mutate_all_scans('), 'bulk deletion is admin-only and mutates the latest history under a lock');
admin_workspace_assert(str_contains($admin, 'resultat_ia') && str_contains($admin, '/api/analyse.php?id='), 'scan cards can open their associated audit report');
admin_workspace_assert(
    str_contains($admin, "['agency', 'missions', 'legal', 'scanner']"),
    'the common Save button is only exposed on supported configuration tabs'
);
admin_workspace_assert(str_contains($admin, 'Team Manager · accès collaborateurs'), 'the agency page identifies its Team Manager section');
admin_workspace_assert(str_contains($admin, 'aria-label="Supprimer ce collaborateur"'), 'the team delete action is visible and accessible');
admin_workspace_assert(
    str_contains($admin, 'const MAX_BULK_RESCANS = 10;')
        && str_contains($admin, 'bulkRescanButton.textContent = `Relancer la sélection (${MAX_BULK_RESCANS} max)`')
        && str_contains($admin, 'cards.length > MAX_BULK_RESCANS'),
    'bulk rescans use one shared batch-size limit'
);
admin_workspace_assert(!str_contains($admin, "['audits', 'missions', 'legal', 'texts', 'team', 'scanner']"), 'unreachable Team-tab export controls are removed');
admin_workspace_assert(str_contains($admin, "\\uFEFF") && str_contains($admin, "replace(/\"/g, '\"\"')"), 'Scanner CSV exports preserve UTF-8 BOM and quote escaped prompt values');
admin_workspace_assert(str_contains($header, 'greenhumble-app.css') && str_contains($theme, 'body[data-gh-role=admin] .gh-content>:is(div,main)[class*="max-w-"]'), 'standalone admin pages share the AI provider visual system');
admin_workspace_assert(
    str_contains($apiGuard, "if (!headers_sent())")
        && str_contains($apiGuard, "header('Cache-Control: no-store"),
    'API no-store headers are emitted safely when a guarded endpoint runs'
);
admin_workspace_assert(str_contains($admin, "isset(\$_SESSION['role']) || \$_SESSION['role'] !== 'admin'") && str_contains($admin, "fetch('/api/audit'"), 'bulk actions are only shown on the admin-guarded page, whose same-origin fetch calls receive CSRF protection from the shared header');
admin_workspace_assert(
    str_contains($analyse, 'assets/js/analyse.js')
        && !str_contains($analyse, 'function openClaimCorrectionModal')
        && str_contains($analyseJs, 'function openClaimCorrectionModal'),
    'analysis page scripts remain external and do not render as page text'
);
admin_workspace_assert(str_contains($workflow, 'tests/admin_workspace_contract_test.php'), 'the admin workspace contract runs in CI');

echo "OK: admin styling, scan inventory, actions, and team manager contract\n";
