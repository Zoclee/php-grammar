<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
chdir(dirname(__DIR__,2));$out='docs/8.6/structural-certification';
if(file_exists($out.'/yield-refinement.json'))throw new RuntimeException('Already refined');
$grammar=file_get_contents('grammar/8.6/php.ebnf');
$md=file_get_contents('grammar/8.6/php.md');
$md=preg_replace_callback('/(<!-- BEGIN GENERATED EBNF -->\R```ebnf\R).*?(\R```\R<!-- END GENERATED EBNF -->)/s',fn($m)=>$m[1].rtrim($grammar).$m[2],$md);
$md=str_replace('the closed-yield throw-expression operand for these three prefixes; it does not','the closed-yield throw-expression operand for arrow/include prefixes. Print retains its\nhigher-precedence operand unless a throw, arrow-function, or include prefix opens\na lower-precedence operand; it does not',$md);
file_put_contents('grammar/8.6/php.md',$md);
$p='tests/fixtures/php/8.6/structural-evidence/cases.json';$cases=S::read($p);$probe=S::read('.audit/structural-yield-probe.json');
foreach($probe as$i=>$r){$file=dirname($p).'/prefix-matrix-'.$i.'.php';file_put_contents($file,$r['source']."\n");$accepted=$r['lint']['exit_code']===0;$cases['cases'][]=['id'=>'prefix-matrix-'.$i,'rule'=>'source-file','fragment'=>$r['source'],'ruleExpected'=>$accepted,'fixture'=>$file,'sha256'=>hash_file('sha256',$file),'lintExit'=>$r['lint']['exit_code'],'structuralExpected'=>$accepted];}
file_put_contents($p,S::json($cases));
copy('.audit/structural-yield-probe.json',$out.'/yield-prefix-discovery.json');
file_put_contents($out.'/yield-refinement.json',S::json(['finding'=>'SG86-002','sourceCommit'=>'8aaada3f9605d81a38b8240f93c7389dfbc77ef9','source'=>'Zend/zend_language_parser.y precedence declarations and expr productions','rejectedOverbroadCorrection'=>'tests/fixtures/php/8.6/invalid/final-yield-key-prefix.php','reason'=>'Print must not absorb bare logical operators into the yield key. A nested lower-precedence prefix can open such an operand. Existing negative fixture remains unchanged; add all 32 paired prefix/operand controls.','grammarSha256'=>hash('sha256',$grammar),'discoverySha256'=>hash_file('sha256',$out.'/yield-prefix-discovery.json')]));
$s=file_get_contents('tools/8.6/pfa-check-ga-v2.php');
$old='$check($changed === $lock[\'modifiedProductions\'], \'Every structural difference mapped to PHP86-001\');';
$new='$expectedChanges=[...$lock[\'modifiedProductions\'],\'hook-name\',\'yield-key\']; sort($expectedChanges); sort($changed);' . "\n" . '$check($changed === $expectedChanges, \'Original five PFA changes plus independently checked structural corrections\');';
if(substr_count($s,$old)!==1)throw new RuntimeException('Unexpected PFA assertion');
file_put_contents('tools/8.6/pfa-check-structural-v1.php',str_replace($old,$new,$s));
echo "Refined yield boundary and retained 32 GA observations\n";
