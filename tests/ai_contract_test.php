<?php
declare(strict_types=1);
require_once __DIR__.'/../api/includes/ai_contract.php';
function ck($x,$m){if(!$x){fwrite(STDERR,"FAIL $m\n");exit(1);}}
$v=['score'=>42,'riskLevel'=>'modere','summary'=>'Résumé','claims'=>[['term'=>'neutre']],'prospection'=>null];
ck(gh_ai_validate_response(json_encode($v))['valid'],'réponse valide');ck(!gh_ai_validate_response('{"score":42} trailing')['valid'],'JSON strict');ck(gh_ai_validate_response("```json\n".json_encode($v)."\n```")['valid'],'JSON encadré');ck(!gh_ai_validate_response("Réponse : ```json\n".json_encode($v)."\n```")['valid'],'JSON strict avec texte autour');
foreach([['score'=>101],['score'=>'NaN'],['score'=>40,'riskLevel'=>'x','summary'=>'x','claims'=>[],'prospection'=>null],['score'=>40,'riskLevel'=>'faible','summary'=>'x','claims'=>'bad','prospection'=>null]] as $bad)ck(!gh_ai_validate_response($bad)['valid'],'plage et schéma');
ck(gh_ai_validate_response(['status'=>'analysis_incomplete'])['code']==='analysis_incomplete','incomplet distinct');
ck(gh_ai_extract_provider_text('gemini',['candidates'=>[['content'=>['parts'=>[['text'=>'{}']]]]]])==='{}','Gemini');
ck(gh_ai_extract_provider_text('groq',['choices'=>[['message'=>['content'=>'{}']]]])==='{}','Groq');
ck(gh_ai_extract_provider_text('mistral',['choices'=>[['message'=>['content'=>'{}']]]])==='{}','Mistral');
$u=gh_ai_canonical_url('HTTPS://Example.COM:443/a//b?x=1');ck($u===gh_ai_canonical_url('https://example.com/a/b'),'URL canonique');
$p=[['url'=>'https://example.com/z','text'=>' Z  contenu '],['url'=>$u,'text'=>'A contenu']];ck(gh_ai_corpus_metadata($u,$p)['corpusFingerprint']===gh_ai_corpus_metadata($u,array_reverse($p))['corpusFingerprint'],'empreinte stable');
echo "Contrats IA réussis.\n";
