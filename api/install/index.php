<?php
// /api/install/index.php
$envPath = __DIR__ . '/../config/.env';
if (file_exists($envPath) && filesize($envPath) > 20) {
    // Si déjà installé
    $alreadyInstalled = true;
} else {
    $alreadyInstalled = false;
}

$phpVersion = phpversion();
$isPhpOk = version_compare($phpVersion, '8.0', '>=');
$extensions = ['pdo_mysql', 'curl', 'openssl', 'json', 'mbstring'];
$extResults = [];
$allOk = $isPhpOk;

foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $extResults[$ext] = $loaded;
    if (!$loaded) $allOk = false;
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <title>Installation ClimatLocal - Étape 1</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-white p-8 sm:p-10 rounded-3xl border border-slate-200 shadow-xl">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 font-black text-xl flex items-center justify-center mx-auto mb-3 shadow-inner">
                🌱
            </div>
            <h1 class="text-2xl font-black text-slate-900">Installation ClimatLocal</h1>
            <p class="text-xs text-slate-500 mt-1">Vérification des prérequis serveur (Étape 1/2)</p>
        </div>

        <?php if ($alreadyInstalled): ?>
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-2xl font-medium">
                ⚠️ Un fichier <code>.env</code> est déjà présent. Poursuivre l'installation écrasera la configuration actuelle.
            </div>
        <?php endif; ?>

        <div class="space-y-3 mb-8">
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">Version PHP (>= 8.0) : <code><?php echo $phpVersion; ?></code></span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full <?php echo $isPhpOk ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $isPhpOk ? '✓ Valide' : '✗ Trop ancienne'; ?>
                </span>
            </div>

            <?php foreach ($extResults as $ext => $ok): ?>
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Extension <code><?php echo $ext; ?></code></span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full <?php echo $ok ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $ok ? '✓ Installée' : '✗ Manquante'; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="/api/index.php" class="text-xs text-slate-500 hover:text-slate-700 font-semibold">Annuler</a>
            <a href="/api/install/step2.php" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow transition-all">
                Étape suivante : Base de données ➔
            </a>
        </div>
    </div>
</body>
</html>
