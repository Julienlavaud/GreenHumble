<?php
/**
 * CLIMAT LOCAL - API REST BACKEND & GESTIONNAIRE D'AUDITS (PHP 8.x)
 * Fichier : api.php
 * Compatible : OVH, IONOS, o2switch, LWS, Apache & Nginx
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    switch ($action) {
        // 1. Diagnostic Serveur & Santé Système
        case 'diagnostic':
            $db = get_db_connection();
            $dbConnected = ($db !== null);
            $tableCount = 0;
            if ($dbConnected) {
                $stmt = $db->query("SHOW TABLES LIKE 'cl_%'");
                $tableCount = count($stmt->fetchAll(PDO::FETCH_COLUMN));
            }

            $geminiTest = test_gemini_api();

            $vitalFolders = [
                'config' => is_writable(CONFIG_DIR),
                'data' => is_writable(DATA_DIR),
                'logs' => is_writable(LOGS_DIR),
                'backups' => is_writable(BACKUPS_DIR),
            ];

            // Recent errors
            $recentErrors = [];
            $errorLogFile = LOGS_DIR . '/error.log';
            if (file_exists($errorLogFile)) {
                $lines = array_slice(file($errorLogFile), -15);
                $recentErrors = array_map('trim', $lines);
            }

            echo json_encode([
                'success' => true,
                'system' => [
                    'app_name' => APP_NAME,
                    'version' => APP_VERSION,
                    'php_version' => PHP_VERSION,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in',
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                ],
                'database' => [
                    'connected' => $dbConnected,
                    'tables_count' => $tableCount,
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'name' => $_ENV['DB_NAME'] ?? '',
                ],
                'gemini_api' => $geminiTest,
                'filesystem' => $vitalFolders,
                'recent_errors' => $recentErrors,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // 2. Test direct de l'API Gemini
        case 'test_gemini':
            $key = $inputData['gemini_key'] ?? null;
            $res = test_gemini_api($key);
            echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // 3. Récupération des audits enregistrés
        case 'audits':
            $db = get_db_connection();
            if (!$db) {
                // Fallback mémoire / JSON local si MySQL non encore configuré
                $jsonFile = DATA_DIR . '/audits.json';
                $audits = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
                echo json_encode(['success' => true, 'source' => 'file_fallback', 'audits' => $audits ?: []]);
                break;
            }

            $stmt = $db->query("SELECT * FROM `cl_audits` ORDER BY `analyzed_at` DESC LIMIT 100");
            $rows = $stmt->fetchAll();
            $audits = [];
            foreach ($rows as $row) {
                // Fetch claims
                $claimStmt = $db->prepare("SELECT * FROM `cl_detected_claims` WHERE `audit_id` = ?");
                $claimStmt->execute([$row['id']]);
                $claims = $claimStmt->fetchAll();

                $audits[] = [
                    'id' => $row['id'],
                    'url' => $row['url'],
                    'cleanUrl' => $row['clean_url'],
                    'companyName' => $row['company_name'],
                    'sector' => $row['sector'],
                    'siret' => $row['siret'],
                    'region' => $row['region'],
                    'riskLevel' => $row['risk_level'],
                    'riskScore' => (int)$row['risk_score'],
                    'totalBannedWordsDetected' => (int)$row['total_banned_words_detected'],
                    'source' => $row['source'],
                    'prospectStatus' => $row['prospect_status'],
                    'prospectNotes' => $row['prospect_notes'],
                    'analyzedAt' => $row['analyzed_at'],
                    'clientSummary' => json_decode($row['client_summary_json'] ?? '{}', true),
                    'commercialProspectingKit' => json_decode($row['commercial_kit_json'] ?? '{}', true),
                    'detectedClaims' => array_map(function($c) {
                        return [
                            'id' => $c['id'],
                            'term' => $c['term'],
                            'quoteInText' => $c['quote_in_text'],
                            'category' => $c['category'],
                            'severity' => $c['severity'],
                            'legalRisk' => $c['legal_risk'],
                            'lawArticle' => $c['law_article'],
                            'sanction' => $c['sanction'],
                            'requiredProof' => $c['required_proof'],
                            'recommendedService' => $c['recommended_service'],
                            'isUnfounded' => (bool)$c['is_unfounded'],
                            'consultantValidationStatus' => $c['consultant_validation_status'],
                            'correctionNote' => $c['correction_note'],
                        ];
                    }, $claims),
                ];
            }
            echo json_encode(['success' => true, 'audits' => $audits], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // 4. Consultation du registre Sirene / Pappers
        case 'company_lookup':
            $q = trim((string)($inputData['q'] ?? $_GET['q'] ?? ''));
            if (empty($q)) {
                echo json_encode(['success' => true, 'result' => null]);
                break;
            }

            $cleanQuery = preg_replace('/^https?:\/\//i', '', $q);
            $cleanQuery = preg_replace('/^www\./i', '', $cleanQuery);
            $cleanQuery = explode('/', $cleanQuery)[0];
            $cleanQuery = explode('.', $cleanQuery)[0];

            $url = "https://recherche-entreprises.api.gouv.fr/search?q=" . urlencode($cleanQuery) . "&per_page=1";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => 'ClimatLocal-PHP-Scanner/2.4',
                CURLOPT_TIMEOUT => 4,
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($res ?: '', true);
            $result = null;
            if (!empty($data['results'][0])) {
                $m = $data['results'][0];
                $siege = $m['siege'] ?? [];
                $siren = $m['siren'] ?? '';
                $result = [
                    'companyName' => $m['nom_complet'] ?? $cleanQuery,
                    'siren' => $siren,
                    'siret' => $siege['siret'] ?? $siren,
                    'address' => $siege['adresse'] ?? '',
                    'postalCode' => $siege['code_postal'] ?? '',
                    'city' => $siege['libelle_commune'] ?? '',
                    'region' => $siege['region'] ?? 'Auvergne-Rhône-Alpes',
                    'department' => $siege['departement'] ?? '',
                    'pappersUrl' => !empty($siren) ? "https://www.pappers.fr/entreprise/{$siren}" : null,
                ];
            }
            echo json_encode(['success' => true, 'result' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // 5. Sauvegarde de la base de données (Export SQL)
        case 'backup':
            $db = get_db_connection();
            if (!$db) {
                echo json_encode(['success' => false, 'error' => 'Base MySQL non connectée pour la sauvegarde.']);
                break;
            }

            $tables = ['cl_settings', 'cl_collaborators', 'cl_cabinets', 'cl_legal_texts', 'cl_service_opportunities', 'cl_audits', 'cl_detected_claims', 'cl_company_contacts'];
            $dump = "-- =======================================================\n";
            $dump .= "-- SAUVEGARDE CLIMAT LOCAL DU " . date('Y-m-d H:i:s') . "\n";
            $dump .= "-- =======================================================\n\n";

            foreach ($tables as $tbl) {
                $createStmt = $db->query("SHOW CREATE TABLE `{$tbl}`")->fetch(PDO::FETCH_ASSOC);
                $dump .= "DROP TABLE IF EXISTS `{$tbl}`;\n";
                $dump .= $createStmt['Create Table'] . ";\n\n";

                $rows = $db->query("SELECT * FROM `{$tbl}`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $keys = array_keys($row);
                        $escapedValues = array_map(function($v) use ($db) {
                            return $v === null ? 'NULL' : $db->quote((string)$v);
                        }, array_values($row));
                        $dump .= "INSERT INTO `{$tbl}` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                    }
                    $dump .= "\n";
                }
            }

            $filename = 'backup_climatlocal_' . date('Ymd_His') . '.sql';
            $filePath = BACKUPS_DIR . '/' . $filename;
            file_put_contents($filePath, $dump);

            // Record in cl_backups table
            $bId = 'bk_' . time();
            $db->prepare("INSERT INTO `cl_backups` (`id`, `filename`, `file_size_bytes`, `backup_type`, `created_by`) VALUES (?, ?, ?, 'sql_dump', ?)")
               ->execute([$bId, $filename, filesize($filePath), 'Admin']);

            write_app_log('INFO', "Sauvegarde de base de données effectuée : {$filename}");

            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'file_size' => filesize($filePath),
                'download_url' => "backups/{$filename}",
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // 6. Consultation des Logs
        case 'logs':
            $logType = $_GET['type'] ?? 'error';
            $file = ($logType === 'app') ? LOGS_DIR . '/app.log' : LOGS_DIR . '/error.log';
            $content = file_exists($file) ? file_get_contents($file) : 'Aucun log pour le moment.';
            echo json_encode([
                'success' => true,
                'type' => $logType,
                'logs' => $content,
            ]);
            break;

        // 7. Vidage des logs
        case 'clear_logs':
            @file_put_contents(LOGS_DIR . '/error.log', '');
            @file_put_contents(LOGS_DIR . '/app.log', '');
            write_app_log('INFO', 'Journaux système purgés par l’administrateur.');
            echo json_encode(['success' => true, 'message' => 'Journaux purgés avec succès.']);
            break;

        default:
            echo json_encode([
                'success' => true,
                'message' => 'API Climat Local (PHP 8.x) opérationnelle.',
                'version' => APP_VERSION,
                'endpoints' => ['diagnostic', 'audits', 'company_lookup', 'backup', 'logs', 'test_gemini']
            ]);
            break;
    }
} catch (Throwable $e) {
    write_app_log('ERROR', 'Erreur API : ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
