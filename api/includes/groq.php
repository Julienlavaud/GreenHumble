<?php

// /api/includes/groq.php

define(
    'GROQ_MODEL_EXTRACTION',
    'openai/gpt-oss-120b'
);

define(
    'GROQ_MODEL_SCORING',
    'openai/gpt-oss-20b'
);

define(
    'GROQ_MODEL_SYNTHESIS',
    'openai/gpt-oss-120b'
);

function call_groq(
    array $payload = [],
    string $apiKey = ''
): string {

    if (empty($apiKey)) {

        $apiKey =
            defined('GROQ_API_KEY')
            && !empty(GROQ_API_KEY)
                ? GROQ_API_KEY
                : (
                    getenv('GROQ_API_KEY')
                    ?: ($_ENV['GROQ_API_KEY'] ?? '')
                );
    }

    if (empty($apiKey)) {

        return
            'Erreur Moteur IA : aucune clé GROQ_API_KEY configurée';
    }

    if (empty($payload['model'])) {

        $payload['model'] =
            'openai/gpt-oss-120b';
    }

    $endpoint =
        'https://api.groq.com/openai/v1/chat/completions';

    $headers = [

        'Content-Type: application/json',

        'Accept: application/json',

        'Authorization: Bearer '
        . trim($apiKey)

    ];

    $ch = curl_init($endpoint);

    curl_setopt_array(
        $ch,
        [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_HTTPHEADER => $headers,

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
                'GreenHumble-Groq-Client/2.0'

        ]
    );

    $response = curl_exec($ch);

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
            'Erreur Groq : '
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
            'Erreur Groq : '
            . $json['error']['message'];
    }

    if (
        $httpCode < 200
        || $httpCode >= 300
    ) {

        return
            'HTTP '
            . $httpCode
            . ' Groq';
    }

    $content =
        $json['choices'][0]['message']['content']
        ?? '';

    if (
        !is_string($content)
        || trim($content) === ''
    ) {

        return
            'Erreur Groq : réponse vide.';
    }

    return trim($content);
}

function execute_groq_api_request(
    $endpoint,
    $payload,
    $apiKey,
    $customHeaders = []
): string {

    return call_groq(
        $payload,
        $apiKey
    );
}

function execute_groq_combo_task(
    $taskType,
    $prompt,
    $apiKey,
    $systemPrompt = null
): string {

    $messages = [];

    if (!empty($systemPrompt)) {

        $messages[] = [

            'role' => 'system',

            'content' => $systemPrompt

        ];
    }

    $messages[] = [

        'role' => 'user',

        'content' => $prompt

    ];

    $payload = [

        'model' =>
            GROQ_MODEL_SYNTHESIS,

        'messages' =>
            $messages,

        'temperature' => 0.2

    ];

    return call_groq(
        $payload,
        $apiKey
    );
}

function groq_call(
    string $prompt,
    string $systemInstruction = '',
    string $model = 'openai/gpt-oss-120b',
    string $apiKey = ''
): string {

    $messages = [];

    if (!empty($systemInstruction)) {

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
            ?: 'openai/gpt-oss-120b',

        'messages' =>
            $messages,

        'temperature' => 0.2

    ];

    return call_groq(
        $payload,
        $apiKey
    );
}
