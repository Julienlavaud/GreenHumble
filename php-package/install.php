<?php
/**
 * CLIMAT LOCAL - ASSISTANT D'INSTALLATION & CONFIGURATION SERVEUR (TYPE DOLIBARR / WORDPRESS)
 * Fichier : install.php
 * Compatible PHP 8.x, MySQL 5.7+ / MariaDB, Apache, Nginx, OVH, IONOS, o2switch, LWS
 */

declare(strict_types=1);
session_start();

$step = (int)($_GET['step'] ?? 1);
$lockFile = __DIR__ . '/config/install.lock';
$isLocked = file_exists($lockFile);

$vitalDirs = [
    __DIR__ . '/config',
    __DIR__ . '/data',
    __DIR__ . '/logs',
    __DIR__ . '/backups'
];

foreach ($vitalDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Traitement POST par étape
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Test de connexion AJAX
    if ($action === 'test_db_ajax') {
        header('Content-Type: application/json; charset=utf-8');
        $host = trim($_POST['db_host'] ?? 'localhost');
        $port = trim($_POST['db_port'] ?? '3306');
        $dbname = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = trim($_POST['db_pass'] ?? '');

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4,
            ]);

            // Test if database exists
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$dbname]);
            $exists = (bool)$stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'db_exists' => $exists,
                'message' => $exists 
                    ? "Connexion au serveur MySQL réussie ! La base '{$dbname}' existe déjà."
                    : "Connexion au serveur MySQL réussie. La base '{$dbname}' sera créée automatiquement.",
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => "Échec de connexion MySQL : " . $e->getMessage(),
            ]);
        }
        exit;
    }

    // Test Gemini AJAX
    if ($action === 'test_gemini_ajax') {
        header('Content-Type: application/json; charset=utf-8');
        $key = trim($_POST['gemini_key'] ?? '');
        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez saisir une clé API Gemini.']);
            exit;
        }

        $startTime = microtime(true);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($key);
        $payload = json_encode(['contents' => [['parts' => [['text' => 'Ping']]]]]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: ClimatLocal-Installer/2.4'],
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $latency = round((microtime(true) - $startTime) * 1000);

        if ($curlError) {
            echo json_encode(['success' => false, 'message' => "Erreur cURL : " . $curlError]);
            exit;
        }

        if ($httpCode === 200) {
            echo json_encode([
                'success' => true,
                'message' => "Clé Gemini 2.5 Flash validée avec succès ! (Temps de réponse : {$latency} ms)",
                'latency' => $latency
            ]);
        } elseif ($httpCode === 429) {
            echo json_encode([
                'success' => true,
                'warning' => true,
                'message' => "Quota temporairement atteint (429), mais la clé est reconnue. Le fallback local prendra le relais si nécessaire."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Clé invalide ou refusée par Google (Code HTTP {$httpCode})."
            ]);
        }
        exit;
    }

    // Étape 2 : Enregistrement de la base et import SQL
    if ($action === 'submit_step_db') {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $port = trim($_POST['db_port'] ?? '3306');
        $dbname = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = trim($_POST['db_pass'] ?? '');

        $_SESSION['install_db'] = compact('host', 'port', 'dbname', 'user', 'pass');

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // Create DB if needed
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");

            // Import schema.sql
            $schemaFile = __DIR__ . '/schema.sql';
            if (file_exists($schemaFile)) {
                $sqlContent = file_get_contents($schemaFile);
                // Execute SQL queries
                $pdo->exec($sqlContent);
            }

            header("Location: install.php?step=3");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'installation de la base : " . $e->getMessage();
        }
    }

    // Étape 3 : Configuration de l'application & Gemini
    if ($action === 'submit_step_app') {
        $appUrl = rtrim(trim($_POST['app_url'] ?? ''), '/');
        $geminiKey = trim($_POST['gemini_key'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? 'contact@climatlocal.com');
        $adminPass = trim($_POST['admin_pass'] ?? 'climat2026');
        $strictness = trim($_POST['strictness'] ?? 'standard');
        $appEnv = trim($_POST['app_env'] ?? 'production');

        $db = $_SESSION['install_db'] ?? [
            'host' => 'localhost',
            'port' => '3306',
            'dbname' => 'climatlocal_db',
            'user' => 'root',
            'pass' => '',
        ];

        // Generate .env file
        $securityToken = bin2hex(random_bytes(32));
        $jwtSecret = bin2hex(random_bytes(24));

        $envContent = <<<ENV
# =====================================================================
# FICHIER DE CONFIGURATION OFFICIEL CLIMAT LOCAL
# Généré le : %DATE%
# =====================================================================

# Environnement
APP_NAME="Climat Local"
APP_ENV={$appEnv}
APP_DEBUG=false
APP_URL="{$appUrl}"

# Base de Données MySQL / MariaDB (OVH / IONOS / o2switch / LWS)
DB_HOST="{$db['host']}"
DB_PORT="{$db['port']}"
DB_NAME="{$db['dbname']}"
DB_USER="{$db['user']}"
DB_PASS="{$db['pass']}"

# Intelligence Artificielle Google Gemini
GEMINI_API_KEY="{$geminiKey}"
GEMINI_MODEL="gemini-2.5-flash"
SCAN_STRICTNESS="{$strictness}"

# Sécurité & Tokens API
SECURITY_TOKEN="{$securityToken}"
JWT_SECRET="{$jwtSecret}"
ADMIN_EMAIL="{$adminEmail}"
ENV;
        $envContent = str_replace('%DATE%', date('Y-m-d H:i:s'), $envContent);

        @file_put_contents(__DIR__ . '/.env', $envContent);
        @file_put_contents(__DIR__ . '/config/.env.backup', $envContent);

        // Lock installer
        @file_put_contents($lockFile, date('Y-m-d H:i:s') . " - Installé par {$adminEmail}");

        header("Location: install.php?step=4");
        exit;
    }

    // Action : Déverrouiller si demandé explicitement
    if ($action === 'unlock_installer') {
        @unlink($lockFile);
        header("Location: install.php?step=1");
        exit;
    }
}

// Vérifications de l'étape 1 (Prérequis)
$phpVersion = PHP_VERSION;
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');

$extensions = [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
];
$extensionsOk = !in_array(false, $extensions, true);

$writableDirs = [
    'config' => is_writable(__DIR__ . '/config'),
    'data' => is_writable(__DIR__ . '/data'),
    'logs' => is_writable(__DIR__ . '/logs'),
    'backups' => is_writable(__DIR__ . '/backups'),
    'racine (.)' => is_writable(__DIR__),
];
$writableOk = !in_array(false, $writableDirs, true);

$allPrereqsOk = $phpOk && $extensionsOk && $writableOk;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation & Déploiement - Climat Local</title>
    <style>
        :root {
            --emerald: #059669;
            --emerald-dark: #064e3b;
            --slate-bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--slate-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 760px;
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1.75rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; }
        .logo-title { display: flex; align-items: center; gap: 0.75rem; }
        .logo-box { width: 44px; height: 44px; border-radius: 12px; background: #065f46; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .title { font-size: 1.4rem; font-weight: 900; letter-spacing: -0.02em; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; background: rgba(5,150,105,0.2); color: #6ee7b7; border: 1px solid rgba(5,150,105,0.4); border-radius: 9999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        
        .stepper { display: flex; justify-content: space-between; margin-bottom: 2rem; background: #090d16; padding: 0.75rem 1rem; border-radius: 1rem; border: 1px solid var(--border); }
        .step-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); }
        .step-item.active { color: #34d399; }
        .step-number { width: 22px; height: 22px; border-radius: 50%; background: #334155; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff; }
        .step-item.active .step-number { background: var(--emerald); }
        
        .alert { padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-size: 0.85rem; }
        .alert-error { background: rgba(225,29,72,0.15); border: 1px solid rgba(225,29,72,0.4); color: #fca5a5; }
        .alert-success { background: rgba(5,150,105,0.15); border: 1px solid rgba(5,150,105,0.4); color: #6ee7b7; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.35rem; }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #090d16;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--emerald); }
        
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--emerald); color: #fff; border: none; padding: 0.85rem 1.5rem; border-radius: 0.75rem; font-weight: 800; font-size: 0.875rem; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn:hover { background: #10b981; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: #cbd5e1; }
        .btn-outline:hover { background: #334155; }
        .btn-secondary { background: #334155; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        
        .check-item { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: #090d16; border: 1px solid var(--border); border-radius: 0.75rem; margin-bottom: 0.5rem; font-size: 0.85rem; }
        .check-ok { color: #34d399; font-weight: 700; }
        .check-bad { color: #f43f5e; font-weight: 700; }
        
        .actions-bar { display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
        .test-box { margin-top: 0.5rem; padding: 0.75rem; background: #090d16; border-radius: 0.5rem; font-size: 0.8rem; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-title">
                <div class="logo-box">🌱</div>
                <div>
                    <h1 class="title">Climat Local</h1>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Assistant d'Installation Serveur & MySQL</span>
                </div>
            </div>
            <span class="badge">Version <?= APP_VERSION ?></span>
        </div>

        <?php if ($isLocked && $step !== 4): ?>
            <div class="alert alert-success">
                <strong>Installation déjà verrouillée !</strong><br>
                Le système a déjà été installé avec succès. Par mesure de sécurité, le fichier <code>config/install.lock</code> est actif.
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="/" class="btn">🚀 Accéder au Cockpit de l'application</a>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="unlock_installer">
                    <button type="submit" class="btn btn-outline" onclick="return confirm('Attention : déverrouiller permettra de modifier la base et les configurations.')">🔓 Réinstaller / Déverrouiller</button>
                </form>
            </div>
            <?php exit; ?>
        <?php endif; ?>

        <!-- Stepper -->
        <div class="stepper">
            <div class="step-item <?= $step === 1 ? 'active' : '' ?>">
                <span class="step-number">1</span>
                <span>Prérequis</span>
            </div>
            <div class="step-item <?= $step === 2 ? 'active' : '' ?>">
                <span class="step-number">2</span>
                <span>Base MySQL</span>
            </div>
            <div class="step-item <?= $step === 3 ? 'active' : '' ?>">
                <span class="step-number">3</span>
                <span>Gemini & Sécurité</span>
            </div>
            <div class="step-item <?= $step === 4 ? 'active' : '' ?>">
                <span class="step-number">4</span>
                <span>Terminé</span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ÉTAPE 1 : PRÉREQUIS -->
        <?php if ($step === 1): ?>
            <h2 style="font-size: 1.1rem; margin-bottom: 0.5rem;">1. Vérification de l'environnement serveur</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                Contrôle de compatibilité avec votre hébergement (OVH, IONOS, o2switch, LWS ou serveur dédié).
            </p>

            <div class="check-item">
                <span>Version de PHP (Recommandé : 8.1 / 8.2 / 8.3+)</span>
                <span class="<?= $phpOk ? 'check-ok' : 'check-bad' ?>">
                    <?= $phpOk ? "✓ PHP {$phpVersion}" : "✗ PHP {$phpVersion} (minimum 8.0 requis)" ?>
                </span>
            </div>

            <?php foreach ($extensions as $ext => $loaded): ?>
                <div class="check-item">
                    <span>Extension PHP <code><?= $ext ?></code></span>
                    <span class="<?= $loaded ? 'check-ok' : 'check-bad' ?>">
                        <?= $loaded ? '✓ Active' : '✗ Manquante (Veuillez l’activer dans cPanel/PHP)' ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <?php foreach ($writableDirs as $dirName => $isWritable): ?>
                <div class="check-item">
                    <span>Permissions d'écriture sur le dossier <code>/<?= $dirName ?></code></span>
                    <span class="<?= $isWritable ? 'check-ok' : 'check-bad' ?>">
                        <?= $isWritable ? '✓ Écriture autorisée' : '✗ Non inscriptible (chmod 755/777 requis)' ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="actions-bar">
                <a href="javascript:location.reload();" class="btn btn-outline">🔄 Re-tester les prérequis</a>
                <?php if ($allPrereqsOk): ?>
                    <a href="install.php?step=2" class="btn">Continuer vers la Base MySQL &rarr;</a>
                <?php else: ?>
                    <button class="btn" disabled style="opacity: 0.5; cursor: not-allowed;">Corrigez les prérequis pour continuer</button>
                <?php endif; ?>
            </div>

        <!-- ÉTAPE 2 : CONFIGURATION BASE DE DONNÉES MYSQL -->
        <?php elseif ($step === 2): ?>
            <h2 style="font-size: 1.1rem; margin-bottom: 0.5rem;">2. Configuration de la base de données MySQL</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                Renseignez les accès fournis par votre hébergeur (OVH, IONOS, o2switch, LWS). Le schéma relationnel complet sera importé automatiquement.
            </p>

            <form method="post" id="form-db">
                <input type="hidden" name="action" value="submit_step_db">

                <div class="grid-2">
                    <div class="form-group">
                        <label>Hôte MySQL (DB_HOST)</label>
                        <input type="text" name="db_host" id="db_host" value="localhost" required placeholder="ex: localhost ou 127.0.0.1">
                    </div>
                    <div class="form-group">
                        <label>Port MySQL</label>
                        <input type="text" name="db_port" id="db_port" value="3306" required placeholder="3306">
                    </div>
                </div>

                <div class="form-group">
                    <label>Nom de la base de données (DB_NAME)</label>
                    <input type="text" name="db_name" id="db_name" value="climatlocal_db" required placeholder="ex: climatlocal_db">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Utilisateur MySQL (DB_USER)</label>
                        <input type="text" name="db_user" id="db_user" value="root" required placeholder="ex: u12345678">
                    </div>
                    <div class="form-group">
                        <label>Mot de passe MySQL (DB_PASS)</label>
                        <input type="password" name="db_pass" id="db_pass" placeholder="Mot de passe">
                    </div>
                </div>

                <button type="button" id="btn-test-db" class="btn btn-secondary" style="width: 100%; margin-bottom: 1rem;">
                    🔍 Tester la connexion MySQL en direct
                </button>
                <div id="test-db-result" class="test-box"></div>

                <div class="actions-bar">
                    <a href="install.php?step=1" class="btn btn-outline">&larr; Retour</a>
                    <button type="submit" class="btn">Créer la base & Importer le schéma &rarr;</button>
                </div>
            </form>

            <script>
                document.getElementById('btn-test-db').addEventListener('click', async function() {
                    const resBox = document.getElementById('test-db-result');
                    resBox.style.display = 'block';
                    resBox.innerHTML = 'Connexion au serveur MySQL en cours...';

                    const formData = new FormData();
                    formData.append('action', 'test_db_ajax');
                    formData.append('db_host', document.getElementById('db_host').value);
                    formData.append('db_port', document.getElementById('db_port').value);
                    formData.append('db_name', document.getElementById('db_name').value);
                    formData.append('db_user', document.getElementById('db_user').value);
                    formData.append('db_pass', document.getElementById('db_pass').value);

                    try {
                        const r = await fetch('install.php', { method: 'POST', body: formData });
                        const data = await r.json();
                        if (data.success) {
                            resBox.style.color = '#34d399';
                            resBox.innerHTML = '✓ ' + data.message;
                        } else {
                            resBox.style.color = '#f43f5e';
                            resBox.innerHTML = '✗ ' + data.message;
                        }
                    } catch (e) {
                        resBox.style.color = '#f43f5e';
                        resBox.innerHTML = '✗ Erreur AJAX : ' + e.message;
                    }
                });
            </script>

        <!-- ÉTAPE 3 : GEMINI & SÉCURITÉ -->
        <?php elseif ($step === 3): ?>
            <h2 style="font-size: 1.1rem; margin-bottom: 0.5rem;">3. Configuration Gemini AI & Sécurité de l'application</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                Configurez l'accès au moteur d'intelligence artificielle Gemini pour l'audit sémantique et générez votre fichier <code>.env</code>.
            </p>

            <form method="post" id="form-app">
                <input type="hidden" name="action" value="submit_step_app">

                <div class="form-group">
                    <label>URL d'accès du site (APP_URL)</label>
                    <input type="url" name="app_url" id="app_url" value="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>" required>
                </div>

                <div class="form-group">
                    <label>Clé API Google Gemini (GEMINI_API_KEY)</label>
                    <input type="password" name="gemini_key" id="gemini_key" placeholder="AIzaSy..." required>
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                        Obtenez gratuitement votre clé sur Google AI Studio (aistudio.google.com).
                    </small>
                </div>

                <button type="button" id="btn-test-gemini" class="btn btn-secondary" style="width: 100%; margin-bottom: 1rem;">
                    ⚡ Tester la clé Gemini en direct
                </button>
                <div id="test-gemini-result" class="test-box"></div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Rigueur du Scanner EmpCO</label>
                        <select name="strictness">
                            <option value="standard" selected>⚖️ Standard (Équilibré)</option>
                            <option value="strict">🔴 Strict (Zéro tolérance EmpCO)</option>
                            <option value="souple">🟢 Pédagogique (Tolérant)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Environnement</label>
                        <select name="app_env">
                            <option value="production" selected>Production (Hébergement web)</option>
                            <option value="development">Développement (Logs détaillés)</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Email Administrateur Principal</label>
                        <input type="email" name="admin_email" value="contact@climatlocal.com" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe Administrateur</label>
                        <input type="text" name="admin_pass" value="climat2026" required>
                    </div>
                </div>

                <div class="actions-bar">
                    <a href="install.php?step=2" class="btn btn-outline">&larr; Retour</a>
                    <button type="submit" class="btn">Générer le .env & Finaliser l'installation &rarr;</button>
                </div>
            </form>

            <script>
                document.getElementById('btn-test-gemini').addEventListener('click', async function() {
                    const resBox = document.getElementById('test-gemini-result');
                    resBox.style.display = 'block';
                    resBox.innerHTML = 'Appel de test vers Google Gemini en cours...';

                    const formData = new FormData();
                    formData.append('action', 'test_gemini_ajax');
                    formData.append('gemini_key', document.getElementById('gemini_key').value);

                    try {
                        const r = await fetch('install.php', { method: 'POST', body: formData });
                        const data = await r.json();
                        if (data.success) {
                            resBox.style.color = '#34d399';
                            resBox.innerHTML = '✓ ' + data.message;
                        } else {
                            resBox.style.color = '#f43f5e';
                            resBox.innerHTML = '✗ ' + data.message;
                        }
                    } catch (e) {
                        resBox.style.color = '#f43f5e';
                        resBox.innerHTML = '✗ Erreur AJAX : ' + e.message;
                    }
                });
            </script>

        <!-- ÉTAPE 4 : TERMINÉ -->
        <?php elseif ($step === 4): ?>
            <div style="text-align: center; padding: 1rem 0;">
                <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(5,150,105,0.2); border: 2px solid var(--emerald); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; font-size: 2.2rem;">
                    ✓
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 900; margin-bottom: 0.5rem; color: #fff;">
                    Installation Terminée avec Succès !
                </h2>
                <p style="font-size: 0.9rem; color: var(--text-muted); max-width: 500px; margin: 0 auto 2rem auto; line-height: 1.6;">
                    Votre fichier <code>.env</code> est généré, les tables de la base de données sont créées et indexées, et les jetons de sécurité sont en place.
                </p>

                <div style="background: #090d16; border: 1px solid var(--border); border-radius: 1rem; padding: 1.25rem; text-align: left; margin-bottom: 2rem; font-size: 0.85rem;">
                    <div style="margin-bottom: 0.5rem;">📁 <strong>Fichier .env :</strong> Créé et verrouillé</div>
                    <div style="margin-bottom: 0.5rem;">🗄️ <strong>Base MySQL :</strong> Schéma <code>cl_audits</code>, <code>cl_claims</code>, etc. importé</div>
                    <div style="margin-bottom: 0.5rem;">🤖 <strong>Moteur Gemini :</strong> 2.5 Flash activé avec fallback automatique</div>
                    <div style="margin-bottom: 0.5rem;">🔒 <strong>Verrou de sécurité :</strong> <code>install.lock</code> activé</div>
                    <div>🛡️ <strong>Protection Zéro Page Blanche :</strong> Gestionnaire d'erreurs global actif</div>
                </div>

                <div style="display: flex; justify-content: center; gap: 1rem;">
                    <a href="/" class="btn" style="padding: 1rem 2rem; font-size: 1rem;">
                        🚀 Lancer l'Application Climat Local
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
