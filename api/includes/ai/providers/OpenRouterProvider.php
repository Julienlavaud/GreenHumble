<?php

require_once __DIR__ . '/../AIProviderInterface.php';
require_once __DIR__ . '/../AIProviderRegistry.php';
require_once __DIR__ . '/../../app_state.php';

final class OpenRouterProvider implements AIProviderInterface
{
    private string $endpoint =
        'https://openrouter.ai/api/v1/chat/completions';

    public function getName(): string
    {
        return 'openrouter';
    }

    public function getLabel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['label']
            ?? 'OpenRouter';
    }

    public function getDefaultModel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['default_model']
            ?? 'openrouter/free';
    }

    public function getModels(): array
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['models']
            ?? [];
    }

    public function getApiKey(): ?string
    {
        $state = load_app_state();

        $scanVars =
            $state['scanVars']
            ?? [];

        if (
            !empty(
                $scanVars['openRouterApiKey']
            )
        ) {
            return (string)
                $scanVars['openRouterApiKey'];
        }

        if (
            defined('OPENROUTER_API_KEY')
            && !empty(OPENROUTER_API_KEY)
        ) {
            return OPENROUTER_API_KEY;
        }

        $env =
            getenv(
                'OPENROUTER_API_KEY'
            );

        if (!empty($env)) {
            return $env;
        }

        return null;
    }

    public function hasApiKey(): bool
    {
        return !empty(
            $this->getApiKey()
        );
    }

    public function testConnection(): bool
    {
        try {

            $response =
                $this->generate(
                    'Réponds uniquement OK',
                    'Réponds uniquement OK',
                    $this->getDefaultModel()
                );

            return
                is_string($response)
                && trim($response) !== '';

        } catch (Throwable $e) {

            error_log(
                'OpenRouter test failed: '
                . $e->getMessage()
            );

            return false;
        }
    }

    public function generate(
        string $prompt,
        string $systemPrompt,
        ?string $model = null
    ): string {

        $apiKey =
            $this->getApiKey();

        if (empty($apiKey)) {
            return
                'Erreur : clé API OpenRouter absente.';
        }

        $selectedModel =
            $model
            ?: $this->getDefaultModel();

        if (
            !in_array(
                $selectedModel,
                $this->getModels(),
                true
            )
        ) {
            $selectedModel =
                $this->getDefaultModel();
        }

        $payload = [

            'model' =>
                $selectedModel,

            'messages' => [

                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],

                [
                    'role' => 'user',
                    'content' => $prompt
                ]

            ],

            'temperature' => 0.2

        ];

        $ch = curl_init();

        curl_setopt_array(
            $ch,
            [

                CURLOPT_URL =>
                    $this->endpoint,

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_POST =>
                    true,

                CURLOPT_TIMEOUT =>
                    90,

                CURLOPT_CONNECTTIMEOUT =>
                    15,

                CURLOPT_SSL_VERIFYPEER =>
                    true,

                CURLOPT_SSL_VERIFYHOST =>
                    2,

                CURLOPT_HTTPHEADER => [

                    'Authorization: Bearer '
                    . $apiKey,

                    'Content-Type: application/json',

                    'HTTP-Referer: https://greenhumble.com',

                    'X-Title: GreenHumble'

                ],

                CURLOPT_POSTFIELDS =>
                    json_encode(
                        $payload,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    )

            ]
        );

        $response =
            curl_exec($ch);

        $httpCode =
            (int) curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        $error =
            curl_error($ch);

        curl_close($ch);

        if (!empty($error)) {

            return
                'Erreur OpenRouter : '
                . $error;
        }

        if (
            $httpCode < 200
            || $httpCode >= 300
        ) {

            return
                'HTTP '
                . $httpCode
                . ' OpenRouter';
        }

        $json =
            json_decode(
                (string) $response,
                true
            );

        if (!is_array($json)) {

            return
                'Erreur OpenRouter : réponse JSON invalide.';
        }

        return
            (string) (
                $json['choices'][0]['message']['content']
                ?? ''
            );
    }
}
