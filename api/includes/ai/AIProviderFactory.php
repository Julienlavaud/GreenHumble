<?php

require_once __DIR__ . '/AIProviderInterface.php';
require_once __DIR__ . '/providers/GeminiProvider.php';
require_once __DIR__ . '/providers/GroqProvider.php';
require_once __DIR__ . '/providers/MistralProvider.php';

final class AIProviderFactory
{
    public static function make(
        string $provider
    ): AIProviderInterface {

        $provider = strtolower(trim($provider));

        switch ($provider) {

            case 'gemini':
                return new GeminiProvider();

            case 'groq':
                return new GroqProvider();

            case 'mistral':
                return new MistralProvider();

            default:
                throw new RuntimeException(
                    sprintf(
                        'Provider IA inconnu : %s',
                        $provider
                    )
                );
        }
    }

    public static function available(): array
    {
        return [
            'gemini',
            'groq',
            'mistral'
        ];
    }

    public static function exists(
        string $provider
    ): bool {

        return in_array(
            strtolower(trim($provider)),
            self::available(),
            true
        );
    }
}
