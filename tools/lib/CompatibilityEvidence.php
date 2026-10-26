<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/HistoricalEvidence.php';
require_once __DIR__ . '/Certification86.php';

/** Bounded current observations. No historical or language-completeness grant. */
final class CompatibilityEvidence
{
    public const CONTRACT = 'current-compatibility-v1';
    public const SCHEMA = 'php-grammar/current-compatibility/1';
    public const POLICY = 'tools/evidence/compatibility-policy.json';
    public const CLAIM = 'Current PHP 8.5 regression compatibility within the executed matrices, including shared APIs and paired version boundaries; not certification renewal.';
    public function __construct(public readonly string $root) {}
    public function read(string $relative): array
    {
        $file = HistoricalEvidence::safeFile($this->root, $relative);
        if ($file === null) throw new \RuntimeException('Missing or unsafe evidence path: ' . $relative);
        return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }
    public function inventory(): array
    {
        $policy = $this->read(self::POLICY);
        if (($policy['contract'] ?? '') !== self::CONTRACT || ($policy['claim'] ?? '') !== self::CLAIM) throw new \RuntimeException('Invalid dependency policy identity');
        $paths = [];
        foreach ($policy['trees'] as $tree => $reason) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root . '/' . $tree, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $file) {
                if ($file->isLink()) throw new \RuntimeException('Symlink in dependency scope');
                if ($file->isFile()) $paths[substr(str_replace('\\', '/', $file->getPathname()), strlen(str_replace('\\', '/', $this->root)) + 1)] = $reason;
            }
        }
        foreach ($policy['files'] as $p) $paths[$p] = 'shared policy, discovery or execution configuration';
        foreach (Support::files($this->root . '/docs/8.5') as $file) {
            $p = substr($file, strlen(str_replace('\\', '/', $this->root)) + 1);
            if (!str_starts_with($p, 'docs/8.5/compatibility/')) $paths[$p] = 'PHP 8.5 evidence and documented external constraints';
        }
        foreach ($this->read('tools/evidence/documentation-inputs.json')['files'] as $p) $paths[$p] = 'explicit transitive PHP 8.6 evidence used by current regression/integration checks';
        // Include literal document dependencies even when added outside the explicit list.
        foreach (array_keys($paths) as $p) {
            if (!str_ends_with($p, '.php') || str_starts_with($p, 'vendor/')) continue;
            preg_match_all('~docs/[A-Za-z0-9_./-]+\.(?:json|md)~', file_get_contents($this->root . '/' . $p), $matches);
            foreach ($matches[0] as $dependency) {
                if (is_file($this->root . '/' . $dependency) && !str_starts_with($dependency, 'docs/8.5/compatibility/')
                    && !str_starts_with($dependency, 'docs/8.6/hybrid-evidence/')) $paths[$dependency] ??= 'literal document dependency of ' . $p;
            }
        }
        $manifest = $this->read('php-grammar.json');
        foreach ($manifest['versions'] as $package) foreach ($package['metadata'] ?? [] as $p) $paths[$p] = 'manifest-discovered version evidence';
        ksort($paths, SORT_STRING); $result = []; $case = [];
        foreach ($paths as $p => $reason) {
            $file = HistoricalEvidence::safeFile($this->root, $p);
            if ($file === null || isset($case[strtolower($p)])) throw new \RuntimeException('Missing/unsafe/colliding mandatory dependency: ' . $p);
            $case[strtolower($p)] = true;
            $result[$p] = ['sha256' => hash_file('sha256', $file), 'reason' => $reason];
        }
        return $result;
    }

    public static function defaults(string $root): array
    {
        $path = static fn($p) => str_replace('\\', '/', realpath($root . '/' . $p) ?: $root . '/' . $p);
        return ['php85' => $path('.audit/php85/php.exe'), 'php86' => $path('.audit/php86/php.exe'),
            'sources85' => $path('.audit/php-php-src-7a4c627'), 'sources86' => $path('.audit/php-php-src-f23b4c2'),
            'source-cache' => $path('.audit/phase7-sources')];
    }
    public function commands(array $config, string $output): array
    {
        $p = [$config['php85'], '-n', '-d', 'zend.multibyte=0', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];
        $unit = [...$p];
        if (PHP_OS_FAMILY === 'Windows') array_push($unit, '-d', 'extension=' . dirname($config['php85']) . '/ext/php_mbstring.dll');
        array_push($unit, 'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress');
        return [
            'manifest-schema' => [...$p, 'tools/validate-manifest.php'],
            'full-regression' => $unit,
            'consumer-api-cli' => [...$unit, '--filter', 'Consumer|ConformanceVersionSupportTest'],
            'version-boundary-regressions' => [...$unit, '--filter', 'VersionBoundaryTest'],
            'infrastructure-negatives' => [...$unit, '--filter', 'Certification86Test|HistoricalEvidenceContractTest|CompatibilityEvidenceTest'],
            'expression-freshness' => [...$p, 'tools/8.5/generate-expressions.php', '--check'],
            'ordinary-differential85' => [...$p, 'bin/php85-conformance.php', $config['php85']],
            'lexer-short-enabled85' => [...$p, '-d', 'short_open_tag=1', 'tools/8.5/lexer-differential.php'],
            'lexer-short-disabled85' => [...$p, '-d', 'short_open_tag=0', 'tools/8.5/lexer-differential.php'],
            'positive-evidence85' => [...$p, 'tools/8.5/coverage-report.php', '--check'],
            'negative-evidence85' => [...$p, 'tools/8.5/negative-report.php', '--check'],
            'phase6-evidence85' => [...$p, 'tools/8.5/phase6-evidence.php', '--check'],
            'source-correspondence85' => [...$p, 'tools/8.5/source-correspondence.php', '--cache', $config['source-cache'], '--check'],
            'source-lock86' => [...$p, 'tools/8.6/certification-gate.php', '--gate', 'source', '--sources86', $config['sources86']],
            'paired-boundaries' => [...$p, 'tools/8.6/integration-check.php', '--php85=' . $config['php85'], '--php86=' . $config['php86'],
                '--sources85=' . $config['sources85'], '--sources86=' . $config['sources86'], '--report=' . $output . '/boundaries.json'],
        ];
    }
    public function environment(array $config): array
    {
        $result = ['interpreters' => [], 'sources' => [], 'errors' => []];
        $lock = $this->read('tools/8.6/source-lock.json');
        foreach (['85','86'] as $v) {
            $binary = $config['php' . $v] ?? '';
            if (!is_file($binary)) { $result['errors'][] = 'Missing interpreter: ' . $v; continue; }
            if (hash_file('sha256', $binary) !== ($lock['executables']['8.' . substr($v, 1)] ?? null)) {
                $result['errors'][] = 'Unreviewed interpreter; not executed: ' . $v; continue;
            }
            $r = Support::run([$binary, '-n', '-v'], $this->root);
            if ($r['exit_code'] || !str_contains($r['output'], 'PHP 8.' . substr($v, 1))) $result['errors'][] = 'Wrong interpreter version: ' . $v;
            $extensions = [];
            if (PHP_OS_FAMILY === 'Windows' && $v === '85') {
                $extension = dirname($binary) . '/ext/php_mbstring.dll';
                $extensions[$extension] = is_file($extension) ? hash_file('sha256', $extension) : null;
                if ($extensions[$extension] === null) $result['errors'][] = 'Missing PHPUnit mbstring prerequisite';
            }
            $result['interpreters'][$v] = ['path' => $binary, 'sha256' => hash_file('sha256', $binary), 'versionOutput' => $r['output'],
                'extensions' => $extensions, 'profile' => '-n; byte source; CLI opcache/JIT disabled; explicit short tags per command',
                'sourceCommitVerified' => false, 'provenance' => 'Existing distribution; exact-pin build not attested'];
        }
        foreach (['8.5'=>'85', '8.6'=>'86'] as $version => $suffix) {
            foreach ($lock['files'][$version] as $p => $expected) {
                $file = $config['sources' . $suffix] . '/' . $p;
                $actual = is_file($file) ? hash_file('sha256', $file) : null;
                $result['sources'][$version][$p] = $actual;
                if ($actual !== $expected) $result['errors'][] = 'Source bytes mismatch: ' . $version . '/' . $p;
            }
            if (($result['interpreters'][$suffix]['sha256'] ?? null) !== $lock['executables'][$version]) $result['errors'][] = 'Unreviewed distribution binary: ' . $version;
        }
        // Normative 8.5 hashes are anchored independently of an editable current lock.
        $required = ['zend_language_parser.y'=>'f108f2c2ff4c98c68b6478e7fbdebc16d8afb8bbbc3ae6079e5b285b448dac0c',
            'zend_language_scanner.l'=>'8dfed84b0f6cabe73f004ea00c69614ea84542130a9b97e7699f801ef7ce7403',
            'zend_compile.c'=>'25190ee20f2660aa66577f5da71b43be63268e90fa7c0ecb2e3cf8f1e621b225'];
        if ($this->read('tools/8.5/source-lock.json') !== $required) $result['errors'][] = 'Invalid PHP 8.5 source lock';
        foreach ($required as $p => $expected) if (($result['sources']['8.5']['Zend/' . $p] ?? null) !== $expected) $result['errors'][] = 'Missing normative source: ' . $p;
        $cache = [];
        if (!is_dir($config['source-cache'])) $result['errors'][] = 'Missing source comparison cache';
        else foreach (Support::files($config['source-cache']) as $file) $cache[substr($file, strlen($config['source-cache']) + 1)] = hash_file('sha256', $file);
        $result['sourceCache'] = $cache;
        $auditLock = $this->read('tools/8.6/certification-source-lock.json');
        $audit = new Certification86($this->root);
        $result['errors'] = [...$result['errors'], ...$audit->sourceErrors($auditLock, $config['sources86'])];
        $result['auditSources86'] = [];
        foreach ($auditLock['files'] as $p => $hash) {
            $file = HistoricalEvidence::safeFile($config['sources86'], $p);
            $result['auditSources86'][$p] = $file === null ? null : hash_file('sha256', $file);
        }
        return $result;
    }
    public static function totals(string $log): ?array
    {
        return preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $log, $m) ? ['tests'=>(int)$m[1], 'assertions'=>(int)$m[2]] : null;
    }
    public function schemaErrors(array $report): array
    {
        $schema = $this->read('schema/certification-compatibility.schema.json'); $errors = [];
        foreach ($schema['required'] as $key) if (!array_key_exists($key, $report)) $errors[] = 'Missing schema field: ' . $key;
        foreach ($report as $key => $value) {
            if (!isset($schema['properties'][$key])) { $errors[] = 'Unknown schema field: ' . $key; continue; }
            $p = $schema['properties'][$key];
            if (isset($p['const']) && $value !== $p['const']) $errors[] = 'Invalid schema identity/claim: ' . $key;
            $valid = match ($p['type'] ?? '') { 'string'=>is_string($value), 'boolean'=>is_bool($value), 'array','object'=>is_array($value), default=>false };
            if (!$valid) $errors[] = 'Invalid schema type: ' . $key;
        }
        return $errors;
    }
    public function verify(array $report, string $directory, bool $environment = true): array
    {
        $errors = $this->schemaErrors($report);
        if ($errors) return $errors;
        $receiptFile = HistoricalEvidence::safeFile($directory, 'run-receipt.json');
        $evidenceFile = HistoricalEvidence::safeFile($directory, 'evidence.json');
        $receipt = $receiptFile === null ? [] : json_decode(file_get_contents($receiptFile), true, 512, JSON_THROW_ON_ERROR);
        if (($receipt['reportSha256'] ?? null) !== hash('sha256', Support::json($report))
            || $evidenceFile === null || ($receipt['reportSha256'] ?? null) !== hash_file('sha256', $evidenceFile)) {
            $errors[] = 'Tampered evidence/run metadata';
        }
        if ($report['dependencies'] !== $this->inventory()) return ['Stale or incomplete mandatory dependency scope'];
        if ($report['inputsUnchanged'] !== true) $errors[] = 'Inputs changed during execution';
        if (array_keys($report['configuration']) !== array_keys(self::defaults($this->root))
            || count(array_filter($report['configuration'], 'is_string')) !== 5) return [...$errors, 'Invalid configuration schema'];
        if ($report['missingPrerequisites'] !== []) $errors[] = 'Missing prerequisites';
        if ($report['historicalReference'] !== ['commit'=>HistoricalEvidence::COMMIT, 'ledgerSha256'=>HistoricalEvidence::CHECKOUT_SHA256, 'integrityImplied'=>false]) $errors[] = 'Invalid historical reference or historical/current substitution';
        if ($report['limitations'] !== self::limitations()) $errors[] = 'Unsupported claim limitations';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $report['generatedAt'])) $errors[] = 'Invalid generation time';
        if (!preg_match('/^[a-f0-9]{40}$/D', $report['repository']['commit'] ?? '') || !is_string($report['repository']['workingTree'] ?? null)) $errors[] = 'Missing repository identity';
        $expected = $this->commands($report['configuration'], $directory);
        $allowed = ['boundaries.json', 'evidence.json', 'run-receipt.json'];
        foreach (array_keys($expected) as $id) array_push($allowed, $id . '.json', $id . '.log');
        $actualFiles = [];
        foreach (Support::files($directory) as $file) $actualFiles[] = substr($file, strlen($directory) + 1);
        sort($allowed); sort($actualFiles);
        if ($actualFiles !== $allowed) $errors[] = 'Unexpected or missing output artifact';
        if (array_keys($report['commands']) !== array_keys($expected)) $errors[] = 'Missing or unexpected validation command';
        foreach ($expected as $id => $command) {
            $row = $report['commands'][$id] ?? [];
            if (($row['command'] ?? null) !== $command || ($row['cwd'] ?? null) !== $this->root || ($row['exit_code'] ?? null) !== 0) $errors[] = 'Invalid or failed command: ' . $id;
            $log = HistoricalEvidence::safeFile($directory, $id . '.log');
            $receipt = HistoricalEvidence::safeFile($directory, $id . '.json');
            if ($log === null || ($row['logSha256'] ?? null) !== hash_file('sha256', $log)) $errors[] = 'Tampered/missing log: ' . $id;
            if ($receipt === null || json_decode(file_get_contents($receipt), true) !== $row) $errors[] = 'Command receipt mismatch: ' . $id;
            if ($log !== null && ($row['totals'] ?? null) !== self::totals(file_get_contents($log))) $errors[] = 'Falsified test counts: ' . $id;
            if (in_array($id, ['full-regression','consumer-api-cli','version-boundary-regressions','infrastructure-negatives'], true) && ($row['totals'] ?? null) === null) $errors[] = 'Missing actual PHPUnit result: ' . $id;
        }
        $boundary = HistoricalEvidence::safeFile($directory, 'boundaries.json');
        if ($boundary === null || hash_file('sha256', $boundary) !== $report['boundarySha256']) $errors[] = 'Missing/tampered version-boundary evidence';
        else {
            $b = json_decode(file_get_contents($boundary), true, 512, JSON_THROW_ON_ERROR);
            if (($b['errors'] ?? null) !== [] || count($b['observations'] ?? []) < 72) $errors[] = 'Incomplete boundary observations';
            foreach (Support::files($this->root . '/tests/fixtures/version-boundaries') as $fixture) {
                if (!str_ends_with($fixture, '.json')) continue;
                $case = json_decode(file_get_contents($fixture), true, 512, JSON_THROW_ON_ERROR);
                if (!isset($case['name'], $case['firstSupportedVersion'])) { $errors[] = 'Malformed boundary case'; continue; }
                foreach (['8.5','8.6'] as $version) {
                    $stage = $case['validationStage'] ?? 'structural';
                    $matches = array_values(array_filter($b['observations'] ?? [], static fn($o) => ($o['id'] ?? null) === $case['name'] && ($o['version'] ?? null) === $version && ($o['stage'] ?? null) === $stage));
                    if (count($matches) !== 1) { $errors[] = 'Missing/duplicate boundary result: ' . $case['name'] . '/' . $version; continue; }
                    $accepted = version_compare($version, $case['firstSupportedVersion'], '>=') && (!isset($case['lastSupportedVersion']) || version_compare($version, $case['lastSupportedVersion'], '<='));
                    $result = $matches[0]['result'];
                    $actual = $stage === 'structural' ? ($result['matched'] ?? null) : (isset($result['stageResult']['exit']) ? $result['stageResult']['exit'] === 0 : null);
                    if ($actual !== $accepted) $errors[] = 'Incorrect boundary outcome: ' . $case['name'] . '/' . $version;
                }
            }
        }
        if ($environment && $report['environment'] !== $this->environment($report['configuration'])) $errors[] = 'Changed interpreter/source environment';
        if (($report['environment']['errors'] ?? null) !== []) $errors[] = 'Invalid source/executable prerequisites';
        return $errors;
    }
    public static function limitations(): array
    {
        return ['Bounded executed regression matrices only; no certification renewal or completeness proof.',
            'Historical integrity is a separate required hybrid prerequisite; old tests were not rerun.',
            'Distribution executables are not exact-pin build attestations.',
            'Legacy 27 release obligations and PHP 8.6 mandatory blockers are not waived.',
            'Digests and receipts detect inconsistency relative to trusted run artifacts; coordinated replacement requires trusted archival/review provenance.'];
    }
}
