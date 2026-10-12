<?php
// /api/includes/ai_engine.php
// Climat Local — Moteur IA central (Audit & Prospection) — Version PRO

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/app_state.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ai/AIProviderInterface.php';
require_once __DIR__ . '/ai/AIProviderRegistry.php';
require_once __DIR__ . '/ai/AIProviderFactory.php';
require_once __DIR__ . '/ai_contract.php';

/**
 * API publique principale
 */
function analyze_url_with_ai($url, $custom_prompt = null, $csrDocumentText = null, $sourceContent = null) {
    return ai_engine_run($url, $custom_prompt, $csrDocumentText, null, null, null, $sourceContent);
}

/**
 * Alias de compatibilité pour analyze_with_ai
 */
function analyze_with_ai($url, $custom_prompt = null, $csrDocumentText = null, $sourceContent = null) {
    return ai_engine_run($url, $custom_prompt, $csrDocumentText, null, null, null, $sourceContent);
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
function ai_engine_scrape_single_page($url, &$links = null) {
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

    if (is_array($links)) {
        $links = [];
        $base = parse_url($url);
        $origin = $scheme . '://' . $host . (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80) ? '' : ':' . $port);
        if (preg_match_all("~<a\\\\b[^>]*href\\\\s*=\\\\s*(?:\"([^\"]*)\"|'([^']*)')~is", $html, $hrefMatches)) {
            foreach ($hrefMatches[1] as $hrefIndex => $quotedHref) {
                $href = html_entity_decode(trim($quotedHref !== '' ? $quotedHref : ($hrefMatches[2][$hrefIndex] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($href === '' || preg_match('/^(?:#|mailto:|tel:|javascript:|data:)/i', $href)) continue;
                if (strpos($href, '//') === 0) $candidate = $scheme . ':' . $href;
                elseif (preg_match('/^https?:\\/\\//i', $href)) $candidate = $href;
                elseif (strpos($href, '/') === 0) $candidate = $origin . $href;
                else {
                    $basePath = $base['path'] ?? '/';
                    $dir = rtrim(substr($basePath, 0, strrpos($basePath, '/') !== false ? strrpos($basePath, '/') + 1 : 0), '/');
                    $candidate = $origin . ($dir ? $dir . '/' : '/') . $href;
                }
                $parts = parse_url($candidate);
                if (!is_array($parts) || strtolower($parts['host'] ?? '') !== $host ||
                    !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) ||
                    isset($parts['user']) || isset($parts['pass']) || isset($parts['query'])) continue;
                $candidatePort = (int)($parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80));
                if (($parts['scheme'] === 'https' && $candidatePort !== 443) || ($parts['scheme'] === 'http' && $candidatePort !== 80)) continue;
                $links[] = $candidate;
            }
        }
    }

    $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
    $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);

    return mb_substr(trim($text), 0, 18000);
}



/**
 * Analyse la page d'entrée et quelques pages éditoriales internes pertinentes.
 * Chaque destination repasse par la validation SSRF du scraper unitaire.
 */
function ai_engine_scrape_website_content($url) {
    $links = [];
    $home = ai_engine_scrape_single_page($url, $links);
    if ($home === '') return '';
    $pages = [['url' => $url, 'text' => $home]];
    $seen = [rtrim(strtolower($url), '/') => true];
    foreach ($links as $link) {
        $path = strtolower(parse_url($link, PHP_URL_PATH) ?: '/');
        if (!preg_match('/(?:about|a-propos|qui-sommes|engagement|impact|rse|durabil|environnement|ecologie|produit|gamme|nos-offres|fabrication|matiere|materiau|responsab|charte|mission)/i', $path)) continue;
        $key = rtrim(strtolower($link), '/');
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $text = ai_engine_scrape_single_page($link);
        if ($text !== '') $pages[] = ['url' => $link, 'text' => $text];
        if (count($pages) >= 3) break;
    }
    $combined = [];
    foreach ($pages as $page) $combined[] = '[SOURCE_URL: ' . $page['url'] . "]\n" . $page['text'];
    return mb_substr(implode("\n\n", $combined), 0, 36000);
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
    $overrideApiKey = null,
    $providedWebContent = null
) {
    // 1. Lecture de l’état applicatif
    $state    = load_app_state();
    $scanVars = $state['scanVars'] ?? [];

    $provider = strtolower($overrideProvider ?: ($scanVars['aiProvider'] ?? (defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini'))); // gemini, groq ou mistral
    $providerModelKey = $provider . 'Model';
    $model = trim((string)($overrideModel ?: ($scanVars[$providerModelKey] ?? ($scanVars['aiModel'] ?? (defined('AI_MODEL') ? AI_MODEL : '')))));
    // Seuls les modèles actuellement proposés par la console sont utilisés. Cela évite
    // les appels vers Gemini 2.0 arrêté et les anciens modèles Groq retirés.
$providerConfig =
    AIProviderRegistry::get(
        $provider
    );

if (!$providerConfig) {

    $provider = 'gemini';

    $providerConfig =
        AIProviderRegistry::get(
            'gemini'
        );
}

$allowedModels =
    $providerConfig['models']
    ?? [];

if (
    !in_array(
        $model,
        $allowedModels,
        true
    )
) {
    $model =
        $providerConfig['default_model'];
}

$providerKeyName = [
    'gemini'     => 'geminiApiKey',
    'groq'       => 'groqApiKey',
    'mistral'    => 'mistralApiKey',
    'openrouter' => 'openRouterApiKey'
][$provider] ?? '';

$apiKey = $overrideApiKey;

if (
    empty($apiKey)
    && $providerKeyName !== ''
) {
    $apiKey =
        $scanVars[$providerKeyName]
        ?? null;
}

if (empty($apiKey)) {
    $apiKey =
        $scanVars['aiApiKey']
        ?? null;
}

if (empty($apiKey)) {
    try {
        $apiKey = AIProviderFactory::make($provider)->getApiKey();
    } catch (Throwable $e) {
        error_log('AI Provider key lookup failed: ' . $e->getMessage());
    }
}

    // 2. Scraping du contenu réel
   $webContent = is_string($providedWebContent)
    ? $providedWebContent
    : (
        preg_match(
            '~^https?://~i',
            (string)$url
        )
            ? ai_engine_scrape_website_content($url)
            : ''
    );

    $canonicalUrl = gh_ai_canonical_url((string)$url) ?? (string)$url;
    $corpus = gh_ai_corpus_pages_from_text($canonicalUrl, (string)$webContent);
    $runMetadata = ['promptVersion' => GH_AI_PROMPT_VERSION, 'schemaVersion' => GH_AI_SCHEMA_VERSION, 'generationVersion' => GH_AI_GENERATION_VERSION, 'provider' => $provider, 'model' => $model, 'generation' => ['temperature' => 0.2], 'corpus' => ['canonicalUrl' => $corpus['canonicalUrl'], 'pageCount' => $corpus['pageCount'], 'fingerprint' => $corpus['corpusFingerprint']]];

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

    // Catalogue d’offres administré : l’IA ne doit recommander que ces services actifs.
    $serviceCatalog = [];
    foreach (($state['serviceOpps'] ?? []) as $service) {
        if (!is_array($service) || !($service['isActive'] ?? true)) continue;
        $serviceCatalog[] = [
            'serviceKey' => (string)($service['serviceKey'] ?? $service['id'] ?? ''),
            'serviceTitle' => (string)($service['serviceTitle'] ?? ''),
            'description' => (string)($service['description'] ?? ''),
            'defaultBudget' => (string)($service['defaultBudget'] ?? ''),
        ];
    }
    $catalogContext = $serviceCatalog
        ? "\n\nCATALOGUE DES OFFRES ACTIVES (choisir uniquement dans cette liste) :\n" . json_encode($serviceCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : "\n\nAucun catalogue d’offres actif n’est configuré : ne recommande pas de prestation ni de budget précis.";

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
            if ($webContent && preg_match_all($pattern, $webContent, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matchedText = $match[0];
                    $pos = $match[1];
                    $startQuote = max(0, $pos - 80);
                    $quote = trim(mb_substr($webContent, $startQuote, 220));
                    $detectedClaims[] = [
                        'term' => $matchedText, 'quoteInText' => $quote,
                        'severity' => 'a_verifier',
                        'legalRisk' => 'Non évalué : une vérification du contexte et des justificatifs est nécessaire.',
                        'lawArticle' => null, 'sanction' => null,
                        'requiredProof' => 'Vérifier le périmètre, la méthode et les justificatifs accessibles au public.',
                        'correctionNote' => null, 'evidenceStatus' => 'to_review'
                    ];
                }
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
            'limitations' => ['Analyse IA indisponible', 'Aucune conclusion juridique ni estimation de sanction produite'],
            'aiMetadata' => $runMetadata
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 6. Construction du prompt système
    $baseInstructions = $scanVars['aiKeywordInstructions']
        ?? "Tu es l’IA GreenHumble, experte en conformité RSE, Green Claims et prospection commerciale. Tu analyses le contenu fourni pour détecter les allégations, les risques et les opportunités.";
    $editablePromptRules = [];
    foreach ([
        'aiAnalysisInstructions' => 'PÉRIMÈTRE D’ANALYSE',
        'aiEvidenceInstructions' => 'PREUVES ET CITATIONS',
        'aiRiskInstructions' => 'ÉVALUATION DU RISQUE',
        'aiCommercialInstructions' => 'RECOMMANDATIONS COMMERCIALES',
        'aiLegalFramework' => 'CADRE JURIDIQUE À VÉRIFIER',
        'aiCorrectiveActions' => 'ACTIONS CORRECTIVES',
        'aiClaimsDictionary' => 'DICTIONNAIRE DE SIGNAUX'
    ] as $ruleKey => $ruleLabel) {
        $ruleText = trim((string)($scanVars[$ruleKey] ?? ''));
        if ($ruleText !== '') $editablePromptRules[] = $ruleLabel . ":\n" . $ruleText;
    }
    $systemInstruction = $baseInstructions
        . (!empty($editablePromptRules) ? "\n\n" . implode("\n\n", $editablePromptRules) : '')
        . " RÈGLES DE FIABILITÉ OBLIGATOIRES : analyse toutes les allégations distinctes présentes dans le contenu fourni, pas seulement la première. Pour chaque allégation, cite une courte citation exacte du texte source dans quoteInText, indique la sourceUrl uniquement parmi les URL SOURCE_URL fournies et justifie le niveau dans riskRationale; ne crée jamais de citation absente du contenu. Si le contenu du site n'a pas pu être récupéré, signale cette limite et ne prétends pas avoir analysé le site. Distingue explicitement (1) signal à vérifier, (2) preuve insuffisante ou manquante, (3) risque juridique plausible, (4) non-conformité établie uniquement si les faits et le fondement applicable sont suffisamment documentés. Une expression environnementale seule ne prouve pas une infraction. Ne déclare jamais qu'une sanction est certaine et ne donne aucun montant de sanction sans fondement légal précis, conditions d'application vérifiées et source factuelle suffisante; sinon sanction=null. N'invente ni articles de loi, ni jurisprudence, ni certifications, ni preuves, ni caractéristiques de l'entreprise. Si le droit applicable ou les faits sont incertains, indique-le et formule une recommandation de vérification. Le score est une estimation de risque argumentée, non une probabilité d'infraction. Le score commercial doit être distinct du score de risque. Retourne toutes les allégations pertinentes dans claims, en évitant les doublons, et utilise evidenceStatus parmi signal, evidence_missing, to_review, substantiated. Réponds en JSON valide uniquement."
        . $customExceptions
        . (!empty($scanVars['customBannedKeywords']) ? "\n\nEXPRESSIONS À VÉRIFIER EN PRIORITÉ (sans présumer d'une infraction) :\n" . (string)$scanVars['customBannedKeywords'] : '')
        . $catalogContext
        . " RÈGLES COMMERCIALES : propose une prochaine action, une priorité (haute, moyenne ou basse) et au maximum une offre du catalogue actif, avec son serviceKey exact et une raison liée aux constats. Si aucune offre ne correspond, laisse recommendedOffer à null. Ne crée jamais de budget ni de subvention absent du catalogue. Relie les arguments aux seules citations effectivement trouvées et n’affirme aucune infraction."
        . $docContext;

    // 7. Construction du prompt utilisateur
    $scrapedPart = "\n\nCONTENU TEXTUEL RÉEL DU SITE WEB À ANALYSER :\n"
        . ($webContent ?: "[Impossible de scraper le contenu direct, utilise tes connaissances si le domaine est connu ou demande des preuves]")
        . "\n\n";

    $configuredUserPrompt = trim((string)($scanVars['aiUserPrompt'] ?? ''));
    $prompt = ($custom_prompt ?: (($configuredUserPrompt !== '' ? $configuredUserPrompt : "Effectue un audit complet de conformité environnementale et de potentiel de prospection") . " pour : $url"))
        . $scrapedPart;

    if (!$custom_prompt) {
        $prompt .= "MISSION : Analyse chaque phrase pour détecter les allégations pertinentes et les opportunités commerciales qui découlent des constats vérifiables.\\n"
            . "RETOUR : JSON {score, riskLevel, summary, financialImpact, claims: [{term, quoteInText, sourceUrl, severity, riskRationale, legalRisk, lawArticle, sanction, requiredProof, correctionNote}], "
            . "prospection: {scoreProspect, priority, priorityRationale, nextAction, recommendedOffer: {serviceKey, serviceTitle, reason, budget} ou null, evidenceBasedReasons: [{claimTerm, quoteInText, reason}], signauxFaibles, besoinsPotentiels, risques, recommandation, messageApproche}}";
    }

    // 8. Dispatch vers le fournisseur IA avec fallback automatique
    $aiResponse = '';

try {

    $providerInstance =
        AIProviderFactory::make(
            $provider
        );

    $aiResponse =
        $providerInstance->generate(
            $prompt,
            $systemInstruction,
            $model,
            $apiKey
        );

} catch (Throwable $e) {

    error_log(
        'AI Provider Error: '
        . $e->getMessage()
    );

    $providerErrorDetail = $e->getMessage();
    $aiResponse = '';
}

    // Le contrat exige un objet JSON brut, complet et conforme au schéma.
    $decodedResponse = gh_ai_validate_response($aiResponse);
    if ($decodedResponse['valid']) {
        $result = $decodedResponse['data'];
        $result['aiMetadata'] = $runMetadata;
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

  foreach (
    AIProviderFactory::instances()
    as $fallbackProviderName => $fallbackProvider
) {

    if (
        $fallbackProviderName === $provider
    ) {
        continue;
    }

    if (
        !$fallbackProvider->hasApiKey()
    ) {
        continue;
    }

    try {

        $fallbackResponse =
            $fallbackProvider->generate(
                $prompt,
                $systemInstruction
            );

        $decodedFallback =
            gh_ai_validate_response(
                $fallbackResponse
            );

        if (
            $decodedFallback['valid']
        ) {

            $result =
                $decodedFallback['data'];

            $runMetadata['provider'] =
                $fallbackProviderName;

            $runMetadata['fallbackUsed'] =
                true;

            $result['aiMetadata'] =
                $runMetadata;

            return json_encode(
                $result,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
            );
        }

    } catch (Throwable $e) {

        error_log(
            'Fallback provider failed: '
            . $fallbackProviderName
            . ' - '
            . $e->getMessage()
        );
    }
}

    $providerErrorDetail = isset($providerErrorDetail) ? $providerErrorDetail : '';
    if ($providerErrorDetail === '' && is_string($aiResponse) && (stripos(trim($aiResponse), 'Erreur') === 0 || stripos(trim($aiResponse), 'HTTP ') === 0)) {
        $providerErrorDetail = trim($aiResponse);
    }
    $providerErrorDetail = preg_replace('/(key=|AIza|gsk_|sk-|Bearer\s+)[A-Za-z0-9_\-]+/i', '$1***', substr($providerErrorDetail, 0, 300));
    $failureCode = 'response_not_json';
    if ($providerErrorDetail !== '' && ($aiResponse === '' || $aiResponse === null)) {
        $failureCode = 'provider_request_failed';
    }
    if (is_string($aiResponse) && (stripos(trim($aiResponse), 'Erreur') === 0 || stripos(trim($aiResponse), 'HTTP ') === 0)) {
        $failureCode = 'provider_request_failed';
    } elseif (in_array(($decodedResponse['code'] ?? ''), ['schema_invalid', 'analysis_incomplete'], true)) {
        $failureCode = $decodedResponse['code'];
    }
    $failureSummary = $failureCode === 'provider_request_failed'
        ? "Le fournisseur IA n’a pas pu traiter la demande. Vérifiez le modèle, les quotas et les journaux serveur."
        : ($failureCode === 'response_missing_score'
            ? "Le fournisseur IA a répondu, mais sans score exploitable. Aucune conclusion de conformité n’a été établie."
            : "La réponse du fournisseur IA n’a pas pu être lue comme un JSON d’audit. Aucune conclusion de conformité n’a été établie.");

    return json_encode([
        'status' => 'analysis_incomplete',
        'analysisComplete' => false,
        'diagnosticCode' => $failureCode,
        'score' => null,
        'riskLevel' => 'a_evaluer',
        'summary' => $failureSummary,
        'claims' => [],
        'prospection' => null,
        'financialImpact' => null,
        'providerError' => $providerErrorDetail !== '' ? $providerErrorDetail : null,
        'limitations' => ['Réponse IA absente, invalide ou non structurée en JSON'],
        'aiMetadata' => $runMetadata
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
