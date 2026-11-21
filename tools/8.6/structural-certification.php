<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/../lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,Support as S,GaCertification as G};
$root=str_replace('\\','/',realpath(__DIR__.'/../..'));chdir($root);$c=new C($root);
$a=S::args(['gate'=>'','output'=>'','revision'=>'','report'=>''],['run','check','claims']);
if($a['gate']!==''){$r=$c->gate($a['gate'],$a['output']);if($a['output']!=='')G::writeNew($a['output'],$r);echo S::json(['gate'=>$a['gate'],'errors'=>$r['errors']]);exit($r['errors']===[]?0:1);}
if($a['run']){
    if(!preg_match('/^[a-z0-9][a-z0-9_-]*$/D',$a['revision']))throw new RuntimeException('Safe revision required');$out=C::OUT.'/runs/'.$a['revision'];if(file_exists($out))throw new RuntimeException('Never overwrite structural evidence');
    $inputs=$c->inventory();mkdir($out,0777,true);$results=[];
    foreach($c->commands($out)as$id=>$cmd){echo "Executing $id\n";$log="$out/$id.log";$r=S::run($cmd,$root,$log);$results[$id]=['obligation'=>C::gates()[$id],'command'=>$cmd,'exit_code'=>$r['exit_code'],'log'=>$log,'sha256'=>hash_file('sha256',$log)];G::writeNew("$out/$id.receipt.json",$results[$id]);echo "$id: ".($r['exit_code']===0?'passed':'failed')."\n";}
    $artifacts=[];foreach(S::files($root.'/'.$out)as$p)$artifacts[substr($p,strlen($root)+1)]=hash_file('sha256',$p);ksort($artifacts);
    $ok=count(array_filter($results,static fn($r)=>$r['exit_code']!==0))===0;$unchanged=$inputs===$c->inventory();
    $r=['contract'=>C::CONTRACT,'schema'=>C::SCHEMA,'claim'=>C::CLAIM,'sourceCommit'=>G::PIN,'output'=>$out,'inputs'=>$inputs,'inputsUnchanged'=>$unchanged,'results'=>$results,'artifacts'=>$artifacts,'structuralCertified'=>$ok&&$unchanged,'extendedCertified'=>false];
    G::writeNew("$out/evidence.json",$r);G::writeNew("$out/receipt.json",['evidenceSha256'=>hash_file('sha256',"$out/evidence.json")]);echo S::json(['passed'=>count(array_filter($results,static fn($r)=>$r['exit_code']===0)),'total'=>count($results),'structuralCertified'=>$r['structuralCertified']]);exit($r['structuralCertified']?0:1);
}
if($a['check']||$a['claims']){$r=$c->read($a['report']);$e=$c->verify($r,$a['report']);if($a['claims']&&!$r['structuralCertified'])$e[]='Structural gates are not all satisfied';echo S::json(['evidenceVerified'=>$e===[],'structuralCertified'=>$e===[]&&$r['structuralCertified'],'extendedCertified'=>false,'errors'=>$e]);exit($e===[]?0:1);}
throw new RuntimeException('Select --run, --gate, --check or --claims');
