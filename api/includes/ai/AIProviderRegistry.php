<?php

final class AIProviderRegistry
{
    public static function all(): array
    {
        return [

            'gemini' => [
                'label' => 'Google Gemini',
                'default_model' => 'gemini-flash-latest',
                'models' => [
                    'gemini-flash-latest',
                    'gemini-flash-lite-latest',
                    'gemini-pro-latest'
                ]
            ],

            'mistral' => [
                'label' => 'Mistral AI',
                'default_model' => 'mistral-large-latest',
                'models' => [
                    'mistral-large-latest',
                    'mistral-small-latest'
                ]
            ],

            'groq' => [
                'label' => 'Groq',
                'default_model' => 'openai/gpt-oss-120b',
                'models' => [
                    'openai/gpt-oss-120b',
                    'openai/gpt-oss-20b',
                    'qwen/qwen3.8-27b'
                ]
            ]

        ];
    }

    public static function get(string $provider): ?array
    {
        $all = self::all();

        return $all[$provider] ?? null;
    }
}
