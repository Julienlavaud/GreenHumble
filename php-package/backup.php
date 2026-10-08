<?php
/**
 * CLIMAT LOCAL - GESTIONNAIRE DE SAUVEGARDES & RESTAURATION (PHP 8.x)
 * Fichier : backup.php
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'download' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = BACKUPS_DIR . '/' . $file;
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    echo "Fichier de sauvegarde introuvable.";
    exit;
}

$backups = glob(BACKUPS_DIR . '/*.sql');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sauvegarde & Restauration MySQL - Climat Local</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; margin: 0; }
        .box { max-width: 760px; margin: 0 auto; background: #1e293b; border: 1px solid #334155; border-radius: 1.5rem; padding: 2rem; }
        h1 { margin-top: 0; font-size: 1.4rem; color: #fff; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
        .btn { display: inline-block; padding: 0.75rem 1.25rem; background: #059669; color: #fff; text-decoration: none; border-radius: 0.75rem; font-weight: bold; font-size: 0.85rem; }
        .row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: #090d16; border: 1px solid #334155; border-radius: 0.75rem; margin-bottom: 0.5rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>💾 Module de Sauvegarde & Restauration MySQL</h1>
        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 1.5rem;">
            Exportez et téléchargez une sauvegarde SQL complète de vos audits, contacts et configurations.
        </p>

        <a href="api.php?action=backup" class="btn" style="margin-bottom: 1.5rem;">⚡ Créer une sauvegarde SQL instantanée</a>

        <h3 style="margin-top: 1.5rem;">Archives disponibles (<?= count($backups) ?>)</h3>
        <?php if (empty($backups)): ?>
            <p style="color: #94a3b8; font-size: 0.85rem;">Aucune sauvegarde pour le moment.</p>
        <?php else: ?>
            <?php foreach ($backups as $b): ?>
                <div class="row">
                    <div>
                        <strong><?= basename($b) ?></strong>
                        <span style="color: #94a3b8; margin-left: 0.5rem; font-size: 0.75rem;"><?= round(filesize($b) / 1024, 1) ?> Ko • <?= date('d/m/Y H:i', filemtime($b)) ?></span>
                    </div>
                    <a href="backup.php?action=download&file=<?= urlencode(basename($b)) ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; background: #334155;">📥 Télécharger</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <a href="/" style="color: #34d399; font-size: 0.85rem; text-decoration: none;">&larr; Retour au Cockpit Climat Local</a>
        </div>
    </div>
</body>
</html>
