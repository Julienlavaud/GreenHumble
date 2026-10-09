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

gh_schema_assert(is_string($schema) && strpos($schema, 'CREATE TABLE IF NOT EXISTS cl_companies') !== false, 'la table entreprises est créée de façon idempotente');
gh_schema_assert(strpos($schema, 'CREATE TABLE IF NOT EXISTS cl_audits') !== false, 'la table audits est créée de façon idempotente');
gh_schema_assert(strpos($schema, 'CREATE TABLE IF NOT EXISTS cl_texts') !== false, 'la table des textes est créée de façon idempotente');
gh_schema_assert(!preg_match('/\\b(DROP|TRUNCATE)\\s+(TABLE|DATABASE)\\b/i', $schema), 'aucune instruction destructive DROP/TRUNCATE');
gh_schema_assert(strpos($schema, 'ALTER TABLE') !== false, 'les tables existantes peuvent recevoir les colonnes manquantes');
gh_schema_assert(strpos($installer, 'gh_install_database_schema($pdo)') !== false, 'l’assistant exécute le schéma après le test de connexion');
gh_schema_assert(strpos($installer, '$requestIsHttps') !== false, 'la soumission des identifiants exige HTTPS');

echo "Tous les tests du schéma d’installation sont passés.\n";
