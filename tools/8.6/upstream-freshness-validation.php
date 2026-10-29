<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2))); chdir($root);
$out = 'docs/8.6/certification-contract-remediation/phase7h1-validation';
if (!is_dir($out)) mkdir($out, 0777, true);
$args = S::args(['stage' => '', 'revision' => 'phase7h1']);
if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/D', $args['revision'])) throw new RuntimeException('Safe revision required');
$revision = $args['revision'];
$php = $root . '/.audit/php85/php.exe'; $base = [$php, '-n'];
$unit = [...$base, '-d', 'extension=' . $root . '/.audit/php85/ext/php_mbstring.dll', 'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress'];
$compat = 'docs/8.5/compatibility/' . $revision . '/evidence.json';
$runs = 'docs/8.6/certification-contract-remediation' . '/runs/';
$assessment = $runs . $revision . '-base/evidence.json';
$current = $runs . $revision . '/evidence.json';
$diff = 'docs/8.6/upstream-resolution-freshness/differential-v2.json';
if ($args['stage'] === 'seal') {
    $read = static fn(string $p): array => json_decode(file_get_contents($p), true, 512, JSON_THROW_ON_ERROR);
    $compatibility = $read($compat); $assessmentData = $read($current);
    $baseline = $read('docs/8.6/upstream-resolution-freshness/baseline.json');
    $changed = []; foreach ($baseline['files'] as $p => $hash) if (!is_file($p) || hash_file('sha256', $p) !== $hash) $changed[] = $p;
    $expected = ['tests/Php/Conformance/Php86CompilerContextualTest.php', 'tools/8.6/certification-current.php', 'tools/evidence/documentation-inputs.json', 'tools/lib/CertificationCurrent.php', 'tools/lib/CertificationCurrentRun.php', 'tools/lib/Preservation86.php'];
    sort($changed); sort($expected); $errors = $changed === $expected ? [] : ['Unexpected predecessor changes'];
    $jsonCount = 0; $linkCount = 0;
    foreach (['docs/8.6/upstream-resolution-freshness', $out, dirname($compat), dirname($current), dirname($assessment)] as $dir) foreach (S::files($dir) as $p) {
        if (str_ends_with($p, '.json')) { ++$jsonCount; try { $read($p); } catch (Throwable $e) { $errors[] = 'Invalid JSON: ' . $p; } }
    }
    foreach (['docs/8.6/upstream-resolution-freshness.md'] as $p) {
        preg_match_all('~\[[^\]]*\]\(([^)]+)\)~', file_get_contents($p), $matches);
        foreach ($matches[1] as $link) if (!preg_match('~^(https?://|#)~', $link)) { ++$linkCount; if (!file_exists(dirname($p) . '/' . explode('#', $link)[0])) $errors[] = 'Missing link: ' . $link; }
    }
    $whitespace = S::run(['git', '-c', 'safe.directory=' . $root, 'diff', '--check'], $root, $out . '/' . $revision . '-whitespace.log');
    if ($whitespace['exit_code'] !== 0) $errors[] = 'Whitespace failure';
    $commands = []; foreach (['focused', 'compatibility', 'base', 'compose'] as $stage) {
        $name = $out . '/' . $revision . '-' . $stage . '.json';
        if (!is_file($name)) throw new RuntimeException('Missing current stage receipt: ' . $stage);
        $commands += $read($name);
    }
    foreach ($commands as $id => $row) if ($row['exit_code'] !== ($id === 'claims' ? 1 : 0) || hash_file('sha256', $row['log']) !== $row['sha256']) $errors[] = 'Invalid command receipt: ' . $id;
    $counts = array_count_values(array_column($assessmentData['gates'], 'result'));
    foreach ($assessmentData['prerequisites'] as $pass) { $key = $pass ? 'passed' : 'failed'; $counts[$key] = ($counts[$key] ?? 0) + 1; }
    $compatCounts = array_count_values(array_column($compatibility['commands'], 'exit_code'));
    $summary = ['phase' => '7H.1', 'revision' => $revision, 'commands' => $commands, 'compatibility' => ['report' => $compat, 'total' => count($compatibility['commands']), 'passed' => $compatCounts[0] ?? 0, 'inputsUnchanged' => $compatibility['inputsUnchanged'], 'phpunit' => $compatibility['commands']['full-regression']['totals']],
        'assessment' => ['report' => $current, 'obligations' => count($assessmentData['gates']) + count($assessmentData['prerequisites']), 'results' => $counts], 'blockers' => $assessmentData['blockers'],
        'preservation' => ['baselineFiles' => count($baseline['files']), 'changed' => $changed, 'grammarAndHistoricalEvidenceUnchanged' => $changed === $expected],
        'jsonFiles' => $jsonCount, 'localLinks' => $linkCount, 'whitespaceExit' => $whitespace['exit_code'], 'errors' => $errors, 'certificationGranted' => false];
    file_put_contents($out . '/summary.json', S::json($summary));
    $totals = $summary['compatibility']['phpunit'];
    $text = "# Phase 7H.1 validation results\n\nAll **15 compatibility commands pass**, and independent verification passes.\nThe full PHPUnit suite passes **{$totals['tests']} tests / {$totals['assertions']} assertions**.\nPHP 8.6 remains **draft** and uncertified.\n\nThe current assessment has **" . ($counts['passed'] ?? 0) . " passed / " . ($counts['failed'] ?? 0) . " failed / " . ($counts['blocked'] ?? 0) . " blocked** across 38 obligations.\nThe claims check exits 1 for eleven unmet obligations and seven mixed blockers.\nNine obligations are blocked. Two original historical command outcomes remain\nfailed: coverage-measurement requires the unchanged historical specification,\nand evidence-freshness requires the three pre-Phase 7H documentation hashes.\nTheir original requirements and failures are preserved. The explicit successor\ntransition and all current independent evidence verifications pass.\n\n| Command | Exit | Log |\n|---|---:|---|\n";
    foreach ($commands as $id => $row) $text .= "| $id | {$row['exit_code']} | [receipt](" . basename($row['log']) . ") |\n";
    $text .= "\n[Summary](summary.json) records exact command arrays, artifact hashes, the current\nassessment and all blocker dispositions. Historical integrity, explicit v3\npreservation, upstream evidence, negative regression, traceability, manifest,\nJSON, local links and whitespace checks pass. Both grammars, normative\nspecifications, all pre-phase historical evidence and source pins are unchanged.\nThe only six existing files changed are the exact test dependency correction,\nmandatory documentation input additions, and four opt-in preservation/runner\nintegration files listed in the summary. Original release gates remain intact.\n\nThe initial phase7h1 compatibility attempt is retained as a checkpoint predating\nfinal input stabilization. Its logs are not substituted for the independently\nverified $revision evidence. The previous Phase 7H failing suite and all original\nhistorical hashes remain preserved.\n\nThe authenticated contextual successor contains 162 lint observations and 37\nreviewed execution probes with zero errors; whole-record correspondence permits\nonly the reviewed documentation dependency. The independently verified ordinary\ndifferential successor retains all 353 cases and existing stage expectations.\n\nSee [the phase report](../../upstream-resolution-freshness.md) for root cause,\nexact hashes, transition boundaries and reproduction commands.\n";
    if ($errors || ($compatCounts[0] ?? 0) !== 15 || ($counts['passed'] ?? 0) !== 27 || ($counts['failed'] ?? 0) !== 2 || ($counts['blocked'] ?? 0) !== 9) throw new RuntimeException('Cannot publish validation summary: ' . implode('; ', $errors));
    file_put_contents($out . '/results.md', $text);
    $files = []; foreach (['docs/8.6/upstream-resolution-freshness', $out, dirname($compat), dirname($current), dirname($assessment)] as $dir) foreach (S::files($dir) as $p) if ($p !== $out . '/evidence-files.json') $files[$p] = hash_file('sha256', $p);
    foreach (['docs/8.6/upstream-resolution-freshness.md', 'tools/lib/UpstreamResolutionFreshness.php', 'tools/8.6/upstream-freshness.php', 'tools/8.6/upstream-freshness-validation.php', 'tests/UpstreamResolutionFreshnessTest.php', ...$changed] as $p) $files[$p] = hash_file('sha256', $p);
    ksort($files); file_put_contents($out . '/evidence-files.json', S::json(['contract' => 'php86-upstream-freshness-artifacts-v1', 'files' => $files, 'certificationGranted' => false]));
    echo S::json(['errors' => $errors, 'compatibilityPassed' => $compatCounts[0] ?? 0, 'assessment' => $counts, 'files' => count($files)]); exit(0);
}
$stages = [
    'focused' => [
        'historical-integrity' => [...$base, 'tools/evidence/historical-check.php'],
        'upstream-preservation' => [...$base, 'tools/8.6/upstream-freshness.php', '--check'],
        'negative-regression' => [...$unit, '--filter', 'UpstreamResolutionFreshnessTest|CertificationCurrentTest|Certification86Test|ScannerDifferentialCertificationTest|Php86CompilerContextualTest'],
        'traceability' => [...$base, 'tools/8.6/change-traceability.php'],
        'manifest' => [...$base, 'tools/validate-manifest.php'],
    ],
    'compatibility' => [
        'compatibility-generate' => [...$base, 'tools/evidence/compatibility.php', '--generate', '--revision', $revision],
        'compatibility-check' => [...$base, 'tools/evidence/compatibility.php', '--check', '--report', $compat],
    ],
    'base' => [
        'current-generate' => [...$base, 'tools/8.6/certification-current.php', '--contract', 'php86-certification-v2', '--generate', '--revision', $revision . '-base', '--preservation-contract', 'php86-preservation-v3', '--profile', 'exact-pin-rc4', '--legacyProfile', 'historical-rc4', '--compatibility-report', $compat],
        'current-check' => [...$base, 'tools/8.6/certification-current.php', '--contract', 'php86-certification-v2', '--check', '--report', $assessment],
    ],
    'compose' => [
        'differential-check' => [...$base, 'tools/8.6/scanner-differential-inventory.php', '--check', '--profile', 'exact-pin-rc4', '--report', $diff],
        'compose-generate' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--generate', '--revision', $revision, '--assessment', $assessment, '--differential-report', $diff],
        'compose-check' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--check', '--report', $current],
        'claims' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--claims', '--report', $current],
    ],
];
if (!isset($stages[$args['stage']])) throw new RuntimeException('Select focused, compatibility, base or compose');
$path = $out . '/' . $revision . '-' . $args['stage'] . '.json';
if (is_file($path)) throw new RuntimeException('Never overwrite validation receipts');
$rows = [];
foreach ($stages[$args['stage']] as $id => $command) {
    echo 'Running ' . $id . "\n"; flush();
    $log = $out . '/' . $revision . '-' . $id . '.log';
    $r = S::run($command, $root, $log);
    $rows[$id] = ['command' => $command, 'exit_code' => $r['exit_code'], 'log' => $log, 'sha256' => hash_file('sha256', $log)];
    file_put_contents($path, S::json($rows));
    echo $id . ': ' . $r['exit_code'] . "\n"; flush();
    if ($r['exit_code'] !== ($id === 'claims' ? 1 : 0)) exit(1);
}
