<?php

interface AIProviderInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function getDefaultModel(): string;

    public function getModels(): array;

    public function getApiKey(): ?string;

    public function hasApiKey(): bool;

    public function testConnection(): bool;

    public function generate(
        string $prompt,
        string $systemPrompt,
        ?string $model = null,
        ?string $apiKey = null
    ): string;
}
