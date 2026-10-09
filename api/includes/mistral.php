<?php
// Mistral AI REST connector for GreenHumble.
function call_mistral($payload = [], $apiKey = '') {
    if (empty($apiKey)) {
        $apiKey = defined('MISTRAL_API_KEY') && !empty(MISTRAL_API_KEY) ? MISTRAL_API_KEY : (getenv('MISTRAL_API_KEY') ?: ($_ENV['MISTRAL_API_KEY'] ?? ''));
    }
    if (empty($apiKey)) return 'Erreur Mistral : clé MISTRAL_API_KEY absente';
    if (empty($payload['model'])) $payload['model'] = 'mistral-small-latest';
    if (empty($payload['messages']) || !is_array($payload['messages'])) return 'Erreur Mistral : messages manquants';
    $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . trim($apiKey), 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'GreenHumble-Mistral-Client/1.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if (!is_string($response) || $response === '' || $httpCode < 200 || $httpCode >= 300) return 'Erreur Mistral : ' . ($curlErr ?: ('HTTP ' . $httpCode));
    $data = json_decode($response, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) return 'Erreur Mistral : réponse fournisseur invalide';
    $text = $data['choices'][0]['message']['content'] ?? null;
    return is_string($text) && trim($text) !== '' ? trim($text) : 'Erreur Mistral : contenu de réponse absent';
}
function mistral_call($prompt, $systemInstruction = '', $model = 'mistral-small-latest', $apiKey = '') {
    $messages = [];
    if (is_string($systemInstruction) && trim($systemInstruction) !== '') $messages[] = ['role' => 'system', 'content' => $systemInstruction];
    $messages[] = ['role' => 'user', 'content' => $prompt];
    return call_mistral(['model' => $model ?: 'mistral-small-latest', 'messages' => $messages, 'temperature' => 0.2, 'response_format' => ['type' => 'json_object']], $apiKey);
}
