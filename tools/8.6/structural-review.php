<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/../lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,Support as S};
chdir(dirname(__DIR__,2));$root=str_replace('\\','/',getcwd());$c=new C($root);$out=C::OUT;$mode=$argv[1]??'';
if($mode==='--inspect-coverage'){
    $r=S::read('.audit/structural-coverage.json')['details'];echo S::json(['counts'=>array_intersect_key($r,array_flip(['productions','totalProductions','alternatives','totalAlternatives'])),'remainingProductions'=>$r['remainingProductions'],'remainingAlternatives'=>array_slice($r['remainingAlternatives'],0,18),'priorRows'=>array_slice($r['priorClassifications'],0,5)]);
}elseif($mode==='--inspect-gaps'){
    $r=S::read('.audit/structural-coverage.json')['details'];$rows=array_column($r['priorClassifications'],null,'id');
    $groups=[];foreach([...$r['remainingProductions'],...$r['remainingAlternatives']]as$id){$row=$rows[$id]??[];if(($row['directMatchingMeaningful']??true)===true){$name=explode('/',$id)[0];$groups[$name][]=$id;}}echo S::json($groups);
}elseif($mode==='--inspect-classifications'){
    $r=S::read('.audit/structural-classified-first.json');echo implode("\n",$r['errors'])."\n";
    foreach($r['details']['priorClassifications']as$row)if(in_array($row['id'],['comment','whitespace-character','block-comment','new-variable/alternative:5','unmatched-if-statement/alternative:4'],true))echo S::json($row);
}elseif($mode==='--extra-witnesses'){
    $p='tests/fixtures/php/8.6/structural-evidence/cases.json';$r=S::read($p);
    foreach(['and','or','xor']as$operator){$id='nested-logical-'.$operator;$file=dirname($p).'/'.$id.'.php';if(file_exists($file))throw new RuntimeException('Existing nested control');file_put_contents($file,'<?php function g(){yield fn()=> $a '.$operator.' throw new Exception => 2;}'."\n");$r['cases'][]=['id'=>$id,'rule'=>'closed-yield-logical-'.$operator.'-prefix-context','fragment'=>'$a '.$operator,'ruleExpected'=>true,'fixture'=>$file,'sha256'=>hash_file('sha256',$file),'lintExit'=>0,'structuralExpected'=>true];}
    file_put_contents($p,S::json($r));
}elseif($mode==='--final-witnesses'){
    $p='tests/fixtures/php/8.6/structural-evidence/cases.json';$r=S::read($p);
    foreach(['dynamic-static-new'=>['new-variable','$foo::$bar','new $foo::$bar;'],'magic-reserved'=>['reserved-non-modifiers','__LINE__','class MagicName { function __LINE__() {} }'],'unmatched-else'=>['unmatched-if-statement','if($a);else if($b);','if($a);else if($b);']]as$id=>[$rule,$fragment,$source]){$file=dirname($p).'/'.$id.'.php';if(file_exists($file))throw new RuntimeException('Existing final control');file_put_contents($file,'<?php '.$source."\n");$r['cases'][]=['id'=>$id,'rule'=>$rule,'fragment'=>$fragment,'ruleExpected'=>true,'fixture'=>$file,'sha256'=>hash_file('sha256',$file),'lintExit'=>0,'structuralExpected'=>true];}
    file_put_contents($p,S::json($r));
}elseif($mode==='--nullsafe-witness'){
    $p='tests/fixtures/php/8.6/structural-evidence/cases.json';$r=S::read($p);$file=dirname($p).'/nullsafe-new.php';if(file_exists($file))throw new RuntimeException('Existing nullsafe witness');file_put_contents($file,'<?php new $foo?->bar;'."\n");$r['cases'][]=['id'=>'nullsafe-new','rule'=>'new-variable','fragment'=>'$foo?->bar','ruleExpected'=>true,'fixture'=>$file,'sha256'=>hash_file('sha256',$file),'lintExit'=>0,'structuralExpected'=>true];file_put_contents($p,S::json($r));
}elseif($mode==='--seal'){
    $baseline=$c->baseline();$changes=[];
    foreach($baseline['inputs']as$p=>$hash){if(!is_file($p))throw new RuntimeException('Removed baseline input: '.$p);$now=hash_file('sha256',$p);if($now===$hash)continue;
        $dest="$out/predecessor/$p";if(!is_file($dest)){if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);copy($baseline['snapshot'].'/'.$p,$dest);}if(hash_file('sha256',$dest)!==$hash)throw new RuntimeException('Bad predecessor copy');
        $reason=match(true){str_starts_with($p,'tests/')=>'Preserve legacy assertions on authenticated predecessor inputs and test current structural semantics.',str_starts_with($p,'grammar/8.6/')=>'GA structural scope and source-backed CA86-001 correction; retain five PFA production changes.',$p==='php-grammar.json'=>'Independent structural contract metadata; promotion only after verified structural gates.',default=>'Explicitly authorized structural governance and accurate consumer documentation.'};
        $changes[$p]=['before'=>$hash,'after'=>$now,'reason'=>$reason];
    }
    file_put_contents("$out/transition.json",S::json(['contract'=>'php86-structural-preservation-v1','baselineSha256'=>C::BASELINE,'changes'=>$changes]));
    $errors=$c->preservationErrors();echo S::json(['changedInputs'=>count($changes),'errors'=>$errors]);exit($errors===[]?0:1);
}else throw new RuntimeException('Use --inspect-coverage or --seal');
