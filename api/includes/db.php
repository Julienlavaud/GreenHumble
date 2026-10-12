<?php
// /api/includes/db.php
require_once __DIR__ . '/../config/config.php';

function ensure_db_schema($pdo) {
    try {
        // Ensure headcount & turnover in cl_companies
        $cols = $pdo->query("SHOW COLUMNS FROM cl_companies")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('headcount', $cols)) {
            $pdo->exec("ALTER TABLE cl_companies ADD COLUMN headcount VARCHAR(50) NULL AFTER sector");
        }
        if (!in_array('turnover', $cols)) {
            $pdo->exec("ALTER TABLE cl_companies ADD COLUMN turnover VARCHAR(50) NULL AFTER headcount");
        }

        // Legacy and commercial persistence columns required by audit canonicalization.
        $companyColumns = $pdo->query("SHOW COLUMNS FROM cl_companies")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['website' => "VARCHAR(255) NULL", 'clean_url' => "VARCHAR(255) NULL", 'siren' => "VARCHAR(20) NULL"] as $column => $definition) {
            if (!in_array($column, $companyColumns, true)) $pdo->exec("ALTER TABLE cl_companies ADD COLUMN `{$column}` {$definition}");
        }

        // Ensure headcount & turnover in cl_audits
        $colsAudit = $pdo->query("SHOW COLUMNS FROM cl_audits")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('headcount', $colsAudit)) {
            $pdo->exec("ALTER TABLE cl_audits ADD COLUMN headcount VARCHAR(50) NULL");
        }
        if (!in_array('turnover', $colsAudit)) {
            $pdo->exec("ALTER TABLE cl_audits ADD COLUMN turnover VARCHAR(50) NULL");
        }

        // Alias de domaines vers une fiche entreprise canonique (SIREN), sans toucher aux historiques.
        $pdo->exec("CREATE TABLE IF NOT EXISTS cl_company_domains (
            domain VARCHAR(255) NOT NULL,
            company_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (domain), KEY idx_cl_company_domains_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $auditColumns = $pdo->query("SHOW COLUMNS FROM cl_audits")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['clean_url' => "VARCHAR(255) NULL", 'summary' => "TEXT NULL", 'financial_impact' => "TEXT NULL", 'source' => "VARCHAR(80) NULL"] as $column => $definition) {
            if (!in_array($column, $auditColumns, true)) $pdo->exec("ALTER TABLE cl_audits ADD COLUMN `{$column}` {$definition}");
        }

        // Migrations additives pour relier les scans publics aux prospects sans supprimer les données existantes.
        if (!in_array('domain', $cols, true)) {
            $pdo->exec("ALTER TABLE cl_companies ADD COLUMN domain VARCHAR(255) NULL AFTER website");
        }

        $contactCols = $pdo->query("SHOW COLUMNS FROM cl_company_contacts")->fetchAll(PDO::FETCH_COLUMN);
        $contactMigrations = [
            'source' => "ALTER TABLE cl_company_contacts ADD COLUMN source VARCHAR(100) NULL",
            'marketing_opt_in' => "ALTER TABLE cl_company_contacts ADD COLUMN marketing_opt_in TINYINT(1) NOT NULL DEFAULT 0",
            'marketing_consent_at' => "ALTER TABLE cl_company_contacts ADD COLUMN marketing_consent_at DATETIME NULL",
            'audit_ref' => "ALTER TABLE cl_company_contacts ADD COLUMN audit_ref VARCHAR(100) NULL",
            'marketing_consent_version' => "ALTER TABLE cl_company_contacts ADD COLUMN marketing_consent_version VARCHAR(40) NULL"
        ];
        foreach ($contactMigrations as $column => $sql) {
            if (!in_array($column, $contactCols, true)) {
                $pdo->exec($sql);
            }
        }
    } catch (Exception $e) {
        // Silently handle if table doesn't exist yet before install
    }
}

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    if (trim((string)DB_HOST) === '' || trim((string)DB_NAME) === '' || trim((string)DB_USER) === '') {
        http_response_code(503);
        die("<div style='font-family:sans-serif;margin:24px;padding:18px;border:1px solid #ddd;border-radius:12px;'>GreenHumble n’est pas configuré. Lancez l’installation sécurisée depuis <a href='/api/install/'>l’assistant d’installation</a>.</div>");
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        ensure_db_schema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        die("<div style='background:#fee2e2;color:#991b1b;padding:16px;border-radius:12px;font-family:sans-serif;margin:20px;'>"
            . "<strong>Connexion à la base de données impossible.</strong> Vérifiez la configuration dans l’espace OVH ou utilisez l’assistant d’installation. Aucun détail SQL n’est affiché."
            . "<br><a href='/api/install/' style='color:#1e40af;text-decoration:underline;margin-top:8px;display:inline-block;'>Ouvrir l’assistant d’installation</a></div>");
    }
}

