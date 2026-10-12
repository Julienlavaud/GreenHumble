<?php

require_once __DIR__ . '/../AIProviderInterface.php';
require_once __DIR__ . '/../AIProviderRegistry.php';
require_once __DIR__ . '/../../groq.php';
require_once __DIR__ . '/../../app_state.php';

final class GroqProvider implements AIProviderInterface
{
    public function getName(): string
    {
        return 'groq';
    }

    public function getLabel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['label']
            ?? 'Groq';
    }

    public function getDefaultModel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['default_model']
            ?? 'openai/gpt-oss-120b';
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
                $scanVars['groqApiKey']
            )
        ) {
            return (string)
                $scanVars['groqApiKey'];
        }

        if (
            defined('GROQ_API_KEY')
            && !empty(GROQ_API_KEY)
        ) {
            return GROQ_API_KEY;
        }

        $env = getenv(
            'GROQ_API_KEY'
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
                && trim($response) !== ''
                && stripos($response, 'Erreur') !== 0
                && stripos($response, 'HTTP ') === false;

        } catch (Throwable $e) {

            error_log(
                'GroqProvider test failed: '
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
                'Erreur : clé API Groq absente.';
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

            $response =
                groq_call(
                    $prompt,
                    $systemPrompt,
                    $selectedModel,
                    $apiKey
                );

            if (
                !is_string($response)
                || trim($response) === ''
            ) {
                return
                    'Erreur Groq : réponse vide.';
            }

            return trim($response);

        } catch (Throwable $e) {

            error_log(
                'GroqProvider generate failed: '
                . $e->getMessage()
            );

            return
                'Erreur Groq : '
                . $e->getMessage();
        }
    }
}
