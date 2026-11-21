<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/../lib/StructuralCertification.php';
use PhpGrammar\Tools\{StructuralCertification as C,GaCertification as G,Support as S};
$root=str_replace('\\','/',realpath(__DIR__.'/../..'));chdir($root);$a=S::args(['output'=>'']);
if(!preg_match('~^'.C::OUT.'/runs/[a-z0-9_-]+/package-integrity\.json$~D',$a['output']))throw new RuntimeException('Explicit revision output required');
$c=new C($root);$r=$c->gate('package-integrity');$path=dirname($a['output']).'/package.tar';
if(file_exists($path)||file_exists($a['output']))throw new RuntimeException('Immutable package output exists');
if(!is_dir(dirname($path)))mkdir(dirname($path),0777,true);
if($r['errors']===[]){
    // Stream USTAR once; PharData independently verifies all archived bytes.
    $tar=fopen($path,'xb');if($tar===false)throw new RuntimeException('Cannot create archive');
    foreach($r['details']['files']as$p=>$hash){
        if(str_starts_with($p,'/')||in_array('..',explode('/',$p),true)||str_contains($p,"\0"))throw new RuntimeException('Unsafe archive path');
        $name=$p;$prefix='';
        if(strlen($name)>100){
            $found=false;
            for($i=min(155,strlen($p)-1);$i>0;--$i)if($p[$i]==='/'&&strlen($p)-$i-1<=100){$prefix=substr($p,0,$i);$name=substr($p,$i+1);$found=true;break;}
            if(!$found)throw new RuntimeException('Path exceeds USTAR limits: '.$p);
        }
        $size=filesize($root.'/'.$p);if($size===false||$size>8589934591)throw new RuntimeException('Invalid USTAR size');
        $mode=$p==='bin/php-grammar'?0755:0644;
        $header=str_pad($name,100,"\0").sprintf("%07o\0%07o\0%07o\0%011o\0%011o\0",$mode,0,0,$size,0)
            .str_repeat(' ',8).'0'.str_repeat("\0",100)."ustar\00000".str_repeat("\0",64)
            .sprintf("%07o\0%07o\0",0,0).str_pad($prefix,155,"\0").str_repeat("\0",12);
        if(strlen($header)!==512)throw new RuntimeException('Invalid tar header size');
        $header=substr_replace($header,sprintf("%06o\0 ",array_sum(unpack('C*',$header))),148,8);
        if(fwrite($tar,$header)!==512)throw new RuntimeException('Short tar header write');
        $source=fopen($root.'/'.$p,'rb');if($source===false)throw new RuntimeException('Cannot read '.$p);
        $copied=stream_copy_to_stream($source,$tar);fclose($source);if($copied!==$size)throw new RuntimeException('Short tar content write');
        $padding=(512-$size%512)%512;if($padding&&fwrite($tar,str_repeat("\0",$padding))!==$padding)throw new RuntimeException('Short padding write');
    }
    if(fwrite($tar,str_repeat("\0",1024))!==1024)throw new RuntimeException('Short tar terminator');fclose($tar);
    $r['details']['archive']=$path;$r['details']['archiveSha256']=hash_file('sha256',$path);$r['errors']=$c->archiveErrors($r['details']);
}
G::writeNew($a['output'],$r);echo S::json(['files'=>count($r['details']['files']),'errors'=>$r['errors']]);exit($r['errors']===[]?0:1);
