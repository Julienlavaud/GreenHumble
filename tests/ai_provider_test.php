<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/includes/ai/AIProviderFactory.php';

function prov_assert(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "ÉCHEC : {$message}\n"); exit(1); }
    echo "OK : {$message}\n";
}

foreach (['gemini', 'groq', 'mistral', 'openrouter'] as $name) {
    $provider = AIProviderFactory::make(strtoupper($name));
    prov_assert($provider instanceof AIProviderInterface && $provider->getName() === $name, "provider $name instancié via la fabrique");
    prov_assert(in_array($provider->getDefaultModel(), $provider->getModels(), true), "$name : modèle par défaut dans la liste autorisée");
    $params = (new ReflectionMethod($provider, 'generate'))->getParameters();
    prov_assert(count($params) === 4 && $params[3]->getName() === 'apiKey', "$name : generate() accepte une clé API explicite");
}

$unknown = false;
try { AIProviderFactory::make('inconnu'); } catch (RuntimeException $e) { $unknown = true; }
prov_assert($unknown, 'provider inconnu : exception explicite');
prov_assert(!AIProviderFactory::exists('inconnu') && AIProviderFactory::exists('groq'), 'exists() cohérent avec le registre');
prov_assert(count(AIProviderFactory::instances()) === 4, 'les quatre providers sont chargés');

$missing = AIProviderFactory::make('openrouter')->generate('p', 's', null, '');
prov_assert(stripos($missing, 'Erreur') === 0 || AIProviderFactory::make('openrouter')->hasApiKey(), 'clé absente : message d’erreur provider');
echo "Tous les tests providers IA sont réussis.\n";
