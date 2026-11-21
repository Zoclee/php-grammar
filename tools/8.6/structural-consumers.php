<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
chdir(dirname(__DIR__,2));$out='docs/8.6/structural-certification';$base=S::read($out.'/baseline.json');
if(($argv[1]??'')!=='--prepare')throw new RuntimeException('Use --prepare once');
foreach(['README.md','docs/usage.md','docs/api.md','docs/sources.md']as$p){
    $dest=$out.'/predecessor/'.$p;if(file_exists($dest))throw new RuntimeException('Consumer document already transitioned');
    if(hash_file('sha256',$p)!==$base['inputs'][$p])throw new RuntimeException('Unexpected document edits');
    if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);copy($p,$dest);$s=file_get_contents($p);
    if($p==='docs/sources.md')$s.="\n## PHP 8.6.0 GA structural certification\n\nThe final `PHP-8.6` source is tag `php-8.6.0` at\n`8aaada3f9605d81a38b8240f93c7389dfbc77ef9`. The versioned GA lock and build\nreceipt remain unchanged. The independent structural contract verifies all\nretained parser/scanner mappings, five PFA production changes, the\nCA86-001-STRUCTURAL hook correction and SG86-002 yield-key prefix correction.\nSee [the structural contract](structural-certification.md),\n[hook Gate B evidence](8.6/structural-certification/gate-b.json), and\n[yield Gate B evidence](8.6/structural-certification/yield-gate-b.json).\nNeither correction is new PHP 8.6 syntax; PHP 8.5 remains unchanged.\nCompiler/VM, ordered contextual, AST and exhaustive scanner-state obligations\nremain visible in the original extended reports.\n";
    else {
        $s=preg_replace('/an incomplete\s+RC4\s+draft/','a GA structural grammar awaiting structural certification',$s);
        $s=preg_replace('/an incomplete\s+RC4\s*\r?\ndraft/','a GA structural grammar awaiting structural certification',$s);
        $s=str_replace('Draft structural matching','Structural matching',$s);
        $s=str_replace('PHP86-013/014/015 remain unresolved','PHP86-013/014/015 remain unresolved under extended conformance',$s);
        $link=$p==='README.md'?'docs/structural-certification.md':'structural-certification.md';
        $s.="\nThe independent [structural certification contract]($link) distinguishes\nstructural grammar support from contextual compiler and runtime equivalence.\nHistorical extended certification results remain available with their original\nfailures and limitations. Stable promotion requires independently verified\nstructural release evidence.\n";
    }file_put_contents($p,$s);
}
echo "Consumer documents now identify the GA structural profile without premature stable promotion.\n";
