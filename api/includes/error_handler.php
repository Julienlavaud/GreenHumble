<?php
// /api/includes/error_handler.php — Gestion centralisée des erreurs (pas de page blanche, pas de détails exposés)
if (defined('GH_ERROR_HANDLER_LOADED')) {
    return;
}
define('GH_ERROR_HANDLER_LOADED', true);

$ghDebug = filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? false), FILTER_VALIDATE_BOOLEAN);
define('GH_DEBUG', $ghDebug);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$ghLogDir = __DIR__ . '/../data/logs';
if (!is_dir($ghLogDir)) {
    @mkdir($ghLogDir, 0755, true);
}
if (is_dir($ghLogDir) && is_writable($ghLogDir)) {
    ini_set('error_log', $ghLogDir . '/php_errors.log');
}

function gh_render_error(int $status, string $detail = ''): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $wantsJson = strpos($accept, 'application/json') !== false
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $show = GH_DEBUG && $detail !== '';
    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['error' => 'Erreur interne du serveur'] + ($show ? ['detail' => $detail] : []));
        return;
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Erreur — GreenHumble</title></head>'
        . '<body style="font-family:system-ui,sans-serif;background:#f8fafc;color:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0">'
        . '<div style="max-width:480px;padding:2rem;text-align:center"><h1 style="font-size:1.5rem">Une erreur est survenue</h1>'
        . '<p>Le serveur n\'a pas pu traiter votre demande. Veuillez réessayer dans un instant.</p>'
        . '<p><a href="/api/admin/index.php" style="color:#059669">Retour à l\'administration</a></p>'
        . ($show ? '<pre style="text-align:left;white-space:pre-wrap;font-size:12px">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</pre>' : '')
        . '</div></body></html>';
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log("PHP[$errno] $errstr in $errfile:$errline");
    return true;
});

set_exception_handler(function (Throwable $e): void {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    gh_render_error(500, $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        error_log("PHP fatal: {$err['message']} in {$err['file']}:{$err['line']}");
        gh_render_error(500, $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')');
    }
});
