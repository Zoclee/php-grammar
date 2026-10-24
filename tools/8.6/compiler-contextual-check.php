<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Repository\RepositoryManifest;

// Research evidence only. Execute only the explicitly reviewed, hash-locked cases.
$root = dirname(__DIR__, 2);
$options = getopt('', ['php85:', 'php86:', 'sources85:', 'sources86:', 'cache:', 'report:']);
foreach (['php85', 'php86', 'sources85', 'sources86', 'cache', 'report'] as $key) {
    if (!isset($options[$key])) {
        fwrite(STDERR, "Required: --php85 --php86 --sources85 --sources86 --cache --report\n");
        exit(1);
    }
}
$read = static fn(string $path): array => json_decode(file_get_contents($root . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
$checks = 0;
$hashChecks = 0;
$check = static function (bool $ok, string $label) use (&$errors, &$checks): void {
    ++$checks;
    if (!$ok) $errors[] = $label;
};
$verifyHash = static function (string $path, ?string $expected) use ($check, &$hashChecks): void {
    ++$hashChecks;
    $actual = is_file($path) ? hash_file('sha256', $path) : null;
    $check($actual === $expected, 'Hash/absence: ' . $path);
};
$run = static function (array $command) use ($root): array {
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('Cannot launch reviewed oracle');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
};
$matchResult = static function (array $actual, array $expected, string $label) use ($check): void {
    $check($actual['exit'] === $expected['exit'], $label . ' exit');
    if (isset($expected['contains'])) $check(str_contains($actual['stdout'] . $actual['stderr'], $expected['contains']), $label . ' diagnostic');
    if (isset($expected['stdout'])) {
        $check($actual['stdout'] === $expected['stdout'], $label . ' output');
        $check($actual['stderr'] === '', $label . ' clean stderr');
    }
};

try {
    $base = $read('tools/8.6/source-lock.json');
    $lock = $read('tools/8.6/reconciliation-source-lock.json');
    $original = $read('docs/8.6/source-evidence.json');
    $cases = $read('tests/fixtures/php/8.6/reconciliation-cases.json');
    $groups = $read('docs/8.6/compiler-contextual-groups.json');
    $historical = $read('docs/8.6/compiler-change-reconciliation.json');
    $manifest = RepositoryManifest::fromRepositoryRoot($root);
    $matcher = PhpGrammarMatcher::forManifest($manifest);
    $check($manifest->package('8.6')->status === 'draft', '8.6 remains draft');
    $check($cases['sourcePins'] === $lock['pins'] && $groups['pins'] === $lock['pins'], 'Source pin agreement');
    $check($lock['pins'] === ['8.5' => '7a4c62795365ed6a97a0184c96375b9fb4d53b1e', '8.6' => 'f23b4c222cebe33be93ee8ecaaa14390c5507a08'], 'Immutable source pins');
    $verifyHash($root . '/tools/8.6/source-lock.json', $lock['baseLockSha256']);
    $verifyHash($root . '/docs/8.6/source-evidence.json', $lock['originalEvidenceSha256']);
    $verifyHash($root . '/docs/8.6/compiler-change-reconciliation.json', $groups['historicalLedgerSha256']);
    $byId = array_column($historical['hunks'], null, 'id');
    $groupIds = array_column($groups['groups'], 'id');
    $caseIds = array_column($cases['cases'], 'id');
    $check(count($caseIds) === count(array_unique($caseIds)), 'Unique case IDs');
    $selected = [];
    $resolved = [];
    foreach ($groups['groups'] as $group) {
        foreach ($group['historicalHunks'] as $hunk) {
            $check(($byId[$hunk['id']] ?? null) === $hunk, 'Unchanged historical hunk ' . $hunk['id']);
            $check(!isset($selected[$hunk['id']]), 'Unique hunk association ' . $hunk['id']);
            $selected[$hunk['id']] = true;
            if (str_starts_with($group['disposition'], 'RESOLVED')) $resolved[$hunk['id']] = true;
        }
        foreach ($group['probeIds'] as $id) $check(in_array($id, $caseIds, true), 'Group probe ' . $id);
        $check($group['remaining'] !== '' && $group['sourceAcceptance'] !== '', 'Bounded group rationale ' . $group['id']);
    }
    $pending = array_values(array_map(static fn($h) => $h['id'], array_filter($historical['hunks'], static fn($h) => ($h['phase22']['disposition'] ?? '') === 'pending-direct-review' && !isset($resolved[$h['id']]))));
    $check($pending === $groups['phase22PendingNotDischarged'], 'Historical pending queue reconciliation');
    $check(count($selected) === $groups['counts']['selectedRawHunks'], 'Selected raw hunk count');
    $check(count($resolved) === $groups['counts']['resolvedSelectedRawHunks'], 'Resolved raw hunk count');
    $check($groups['finding013'] === 'UNRESOLVED — BLOCKING', 'Discovery remains open');
    $cache = realpath($options['cache']);
    if ($cache === false) throw new RuntimeException('Missing research cache');
    foreach ($original['artifactOrder'] as $i => $path) $verifyHash($cache . '/' . $path, $original['sha256'][$i]);
    foreach ($lock['cachedUpstreamSnapshots'] as $path => $hash) $verifyHash($cache . '/' . $path, $hash);
    $observations = [];
    $interpreters = [];
    $fixtureHashes = [];
    $sourceHashes = [];
    $invocations = ['lint' => 0, 'execution' => 0];
    foreach (['8.5' => ['85', 'old'], '8.6' => ['86', 'new']] as $version => [$suffix, $side]) {
        $binary = realpath($options['php' . $suffix]);
        $sources = realpath($options['sources' . $suffix]);
        if ($binary === false || $sources === false) throw new RuntimeException('Missing binary/source directory');
        $verifyHash($binary, $base['executables'][$version]);
        foreach ($original['files'] as $path => $sides) $verifyHash($sources . '/' . $path, $sides[$side]);
        foreach ($base['files'][$version] + $lock['sourceFiles'][$version] as $path => $hash) {
            $verifyHash($sources . '/' . $path, $hash);
            $sourceHashes[$version][$path] = $hash;
        }
        foreach ($lock['sourceSlices'] as $slice) {
            if ($slice['version'] !== $version) continue;
            $text = implode('', array_slice(file($sources . '/' . $slice['file']), $slice['start'] - 1, $slice['end'] - $slice['start'] + 1));
            $check(hash('sha256', $text) === $slice['sha256'] && str_contains($text, $slice['anchor']), 'Source slice ' . $slice['anchor']);
        }
        // Do not execute any probes if their provenance check has already failed.
        if ($errors !== []) throw new RuntimeException('Source/ledger checks failed before oracle probes');
        $prefix = [$binary, '-n', '-d', 'zend.multibyte=0', '-d', 'short_open_tag=1', '-d', 'error_reporting=-1', '-d', 'max_execution_time=5', '-d', 'memory_limit=64M'];
        $profile = $run([...$prefix, '-r', 'echo json_encode(["version"=>PHP_VERSION,"sapi"=>PHP_SAPI,"zts"=>PHP_ZTS,"debug"=>PHP_DEBUG,"opcache.enable_cli"=>ini_get("opcache.enable_cli"),"opcache.jit"=>ini_get("opcache.jit"),"zend.multibyte"=>ini_get("zend.multibyte"),"short_open_tag"=>ini_get("short_open_tag")]);']);
        $settings = json_decode($profile['stdout'], true, 512, JSON_THROW_ON_ERROR);
        $check($profile['exit'] === 0 && !$settings['opcache.enable_cli'], 'CLI opcache disabled ' . $version);
        $interpreters[$version] = ['sha256' => hash_file('sha256', $binary), 'version' => $run([$binary, '-n', '-v']), 'configuration' => array_slice($prefix, 1), 'profile' => $settings, 'exactPinVerified' => false];
        foreach ($cases['cases'] as $case) {
            $path = $root . '/' . $case['fixture'];
            $source = file_get_contents($path);
            $fixtureHashes[$case['fixture']] = hash('sha256', $source);
            if ($fixtureHashes[$case['fixture']] !== $case['sha256']) throw new RuntimeException('Unreviewed fixture bytes: ' . $case['id']);
            foreach ($case['groups'] as $id) $check(in_array($id, $groupIds, true), 'Case group ' . $id);
            $expected = $case['expect'][$version];
            $tokens = Lexer::forVersion($version)->tokenize($source);
            $lexical = implode('', array_map(static fn($t) => $t->lexeme, $tokens->all())) === $source;
            $check($lexical === $expected['lexerAccepted'], 'Lexical bytes ' . $version . ' ' . $case['id']);
            $structural = $matcher->matches($version, $source)->matched;
            $check($structural === $expected['structural'], 'Structure ' . $version . ' ' . $case['id']);
            $lint = $run([...$prefix, '-l', $path]);
            ++$invocations['lint'];
            $matchResult($lint, $expected['lint'], 'Lint ' . $version . ' ' . $case['id']);
            $execution = null;
            if ($case['executeReviewed'] && $lint['exit'] === 0) {
                $check($case['executionReview'] !== '', 'Execution review ' . $case['id']);
                $execution = $run([...$prefix, $path]);
                ++$invocations['execution'];
                $check($expected['execution'] !== null, 'Expected execution ' . $case['id']);
                if ($expected['execution'] !== null) $matchResult($execution, $expected['execution'], 'Execution ' . $version . ' ' . $case['id']);
            } else {
                $check($expected['execution'] === null, 'Execution not performed ' . $case['id']);
            }
            $observations[] = ['id' => $case['id'], 'version' => $version, 'lexicalBytePreservation' => $lexical, 'structural' => $structural, 'lint' => $lint, 'executionStage' => $execution === null ? null : $case['executionStage'], 'execution' => $execution, 'opcodeObservation' => 'No opcode dump. Lint combines parsing and compilation; operand-kind mechanism is source analysis, not a separately observed generation stage.'];
        }
    }
    $inputs = [];
    foreach (['tools/8.6/compiler-contextual-check.php', 'tools/8.6/reconciliation-source-lock.json', 'tests/fixtures/php/8.6/reconciliation-cases.json', 'docs/8.6/compiler-contextual-groups.json', 'grammar/8.5/php.ebnf', 'grammar/8.5/php.md', 'grammar/8.6/php.ebnf', 'grammar/8.6/php.md', 'php-grammar.json'] as $path) $inputs[$path] = hash_file('sha256', $root . '/' . $path);
    $report = ['phase' => '5C', 'scope' => 'Finite research evidence; no language-intent inference from successful lint or execution.', 'pins' => $lock['pins'], 'inputHashes' => $inputs, 'fixtureHashes' => $fixtureHashes, 'sourceHashes' => $sourceHashes, 'interpreters' => $interpreters, 'checks' => $checks, 'hashChecks' => $hashChecks, 'invocations' => $invocations, 'observations' => $observations, 'errors' => $errors];
    array_walk_recursive($report, static function (&$value) use ($root): void {
        if (is_string($value)) $value = str_replace([$root, str_replace('\\', '/', $root)], '<repository>', $value);
    });
    file_put_contents($options['report'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    echo json_encode(['checks' => $checks, 'hashChecks' => $hashChecks, 'caseVersionObservations' => count($observations), 'invocations' => $invocations, 'errors' => $errors], JSON_PRETTY_PRINT) . "\n";
    exit($errors === [] ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n" . implode("\n", $errors) . "\n");
    exit(1);
}
