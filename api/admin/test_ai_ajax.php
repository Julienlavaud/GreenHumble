<?php
// /api/admin/test_ai_ajax.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_engine.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['admin_logged_in'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$provider = trim($input['provider'] ?? (defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini'));
$model = trim($input['model'] ?? (defined('AI_MODEL') ? AI_MODEL : 'gemini-3.8-flash'));
$apiKey = trim($input['api_key'] ?? '');
$customEndpoint = trim($input['custom_endpoint'] ?? '');

$testPrompt = "Réponds en 2 phrases très courtes : Es-tu opérationnel pour auditer les allégations RSE selon la directive européenne EmpCO ? Précise ton nom de modèle.";

$result = analyze_with_ai('https://climatlocal.com', $testPrompt, null, $provider, $model, $apiKey ?: null, $customEndpoint ?: null);

$isError = strpos($result, 'Erreur Moteur IA') === 0 || strpos($result, 'Fournisseur d\'IA inconnu') === 0;

echo json_encode([
    'success' => !$isError,
    'provider' => $provider,
    'model' => $model,
    'raw' => $result,
    'error' => $isError ? $result : null
]);
