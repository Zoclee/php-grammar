<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\{Lexer,LexerException};
use PhpGrammar\Repository\RepositoryManifest;
$root=dirname(__DIR__,2);chdir($root);
$read=static fn($p)=>json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR);
$metadata=[];$inputs=[];
foreach(glob('tests/fixtures/php/8.6/*cases.json') as $path){
 $inputs[$path]=hash_file('sha256',$path);$data=$read($path);
 foreach($data['cases']??$data as $i=>$case) if(is_array($case)&&isset($case['fixture'])) $metadata[$case['fixture']][]=['record'=>$path.'#/'.$i,'stage'=>$case['stage']??null,'accept86'=>$case['accept86']??null,'finding'=>$case['finding']??null];
}
$manifest=RepositoryManifest::fromRepositoryRoot($root);$matcher=PhpGrammarMatcher::forManifest($manifest);$rows=[];$counts=[];$errors=[];
foreach(['valid','invalid','contextual-invalid','known-discrepancies/valid'] as $kind)foreach(glob('tests/fixtures/php/8.6/'.$kind.'/*.php') as $file){
 $text=file_get_contents($file);$inputs[$file]=hash('sha256',$text);$lexical=true;$diagnostic=null;
 try{Lexer::forVersion('8.6')->tokenize($text);}catch(LexerException $e){$lexical=false;$diagnostic=$e->getMessage();}
 $structural=$matcher->matches('8.6',$text)->matched;
 $log=tempnam(sys_get_temp_dir(),'audit86-lint-');
 $cmd=['.audit/php86/php.exe','-n','-d','zend.multibyte=0','-d','short_open_tag=1','-d','error_reporting=-1','-l',$file];
 $p=proc_open($cmd,[1=>['file',$log,'w'],2=>['file',$log,'a']],$pipes);if(!is_resource($p))throw new RuntimeException('Cannot lint');
 $exit=proc_close($p);$output=file_get_contents($log);unlink($log);
 $caseMetadata=$metadata[$file]??[];$stages=array_values(array_unique(array_filter(array_column($caseMetadata,'stage'))));
 $classification=match(true){
  str_starts_with($kind,'known-discrepancies')=>'known-discrepancy',
  !$lexical=>'lexical-rejection',
  !$structural=>'structural-or-lexical-adapter-rejection',
  $exit!==0=>'compiler-contextual-rejection',
  in_array('declaration-link',$stages,true)&&$kind==='contextual-invalid'=>'deferred-declaration-link-rejection',
  str_contains($output,'Deprecated:')||str_contains($output,'Warning:')=>'accepted-with-diagnostic',
  $kind==='contextual-invalid'=>'lint-surviving-contextual-case-review-stage',
  default=>'accepted-in-lint-and-structure'};
 if($kind==='valid'&&(!$structural||$exit!==0))$errors[]=$file.': positive rejected';
 if($kind==='invalid'&&($structural||$exit===0))$errors[]=$file.': negative acceptance mismatch';
 if($kind==='contextual-invalid'&&!$structural)$errors[]=$file.': contextual fixture rejected structurally';
 $counts[$classification]=($counts[$classification]??0)+1;
 $rows[]=['fixture'=>$file,'sha256'=>$inputs[$file],'kind'=>$kind,'lexicalAccepted'=>$lexical,'lexicalDiagnostic'=>$diagnostic,'structuralAccepted'=>$structural,'lintExit'=>$exit,'lintOutput'=>$output,'metadata'=>$caseMetadata,'classification'=>$classification,
 'stageLimit'=>'Lint combines parser and compiler checks, and may defer linking. No unreviewed fixture execution. Linking/runtime results remain in the dedicated oracle matrices.'];
}
$report=['date'=>'2026-10-24','sourcePin'=>'f23b4c222cebe33be93ee8ecaaa14390c5507a08','interpreterSha256'=>hash_file('sha256','.audit/php86/php.exe'),'exactPinExecutableVerified'=>false,
 'commandTemplate'=>['rtk','proxy',...array_slice($cmd,0,-1),'<fixture>'],'inputs'=>$inputs,'counts'=>$counts,'cases'=>$rows,'errors'=>$errors];
file_put_contents('docs/8.6/source-audit-boundaries.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
echo json_encode(['cases'=>count($rows),'counts'=>$counts,'errors'=>$errors],JSON_PRETTY_PRINT),"\n";exit($errors===[]?0:1);
