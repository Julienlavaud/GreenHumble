<?php
// /api/includes/ai_engine.php
// Climat Local — Moteur IA central (Audit & Prospection) — Version PRO

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/app_state.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/groq.php';
require_once __DIR__ . '/mistral.php';

/**
 * API publique principale
 */
function analyze_url_with_ai($url, $custom_prompt = null, $csrDocumentText = null) {
    return ai_engine_run($url, $custom_prompt, $csrDocumentText);
}

/**
 * Alias de compatibilité pour analyze_with_ai
 */
function analyze_with_ai($url, $custom_prompt = null, $csrDocumentText = null) {
    return ai_engine_run($url, $custom_prompt, $csrDocumentText);
}

/**
 * Vérifie qu'une URL cible un hôte public avant tout scraping.
 * Pour limiter les risques de DNS rebinding, le scraping épingle ensuite l'IPv4 validée.
 */
function ai_engine_resolve_public_ipv4($host) {
    $host = trim((string)$host, '[]');
    if ($host === '' || strtolower($host) === 'localhost' || preg_match('/\.local$/i', $host)) {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ? $host : false;
    }

    // Ce scraper n'accepte que les hôtes disposant d'une IPv4 publique validable.
    $ips = @gethostbynamel($host);
    if (!is_array($ips) || !$ips) {
        return false;
    }

    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
    }

    return $ips[0];
}

/**
 * Scraping rapide du contenu d’un site
 */
function ai_engine_scrape_website_content($url) {
    if (!is_string($url) || trim($url) === '') return "";

    $parts = parse_url(trim($url));
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return "";

    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
        return "";
    }

    $host = strtolower($parts['host']);
    $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
    // Limiter aux ports web standards et éviter les services internes exposés sur d'autres ports.
    if (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) return "";

    $resolvedIp = ai_engine_resolve_public_ipv4($host);
    if (!$resolvedIp) return "";

    $ch = curl_init();
    $options = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        // Ne pas suivre les redirections : chaque destination devrait être revalidée avant une nouvelle requête.
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS      => 0,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT      => 'GreenHumble-AI-Client/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_MAXFILESIZE    => 2097152,
    ];

    // Épingler le DNS vérifié pour éviter une seconde résolution différente par cURL.
    if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $options[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $resolvedIp];
    }

    curl_setopt_array($ch, $options);
    $html = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($html) || $html === '' || $httpCode < 200 || $httpCode >= 300) return "";

    $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
    $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);

    return mb_substr(trim($text), 0, 18000);
}

/**
 * Moteur IA central : construit le contexte, choisit le fournisseur, renvoie un JSON
 */
function ai_engine_run(
    $url,
    $custom_prompt = null,
    $csrDocumentText = null,
    $overrideProvider = null,
    $overrideModel = null,
    $overrideApiKey = null
) {
    // 1. Lecture de l’état applicatif
    $state    = load_app_state();
    $scanVars = $state['scanVars'] ?? [];

    $provider = strtolower($overrideProvider ?: ($scanVars['aiProvider'] ?? (defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini'))); // gemini, groq ou mistral
    $providerModelKey = $provider . 'Model';
    $model = $overrideModel ?: ($scanVars[$providerModelKey] ?? ($scanVars['aiModel'] ?? (defined('AI_MODEL') ? AI_MODEL : '')));
    // Ne pas envoyer par défaut le modèle Gemini à Mistral (ou inversement) lorsque seul le fournisseur a été changé.
    if (empty($model) ||
        ($provider === 'mistral' && preg_match('/^(gemini-|llama-|mixtral-)/i', $model)) ||
        ($provider === 'groq' && preg_match('/^(gemini-|mistral-)/i', $model)) ||
        ($provider === 'gemini' && preg_match('/^(mistral-|llama-|mixtral-)/i', $model))) {
        $model = $provider === 'mistral'
            ? 'mistral-small-latest'
            : ($provider === 'groq' ? 'llama-3.3-70b-versatile' : 'gemini-2.5-flash');
    }
    
    // Priorité : clé explicitement passée, puis clé chiffrée/configurée en console,
    // puis variables d'environnement, puis ancienne clé générique pour compatibilité.
    $apiKey = $overrideApiKey;
    $providerKeyName = ['gemini' => 'geminiApiKey', 'mistral' => 'mistralApiKey', 'groq' => 'groqApiKey'][$provider] ?? '';
    if (empty($apiKey) && $providerKeyName !== '' && !empty($scanVars[$providerKeyName])) {
        $apiKey = $scanVars[$providerKeyName];
    }
    if (empty($apiKey)) {
        if ($provider === 'groq') {
            $apiKey = defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? ''));
        } elseif ($provider === 'mistral') {
            $apiKey = defined('MISTRAL_API_KEY') && !empty(MISTRAL_API_KEY) ? MISTRAL_API_KEY : (getenv('MISTRAL_API_KEY') ?: ($_ENV['MISTRAL_API_KEY'] ?? ''));
        } else {
            $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? GEMINI_API_KEY : (defined('AI_API_KEY') && !empty(AI_API_KEY) ? AI_API_KEY : (getenv('GEMINI_API_KEY') ?: getenv('AI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '')));
        }
    }
    if (empty($apiKey)) $apiKey = $scanVars['aiApiKey'] ?? null;

    // 2. Scraping du contenu réel
    $webContent = "";
    if (strpos($url, 'https://') === 0 || strpos($url, 'http://') === 0) {
        $webContent = ai_engine_scrape_website_content($url);
    }

    // 3. Règles métier / exceptions
    $customExceptions = "";
    try {
        $db         = get_db_connection();
        $stmt       = $db->query("SELECT content FROM cl_texts WHERE text_key = 'ai_custom_exceptions' LIMIT 1");
        $ruleContent = $stmt->fetchColumn();
        if ($ruleContent) {
            $customExceptions = "\n\nEXCEPTIONS ET RÈGLES MÉTIER À APPLIQUER :\n" . $ruleContent;
        }
    } catch (Exception $e) {
        // silencieux
    }

    // 4. Contexte Document RSE
    $docContext = "";
    if (!empty($csrDocumentText)) {
        $truncatedDoc = mb_substr(trim($csrDocumentText), 0, 15000);
        $docContext   = "\n\n--- DOCUMENT RSE FOURNI ---\n" . $truncatedDoc . "\n--- FIN DOCUMENT ---\n"
            . "CONSIGNE : Si une mention sur le site est justifiée dans ce document, marque-la comme 'justifie_par_preuve'.";
    }

    // 5. Mode dégradé sans clé API : ne pas produire de score ni de conclusion juridique.
    // Les expressions détectées sont des signaux à vérifier, pas des preuves de greenwashing.
    if (empty($apiKey)) {
        $detectedClaims = [];
        $domain = parse_url($url, PHP_URL_HOST) ?: $url;
        $patterns = [
            '/neutre\s+en\s+carbone|neutralit[ée]\s+carbone|z[ée]ro\s+carbone/i',
            '/[ée]co-?responsable|[ée]co-?con[çc]u|[ée]cologique|ami\s+de\s+la\s+nature/i',
            '/100\s*%\s*vert|100\s*%\s*naturel|100\s*%\s*durable|100\s*%\s*propre/i',
            '/compens[ée]\s+carbone|compensation\s+carbone|contribution\s+climat/i',
            '/biod[ée]gradable|compostable/i',
            '/bon\s+pour\s+la\s+plan[èe]te|pr[ée]serve\s+la\s+plan[èe]te|sauve\s+le\s+climat/i'
        ];
        foreach ($patterns as $pattern) {
            if ($webContent && preg_match($pattern, $webContent, $matches, PREG_OFFSET_CAPTURE)) {
                $matchedText = $matches[0][0];
                $pos = $matches[0][1];
                $startQuote = max(0, $pos - 80);
                $quote = trim(mb_substr($webContent, $startQuote, 220));
                $detectedClaims[] = [
                    'term' => $matchedText,
                    'quoteInText' => $quote,
                    'severity' => 'a_verifier',
                    'legalRisk' => 'Non évalué : une vérification du contexte et des justificatifs est nécessaire.',
                    'lawArticle' => null,
                    'sanction' => null,
                    'requiredProof' => 'Vérifier le périmètre, la méthode et les justificatifs accessibles au public.',
                    'correctionNote' => null,
                    'evidenceStatus' => 'to_review'
                ];
            }
        }

        return json_encode([
            'status' => 'analysis_incomplete',
            'analysisComplete' => false,
            'score' => null,
            'riskLevel' => 'a_evaluer',
            'summary' => "Analyse incomplète du site $domain : aucune clé API IA n’est configurée. Les expressions relevées sont uniquement des signaux à vérifier et ne constituent pas une conclusion de non-conformité.",
            'claims' => $detectedClaims,
            'prospection' => null,
            'financialImpact' => null,
            'limitations' => ['Analyse IA indisponible', 'Aucune conclusion juridique ni estimation de sanction produite']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 6. Construction du prompt système
    $baseInstructions = $scanVars['aiKeywordInstructions']
        ?? "Tu es l’IA GreenHumble, experte en conformité RSE, Green Claims et prospection commerciale. Tu analyses le contenu fourni pour détecter les allégations, les risques et les opportunités.";
    $systemInstruction = $baseInstructions
        . " Réponds en JSON valide uniquement."
        . $customExceptions
        . $docContext;

    // 7. Construction du prompt utilisateur
    $scrapedPart = "\n\nCONTENU TEXTUEL RÉEL DU SITE WEB À ANALYSER :\n"
        . ($webContent ?: "[Impossible de scraper le contenu direct, utilise tes connaissances si le domaine est connu ou demande des preuves]")
        . "\n\n";

    $prompt = ($custom_prompt ?: "Effectue un audit complet de conformité environnementale et de potentiel de prospection pour : $url")
        . $scrapedPart;

    if (!$custom_prompt) {
        $prompt .= "MISSION : Analyse chaque phrase pour détecter des allégations trompeuses (greenwashing) selon EmpCO et Green Claims, "
            . "ET des signaux faibles de prospection (besoins, risques, opportunités).\n"
            . "RETOUR : JSON {score, riskLevel, summary, financialImpact, claims: [{term, quoteInText, severity, legalRisk, lawArticle, sanction, requiredProof, correctionNote}], "
            . "prospection: {scoreProspect, signauxFaibles, besoinsPotentiels, risques, recommandation, messageApproche}}";
    }

    // 8. Dispatch vers le fournisseur IA avec fallback automatique
    $aiResponse = '';
    if ($provider === 'groq') {
        $model = $model ?: 'llama-3.3-70b-versatile';
        $aiResponse = groq_call($prompt, $systemInstruction, $model, $apiKey);
    } elseif ($provider === 'mistral') {
        $model = $model ?: 'mistral-small-latest';
        $aiResponse = mistral_call($prompt, $systemInstruction, $model, $apiKey);
    } else {
        $model = $model ?: 'gemini-2.5-flash';
        $aiResponse = gemini_call($prompt, $systemInstruction, $model, $apiKey);
    }

    // Normaliser les réponses JSON: certains fournisseurs ajoutent des fences Markdown
    // malgré la consigne JSON. Ne jamais fabriquer de score si le contenu reste invalide.
    $decodeAiObject = static function ($raw) {
        if (!is_string($raw) || trim($raw) === '') return null;
        $raw = trim($raw);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) return $decoded;
        $withoutFences = preg_replace('/^\x60{3}(?:json)?\s*|\s*\x60{3}$/i', '', $raw);
        $decoded = json_decode(trim($withoutFences), true);
        if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) return $decoded;
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);
            if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
        return null;
    };
    $decodedResponse = $decodeAiObject($aiResponse);
    if (
        is_array($decodedResponse) &&
        json_last_error() === JSON_ERROR_NONE &&
        (array_key_exists('score', $decodedResponse) ||
         array_key_exists('riskScore', $decodedResponse) ||
         array_key_exists('claims', $decodedResponse) ||
         array_key_exists('detectedClaims', $decodedResponse) ||
         array_key_exists('status', $decodedResponse))
    ) {
        return json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // Fallbacks optionnels : seuls les fournisseurs dont la clé est configurée sont appelés.
    $fallbackProviders = [];
    $groqKey = defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? ''));
    $mistralKey = defined('MISTRAL_API_KEY') && !empty(MISTRAL_API_KEY) ? MISTRAL_API_KEY : (getenv('MISTRAL_API_KEY') ?: ($_ENV['MISTRAL_API_KEY'] ?? ''));
    if ($provider !== 'groq' && !empty($groqKey)) {
        $fallbackProviders[] = ['provider' => 'groq', 'key' => $groqKey, 'model' => 'llama-3.3-70b-versatile'];
    }
    if ($provider !== 'mistral' && !empty($mistralKey)) {
        $fallbackProviders[] = ['provider' => 'mistral', 'key' => $mistralKey, 'model' => 'mistral-small-latest'];
    }

    foreach ($fallbackProviders as $fallback) {
        if ($fallback['provider'] === 'groq') {
            $fallbackRes = groq_call($prompt, $systemInstruction, $fallback['model'], $fallback['key']);
        } else {
            $fallbackRes = mistral_call($prompt, $systemInstruction, $fallback['model'], $fallback['key']);
        }
        $decodedFallback = $decodeAiObject($fallbackRes);
        if (
            is_array($decodedFallback) &&
            json_last_error() === JSON_ERROR_NONE &&
            (array_key_exists('score', $decodedFallback) ||
             array_key_exists('riskScore', $decodedFallback) ||
             array_key_exists('claims', $decodedFallback) ||
             array_key_exists('detectedClaims', $decodedFallback) ||
             array_key_exists('status', $decodedFallback))
        ) {
            return json_encode($decodedFallback, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    return json_encode([
        'status' => 'analysis_incomplete',
        'analysisComplete' => false,
        'score' => null,
        'riskLevel' => 'a_evaluer',
        'summary' => "Analyse incomplète : le fournisseur IA n’a pas renvoyé un JSON exploitable. Aucune conclusion de conformité n’a été établie.",
        'claims' => [],
        'prospection' => null,
        'financialImpact' => null,
        'limitations' => ['Réponse IA absente, invalide ou non structurée en JSON']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
