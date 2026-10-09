<?php
declare(strict_types=1);

/**
 * Schéma GreenHumble : installation additive et réexécutable.
 * Ne supprime jamais de table ni de donnée. La configuration MySQL est fournie
 * par l'installateur et reste isolée dans api/config/.env.
 */
function gh_install_database_schema(PDO $pdo): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS cl_companies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            domain VARCHAR(255) NULL,
            sector VARCHAR(255) NULL,
            siren VARCHAR(20) NULL,
            siret VARCHAR(20) NULL,
            naf_code VARCHAR(20) NULL,
            legal_form VARCHAR(120) NULL,
            headcount VARCHAR(50) NULL,
            turnover VARCHAR(50) NULL,
            address VARCHAR(255) NULL,
            city VARCHAR(120) NULL,
            postal_code VARCHAR(20) NULL,
            region VARCHAR(120) NULL,
            department VARCHAR(120) NULL,
            contact_name VARCHAR(255) NULL,
            contact_email VARCHAR(255) NULL,
            contact_phone VARCHAR(80) NULL,
            contact_role VARCHAR(120) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_companies_domain (domain), KEY idx_cl_companies_name (name),
            KEY idx_cl_companies_contact_email (contact_email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_audits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NULL,
            url VARCHAR(2048) NULL,
            score DECIMAL(6,2) NULL,
            riskLevel VARCHAR(80) NULL,
            headcount VARCHAR(50) NULL,
            turnover VARCHAR(50) NULL,
            analyzedAt DATETIME NULL,
            assigned_to VARCHAR(255) NULL,
            raw_response LONGTEXT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_audits_company_id (company_id),
            KEY idx_cl_audits_analyzed_at (analyzedAt), KEY idx_cl_audits_assigned_to (assigned_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(254) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(40) NOT NULL DEFAULT 'collaborator',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login_at DATETIME NULL,
            PRIMARY KEY (id), UNIQUE KEY uq_cl_users_email (email), KEY idx_cl_users_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_claims (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_id BIGINT UNSIGNED NULL,
            claim_text TEXT NOT NULL,
            category VARCHAR(120) NULL,
            severity VARCHAR(40) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'to_review',
            evidence TEXT NULL,
            legal_references TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_claims_audit_id (audit_id), KEY idx_cl_claims_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_business_opportunities (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NULL,
            audit_id BIGINT UNSIGNED NULL,
            service_key VARCHAR(120) NOT NULL,
            title VARCHAR(255) NOT NULL,
            rationale TEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'suggested',
            assigned_to VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_opportunities_company (company_id), KEY idx_cl_opportunities_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_company_contacts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NULL,
            email VARCHAR(254) NULL,
            phone VARCHAR(80) NULL,
            role VARCHAR(120) NULL,
            source VARCHAR(80) NULL,
            marketing_opt_in TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_contacts_company (company_id), KEY idx_cl_contacts_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_id BIGINT UNSIGNED NULL,
            actor_id BIGINT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            details TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_audit_logs_audit (audit_id), KEY idx_cl_audit_logs_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_audit_tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_id BIGINT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'open',
            assigned_to VARCHAR(255) NULL,
            due_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_audit_tasks_audit (audit_id), KEY idx_cl_audit_tasks_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_prospecting_kits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NULL,
            audit_id BIGINT UNSIGNED NULL,
            payload LONGTEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'draft',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_prospecting_company (company_id), KEY idx_cl_prospecting_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_commercial_checklist (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NULL,
            item_key VARCHAR(120) NOT NULL,
            label VARCHAR(255) NOT NULL,
            is_done TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_checklist_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_role_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(40) NOT NULL,
            scope VARCHAR(120) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_cl_role_assignments_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_teams (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_settings (
            setting_key VARCHAR(191) NOT NULL,
            setting_value LONGTEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_texts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            text_key VARCHAR(191) NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            content LONGTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), UNIQUE KEY uq_cl_texts_text_key (text_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    // Ajout de colonnes manquantes sur d'anciennes tables, sans effacer les données.
    $requiredColumns = [
        'cl_companies' => [
            'name' => "VARCHAR(255) NULL", 'domain' => "VARCHAR(255) NULL",
            'sector' => "VARCHAR(255) NULL", 'siren' => "VARCHAR(20) NULL",
            'siret' => "VARCHAR(20) NULL", 'naf_code' => "VARCHAR(20) NULL",
            'legal_form' => "VARCHAR(120) NULL", 'headcount' => "VARCHAR(50) NULL",
            'turnover' => "VARCHAR(50) NULL", 'address' => "VARCHAR(255) NULL",
            'city' => "VARCHAR(120) NULL", 'postal_code' => "VARCHAR(20) NULL",
            'region' => "VARCHAR(120) NULL", 'department' => "VARCHAR(120) NULL",
            'contact_name' => "VARCHAR(255) NULL", 'contact_email' => "VARCHAR(254) NULL",
            'contact_phone' => "VARCHAR(80) NULL", 'contact_role' => "VARCHAR(120) NULL",
            'notes' => "TEXT NULL"
        ],
        'cl_audits' => [
            'company_id' => "BIGINT UNSIGNED NULL", 'url' => "VARCHAR(2048) NULL",
            'score' => "DECIMAL(6,2) NULL", 'riskLevel' => "VARCHAR(80) NULL",
            'headcount' => "VARCHAR(50) NULL", 'turnover' => "VARCHAR(50) NULL",
            'analyzedAt' => "DATETIME NULL", 'assigned_to' => "VARCHAR(255) NULL",
            'raw_response' => "LONGTEXT NULL", 'notes' => "TEXT NULL"
        ],
        'cl_users' => [
            'name' => "VARCHAR(255) NULL",
            'email' => "VARCHAR(254) NULL",
            'password_hash' => "VARCHAR(255) NULL",
            'role' => "VARCHAR(40) NOT NULL DEFAULT 'collaborator'",
            'is_active' => "TINYINT(1) NOT NULL DEFAULT 1",
            'created_at' => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            'last_login_at' => "DATETIME NULL"
        ],
        'cl_texts' => [
            'text_key' => "VARCHAR(191) NULL", 'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'content' => "LONGTEXT NULL"
        ]
    ];

    foreach ($requiredColumns as $table => $columnsToEnsure) {
        $present = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columnsToEnsure as $column => $definition) {
            if (!in_array($column, $present, true)) {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
                $present[] = $column;
            }
        }

        // Normalise les anciens champs utilisateur si présents, sans afficher ni migrer de mot de passe en clair.
        if ($table === 'cl_users') {
            if (in_array('username', $present, true)) {
                $pdo->exec("UPDATE cl_users SET email = COALESCE(NULLIF(email, ''), username) WHERE email IS NULL OR email = ''");
            }
            if (in_array('active', $present, true)) {
                $pdo->exec("UPDATE cl_users SET is_active = IF(active IN (1, '1', 'true', 'yes'), 1, 0)");
            }
        }

        // Reprend les anciennes colonnes connues sans les supprimer.
        if ($table === 'cl_companies') {
            foreach (['website', 'clean_url'] as $legacy) {
                if (in_array($legacy, $present, true)) {
                    $pdo->exec("UPDATE cl_companies SET domain = COALESCE(NULLIF(domain, ''), NULLIF(`{$legacy}`, '')) WHERE domain IS NULL OR domain = ''");
                }
            }
        }
        if ($table === 'cl_audits' && in_array('clean_url', $present, true)) {
            $pdo->exec("UPDATE cl_audits SET url = COALESCE(NULLIF(url, ''), NULLIF(clean_url, '')) WHERE url IS NULL OR url = ''");
        }
    }
}
