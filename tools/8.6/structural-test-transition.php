<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
chdir(dirname(__DIR__,2));$out='docs/8.6/structural-certification';$base=S::read($out.'/baseline.json');
$files=['tests/GaCertificationTest.php','tests/Php/Conformance/Php86CompilerContextualTest.php','tests/Php/Conformance/Php86ContextualChangesTest.php','tests/Php/Conformance/Php86CoverageExpansionTest.php','tests/UpstreamResolutionFreshnessTest.php','tests/Php/Conformance/Php86PartialApplicationTest.php','tests/Php/Conformance/Php86TraceabilityTest.php'];
foreach($files as$p){
    $dest=$out.'/predecessor/'.$p;if(file_exists($dest))throw new RuntimeException('Already transitioned: '.$p);
    if(hash_file('sha256',$p)!==$base['inputs'][$p])throw new RuntimeException('Unexpected user edit: '.$p);
    if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);copy($p,$dest);$s=file_get_contents($p);
    if(str_ends_with($p,'GaCertificationTest.php'))$s=str_replace("new G(str_replace('\\\\', '/', realpath(__DIR__ . '/..')))",'new G(\\PhpGrammar\\Tests\\Support\\StructuralPredecessor::root())',$s);
    elseif(str_ends_with($p,'UpstreamResolutionFreshnessTest.php')){
        $s=str_replace('$root = dirname(__DIR__);','$root = \\PhpGrammar\\Tests\\Support\\StructuralPredecessor::root();',$s);
        $s=str_replace("str_replace('\\\\', '/', dirname(__DIR__))",'\\PhpGrammar\\Tests\\Support\\StructuralPredecessor::root()',$s);
    }elseif(str_ends_with($p,'Php86PartialApplicationTest.php'))$s=str_replace("'clone-argument-list', 'argument', 'argument-no-expression'], \$changed)","'clone-argument-list', 'argument', 'argument-no-expression', 'hook-name'], \$changed)",$s);
    elseif(str_ends_with($p,'Php86TraceabilityTest.php'))$s=str_replace("assertCount(5,\$r['differences']['modified'])","assertCount(6,\$r['differences']['modified'])",$s);
    else {
        $s=preg_replace('/return dirname\(__DIR__,\s*3\);/','return \\PhpGrammar\\Tests\\Support\\StructuralPredecessor::root();',$s);
        $s=str_replace("require_once self::root() . '/tools/lib/UpstreamResolutionFreshness.php';","require_once dirname(__DIR__, 3) . '/tools/lib/UpstreamResolutionFreshness.php';",$s);
    }
    if($s===file_get_contents($p))throw new RuntimeException('No transition matched: '.$p);
    file_put_contents($p,$s);echo $p."\n";
}
