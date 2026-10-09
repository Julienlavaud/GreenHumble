<?php
// /api/includes/app_state.php
// Centralized Data Controller for Climat Local

/**
 * Returns the absolute path to the data file.
 * Specifically handled for OVH / standard environments.
 */
function get_app_state_file_path() {
    $dir = dirname(__DIR__, 2) . '/data';
    if (!file_exists($dir)) {
        @mkdir($dir, 0750, true);
    }
    return $dir . '/app_state.json';
}

/**
 * Default state containing all 17 missions, 8 legal texts, 4 cabinets, etc.
 */
function get_default_app_state() {
    return [
        "agency" => [
            "agencyName" => "Climat Local",
            "consultantName" => "Julien Lavaud",
            "consultantRole" => "Consultant Fondateur • Climat & Décarbonation",
            "agencyEmail" => "contact@climatlocal.com",
            "agencyPhone" => "04 73 00 00 00",
            "agencyCity" => "Auvergne-Rhône-Alpes / France",
            "agencyWebsite" => "https://climatlocal.com",
            "slogan" => "Bilan Carbone®, Stratégie RSE, ACV, FDES & Projets Territoriaux",
        ],
        "updatedAt" => date('c'),
        // Password hashes are provisioned explicitly; never ship default credentials.
        "passwordsMap" => [],
        "pageTexts" => [
            "hero_title" => "Scanner de conformité aux allégations environnementales et détection de greenwashing",
            "widget_subtitle" => "Vérifiez en 30 secondes la conformité de vos supports de communication avec la Directive Green Claims (UE) 2024/825 et le Décret n° 2022-539.",
            "footer_text" => "Cabinet Climat Local • Bilan Carbone®, Stratégie RSE, ACV, FDES & Projets Territoriaux",
            "widget_disclaimer" => "Cet audit préliminaire identifie les risques d'infraction au Code de la Consommation, à la Loi Climat et Résilience (Décret n° 2022-539) et à la Directive Européenne Green Claims. Réalisé par le cabinet d'ingénierie Climat Local.",
            "audit_intro" => "Audit de conformité environnementale et détection des allégations trompeuses (greenwashing) selon les référentiels officiels français et européens."
        ],
        "cabinets" => [
            [
                "id" => "cab_1",
                "cabinetName" => "Climat Local Siège Auvergne",
                "region" => "Auvergne-Rhône-Alpes (Puy-de-Dôme)",
                "email" => "contact@climatlocal.com",
                "phone" => "04 73 00 00 00",
                "website" => "https://climatlocal.com",
                "managerName" => "Julien Lavaud"
            ],
            [
                "id" => "cab_2",
                "cabinetName" => "Climat Local Antenne Métropole de Lyon",
                "region" => "Auvergne-Rhône-Alpes (Rhône)",
                "email" => "lyon@climatlocal.com",
                "phone" => "04 72 00 00 00",
                "website" => "https://climatlocal.com/lyon",
                "managerName" => "Sarah Benali"
            ],
            [
                "id" => "cab_3",
                "cabinetName" => "Climat Local Bureau Île-de-France",
                "region" => "Île-de-France (Paris)",
                "email" => "paris@climatlocal.com",
                "phone" => "01 40 00 00 00",
                "website" => "https://climatlocal.com/paris",
                "managerName" => "Thomas Roche"
            ],
            [
                "id" => "cab_4",
                "cabinetName" => "Climat Local Antenne Nouvelle-Aquitaine",
                "region" => "Nouvelle-Aquitaine (Bordeaux)",
                "email" => "bordeaux@climatlocal.com",
                "phone" => "05 56 00 00 00",
                "website" => "https://climatlocal.com/bordeaux",
                "managerName" => "Camille Dupuis"
            ]
        ],
        "serviceOpps" => [
            [ "id" => "m_1", "serviceKey" => "rse", "serviceTitle" => "Stratégie RSE & Reporting ESG", "defaultBudget" => "4 000 € à 8 000 €", "subsidyAmount" => "1 500 €", "duration" => "4 semaines", "description" => "Stratégie RSE globale, cartographie des parties prenantes, matrice de double matérialité.", "isActive" => true ],
            [ "id" => "m_2", "serviceKey" => "bc_bpi", "serviceTitle" => "Diag Décarbon'Action Bpifrance", "defaultBudget" => "5 000 €", "subsidyAmount" => "4 000 €", "duration" => "3 semaines", "description" => "Programme officiel Bpifrance / ADEME avec consultant habilité.", "isActive" => true ],
            [ "id" => "m_3", "serviceKey" => "bc", "serviceTitle" => "Bilan Carbone® Complet Scopes 1, 2 & 3", "defaultBudget" => "7 500 €", "subsidyAmount" => "4 000 €", "duration" => "6 semaines", "description" => "Mesure exhaustive des émissions directes et indirectes selon la méthode ABC.", "isActive" => true ],
            [ "id" => "m_4", "serviceKey" => "format_rse", "serviceTitle" => "Formation Dirigeants & Managers RSE", "defaultBudget" => "2 500 €", "subsidyAmount" => "100% OPCO", "duration" => "2 jours", "description" => "Formation certifiante Qualiopi sur les risques réglementaires.", "isActive" => true ],
            [ "id" => "m_5", "serviceKey" => "format_bc", "serviceTitle" => "Ateliers Climat & Sensibilisation Fresque", "defaultBudget" => "2 500 €", "subsidyAmount" => "100% OPCO", "duration" => "2 jours", "description" => "Fresque du Climat et ateliers 2tonnes pour engager les collaborateurs.", "isActive" => true ],
            [ "id" => "m_6", "serviceKey" => "externalisation", "serviceTitle" => "Direction RSE & Climat Externalisée", "defaultBudget" => "1 500 €/mois", "subsidyAmount" => "N/C", "duration" => "Annuel", "description" => "Accompagnement récurrent pour piloter votre stratégie durable.", "isActive" => true ],
            [ "id" => "m_7", "serviceKey" => "acv", "serviceTitle" => "Analyse du Cycle de Vie (ACV)", "defaultBudget" => "8 000 €", "subsidyAmount" => "ADEME Tremplin", "duration" => "6 semaines", "description" => "Évaluation multicritère normée ISO 14040/14044.", "isActive" => true ],
            [ "id" => "m_8", "serviceKey" => "labellisation", "serviceTitle" => "Accompagnement Labellisation RSE", "defaultBudget" => "5 500 €", "subsidyAmount" => "Aides Région", "duration" => "2 mois", "description" => "Préparation aux labels B-Corp, Engagé RSE, Lucie 26000.", "isActive" => true ],
            [ "id" => "m_9", "serviceKey" => "com_resp", "serviceTitle" => "Audit Communication Responsable", "defaultBudget" => "3 500 €", "subsidyAmount" => "N/C", "duration" => "3 semaines", "description" => "Revue juridique et sémantique anti-greenwashing.", "isActive" => true ],
            [ "id" => "m_10", "serviceKey" => "sensib_arbre", "serviceTitle" => "Sensibilisation Arbre & Haies", "defaultBudget" => "3 000 €", "subsidyAmount" => "Défiscalisation", "duration" => "1 mois", "description" => "Chantiers participatifs de plantation de haies bocagères locales.", "isActive" => true ],
            [ "id" => "m_11", "serviceKey" => "sensib_rse", "serviceTitle" => "Sensibilisation RSE & QVT", "defaultBudget" => "2 000 €", "subsidyAmount" => "OPCO", "duration" => "1 semaine", "description" => "Webinaires et défis internes pour ancrer la culture RSE.", "isActive" => true ],
            [ "id" => "m_12", "serviceKey" => "sensib_carbone", "serviceTitle" => "Sensibilisation Carbone & Sobriété", "defaultBudget" => "2 000 €", "subsidyAmount" => "OPCO", "duration" => "1 semaine", "description" => "Ateliers techniques de compréhension des enjeux carbone.", "isActive" => true ],
            [ "id" => "m_13", "serviceKey" => "act_pas_pas", "serviceTitle" => "Méthode ACT Pas à Pas ADEME", "defaultBudget" => "6 000 €", "subsidyAmount" => "Subvention ADEME", "duration" => "2 mois", "description" => "Stratégie de décarbonation alignée sur les accords de Paris.", "isActive" => true ],
            [ "id" => "m_14", "serviceKey" => "qcs", "serviceTitle" => "Management Intégré QCS", "defaultBudget" => "4 000 €", "subsidyAmount" => "N/C", "duration" => "3 semaines", "description" => "Qualité, Carbone et Sécurité intégrés aux processus.", "isActive" => true ],
            [ "id" => "m_15", "serviceKey" => "etude_territoriale", "serviceTitle" => "Diagnostic Carbone Territorial", "defaultBudget" => "10 000 €", "subsidyAmount" => "Fonds Vert", "duration" => "2 mois", "description" => "Bilan carbone de filière et résilience locale.", "isActive" => true ],
            [ "id" => "m_16", "serviceKey" => "redaction_methode", "serviceTitle" => "Rédaction de Référentiels", "defaultBudget" => "7 000 €", "subsidyAmount" => "N/C", "duration" => "1 mois", "description" => "Création de guides de bonnes pratiques sectorielles.", "isActive" => true ],
            [ "id" => "m_17", "serviceKey" => "compensation", "serviceTitle" => "Contribution Carbone Locale", "defaultBudget" => "5 000 €", "subsidyAmount" => "Label Bas-Carbone", "duration" => "Pluriannuel", "description" => "Financement direct de projets territoriaux certifiés.", "isActive" => true ]
        ],
        "legalTexts" => [
            [ "id" => "leg_1", "title" => "Loi Climat & Résilience", "reference" => "Décret 2022-539", "jurisdiction" => "france", "sanction" => "100 000 €", "description" => "Encadrement strict de la neutralité carbone publicitaire.", "isActive" => true, "weight" => 10, "link" => "https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000045570611" ],
            [ "id" => "leg_2", "title" => "Directive EmpCO", "reference" => "UE 2024/825", "jurisdiction" => "europe", "sanction" => "4% du CA", "description" => "Interdiction des allégations génériques (éco-responsable, vert).", "isActive" => true, "weight" => 10, "link" => "https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825" ],
            [ "id" => "leg_3", "title" => "CSRD", "reference" => "UE 2022/2464", "jurisdiction" => "europe", "sanction" => "75 000 €", "description" => "Reporting de durabilité et double matérialité obligatoires.", "isActive" => true, "weight" => 8, "link" => "https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32022L2464" ],
            [ "id" => "leg_4", "title" => "Loi AGEC", "reference" => "2020-105", "jurisdiction" => "france", "sanction" => "15 000 €", "description" => "Interdiction 'biodégradable' et 'respectueux de l'environnement'.", "isActive" => true, "weight" => 9, "link" => "https://www.legifrance.gouv.fr/loda/id/JORFTEXT000041553759/" ],
            [ "id" => "leg_5", "title" => "Décret BEGES", "reference" => "2022-982", "jurisdiction" => "france", "sanction" => "20 000 €", "description" => "Bilan GES complet incluant obligatoirement le Scope 3.", "isActive" => true, "weight" => 8, "link" => "https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000046005527" ],
            [ "id" => "leg_6", "title" => "Code Consommation", "reference" => "L. 121-2", "jurisdiction" => "france", "sanction" => "300 000 €", "description" => "Sanction des pratiques commerciales trompeuses environnementales.", "isActive" => true, "weight" => 10, "link" => "https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000049303668" ],
            [ "id" => "leg_7", "title" => "Green Claims", "reference" => "COM(2023) 166", "jurisdiction" => "europe", "sanction" => "CA proportionné", "description" => "Vérification scientifique ex-ante des allégations.", "isActive" => true, "weight" => 9, "link" => "https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=COM:2023:166:FIN" ],
            [ "id" => "leg_8", "title" => "RE2020 & FDES", "reference" => "ISO 14025", "jurisdiction" => "france", "sanction" => "Retrait PC", "description" => "ACV dynamique du bâtiment et fiches INIES vérifiées.", "isActive" => true, "weight" => 8, "link" => "https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000043876100" ]
        ],
        "scanVars" => [
            "aiProvider" => "gemini",
            "aiModel" => "gemini-3.8-flash",
            "aiApiKey" => "",
            "criticalThreshold" => 40,
            "bpifranceSubsidy" => 4000,
            "customBannedKeywords" => "neutre en carbone, éco-responsable, vert, bio, naturel, durable",
            "aiKeywordInstructions" => "Tu es l’IA GreenHumble, experte en conformité RSE, allégations environnementales et analyse de sites. Réponds en français, avec précision et sans tirer de conclusion au-delà des preuves.",
            "aiAnalysisInstructions" => "Analyse les pages accessibles du site et relève chaque allégation environnementale distincte dans son contexte. Signale aussi les limites de couverture du scan.",
            "aiEvidenceInstructions" => "Pour chaque signal, donne une citation exacte, l’URL de la page source, les éléments justificatifs manquants et les incertitudes à lever. N’invente jamais de citation ni de preuve.",
            "aiRiskInstructions" => "Distingue un signal à vérifier, une preuve insuffisante, un risque plausible et une non-conformité documentée. Explique le niveau de risque sans présenter une suspicion comme une infraction établie.",
            "aiCommercialInstructions" => "Propose une prochaine action et une priorité adaptées aux constats vérifiables. Ne recommande qu’une offre active du catalogue et relie chaque argument à une preuve effectivement relevée."
        ],
        "collaborators" => [
            [ "id" => "collab_1", "name" => "Julien Lavaud", "email" => "julien@climatlocal.com", "role" => "Admin", "avatarColor" => "bg-emerald-700", "assignedProspectsCount" => 0 ],
            [ "id" => "collab_2", "name" => "Sarah Benali", "email" => "sarah@climatlocal.com", "role" => "Consultante", "avatarColor" => "bg-teal-600", "assignedProspectsCount" => 0 ]
        ]
    ];
}

/**
 * Loads the application state from JSON or returns default.
 */
function load_app_state() {
    $file = get_app_state_file_path();
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) {
            // Merge with defaults to ensure all keys exist
            return array_replace_recursive(get_default_app_state(), $data);
        }
    }
    $default = get_default_app_state();
    save_app_state($default);
    return $default;
}

/**
 * Saves the application state to JSON.
 */
function save_app_state($data) {
    $file = get_app_state_file_path();
    $data['updatedAt'] = date('c');
    // Ensure data directory exists
    $dir = dirname($file);
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
    $res = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    if ($res !== false) {
        @chmod($file, 0640);
    }
    
    // Purger OPcache après chaque mise à jour
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    
    return $res;
}
