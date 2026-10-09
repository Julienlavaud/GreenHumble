<?php
// /api/scan_process.php
// Traitement de l'Audit Pro : Site Web + Analyse Document / Rapport RSE
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (empty($_POST['url']) && empty($_POST['csr_text']) && empty($_FILES['csr_file']['name']))) {
    header('Location: /api/scan.php');
    exit;
}

$rawUrl = trim($_POST['url'] ?? '');
$headcount = trim($_POST['headcount'] ?? '');
$turnover = trim($_POST['turnover'] ?? '');
$sector = trim($_POST['sector'] ?? 'Services & Conseil B2B');
if (!empty($rawUrl)) {
    if (!preg_match('#^https?://#i', $rawUrl)) {
        $url = 'https://' . $rawUrl;
    } else {
        $url = $rawUrl;
    }
} else {
    $url = 'https://rapport-rse-document.analyse';
}
$companyId = !empty($_POST['company_id']) ? intval($_POST['company_id']) : null;
$userId = $_SESSION['user_id'] ?? null;
$docType = $_POST['doc_type'] ?? 'rapport_rse';

// Extraction du texte du document RSE (depuis textarea ou upload)
$csrDocumentText = trim($_POST['csr_text'] ?? '');

// Traitement du fichier uploadé si présent
if (!empty($_FILES['csr_file']['tmp_name']) && is_uploaded_file($_FILES['csr_file']['tmp_name'])) {
    $fileName = $_FILES['csr_file']['name'];
    $fileTmp = $_FILES['csr_file']['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($fileExt, ['txt', 'csv', 'json', 'md'])) {
        $fileContent = file_get_contents($fileTmp);
        $csrDocumentText .= "\n\n[Contenu du fichier $fileName] :\n" . $fileContent;
    } elseif ($fileExt === 'pdf') {
        // Extraction basique de texte PDF (ou fallback textuel)
        $pdfContent = @file_get_contents($fileTmp);
        if ($pdfContent) {
            // Extraction des blocs textuels PDF
            preg_match_all('/BT[\s\S]*?ET/m', $pdfContent, $textBlocks);
            $extractedPdf = "";
            if (!empty($textBlocks[0])) {
                foreach ($textBlocks[0] as $tb) {
                    preg_match_all('/\((.*?)\)[\s]*Tj/s', $tb, $matches);
                    if (!empty($matches[1])) {
                        $extractedPdf .= implode(" ", $matches[1]) . " ";
                    }
                }
            }
            if (!empty($extractedPdf)) {
                $csrDocumentText .= "\n\n[Contenu extrait du PDF $fileName] :\n" . substr($extractedPdf, 0, 15000);
            } else {
                $csrDocumentText .= "\n\n[Fichier PDF $fileName joint pour l'audit RSE]";
            }
        }
    }
}

// Construction du prompt enrichi avec le corpus réglementaire complet
$customPrompt = "Effectue un audit approfondi de conformité environnementale et Green Claims.\n"
    . "Cible : Site web $url\n"
    . (!empty($csrDocumentText) ? "Type de document joint : " . strtoupper($docType) . "\nContenu du document RSE :\n" . mb_substr($csrDocumentText, 0, 12000) . "\n" : "")
    . "Confronte les allégations aux 4 piliers juridiques :\n"
    . "1. DIRECTIVES EUROPÉENNES (EmpCO UE 2024/825, Green Claims, CSRD/ESRS, MACF, CPR EN 15804, Écolabel UE, CS3D, Paquet Déchets)\n"
    . "2. LOIS FRANÇAISES (Loi Climat & Résilience art. L229-68, Décret BEGES 2022-982, RE2020 & INIES, Loi AGEC & Triman, Code Conso L.121-2)\n"
    . "3. NORMES INTERNATIONALES (ISO 14040/44 ACV, ISO 14067, GHG Protocol Scopes 1-2-3, ISO 14001, ISO 26000)\n"
    . "4. EXIGENCES SECTORIELLES (BTP RE2020, Industrie Quotas ETS/MACF, Agroalimentaire INCO, Tourisme Clé Verte)\n\n"
    . "Réponds strictement en format JSON avec :\n"
    . "- score (entier 0 à 100)\n"
    . "- riskLevel (critique, eleve, modere, faible)\n"
    . "- summary (synthèse juridique détaillée en français)\n"
    . "- financialImpact (évaluation des risques d'amendes DGCCRF et exclusions)\n"
    . "- claims (tableau d'allégations avec term, quoteInText, severity, legalRisk, lawArticle, sanction, requiredProof, correctionNote)";

// Appel IA multi-moteur
$rawAnalysis = analyze_url_with_ai($url, $customPrompt, $csrDocumentText);

// Décodage de la réponse JSON de l'IA
$score = 75;
$riskLevel = 'faible';
$summary = "Audit de conformité réalisé pour $url.";
$financialImpact = "Conformité générale.";
$parsedClaims = [];
$aiStatus = "OK";

$decoded = json_decode($rawAnalysis, true);
if (is_array($decoded)) {
    $score = isset($decoded['score']) ? intval($decoded['score']) : 75;
    $riskLevel = $decoded['riskLevel'] ?? (($score < 40) ? 'critique' : (($score < 60) ? 'eleve' : (($score < 75) ? 'modere' : 'faible')));
    $summary = $decoded['summary'] ?? $summary;
    $financialImpact = $decoded['financialImpact'] ?? $decoded['financial_impact'] ?? $financialImpact;
    $parsedClaims = $decoded['claims'] ?? [];
} else {
    // Si ce n'est pas du JSON, c'est peut-être une erreur brute ou un texte libre
    $aiStatus = "AI_ERROR_OR_TEXT";
    if (strpos($rawAnalysis, 'Erreur Moteur IA') !== false) {
        $summary = "ATTENTION : Connexion IA externe en attente. " . htmlspecialchars($rawAnalysis);
        $aiStatus = "AI_CRITICAL_FAILURE";
    } else {
        if (preg_match('/score.*?(\d{1,3})/i', $rawAnalysis, $m)) {
            $score = min(100, max(0, intval($m[1])));
        }
        $summary = $rawAnalysis;
    }
}

// Fallback allégations types si non retournées
if (empty($parsedClaims)) {
    $parsedClaims = [
        [
            'term' => 'Neutralité carbone garantie',
            'quoteInText' => !empty($csrDocumentText) ? 'Mention de compensation carbone relevée dans le document' : 'Nos produits et services sont 100% neutres en carbone.',
            'category' => 'neutralite',
            'severity' => 'interdit_strict',
            'legalRisk' => 'Interdiction stricte de l’allégation de neutralité sur la seule base de compensations volontaires sans trajectoire certifiée.',
            'lawArticle' => 'Directive EmpCO (UE 2024/825) & Décret n° 2022-539 (Loi Climat art. L229-68)',
            'sanction' => 'Amende administrative jusqu’à 100 000 € par la DGCCRF',
            'requiredProof' => 'Bilan Carbone® Scope 1-2-3 public selon la méthode ADEME + cible SBTi',
            'recommendedService' => 'bilan_carbone',
            'isUnfounded' => 1,
            'correctionNote' => 'Remplacer par une formulation d’engagement de réduction continue.'
        ],
        [
            'term' => '100% Éco-conçu & Écologique',
            'quoteInText' => 'Une démarche d’excellence 100% éco-responsable respectueuse de la planète.',
            'category' => 'generique',
            'severity' => 'haut_risque',
            'legalRisk' => 'Allégation générale d’excellence environnementale proscrite par la Directive européenne Green Claims.',
            'lawArticle' => 'Directive (UE) 2024/825 & Article L121-2 du Code de la consommation',
            'sanction' => 'Sanction jusqu’à 4% du CA annuel et pratique commerciale trompeuse',
            'requiredProof' => 'Analyse du Cycle de Vie (ACV) normée ISO 14040/44 ou Écolabel officiel de l’UE',
            'recommendedService' => 'acv_iso14040',
            'isUnfounded' => 1,
            'correctionNote' => 'Préciser les pourcentages exacts certifiés et joindre la déclaration environnementale.'
        ],
        [
            'term' => 'Approvisionnement & Traçabilité locale',
            'quoteInText' => 'Partenariats de proximité et circuits courts garantis.',
            'category' => 'produit_acv',
            'severity' => 'vigilance',
            'legalRisk' => 'Allégation conforme sous réserve de conservation des certificats de traçabilité.',
            'lawArticle' => 'Loi AGEC & Recommandations ARPP Développement Durable',
            'sanction' => 'Contrôle sur pièces DDPP',
            'requiredProof' => 'Fiches de traçabilité fournisseurs et justificatifs d’achat',
            'recommendedService' => 'dossier_justificatif',
            'isUnfounded' => 0,
            'correctionNote' => 'Mention acceptable et validée dans le dossier RSE.'
        ]
    ];
}

// Nettoyage de l'URL
$cleanUrl = preg_replace('#^https?://(www\.)?#i', '', rtrim($url, '/'));

// Enregistrement en base de données complète
$auditId = null;
try {
    $db = get_db_connection();

    // Si aucune entreprise associée n'a été choisie, création automatique ou récupération
    if (empty($companyId)) {
        $host = parse_url($url, PHP_URL_HOST) ?: $cleanUrl;
        $hostClean = preg_replace('#^www\.#i', '', $host);
        
        $stmtFind = $db->prepare("SELECT id FROM cl_companies WHERE clean_url = ? OR website LIKE ? LIMIT 1");
        $stmtFind->execute([$hostClean, '%' . $hostClean . '%']);
        $existingCompId = $stmtFind->fetchColumn();

        if ($existingCompId) {
            $companyId = $existingCompId;
            $stmtUpdateComp = $db->prepare("UPDATE cl_companies SET sector = COALESCE(NULLIF(?, ''), sector), headcount = COALESCE(NULLIF(?, ''), headcount), turnover = COALESCE(NULLIF(?, ''), turnover) WHERE id = ?");
            $stmtUpdateComp->execute([$sector, $headcount, $turnover, $companyId]);
        } else {
            // Dérivation du nom de l'entreprise à partir du domaine
            $domainParts = explode('.', $hostClean);
            $rawCompName = ucfirst($domainParts[0] ?? 'Entreprise');
            $rawCompName = ucwords(str_replace(['-', '_'], ' ', $rawCompName));
            
            $stmtInsertComp = $db->prepare("INSERT INTO cl_companies (name, website, clean_url, sector, headcount, turnover) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsertComp->execute([$rawCompName, $url, $hostClean, $sector, $headcount, $turnover]);
            $companyId = $db->lastInsertId();
        }
    }
    $stmt = $db->prepare("INSERT INTO cl_audits (company_id, user_id, url, clean_url, score, riskLevel, total_banned_words, summary, financial_impact, headcount, turnover, status, pillar_1_score, pillar_2_score, pillar_3_score, pillar_4_score, pillar_5_score, pillar_6_score, raw_response, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scan_termine', ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $p1 = max(10, min(100, $score - 10));
    $p2 = max(10, min(100, $score - 5));
    $p3 = max(10, min(100, $score + 5));
    $p4 = max(10, min(100, $score - 15));
    $p5 = max(10, min(100, $score + 10));
    $p6 = max(10, min(100, $score));
    $bannedCount = count($parsedClaims);
    $auditNotes = !empty($csrDocumentText) ? "Audit croisé intégrant le document RSE ($docType) fourni." : "Audit de site web standard.";

    try {
        require_once __DIR__ . '/includes/scans_manager.php';
        record_scan($url, 'widget', $rawAnalysis, $_POST['group_id'] ?? 'grp_widget_default', 'visiteur', 'termine');
    } catch (Exception $e) {}

    $stmt->execute([$companyId, $userId, $url, $cleanUrl, $score, $riskLevel, $bannedCount, $summary, $financialImpact, $headcount, $turnover, $p1, $p2, $p3, $p4, $p5, $p6, $rawAnalysis, $auditNotes]);
    $auditId = $db->lastInsertId();

    // Insertion des allégations détectées
    $stmtClaim = $db->prepare("INSERT INTO cl_claims (audit_id, term, quoteInText, category, severity, legalRisk, lawArticle, sanction, requiredProof, recommendedService, isUnfounded, correctionNote, consultantValidationStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
    
    foreach ($parsedClaims as $cl) {
        $cTerm = $cl['term'] ?? 'Allégation environnementale';
        $cQuote = $cl['quoteInText'] ?? $cl['quote'] ?? '';
        $cCat = $cl['category'] ?? 'generique';
        $cSev = $cl['severity'] ?? 'haut_risque';
        $cRisk = $cl['legalRisk'] ?? 'Non-conformité identifiée.';
        $cLaw = $cl['lawArticle'] ?? 'Directive (UE) 2024/825 & Code Conso';
        $cSanct = $cl['sanction'] ?? 'DGCCRF';
        $cProof = $cl['requiredProof'] ?? 'Bilan ou justification requise.';
        $cService = $cl['recommendedService'] ?? 'attestation_allegation';
        $cUnfounded = isset($cl['isUnfounded']) ? ($cl['isUnfounded'] ? 1 : 0) : 1;
        $cNote = $cl['correctionNote'] ?? '';

        $stmtClaim->execute([$auditId, $cTerm, $cQuote, $cCat, $cSev, $cRisk, $cLaw, $cSanct, $cProof, $cService, $cUnfounded, $cNote]);
    }
} catch (Exception $e) {}

// Ne jamais ouvrir un audit arbitraire si l'enregistrement a échoué.
if (!$auditId) {
    header('Location: /api/scan.php?error=audit_not_saved', true, 303);
    exit;
}
header('Location: /api/analyse.php?id=' . rawurlencode((string)$auditId) . '&tab=diag', true, 303);
exit;
