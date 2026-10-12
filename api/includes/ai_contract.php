<?php
declare(strict_types=1);
const GH_AI_PROMPT_VERSION='2026-10-10.1';
const GH_AI_SCHEMA_VERSION='1.0.0';
const GH_AI_GENERATION_VERSION='1';
function gh_ai_canonical_url(string $url):?string{$p=parse_url(trim($url));if(!is_array($p)||empty($p['host'])||!in_array(strtolower($p['scheme']??''),['http','https'],true)||isset($p['user'])||isset($p['pass']))return null;$s=strtolower($p['scheme']);$h=strtolower(rtrim($p['host'],'.'));$port=isset($p['port'])?(int)$p['port']:null;if(($s==='https'&&$port===443)||($s==='http'&&$port===80))$port=null;$path=preg_replace('~/+~','/',$p['path']??'/');return $s.'://'.$h.($port?':'.$port:'').($path===''?'/':$path);}
function gh_ai_corpus_metadata(string $canonicalUrl,array $pages):array{usort($pages,static fn($a,$b)=>strcmp((string)($a['url']??''),(string)($b['url']??'')));$clean=[];foreach($pages as $p){$u=gh_ai_canonical_url((string)($p['url']??''));$t=trim(preg_replace('/\\s+/u',' ',strip_tags((string)($p['text']??''))));if($u!==null&&$t!=='')$clean[]=['url'=>$u,'text'=>$t];}return ['canonicalUrl'=>$canonicalUrl,'pages'=>$clean,'pageCount'=>count($clean),'corpusFingerprint'=>hash('sha256',(string)json_encode(['url'=>$canonicalUrl,'pages'=>$clean],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))];}
function gh_ai_extract_provider_text(string $provider,array $e):?string{$v=$provider==='gemini'?($e['candidates'][0]['content']['parts'][0]['text']??null):($e['choices'][0]['message']['content']??null);return is_string($v)&&trim($v)!==''?trim($v):null;}
function gh_ai_extract_json(string $raw):string{$t=trim($raw);$t=preg_replace('/^\xEF\xBB\xBF/','',$t);if(preg_match('/```(?:json)?\s*(.*?)```/is',$t,$m))$t=trim($m[1]);if(json_decode($t,true)===null&&json_last_error()!==JSON_ERROR_NONE){$a=strpos($t,'{');$b=strrpos($t,'}');if($a!==false&&$b!==false&&$b>$a)$t=substr($t,$a,$b-$a+1);}return $t;}
function gh_ai_normalize(array $d):array{
    if(!isset($d['score'])&&isset($d['riskScore']))$d['score']=$d['riskScore'];
    if(isset($d['score'])&&is_string($d['score'])&&preg_match('/-?\d+(?:[.,]\d+)?/',$d['score'],$m))$d['score']=(float)str_replace(',','.',$m[0]);
    if(isset($d['score'])&&is_numeric($d['score'])&&(float)$d['score']>0&&(float)$d['score']<=1)$d['score']=(float)$d['score']*100;
    if(isset($d['riskLevel'])&&is_string($d['riskLevel'])){
        $l=strtr(strtolower(trim($d['riskLevel'])),['é'=>'e','è'=>'e','ê'=>'e','É'=>'e','È'=>'e']);
        $map=['faible'=>'faible','low'=>'faible','bas'=>'faible','modere'=>'modere','moyen'=>'modere','medium'=>'modere','moderate'=>'modere','eleve'=>'eleve','haut'=>'eleve','high'=>'eleve','critique'=>'critique','critical'=>'critique','a_evaluer'=>'a_evaluer','a evaluer'=>'a_evaluer'];
        $d['riskLevel']=$map[$l]??$d['riskLevel'];
    }
    if(isset($d['riskLevel'])&&!in_array($d['riskLevel'],['faible','modere','modéré','eleve','élevé','critique','a_evaluer'],true))unset($d['riskLevel']);
    if(!isset($d['riskLevel'])&&isset($d['score'])&&is_numeric($d['score'])){$s=(float)$d['score'];$d['riskLevel']=$s>=75?'critique':($s>=50?'eleve':($s>=25?'modere':'faible'));}elseif(!isset($d['riskLevel']))$d['riskLevel']='a_evaluer';
    if((!isset($d['summary'])||!is_string($d['summary'])||trim($d['summary'])==='')){
        $alt=$d['clientSummary']['riskVerdict']??($d['clientSummary']['headline']??($d['synthese']??null));
        $d['summary']=is_string($alt)&&trim($alt)!==''?$alt:'Synthèse non fournie par le fournisseur IA.';
    }
    if(!isset($d['claims'])||!is_array($d['claims']))$d['claims']=is_array($d['detectedClaims']??null)?$d['detectedClaims']:[];
    $claims=[];
    foreach($d['claims'] as $cl){
        if(is_string($cl))$cl=['term'=>$cl];
        if(!is_array($cl))continue;
        if(!isset($cl['term'])||!is_string($cl['term'])||trim($cl['term'])===''){
            $cl['term']=null;
            foreach(['claim','title','phrase','allegation','expression'] as $k)if(isset($cl[$k])&&is_string($cl[$k])&&trim($cl[$k])!==''){$cl['term']=$cl[$k];break;}
            if($cl['term']===null)continue;
        }
        $claims[]=$cl;
    }
    $d['claims']=$claims;
    if(!array_key_exists('prospection',$d))$d['prospection']=$d['commercialProspectingKit']??null;
    if($d['prospection']!==null&&!is_array($d['prospection']))$d['prospection']=null;
    return $d;
}
function gh_ai_validate_response($raw):array{if(is_string($raw)){$raw=gh_ai_extract_json($raw);$d=json_decode($raw,true);if(json_last_error()!==JSON_ERROR_NONE||!is_array($d)||array_is_list($d))return ['valid'=>false,'code'=>'invalid_json','data'=>null];}elseif(is_array($raw))$d=$raw;else return ['valid'=>false,'code'=>'invalid_json','data'=>null];$d=gh_ai_normalize($d);if(($d['status']??null)==='analysis_incomplete'||($d['analysisComplete']??true)===false)return ['valid'=>false,'code'=>'analysis_incomplete','data'=>$d];$s=$d['score']??$d['riskScore']??null;if(!is_numeric($s)||!is_finite((float)$s)||(float)$s<0||(float)$s>100||!isset($d['summary'])||!is_string($d['summary'])||trim($d['summary'])===''||!isset($d['riskLevel'])||!in_array($d['riskLevel'],['faible','modere','modéré','eleve','élevé','critique','a_evaluer'],true)||!isset($d['claims'])||!is_array($d['claims'])||!array_key_exists('prospection',$d))return ['valid'=>false,'code'=>'schema_invalid','data'=>$d];foreach($d['claims'] as $claim)if(!is_array($claim)||!isset($claim['term'])||!is_string($claim['term'])||trim($claim['term'])==='')return ['valid'=>false,'code'=>'schema_invalid','data'=>$d];if($d['prospection']!==null&&!is_array($d['prospection']))return ['valid'=>false,'code'=>'schema_invalid','data'=>$d];$d['score']=(float)$s;return ['valid'=>true,'code'=>'ok','data'=>$d];}

function gh_ai_corpus_pages_from_text(string $canonicalUrl,string $text):array {
 $pages=[]; if(preg_match_all('/\\[SOURCE_URL:\\s*([^\\]]+)\\]\\s*(.*?)(?=\\n\\s*\\n\\[SOURCE_URL:|$)/s',$text,$matches,PREG_SET_ORDER)) {
  foreach($matches as $m)$pages[]=['url'=>trim($m[1]),'text'=>trim($m[2])];
 }
 if(!$pages&&trim($text)!=='')$pages[]=['url'=>$canonicalUrl,'text'=>$text];
 return gh_ai_corpus_metadata($canonicalUrl,$pages);
}
