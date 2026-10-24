<?php
declare(strict_types=1);
chdir(dirname(__DIR__, 2));
$pins=['8.5'=>'7a4c62795365ed6a97a0184c96375b9fb4d53b1e','8.6'=>'f23b4c222cebe33be93ee8ecaaa14390c5507a08'];$rows=[];
foreach($pins as $version=>$pin)foreach(['Zend/zend_language_parser.y','Zend/zend_language_scanner.l','Zend/zend_compile.c','Zend/zend_ast.c','Zend/zend_ast.h','Zend/zend_inheritance.c','Zend/zend_attributes.c','Zend/zend_enum.c','Zend/zend_constants.c'] as $path){
 $url='https://raw.githubusercontent.com/php/php-src/'.$pin.'/'.$path;$out='.audit/phase6-remote-'.str_replace(['/','.'],['_','_'],$version.'-'.$path);
 $p=proc_open(['curl.exe','--fail','--silent','--show-error','--location','--max-time','25',$url,'--output',$out],[1=>STDOUT,2=>STDERR],$pipes);$exit=proc_close($p);
 $local='.audit/php-php-src-'.substr($pin,0,7).'/'.$path;
 $rows[]=['version'=>$version,'path'=>$path,'url'=>$url,'exit'=>$exit,'remoteSha256'=>$exit===0?hash_file('sha256',$out):null,'localSha256'=>hash_file('sha256',$local),'matches'=>$exit===0&&hash_file('sha256',$out)===hash_file('sha256',$local)];
 echo $version,' ',$path,' exit ',$exit,"\n";
 if($exit!==0)break 2;
}
$discussions=[];
foreach(['issues/20628/comments','pulls/20628/comments','issues/19658/comments','pulls/19658/comments'] as $path){
 $url='https://api.github.com/repos/php/php-src/'.$path.'?per_page=100';$out='.audit/phase6-discussion-'.str_replace('/','-',$path).'.json';
 $p=proc_open(['curl.exe','--fail','--silent','--show-error','--location','--max-time','25','--header','User-Agent: php-grammar-source-audit',$url,'--output',$out],[1=>STDOUT,2=>STDERR],$pipes);$exit=proc_close($p);
 $comments=$exit===0?json_decode(file_get_contents($out),true):[];
 $discussions[]=['url'=>$url,'exit'=>$exit,'snapshotSha256'=>$exit===0?hash_file('sha256',$out):null,'scope'=>'First 100 comments only; bounded upstream search, not absence proof.','comments'=>$comments];
 if($exit!==0)break;
}
file_put_contents('docs/8.6/source-audit-upstream.json',json_encode(['date'=>'2026-10-24','immutableSourceDownloads'=>$rows,'discussions'=>$discussions],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
exit(count($rows)===18 && !array_filter($rows,static fn($row)=>!$row['matches']) && count($discussions)===4 && !array_filter($discussions,static fn($row)=>$row['exit']!==0) ? 0 : 1);


