<?php
// /api/includes/groq.php
// Connecteur API Groq REST

define('GROQ_MODEL_EXTRACTION', 'llama-3.1-70b');
define('GROQ_MODEL_SCORING', 'allam-2-7b');
define('GROQ_MODEL_SYNTHESIS', 'mixtral-8x7b');

function call_groq($payload = [], $apiKey = '') {
    if (empty($apiKey)) {
        $apiKey = defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? ''));
    }

    if (empty($apiKey)) {
        return "Erreur Moteur IA : Aucune clé GROQ_API_KEY configurée";
    }

    if (empty($payload['model'])) {
        $payload['model'] = 'llama-3.3-70b-versatile';
    }

    $endpoint = "https://api.groq.com/openai/v1/chat/completions";
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . trim($apiKey),
        'Accept: application/json'
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_USERAGENT      => 'GreenHumble-Groq-Client/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300 && $response) {
        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if ($content) {
            return $content;
        }
        return $response;
    }

    return "Erreur Moteur IA Groq (HTTP $httpCode) : " . ($curlErr ?: $response);
}

function execute_groq_api_request($endpoint, $payload, $apiKey, $customHeaders = []) {
    return call_groq($payload, $apiKey);
}

function execute_groq_combo_task($taskType, $prompt, $apiKey, $systemPrompt = null) {
    $messages = [];
    if (!empty($systemPrompt)) {
        $messages[] = ["role" => "system", "content" => $systemPrompt];
    }
    $messages[] = ["role" => "user", "content" => $prompt];

    $payload = [
        "model" => GROQ_MODEL_SYNTHESIS,
        "messages" => $messages,
        "temperature" => 0.2,
        "response_format" => ["type" => "json_object"]
    ];

    return call_groq($payload, $apiKey);
}

function groq_call($prompt, $systemInstruction = '', $model = 'llama-3.3-70b-versatile', $apiKey = '') {
    $messages = [];
    if (!empty($systemInstruction)) {
        $messages[] = ["role" => "system", "content" => $systemInstruction];
    }
    $messages[] = ["role" => "user", "content" => $prompt];

    $payload = [
        "model" => $model ?: 'llama-3.3-70b-versatile',
        "messages" => $messages,
        "temperature" => 0.2,
        "response_format" => ["type" => "json_object"]
    ];

    return call_groq($payload, $apiKey);
}
