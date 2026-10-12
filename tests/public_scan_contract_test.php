<?php
function check_contract($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : " . $message . PHP_EOL);
        exit(1);
    }
}

$home = file_get_contents(__DIR__ . '/../api/index.php');
$script = file_get_contents(__DIR__ . '/../api/assets/js/public-audit.js');
$audit = file_get_contents(__DIR__ . '/../api/audit.php');
$router = file_get_contents(__DIR__ . '/../api/router.php');
$legacyScanProcess = __DIR__ . '/../api/scan_process.php';
$unvalidatedScanCreate = __DIR__ . '/../api/scans/create.php';

check_contract(strpos($home, '/api/assets/js/public-audit.js') !== false, 'la page publique charge son script séparé');
check_contract(strpos($script, "fetch('/api/audit'") !== false, 'le formulaire appelle le point d’entrée officiel du scan');
check_contract(strpos($script, 'type_audit: \'full\'') !== false, 'le scan envoie le type d’audit attendu');
check_contract(strpos($script, 'data.detectedClaims') !== false, 'le rendu lit le contrat detectedClaims de l’API');
check_contract(strpos($script, 'data.clientSummary.riskVerdict') !== false, 'le rendu lit la synthèse normalisée');
check_contract(strpos($script, 'analysisComplete === false') !== false, 'une analyse incomplète est refusée');
check_contract(strpos($script, 'rawScore === null') !== false, 'un score absent ne devient pas artificiellement zéro');
check_contract(strpos($script, 'innerHTML') === false, 'les données externes ne sont pas injectées comme du HTML');
check_contract(strpos($script, 'Cela ne garantit pas la conformité du site') !== false, 'l’absence de signal ne vaut pas preuve de conformité');

check_contract(strpos($audit, "require_api_role(['admin', 'pipeline', 'collaborator', 'consultant'])") !== false, 'la lecture des audits est réservée aux utilisateurs authentifiés');
check_contract(strpos($audit, "require_api_role(['admin'])") !== false, 'la suppression des audits est réservée à l’administration');
check_contract(strpos($audit, 'LOCK_EX') !== false && strpos($audit, "'storage_unavailable'") !== false, 'un audit n’est pas annoncé comme sauvegardé si son écriture échoue');
check_contract(strpos($router, "'scan/process'") === false && !is_file($legacyScanProcess), 'le chemin historique qui enregistrait des allégations de secours fabriquées est supprimé');
check_contract(strpos($router, "'scans/create'") === false && !is_file($unvalidatedScanCreate), 'la création directe de scans sans vérification de preuve est supprimée');

$lead = file_get_contents(__DIR__ . '/../api/public_lead.php');
check_contract(strpos($lead, 'cl_company_contacts') !== false, 'la demande publique utilise la table de contacts installée');
check_contract(strpos($lead, 'marketing_opt_in') !== false, 'le consentement marketing reste distinct et facultatif');
check_contract(strpos($lead, 'cl_prospects') === false, 'la capture ne dépend pas d’une table absente du schéma');
check_contract(strpos($lead, "'score' => 65") === false && strpos($lead, 'INSERT INTO cl_audits') === false, 'la capture ne fabrique pas d’audit ni de score');

echo "OK — contrat du scan public, rendu sécurisé et contrôles d’accès" . PHP_EOL;
