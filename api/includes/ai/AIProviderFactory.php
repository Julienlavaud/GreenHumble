
<?php

require_once __DIR__ . '/providers/GeminiProvider.php';
require_once __DIR__ . '/providers/MistralProvider.php';
require_once __DIR__ . '/providers/GroqProvider.php';

final class AIProviderFactory
{
    public static function make(
        string $provider
    ): AIProviderInterface {

        return match ($provider) {

            'gemini' =>
                new GeminiProvider(),

            'mistral' =>
                new MistralProvider(),

            'groq' =>
                new GroqProvider(),

            default =>
                throw new RuntimeException(
                    'Provider inconnu : ' . $provider
                )
        };
    }
}
