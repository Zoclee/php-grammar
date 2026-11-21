<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$root=str_replace('\\','/',dirname(__DIR__,2));chdir($root);
if(($argv[1]??'')!=='--preserve-r1')throw new RuntimeException('Explicit --preserve-r1 required');
foreach(['refs/heads/release/v1.1.0','refs/tags/v1.1.0']as$ref)if(S::run(['git','show-ref','--verify','--quiet',$ref],$root)['exit_code']===0)throw new RuntimeException('Release ref exists; do not reset');
$dir='docs/8.6/structural-certification/preparation-revisions';if(!is_dir($dir))mkdir($dir,0777,true);
$old='docs/8.6/structural-release-signoff.md';$saved=$dir.'/r1-signoff.md';
if(!is_file($old)||file_exists($saved))throw new RuntimeException('Unexpected sign-off state');
$hash=hash_file('sha256',$old);if(!rename($old,$saved)||hash_file('sha256',$saved)!==$hash)throw new RuntimeException('Failed to preserve prior sign-off');
file_put_contents($dir.'/r1.json',S::json(['report'=>'docs/8.6/structural-certification/runs/release-v1_1_0-r1/evidence.json','signoff'=>$saved,'sha256'=>$hash,'reason'=>'Structural gates passed. Local Git preparation stopped before refs because batch stored-blob length differs from filtered checkout length. Fix the checker and regenerate certification for the changed tooling.']));
$index=$root.'/.audit/v1.1.0-release.index';if(is_file($index))unlink($index);
echo "Prior sign-off preserved; only the task-owned temporary index removed\n";
