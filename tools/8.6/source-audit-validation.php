<?php
declare(strict_types=1);
// Research validation only: does not invoke certification or change release gates.
$root=dirname(__DIR__,2); chdir($root);
$php='.audit/php85/php.exe';
$pair=['--php85='.$php,'--php86=.audit/php86/php.exe'];
$sources=['--sources85=.audit/php-php-src-7a4c627','--sources86=.audit/php-php-src-f23b4c2'];
$jobs=[
 'coverage'=>['tools/8.6/final-coverage-expansion.php',...$pair,...$sources,'--report=docs/8.6/source-audit-oracles.json','--coverage=docs/8.6/source-audit-coverage.json'],
 'integration'=>['tools/8.6/integration-check.php',...$pair,...$sources,'--report=docs/8.6/source-audit-integration.json'],
 'pfa'=>['tools/8.6/pfa-check.php',...$pair,'--sources=.audit/php-php-src-f23b4c2','--report=docs/8.6/source-audit-pfa.json'],
 'context'=>['tools/8.6/context-check.php',...$pair,...$sources,'--report=docs/8.6/source-audit-context.json'],
 'deprecation'=>['tools/8.6/deprecation-check.php',...$pair,...$sources,'--report=docs/8.6/source-audit-deprecation.json'],
 'discrepancies'=>['tools/8.6/compiler-contextual-check.php',...$pair,...$sources,'--cache=.audit','--report=docs/8.6/source-audit-probes.json'],
 'traceability'=>['tools/8.6/change-traceability.php','--report=docs/8.6/source-audit-traceability.json'],
 'manifest'=>['tools/validate-manifest.php'],
 'suite'=>['-d','extension=.audit/php85/ext/php_mbstring.dll','vendor/phpunit/phpunit/phpunit','--do-not-cache-result','--no-progress'],
];
$results=[];
foreach($jobs as $id=>$args) {
 $cmd=[$php,'-n',...$args]; $log=tempnam(sys_get_temp_dir(),'audit86-');
 $p=proc_open($cmd,[1=>['file',$log,'w'],2=>['file',$log,'a']],$pipes,$root);
 if(!is_resource($p)) throw new RuntimeException('Cannot start '.$id);
 $exit=proc_close($p);$output=file_get_contents($log);unlink($log);
 $results[$id]=['command'=>['rtk','proxy',...$cmd],'exit'=>$exit,'output'=>$output];
 echo $id,': exit ',$exit,' ',trim($output),"\n";
 file_put_contents('docs/8.6/source-audit-validation.json',json_encode(['date'=>'2026-10-24','commands'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
}
exit(count(array_filter($results,static fn($r)=>$r['exit']!==0))?1:0);
