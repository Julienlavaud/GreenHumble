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

echo "OK — contrat du scan public et contrôles d’accès aux audits" . PHP_EOL;
