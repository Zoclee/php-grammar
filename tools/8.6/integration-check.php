<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Conformance\VersionBoundaryFixtureRepository;
use PhpGrammar\Php\Lexing\Lexer;

// A finite draft matrix. Execution is limited to reviewed declaration-link fixtures.
$root=dirname(__DIR__,2);
$options=getopt('', ['php85:', 'php86:', 'sources85:', 'sources86:', 'report:']);
foreach(['php85','php86','sources85','sources86']as $key)if(!isset($options[$key])){fwrite(STDERR,"Required: --php85 --php86 --sources85 --sources86 [--report]\n");exit(1);}
$errors=[];$checks=0;$observations=[];
$check=static function(bool $ok,string $label)use(&$errors,&$checks):void{$checks++;if(!$ok)$errors[]=$label;};
$run=static function(array $cmd)use($root):array{
    $p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
    if(!is_resource($p))throw new RuntimeException('Cannot launch oracle');
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    return ['exit'=>proc_close($p),'stdout'=>$out,'stderr'=>$err];
};
$temporary=tempnam(sys_get_temp_dir(),'php86-integration-');
try {
    $lock=json_decode(file_get_contents(__DIR__.'/source-lock.json'),true,512,JSON_THROW_ON_ERROR);
    foreach($lock['records']as $p=>$h)$check(hash_file('sha256',$root.'/'.$p)===$h,'Evidence record hash '.$p);
    $data=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/integration-cases.json'),true,512,JSON_THROW_ON_ERROR);
    $matcher=PhpGrammarMatcher::forRepositoryRoot($root);
    $boundaries=(new VersionBoundaryFixtureRepository($root.'/tests/fixtures/version-boundaries'))->load();
    $context=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/context-cases.json'),true,512,JSON_THROW_ON_ERROR);
    // Additional reviewed Phase 5B stage fixtures; original case manifests remain immutable.
    $expansion=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/coverage-cases.json'),true,512,JSON_THROW_ON_ERROR);
    $context=array_merge($context,$expansion['cases']);
    // Additional reviewed Phase 5E declaration-link/lint fixtures.
    $finalExpansion=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/final-coverage-cases.json'),true,512,JSON_THROW_ON_ERROR);
    $context=array_merge($context,$finalExpansion['cases']);
    $index=json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/boundary-cases.json'),true,512,JSON_THROW_ON_ERROR);
    $check(count($index['files'])===count($boundaries),'Complete boundary index');
    foreach($index['files']as $path=>$hash)$check(is_file($root.'/'.$path)&&hash_file('sha256',$root.'/'.$path)===$hash,'Boundary metadata hash '.$path);
    $interpreters=[];
    foreach(['8.5'=>'85','8.6'=>'86']as $version=>$suffix){
        $binary=realpath($options['php'.$suffix]);$sources=realpath($options['sources'.$suffix]);
        if($binary===false||$sources===false)throw new RuntimeException('Missing source tree or executable for '.$version);
        $interpreters[$version]=['sha256'=>hash_file('sha256',$binary),'version'=>$run([$binary,'-n','-v']),'exactPinVerified'=>false];
        $check($interpreters[$version]['sha256']===$lock['executables'][$version],'Distribution hash '.$version);
        foreach($lock['files'][$version]as $path=>$hash)$check($hash===null?!file_exists($sources.'/'.$path):(is_file($sources.'/'.$path)&&hash_file('sha256',$sources.'/'.$path)===$hash),'Pinned source '.$version.' '.$path);
        foreach([true,false]as $short){
            $prefix=[$binary,'-n','-d','zend.multibyte=0','-d','short_open_tag='.($short?'1':'0'),'-d','error_reporting=-1'];
            foreach($data['cases']as $case){
                $file=$root.'/'.$case['fixture'];$source=file_get_contents($file);
                $check(hash('sha256',$source)===$case['sha256'],'Fixture hash '.$case['id']);
                $result=$run([...$prefix,'-l',$file]);
                $structural=$matcher->withShortOpenTag($short)->matches($version,$source)->matched;
                $check($structural===$case['accepted'],'Structure '.$version.' '.$case['id']);
                $check(($result['exit']===0)===$case['accepted'],'Lint '.$version.' '.$case['id']);
                $observations[]=['id'=>$case['id'],'version'=>$version,'shortOpenTag'=>$short,'stage'=>'lint','structural'=>$structural,'result'=>$result];
            }
        }
        foreach($data['lexical']as $index=>$case){
            $tokens=Lexer::forVersion($version)->withShortOpenTag($case['shortOpenTag'])->tokenize($case['source']);
            $check(implode('',array_map(static fn($t)=>$t->lexeme,$tokens->all()))===$case['source'],'Lexical byte preservation '.$version.' '.$case['id']);
            file_put_contents($temporary,$case['source']);
            $prefix=[$binary,'-n','-d','zend.multibyte=0','-d','short_open_tag='.($case['shortOpenTag']?'1':'0')];
            $lint=$run([...$prefix,'-l',$temporary]);$check(($lint['exit']===0)=== !($case['id']==='pfa' && $version==='8.5'),'Lexical lint '.$version.' '.$case['id']);
            $observations[]=['id'=>$case['id'],'version'=>$version,'stage'=>'lexical-lint','shortOpenTag'=>$case['shortOpenTag'],'result'=>$lint];
            if($index<7){
                $oracle=$run([...$prefix,'-r','echo json_encode(array_values(array_map(static fn($t)=>trim(is_array($t)?$t[1]:$t),array_filter(token_get_all(file_get_contents($argv[1])),static fn($t)=>!is_array($t)||!in_array($t[0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true)))));',$temporary]);
                $own=array_map(static fn($t)=>trim($t->lexeme),$tokens->withoutTrivia()->all());
                $check($oracle['exit']===0 && json_decode($oracle['stdout'],true)===$own,'Native token boundaries '.$version.' '.$case['id']);
                $observations[]=['id'=>$case['id'],'version'=>$version,'stage'=>'token-boundaries','repository'=>$own,'oracle'=>$oracle];
            }
        }
        foreach($boundaries as $case){
            $structural=$matcher->matches($version,$case->source)->matched;
            if($case->validationStage==='structural'){$check($structural===$case->shouldAccept($version),'Structural boundary '.$version.' '.$case->name);$result=['matched'=>$structural];}
            else {
                $allowed=array_values(array_filter($context,static fn($c)=>$c['finding']===$case->finding && $c['stage']===$case->validationStage && file_get_contents($root.'/'.$c['fixture'])===$case->source));
                if(count($allowed)!==1)throw new RuntimeException('Boundary is not a uniquely reviewed stage fixture: '.$case->name);
                file_put_contents($temporary,$case->source);
                $prefix=[$binary,'-n','-d','zend.multibyte=0','-d','short_open_tag=1','-d','error_reporting=-1'];
                $lint=$run([...$prefix,'-l',$temporary]);
                $result=$case->validationStage==='lint'?$lint:$run([...$prefix,$temporary]);
                $check($structural,'Context boundary must remain structurally accepted '.$case->name);
                $check(($result['exit']===0)===$case->shouldAccept($version),'Stage boundary '.$version.' '.$case->name);
                $result=['lint'=>$lint,'stageResult'=>$result];
            }
            $observations[]=['id'=>$case->name,'version'=>$version,'stage'=>$case->validationStage,'result'=>$result];
        }
    }
    $report=['scope'=>'Functional draft matrix only; no certification','sourcePin'=>$lock['phpSource'],
        'profile'=>'-n; zend.multibyte=0; both short-tag profiles for compatibility; boundary stage explicit; controlled declaration linking only',
        'inputHashes'=>['checker'=>hash_file('sha256',__FILE__),'cases'=>hash_file('sha256',$root.'/tests/fixtures/php/8.6/integration-cases.json'),'sourceLock'=>hash_file('sha256',__DIR__.'/source-lock.json'),'boundaries'=>hash_file('sha256',$root.'/tests/fixtures/php/8.6/boundary-cases.json')],
        'interpreters'=>$interpreters,'checks'=>$checks,'observations'=>$observations,'errors'=>$errors,'openFindings'=>$lock['openFindings']];
    // Normalize transient paths without changing diagnostics or exit codes.
    array_walk_recursive($report,static function(&$value)use($root,$temporary):void{if(is_string($value))$value=str_replace([$temporary,$root,str_replace('\\','/',$root)],['<probe>','<repository>','<repository>'],$value);});
    if(isset($options['report']))file_put_contents($options['report'],json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
    echo "$checks checks; ".count($observations).' observations; '.count($errors)." failures\n";foreach($errors as $error)echo "$error\n";
    $exitCode=$errors===[]?0:1;
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");$exitCode=1;}finally{unlink($temporary);}
exit($exitCode);
