<?php
/**
 * CLIMAT LOCAL - OUTIL DE DIAGNOSTIC SERVEUR & INTÉGRITÉ SYSTÈME (PHP 8.x)
 * Fichier : diagnostic.php
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$db = get_db_connection();
$gemini = test_gemini_api();

$folders = [
    'Racine' => [__DIR__, is_writable(__DIR__)],
    'Config (/config)' => [CONFIG_DIR, is_writable(CONFIG_DIR)],
    'Données (/data)' => [DATA_DIR, is_writable(DATA_DIR)],
    'Logs (/logs)' => [LOGS_DIR, is_writable(LOGS_DIR)],
    'Sauvegardes (/backups)' => [BACKUPS_DIR, is_writable(BACKUPS_DIR)],
];

$exts = [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
];

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'php' => PHP_VERSION,
        'mysql' => $db !== null,
        'gemini' => $gemini,
        'extensions' => $exts,
        'folders' => $folders,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Santé Serveur - Climat Local</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; margin: 0; }
        .box { max-width: 760px; margin: 0 auto; background: #1e293b; border: 1px solid #334155; border-radius: 1.5rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        h1 { margin-top: 0; font-size: 1.5rem; color: #fff; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
        .row { display: flex; justify-content: space-between; padding: 0.75rem 1rem; background: #090d16; border: 1px solid #334155; border-radius: 0.75rem; margin-bottom: 0.5rem; font-size: 0.85rem; }
        .ok { color: #34d399; font-weight: bold; }
        .err { color: #f43f5e; font-weight: bold; }
        .btn { display: inline-block; padding: 0.75rem 1.25rem; background: #059669; color: #fff; text-decoration: none; border-radius: 0.75rem; font-weight: bold; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🩺 Diagnostic de Santé Serveur • Climat Local</h1>

        <h3>1. Environnement & PHP</h3>
        <div class="row">
            <span>Version de PHP</span>
            <span class="<?= version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'err' ?>"><?= PHP_VERSION ?></span>
        </div>
        <div class="row">
            <span>Mémoire allouée (memory_limit)</span>
            <span class="ok"><?= ini_get('memory_limit') ?></span>
        </div>

        <h3>2. Extensions PHP</h3>
        <?php foreach ($exts as $e => $loaded): ?>
            <div class="row">
                <span>Extension <code><?= $e ?></code></span>
                <span class="<?= $loaded ? 'ok' : 'err' ?>"><?= $loaded ? 'Active' : 'Manquante' ?></span>
            </div>
        <?php endforeach; ?>

        <h3>3. Base de Données MySQL</h3>
        <div class="row">
            <span>Connexion PDO MySQL</span>
            <span class="<?= $db ? 'ok' : 'err' ?>"><?= $db ? 'Connecté avec succès' : 'Échec de connexion (vérifier .env)' ?></span>
        </div>

        <h3>4. Intelligence Artificielle Google Gemini</h3>
        <div class="row">
            <span>API Gemini (2.5 Flash)</span>
            <span class="<?= $gemini['success'] ? 'ok' : 'err' ?>">
                <?= htmlspecialchars($gemini['message']) ?>
                <?= isset($gemini['latency_ms']) ? " ({$gemini['latency_ms']} ms)" : "" ?>
            </span>
        </div>

        <h3>5. Permissions d'écriture des répertoires</h3>
        <?php foreach ($folders as $name => [$path, $writable]): ?>
            <div class="row">
                <span><?= $name ?></span>
                <span class="<?= $writable ? 'ok' : 'err' ?>"><?= $writable ? 'Inscriptible' : 'Non inscriptible' ?></span>
            </div>
        <?php endforeach; ?>

        <a href="/" class="btn">&larr; Retour au Cockpit</a>
    </div>
</body>
</html>
