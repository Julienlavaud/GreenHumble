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
        return 'Groq';
    }

    public function getDefaultModel(): string
    {
        $config = AIProviderRegistry::get('groq');

        return $config['default_model']
            ?? 'openai/gpt-oss-120b';
    }

    public function getModels(): array
    {
        $config = AIProviderRegistry::get('groq');

        return $config['models']
            ?? [];
    }

    public function getApiKey(): ?string
    {
        $state = load_app_state();
        $scanVars = $state['scanVars'] ?? [];

        if (!empty($scanVars['groqApiKey'])) {
            return (string)$scanVars['groqApiKey'];
        }

        if (
            defined('GROQ_API_KEY')
            && !empty(GROQ_API_KEY)
        ) {
            return GROQ_API_KEY;
        }

        if (!empty(getenv('GROQ_API_KEY'))) {
            return getenv('GROQ_API_KEY');
        }

        return null;
    }

    public function hasApiKey(): bool
    {
        return !empty($this->getApiKey());
    }

    public function testConnection(): bool
    {
        $apiKey = $this->getApiKey();

        if (empty($apiKey)) {
            return false;
        }

        $reply = call_groq(
            [
                'model' => $this->getDefaultModel(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Réponds uniquement OK'
                    ]
                ],
                'temperature' => 0
            ],
            $apiKey
        );

        return
            is_string($reply)
            && trim($reply) !== ''
            && stripos($reply, 'erreur') !== 0
            && stripos($reply, 'http ') === false;
    }

    public function generate(
        string $prompt,
        string $systemPrompt,
        ?string $model = null
    ): string {

        $apiKey = $this->getApiKey();

        if (empty($apiKey)) {
            return 'Erreur : clé API Groq absente.';
        }

        $selectedModel =
            $model
            ?: $this->getDefaultModel();

        return groq_call(
            $prompt,
            $systemPrompt,
            $selectedModel,
            $apiKey
        );
    }
}
