<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/GaCertification.php';
use PhpGrammar\Tools\GaCertification as G;
use PhpGrammar\Tools\Support as S;
$root = str_replace('\\', '/', realpath(__DIR__ . '/../..')); chdir($root);
$out = 'docs/8.6/structural-certification';
if (($argv[1] ?? '') !== '--baseline') throw new RuntimeException('Use --baseline once before implementation');
if (file_exists("$out/baseline.json")) throw new RuntimeException('Baseline already exists');
$g = new G($root);
$priorPath = G::OUT . '/runs/stable-closure-r1/evidence.json';
$prior = S::read($priorPath);
$errors = $g->hashErrors($prior['inputs']);
if ($errors !== []) throw new RuntimeException(implode('; ', $errors));
$inputs = $g->inventory();
$inputs[G::OUT . '.md'] = hash_file('sha256', G::OUT . '.md');
foreach (S::files($root . '/' . G::OUT . '/runs') as $p) $inputs[substr($p, strlen($root)+1)] = hash_file('sha256', $p);
ksort($inputs);
$snapshot = '.audit/structural-predecessor';
if (is_dir($snapshot)) throw new RuntimeException('Snapshot already exists');
foreach ($inputs as $p=>$hash) {
    $dest="$snapshot/$p"; if (!is_dir(dirname($dest))) mkdir(dirname($dest),0777,true);
    copy($p,$dest); if (hash_file('sha256',$dest)!==$hash) throw new RuntimeException('Copy mismatch: '.$p);
}
mkdir($out,0777,true);
$baseline=['contract'=>'php86-structural-baseline-v1','head'=>S::run(['git','rev-parse','HEAD'],$root),
    'status'=>S::run(['git','status','--porcelain=v1'],$root),'remoteTags'=>S::run(['git','ls-remote','--tags','https://github.com/Zoclee/php-grammar.git'],$root),
    'officialTag'=>S::run(['git','ls-remote','https://github.com/php/php-src.git','refs/tags/php-8.6.0','refs/tags/php-8.6.0^{}'],$root),
    'previousEvidence'=>['path'=>$priorPath,'sha256'=>hash_file('sha256',$priorPath),'results'=>array_count_values(array_column($prior['results'],'status'))],
    'extendedGateInventory'=>$g->gates(),'extendedBlockers'=>S::read(G::OUT.'/blockers-v2.json'),
    'manifest'=>S::read('php-grammar.json'),'inputs'=>$inputs,'snapshot'=>$snapshot,'sourceCommit'=>G::PIN];
file_put_contents("$out/baseline.json",S::json($baseline));
copy(G::OUT.'.md',"$out/extended-summary.md");
echo count($inputs)." authenticated predecessor inputs; baseline SHA-256 ".hash_file('sha256',"$out/baseline.json")."\n";
