<?php
// /api/config/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Recherche du fichier .env à la racine du projet ou dans /api/config
$possibleEnvPaths = [
    __DIR__ . '/.env',
    ROOT_PATH . '/.env',
    dirname(ROOT_PATH) . '/.env',
    __DIR__ . '/../.env'
];

foreach ($possibleEnvPaths as $envFile) {
    if (file_exists($envFile)) {
        // Chargement direct avec parse_ini_file ou fallback par ligne
        $parsedEnv = @parse_ini_file($envFile, false, INI_SCANNER_RAW);
        if ($parsedEnv !== false && is_array($parsedEnv)) {
            foreach ($parsedEnv as $name => $value) {
                $name = trim((string)$name);
                $value = trim((string)$value, " \t\n\r\0\x0B\"'");
                if (!defined($name)) {
                    define($name, $value);
                }
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        } else {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!defined($name)) {
                    define($name, $value);
                }
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
}

// Chargement de l'état applicatif centralisé (Priorité sur .env pour les réglages métiers)
require_once __DIR__ . '/../includes/app_state.php';
$appState = load_app_state();

// Fallback constantes par défaut ou depuis app_state.json
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'climatlocal');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Réglages IA (Priorité State > Constantes > Defaults)
$stAI = $appState['scanVars'] ?? [];
if (!defined('AI_PROVIDER')) define('AI_PROVIDER', $stAI['aiProvider'] ?? 'gemini');
if (!defined('AI_MODEL')) define('AI_MODEL', $stAI['aiModel'] ?? 'gemini-3.8-flash');
if (!defined('AI_API_KEY')) define('AI_API_KEY', $stAI['aiApiKey'] ?? '');
if (!defined('GEMINI_API_KEY')) define('GEMINI_API_KEY', defined('AI_API_KEY') ? AI_API_KEY : '');
if (!defined('ADMIN_PASSWORD_HASH')) {
    // Fail closed: no default admin password hash is shipped in source control.
    define('ADMIN_PASSWORD_HASH', '');
}
