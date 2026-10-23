<?php
declare(strict_types=1);

// Focused stage-specific evidence, not a contextual validator or release gate.
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
$options=getopt('', ['php85:', 'php86:', 'sources85:', 'sources86:', 'report:']);
foreach(['php85','php86','sources85','sources86'] as $option) {
    if (!isset($options[$option])) {fwrite(STDERR,"Required: --$option=<path>\n");exit(2);}
}
$cases=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/context-cases.json'),true,512,JSON_THROW_ON_ERROR);
$lock=json_decode(file_get_contents(__DIR__.'/context-source-lock.json'),true,512,JSON_THROW_ON_ERROR);
$errors=[];$checks=0;
$check=static function(bool $ok,string $message)use(&$errors,&$checks):void{$checks++;if(!$ok)$errors[]=$message;};
$report=['scope'=>'PHP86-003 through PHP86-007; stage-specific observations, not exact-pin or whole-version certification','sourcePins'=>$lock['phpSource'],'observations'=>[]];
foreach(['85'=>'8.5','86'=>'8.6']as $suffix=>$version){
    $exe=$options['php'.$suffix];
    $run=static function(array $args)use($exe,$root):array{
        $command=array_merge([$exe,'-n','-d','error_reporting=-1','-d','zend.multibyte=0','-d','short_open_tag=1'],$args);
        $p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($p))throw new RuntimeException('Cannot start interpreter');
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        return ['exit'=>proc_close($p),'output'=>trim(str_replace([$root,str_replace('\\','/',$root)],'<repository>',$out))];
    };
    $report['interpreters'][$version]=['sha256'=>hash_file('sha256',$exe),'version'=>$run(['-v'])];
    $check(str_contains($report['interpreters'][$version]['version']['output'],'PHP '.$version.'.'),'Interpreter version '.$version);
    $check($report['interpreters'][$version]['sha256']===$lock['executables'][$version],'Recorded distribution hash '.$version);
    foreach($lock['files'][$version] as $file=>$hash){
        $path=$options['sources'.$suffix].'/'.$file;
        $check(is_file($path)&&hash_file('sha256',$path)===$hash,'Source hash '.$version.' '.$file);
    }
    foreach($cases as $case){
        $file=$root.'/'.$case['fixture'];
        $lint=$run(['-l',$file]);
        $result=$case['stage']==='lint'?$lint:$run([$file]);
        $report['observations'][]=['id'=>$case['id'],'finding'=>$case['finding'],'version'=>$version,'stage'=>$case['stage'],'fixtureSha256'=>hash_file('sha256',$file),'lint'=>$lint,'result'=>$result];
        $check(($result['exit']===0)===$case['accept'.$suffix],$version.' acceptance '.$case['id']);
        if($version==='8.6'){
            if($case['diagnostic86']!==null)$check(str_contains(strtolower($result['output']),strtolower($case['diagnostic86'])),'Diagnostic '.$case['id']);
            if($case['output86']!==null)$check($result['output']===$case['output86'],'Runtime output '.$case['id']);
        }
    }
}
$check(hash_file('sha256',$root.'/grammar/8.6/php.ebnf')===$lock['grammarBefore'],'Zero Phase 3C EBNF changes');
$source=file_get_contents($root.'/grammar/8.6/php.ebnf');
$grammar=(new \PhpGrammar\Ebnf\Parser())->parse($source);
$doc=file_get_contents($root.'/grammar/8.6/php.md');
preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s',$doc,$match);
$check(isset($match[1])&&trim($match[1])===trim($source),'Markdown production parity');
$changes=file_get_contents($root.'/grammar/8.6/changes.md');
foreach($lock['productionsByFinding']as $finding=>$productions){
    $check(str_contains($changes,$finding)&&str_contains($doc,$finding),'Specification and change entry '.$finding);
    foreach($productions as $production)$check($grammar->hasProduction($production)&&str_contains($changes,'`'.$production.'`'),'Mapped production '.$finding.' '.$production);
    $related=array_filter($cases,static fn($case)=>$case['finding']===$finding);
    $check(count($related)>0,'Fixtures for '.$finding);
}
foreach($cases as $case)$check(isset($lock['productionsByFinding'][$case['finding']])&&$case['evidence']!=='','Fixture traceability '.$case['id']);
$report['checks']=$checks;$report['errors']=$errors;
if(isset($options['report']))file_put_contents($options['report'],json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo "$checks checks; ".count($report['observations']).' paired-version stage observations; '.count($errors)." failures\n";
foreach($errors as $error)fwrite(STDERR,$error."\n");
exit($errors?1:0);
