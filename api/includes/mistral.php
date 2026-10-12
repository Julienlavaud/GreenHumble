<?php

// Mistral AI REST connector for GreenHumble.

function call_mistral(
    array $payload = [],
    string $apiKey = ''
): string {

    if (empty($apiKey)) {

        $apiKey =
            defined('MISTRAL_API_KEY')
            && !empty(MISTRAL_API_KEY)
                ? MISTRAL_API_KEY
                : (
                    getenv('MISTRAL_API_KEY')
                    ?: ($_ENV['MISTRAL_API_KEY'] ?? '')
                );
    }

    if (empty($apiKey)) {

        return
            'Erreur Mistral : clé MISTRAL_API_KEY absente';
    }

    if (empty($payload['model'])) {

        $payload['model'] =
            'mistral-small-latest';
    }

    if (
        empty($payload['messages'])
        || !is_array($payload['messages'])
    ) {

        return
            'Erreur Mistral : messages manquants';
    }

    $endpoint =
        'https://api.mistral.ai/v1/chat/completions';

    $ch = curl_init($endpoint);

    curl_setopt_array(
        $ch,
        [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_HTTPHEADER => [

                'Content-Type: application/json',

                'Accept: application/json',

                'Authorization: Bearer '
                . trim($apiKey)

            ],

            CURLOPT_POSTFIELDS =>
                json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ),

            CURLOPT_SSL_VERIFYPEER => true,

            CURLOPT_SSL_VERIFYHOST => 2,

            CURLOPT_CONNECTTIMEOUT => 10,

            CURLOPT_TIMEOUT => 60,

            CURLOPT_USERAGENT =>
                'GreenHumble-Mistral-Client/2.0'

        ]
    );

    $response =
        curl_exec($ch);

    $httpCode =
        (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    $curlError =
        curl_error($ch);

    curl_close($ch);

    if (!empty($curlError)) {

        return
            'Erreur Mistral : '
            . $curlError;
    }

    $json =
        json_decode(
            (string) $response,
            true
        );

    if (
        isset($json['error']['message'])
    ) {

        return
            'Erreur Mistral : '
            . $json['error']['message'];
    }

    if (
        $httpCode < 200
        || $httpCode >= 300
    ) {

        return
            'HTTP '
            . $httpCode
            . ' Mistral';
    }

    $content =
        $json['choices'][0]['message']['content']
        ?? '';

    if (
        !is_string($content)
        || trim($content) === ''
    ) {

        return
            'Erreur Mistral : réponse vide.';
    }

    return trim($content);
}

function mistral_call(
    string $prompt,
    string $systemInstruction = '',
    string $model = 'mistral-small-latest',
    string $apiKey = ''
): string {

    $messages = [];

    if (
        trim($systemInstruction) !== ''
    ) {

        $messages[] = [

            'role' => 'system',

            'content' => $systemInstruction

        ];
    }

    $messages[] = [

        'role' => 'user',

        'content' => $prompt

    ];

    $payload = [

        'model' =>
            $model
            ?: 'mistral-small-latest',

        'messages' =>
            $messages,

        'temperature' => 0.2

    ];

    return call_mistral(
        $payload,
        $apiKey
    );
}
