<?php

require_once __DIR__ . '/../AIProviderInterface.php';
require_once __DIR__ . '/../AIProviderRegistry.php';
require_once __DIR__ . '/../../mistral.php';
require_once __DIR__ . '/../../app_state.php';

final class MistralProvider implements AIProviderInterface
{
    public function getName(): string
    {
        return 'mistral';
    }

    public function getLabel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['label']
            ?? 'Mistral AI';
    }

    public function getDefaultModel(): string
    {
        $config = AIProviderRegistry::get(
            $this->getName()
        );

        return
            $config['default_model']
            ?? 'mistral-large-latest';
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
                $scanVars['mistralApiKey']
            )
        ) {
            return (string)
                $scanVars['mistralApiKey'];
        }

        if (
            defined('MISTRAL_API_KEY')
            && !empty(MISTRAL_API_KEY)
        ) {
            return MISTRAL_API_KEY;
        }

        $env =
            getenv('MISTRAL_API_KEY');

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
        $apiKey =
            $this->getApiKey();

        if (empty($apiKey)) {
            return false;
        }

        try {

            $reply = call_mistral(
                [
                    'model' =>
                        $this->getDefaultModel(),

                    'messages' => [
                        [
                            'role' => 'user',
                            'content' =>
                                'Réponds uniquement OK'
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

        } catch (Throwable $e) {

            error_log(
                'MistralProvider test failed: '
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
                'Erreur : clé API Mistral absente.';
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

            return mistral_call(
                $prompt,
                $systemPrompt,
                $selectedModel,
                $apiKey
            );

        } catch (Throwable $e) {

            error_log(
                'MistralProvider generate failed: '
                . $e->getMessage()
            );

            return
                'Erreur Mistral : '
                . $e->getMessage();
        }
    }
}
