<?php
declare(strict_types=1);

// Phase 8A research acquisition. Never changes an active source profile.
$root = dirname(__DIR__, 2);
chdir($root);
$out = 'docs/8.6/rc7-source-reconciliation';
if (!is_dir($out)) mkdir($out, 0777, true);
require_once __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$rc4 = 'f23b4c222cebe33be93ee8ecaaa14390c5507a08';
$rc7 = 'b63cd83da3279445ca4a913f03186141f5c3466e';
if (in_array($argv[1] ?? '', ['--seal', '--check'], true)) {
    $errors = []; $files = [];
    $required = ['source-check.json', 'tree-inventory.json', 'fix-status.json', 'fix-pr.body', 'probes-8.5.json', 'probes-8.6.json', 'proposal-controls.json', 'upstream-tests.json', 'validation.json', 'baseline.json'];
    foreach ($required as $name) if (!is_file("$out/$name")) $errors[] = "Missing $name";
    if ($errors) { echo S::json($errors); exit(1); }
    $baseline = S::read("$out/baseline.json");
    foreach ($baseline['files'] as $path => $hash) if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = "Historical input changed: $path";
    $lock = S::read('tools/8.6/certification-source-lock.json');
    $source = S::read("$out/source-check.json");
    foreach ($source['files'] as $path => $hashes) {
        foreach (['rc4', 'rc7'] as $profile) if (hash_file('sha256', ".audit/phase8a/$profile/$path") !== $hashes[$profile]) $errors[] = "$profile source changed: $path";
        if (isset($lock['files'][$path]) && $hashes['rc4'] !== $lock['files'][$path]) $errors[] = "RC4 lock mismatch: $path";
        if (hash_file('sha256', ".audit/phase8a/php-8.6.0RC7/$path") !== $hashes['archive']) $errors[] = "Archive source changed: $path";
        if ($path !== 'NEWS' && $hashes['archive'] !== $hashes['rc7']) $errors[] = "Archive/Git mismatch: $path";
    }
    $news = file_get_contents('.audit/phase8a/rc7/NEWS');
    $archiveNews = file_get_contents('.audit/phase8a/php-8.6.0RC7/NEWS');
    $offset = strpos($news, '12 Nov 2026, PHP 8.6.0RC7');
    if ($archiveNews !== substr($news, 0, $offset) . "12 Nov 2026, PHP 8.6.0RC7\n\n\n" . substr($news, $offset)) $errors[] = 'Unexpected NEWS packaging difference';
    if (hash_file('sha256', '.audit/phase8a/php-8.6.0RC7.tar.gz') !== '68b753e1d45e1417c6874008500de66102963cf0791e8c541211e9386318722e') $errors[] = 'Source archive changed';
    foreach (['rc4' => $rc4, 'rc7' => $rc7] as $profile => $commit) {
        $r = S::run(['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git', 'ls-tree', '-r', $commit], $root);
        if ($r['exit_code'] !== 0 || $r['output'] !== file_get_contents("$out/tree-$profile.log")) $errors[] = 'Git tree mismatch: ' . $profile;
    }
    $diff = S::run(['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git', 'diff', '--name-status', $rc4, $rc7], $root);
    if ($diff['exit_code'] !== 0 || $diff['output'] !== file_get_contents("$out/changed-files.log")) $errors[] = 'Changed-path inventory differs from Git';
    $changedPaths = [];
    foreach (explode("\n", trim($diff['output'])) as $line) { $parts = explode("\t", $line); $changedPaths[] = end($parts); }
    $review = S::read("$out/review-ledger.json"); $reviewPaths = array_column($review['files'], 'path');
    sort($changedPaths); sort($reviewPaths);
    if ($changedPaths !== $reviewPaths) $errors[] = 'Incomplete risk review path membership';
    $inventory = S::read("$out/tree-inventory.json"); $inventoryPaths = array_keys($inventory['changes']); sort($inventoryPaths);
    if ($inventoryPaths !== $changedPaths) $errors[] = 'Tree inventory differs from Git diff';
    $validation = S::read("$out/validation.json");
    foreach (['manifest', 'traceability', 'historical-integrity', 'compatibility-check', 'certification-check', 'certification-claims', 'phpunit'] as $id) {
        if (!isset($validation[$id])) { $errors[] = 'Missing validation command: ' . $id; continue; }
        $r = $validation[$id];
        if (hash_file('sha256', $r['log']) !== $r['sha256']) $errors[] = 'Validation log changed: ' . $id;
        $expectedExit = in_array($id, ['manifest', 'traceability', 'phpunit'], true) ? 0 : 1;
        if ($r['exitCode'] !== $expectedExit) $errors[] = 'Unexpected validation result: ' . $id;
    }
    foreach (['8.5', '8.6'] as $version) {
        $p = S::read("$out/probes-$version.json");
        foreach ($p['inputs'] + $p['binaryHashes'] as $path => $hash) if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = 'Probe input changed: ' . $path;
        $expected = $version === '8.6' ? ['reconciliation/hook-retained:lint'] : [];
        if ($p['mismatches'] !== $expected) $errors[] = 'Unreviewed probe discrepancy: ' . $version;
    }
    $jsonCount = 0;
    foreach (S::files($out) as $path) {
        if (str_ends_with($path, '.json')) { S::read($path); ++$jsonCount; }
        if (!in_array(basename($path), ['artifacts.json', 'check.json'], true)) $files[$path] = hash_file('sha256', $path);
    }
    foreach (['tools/8.6/rc7-source-reconciliation.php', 'docs/8.6/rc7-source-reconciliation.md'] as $path) $files[$path] = hash_file('sha256', $path);
    foreach (['tools/8.6/rc7-source-reconciliation.php', 'docs/8.6/rc7-source-reconciliation.md', "$out/review-ledger.json", "$out/candidate-source-profile.json"] as $path) {
        if (preg_match('/[\t ]+$/m', file_get_contents($path))) $errors[] = 'Trailing whitespace: ' . $path;
    }
    $report = file_get_contents('docs/8.6/rc7-source-reconciliation.md');
    preg_match_all('~\[[^\]]*\]\(([^)]+)\)~', $report, $links);
    foreach ($links[1] as $link) {
        if (($argv[1] ?? '') === '--seal' && in_array($link, ['rc7-source-reconciliation/artifacts.json', 'rc7-source-reconciliation/check.json'], true)) continue;
        if (!preg_match('~^(https?://|#)~', $link) && !file_exists('docs/8.6/' . explode('#', $link)[0])) $errors[] = 'Missing report link: ' . $link;
    }
    ksort($files);
    if (($argv[1] ?? '') === '--seal') {
        if (!$errors) file_put_contents("$out/artifacts.json", S::json(['contract' => 'php86-rc7-source-reconciliation-v1', 'files' => $files, 'certificationGranted' => false]));
    } else {
        $sealed = S::read("$out/artifacts.json");
        if ($sealed['files'] !== $files) $errors[] = 'Artifact membership or bytes changed';
    }
    $result = ['errors' => $errors, 'preservedFiles' => count($baseline['files']), 'sourceFiles' => count($source['files']), 'jsonFiles' => $jsonCount, 'artifactFiles' => count($files), 'certificationGranted' => false];
    file_put_contents("$out/check.json", S::json($result)); echo S::json($result); exit($errors ? 1 : 0);
}
if (($argv[1] ?? '') === '--proposal-controls') {
    $cases = [
        'ordinary-dimension' => '($a = [1])[0] = 2; echo json_encode($a);',
        'ordinary-property' => '($a = new stdClass)->p = 1; echo json_encode($a);',
        'compound' => '($a = [1])[0] += 2;',
        'increment' => '($a = [1])[0]++;',
        'unset' => 'unset(($a = [1])[0]);',
        'reference-bind' => '$r = &($a = [1])[0];',
        'reference-argument-dimension' => 'function f(&$x) {} f(($a = [1])[0]);',
        'reference-assignment-write' => '$b = [1]; ($a =& $b)[0] = 2; echo json_encode([$a, $b]);',
        'destructuring-reference-write' => '$arr = [[1]]; ([&$x] = $arr)[0] = 5; echo json_encode([$arr, $x]);',
        'destructuring-reference-argument' => 'function f(&$x) {$x[] = 9;} $arr = [[1]]; f([&$x] = $arr); echo json_encode($arr);',
        'reference-assignment-argument' => 'function f(&$x) {$x[] = 9;} $b = [1]; f($a =& $b); echo json_encode([$a, $b]);',
        'foreach-reference-assignment' => '$b = [1]; foreach (($a =& $b) as &$v) {$v = 3;} echo json_encode([$a, $b]);',
    ];
    $rows = []; $dir = "$out/probes"; if (!is_dir($dir)) mkdir($dir, 0777, true);
    foreach ($cases as $id => $code) {
        $path = "$dir/$id.php"; file_put_contents($path, "<?php\n$code\n");
        foreach (['8.5.10' => '.audit/php85/php.exe', '8.6.0RC7' => '.audit/phase8a/php86rc7/php.exe'] as $version => $php) {
            foreach (['lint', 'execution'] as $stage) {
                $command = [$php, '-n', '-d', 'display_errors=1', '-d', 'log_errors=0', '-d', 'max_execution_time=5', ...($stage === 'lint' ? ['-l'] : []), $path];
                $r = S::run($command, $root);
                $rows[] = ['id' => $id, 'fixture' => $path, 'sha256' => hash_file('sha256', $path), 'version' => $version, 'stage' => $stage, 'command' => $command, 'exitCode' => $r['exit_code'], 'output' => $r['output']];
            }
        }
    }
    file_put_contents("$out/proposal-controls.json", S::json(['profiles' => 'Official distributions; proposed patch not applied', 'observations' => $rows]));
    echo count($rows) . " observations\n"; exit;
}
if (($argv[1] ?? '') === '--upstream-tests') {
    $source = $root . '/.audit/phase8a/php-8.6.0RC7';
    $php = $root . '/.audit/phase8a/php86rc7/php.exe';
    $tests = ['Zend/tests/array_map_foreach_optimization_function_name.phpt', 'Zend/tests/closures/gh23911.phpt',
        'Zend/tests/partial_application/gh25007.phpt', 'Zend/tests/partial_application/static_pfa_004.phpt',
        'Zend/tests/constexpr/new_named_params_duplicate_leak.phpt', 'Zend/tests/assert/expect_final_abstract_modifiers.phpt',
        'Zend/tests/assert/expect_readonly_anon_class.phpt', 'Zend/tests/attributes/override/constants/anon_failure.phpt'];
    for ($i = 1; $i <= 6; ++$i) $tests[] = sprintf('Zend/tests/first_class_callable/constexpr/trait_scope_%03d.phpt', $i);
    $hashes = []; foreach ($tests as $test) $hashes[$test] = hash_file('sha256', "$source/$test");
    putenv('NO_INTERACTION=1'); putenv('REPORT_EXIT_STATUS=1');
    $command = [$php, '-n', 'run-tests.php', '-q', '-n', '-p', $php, ...$tests];
    $log = "$out/upstream-tests.log";
    $r = S::run($command, $source, $root . '/' . $log);
    file_put_contents("$out/upstream-tests.json", S::json(['command' => $command, 'cwd' => $source, 'exitCode' => $r['exit_code'], 'log' => $log, 'sha256' => hash_file('sha256', $log), 'testHashes' => $hashes]));
    echo $r['output']; exit($r['exit_code']);
}
if (($argv[1] ?? '') === '--inventory-summary') {
    $j = S::read("$out/tree-inventory.json");
    echo S::json(['treeCounts' => $j['treeCounts'], 'changed' => count($j['changes']), 'archiveMatched' => $j['archiveCorrespondence']['matched'], 'archiveMissing' => $j['archiveCorrespondence']['missing'], 'archiveDifferent' => array_keys($j['archiveCorrespondence']['different'])]); exit;
}
if (($argv[1] ?? '') === '--validate') {
    $php = '.audit/php85/php.exe';
    $base = [$php, '-n'];
    $unit = [...$base, '-d', 'extension=' . $root . '/.audit/php85/ext/php_mbstring.dll', 'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress'];
    $report = 'docs/8.6/certification-contract-remediation/runs/phase7h1-audited/evidence.json';
    $commands = [
        'manifest' => [...$base, 'tools/validate-manifest.php'],
        'traceability' => [...$base, 'tools/8.6/change-traceability.php'],
        'historical-integrity' => [...$base, 'tools/evidence/historical-check.php'],
        'compatibility-check' => [...$base, 'tools/evidence/compatibility.php', '--check', '--report', 'docs/8.5/compatibility/phase7h1-audited/evidence.json'],
        'certification-check' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--check', '--report', $report],
        'certification-claims' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--claims', '--report', $report],
        'phpunit' => $unit,
    ];
    $rows = [];
    foreach ($commands as $id => $command) {
        $log = "$out/validation-$id.log";
        echo "Running $id\n"; flush();
        $r = S::run($command, $root, $log);
        $rows[$id] = ['command' => $command, 'exitCode' => $r['exit_code'], 'log' => $log, 'sha256' => hash_file('sha256', $log)];
        file_put_contents("$out/validation.json", S::json($rows));
        echo "$id: {$r['exit_code']}\n"; flush();
    }
    exit;
}
if (($argv[1] ?? '') === '--php85') {
    $url = 'https://downloads.php.net/~windows/releases/archives/php-8.5.10-nts-Win32-vs17-x64.zip';
    $path = '.audit/phase8a/php85.zip';
    if (!is_file($path)) {
        $f = fopen($path, 'wb'); $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_FILE => $f, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 180]);
        $ok = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); fclose($f);
        if (!$ok || $status !== 200) throw new RuntimeException('PHP 8.5 archive unavailable');
    }
    $zip = new ZipArchive(); if ($zip->open($path) !== true) throw new RuntimeException('Invalid PHP 8.5 archive');
    $expected = S::read('tools/8.6/context-source-lock.json')['executables']['8.5'];
    if (hash('sha256', $zip->getFromName('php.exe')) !== $expected) throw new RuntimeException('PHP 8.5 executable does not match historical oracle');
    if (!is_dir('.audit/php85')) $zip->extractTo('.audit/php85'); $zip->close();
    file_put_contents("$out/php85-distribution.json", S::json(['url' => $url, 'archiveSha256' => hash_file('sha256', $path), 'executableSha256' => hash_file('sha256', '.audit/php85/php.exe'), 'historicalExpectedExecutableSha256' => $expected]));
    echo "Historical PHP 8.5.10 distribution restored and verified\n"; exit;
}
if (($argv[1] ?? '') === '--inventory') {
    $trees = [];
    foreach (['rc4', 'rc7'] as $profile) {
        foreach (explode("\n", trim(file_get_contents("$out/tree-$profile.log"))) as $line) {
            if (preg_match('/^(\d+) blob ([a-f0-9]{40})\t(.+)$/D', $line, $m)) $trees[$profile][$m[3]] = ['mode' => $m[1], 'oid' => $m[2]];
        }
    }
    $paths = array_unique([...array_keys($trees['rc4']), ...array_keys($trees['rc7'])]); sort($paths);
    $selected = S::read("$out/source-check.json")['files'];
    $changed = []; $archive = ['scope' => 'Every changed RC7 path plus the 235-file historical source inventory; unchanged unrelated files are not individually compared', 'matched' => 0, 'notIndividuallyCompared' => 0, 'missing' => [], 'different' => []];
    foreach ($paths as $path) {
        $before = $trees['rc4'][$path] ?? null; $after = $trees['rc7'][$path] ?? null;
        if ($before !== $after) $changed[$path] = ['rc4' => $before, 'rc7' => $after];
        if ($after === null) continue;
        if ($before === $after && !isset($selected[$path])) { ++$archive['notIndividuallyCompared']; continue; }
        $file = '.audit/phase8a/php-8.6.0RC7/' . $path;
        if (!is_file($file)) { $archive['missing'][] = $path; continue; }
        $bytes = file_get_contents($file); $oid = sha1('blob ' . strlen($bytes) . "\0" . $bytes);
        if ($oid === $after['oid']) ++$archive['matched']; else $archive['different'][$path] = ['expectedGitBlob' => $after['oid'], 'archiveGitBlob' => $oid, 'sha256' => hash('sha256', $bytes)];
    }
    file_put_contents("$out/tree-inventory.json", S::json(['rc4' => $rc4, 'rc7' => $rc7, 'treeCounts' => array_map('count', $trees), 'changes' => $changed, 'archiveCorrespondence' => $archive]));
    echo S::json(['changed' => count($changed), 'archiveMatched' => $archive['matched'], 'archiveMissing' => count($archive['missing']), 'archiveDifferent' => count($archive['different'])]); exit;
}
if (($argv[1] ?? '') === '--summary') {
    $p = S::read("$out/probes.json");
    foreach ($p['observations'] as $row) if (!$row['matches']) echo S::json($row);
    echo S::json(['total' => count($p['observations']), 'groups' => array_count_values(array_map(static fn($r) => explode('/', $r['id'])[0], $p['observations']))]); exit;
}
if (($argv[1] ?? '') === '--fix') {
    $git = ['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git'];
    $fix = '5089bcc83c8193e6a965dd1579767b1d503401ba';
    $branch = 'ac377d55a9e7af2e1dd16446a9a1a7a7d6bd300e';
    $rows = [];
    foreach (['rc7' => $rc7, 'branchSnapshot' => $branch] as $id => $commit) {
        $command = [...$git, 'merge-base', '--is-ancestor', $fix, $commit];
        $r = S::run($command, $root);
        $source = S::run([...$git, 'show', "$commit:Zend/zend_compile.c"], $root);
        $start = strpos($source['output'], 'static zend_op *zend_compile_var_inner');
        $end = strpos($source['output'], "\n}", $start);
        $body = substr($source['output'], $start, $end - $start + 2);
        $rows[$id] = ['commit' => $commit, 'ancestryCommand' => $command, 'ancestryExit' => $r['exit_code'], 'function' => $body,
            'containsProposedGuard' => str_contains($body, 'by_ref || (type != BP_VAR_W')];
    }
    $pr = S::read("$out/fix-pr.body");
    if ($pr['head']['sha'] !== $fix || $pr['base']['ref'] !== 'PHP-8.6') throw new RuntimeException('Unexpected proposed-fix PR identity');
    file_put_contents("$out/fix-status.json", S::json(['proposedCommit' => $fix, 'officialSnapshots' => $rows,
        'issue' => S::read("$out/issue24792.body")['state'], 'associatedPulls' => S::read("$out/fix-pulls.body"),
        'submission' => ['url' => $pr['html_url'], 'head' => $pr['head']['sha'], 'targetBranch' => $pr['base']['ref'], 'state' => $pr['state'], 'merged' => $pr['merged'], 'officialMergedCommit' => $pr['merged'] ? $pr['merge_commit_sha'] : null],
        'associationCaveat' => 'Empty commit-association response does not negate PR25046 found by repository-wide search.',
        'final860Schedule' => 'Unestablished; proposed NEWS entry is 8.6.1, not a final-release scheduling commitment.',
        'proposedPatchExecutedLocally' => false, 'certificationGranted' => false]));
    echo S::json($rows); exit;
}
if (($argv[1] ?? '') === '--probes') {
    $version = $argv[2] ?? '8.6';
    if (!in_array($version, ['8.5', '8.6'], true)) throw new RuntimeException('Unsupported probe version');
    $binaryDir = $version === '8.6' ? '.audit/phase8a/php86rc7' : '.audit/php85';
    $php = "$binaryDir/php.exe";
    $zipPath = $version === '8.6' ? '.audit/phase8a/php-8.6.0RC7-nts-Win32-vs18-x64.zip' : '.audit/phase8a/php85.zip';
    if ($version === '8.6' && hash_file('sha256', $zipPath) !== '03da81bc29974c42eb0678c2c4cf94e52c484d1dfa7a61b3c36d98eb23350588') throw new RuntimeException('Unauthenticated distribution');
    if ($version === '8.5' && hash_file('sha256', $php) !== S::read('tools/8.6/context-source-lock.json')['executables']['8.5']) throw new RuntimeException('Unauthenticated PHP 8.5');
    $zip = new ZipArchive(); $zip->open($zipPath); $binaryHashes = [];
    for ($i = 0; $i < $zip->numFiles; ++$i) {
        $name = $zip->getNameIndex($i);
        if (!preg_match('/\.(exe|dll)$/i', $name)) continue;
        $path = $binaryDir . '/' . $name;
        if (hash_file('sha256', $path) !== hash('sha256', $zip->getFromIndex($i))) throw new RuntimeException('Distribution file mismatch: ' . $name);
        $binaryHashes[$path] = hash_file('sha256', $path);
    }
    $zip->close();
    $base = [$php, '-n', '-d', 'display_errors=1', '-d', 'log_errors=0', '-d', 'error_reporting=-1', '-d', 'max_execution_time=5'];
    $rows = []; $inputs = []; $mismatches = [];
    $boundariesOnly = ($argv[3] ?? '') === '--boundaries-only';
    if ($boundariesOnly) {
        $old = S::read("$out/probes-$version.json");
        $checkpoint = "$out/probes-$version-before-boundary-correction.json";
        if (!is_file($checkpoint)) copy("$out/probes-$version.json", $checkpoint);
        $rows = array_values(array_filter($old['observations'], static fn($r) => !str_starts_with($r['id'], 'boundary/')));
        $inputs = $old['inputs'];
        $mismatches = array_values(array_filter($old['mismatches'], static fn($id) => !str_starts_with($id, 'boundary/')));
    }
    $run = static function (string $id, string $path, string $stage, ?bool $expected, ?string $diagnostic = null) use (&$rows, &$inputs, &$mismatches, $base, $root): void {
        $command = [...$base, ...($stage === 'lint' ? ['-l'] : []), $path];
        $result = S::run($command, $root); $inputs[$path] = hash_file('sha256', $path);
        $matches = ($expected === null || (($result['exit_code'] === 0) === $expected)) && ($diagnostic === null || $diagnostic === '' || str_contains($result['output'], $diagnostic));
        $rows[] = ['id' => $id, 'fixture' => $path, 'stage' => $stage, 'command' => $command, 'exitCode' => $result['exit_code'], 'output' => $result['output'], 'historicalExpectedAcceptance' => $expected, 'expectedDiagnostic' => $diagnostic, 'matches' => $matches];
        if (!$matches) $mismatches[] = "$id:$stage";
    };
    foreach ($boundariesOnly ? [] : ['pfa', 'context', 'reconciliation', 'exact-pin-reconciliation', 'deprecation'] as $group) {
        $manifest = "tests/fixtures/php/8.6/$group-cases.json";
        $inputs[$manifest] = hash_file('sha256', $manifest); $data = S::read($manifest);
        foreach ($data['cases'] ?? $data as $case) {
            $id = "$group/{$case['id']}";
            if ($group === 'pfa') $run($id, $case['fixture'], 'lint', $case['lint' . str_replace('.', '', $version)], $version === '8.6' ? $case['diagnostic86'] : null);
            elseif ($group === 'context') $run($id, $case['fixture'], $case['stage'], $case['accept' . str_replace('.', '', $version)], $version === '8.6' ? $case['diagnostic86'] : null);
            elseif (str_ends_with($group, 'reconciliation')) {
                $run($id, $case['fixture'], 'lint', $case['expect'][$version]['lint']['exit'] === 0, $case['expect'][$version]['lint']['contains'] ?? null);
                if (($case['executeReviewed'] ?? false) && isset($case['expect'][$version]['execution'])) $run($id, $case['fixture'], 'execution', $case['expect'][$version]['execution']['exit'] === 0, $case['expect'][$version]['execution']['contains'] ?? null);
            } else {
                $dir = '.audit/phase8a/deprecation'; if (!is_dir($dir)) mkdir($dir, 0777, true);
                $path = "$dir/{$case['id']}.php"; file_put_contents($path, $case['source']);
                foreach (['lint', 'execution'] as $stage) $run($id, $path, $stage, $case['accepted'], $case['expected'][$version][$stage]);
            }
        }
    }
    foreach (S::files('tests/fixtures/version-boundaries') as $manifest) {
        if (!str_ends_with($manifest, '.json')) continue;
        $case = S::read($manifest); $inputs[$manifest] = hash_file('sha256', $manifest);
        $path = str_replace('\\', '/', realpath(dirname($manifest) . '/' . $case['fixture']));
        $path = substr($path, strlen(str_replace('\\', '/', $root)) + 1);
        $accept = version_compare($version, $case['firstSupportedVersion'], '>=') && (($case['lastSupportedVersion'] ?? null) === null || version_compare($version, $case['lastSupportedVersion'], '<='));
        $stage = $case['validationStage'] ?? 'lint';
        $run('boundary/' . $case['name'], $path, $stage === 'structural' ? 'lint' : $stage, $accept);
    }
    foreach ($binaryHashes as $path => $hash) if (hash_file('sha256', $path) !== $hash) throw new RuntimeException('Binary changed during probes');
    $result = ['contract' => 'php86-rc7-research-observations-v1', 'sourceCommit' => $version === '8.6' ? $rc7 : null, 'profile' => $version === '8.6' ? 'official-distribution-rc7' : 'historical-distribution-8.5.10', 'exactSourceExecutable' => false,
        'version' => S::run([$php, '-n', '-v'], $root), 'binaryHashes' => $binaryHashes, 'inputs' => $inputs, 'observations' => $rows, 'mismatches' => $mismatches, 'certificationGranted' => false];
    if ($version === '8.6' && is_file("$out/probes.json") && !is_file("$out/probes-initial.json")) copy("$out/probes.json", "$out/probes-initial.json");
    file_put_contents("$out/probes-$version.json", S::json($result));
    echo S::json(['observations' => count($rows), 'mismatches' => $mismatches]); exit;
}
if (($argv[1] ?? '') === '--review') {
    $parts = preg_split('/(?=^diff --git )/m', file_get_contents("$out/source-diff.log"));
    foreach ($parts as $part) {
        if (str_starts_with($part, 'diff --git a/' . ($argv[2] ?? 'Zend/zend_compile.c') . ' ')) echo $part;
    }
    exit;
}
if (($argv[1] ?? '') === '--sources') {
    $git = ['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git'];
    if (is_file("$out/source-check.json") && !is_file("$out/source-check-initial.json")) copy("$out/source-check.json", "$out/source-check-initial.json");
    $commands = [
        'tree-rc4' => [...$git, 'ls-tree', '-r', $rc4],
        'tree-rc7' => [...$git, 'ls-tree', '-r', $rc7],
        'source-diff' => [...$git, 'diff', '--no-ext-diff', $rc4, $rc7, '--', 'Zend', 'NEWS', 'UPGRADING', 'UPGRADING.INTERNALS'],
        'changed-files' => [...$git, 'diff', '--name-status', $rc4, $rc7],
        'history' => [...$git, 'log', '--format=%H %s', "$rc4..$rc7"],
        'tag' => [...$git, 'cat-file', '-p', 'php-8.6.0RC7'],
    ];
    $receipts = [];
    foreach ($commands as $id => $command) {
        $log = "$out/$id.log"; $r = S::run($command, $root, $log);
        if ($r['exit_code'] !== 0) throw new RuntimeException("$id failed: {$r['output']}");
        $receipts[$id] = ['command' => $command, 'exitCode' => $r['exit_code'], 'path' => $log, 'sha256' => hash_file('sha256', $log)];
    }
    $lock = S::read('tools/8.6/certification-source-lock.json');
    $paths = array_unique([...array_keys($lock['files']), 'Zend/zend_language_parser.y', 'Zend/zend_language_scanner.l', 'Zend/zend_ast.c', 'Zend/zend_compile.c', 'Zend/zend_vm_def.h', 'Zend/zend_inheritance.c', 'Zend/zend_property_hooks.c', 'NEWS', 'UPGRADING']);
    $hashes = []; $errors = [];
    foreach ($paths as $path) {
        foreach (['rc4' => $rc4, 'rc7' => $rc7] as $profile => $commit) {
            $dest = ".audit/phase8a/$profile/$path";
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
            $r = S::run([...$git, 'show', "$commit:$path"], $root, $dest);
            if ($r['exit_code'] !== 0) { $errors[] = "$profile missing $path"; continue; }
            $hashes[$path][$profile] = hash_file('sha256', $dest);
        }
        if (isset($lock['files'][$path]) && ($hashes[$path]['rc4'] ?? null) !== $lock['files'][$path]) $errors[] = "RC4 lock mismatch: $path";
        $archive = ".audit/phase8a/php-8.6.0RC7/$path";
        $hashes[$path]['archive'] = is_file($archive) ? hash_file('sha256', $archive) : null;
        if ($hashes[$path]['archive'] !== ($hashes[$path]['rc7'] ?? null)) $errors[] = "RC7 archive mismatch: $path";
    }
    $tracked = S::run(['git', 'ls-files'], $root);
    if ($tracked['exit_code'] !== 0) throw new RuntimeException('Cannot read preservation inventory');
    $baseline = S::hashes(array_filter(explode("\n", trim($tracked['output']))));
    if (!is_file("$out/baseline.json")) file_put_contents("$out/baseline.json", S::json(['files' => $baseline]));
    file_put_contents("$out/source-check.json", S::json(['rc4' => $rc4, 'rc7' => $rc7, 'commands' => $receipts, 'files' => $hashes, 'errors' => $errors]));
    echo S::json(['files' => count($hashes), 'errors' => $errors]); exit($errors ? 1 : 0);
}
if (($argv[1] ?? '') === '--artifacts') {
    $items = [
        'php-8.6.0RC7.tar.gz' => ['https://downloads.php.net/~svpernova09/php-8.6.0RC7.tar.gz', '68b753e1d45e1417c6874008500de66102963cf0791e8c541211e9386318722e'],
        'php-8.6.0RC7-nts-Win32-vs18-x64.zip' => ['https://downloads.php.net/~windows/qa/php-8.6.0RC7-nts-Win32-vs18-x64.zip', '03da81bc29974c42eb0678c2c4cf94e52c484d1dfa7a61b3c36d98eb23350588'],
    ];
    $dir = '.audit/phase8a';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    foreach ($items as $name => [$url, $expected]) {
        $path = "$dir/$name";
        if (!is_file($path)) {
            $f = fopen($path, 'wb'); $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_FILE => $f, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 240]);
            $ok = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); fclose($f);
            if (!$ok || $code !== 200) throw new RuntimeException("Download failed: $url");
        }
        $actual = hash_file('sha256', $path);
        if ($actual !== $expected) throw new RuntimeException("Archive hash mismatch: $path");
        $receipt = ['url' => $url, 'path' => $path, 'sha256' => $actual, 'publishedSha256' => $expected, 'bytes' => filesize($path)];
        file_put_contents("$out/$name.json", json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        if (str_ends_with($name, '.zip')) {
            if (!is_dir("$dir/php86rc7")) { $zip = new ZipArchive(); $zip->open($path); $zip->extractTo("$dir/php86rc7"); $zip->close(); }
        } elseif (!is_dir("$dir/php-8.6.0RC7")) { (new PharData($path))->extractTo($dir); }
        echo "$name verified and extracted\n";
    }
    exit;
}
if (($argv[1] ?? '') === '--inspect') {
    foreach (['comparison', 'proposed-fix', 'rc7-ref', 'issue24792-events'] as $id) {
        $j = json_decode(file_get_contents("$out/$id.body"), true, 512, JSON_THROW_ON_ERROR);
        echo json_encode(['id' => $id, 'status' => $j['status'] ?? null, 'object' => $j['object'] ?? null,
            'total_commits' => $j['total_commits'] ?? null,
            'commits' => array_map(static fn($c) => [$c['sha'], $c['commit']['message']], $j['commits'] ?? []),
            'files' => array_map(static fn($f) => [$f['filename'], $f['status'], $id === 'proposed-fix' ? ($f['patch'] ?? null) : null], $j['files'] ?? []),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    foreach (['downloads', 'windows-downloads'] as $id) {
        preg_match_all('~(?:href|sha256)=["\x27]([^"\x27]+)|[a-f0-9]{64}~i', file_get_contents("$out/$id.body"), $m);
        echo json_encode([$id => array_values(array_filter($m[0], static fn($s) => str_contains($s, '8.6') || preg_match('/[a-f0-9]{64}/', $s)))]) . "\n";
    }
    exit;
}
$urls = [
    'fix-pr' => 'https://api.github.com/repos/php/php-src/pulls/25046',
    'fix-pr-files' => 'https://api.github.com/repos/php/php-src/pulls/25046/files?per_page=100',
    'fix-pr-commits' => 'https://api.github.com/repos/php/php-src/pulls/25046/commits?per_page=100',
    'rc7-tag' => 'https://api.github.com/repos/php/php-src/git/tags/163f211f0aed3b16d30091187d9b742159ebdbd8',
    'qa-downloads' => 'https://qa.php.net/',
    'windows-qa' => 'https://windows.php.net/qa/',
    'fix-search' => 'https://api.github.com/search/issues?q=repo:php/php-src+24792',
    'rc7-ref' => 'https://api.github.com/repos/php/php-src/git/ref/tags/php-8.6.0RC7',
    'rc7-commit' => 'https://api.github.com/repos/php/php-src/commits/php-8.6.0RC7',
    'comparison' => 'https://api.github.com/repos/php/php-src/compare/f23b4c222cebe33be93ee8ecaaa14390c5507a08...php-8.6.0RC7',
    'issue24792' => 'https://api.github.com/repos/php/php-src/issues/24792',
    'issue24792-events' => 'https://api.github.com/repos/php/php-src/issues/24792/events?per_page=100',
    'proposed-fix' => 'https://api.github.com/repos/ChrisAbner/php-src/commits/5089bcc83c8193e6a965dd1579767b1d503401ba',
    'official-fix' => 'https://api.github.com/repos/php/php-src/commits/5089bcc83c8193e6a965dd1579767b1d503401ba',
    'fix-pulls' => 'https://api.github.com/repos/php/php-src/commits/5089bcc83c8193e6a965dd1579767b1d503401ba/pulls',
    'release-branch' => 'https://api.github.com/repos/php/php-src/commits/PHP-8.6',
    'downloads' => 'https://www.php.net/downloads.php',
    'windows-downloads' => 'https://windows.php.net/download/',
];
foreach ($urls as $id => $url) {
    $file = "$out/$id.body";
    if (is_file($file)) { echo "$id cached\n"; continue; }
    $headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'php-grammar Phase8A source reconciliation', CURLOPT_TIMEOUT => 90,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int { $headers[] = $line; return strlen($line); }]);
    $body = curl_exec($ch);
    $receipt = ['url' => $url, 'retrievedAt' => gmdate('c'), 'httpStatus' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'effectiveUrl' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), 'error' => curl_error($ch)];
    if ($body !== false) { file_put_contents($file, $body); $receipt['sha256'] = hash_file('sha256', $file); }
    file_put_contents("$out/$id.headers", implode('', $headers));
    file_put_contents("$out/$id.receipt.json", json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "$id: {$receipt['httpStatus']} {$receipt['error']}\n";
}
