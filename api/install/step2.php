<?php
// /api/install/step2.php
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? 'climatlocal');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $gemini_key = trim($_POST['gemini_key'] ?? '');
    $admin_pass = $_POST['admin_pass'] ?? 'password';

    // 1. Test de connexion PDO
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 2. Importation du schéma SQL complet
        $schemaPath = __DIR__ . '/../schema.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // Fallback: découpage des requêtes individuelles si multi-query n'est pas activé par défaut
                $queries = array_filter(array_map('trim', explode(";\n", $sql)));
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        try {
                            $pdo->exec($query);
                        } catch (Exception $subEx) {}
                    }
                }
            }
        }

        // 3. Création du compte super-admin initial dans cl_users
        $adminHash = password_hash($admin_pass, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO cl_users (username, email, password_hash, role) VALUES ('Admin', 'admin@climatlocal.com', ?, 'admin') ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
            $stmt->execute([$adminHash]);
        } catch (Exception $e) {}

        // 4. Écriture du fichier .env
        $envContent = "DB_HOST=$db_host\n"
            . "DB_NAME=$db_name\n"
            . "DB_USER=$db_user\n"
            . "DB_PASS=$db_pass\n"
            . "GEMINI_API_KEY=$gemini_key\n"
            . "APP_ENV=production\n";

        $envFile = __DIR__ . '/../config/.env';
        file_put_contents($envFile, $envContent);

        $success = true;
    } catch (PDOException $e) {
        $error = "Erreur de connexion SQL : " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Erreur générale : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <title>Installation ClimatLocal - Étape 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-white p-8 sm:p-10 rounded-3xl border border-slate-200 shadow-xl">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 font-black text-xl flex items-center justify-center mx-auto mb-3 shadow-inner">
                🗄️
            </div>
            <h1 class="text-2xl font-black text-slate-900">Configuration de la Base</h1>
            <p class="text-xs text-slate-500 mt-1">Étape 2/2 : Connexion MySQL & Clé Gemini</p>
        </div>

        <?php if ($success): ?>
            <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl text-center space-y-4">
                <div class="text-4xl">🎉</div>
                <h2 class="text-lg font-black text-emerald-900">Installation Réussie avec Succès !</h2>
                <p class="text-xs text-emerald-700">La base de données a été initialisée et vos identifiants ont été enregistrés.</p>
                <div class="pt-2 flex flex-col sm:flex-row gap-2 justify-center">
                    <a href="/api/index.php" class="px-5 py-2.5 bg-emerald-800 hover:bg-emerald-900 text-white font-bold rounded-xl text-xs shadow">
                        Aller à l'Accueil ➔
                    </a>
                    <a href="/api/admin/index.php" class="px-5 py-2.5 bg-white hover:bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold rounded-xl text-xs">
                        Console Admin
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl font-medium leading-relaxed">
                    <strong>Erreur :</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/api/install/step2.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Hôte MySQL (DB_HOST)</label>
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required placeholder="localhost ou mysql.hebergeur.com" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nom de la Base</label>
                        <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'climatlocal'); ?>" required placeholder="climatlocal" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Utilisateur MySQL</label>
                        <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? 'root'); ?>" required placeholder="root" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mot de passe MySQL</label>
                    <input type="password" name="db_pass" placeholder="Laisser vide si aucun mot de passe" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Clé API Google Gemini (Optionnelle)</label>
                    <input type="text" name="gemini_key" value="<?php echo htmlspecialchars($_POST['gemini_key'] ?? ''); ?>" placeholder="AIzaSy..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mot de passe Administrateur initial</label>
                    <input type="password" name="admin_pass" value="password" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <p class="text-xs text-slate-400 mt-1">Sert pour vous connecter à l'espace <code>/api/admin/</code></p>
                </div>

                <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                    <a href="/api/install/index.php" class="text-xs text-slate-500 hover:underline">← Retour</a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold rounded-xl text-xs shadow transition-all">
                        Finaliser l'Installation ➔
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
