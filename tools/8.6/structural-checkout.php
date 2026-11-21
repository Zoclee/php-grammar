<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/../lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,Support as S};
$root=str_replace('\\','/',dirname(__DIR__,2));chdir($root);
$marker="\n# Structural release: retain authenticated bytes across Git checkout.\n";
if(($argv[1]??'')==='--preserve'){$s=file_get_contents('.gitattributes');$at=strpos($s,$marker);if($at!==false)file_put_contents('.gitattributes',substr($s,0,$at));}
$files=array_keys((new C($root))->packageInventory());
$input=tempnam(sys_get_temp_dir(),'php86-paths-');$output=tempnam(sys_get_temp_dir(),'php86-hashes-');$err=tempnam(sys_get_temp_dir(),'php86-git-');
file_put_contents($input,implode("\n",$files)."\n");
$p=proc_open(['rtk','proxy','git','hash-object','--stdin-paths'],[0=>['file',$input,'r'],1=>['file',$output,'w'],2=>['file',$err,'w']],$pipes,$root);
if(!is_resource($p)||proc_close($p)!==0)throw new RuntimeException(file_get_contents($err));
$hashes=preg_split('/\R/',trim(file_get_contents($output)));$changed=[];
if(count($hashes)!==count($files))throw new RuntimeException('Incomplete Git hash output');
$p=proc_open(['rtk','proxy','git','check-attr','--stdin','eol'],[0=>['file',$input,'r'],1=>['file',$output,'w'],2=>['file',$err,'w']],$pipes,$root);
if(!is_resource($p)||proc_close($p)!==0)throw new RuntimeException(file_get_contents($err));
$eols=[];foreach(preg_split('/\R/',trim(file_get_contents($output)))as$row){if(preg_match('/^(.*): eol: (.*)$/',$row,$m))$eols[$m[1]]=$m[2];}
foreach($files as$i=>$file){$s=file_get_contents($file);if(sha1('blob '.strlen($s)."\0".$s)===$hashes[$i])continue;
    // A CRLF checkout policy intentionally cleans CRLF to LF and restores it.
    if(($eols[$file]??'')==='crlf'&&str_replace("\n","\r\n",str_replace("\r\n","\n",$s))===$s)continue;
    $changed[]=$file;
}
foreach([$input,$output,$err]as$p)unlink($p);
if(($argv[1]??'')==='--preserve'&&$changed!==[]){$s="\n# Structural release: retain authenticated bytes across Git checkout.\n";foreach($changed as$p)$s.='/'.$p." -text\n";file_put_contents('.gitattributes',$s,FILE_APPEND);}
file_put_contents('.audit/structural-checkout.json',S::json(['filteredFiles'=>count($files),'byteChangingFilters'=>$changed]));
echo S::json(['filteredFiles'=>count($files),'byteChangingFilters'=>count($changed)]);exit($changed===[]?0:1);
