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
        return 'Mistral AI';
    }

    public function getDefaultModel(): string
    {
        $config = AIProviderRegistry::get('mistral');

        return $config['default_model']
            ?? 'mistral-large-latest';
    }

    public function getModels(): array
    {
        $config = AIProviderRegistry::get('mistral');

        return $config['models']
            ?? [];
    }

    public function getApiKey(): ?string
    {
        $state = load_app_state();
        $scanVars = $state['scanVars'] ?? [];

        if (!empty($scanVars['mistralApiKey'])) {
            return (string) $scanVars['mistralApiKey'];
        
