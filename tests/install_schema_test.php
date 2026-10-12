<?php
declare(strict_types=1);

$schema = file_get_contents(__DIR__ . '/../api/install/schema.php');
$installer = file_get_contents(__DIR__ . '/../api/install/index.php');

function gh_schema_assert(bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

foreach ([
    'cl_companies' => 'entreprises',
    'cl_audits' => 'audits',
    'cl_users' => 'comptes et rôles',
    'cl_claims' => 'allégations détectées',
    'cl_business_opportunities' => 'opportunités commerciales',
    'cl_company_contacts' => 'contacts et consentement marketing',
    'cl_audit_logs' => 'journal d’audit',
    'cl_audit_tasks' => 'tâches',
    'cl_prospecting_kits' => 'dossiers de prospection',
    'cl_commercial_checklist' => 'suivi commercial',
    'cl_role_assignments' => 'attributions de rôles',
    'cl_teams' => 'équipes',
    'cl_settings' => 'paramètres',
    'cl_texts' => 'textes'
] as $table => $label) {
    gh_schema_assert(strpos($schema, 'CREATE TABLE IF NOT EXISTS ' . $table) !== false, 'table ' . $label . ' créée de façon idempotente');
}
gh_schema_assert(!preg_match('/\\b(DROP|TRUNCATE)\\s+(TABLE|DATABASE)\\b/i', $schema), 'aucune instruction destructive DROP/TRUNCATE');
gh_schema_assert(strpos($schema, 'ALTER TABLE') !== false, 'les tables existantes peuvent recevoir les colonnes manquantes');
gh_schema_assert(strpos($schema, "'cl_company_contacts' => [") !== false, 'les anciennes tables de contacts reçoivent les colonnes manquantes');
gh_schema_assert(strpos($schema, "'source' => \"VARCHAR(80) NULL\"") !== false, 'la source du formulaire public est créée à l’installation');
gh_schema_assert(strpos($schema, "'marketing_opt_in' => \"TINYINT(1) NOT NULL DEFAULT 0\"") !== false, 'le consentement marketing est créé avec une valeur prudente par défaut');
gh_schema_assert(strpos($schema, 'in_array(\'clean_url\', $present, true)') !== false, 'migration des anciennes URL d’audit prévue');
gh_schema_assert(strpos($schema, "'cl_users' => [") !== false && strpos($schema, "'password_hash' => \"VARCHAR(255) NULL\"") !== false, 'les anciennes tables utilisateurs reçoivent les champs de compte sécurisés');
gh_schema_assert(strpos($installer, 'gh_install_database_schema($pdo)') !== false, 'l’assistant exécute le schéma après le test de connexion');
gh_schema_assert(strpos($installer, 'if (!$installIsHttps)') !== false, 'la soumission des identifiants exige HTTPS');
gh_schema_assert(strpos($installer, 'GREENHUMBLE_INSTALLATION_COMPLETED=1') !== false, 'l’installation verrouille son assistant après succès');
gh_schema_assert(strpos($installer, "header('Location: /api/login.php?installed=1'") !== false, 'après installation, redirection vers la connexion GreenHumble');
gh_schema_assert(strpos($installer, "dirname(__DIR__, 2) . '/data'") !== false, 'le stockage privé est dans la racine dédiée à GreenHumble');
$guide = file_get_contents(__DIR__ . '/../OVH-INSTALLATION-GUIDE.md');
gh_schema_assert(strpos($guide, 'www.climatlocal.com') !== false && strpos($guide, 'hors périmètre') !== false, 'le guide exclut explicitement le site principal');
gh_schema_assert(strpos($guide, 'doo.climatlocal.com') !== false, 'le guide cible explicitement le sous-domaine GreenHumble');

echo "Tous les tests du schéma d’installation sont passés.\n";
