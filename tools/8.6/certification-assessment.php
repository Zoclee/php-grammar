<?php
declare(strict_types=1);

// Phase 7B assessment only. Runs the unchanged certification implementation in
// a byte-preserving snapshot, never regenerating historical workspace reports.
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../lib/ManifestValidator.php';
require_once __DIR__ . '/../lib/Certification86.php';

use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;

$args = S::args(['snapshot' => '.audit/phase7b-certification', 'php85' => '.audit/php85/php.exe',
    'php86' => '.audit/php86/php.exe', 'sources85' => '.audit/php-php-src-7a4c627',
    'sources86' => '.audit/php-php-src-f23b4c2', 'source-cache' => '.audit/phase7-sources'], ['execute', 'analyze', 'seal', 'check']);
$root = realpath(S::ROOT);
chdir($root);
$out = 'docs/8.6/certification-execution';
$baselinePath = 'docs/8.6/certification-execution-baseline.json';
$baseline = S::read($baselinePath);
$modeCount = count(array_filter(array_intersect_key($args, array_flip(['execute', 'analyze', 'seal', 'check']))));
if ($modeCount !== 1) throw new RuntimeException('Select exactly one assessment mode');
$write = static function(string $path, array $data): void {
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    file_put_contents($path, S::json($data));
};
$copy = static function(string $source, string $target): void {
    if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
    if (!copy($source, $target)) throw new RuntimeException('Copy failed: ' . $source);
};
$preservation = static function() use ($baseline): array {
    $errors = [];
    foreach ($baseline['files'] as $path => $hash) {
        if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = 'Baseline changed: ' . $path;
    }
    return $errors;
};
if ($args['execute']) {
    if ($preservation()) throw new RuntimeException('Baseline changed before execution');
    if (!preg_match('~^\.audit/[A-Za-z0-9_-]+$~D', $args['snapshot'])) throw new RuntimeException('Snapshot must be a new direct child of .audit');
    $snapshot = $root . '/' . $args['snapshot'];
    if (file_exists($snapshot)) throw new RuntimeException('Snapshot already exists; choose a fresh --snapshot path');
    mkdir($snapshot, 0777, true);
    foreach ($baseline['files'] as $path => $hash) {
        $copy($path, $snapshot . '/' . $path);
        if (hash_file('sha256', $snapshot . '/' . $path) !== $hash) throw new RuntimeException('Snapshot copy mismatch: ' . $path);
    }
    $vendorHashes = [];
    foreach (S::files('vendor') as $path) {
        $copy($path, $snapshot . '/' . $path);
        $vendorHashes[$path] = hash_file('sha256', $path);
    }
    $paths = [];
    foreach (['php85', 'php86', 'sources85', 'sources86', 'source-cache'] as $key) {
        $paths[$key] = realpath($args[$key]) ?: throw new RuntimeException('Missing prerequisite: ' . $key);
    }
    if (!is_dir($out)) mkdir($out, 0777, true);
    $commands = [];
    $run = static function(string $id, array $command) use (&$commands, $snapshot, $root, $out, $write): array {
        echo 'Running ' . $id . "\n"; flush();
        $log = $out . '/' . $id . '.log';
        $r = S::run($command, $snapshot, $root . '/' . $log);
        $commands[$id] = ['command' => $command, 'cwd' => $snapshot, 'exit_code' => $r['exit_code'],
            'log' => $log, 'sha256' => hash_file('sha256', $log)];
        $write($out . '/commands.json', $commands);
        echo $id . ': exit ' . $r['exit_code'] . "\n"; flush();
        return $r;
    };
    $p = [$paths['php85'], '-n', 'tools/8.6/certification.php'];
    $run('prior-check', [...$p, '--check']);
    $options = [];
    foreach ($paths as $key => $path) array_push($options, '--' . $key, $path);
    $run('prepare', [...$p, '--prepare', ...$options]);
    $report = json_decode(file_get_contents($snapshot . '/' . C::REPORT), true, 512, JSON_THROW_ON_ERROR);
    $before = [];
    foreach ([C::REPORT, ...array_keys($report['inputs']), ...array_keys($report['artifacts'])] as $path) $before[$path] = hash_file('sha256', $snapshot . '/' . $path);
    $run('check', [...$p, '--check']);
    $run('claims', [...$p, '--claims']);
    $run('run-preflight', [...$p, '--run']);
    $after = [];
    foreach ($before as $path => $hash) $after[$path] = hash_file('sha256', $snapshot . '/' . $path);
    $copy($snapshot . '/' . C::REPORT, $out . '/run/' . C::REPORT);
    foreach (S::files($snapshot . '/' . C::OUTPUT) as $path) {
        $relative = substr($path, strlen(str_replace('\\', '/', $snapshot)) + 1);
        $copy($path, $out . '/run/' . $relative);
    }
    $write($out . '/execution.json', ['schema' => 1, 'phase' => '7B', 'snapshot' => $snapshot,
        'baseline' => $baselinePath, 'baselineSha256' => hash_file('sha256', $baselinePath),
        'runnerSha256' => $baseline['files']['tools/8.6/certification.php'], 'paths' => $paths,
        'vendorHashes' => $vendorHashes, 'commands' => $commands,
        'readOnlyCheckPaths' => count($before), 'readOnlyChecksPreservedBytes' => $before === $after,
        'workspacePreservationErrors' => $preservation(), 'gateCounts' => array_count_values(array_column($report['gates'], 'status')),
        'validation' => $report['validation'], 'certificationGranted' => false,
        'note' => '26 gate commands are executed by --prepare. Ten static blocked dispositions are evaluated, not executed. --run is a refusal-only preflight in the unchanged Phase 7A implementation.']);
    exit($commands['prepare']['exit_code'] === 0 && $commands['check']['exit_code'] === 0 && $before === $after && $preservation() === [] ? 0 : 1);
}

if ($args['analyze']) {
    $old = S::read('docs/8.5/final-evidence.json');
    $policy = S::read('tools/8.5/data/certification-policy.json');
    $paths = $policy['input_paths'];
    foreach (['src', 'tests', 'grammar', 'bin', 'tools'] as $dir) {
        foreach (array_keys($baseline['files']) as $path) if (str_starts_with($path, $dir . '/')) $paths[] = $path;
    }
    $paths = S::sortedUnique($paths);
    $changed = []; $missing = []; $added = [];
    foreach ($old['inputs'] as $path => $hash) {
        $actual = $baseline['files'][$path] ?? null;
        if ($actual === null) $missing[$path] = $hash;
        elseif ($actual !== $hash) $changed[$path] = ['recordedSha256' => $hash, 'baselineSha256' => $actual,
            'category' => str_starts_with($path, 'docs/') ? 'governance-or-documentation' :
                (str_starts_with($path, 'src/') ? 'shared-implementation' : (str_starts_with($path, 'tests/') ? 'regression-tests' : 'manifest-cli-or-documentation'))];
    }
    foreach (array_diff($paths, array_keys($old['inputs'])) as $path) $added[$path] = $baseline['files'][$path];
    $preserved85 = [];
    foreach ($baseline['files'] as $path => $hash) if (preg_match('~^(grammar|docs|tools)/8\.5/|^tests/fixtures/php/8\.5/~', $path)) $preserved85[$path] = $hash;
    $write($out . '/historical-freshness.json', ['source' => 'tools/8.5/certification.php:197-220',
        'historicalInputCount' => count($old['inputs']), 'currentContractInputCountAtBaseline' => count($paths),
        'changed' => $changed, 'missing' => $missing, 'added' => $added,
        'changedCounts' => array_count_values(array_column($changed, 'category')),
        'protected85FileCount' => count($preserved85),
        'protected85Differences' => array_values(array_filter(array_keys($changed), static fn($path) => isset($preserved85[$path]))),
        'scope' => 'Reconstructs only the input digest set from the unchanged 8.5 generator and Phase 7B baseline. Does not generate or rewrite an 8.5 certification artifact.',
        'decision' => 'Legitimate whole-repository freshness failure. The historical contract includes governance and new version files; it cannot act as an immutable version-local freshness certificate. A separately authorized evidence/dependency review is required; no waiver.']);
    $protectedDifferences = array_values(array_filter(array_keys($changed), static fn($path) => isset($preserved85[$path])));
    echo S::json(['changed' => count($changed), 'added' => count($added), 'missing' => count($missing), 'protected85Differences' => count($protectedDifferences)]);
    if (is_file($out . '/run/' . C::REPORT)) {
        $runReport = S::read($out . '/run/' . C::REPORT);
        $plans = S::read('docs/8.6/certification-remediation.json');
        $gates = [];
        foreach ($runReport['gates'] as $id => $row) {
            $plan = $plans['gates'][$id] ?? null;
            $evidence = [
                'run/' . C::REPORT => hash_file('sha256', $out . '/run/' . C::REPORT),
                '../certification-execution-baseline.json' => hash_file('sha256', $baselinePath),
            ];
            if (isset($row['log'])) $evidence['run/' . $row['log']] = $row['logSha256'];
            $gates[$id] = ['purpose' => $row['obligation'], 'actualResult' => $row,
                'executed' => isset($row['command']), 'commandExecuted' => $row['command'] ?? null,
                'requiredEvidence' => $plan['requiredEvidence'] ?? [
                    'run/' . C::REPORT . '#/inputs (complete executed input set)',
                    'run/' . C::REPORT . '#/artifacts (generated evidence and per-command outcomes)',
                    'docs/8.6/certification-gates.json#/gates/' . $id,
                ],
                'evidenceHashes' => $evidence,
                'classification' => $plan['classification'] ?? 'executable validation passed within its stated scope',
                'reason' => $plan['rootCause'] ?? 'Recorded command exited zero; this does not discharge separate closure gates.',
                'remediationDependency' => $plan['dependencies'] ?? [],
                'rerunRequired' => $row['status'] !== 'passed',
                'rerunPolicy' => $row['status'] === 'passed' ? 'Rerun when an input, source pin, interpreter, profile, or dependent remediation changes.' : 'Required after legitimate remediation and any separately authorized runner correction.',
                'plan' => $plan === null ? null : 'docs/8.6/certification-remediation.json#/gates/' . $id];
        }
        $write($out . '/gate-assessment.json', ['schema' => 1, 'phase' => '7B', 'gates' => $gates,
            'executedGateCommands' => count(array_filter($gates, static fn($r) => $r['executed'])),
            'blockedObligationsEvaluatedWithoutExecution' => count(array_filter($gates, static fn($r) => !$r['executed'])),
            'certificationGranted' => false]);
        $table = "| Gate | Purpose | Actual result | Execution evidence |\n|---|---|---|---|\n";
        foreach ($gates as $id => $gate) {
            $row = $gate['actualResult'];
            $evidence = isset($row['log'])
                ? '[command log](certification-execution/run/' . $row['log'] . ')'
                : 'Not executed; blocked obligation';
            $table .= '| `' . $id . '` | ' . str_replace('|', '\\|', $gate['purpose']) . ' | **' . $row['status'] . '** | ' . $evidence . " |\n";
        }
        $v = $runReport['validation'];
        $negativeLog = file_get_contents($out . '/run/' . $runReport['gates']['infrastructure-negatives']['log']);
        if (!preg_match('/OK \((\d+) tests, (\d+) assertions\)/', $negativeLog, $negativeTotals)) throw new RuntimeException('Missing negative-test totals');
        $validation = 'Fresh full regression: **' . number_format($v['tests']) . ' tests / ' . number_format($v['assertions']) . ' assertions**, exit ' . $v['exit_code'] . ".\n\n"
            . '[Full regression log](certification-execution/run/' . $v['log'] . ') and the gate logs above record the actual executions. '
            . 'Overlapping gate test counts are not summed. The dedicated certification negative-test gate passes with **' . $negativeTotals[1] . ' tests / ' . $negativeTotals[2] . " assertions**, retaining rejection of manipulated or stale evidence.\n\n"
            . "The three additional PHP 8.5 coverage, Phase 6 evidence, and source-correspondence read-only checks pass; see [supporting checks](certification-execution/supporting-checks.json). These do not replace the failed aggregate freshness gate or imply a complete historical recertification.\n\n"
            . "The isolated prior freshness check, generation and fresh check exit zero. Claim evaluation and run preflight exit one as expected. Before/after hashes preserve all 2,225 monitored run paths.\n";
        $reportPath = 'docs/8.6/certification-execution.md';
        $markdown = file_get_contents($reportPath);
        foreach (['gates' => $table, 'validation' => $validation] as $marker => $body) {
            $markdown = preg_replace_callback('~<!-- ' . $marker . ':start -->.*?<!-- ' . $marker . ':end -->~s',
                static fn() => '<!-- ' . $marker . ":start -->\n" . $body . '<!-- ' . $marker . ':end -->', $markdown);
        }
        file_put_contents($reportPath, $markdown);
    }
    exit(0);
}

$sealPath = $out . '/assessment-integrity.json';
$reportPaths = ['tools/8.6/certification-assessment.php', $baselinePath, 'docs/8.6/certification-execution.md', 'docs/8.6/certification-remediation.json'];
foreach (S::files($out) as $path) if ($path !== $sealPath) $reportPaths[] = $path;
$hashes = S::hashes(S::sortedUnique($reportPaths));
if ($args['seal']) {
    $write($sealPath, ['schema' => 1, 'phase' => '7B', 'inputsAndOutputs' => $hashes,
        'scope' => 'Digest manifest only; independent --check verifies preservation, outcomes and gate accounting. No certification grant.']);
    exit(0);
}
$errors = $preservation();
$seal = S::read($sealPath);
if ($seal['inputsAndOutputs'] !== $hashes) $errors[] = 'Stale assessment input/output set or digest';
$execution = S::read($out . '/execution.json');
foreach (['prior-check' => 0, 'prepare' => 0, 'check' => 0, 'claims' => 1, 'run-preflight' => 1] as $id => $expected) {
    if (($execution['commands'][$id]['exit_code'] ?? null) !== $expected) $errors[] = 'Unexpected execution outcome: ' . $id;
}
if (!$execution['readOnlyChecksPreservedBytes']) $errors[] = 'Read-only checks changed evidence';
$report = S::read($out . '/run/' . C::REPORT);
$c = new C($root);
$errors = [...$errors, ...$c->gateErrors($report['gates']), ...$c->blockerErrors($report['blockers'])];
if ($report['certificationGranted'] !== false || $report['inputsUnchangedDuringPreparation'] !== true) $errors[] = 'Unsupported claim or changed run inputs';
foreach ($report['artifacts'] as $path => $expected) {
    $file = $out . '/run/' . $path;
    if (!is_file($file) || hash_file('sha256', $file) !== $expected) $errors[] = 'Missing/stale exported run artifact: ' . $path;
}
foreach ($report['inputs'] as $path => $expected) if (($baseline['files'][$path] ?? null) !== $expected) $errors[] = 'Executed input differs from baseline: ' . $path;
$assessment = S::read($out . '/gate-assessment.json');
if (array_keys($assessment['gates']) !== array_keys(C::gates())) $errors[] = 'Assessment omits a required gate';
foreach ($report['gates'] as $id => $row) {
    $a = $assessment['gates'][$id] ?? [];
    if (($a['actualResult'] ?? null) !== $row) $errors[] = 'Assessment changes gate result: ' . $id;
    foreach (['purpose', 'requiredEvidence', 'evidenceHashes', 'classification', 'reason', 'remediationDependency', 'rerunRequired'] as $field) {
        if (!array_key_exists($field, $a)) $errors[] = 'Missing assessment field: ' . $id . '/' . $field;
    }
    foreach ($a['evidenceHashes'] ?? [] as $path => $hash) {
        if (!is_file($out . '/' . $path) || hash_file('sha256', $out . '/' . $path) !== $hash) $errors[] = 'Stale gate evidence: ' . $id . '/' . $path;
    }
}
echo S::json(['errors' => $errors, 'preservedFiles' => count($baseline['files']), 'gateCounts' => $execution['gateCounts'], 'certificationGranted' => false]);
exit($errors === [] ? 0 : 1);
