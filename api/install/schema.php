<?php
declare(strict_types=1);

/**
 * Schéma initial GreenHumble.
 *
 * Installation additive et réexécutable : uniquement CREATE TABLE IF NOT EXISTS.
 * Aucun DROP, TRUNCATE ou effacement de données existantes.
 */
function gh_install_database_schema(PDO $pdo): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS cl_companies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            domain VARCHAR(255) NULL,
            sector VARCHAR(255) NULL,
            headcount VARCHAR(50) NULL,
            turnover VARCHAR(50) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cl_companies_domain (domain),
            KEY idx_cl_companies_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_audits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NULL,
            url VARCHAR(2048) NOT NULL,
            score DECIMAL(6,2) NULL,
            riskLevel VARCHAR(80) NULL,
            headcount VARCHAR(50) NULL,
            turnover VARCHAR(50) NULL,
            analyzedAt DATETIME NULL,
            assigned_to VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cl_audits_company_id (company_id),
            KEY idx_cl_audits_analyzed_at (analyzedAt),
            KEY idx_cl_audits_assigned_to (assigned_to),
            CONSTRAINT fk_cl_audits_company FOREIGN KEY (company_id)
                REFERENCES cl_companies(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS cl_texts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            text_key VARCHAR(191) NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            content LONGTEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_cl_texts_text_key (text_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    // Complète prudemment les tables existantes sans supprimer ni renommer de colonne.
    $requiredColumns = [
        'cl_companies' => [
            'name' => "VARCHAR(255) NULL",
            'domain' => "VARCHAR(255) NULL",
            'sector' => "VARCHAR(255) NULL",
            'headcount' => "VARCHAR(50) NULL",
            'turnover' => "VARCHAR(50) NULL",
        ],
        'cl_audits' => [
            'company_id' => "BIGINT UNSIGNED NULL",
            'url' => "VARCHAR(2048) NULL",
            'score' => "DECIMAL(6,2) NULL",
            'riskLevel' => "VARCHAR(80) NULL",
            'headcount' => "VARCHAR(50) NULL",
            'turnover' => "VARCHAR(50) NULL",
            'analyzedAt' => "DATETIME NULL",
            'assigned_to' => "VARCHAR(255) NULL",
        ],
        'cl_texts' => [
            'text_key' => "VARCHAR(191) NULL",
            'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'content' => "LONGTEXT NULL",
        ],
    ];

    foreach ($requiredColumns as $table => $columnsToEnsure) {
        $present = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columnsToEnsure as $column => $definition) {
            if (!in_array($column, $present, true)) {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }
}
