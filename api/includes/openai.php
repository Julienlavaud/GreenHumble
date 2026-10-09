<?php
// Connecteur OpenAI Chat Completions pour GreenHumble.
function call_openai($payload = [], $apiKey = '') {
    if (empty($apiKey)) {
        $apiKey = defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) ? OPENAI_API_KEY : (getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? ''));
    }
    if (empty($apiKey)) return 'Erreur OpenAI : clé OPENAI_API_KEY absente';
    if (empty($payload['model'])) $payload['model'] = 'gpt-4.1-mini';
    if (empty($payload['messages']) || !is_array($payload['messages'])) return 'Erreur OpenAI : messages manquants';
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . trim($apiKey), 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'GreenHumble-OpenAI-Client/1.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if (!is_string($response) || $response === '' || $httpCode < 200 || $httpCode >= 300) {
        return 'Erreur OpenAI : ' . ($curlErr ?: ('HTTP ' . $httpCode));
    }
    $data = json_decode($response, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) return 'Erreur OpenAI : réponse fournisseur invalide';
    $content = $data['choices'][0]['message']['content'] ?? null;
    return is_string($content) && trim($content) !== '' ? trim($content) : 'Erreur OpenAI : contenu de réponse absent';
}
function openai_call($prompt, $systemInstruction = '', $model = 'gpt-4.1-mini', $apiKey = '') {
    $messages = [];
    if (is_string($systemInstruction) && trim($systemInstruction) !== '') $messages[] = ['role' => 'system', 'content' => $systemInstruction];
    $messages[] = ['role' => 'user', 'content' => $prompt];
    return call_openai(['model' => $model ?: 'gpt-4.1-mini', 'messages' => $messages, 'temperature' => 0.2, 'response_format' => ['type' => 'json_object']], $apiKey);
}
