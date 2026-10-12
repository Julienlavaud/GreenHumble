<?php
declare(strict_types=1);
require_once __DIR__.'/../api/includes/risk_scoring.php';
function risk_assert($ok,$message){if(!$ok){fwrite(STDERR,"FAIL $message\n");exit(1);}}
$claim=['term'=>'neutre en carbone','quoteInText'=>'Tous nos produits sont neutres en carbone','evidenceVerified'=>true,'evidenceStatus'=>'signal','riskRationale'=>'Allégation absolue sans périmètre ni preuve.'];
$a=gh_calculate_risk_assessment([$claim]);$b=gh_calculate_risk_assessment([$claim]);
risk_assert($a===$b,'calcul déterministe');
risk_assert($a['riskScore']>=70&&$a['riskLevel']==='eleve','allégation absolue et large portée');
risk_assert($a['reasons'][0]['factors']['nature']===24&&$a['reasons'][0]['factors']['scope']===18,'motifs détaillés');
$missing=$claim;$missing['evidenceVerified']=false;$missing['evidenceStatus']='evidence_missing';$missing['riskRationale']='';
$m=gh_calculate_risk_assessment([$missing]);
risk_assert($m['riskScore']<$a['riskScore']&&$m['confidence']<$a['confidence'],'preuve absente ne gonfle pas le risque et réduit confiance');
risk_assert(gh_calculate_risk_assessment([])['riskScore']===null,'aucun signal ne devient pas un score de conformité');
$softer=$claim;$softer['term']='écologique';$softer['quoteInText']='Un produit écologique';$soft=gh_calculate_risk_assessment([$softer]);
risk_assert($soft['riskScore']<$a['riskScore'],'précision et portée changent le score');
echo "Scoring de risque réussi.\n";
