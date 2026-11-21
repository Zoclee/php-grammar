<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__,2).'/vendor/autoload.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
$root=str_replace('\\','/',realpath(__DIR__.'/../..')); chdir($root);
$dir='tests/fixtures/php/8.6/structural-hooks';
$out=$argv[2] ?? 'docs/8.6/structural-certification/hooks-before.json';
$mode=$argv[1]??'';
if($mode==='--prepare') {
    if(is_dir($dir)) throw new RuntimeException('Hook corpus already exists'); mkdir($dir,0777,true);
    $cases=[];
    foreach(['get','GET','set','SeT','unknown','UNKNOWN','from','enum','__get','h'.chr(0xe9),'class','yield','match','readonly','123','"get"','A\\get','$get'] as $i=>$name) {
        $structural=$i<10; $legal=$i<4;
        foreach(['live','retained','discarded','promoted-live','promoted-discarded'] as $stage) {
            $property=str_starts_with($stage,'promoted') ? 'public function __construct(public int $x {'.$name.' => 1;}) {}' : 'public int $x {'.$name.' => 1;}';
            $declaration='new class {'.$property.'}';
            if(str_starts_with($stage,'promoted')) $declaration='new class(1) {'.$property.'}';
            $discarded=str_ends_with($stage,'discarded');
            $source='<?php '.($stage==='live' || $stage==='promoted-live' ? $declaration.';' : ($discarded?'false && ':'$f=').'static function(){return '.$declaration.';};')."\n";
            $id='hook-'.$i.'-'.$stage; $path="$dir/$id.php"; file_put_contents($path,$source);
            $cases[]=['id'=>$id,'fixture'=>$path,'sha256'=>hash('sha256',$source),'nameHex'=>bin2hex($name),'stage'=>$stage,'structural'=>$structural,'lintAccepted'=>$structural&&($legal||$discarded), 'unknownHook'=>$structural&&!$legal&&!$discarded];
        }
    }
    file_put_contents("$dir/cases.json",S::json(['contract'=>'php86-hook-correction-v1','cases'=>$cases]));
    echo count($cases)." hook controls prepared\n";
} elseif(in_array($mode,['--observe','--check'],true)) {
    $m=PhpGrammarMatcher::forRepositoryRoot($root);$rows=[];$errors=[];
    foreach(S::read("$dir/cases.json")['cases'] as $c) {
        if(hash_file('sha256',$c['fixture'])!==$c['sha256']) throw new RuntimeException('Changed hook fixture');
        $command=[$root.'/.audit/phase8b/source/x64/Release/php.exe','-n','-d','display_errors=1','-d','log_errors=0','-l',$c['fixture']];
        $lint=S::run($command,$root);$accepted=$lint['exit_code']===0;
        $native=$m->matches('8.6',file_get_contents($c['fixture']))->matched;
        if($accepted!==$c['lintAccepted'] || ($c['unknownHook']&&!str_contains($lint['output'],'Unknown hook'))) $errors[]='Oracle mismatch: '.$c['id'];
        if($mode==='--check' && $native!==$c['structural']) $errors[]='Structural mismatch: '.$c['id'];
        $lint['outputBase64']=base64_encode($lint['output']);
        $lint['output']=json_decode(json_encode($lint['output'],JSON_INVALID_UTF8_SUBSTITUTE),true);
        $rows[]=$c+['command'=>$command,'lint'=>$lint,'native'=>$native,'historical85'=>$m->matches('8.5',file_get_contents($c['fixture']))->matched];
    }
    $data=['contract'=>'php86-hook-correction-v1','sourceCommit'=>'8aaada3f9605d81a38b8240f93c7389dfbc77ef9','observations'=>$rows,'errors'=>$errors];
    if(file_exists($out)) throw new RuntimeException('Refusing overwrite');
    file_put_contents($out,S::json($data));echo S::json(['count'=>count($rows),'errors'=>$errors]);exit($errors===[]?0:1);
} else throw new RuntimeException('Use --prepare, --observe or --check [new-output]');
