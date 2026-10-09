<?php
declare(strict_types=1);
// GreenHumble — assistant d'installation OVH, sans SSH.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');

// Ne jamais afficher le formulaire ni recevoir des identifiants hors HTTPS.
$installIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (!$installIsHttps) {
    http_response_code(400);
    exit('Ouvrez l’assistant via une adresse HTTPS pour protéger vos identifiants.');
}

$configDir = dirname(__DIR__) . '/config';
$envPath = $configDir . '/.env';
$existing = is_file($envPath) ? (string) @file_get_contents($envPath) : '';
if (preg_match('/^GREENHUMBLE_INSTALLATION_COMPLETED=1$/m', $existing)) {
    http_response_code(410);
    exit('GreenHumble est déjà configuré. Pour modifier les paramètres, sauvegardez et modifiez la configuration depuis le gestionnaire de fichiers OVH.');
}
if (trim($existing) !== '') {
    http_response_code(409);
    exit('Un fichier de configuration existe déjà. Par sécurité, l’assistant ne l’écrase pas. Sauvegardez-le et vérifiez la configuration actuelle depuis le gestionnaire de fichiers OVH.');
}
if (empty($_SESSION['gh_install_csrf'])) $_SESSION['gh_install_csrf'] = bin2hex(random_bytes(32));
$values = ['db_host'=>'localhost','db_name'=>'','db_user'=>'','admin_username'=>'','agency_name'=>'GreenHumble'];
$errors = [];
function gh_install_escape(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function gh_install_protect_directory(string $directory): void {
    $rule = "AuthMerging Off\nRequire all denied";
    $htaccess = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';
    $current = is_file($htaccess) ? (string) file_get_contents($htaccess) : '';
    if (stripos($current, $rule) === false) {
        $prefix = $current !== '' && substr($current, -1) !== "\n" ? "\n" : '';
        if (file_put_contents($htaccess, $prefix . $rule . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Unable to protect private directory');
        }
    }
}
function gh_install_ini_value(string $value): string {
    $value = str_replace("\\", "\\\\", $value);
    $value = str_replace('"', '\\"', $value);
    $value = str_replace(["\r", "\n"], '', $value);
    return '"' . $value . '"';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string)$_SESSION['gh_install_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Session expirée. Rechargez la page puis réessayez.';
    } else {
        foreach (array_keys($values) as $key) $values[$key] = trim((string)($_POST[$key] ?? ''));
        $dbPass = (string)($_POST['db_pass'] ?? '');
        $adminPass = (string)($_POST['admin_password'] ?? '');
        $confirm = (string)($_POST['admin_password_confirm'] ?? '');

        foreach (['db_host'=>'Hôte MySQL','db_name'=>'Nom de la base','db_user'=>'Utilisateur MySQL','admin_username'=>'Identifiant administrateur','agency_name'=>'Nom de l’organisation'] as $key=>$label) {
            if ($values[$key] === '') $errors[] = $label . ' : champ obligatoire.';
        }
        if (preg_match('/[\r\n=]/', $values['db_host'].$values['db_name'].$values['db_user'].$values['admin_username'])) $errors[] = 'Un champ contient des caractères non autorisés.';
        if (strlen($adminPass) < 12) $errors[] = 'Le mot de passe administrateur doit contenir au moins 12 caractères.';
        if (!hash_equals($adminPass, $confirm)) $errors[] = 'Les mots de passe administrateur ne correspondent pas.';
        if (!extension_loaded('pdo_mysql')) $errors[] = 'L’extension PHP pdo_mysql doit être activée dans OVH.';

        if (!$errors) {
            try {
                $dsn = 'mysql:host='.$values['db_host'].';dbname='.$values['db_name'].';charset=utf8mb4';
                $pdo = new PDO($dsn, $values['db_user'], $dbPass, [
                    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT=>5,
                    PDO::ATTR_EMULATE_PREPARES=>false
                ]);
                $pdo->query('SELECT 1')->fetchColumn();
                require_once __DIR__ . '/schema.php';
                gh_install_database_schema($pdo);
                if (!is_dir($configDir) || !is_writable($configDir)) throw new RuntimeException('Configuration folder is not writable');
                $dataDir = dirname(__DIR__, 2) . '/data';
                if (!is_dir($dataDir) && !@mkdir($dataDir, 0750, true) && !is_dir($dataDir)) {
                    throw new RuntimeException('Data folder could not be created');
                }
                if (!is_writable($dataDir)) throw new RuntimeException('Data folder is not writable');
                gh_install_protect_directory($configDir);
                gh_install_protect_directory($dataDir);

                $cleanAgency = str_replace(["\r","\n",'='], '', $values['agency_name']);
                $lines = [
                    'DB_HOST='.gh_install_ini_value($values['db_host']),
                    'DB_NAME='.gh_install_ini_value($values['db_name']),
                    'DB_USER='.gh_install_ini_value($values['db_user']),
                    'DB_PASS='.gh_install_ini_value($dbPass),
                    'GREENHUMBLE_ADMIN_USERNAME='.gh_install_ini_value($values['admin_username']),
                    'GREENHUMBLE_ADMIN_PASSWORD_HASH='.password_hash($adminPass, PASSWORD_DEFAULT),
                    'GREENHUMBLE_INSTALLATION_COMPLETED=1',
                    'AGENCY_NAME='.gh_install_ini_value($cleanAgency)
                ];
                $tmp = tempnam($configDir, '.gh-install-');
                if ($tmp === false || file_put_contents($tmp, implode("\n", $lines)."\n", LOCK_EX) === false) throw new RuntimeException('Configuration write failed');
                @chmod($tmp, 0640);
                if (!rename($tmp, $envPath)) { @unlink($tmp); throw new RuntimeException('Configuration activation failed'); }
                @chmod($envPath, 0640);
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $cookie = session_get_cookie_params();
                    setcookie(session_name(), '', [
                        'expires' => time() - 42000,
                        'path' => $cookie['path'] ?: '/',
                        'domain' => $cookie['domain'],
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }
                session_destroy();
                header('Location: /api/login.php?installed=1', true, 303);
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Connexion MySQL ou écriture impossible. Vérifiez les paramètres et les permissions du dossier api/config. Aucun détail technique sensible n’est affiché.';
            }
        }
    }
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Installation GreenHumble</title>
<style>
:root{color-scheme:light;--ink:#18332d;--muted:#61746e;--line:#d9e5df;--green:#176b52;--bg:#f4f7f5}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}main{max-width:720px;margin:36px auto;padding:0 18px}.card{background:white;border:1px solid var(--line);border-radius:18px;padding:clamp(20px,5vw,38px);box-shadow:0 10px 35px #17392b0b}h1{font-size:clamp(26px,4vw,34px);line-height:1.2;margin:0 0 8px}.intro,.small{color:var(--muted)}h2{font-size:18px;margin:25px 0 12px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:flex;flex-direction:column;gap:6px}.full{grid-column:1/-1}label{font-weight:650;font-size:14px}input{width:100%;padding:12px;border:1px solid #bacbc3;border-radius:9px;font:inherit;background:white;color:var(--ink)}input:focus{outline:3px solid #bce5d3;border-color:var(--green)}button{margin-top:22px;width:100%;border:0;border-radius:10px;padding:14px;background:var(--green);color:white;font-weight:750;font-size:16px;cursor:pointer}.note{background:#eff8f2;border-radius:10px;padding:12px 14px;font-size:14px;color:#2b5946;margin-top:18px}.errors{background:#fff0ee;border:1px solid #f1c7c1;color:#8e2e25;border-radius:10px;padding:12px 16px;margin:18px 0}.errors ul{margin:0;padding-left:20px}.small{font-size:13px;margin-top:16px}@media(max-width:560px){.grid{grid-template-columns:1fr}.full{grid-column:auto}main{margin:18px auto}}
</style></head><body><main><section class="card">
<p style="font-weight:800;letter-spacing:.08em;color:var(--green);font-size:12px">GREENHUMBLE · INSTALLATION</p><h1>Configurer votre espace</h1>
<p class="intro">Assistant d’installation pour hébergement mutualisé OVH, sans SSH. Récupérez les identifiants MySQL dans votre espace client OVH.</p>
<?php if ($errors): ?><div class="errors" role="alert"><strong>Installation non terminée</strong><ul><?php foreach ($errors as $error): ?><li><?= gh_install_escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= gh_install_escape((string)$_SESSION['gh_install_csrf']) ?>">
<h2>1. Base de données MySQL</h2><div class="grid">
<div class="field"><label for="db_host">Hôte MySQL</label><input id="db_host" name="db_host" required value="<?= gh_install_escape($values['db_host']) ?>"></div>
<div class="field"><label for="db_name">Nom de la base</label><input id="db_name" name="db_name" required value="<?= gh_install_escape($values['db_name']) ?>"></div>
<div class="field"><label for="db_user">Utilisateur MySQL</label><input id="db_user" name="db_user" required value="<?= gh_install_escape($values['db_user']) ?>"></div>
<div class="field"><label for="db_pass">Mot de passe MySQL</label><input id="db_pass" name="db_pass" type="password" required autocomplete="new-password"></div>
</div><h2>2. Accès administrateur</h2><div class="grid">
<div class="field full"><label for="admin_username">Identifiant administrateur</label><input id="admin_username" name="admin_username" required value="<?= gh_install_escape($values['admin_username']) ?>" autocomplete="username"></div>
<div class="field"><label for="admin_password">Mot de passe (12 caractères minimum)</label><input id="admin_password" name="admin_password" type="password" minlength="12" required autocomplete="new-password"></div>
<div class="field"><label for="admin_password_confirm">Confirmer le mot de passe</label><input id="admin_password_confirm" name="admin_password_confirm" type="password" minlength="12" required autocomplete="new-password"></div>
<div class="field full"><label for="agency_name">Nom de l’organisation</label><input id="agency_name" name="agency_name" required maxlength="120" value="<?= gh_install_escape($values['agency_name']) ?>"></div>
</div><div class="note"><strong>À savoir :</strong> l’assistant teste la connexion MySQL et enregistre la configuration avec un hash du mot de passe. Il ne supprime aucune table et ne migre pas la base existante. Sauvegardez la base avant toute installation.</div><button type="submit">Vérifier et installer</button></form>
<p class="small">L’assistant se verrouille après installation. Si la configuration existe déjà, il ne la remplace pas.</p>
</section></main></body></html>