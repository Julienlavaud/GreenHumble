<?php

// /api/includes/gemini.php

function call_gemini(
    string $model = 'gemini-flash-latest',
    array $payload = [],
    string $apiKey = ''
): string {

    if (empty($apiKey)) {

        $apiKey =
            defined('GEMINI_API_KEY')
            && !empty(GEMINI_API_KEY)
                ? GEMINI_API_KEY
                : (
                    getenv('GEMINI_API_KEY')
                    ?: ($_ENV['GEMINI_API_KEY'] ?? '')
                );
    }

    if (empty($apiKey)) {

        return
            'Erreur Moteur IA : aucune clé GEMINI_API_KEY configurée';
    }

    $modelsCascade = array_unique([

        !empty($model)
            ? trim($model)
            : 'gemini-flash-latest',

        'gemini-flash-latest',

        'gemini-flash-lite-latest'

    ]);

    $lastError = '';

    foreach ($modelsCascade as $currentModel) {

        $endpoint =
            'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($currentModel)
            . ':generateContent?key='
            . rawurlencode($apiKey);

        $ch = curl_init($endpoint);

        curl_setopt_array(
            $ch,
            [

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_POST => true,

                CURLOPT_HTTPHEADER => [

                    'Content-Type: application/json',
                    'Accept: application/json'

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
                    'GreenHumble-Gemini-Client/2.0'

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

            $lastError = $curlError;
            continue;
        }

        $json =
            json_decode(
                (string) $response,
                true
            );

        if (
            isset($json['error']['message'])
        ) {

            $lastError =
                $json['error']['message'];

            continue;
        }

        if (
            $httpCode >= 200
            && $httpCode < 300
        ) {

            $text =
                $json['candidates'][0]['content']['parts'][0]['text']
                ?? '';

            if (
                is_string($text)
                && trim($text) !== ''
            ) {

                return trim($text);
            }
        }

        $lastError =
            'HTTP ' . $httpCode;
    }

    return
        'Erreur Gemini : '
        . $lastError;
}

function execute_gemini_api_request(
    $endpoint,
    $payload,
    $apiKey,
    $customHeaders = []
): string {

    return call_gemini(
        'gemini-flash-latest',
        $payload,
        $apiKey
    );
}

function gemini_call(
    string $prompt,
    string $systemInstruction = '',
    string $model = 'gemini-flash-latest',
    string $apiKey = ''
): string {

    $payload = [

        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],

        'systemInstruction' => [
            'parts' => [
                [
                    'text' => $systemInstruction
                ]
            ]
        ],

        'generationConfig' => [
            'temperature' => 0.2,
            'responseMimeType' => 'application/json'
        ]

    ];

    return call_gemini(
        $model,
        $payload,
        $apiKey
    );
}

function analyze_url_with_gemini(
    $url,
    $custom_prompt = null,
    $csrDocumentText = null
) {

    if (
        function_exists(
            'ai_engine_run'
        )
    ) {
        return ai_engine_run(
            $url,
            $custom_prompt,
            $csrDocumentText
        );
    }

    return json_encode(
        [
            'status' => 'analysis_incomplete',
            'analysisComplete' => false,
            'score' => null,
            'riskLevel' => 'a_evaluer',
            'summary' => 'Moteur IA indisponible : aucune analyse réalisée.'
        ],
        JSON_UNESCAPED_UNICODE
    );
}
