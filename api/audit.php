<?php
/**
 * GreenHumble v1.1 — Endpoint REST & Page HTML Officiels /api/audit
 * Architecture unifiée, sécurisée et compatible production OVH.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/app_state.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit_database.php';
require_once __DIR__ . '/includes/ai_engine.php';
require_once __DIR__ . '/includes/scans_manager.php';
require_once __DIR__ . '/includes/auth.php';

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
function autoDeductEntrepriseData($parsed, $webText, $domain, $registryData = null, $manualData = []) {
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

    $manualHeadcount = trim((string)($manualData['headcount'] ?? ''));
    $manualTurnover = trim((string)($manualData['turnover'] ?? ''));
    $registryHeadcount = is_array($registryData) ? gh_registry_headcount_label($registryData['tranche_effectif_salarie'] ?? '') : null;
    $registryCa = null;
    $registryCaYear = null;
    if (is_array($registryData['finances'] ?? null)) {
        foreach ($registryData['finances'] as $period => $finance) {
            $amount = is_array($finance) ? ($finance['ca'] ?? null) : null;
            if (is_numeric($amount) && (float)$amount > 0) {
                $registryCa = number_format((float)$amount, 0, ',', ' ') . ' €';
                $registryCaYear = null; // L'API ne fournit pas toujours l'année d'exercice.
                break;
            }
        }
    }

    $headcount = $manualHeadcount !== '' ? $manualHeadcount : $registryHeadcount;
    $turnover = $manualTurnover !== '' ? $manualTurnover : $registryCa;
    $registrySector = is_array($registryData) ? trim((string)($registryData['libelle_activite_principale'] ?? '')) : '';
    if ($registrySector !== '') $secteur = $registrySector;
    return [
        'entreprise_secteur' => trim($secteur),
        'entreprise_secteur_source' => $registrySector !== '' ? 'registre_public_insee' : 'estimation_non_verifiee',
        'entreprise_salaries' => $headcount,
        'entreprise_salaries_source' => $manualHeadcount !== '' ? 'saisie_utilisateur' : ($registryHeadcount !== null ? 'registre_public_insee' : 'non_communique'),
        'entreprise_salaries_annee' => is_array($registryData) ? ($registryData['annee_tranche_effectif_salarie'] ?? null) : null,
        'entreprise_ca' => $turnover,
        'entreprise_ca_source' => $manualTurnover !== '' ? 'saisie_utilisateur' : ($registryCa !== null ? 'registre_public_etat' : 'non_communique'),
        'entreprise_ca_periode' => $registryCaYear,
        'entreprise_ca_estimation' => null,
        'company_name' => is_array($registryData) ? ($registryData['nom_raison_sociale'] ?? ($registryData['nom_complet'] ?? null)) : null,
        'company_siren' => is_array($registryData) ? ($registryData['siren'] ?? null) : null,
        'company_naf' => is_array($registryData) ? ($registryData['activite_principale'] ?? null) : null
    ];
}

/**
 * Recherche une entreprise dans l'API publique de l'État.
 * Les résultats ne sont utilisés que si le SIREN est fourni ou si le nom exact est unique.
 */
function gh_lookup_public_company_registry($query, $expectedSiren = null) {
    $query = trim((string)$query);
    if ($query === '') return null;

    $url = 'https://recherche-entreprises.api.gouv.fr/search?q=' . rawurlencode($query) . '&page=1&per_page=10';
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'timeout' => 4,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\nUser-Agent: GreenHumble/1.0\r\n"
    ]]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') return null;
    $payload = json_decode($raw, true);
    if (!is_array($payload) || !is_array($payload['results'] ?? null)) return null;

    $results = array_values(array_filter($payload['results'], static function ($item) use ($expectedSiren, $query) {
        if (!is_array($item) || !preg_match('/^\\d{9}$/', (string)($item['siren'] ?? ''))) return false;
        if ($expectedSiren !== null && $expectedSiren !== '') return (string)$item['siren'] === (string)$expectedSiren;
        $normalize = static function ($value) {
            $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$value) : (string)$value;
            if (!is_string($ascii)) $ascii = (string)$value;
            return strtolower(preg_replace('/[^a-z0-9]/', '', $ascii));
        };
        $officialName = $item['nom_raison_sociale'] ?? ($item['nom_complet'] ?? '');
        return $normalize($officialName) !== '' && $normalize($officialName) === $normalize($query);
    }));

    if ($expectedSiren !== null && $expectedSiren !== '') {
        foreach ($results as $item) if ((string)$item['siren'] === (string)$expectedSiren) return $item;
        return null;
    }
    return count($results) === 1 ? $results[0] : null;
}

function gh_registry_headcount_label($code) {
    $labels = [
        'NN' => null, '00' => '0 salarié', '01' => '1 à 2 salariés', '02' => '3 à 5 salariés',
        '03' => '6 à 9 salariés', '11' => '10 à 19 salariés', '12' => '20 à 49 salariés',
        '21' => '50 à 99 salariés', '22' => '100 à 199 salariés', '31' => '200 à 249 salariés',
        '32' => '250 à 499 salariés', '41' => '500 à 999 salariés', '42' => '1 000 à 1 999 salariés',
        '51' => '2 000 à 4 999 salariés', '52' => '5 000 à 9 999 salariés', '53' => '10 000 salariés ou plus'
    ];
    return $labels[(string)$code] ?? null;
}

/**
 * validateIAResponse() : vérifie structure JSON, normalise et fiabilise.
 */
require_once __DIR__ . '/includes/audit_response.php';

function validateIAResponse($parsed, $domain, $sourceText = '', $sourceUrl = '', $serviceCatalog = []) {
    return gh_validate_ia_response($parsed, (string)$domain, (string)$sourceText, (string)$sourceUrl, is_array($serviceCatalog) ? $serviceCatalog : []);
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

// Toute modification est protégée par un verrou stable et relit le fichier
// sous verrou pour éviter de perdre les audits écrits par des requêtes concurrentes.
function mutate_stored_audits($file, callable $mutator) {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) return false;
    if (!is_writable($dir)) return false;

    $lock = @fopen($file . '.lock', 'c');
    if ($lock === false) return false;

    try {
        if (!@flock($lock, LOCK_EX)) return false;
        @chmod($file . '.lock', 0640);

        $audits = [];
        if (is_file($file)) {
            $content = @file_get_contents($file);
            if ($content === false) return false;
            if (trim($content) !== '') {
                $decoded = json_decode($content, true);
                if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                    error_log('GreenHumble: stored_audits.json invalide; modification interrompue pour préserver les données.');
                    return false;
                }
                $audits = $decoded;
            }
        }

        $updatedAudits = $mutator($audits);
        if (!is_array($updatedAudits)) return false;
        $json = json_encode(array_values($updatedAudits), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) return false;

        $tmp = tempnam($dir, '.gh-audit-');
        if ($tmp === false) return false;
        $written = @file_put_contents($tmp, $json, LOCK_EX);
        if ($written !== strlen($json)) {
            @unlink($tmp);
            return false;
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        @chmod($file, 0640);
        return true;
    } catch (Throwable $e) {
        error_log('GreenHumble: échec de mise à jour de stored_audits.json (' . get_class($e) . ').');
        return false;
    } finally {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
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
    // Les audits peuvent contenir des données commerciales : lecture réservée aux utilisateurs authentifiés.
    require_api_role(['admin', 'pipeline', 'collaborator', 'consultant']);
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
    $aiResult = analyze_url_with_ai($url, $inputData['customPrompt'] ?? null, $inputData['csrDocumentText'] ?? null, $webContent);
    $resultData = is_array($aiResult) ? $aiResult : (json_decode($aiResult, true) ?: []);

    // 3. Enrichissement avec le registre public de l'État, sans inventer les champs absents.
    $registryQuery = trim((string)($resultData['companyName'] ?? ''));
    $expectedSiren = null;
    $companyId = filter_var($inputData['company_id'] ?? '', FILTER_VALIDATE_INT);
    if ($companyId && $companyId > 0) {
        try {
            $companyStmt = get_db_connection()->prepare('SELECT name, siren, website FROM cl_companies WHERE id = ? LIMIT 1');
            $companyStmt->execute([$companyId]);
            $selectedCompany = $companyStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $selectedHost = parse_url((string)($selectedCompany['website'] ?? ''), PHP_URL_HOST);
            $selectedDomain = strtolower(preg_replace('/^www\\./i', '', (string)($selectedHost ?: '')));
            if ($selectedDomain === $domain && preg_match('/^\\d{9}$/', (string)($selectedCompany['siren'] ?? ''))) {
                $expectedSiren = (string)$selectedCompany['siren'];
                $registryQuery = $expectedSiren;
            }
        } catch (Throwable $e) {
            error_log('GreenHumble: impossible de résoudre l’entreprise sélectionnée dans cl_companies.');
        }
    }
    $registryData = gh_lookup_public_company_registry($registryQuery, $expectedSiren);
    $enterpriseData = autoDeductEntrepriseData($resultData, $webContent, $domain, $registryData, $inputData);
    if (is_array($registryData) && !empty($enterpriseData['company_name'])) {
        $resultData['companyName'] = $enterpriseData['company_name'];
    }
    $enterpriseData['company_name'] = trim((string)($resultData['companyName'] ?? '')) ?: ucfirst(explode('.', $domain)[0]);

    // 4. Valider la réponse IA
    $appStateForOffers = load_app_state();
    $serviceCatalog = array_values(array_filter(
        is_array($appStateForOffers['serviceOpps'] ?? null) ? $appStateForOffers['serviceOpps'] : [],
        static function ($service) { return is_array($service) && ($service['isActive'] ?? true); }
    ));
    $validatedAudit = validateIAResponse($resultData, $domain, $webContent, $url, $serviceCatalog);
    if (!empty($enterpriseData['company_name'])) $validatedAudit['companyName'] = $enterpriseData['company_name'];
    $validatedAudit['companyRegistry'] = [
        'provider' => !empty($enterpriseData['company_siren']) ? 'recherche-entreprises.api.gouv.fr' : null,
        'siren' => $enterpriseData['company_siren'] ?? null,
        'naf' => $enterpriseData['company_naf'] ?? null,
        'sectorSource' => $enterpriseData['entreprise_secteur_source'] ?? 'estimation_non_verifiee',
        'headcountSource' => $enterpriseData['entreprise_salaries_source'] ?? 'non_communique',
        'headcountYear' => $enterpriseData['entreprise_salaries_annee'] ?? null,
        'turnoverSource' => $enterpriseData['entreprise_ca_source'] ?? 'non_communique',
        'turnoverPeriod' => $enterpriseData['entreprise_ca_periode'] ?? null
    ];

    // Ne pas enregistrer ni présenter un résultat incomplet comme un audit réussi.
    if (($validatedAudit['status'] ?? '') === 'analysis_incomplete' || $validatedAudit['riskScore'] === null) {
        http_response_code(503);
        echo json_encode([
            'status' => 'analysis_incomplete',
            'analysisComplete' => false,
            'diagnosticCode' => $validatedAudit['diagnosticCode'] ?? 'analysis_incomplete',
            'error' => 'L’analyse IA est incomplète. Aucun score de risque ni kit de prospection n’a été généré.',
            'summary' => $validatedAudit['clientSummary']['riskVerdict'] ?? 'Vérifiez la configuration du fournisseur IA puis réessayez.',
            'riskScore' => null,
            'riskLevel' => 'a_evaluer',
            'detectedClaims' => $validatedAudit['detectedClaims'] ?? [],
            'commercialProspectingKit' => null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Identifiant unique même si plusieurs audits de la même URL démarrent la même seconde.
    $auditId = 'aud_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8));
    $validatedAudit['id'] = $auditId;
    $validatedAudit['url'] = $url;
    $validatedAudit['analyzedAt'] = date('c');
    $validatedAudit['type_audit'] = $type_audit;
    $validatedAudit['liste_pre_audit'] = $liste_pre_audit;
    $validatedAudit['entreprise_secteur'] = $enterpriseData['entreprise_secteur'];
    $validatedAudit['entreprise_salaries'] = $enterpriseData['entreprise_salaries'];
    $validatedAudit['entreprise_ca'] = $enterpriseData['entreprise_ca'];

    // Sauvegarde sérialisée pour ne pas écraser un audit ajouté en parallèle.
    if (!mutate_stored_audits($storageFile, static function (array $audits) use ($validatedAudit) {
        array_unshift($audits, $validatedAudit);
        return array_slice($audits, 0, 100);
    })) {
        error_log('GreenHumble: audit validé mais stockage principal indisponible.');
        http_response_code(503);
        echo json_encode([
            'status' => 'storage_unavailable',
            'analysisComplete' => false,
            'error' => 'Analyse réalisée mais impossible à enregistrer. Aucun résultat n’est présenté comme sauvegardé ; réessayez plus tard.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Formater resultat_ia pour scans.json
    $formattedResultatIa = [
        'auditId' => $auditId,
        'score' => $validatedAudit['riskScore'],
        'riskLevel' => $validatedAudit['riskLevel'],
        'companyName' => $validatedAudit['companyName'] ?? $enterpriseData['company_name'],
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
        'entreprise_ca' => $enterpriseData['entreprise_ca'],
        'companyRegistry' => $validatedAudit['companyRegistry']
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

    // Persistance commerciale SQL facultative : le scan JSON reste disponible en secours.
    $databaseAuditId = null;
    $databaseSaved = gh_persist_commercial_audit($validatedAudit, $enterpriseData, $databaseAuditId);

    // 7. Retour JSON strict, complet et validé
    $responsePayload = [
        'id' => $auditId,
        'scan_id' => $savedScan['id'],
        'databaseSaved' => $databaseSaved,
        'analysisId' => $databaseAuditId ?: $auditId,
        'url' => $url,
        'statut' => 'ok',
        'type_audit' => $type_audit,
        'liste_pre_audit' => $liste_pre_audit,
        'entreprise_secteur' => $enterpriseData['entreprise_secteur'],
        'entreprise_salaries' => $enterpriseData['entreprise_salaries'],
        'entreprise_ca' => $enterpriseData['entreprise_ca'],
        'companyRegistry' => $validatedAudit['companyRegistry'],
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
    require_api_role(['admin']);
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true) ?: $_GET;
    $idToDelete = trim($inputData['id'] ?? '');

    if (empty($idToDelete)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID d\'audit manquant pour la suppression.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!mutate_stored_audits($storageFile, static function (array $audits) use ($idToDelete) {
        return array_values(array_filter($audits, static function ($item) use ($idToDelete) {
            return ($item['id'] ?? '') !== $idToDelete;
        }));
    })) {
        http_response_code(503);
        echo json_encode(['error' => 'Impossible de mettre à jour l’historique des audits. Réessayez plus tard.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['success' => true, 'message' => "Audit {$idToDelete} supprimé avec succès."], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode HTTP non autorisée.'], JSON_UNESCAPED_UNICODE);
exit;
