<?php
// /api/includes/ai_engine.php
// Climat Local — Moteur IA central (Audit & Prospection) — Version PRO

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/app_state.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/groq.php';

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
 * Scraping rapide du contenu d’un site
 */
function ai_engine_scrape_website_content($url) {
    if (empty($url)) return "";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return "";

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

    $provider = $overrideProvider ?: strtolower($scanVars['aiProvider'] ?? (defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini')); // gemini ou groq
    $model    = $overrideModel   ?: ($scanVars['aiModel'] ?? (defined('AI_MODEL') ? AI_MODEL : 'gemini-3.8-flash'));
    
    // Détection robuste de la clé API (ScanVars > Constante > Env)
    $apiKey = $overrideApiKey ?: ($scanVars['aiApiKey'] ?? null);
    if (empty($apiKey)) {
        if ($provider === 'groq') {
            $apiKey = defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? ''));
        } else {
            $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? GEMINI_API_KEY : (defined('AI_API_KEY') && !empty(AI_API_KEY) ? AI_API_KEY : (getenv('GEMINI_API_KEY') ?: getenv('AI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '')));
        }
    }

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
            '/neutre\\s+en\\s+carbone|neutralit[ée]\\s+carbone|z[ée]ro\\s+carbone/i',
            '/[ée]co-?responsable|[ée]co-?con[çc]u|[ée]cologique|ami\\s+de\\s+la\\s+nature/i',
            '/100\\s*%\\s*vert|100\\s*%\\s*naturel|100\\s*%\\s*durable|100\\s*%\\s*propre/i',
            '/compens[ée]\\s+carbone|compensation\\s+carbone|contribution\\s+climat/i',
            '/biod[ée]gradable|compostable/i',
            '/bon\\s+pour\\s+la\\s+plan[èe]te|pr[ée]serve\\s+la\\s+plan[èe]te|sauve\\s+le\\s+climat/i'
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
    } else {
        $model = $model ?: 'gemini-3.8-flash';
        $aiResponse = gemini_call($prompt, $systemInstruction, $model, $apiKey);
    }

    // Si le résultat est valide, le renvoyer
    if ($aiResponse && strpos($aiResponse, 'Erreur Moteur IA') === false) {
        return $aiResponse;
    }

    // Fallback automatique vers Groq si Gemini échoue
    $groqKey = defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? ''));
    if (!empty($groqKey) && $provider !== 'groq') {
        $fallbackRes = groq_call($prompt, $systemInstruction, 'llama-3.3-70b-versatile', $groqKey);
        if ($fallbackRes && strpos($fallbackRes, 'Erreur') === false) {
            return $fallbackRes;
        }
    }

    return $aiResponse;
}
