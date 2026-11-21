<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Ebnf\{Parser,LiteralNode,AlternativeNode};
use PhpGrammar\Ebnf\Coverage\CoverageCollector;
chdir(dirname(__DIR__,2));$root=str_replace('\\','/',getcwd());$dir='tests/fixtures/php/8.6/structural-evidence';$mode=$argv[1]??'';
if($mode==='--prepare'){
    if(is_dir($dir))throw new RuntimeException('Existing structural witness corpus');mkdir($dir,0777,true);$cases=[];
    $add=static function($id,$rule,$fragment,$source,$lint=0,$ruleExpected=true)use(&$cases,$dir){$p="$dir/$id.php";file_put_contents($p,"<?php ".$source."\n");$cases[]=['id'=>$id,'rule'=>$rule,'fragment'=>$fragment,'ruleExpected'=>$ruleExpected,'fixture'=>$p,'sha256'=>hash_file('sha256',$p),'lintExit'=>$lint,'structuralExpected'=>true];};
    $g=(new Parser())->parse(file_get_contents('grammar/8.6/php.ebnf'));$node=$g->production('reserved-non-modifiers')->expression;
    foreach($node->alternatives as$i=>$part)if($part instanceof LiteralNode){$name=$part->value;$add('reserved-'.$i,'reserved-non-modifiers',$name,'class K'.$i.' { function '.$name.'() {} }',0,$name!=='enum');}
    foreach(['arrow'=>'fn()=>','include'=>'include','logical-and'=>'$a and','logical-or'=>'$a or','logical-xor'=>'$a xor','print'=>'print']as$id=>$prefix)$add('prefix-'.$id,'closed-yield-'.$id.'-prefix-context',$prefix,'function g(){ yield '.$prefix.' throw new Exception => 2; }');
    $add('constructor-fcc','constructor-argument-list','(...)','new Foo(...);',255);
    $add('instanceof-prefix','instanceof-prefix-context','$a instanceof Foo **','function g(){ return $a instanceof Foo ** throw new Exception; }');
    $add('qualified-use','legacy-namespace-name','\\A\\B','use \\A\\B as C;');
    $add('static-new','new-variable','Foo::$class','new Foo::$class;');
    $add('postfix-dec','postfix-expression','$a--','$a--;');
    $add('void-type','simple-type','void','function g(): void {}');
    $add('unmatched-elseif','unmatched-if-statement','if($a) ; elseif($b) ;','if($a) ; elseif($b) ;');
    foreach(['while'=>'while($a) if($b) ;','foreach'=>'foreach($a as $b) if($b) ;','declare'=>'declare(ticks=1) if($a) ;']as$id=>$s)$add('unmatched-'.$id,'unmatched-statement',$s,$s);
    file_put_contents("$dir/cases.json",S::json(['contract'=>'php86-structural-witnesses-v1','cases'=>$cases]));echo count($cases)." cases\n";
}elseif($mode==='--check'){
    $out=$argv[2]??'docs/8.6/structural-certification/witnesses.json';if(file_exists($out))throw new RuntimeException('Refusing overwrite');$m=PhpGrammarMatcher::forRepositoryRoot($root);$rows=[];$errors=[];
    foreach(S::read("$dir/cases.json")['cases']as$c){$coverage=new CoverageCollector();$rule=$m->withCoverage($coverage)->matchesRule('8.6',$c['rule'],$c['fragment'])->matched;$native=$m->matches('8.6',file_get_contents($c['fixture']))->matched;
        $cmd=[$root.'/.audit/phase8b/source/x64/Release/php.exe','-n','-d','display_errors=1','-d','log_errors=0','-l',$c['fixture']];$r=S::run($cmd,$root);
        if($rule!==$c['ruleExpected']||$native!==$c['structuralExpected']||$r['exit_code']!==$c['lintExit']||hash_file('sha256',$c['fixture'])!==$c['sha256'])$errors[]=$c['id'].': rule='.(int)$rule.' native='.(int)$native.' lint='.$r['exit_code'].' '.$r['output'];
        $rows[]=$c+['ruleMatched'=>$rule,'native'=>$native,'matchedAlternatives'=>$coverage->matchedAlternatives(),'command'=>$cmd,'lint'=>$r];
    }
    file_put_contents($out,S::json(['sourceCommit'=>'8aaada3f9605d81a38b8240f93c7389dfbc77ef9','observations'=>$rows,'errors'=>$errors]));echo S::json(['count'=>count($rows),'errors'=>$errors]);exit($errors===[]?0:1);
}else throw new RuntimeException('Use --prepare or --check [new-report]');
