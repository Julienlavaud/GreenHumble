<?php

final class AIProviderRegistry
{
    public static function all(): array
    {
        return [

            'gemini' => [
                'label'          => 'Google Gemini',
                'provider_class' => 'GeminiProvider',
                'state_key'      => 'geminiApiKey',
                'env_key'        => 'GEMINI_API_KEY',
                'default_model'  => 'gemini-flash-latest',
                'models'         => [
                    'gemini-flash-latest',
                    'gemini-flash-lite-latest'
                ]
            ],

            'groq' => [
                'label'          => 'Groq',
                'provider_class' => 'GroqProvider',
                'state_key'      => 'groqApiKey',
                'env_key'        => 'GROQ_API_KEY',
                'default_model'  => 'openai/gpt-oss-120b',
                'models'         => [
                    'openai/gpt-oss-120b',
                    'openai/gpt-oss-20b'
                ]
            ],

            'mistral' => [
                'label'          => 'Mistral AI',
                'provider_class' => 'MistralProvider',
                'state_key'      => 'mistralApiKey',
                'env_key'        => 'MISTRAL_API_KEY',
                'default_model'  => 'mistral-large-latest',
                'models'         => [
                    'mistral-large-latest',
                    'mistral-small-latest'
                ]
            ],

            'openrouter' => [
                'label'          => 'OpenRouter',
                'provider_class' => 'OpenRouterProvider',
                'state_key'      => 'openRouterApiKey',
                'env_key'        => 'OPENROUTER_API_KEY',
                'default_model'  => 'google/gemma-4-26b-a4b-it:free',
                'models'         => [
                    'google/gemma-4-26b-a4b-it:free',
                    'openai/gpt-oss-20b:free'
                ]
            ]

        ];
    }

    public static function get(
        string $provider
    ): ?array {
        $all = self::all();

        return $all[strtolower($provider)] ?? null;
    }

    public static function exists(
        string $provider
    ): bool {
        return self::get($provider) !== null;
    }
}
``
