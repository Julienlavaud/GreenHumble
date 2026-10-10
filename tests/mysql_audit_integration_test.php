<?php
declare(strict_types=1);

$host = getenv('GH_TEST_DB_HOST') ?: '';
$name = getenv('GH_TEST_DB_NAME') ?: '';
$user = getenv('GH_TEST_DB_USER') ?: '';
$password = getenv('GH_TEST_DB_PASS') ?: '';
if ($host === '' || $name === '' || $user === '') {
    fwrite(STDERR, "ÉCHEC : configuration MySQL de test absente.\n");
    exit(1);
}

if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_NAME')) define('DB_NAME', $name);
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $password);

require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/../api/install/schema.php';
require_once __DIR__ . '/../api/includes/db.php';
require_once __DIR__ . '/../api/includes/audit_database.php';

function mysql_audit_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
gh_install_database_schema($pdo);
gh_install_database_schema($pdo);
mysql_audit_assert((int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cl_company_domains'")->fetchColumn() === 1, 'le schéma d’installation peut être appliqué deux fois');

$companySiren = str_pad((string)random_int(1, 999999999), 9, '0', STR_PAD_LEFT);
$token = bin2hex(random_bytes(5));
$domains = ['site-a-' . $token . '.example.test', 'site-b-' . $token . '.example.test'];
$saved = false;

try {
    foreach ($domains as $index => $domain) {
        $audit = [
            'url' => 'https://' . $domain . '/mentions',
            'companyName' => 'Entreprise de test ' . $token,
            'companyRegistry' => ['siren' => $companySiren],
            'riskScore' => 42 + $index,
            'riskLevel' => 'modere',
            'summary' => 'Audit d’intégration sans données externes.',
            'analyzedAt' => date('c'),
        ];
        $enterpriseData = [
            'company_siren' => $companySiren,
            'entreprise_secteur' => 'Services de test',
            'entreprise_salaries' => '10 à 19 salariés',
            'entreprise_ca' => null,
        ];
        mysql_audit_assert(gh_persist_commercial_audit($audit, $enterpriseData), 'l’audit du domaine ' . ($index + 1) . ' est persisté');
    }

    $companyQuery = $pdo->prepare('SELECT id FROM cl_companies WHERE siren = ?');
    $companyQuery->execute([$companySiren]);
    $companyIds = array_map('intval', $companyQuery->fetchAll(PDO::FETCH_COLUMN));
    mysql_audit_assert(count($companyIds) === 1, 'deux domaines avec un SIREN confirmé partagent une fiche entreprise');
    $companyId = $companyIds[0];

    $auditCount = $pdo->prepare('SELECT COUNT(*) FROM cl_audits WHERE company_id = ?');
    $auditCount->execute([$companyId]);
    mysql_audit_assert((int)$auditCount->fetchColumn() === 2, 'les deux audits restent rattachés à la fiche canonique');

    $domainCount = $pdo->prepare('SELECT COUNT(*) FROM cl_company_domains WHERE company_id = ? AND domain IN (?, ?)');
    $domainCount->execute([$companyId, ...$domains]);
    mysql_audit_assert((int)$domainCount->fetchColumn() === 2, 'les deux domaines sont conservés comme alias de la même entreprise');

    $rawQuery = $pdo->prepare('SELECT raw_response FROM cl_audits WHERE company_id = ? ORDER BY id ASC LIMIT 1');
    $rawQuery->execute([$companyId]);
    $persistedPayload = json_decode((string)$rawQuery->fetchColumn(), true);
    mysql_audit_assert(($persistedPayload['companyRegistry']['siren'] ?? null) === $companySiren, 'la provenance SIREN reste dans le résultat SQL brut');
    $saved = true;
} finally {
    $companyQuery = $pdo->prepare('SELECT id FROM cl_companies WHERE siren = ?');
    $companyQuery->execute([$companySiren]);
    $ids = array_map('intval', $companyQuery->fetchAll(PDO::FETCH_COLUMN));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        foreach (['cl_audits', 'cl_company_domains'] as $table) {
            $delete = $pdo->prepare('DELETE FROM ' . $table . ' WHERE company_id IN (' . $placeholders . ')');
            $delete->execute($ids);
        }
        $deleteCompanies = $pdo->prepare('DELETE FROM cl_companies WHERE id IN (' . $placeholders . ')');
        $deleteCompanies->execute($ids);
    }
}
if ($saved) echo "Tests d’intégration MySQL réussis.\n";
