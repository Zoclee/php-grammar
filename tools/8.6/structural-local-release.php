<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/../lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,GaCertification as G,Support as S};
$root=str_replace('\\','/',dirname(__DIR__,2));chdir($root);$a=S::args(['report'=>''],['prepare','check-tree']);
if($a['prepare']===$a['check-tree'])throw new RuntimeException('Choose --prepare or --check-tree exclusively');
$checkOnly=$a['check-tree'];$c=new C($root);
if(!$checkOnly){$r=$c->read($a['report']);$errors=$c->verify($r,$a['report']);
if($errors!==[]||!$r['structuralCertified']||$c->read('php-grammar.json')['versions'][1]['status']!=='stable')throw new RuntimeException(S::json($errors?:['Stable certification required']));
$check=S::run([PHP_BINARY,'-n',__DIR__.'/structural-release.php','--check-signoff','--report',$a['report']],$root);
if($check['exit_code']!==0)throw new RuntimeException($check['output']);
}
$git=static function(array $args)use($root):string{$r=S::run(['git',...$args],$root);if($r['exit_code']!==0)throw new RuntimeException($r['output']);return trim($r['output']);};
$head=$git(['rev-parse','HEAD']);if($head!==trim($c->baseline()['head']['output']))throw new RuntimeException('Repository HEAD changed; review before release preparation');
foreach(['refs/heads/release/v1.1.0','refs/tags/v1.1.0']as$ref)if(S::run(['git','show-ref','--verify','--quiet',$ref],$root)['exit_code']===0)throw new RuntimeException('Ref already exists: '.$ref);
$index=$git(['rev-parse','--git-path','index']);$indexHash=hash_file('sha256',$index);
$paths=$c->packageInventory();
foreach([C::OUT.'/runs',G::OUT.'/runs']as$dir)foreach(S::files($root.'/'.$dir)as$file){$p=substr($file,strlen($root)+1);if(str_ends_with($p,'/package.tar'))continue;$paths[$p]=hash_file('sha256',$file);}
if(is_file('docs/8.6/structural-release-signoff.md'))$paths['docs/8.6/structural-release-signoff.md']=hash_file('sha256','docs/8.6/structural-release-signoff.md');ksort($paths);
foreach($paths as$p=>$hash)if(filesize($p)>100*1024*1024)throw new RuntimeException('Use a release asset for oversized Git file: '.$p);
$releaseIndex=$root.'/.audit/v1.1.0-'.($checkOnly?'check':'release').'.index';if(file_exists($releaseIndex))throw new RuntimeException('Release index already exists');
file_put_contents('.audit/v1.1.0-release-paths',implode("\0",array_keys($paths))."\0");
file_put_contents('.audit/v1.1.0-release-message',"Release v1.1.0: stable PHP 8.6 structural grammar\n\nIndependent structural certification; preserve extended limitations and PHP 8.5.\n");
putenv('GIT_INDEX_FILE='.$releaseIndex);
try{
    $git(['read-tree',$head]);$git(['add','--pathspec-from-file=.audit/v1.1.0-release-paths','--pathspec-file-nul']);$tree=$git(['write-tree']);
    // Read the candidate tree through Git checkout filters before creating refs.
    $input=tempnam(sys_get_temp_dir(),'php86-tree-');$output=tempnam(sys_get_temp_dir(),'php86-blobs-');$err=tempnam(sys_get_temp_dir(),'php86-git-');
    $lines=[];foreach($paths as$p=>$hash)$lines[]=$tree.':'.$p.' '.$p;file_put_contents($input,implode("\n",$lines)."\n");
    $proc=proc_open(['git','cat-file','--filters','--batch'],[0=>['file',$input,'r'],1=>['file',$output,'w'],2=>['file',$err,'w']],$pipes,$root);
    if(!is_resource($proc)||proc_close($proc)!==0)throw new RuntimeException(file_get_contents($err));
    $stream=fopen($output,'rb');
    // --batch reports the stored blob size even when --filters changes CRLF
    // length. Consume the expected checkout length, then verify both its hash
    // and the batch separator; extra/missing output still fails closed.
    foreach($paths as$p=>$hash){$header=fgets($stream);if(!preg_match('/ blob (\d+)\n$/D',$header,$m))throw new RuntimeException('Missing candidate blob: '.$p);$remaining=filesize($p);$context=hash_init('sha256');while($remaining>0){$bytes=fread($stream,min($remaining,1048576));if($bytes===false||$bytes==='')throw new RuntimeException('Truncated candidate blob');hash_update($context,$bytes);$remaining-=strlen($bytes);}if(fread($stream,1)!=="\n"||hash_final($context)!==$hash)throw new RuntimeException('Checkout bytes differ: '.$p);}
    fclose($stream);foreach([$input,$output,$err]as$p)unlink($p);
    if(!$checkOnly){$commit=$git(['commit-tree',$tree,'-p',$head,'-F','.audit/v1.1.0-release-message']);
    $git(['update-ref','refs/heads/release/v1.1.0',$commit,str_repeat('0',40)]);
    $git(['tag','-a','v1.1.0',$commit,'-F','.audit/v1.1.0-release-message']);
    }
}finally{putenv('GIT_INDEX_FILE');}
if(hash_file('sha256',$index)!==$indexHash)throw new RuntimeException('User index unexpectedly changed');
if($checkOnly){unlink($releaseIndex);echo S::json(['checkoutVerified'=>true,'files'=>count($paths),'indexUnchanged'=>true,'refsCreated'=>false]);exit;}
G::writeNew('.audit/v1.1.0-local-release.json',['commit'=>$commit,'tree'=>$tree,'parent'=>$head,'branch'=>'release/v1.1.0','tag'=>'v1.1.0','files'=>count($paths),'indexUnchanged'=>true,'evidence'=>$a['report'],'evidenceSha256'=>hash_file('sha256',$a['report']),'published'=>false]);
echo S::json(S::read('.audit/v1.1.0-local-release.json'));
