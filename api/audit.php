<?php
/**
 * GreenHumble v1.1 — Endpoint REST & Page HTML Officiels /api/audit
 * Architecture unifiée, sécurisée et compatible production OVH.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/app_state.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/ai_engine.php';
require_once __DIR__ . '/includes/scans_manager.php';

$storageFile = __DIR__ . '/../data/stored_audits.json';

/**
 * loadIASettings() : charge /data/ia_settings.json avant chaque appel IA.
 */
function loadIASettings() {
    $p1 = __DIR__ . '/../../data/ia_settings.json';
    $p2 = __DIR__ . '/../data/ia_settings.json';
    $path = file_exists($p1) ? $p1 : (file_exists($p2) ? $p2 : null);

    if ($path && file_exists($path)) {
        $raw = @file_get_contents($path);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return array_merge([
                'model' => 'gemini-2.0-flash',
                'temperature' => 0.3,
                'maxTokens' => 4096,
                'systemPrompt' => "Vous êtes l'expert juridique Climat Local. Vous analysez la conformité environnementale selon les directives EmpCO et Green Claims.",
                'userPrompt' => "Analysez ce site web et déterminez les risques de greenwashing.",
                'dictionaries' => "",
                'rules' => "",
                'businessOpportunityParams' => "",
                'greenClaimsParams' => ""
            ], $data);
        }
    }

    return [
        'model' => 'gemini-2.0-flash',
        'temperature' => 0.3,
        'maxTokens' => 4096,
        'systemPrompt' => "Vous êtes l'expert juridique Climat Local. Vous analysez la conformité environnementale selon les directives EmpCO et Green Claims.",
        'userPrompt' => "Analysez ce site web et déterminez les risques de greenwashing.",
        'dictionaries' => "",
        'rules' => "",
        'businessOpportunityParams' => "",
        'greenClaimsParams' => ""
    ];
}

/**
 * autoDeductEntrepriseData() : IA → secteur, CA, salariés.
 */
function autoDeductEntrepriseData($parsed, $webText, $domain) {
    $secteur = $parsed['entreprise_secteur'] ?? ($parsed['sector'] ?? null);
    if (empty($secteur) || $secteur === 'Non défini' || $secteur === 'À estimer' || $secteur === 'Services & Industrie') {
        $text = strtolower($webText);
        if (strpos($text, 'agro') !== false || strpos($text, 'alimentaire') !== false || strpos($text, 'bio') !== false || strpos($text, 'vin') !== false) {
            $secteur = 'Agroalimentaire & Boissons';
        } elseif (strpos($text, 'textile') !== false || strpos($text, 'mode') !== false || strpos($text, 'vêtement') !== false) {
            $secteur = 'Mode, Textile & Habillement';
        } elseif (strpos($text, 'cosmétique') !== false || strpos($text, 'beauté') !== false || strpos($text, 'soin') !== false) {
            $secteur = 'Cosmétique & Hygiène';
        } elseif (strpos($text, 'bâtiment') !== false || strpos($text, 'construction') !== false || strpos($text, 'isolation') !== false) {
            $secteur = 'BTP & Construction Durable';
        } elseif (strpos($text, 'énergie') !== false || strpos($text, 'solaire') !== false || strpos($text, 'panneau') !== false) {
            $secteur = 'Énergie & Environnement';
        } elseif (strpos($text, 'transport') !== false || strpos($text, 'logistique') !== false || strpos($text, 'livraison') !== false) {
            $secteur = 'Transport & Mobilité Durable';
        } elseif (strpos($text, 'conseil') !== false || strpos($text, 'logiciel') !== false || strpos($text, 'saas') !== false || strpos($text, 'service') !== false) {
            $secteur = 'Services, Conseil & Numérique';
        } else {
            $secteur = 'Industrie & Services B2B';
        }
    }

    $salaries = $parsed['entreprise_salaries'] ?? ($parsed['headcount'] ?? '11-50 salariés');
    $ca = $parsed['entreprise_ca'] ?? ($parsed['turnover'] ?? '2M€ - 10M€');

    return [
        'entreprise_secteur' => trim($secteur),
        'entreprise_salaries' => trim($salaries),
        'entreprise_ca' => trim($ca)
    ];
}

/**
 * validateIAResponse() : vérifie structure JSON, normalise et fiabilise.
 */
function validateIAResponse($parsed, $domain) {
    // Fail closed : une réponse absente/incomplète ne doit jamais devenir un score artificiel.
    $hasScore = is_array($parsed) &&
        (array_key_exists('riskScore', $parsed) || array_key_exists('score', $parsed));
    $rawScore = is_array($parsed) ? ($parsed['riskScore'] ?? ($parsed['score'] ?? null)) : null;
    $isIncomplete = !is_array($parsed) ||
        empty($parsed) ||
        (($parsed['status'] ?? '') === 'analysis_incomplete') ||
        (($parsed['analysisComplete'] ?? true) === false) ||
        !$hasScore ||
        !is_numeric($rawScore);

    if ($isIncomplete) {
        $claims = is_array($parsed['detectedClaims'] ?? null)
            ? $parsed['detectedClaims']
            : (is_array($parsed['claims'] ?? null) ? $parsed['claims'] : []);
        $summary = is_array($parsed) && is_string($parsed['summary'] ?? null)
            ? $parsed['summary']
            : "Analyse incomplète : aucune conclusion fiable n’a pu être produite.";
        return array_merge(is_array($parsed) ? $parsed : [], [
            'status' => 'analysis_incomplete',
            'analysisComplete' => false,
            'riskScore' => null,
            'riskLevel' => 'a_evaluer',
            'detectedClaims' => $claims,
            'clientSummary' => [
                'headline' => "Analyse incomplète : {$domain}",
                'riskVerdict' => $summary,
                'keyUrgentAction' => "Relancer l’analyse après vérification de la configuration du fournisseur IA.",
                'immediateRisks' => [],
                'stepsToCompliance' => []
            ],
            'commercialProspectingKit' => null
        ]);
    }

    $score = intval($rawScore);
    $score = max(0, min(100, $score));

    $riskLevel = 'eleve';
    if ($score >= 75) $riskLevel = 'faible';
    elseif ($score >= 55) $riskLevel = 'modere';
    elseif ($score >= 35) $riskLevel = 'eleve';
    else $riskLevel = 'critique';

    $claims = $parsed['detectedClaims'] ?? ($parsed['claims'] ?? []);
    if (!is_array($claims)) $claims = [];

    // Ne jamais fabriquer de conclusions juridiques ou de recommandations non fournies par l'analyse.
    $clientSummary = is_array($parsed['clientSummary'] ?? null)
        ? $parsed['clientSummary']
        : [
            'headline' => "Résultat d'analyse à examiner : {$domain}",
            'riskVerdict' => "Score calculé : {$score}/100. Ce score ne constitue pas, à lui seul, une conclusion juridique.",
            'keyUrgentAction' => "Vérifier les allégations et leurs justificatifs avant toute conclusion.",
            'immediateRisks' => [],
            'stepsToCompliance' => []
        ];

    // Le kit commercial doit provenir explicitement de la réponse validée ou du catalogue approuvé.
    // Aucun prix, subside, forfait ou argumentaire juridique n'est inventé ici.
    $commercialProspectingKit = $parsed['commercialProspectingKit'] ?? ($parsed['prospection'] ?? null);
    if (!is_array($commercialProspectingKit)) {
        $commercialProspectingKit = null;
    }

    return array_merge($parsed, [
        'analysisComplete' => true,
        'riskScore' => $score,
        'riskLevel' => $riskLevel,
        'detectedClaims' => $claims,
        'clientSummary' => $clientSummary,
        'commercialProspectingKit' => $commercialProspectingKit
    ]);
}

// Helper de lecture des audits
function get_stored_audits($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = @file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// Helper d'écriture des audits
function save_stored_audits($file, $audits) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($file, json_encode($audits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isHtmlRequest = (strpos($acceptHeader, 'text/html') !== false || isset($_GET['view'])) && !isset($_GET['json']) && $method === 'GET' && empty($_GET['url']) && empty($_GET['id']);

if (!$isHtmlRequest) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

if ($method === 'GET') {
    $audits = get_stored_audits($storageFile);
    $idQuery = trim($_GET['id'] ?? '');

    if (!empty($idQuery)) {
        foreach ($audits as $item) {
            if (($item['id'] ?? '') === $idQuery) {
                echo json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Audit non trouvé pour l\'ID spécifié.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'total' => count($audits),
        'audits' => $audits
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true) ?: $_POST;

    $url = trim($inputData['url'] ?? $_GET['url'] ?? '');

    if (empty($url)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'URL manquante ou invalide dans le corps de la requête POST.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    $domain = strtolower(preg_replace('/^www\./i', '', parse_url($url, PHP_URL_HOST) ?: $url));
    $type_audit = $inputData['type_audit'] ?? 'full';
    $liste_pre_audit = is_array($inputData['liste_pre_audit'] ?? null) ? $inputData['liste_pre_audit'] : [];
    $utilisateur = $inputData['utilisateur'] ?? 'Julien Lavaud';

    // 1. Charger les paramètres IA
    $iaSettings = loadIASettings();

    // 2. Scraping & Analyse IA
    $webContent = ai_engine_scrape_website_content($url);
    $aiResult = analyze_url_with_ai($url, $inputData['customPrompt'] ?? null, $inputData['csrDocumentText'] ?? null);
    $resultData = is_array($aiResult) ? $aiResult : (json_decode($aiResult, true) ?: []);

    // 3. Auto-déduire secteur, CA, salariés
    $enterpriseData = autoDeductEntrepriseData($resultData, $webContent, $domain);

    // 4. Valider la réponse IA
    $validatedAudit = validateIAResponse($resultData, $domain);

    // Ne pas enregistrer ni présenter un résultat incomplet comme un audit réussi.
    if (($validatedAudit['status'] ?? '') === 'analysis_incomplete' || $validatedAudit['riskScore'] === null) {
        http_response_code(503);
        echo json_encode([
            'status' => 'analysis_incomplete',
            'analysisComplete' => false,
            'error' => 'L’analyse IA est incomplète. Aucun score de risque ni kit de prospection n’a été généré.',
            'summary' => $validatedAudit['clientSummary']['riskVerdict'] ?? 'Vérifiez la configuration du fournisseur IA puis réessayez.',
            'riskScore' => null,
            'riskLevel' => 'a_evaluer',
            'detectedClaims' => $validatedAudit['detectedClaims'] ?? [],
            'commercialProspectingKit' => null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $auditId = 'aud_' . date('Ymd_His') . '_' . substr(md5($url), 0, 6);
    $validatedAudit['id'] = $auditId;
    $validatedAudit['url'] = $url;
    $validatedAudit['analyzedAt'] = date('c');
    $validatedAudit['type_audit'] = $type_audit;
    $validatedAudit['liste_pre_audit'] = $liste_pre_audit;
    $validatedAudit['entreprise_secteur'] = $enterpriseData['entreprise_secteur'];
    $validatedAudit['entreprise_salaries'] = $enterpriseData['entreprise_salaries'];
    $validatedAudit['entreprise_ca'] = $enterpriseData['entreprise_ca'];

    // Sauvegarde dans stored_audits.json
    $audits = get_stored_audits($storageFile);
    array_unshift($audits, $validatedAudit);
    save_stored_audits($storageFile, array_slice($audits, 0, 100));

    // 5. Formater resultat_ia pour scans.json
    $formattedResultatIa = [
        'score' => $validatedAudit['riskScore'],
        'summary' => $validatedAudit['clientSummary']['riskVerdict'] ?? ($validatedAudit['clientSummary']['headline'] ?? 'Audit GreenHumble'),
        'risks' => $validatedAudit['detectedClaims'],
        'prospection' => $validatedAudit['commercialProspectingKit'],
        'recommendations' => $validatedAudit['clientSummary']['stepsToCompliance'] ?? [],
        'raw' => [
            'model' => $iaSettings['model']
        ],
        'type_audit' => $type_audit,
        'entreprise_secteur' => $enterpriseData['entreprise_secteur'],
        'entreprise_salaries' => $enterpriseData['entreprise_salaries'],
        'entreprise_ca' => $enterpriseData['entreprise_ca']
    ];

    // 6. Enregistrement dans scans.json via appendScanToJson() dans record_scan()
    try {
        $savedScan = record_scan(
            $url,
            'diagnostic',
            $formattedResultatIa,
            $type_audit,
            $liste_pre_audit,
            $utilisateur,
            'ok',
            $enterpriseData
        );
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => "Impossible d'écrire dans les données"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 7. Retour JSON strict, complet et validé
    $responsePayload = [
        'id' => $auditId,
        'scan_id' => $savedScan['id'],
        'url' => $url,
        'statut' => 'ok',
        'type_audit' => $type_audit,
        'liste_pre_audit' => $liste_pre_audit,
        'entreprise_secteur' => $enterpriseData['entreprise_secteur'],
        'entreprise_salaries' => $enterpriseData['entreprise_salaries'],
        'entreprise_ca' => $enterpriseData['entreprise_ca'],
        'projet_id' => $savedScan['projet_id'] ?? null,
        'riskScore' => $validatedAudit['riskScore'],
        'riskLevel' => $validatedAudit['riskLevel'],
        'detectedClaims' => $validatedAudit['detectedClaims'],
        'clientSummary' => $validatedAudit['clientSummary'],
        'commercialProspectingKit' => $validatedAudit['commercialProspectingKit'],
        'resultat_ia' => $formattedResultatIa,
        'analyzedAt' => $validatedAudit['analyzedAt'],
        'companyName' => $validatedAudit['companyName'] ?? ucfirst(explode('.', $domain)[0]),
        'suggested_domain' => $domain
    ];

    echo json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'DELETE') {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true) ?: $_GET;
    $idToDelete = trim($inputData['id'] ?? '');

    if (empty($idToDelete)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID d\'audit manquant pour la suppression.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $audits = get_stored_audits($storageFile);
    $filtered = array_values(array_filter($audits, function($item) use ($idToDelete) {
        return ($item['id'] ?? '') !== $idToDelete;
    }));

    save_stored_audits($storageFile, $filtered);
    echo json_encode(['success' => true, 'message' => "Audit {$idToDelete} supprimé avec succès."], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode HTTP non autorisée.'], JSON_UNESCAPED_UNICODE);
exit;
