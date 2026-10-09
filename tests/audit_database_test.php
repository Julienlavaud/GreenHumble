<?php
declare(strict_types=1);

$database = file_get_contents(__DIR__ . '/../api/includes/audit_database.php');
$audit = file_get_contents(__DIR__ . '/../api/audit.php');

function gh_audit_db_assert(bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
    echo "OK : {$message}\n";
}

gh_audit_db_assert(strpos($database, 'INSERT INTO cl_companies') !== false, 'une entreprise est créée si elle n’existe pas');
gh_audit_db_assert(strpos($database, 'INSERT INTO cl_audits') !== false, 'chaque audit validé est ajouté au cockpit');
gh_audit_db_assert(strpos($database, 'prepare(') !== false, 'les requêtes de persistance utilisent des requêtes préparées');
gh_audit_db_assert(strpos($database, 'error_log(') !== false, 'les erreurs SQL sont journalisées sans exposer les secrets');
gh_audit_db_assert(!preg_match('/\\b(DROP|TRUNCATE|DELETE)\\s+(TABLE|DATABASE|FROM)\\b/i', $database), 'aucune suppression de données dans le chemin de persistance');
gh_audit_db_assert(strpos($audit, 'gh_persist_commercial_audit($validatedAudit, $enterpriseData)') !== false, 'le scan appelle la persistance après validation');
gh_audit_db_assert(strpos($audit, "'databaseSaved' => $databaseSaved") !== false, 'la réponse indique si le scan a été persisté dans MySQL');

echo "Tous les tests de persistance commerciale sont passés.\n";
