<?php
// /api/includes/gemini.php
// Connecteur API Gemini REST avec cascade de modèles (gemini-3.8-flash, gemini-2.5-flash, gemini-2.0-flash)

function call_gemini($model = 'gemini-3.8-flash', $payload = [], $apiKey = '') {
    if (empty($apiKey)) {
        $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? GEMINI_API_KEY : (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ''));
    }

    if (empty($apiKey)) {
        return "Erreur Moteur IA : Aucune clé GEMINI_API_KEY configurée dans /api/config/.env";
    }

    $modelsCascade = array_unique([
        !empty($model) ? trim($model) : 'gemini-3.8-flash',
        'gemini-3.8-flash',
        'gemini-2.5-flash',
        'gemini-2.0-flash'
    ]);

    $lastError = '';

    foreach ($modelsCascade as $currentModel) {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($currentModel) . ":generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_USERAGENT      => 'GreenHumble-AI-Client/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text) {
                return $text;
            }
            return $response;
        }

        $lastError = "HTTP $httpCode : " . ($curlErr ?: $response);
    }

    return "Erreur Moteur IA Gemini : " . $lastError;
}

function execute_gemini_api_request($endpoint, $payload, $apiKey, $customHeaders = []) {
    return call_gemini('gemini-3.8-flash', $payload, $apiKey);
}

function gemini_call($prompt, $systemInstruction = '', $model = 'gemini-3.8-flash', $apiKey = '') {
    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "systemInstruction" => ["parts" => [["text" => $systemInstruction]]],
        "generationConfig" => ["responseMimeType" => "application/json", "temperature" => 0.2]
    ];
    return call_gemini($model, $payload, $apiKey);
}

function analyze_url_with_gemini($url, $custom_prompt = null, $csrDocumentText = null) {
    if (function_exists('ai_engine_run')) {
        return ai_engine_run($url, $custom_prompt, $csrDocumentText);
    }
    return json_encode(['score' => 60, 'summary' => 'Analyse IA initialisée.']);
}
