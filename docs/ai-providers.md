# Moteur IA et providers

`api/includes/ai_engine.php` (`ai_engine_run`) délègue les appels aux providers de `api/includes/ai/`.

- `AIProviderRegistry` : providers déclarés (`gemini`, `groq`, `mistral`, `openrouter`), modèle par défaut et modèles autorisés.
- `AIProviderFactory::make($nom)` : instancie le provider ; un nom inconnu lève une `RuntimeException`.
- `AIProviderInterface::generate($prompt, $system, $model, $apiKey)` : renvoie le texte du modèle, ou une chaîne commençant par `Erreur` / `HTTP ` en cas d'échec.

Sélection : paramètre `overrideProvider`, sinon `scanVars.aiProvider`, sinon `AI_PROVIDER`. Un provider inconnu retombe sur `gemini`. Un modèle non autorisé retombe sur le modèle par défaut du provider.

Clé API (ordre) : clé explicite, `scanVars.<provider>ApiKey`, `scanVars.aiApiKey`, puis clé du provider (constante ou variable d'environnement `GEMINI_API_KEY`, `GROQ_API_KEY`, `MISTRAL_API_KEY`, `OPENROUTER_API_KEY`). Sans clé, l'analyse est « incomplète » et sans score.

Si le provider échoue ou renvoie une réponse vide/non conforme au contrat JSON, les autres providers ayant une clé sont essayés ; sinon `analysis_incomplete` est renvoyé.

Tests : `php tests/ai_provider_test.php`.
