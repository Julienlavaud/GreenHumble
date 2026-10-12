<?php

require_once __DIR__ . '/AIProviderInterface.php';
require_once __DIR__ . '/AIProviderRegistry.php';

require_once __DIR__ . '/providers/GeminiProvider.php';
require_once __DIR__ . '/providers/GroqProvider.php';
require_once __DIR__ . '/providers/MistralProvider.php';
require_once __DIR__ . '/providers/OpenRouterProvider.php';

final class AIProviderFactory
{
    public static function make(
        string $provider
    ): AIProviderInterface {

        $provider = strtolower(
            trim($provider)
        );

        $config =
            AIProviderRegistry::get(
                $provider
            );

        if (!$config) {
            throw new RuntimeException(
                sprintf(
                    'Provider IA inconnu : %s',
                    $provider
                )
            );
        }

        $class =
            $config['provider_class']
            ?? null;

        if (
            !$class
            || !class_exists($class)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Classe provider absente : %s',
                    (string)$class
                )
            );
        }

        $providerInstance =
            new $class();

        if (
            !($providerInstance instanceof AIProviderInterface)
        ) {
            throw new RuntimeException(
                sprintf(
                    '%s doit implémenter AIProviderInterface',
                    $class
                )
            );
        }

        return $providerInstance;
    }

    public static function available(): array
    {
        return array_keys(
            AIProviderRegistry::all()
        );
    }

    public static function exists(
        string $provider
    ): bool {

        return
            AIProviderRegistry::get(
                strtolower(trim($provider))
            ) !== null;
    }

    public static function instances(): array
    {
        $instances = [];

        foreach (
            self::available()
            as $provider
        ) {

            try {

                $instances[$provider] =
                    self::make($provider);

            } catch (Throwable $e) {

                error_log(
                    'AIProviderFactory: impossible de charger '
                    . $provider
                    . ' : '
                    . $e->getMessage()
                );
            }
        }

        return $instances;
    }
}
