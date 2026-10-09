<?php
function check_contract($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : " . $message . PHP_EOL);
        exit(1);
    }
}

$home = file_get_contents(__DIR__ . '/../api/index.php');
$audit = file_get_contents(__DIR__ . '/../api/audit.php');

check_contract(strpos($home, 'function escapeScanHtml') !== false, 'le rendu du scan doit échapper les données externes');
check_contract(strpos($home, 'data.detectedClaims') !== false, 'le rendu doit lire le contrat detectedClaims de l’API');
check_contract(strpos($home, 'data.clientSummary.riskVerdict') !== false, 'le rendu doit lire la synthèse normalisée');
check_contract(strpos($home, 'analysisComplete === false') !== false, 'une analyse incomplète doit être refusée par le rendu');
check_contract(strpos($home, 'Cela ne garantit pas la conformité du site') !== false, 'l’absence de signal ne doit pas être présentée comme une conformité');
check_contract(strpos($audit, "require_api_role(['admin', 'pipeline', 'collaborator', 'consultant'])") !== false, 'la lecture des audits doit être réservée aux utilisateurs authentifiés');
check_contract(strpos($audit, "require_api_role(['admin'])") !== false, 'la suppression des audits doit être réservée à l’administration');

$lead = file_get_contents(__DIR__ . '/../api/public_lead.php');
check_contract(strpos($audit, 'LOCK_EX') !== false && strpos($audit, "'storage_unavailable'") !== false, 'un audit ne doit pas être annoncé comme sauvegardé si l’écriture échoue');
check_contract(strpos($lead, 'cl_company_contacts') !== false, 'la demande publique utilise la table de contacts installée');
check_contract(strpos($lead, 'marketing_opt_in') !== false, 'le consentement marketing est explicite et facultatif');
check_contract(strpos($lead, 'cl_prospects') === false, 'la capture ne dépend pas d’une table prospects absente du schéma livré');
check_contract(strpos($lead, "'score' => 65") === false && strpos($lead, 'INSERT INTO cl_audits') === false, 'la capture de lead ne fabrique pas de faux audit ni de score');

echo "OK — contrat du scan public et contrôles d’accès aux audits" . PHP_EOL;
