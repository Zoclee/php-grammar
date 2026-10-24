<?php
declare(strict_types=1);
// Verify additive audit evidence without changing a historical release gate.
$root=dirname(__DIR__,2);chdir($root);$checks=0;$errors=[];
$check=static function(bool $ok,string $message)use(&$checks,&$errors):void{++$checks;if(!$ok)$errors[]=$message;};
$read=static fn(string $p):array=>json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR);
$run=static function(array $command)use($root):array{
 $log=tempnam(sys_get_temp_dir(),'audit86-check-');$p=proc_open($command,[1=>['file',$log,'w'],2=>['file',$log,'a']],$pipes,$root);
 if(!is_resource($p))throw new RuntimeException('Cannot start check');$exit=proc_close($p);$text=file_get_contents($log);unlink($log);return ['command'=>['rtk','proxy',...$command],'exit'=>$exit,'output'=>$text];
};
$ledger=$read('docs/8.6/source-completeness-ledger.json');$baseline=$read('docs/8.6/source-audit-baseline.json');$historical=0;
foreach($baseline['hashes'] as $path=>$hash){$check(is_file($path)&&hash_file('sha256',$path)===$hash,'Preservation: '.$path);if(str_contains($path,'/8.5/'))++$historical;}
foreach($ledger['inputs'] as $path=>$hash)$check(is_file($path)&&hash_file('sha256',$path)===$hash,'Ledger input: '.$path);
$check($ledger['validation']['errors']===[],'Ledger validation');
$check(count($ledger['canonical']['elements'])===1161,'Canonical identity inventory');
$check(count($ledger['contextual']['restrictions'])===73,'Contextual restriction inventory');
$check(count($ledger['rfcs'])===59,'RFC inventory');
$check(count($ledger['discovery']['hunks'])===578,'Hunk inventory');
foreach($ledger['parser']['rows'] as $name=>$row)foreach($row['positiveWitnesses'] as $witnesses)foreach($witnesses as $id)if(str_starts_with($id,'fixture:'))$check(is_file('tests/fixtures/php/8.6/valid/'.substr($id,8)),'Parser witness '.$name.': '.$id);
$validation=$read('docs/8.6/source-audit-validation.json');
foreach($validation['commands'] as $id=>$row)$check($row['exit']===0,'Validation command '.$id);
$check(str_contains($validation['commands']['suite']['output'],'OK (6147 tests, 58021 assertions)'),'Actual full-suite counts');
$boundaries=$read('docs/8.6/source-audit-boundaries.json');$check($boundaries['errors']===[],'Boundary mismatches');
foreach($boundaries['inputs'] as $path=>$hash)$check(hash_file('sha256',$path)===$hash,'Boundary input '.$path);
$remote=$read('docs/8.6/source-audit-upstream.json');$check(count($remote['immutableSourceDownloads'])===18,'Remote source inventory');
foreach($remote['immutableSourceDownloads'] as $row){$check($row['exit']===0&&$row['matches']&&$row['remoteSha256']===$row['localSha256'],'Immutable upstream '.$row['version'].' '.$row['path']);}
foreach($remote['discussions'] as $row)$check($row['exit']===0&&count($row['comments'])<100,'Bounded discussion endpoint '.$row['url']);
$jsonPaths=[...glob('docs/8.6/source-audit-*.json'),'docs/8.6/source-completeness-ledger.json'];
foreach($jsonPaths as $path){$read($path);$check(true,'JSON '.$path);}
$links=0;
foreach(['docs/8.6/source-completeness-audit.md'] as $path){
 $text=file_get_contents($path);$check(!preg_match('/[\t ]+$/m',$text),'Markdown trailing whitespace '.$path);
 preg_match_all('~\[[^\]]*\]\(([^)]+)\)~',$text,$matches);
 foreach($matches[1] as $link){if(preg_match('~^https?://~',$link))continue;[$target,$anchor]=array_pad(explode('#',$link,2),2,null);$file=$target===''?$path:dirname($path).'/'.$target;
  ++$links;$check(is_file($file),'Local link '.$link);
  if($anchor!==null&&str_ends_with($file,'.md')&&is_file($file)){
   preg_match_all('/^#+ (.+)$/m',file_get_contents($file),$headings);$slugs=[];
   foreach($headings[1] as $heading)$slugs[]=str_replace(' ','-',preg_replace('/[^\pL\pN_\- ]/u','',strtolower($heading)));
   $check(in_array($anchor,$slugs,true),'Local anchor '.$link);
  }
 }
}
$commands=[];$php='.audit/php85/php.exe';
foreach(glob('tools/8.6/source-*.php') as $path){$r=$run([$php,'-n','-l',$path]);$check($r['exit']===0,'PHP syntax '.$path);$commands[]=$r;}
$r=$run([$php,'-n','tools/8.6/source-completeness-audit.php','--check']);$check($r['exit']===0,'Audit freshness');$commands[]=$r;
$temporary=tempnam(sys_get_temp_dir(),'audit86-negative-');
try{
 $r=$run([$php,'-n','tools/8.6/source-completeness-audit.php','--report='.$temporary]);$check($r['exit']===0,'Temporary ledger generation');$commands[]=$r;
 $text=file_get_contents($temporary);$check(str_contains($text,'"productions": 176'),'Mutation target');
 file_put_contents($temporary,str_replace('"productions": 176','"productions": 175',$text));
 $r=$run([$php,'-n','tools/8.6/source-completeness-audit.php','--report='.$temporary,'--check']);
 $check($r['exit']===1&&str_contains($r['output'],'Audit ledger freshness'),'Corrupted inventory must fail freshness');$commands[]=$r;
}finally{if(is_file($temporary))unlink($temporary);}
$artifactHashes=[];
foreach([...glob('docs/8.6/source-audit-*.json'),'docs/8.6/source-completeness-ledger.json','docs/8.6/source-completeness-audit.md',...glob('tools/8.6/source-*.php')] as $path)if($path!=='docs/8.6/source-audit-check.json')$artifactHashes[$path]=hash_file('sha256',$path);
$report=['date'=>'2026-10-24','checks'=>$checks,'errors'=>$errors,'preservedFiles'=>count($baseline['hashes']),'preserved85PathFiles'=>$historical,'localLinks'=>$links,'jsonFiles'=>count($jsonPaths),'artifactHashes'=>$artifactHashes,'commands'=>$commands,'negativeTest'=>'Only temporary output mutated; original evidence unchanged.'];
file_put_contents('docs/8.6/source-audit-check.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
echo json_encode(['checks'=>$checks,'errors'=>$errors,'preservedFiles'=>count($baseline['hashes']),'preserved85PathFiles'=>$historical,'localLinks'=>$links],JSON_PRETTY_PRINT),"\n";exit($errors===[]?0:1);
