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
        CURLOPT_SSL_VERIFYPEER => false,
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

    // 5. Analyse Heuristique Dynamique Réelle (Fallback si pas de clé API)
    if (empty($apiKey)) {
        // Extraction dynamique basée sur le contenu réel scrapé
        $detectedClaims = [];
        $domain = parse_url($url, PHP_URL_HOST) ?: $url;
        $rules = [
            ['pattern' => '/(neutre\s+en\s+carbone|neutralit[ée]\s+carbone|z[ée]ro\s+carbone)/i', 'term' => 'Neutralité carbone', 'severity' => 'interdit_strict', 'law' => 'Loi Climat Art. L229-68', 'sanction' => '100 000 € d’amende', 'risk' => 'Interdiction stricte sans bilan GES Scopes 1-2-3 et trajectoire de réduction validée.'],
            ['pattern' => '/([ée]co-?responsable|[ée]co-?con[çc]u|[ée]cologique|ami\s+de\s+la\s+nature)/i', 'term' => 'Allégation environnementale générique', 'severity' => 'flou_interdit', 'law' => 'Directive EmpCO (UE) 2024/825', 'sanction' => 'Amende jusqu’à 4% du CA annuel', 'risk' => 'Interdiction des allégations génériques sans certification officielle tierce (Ecolabel UE).'],
            ['pattern' => '/(100\s*%\s*vert|100\s*%\s*naturel|100\s*%\s*durable|100\s*%\s*propre)/i', 'term' => 'Allégation absolue non étayée', 'severity' => 'interdit_strict', 'law' => 'Directive Green Claims & Code Conso L121-2', 'sanction' => '300 000 € d’amende et 2 ans de prison', 'risk' => 'Pratique commerciale trompeuse sur allégation environnementale absolue.'],
            ['pattern' => '/(compens[ée]\s+carbone|compensation\s+carbone|contribution\s+climat)/i', 'term' => 'Compensation carbone non certifiée', 'severity' => 'non_justifie', 'law' => 'Décret 2022-539 & L229-68', 'sanction' => 'Obligation de retrait et sanctions DGCCRF', 'risk' => 'La compensation ne peut justifier la neutralité d’un produit ou d’une entreprise.'],
            ['pattern' => '/(biod[ée]gradable|compostable)/i', 'term' => 'Mention interdite sur produit/emballage', 'severity' => 'interdit_strict', 'law' => 'Loi AGEC Article 13', 'sanction' => '15 000 € par infraction', 'risk' => 'Interdiction formelle de la mention biodégradable sur tout produit ou emballage.'],
            ['pattern' => '/(bon\s+pour\s+la\s+plan[èe]te|pr[ée]serve\s+la\s+plan[èe]te|sauve\s+le\s+climat)/i', 'term' => 'Marketing écologique flou', 'severity' => 'flou_interdit', 'law' => 'Directive Green Claims', 'sanction' => 'Sanctions administratives et réputationnelles', 'risk' => 'Exigence de preuves scientifiques mesurables et vérifiées.']
        ];

        $totalPenalties = 0;
        foreach ($rules as $r) {
            if ($webContent && preg_match($r['pattern'], $webContent, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                $start = max(0, $pos - 60);
                $quote = mb_substr($webContent, $start, 150);
                $detectedClaims[] = [
                    'term' => $matches[0][0],
                    'quoteInText' => '« ... ' . trim($quote) . ' ... »',
                    'severity' => $r['severity'],
                    'legalRisk' => $r['risk'],
                    'lawArticle' => $r['law'],
                    'sanction' => $r['sanction'],
                    'requiredProof' => 'Certification indépendante ou ACV conforme ISO 14040/44',
                    'correctionNote' => 'Remplacer l’affirmation absolue par des données chiffrées vérifiables et auditées.'
                ];
                $totalPenalties += 12;
            }
        }

        $baseScore = !empty($csrDocumentText) ? 82 : 78;
        $score = max(25, min(95, $baseScore - $totalPenalties));
        $riskLevel = ($score < 40) ? 'critique' : (($score < 60) ? 'eleve' : (($score < 75) ? 'modere' : 'faible'));

        return json_encode([
            'score'          => $score,
            'riskLevel'      => $riskLevel,
            'summary'        => "Analyse du site $domain : " . (count($detectedClaims) > 0 ? count($detectedClaims) . " allégations environnementales détectées et confrontées aux Directives EmpCO, Green Claims et Loi Climat." : "Site analysé. Aucune allégation critique immédiate détectée sur la page d'accueil."),
            'claims'         => $detectedClaims,
            'prospection'    => [
                'scoreProspect'    => count($detectedClaims) > 1 ? 4 : 2,
                'signauxFaibles'   => count($detectedClaims) > 0 ? ['Présence d’allégations RSE sans label certifié', 'Exposition potentielle DGCCRF'] : ['Absence de communication bas-carbone structurée'],
                'besoinsPotentiels'=> ['Accompagnement Bilan Carbone Scopes 1-2-3', 'Sécurisation juridique des allégations Green Claims', 'Contribution Carbone Locale certifiée'],
                'recommandation'   => count($detectedClaims) > 0 ? 'Proposer un atelier de cadrage réglementaire et audit de conformité complet.' : 'Proposer un premier diagnostic d’empreinte carbone.',
                'messageApproche'  => "Bonjour, dans le cadre du renforcement des exigences Green Claims et CSRD, nous avons analysé $domain et identifié des opportunités concrètes pour valoriser vos engagements en toute conformité."
            ],
            'financialImpact' => count($detectedClaims) > 0 ? "Risque d’amende administrative et mise en demeure estimé de 15 000 € à 100 000 € selon le Code de la Consommation." : "Risque juridique direct faible."
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
