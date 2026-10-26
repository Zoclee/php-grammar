<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/CompatibilityEvidence.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\CompatibilityEvidence as E;
use PhpGrammar\Tools\HistoricalEvidence as H;
$root = str_replace('\\', '/', realpath(S::ROOT));
$args = S::args(E::defaults($root) + ['revision'=>'', 'report'=>''], ['generate','check']);
try {
    if ((int)$args['generate'] + (int)$args['check'] !== 1) throw new RuntimeException('Select --generate or --check');
    $e = new E($root);
    if ($args['check']) {
        $path = H::safeFile($root, $args['report']);
        if ($path === null) throw new RuntimeException('Missing or unsafe compatibility report');
        $r = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $errors = $e->verify($r, str_replace('\\','/',dirname($path)));
        echo S::json(['contract'=>E::CONTRACT,'errors'=>$errors,'currentCompatibilityVerified'=>$errors===[], 'certificationGranted'=>false]);
        exit($errors === [] ? 0 : 1);
    }
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/D', $args['revision'])) throw new RuntimeException('A safe new --revision is required');
    $relative = 'docs/8.5/compatibility/' . $args['revision'];
    $directory = $root . '/' . $relative;
    if (file_exists($directory)) throw new RuntimeException('Revision already exists; never overwrite evidence');
    $parent = $root;
    foreach (explode('/', $relative) as $part) { $parent .= '/' . $part; if (is_link($parent)) throw new RuntimeException('Symlink output directory'); }
    $inputs = $e->inventory();
    $config = array_intersect_key($args, E::defaults($root));
    $environment = $e->environment($config);
    mkdir($directory, 0777, true);
    $git = ['git','-c','safe.directory=' . $root];
    $head = S::run([...$git,'rev-parse','HEAD'], $root);
    $status = S::run([...$git,'status','--short','--untracked-files=all'], $root);
    if ($head['exit_code'] || $status['exit_code']) throw new RuntimeException('Cannot record repository identity');
    $results = [];
    foreach ($e->commands($config, $directory) as $id => $command) {
        echo 'Running ' . $id . "\n"; flush();
        $log = $directory . '/' . $id . '.log';
        try {
            if ($environment['errors'] !== []) throw new RuntimeException('Prerequisites unavailable; command not executed: ' . implode('; ', $environment['errors']));
            $r = S::run($command, $root, $log);
        }
        catch (Throwable $ex) { file_put_contents($log, $ex->getMessage()); $r = ['exit_code'=>127,'output'=>$ex->getMessage()]; }
        $results[$id] = ['command'=>$command,'cwd'=>$root,'exit_code'=>$r['exit_code'],'log'=>$id.'.log',
            'logSha256'=>hash_file('sha256',$log),'totals'=>E::totals($r['output'])];
        file_put_contents($directory . '/' . $id . '.json', S::json($results[$id]));
        echo $id . ': ' . $r['exit_code'] . "\n"; flush();
    }
    $report = ['schemaVersion'=>E::SCHEMA,'evidenceKind'=>'current-compatibility','contract'=>E::CONTRACT,'version'=>'8.5',
        'claim'=>E::CLAIM,'certificationGranted'=>false,'generatedAt'=>gmdate('Y-m-d\TH:i:s\Z'),
        'repository'=>['commit'=>trim($head['output']),'workingTree'=>$status['output']],
        'historicalReference'=>['commit'=>H::COMMIT,'ledgerSha256'=>H::CHECKOUT_SHA256,'integrityImplied'=>false],
        'configuration'=>$config,'environment'=>$environment,'dependencies'=>$inputs,'inputsUnchanged'=>$inputs===$e->inventory(),
        'commands'=>$results,'boundarySha256'=>is_file($directory.'/boundaries.json')?hash_file('sha256',$directory.'/boundaries.json'):'',
        'missingPrerequisites'=>$environment['errors'],'limitations'=>E::limitations()];
    file_put_contents($directory . '/evidence.json', S::json($report));
    file_put_contents($directory . '/run-receipt.json', S::json(['reportSha256'=>hash_file('sha256',$directory . '/evidence.json'),
        'scope'=>'Separate run receipt; preserve with trusted run artifacts. Not a signature or certification.']));
    echo S::json(['report'=>$relative.'/evidence.json','commandFailures'=>array_keys(array_filter($results,static fn($r)=>$r['exit_code']!==0)),
        'inputsUnchanged'=>$report['inputsUnchanged'],'note'=>'Generation is not verification. Run --check separately.','certificationGranted'=>false]);
    exit($report['inputsUnchanged'] ? 0 : 1);
} catch (Throwable $e) { echo S::json(['errors'=>[$e->getMessage()],'certificationGranted'=>false]); exit(1); }
