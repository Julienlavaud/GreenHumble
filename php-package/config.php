<?php
/**
 * CLIMAT LOCAL - CONFIGURATION CENTRALE & GESTIONNAIRE D'ERREURS (PHP 8.x)
 * Fichier : config.php
 * Compatible : OVH, IONOS, o2switch, LWS, Apache & Nginx
 */

declare(strict_types=1);

// 1. Définition des constantes de base
define('APP_NAME', 'Climat Local');
define('APP_VERSION', '2.4.0');
define('ROOT_DIR', __DIR__);
define('CONFIG_DIR', ROOT_DIR . '/config');
define('DATA_DIR', ROOT_DIR . '/data');
define('LOGS_DIR', ROOT_DIR . '/logs');
define('BACKUPS_DIR', ROOT_DIR . '/backups');

// 2. Création automatique des répertoires vitaux s'ils sont manquants
$vitalDirs = [CONFIG_DIR, DATA_DIR, LOGS_DIR, BACKUPS_DIR];
foreach ($vitalDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 3. Chargement sécurisé du fichier .env
function load_env(string $filePath): array {
    $env = [];
    if (file_exists($filePath)) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                $env[$key] = $val;
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $val;
                    putenv("{$key}={$val}");
                }
            }
        }
    }
    return $env;
}

$envVars = load_env(ROOT_DIR . '/.env');

// 4. Configuration Environnement & Débogage
$appEnv = $envVars['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
$isDebug = ($appEnv === 'development' || ($envVars['APP_DEBUG'] ?? 'false') === 'true');

if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
ini_set('error_log', LOGS_DIR . '/error.log');

// 5. Gestionnaire global d'exceptions et d'erreurs (Zéro Page Blanche)
set_exception_handler(function (Throwable $e) use ($isDebug) {
    write_app_log('CRITICAL', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if (php_sapi_name() === 'cli' || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $isDebug ? $e->getMessage() : 'Une anomalie temporaire est survenue sur le serveur.',
            'code' => 500,
            'trace' => $isDebug ? $e->getTrace() : null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Page HTML stylée anti-page blanche
    http_response_code(500);
    echo render_styled_error_page($e->getMessage(), $isDebug ? $e->getTraceAsString() : null);
    exit;
});

// 6. Fonction de journalisation unifiée (Logs)
function write_app_log(string $level, string $message, array $context = []): void {
    $logFile = LOGS_DIR . '/app.log';
    if ($level === 'ERROR' || $level === 'CRITICAL') {
        $logFile = LOGS_DIR . '/error.log';
    }
    $date = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    $entry = "[{$date}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// 7. Connexion PDO MySQL avec reconnexion et gestion propre
function get_db_connection(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'climatlocal_db';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        write_app_log('ERROR', 'Échec de connexion MySQL : ' . $e->getMessage(), ['host' => $host, 'db' => $dbname]);
        return null;
    }
}

// 8. Test de connexion à Gemini
function test_gemini_api(?string $key = null): array {
    $apiKey = $key ?: ($_ENV['GEMINI_API_KEY'] ?? '');
    if (empty($apiKey)) {
        return [
            'success' => false,
            'status' => 'missing_key',
            'message' => 'Clé GEMINI_API_KEY non configurée dans le fichier .env',
        ];
    }

    $startTime = microtime(true);
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey);
    $payload = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Ping de test. Répondez simplement par le mot OK.']
                ]
            ]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: ClimatLocal-Installer/2.4'
        ],
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    $latencyMs = round((microtime(true) - $startTime) * 1000);

    if (!empty($curlError)) {
        return [
            'success' => false,
            'status' => 'network_error',
            'message' => 'Erreur de connexion réseau vers Google Gemini : ' . $curlError,
            'latency_ms' => $latencyMs,
        ];
    }

    if ($httpCode === 200) {
        return [
            'success' => true,
            'status' => 'connected',
            'message' => 'Connexion à l’API Gemini 2.5 Flash validée avec succès.',
            'latency_ms' => $latencyMs,
            'http_code' => 200,
        ];
    } elseif ($httpCode === 400 || $httpCode === 403) {
        return [
            'success' => false,
            'status' => 'invalid_key',
            'message' => 'Clé d’API Gemini invalide ou droits insuffisants (Code HTTP ' . $httpCode . ').',
            'latency_ms' => $latencyMs,
            'http_code' => $httpCode,
        ];
    } elseif ($httpCode === 429) {
        return [
            'success' => false,
            'status' => 'quota_exceeded',
            'message' => 'Quota d’appels API Gemini atteint (Rate Limit 429). Le moteur basculera sur le fallback réglementaire local.',
            'latency_ms' => $latencyMs,
            'http_code' => 429,
        ];
    }

    return [
        'success' => false,
        'status' => 'http_error',
        'message' => 'Réponse inattendue de l’API Gemini (Code HTTP ' . $httpCode . ').',
        'latency_ms' => $latencyMs,
        'http_code' => $httpCode,
    ];
}

// 9. Rendu d'une page d'erreur graphique élégante (Anti-Page Blanche)
function render_styled_error_page(string $message, ?string $trace = null): string {
    $cleanMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $cleanTrace = $trace ? htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') : '';
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Système Intercepté - Climat Local</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 2rem; display: flex; align-items: center; justify-content: center; min-height: 100vh; box-sizing: border-box; }
        .card { max-width: 640px; width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 1.5rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; background: rgba(244,63,94,0.2); color: #fda4af; border: 1px solid rgba(244,63,94,0.4); border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        h1 { margin: 0 0 1rem 0; font-size: 1.5rem; color: #ffffff; }
        p { color: #94a3b8; font-size: 0.875rem; line-height: 1.6; margin: 0 0 1.5rem 0; }
        .error-box { background: #090d16; border: 1px solid #1e293b; border-radius: 1rem; padding: 1rem; font-family: monospace; font-size: 0.8rem; color: #fca5a5; word-break: break-all; margin-bottom: 1.5rem; }
        .btn { display: inline-block; background: #059669; color: #ffffff; text-decoration: none; padding: 0.75rem 1.25rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.875rem; transition: background 0.2s; }
        .btn:hover { background: #10b981; }
        .btn-outline { background: transparent; border: 1px solid #475569; color: #cbd5e1; margin-left: 0.5rem; }
        .btn-outline:hover { background: #334155; color: #fff; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Sécurité & Continuité de Service</span>
        <h1>Incident Serveur Intercepté avec Succès</h1>
        <p>Une interruption a été captée par le gestionnaire d'intégrité de Climat Local pour prévenir toute page blanche. Vos bases de données et fichiers de configuration sont protégés.</p>
        <div class="error-box">{$cleanMessage}</div>
        <div>
            <a href="javascript:location.reload();" class="btn">🔄 Recharger la page</a>
            <a href="/install.php" class="btn btn-outline">⚙️ Assistant de Diagnostic / Réparation</a>
        </div>
    </div>
</body>
</html>
HTML;
}
