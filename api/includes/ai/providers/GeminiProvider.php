<?php

require_once __DIR__ . '/../AIProviderInterface.php';
require_once __DIR__ . '/../AIProviderRegistry.php';
require_once __DIR__ . '/../../gemini.php';
require_once __DIR__ . '/../../app_state.php';

final class GeminiProvider implements AIProviderInterface
{
    public function getName(): string
    {
        return 'gemini';
    }

    public function getLabel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['label']
            ?? 'Google Gemini';
    }

    public function getDefaultModel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['default_model']
            ?? 'gemini-flash-latest';
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
                $scanVars['geminiApiKey']
            )
        ) {
            return (string)
                $scanVars['geminiApiKey'];
        }

        if (
            defined('GEMINI_API_KEY')
            && !empty(GEMINI_API_KEY)
        ) {
            return GEMINI_API_KEY;
        }

        $env =
            getenv('GEMINI_API_KEY');

        if (!empty($env)) {
            return $env;
        }

        $legacy =
            getenv('AI_API_KEY');

        if (!empty($legacy)) {
            return $legacy;
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
        $apiKey =
            $this->getApiKey();

        if (empty($apiKey)) {
            return false;
        }

        try {

            $reply = call_gemini(
                $this->getDefaultModel(),
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' =>
                                        'Réponds uniquement OK'
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0
                    ]
                ],
                $apiKey
            );

            return
                is_string($reply)
                && trim($reply) !== ''
                && stripos($reply, 'erreur') !== 0
                && stripos($reply, 'http ') === false;

        } catch (Throwable $e) {

            error_log(
                'GeminiProvider test failed: '
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
                'Erreur : clé API Gemini absente.';
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

        try {

            return gemini_call(
                $prompt,
                $systemPrompt,
                $selectedModel,
                $apiKey
            );

        } catch (Throwable $e) {

            error_log(
                'GeminiProvider generate failed: '
                . $e->getMessage()
            );

            return
                'Erreur Gemini : '
                . $e->getMessage();
        }
    }
}
