<?php
// /api/admin/save.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (($_SESSION['admin_logged_in'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Non autorisé.");
}

$envPath = __DIR__ . '/../config/.env';

// 1. Sauvegarde formulaire graphique Configuration IA & Moteur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_config'])) {
    $aiProvider = trim($_POST['ai_provider'] ?? 'gemini');
    $aiModel = trim($_POST['ai_model'] ?? 'gemini-3.8-flash');
    if (!empty($_POST['custom_model'])) {
        $aiModel = trim($_POST['custom_model']);
    }
    $aiApiKey = trim($_POST['ai_api_key'] ?? '');
    $aiCustomEndpoint = trim($_POST['ai_custom_endpoint'] ?? '');
    $aiCustomExceptions = trim($_POST['ai_custom_exceptions'] ?? '');

    // Lecture du .env existant
    $envLines = file_exists($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];
    $envData = [];
    foreach ($envLines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            $envData[trim($k)] = trim($v);
        }
    }

    $envData['AI_PROVIDER'] = $aiProvider;
    $envData['AI_MODEL'] = $aiModel;
    if (!empty($aiApiKey)) {
        $envData['AI_API_KEY'] = $aiApiKey;
        $envData['GEMINI_API_KEY'] = $aiApiKey; // Compatibilité ascendante
    }
    if (!empty($aiCustomEndpoint)) {
        $envData['AI_CUSTOM_ENDPOINT'] = $aiCustomEndpoint;
    }

    // Réécriture du fichier .env
    $newEnvContent = "";
    foreach ($envData as $k => $v) {
        $newEnvContent .= "$k=$v\n";
    }
    file_put_contents($envPath, $newEnvContent);

    // Enregistrement des exceptions d'entraînement métier dans cl_texts
    try {
        $db = get_db_connection();
        $stmt = $db->prepare("INSERT INTO cl_texts (text_key, title, content, category) VALUES ('ai_custom_exceptions', 'Règles & Exceptions Métier IA', ?, 'mots_bannis') ON DUPLICATE KEY UPDATE content = VALUES(content)");
        $stmt->execute([$aiCustomExceptions]);
    } catch (Exception $e) {}

    header('Location: /api/admin/index.php?saved=1');
    exit;
}

// 2. Sauvegarde brute .env
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['env_content'])) {
    $content = trim($_POST['env_content']);
    file_put_contents($envPath, $content);
    header('Location: /api/admin/index.php?saved=1');
    exit;
}

header('Location: /api/admin/index.php');
exit;
