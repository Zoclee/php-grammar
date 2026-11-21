<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';
require dirname(__DIR__,2).'/vendor/autoload.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
$root=dirname(__DIR__,2);chdir($root);
$m=PhpGrammarMatcher::forRepositoryRoot($root);$rows=[];
$prefixes=['throw','fn()=>','include','print'];
foreach($prefixes as $a)foreach($prefixes as $b)foreach(['new Exception','1 and 2'] as $operand){
    $s='<?php function g(){ yield '.$a.' '.$b.' '.$operand.' => 2; }';
    $p='.audit/structural-yield-probe.php';file_put_contents($p,$s);
    $r=S::run([$root.'/.audit/phase8b/source/x64/Release/php.exe','-n','-l',$p],$root);
    $native=$m->matches('8.6',$s)->matched;
    $rows[]=['source'=>$s,'lint'=>$r,'native'=>$native];
    echo ($r['exit_code']===0?'PASS':'FAIL').' '.($native?'MATCH':'REJECT').' '.$a.' '.$b.' '.$operand."\n";
}
file_put_contents('.audit/structural-yield-probe.json',S::json($rows));
