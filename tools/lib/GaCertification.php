<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/Certification86.php';
require_once __DIR__ . '/ManifestValidator.php';
require_once __DIR__ . '/ChangeTraceability.php';
require_once __DIR__ . '/DifferentialInventory.php';
require_once __DIR__ . '/CompatibilityEvidence.php';
require_once __DIR__ . '/GaLocalObligations.php';

/** Explicit GA successor. Legacy RC4 contracts are never reinterpreted. */
final class GaCertification
{
    public const CONTRACT = 'php86-ga-certification-v2';
    public const PIN = '8aaada3f9605d81a38b8240f93c7389dfbc77ef9';
    public const OUT = 'docs/8.6/ga-certification-remediation';
    public const BASELINE = 'b2a0d55a7cec36ef78d9b7e307af04f407643965c5b33553fe2137bcdcd6f6ed';
    public const SEAL = '05b43c14e487160b5451d34bbd330276feaffacedebbe3249d415e576563bfb7';
    public const TRANSITION = 'b238e111b7744224d91616e4c9dad9e7ef0f2780018eb967b9cfa14a6aeeee51';
    public const PHP = '.audit/php85/php.exe';
    public const GA = '.audit/phase8b/source/x64/Release/php.exe';
    public const SOURCES = '.audit/phase8b/source';
    public const RUNNER_REPLACEMENTS = ['source-lock.json' => 'source-lock-ga-v2.json',
        'pfa-source-lock.json' => 'pfa-source-lock-ga-v1.json', 'context-source-lock.json' => 'context-source-lock-ga-v1.json',
        'final-coverage-source-lock.json' => 'final-coverage-source-lock-ga-v2.json',
        'tests/fixtures/php/8.6/deprecation-cases.json' => 'tools/8.6/deprecation-cases-ga-v1.json'];
    public const CHANGED = ['php-grammar.json', 'grammar/8.6/changes.md', 'tests/Certification86Test.php',
        'tests/Php/Conformance/Php86TraceabilityTest.php', 'tests/Repository/ConsumerCliTest.php',
        'tools/8.6/ga-remediation-prepare.php', 'tests/UpstreamResolutionFreshnessTest.php',
        'tests/Php/Conformance/Php86CompilerContextualTest.php'];
    public function __construct(public readonly string $root) {}
    public function read(string $p): array { return json_decode(file_get_contents($this->root . '/' . $p), true, 512, JSON_THROW_ON_ERROR); }
    public function hashes(array $paths): array {
        $r = []; foreach ($paths as $p) $r[$p] = hash_file('sha256', $this->root . '/' . $p);
        ksort($r); return $r;
    }
    public function hashErrors(array $hashes): array {
        $errors = []; foreach ($hashes as $p => $h) if (!is_file($this->root . '/' . $p) || hash_file('sha256', $this->root . '/' . $p) !== $h) $errors[] = 'Missing/stale bytes: ' . $p;
        return $errors;
    }
    public static function writeNew(string $p, array $r): void {
        if (file_exists($p)) throw new \RuntimeException('Immutable evidence already exists: ' . $p);
        if (!is_dir(dirname($p))) mkdir(dirname($p), 0777, true);
        file_put_contents($p, Support::json($r));
    }
    public function inventory(): array {
        $paths = ['AGENTS.md', '.gitattributes', '.gitignore', 'composer.json', 'composer.lock', 'phpunit.xml.dist', 'php-grammar.json', 'README.md'];
        foreach (['src', 'tests', 'grammar', 'tools', 'docs', 'bin', 'schema', 'vendor'] as $tree) {
            foreach (Support::files($this->root . '/' . $tree) as $file) {
                $p = substr($file, strlen($this->root) + 1);
                if (str_starts_with($p, self::OUT . '/runs/') || $p === self::OUT . '.md') continue;
                $paths[] = $p;
            }
        }
        return $this->hashes(array_unique($paths));
    }
    public function preservation(): array {
        $errors = $this->hashErrors([self::OUT . '/baseline.json' => self::BASELINE,
            'docs/8.6/ga-finalization/artifacts.json' => self::SEAL, self::OUT . '/transition-v2.json' => self::TRANSITION]);
        if ($errors) return $errors;
        $baseline = $this->read(self::OUT . '/baseline.json')['files'];
        $transition = $this->read(self::OUT . '/transition-v2.json');
        if ($transition['contract'] !== 'php86-ga-preservation-v2' || $transition['baseline'] !== self::BASELINE
            || array_keys($transition['changes']) !== self::CHANGED) return ['Unapproved preservation transition'];
        foreach ($transition['changes'] as $p => $row) {
            if ($row['before'] !== $baseline[$p]) $errors[] = 'Incorrect predecessor identity: ' . $p;
            $errors = [...$errors, ...$this->hashErrors([$p => $row['after']])];
        }
        $errors = [...$errors, ...$this->hashErrors($transition['additions'])];
        foreach (self::CHANGED as $p) unset($baseline[$p]);
        $errors = [...$errors, ...$this->hashErrors($baseline), ...$this->hashErrors($this->read('docs/8.6/ga-finalization/artifacts.json')['files'])];
        $old = $this->read(self::OUT . '/predecessor/php-grammar.json');
        $new = $this->read('php-grammar.json');
        if ($old['versions'][0] !== $new['versions'][0]) $errors[] = 'PHP 8.5 manifest identity changed';
        foreach (self::CHANGED as $p) {
            if ($p === 'tools/8.6/ga-remediation-prepare.php') continue;
            if (hash_file('sha256', $this->root . '/' . self::OUT . '/predecessor/' . $p) !== $this->read(self::OUT . '/baseline.json')['files'][$p]) $errors[] = 'Predecessor bytes changed: ' . $p;
        }
        return $errors;
    }
    public function profileMetadataErrors(?array $manifest = null, ?array $lock = null): array {
        $manifest ??= $this->read('php-grammar.json');
        $errors = (new ManifestValidator())->validate(json_decode(json_encode($manifest, JSON_THROW_ON_ERROR)), $this->root);
        $p = $manifest['versions'][1];
        if ($p['version'] !== '8.6' || $p['status'] !== 'draft' || $p['phpSource'] !== ['branch' => 'PHP-8.6', 'commit' => self::PIN]
            || $p['metadata']['sourceLock'] !== 'tools/8.6/source-lock-ga-v2.json'
            || $p['metadata']['changeTraceability'] !== 'tools/8.6/change-map-ga-v2.json') $errors[] = 'Invalid GA draft manifest';
        $lock ??= $this->read('tools/8.6/source-lock-ga-v2.json');
        if ($lock['phpSource']['commit'] !== self::PIN || $lock['phpSource']['tag'] !== 'php-8.6.0'
            || $lock['executables']['8.6'] !== '880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b') $errors[] = 'Unapproved source/executable identity';
        return $errors;
    }
    public function profileErrors(): array {
        $errors = $this->profileMetadataErrors();
        $lock = $this->read('tools/8.6/source-lock-ga-v2.json');
        $errors = [...$errors, ...$this->hashErrors([self::GA => '880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b',
            dirname(self::GA) . '/php8.dll' => '9b2a8bad3ff00c1aa57c83676cff79a830df63ed5af1fc3eb6d55de8b8ab57f9',
            self::PHP => '32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9'])];
        $auth = $this->read('docs/8.6/ga-finalization/build-source.json')['sourceHashes'];
        if (array_keys($lock['files']['8.6']) !== array_keys($this->read('tools/8.6/source-lock.json')['files']['8.6'])) $errors[] = 'Source inventory obligations changed';
        foreach ($lock['files']['8.6'] as $path => $hash) {
            if (($auth[$path] ?? null) !== $hash) $errors[] = 'Source lock not bound to authenticated Git: ' . $path;
            $errors = [...$errors, ...$this->hashErrors([self::SOURCES . '/' . $path => $hash])];
        }
        return $errors;
    }
    public function runnerErrors(): array {
        $transition = $this->read('tools/8.6/ga-runner-transition-v2.json'); $errors = [];
        if ($transition['contract'] !== 'php86-ga-runner-transition-v2' || $transition['replacements'] !== self::RUNNER_REPLACEMENTS || array_keys($transition['runners']) !== array_map(static fn($s) => 'tools/8.6/' . $s . '-ga-v2.php', ['integration-check', 'pfa-check', 'context-check', 'deprecation-check', 'final-coverage-expansion'])) return ['Unapproved GA runner transition'];
        foreach ($transition['runners'] as $path => $r) {
            $errors = [...$errors, ...$this->hashErrors([$r['predecessor'] => $r['before'], $path => $r['after']])];
            if (file_get_contents($this->root . '/' . $path) !== strtr(file_get_contents($this->root . '/' . $r['predecessor']), $transition['replacements'])) $errors[] = 'GA runner changed checks: ' . $path;
        }
        foreach (['deprecation-cases' => 'tests/fixtures/php/8.6/deprecation-cases.json'] as $name => $old) {
            if ($this->read('tools/8.6/' . $name . '-ga-v1.json')['cases'] !== $this->read($old)['cases']) $errors[] = 'Historical expectations changed';
        }
        return $errors;
    }
    public function gates(): array {
        $gates = Certification86::gates();
        $v2 = $this->read('docs/8.6/certification-gates-v2.json')['gates'];
        foreach (['historical-artifact-integrity85', 'current-compatibility85'] as $id) $gates[$id] = $v2[$id];
        return $gates;
    }
    public function commands(string $out): array {
        $php = [$this->root . '/' . self::PHP, '-n', '-d', 'memory_limit=1G'];
        $unit = [...$php, '-d', 'extension=' . $this->root . '/.audit/php85/ext/php_mbstring.dll', 'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress'];
        $pair = ['--php85=' . $this->root . '/' . self::PHP, '--php86=' . $this->root . '/' . self::GA];
        $sources = ['--sources85=' . $this->root . '/.audit/phase8d/sources85', '--sources86=' . $this->root . '/' . self::SOURCES];
        $filters = ['test-manifest' => 'ToolManifestTest', 'test-integration' => 'Php86IntegrationTest|VersionPackageConsistencyTest|RepositoryManifestTest',
            'test-fixtures' => 'PhpConformanceFixtureTest', 'test-compiler' => 'Php86CompilerContextualTest',
            'test-consumer' => 'Consumer|ConformanceVersionSupportTest', 'test-certification' => 'Certification86Test|GaCertificationTest|GaLocalObligationsTest'];
        $commands = [];
        foreach ($this->gates() as $id => [$kind]) {
            $command = [...$php, 'tools/8.6/certification-ga.php', '--gate', $id, '--output', $out . '/' . $id . '.json'];
            if ($id === 'compiler-contextual-reconciliation') { /* Fresh GA staged replay; legacy regression assertions also run in test-all. */ }
            elseif (isset($filters[$kind])) $command = [...$unit, '--filter', $filters[$kind]];
            elseif ($kind === 'test-all') $command = $unit;
            elseif ($kind === 'integration') $command = [...$php, 'tools/8.6/integration-check-ga-v2.php', ...$pair, ...$sources, '--report=' . $out . '/' . $id . '.json'];
            elseif ($kind === 'pfa') $command = [...$php, 'tools/8.6/pfa-check-ga-v2.php', ...$pair, '--sources=' . $this->root . '/' . self::SOURCES, '--report=' . $out . '/' . $id . '.json'];
            elseif (in_array($kind, ['context', 'deprecation'], true)) $command = [...$php, 'tools/8.6/' . $kind . '-check-ga-v2.php', ...$pair, ...$sources, '--report=' . $out . '/' . $id . '.json'];
            elseif ($kind === 'coverage') $command = [...$php, 'tools/8.6/final-coverage-expansion-ga-v2.php', ...$pair, ...$sources, '--report=' . $out . '/' . $id . '.json', '--coverage=' . $out . '/coverage.json'];
            elseif ($kind === 'historical') $command = [...$php, 'tools/8.5/certification.php', '--source-cache', $this->root . '/.audit/phase8d/source-cache', '--check'];
            elseif ($kind === 'composer') {
                $composer = Support::composer('composer', $this->root . '/' . self::PHP);
                $command = $composer[0] === $this->root . '/' . self::PHP
                    ? [...$php, '-d', 'extension=' . $this->root . '/.audit/php85/ext/php_openssl.dll', ...array_slice($composer, 1), 'validate', '--strict']
                    : [...$composer, 'validate', '--strict'];
            }
            elseif ($kind === 'provenance') $command = [...$php, 'tools/8.6/ga-finalization.php', '--verify-build'];
            elseif ($id === 'historical-artifact-integrity85') $command = [...$php, 'tools/evidence/historical-check.php', '--snapshot', $this->root . '/.audit/phase8d/historical', '--supplement', $this->root . '/.audit/phase8d/historical'];
            $commands[$id] = $command;
        }
        return $commands;
    }
    public function ordinary(): array {
        $path = 'docs/8.6/scanner-grammar-binding-closure/inventory.json';
        $cases = $this->read($path)['cases']; $rows = []; $errors = $this->profileErrors();
        if ($errors) return ['errors' => $errors];
        $d = new DifferentialInventory($this->root);
        foreach ($cases as $r) {
            $errors = [...$errors, ...$this->hashErrors([$r['fixture'] => $r['sha256']])];
            if ($errors) break;
            $command = [$this->root . '/' . self::GA, '-n', '-d', 'short_open_tag=1', '-d', 'zend.multibyte=0', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0', '-d', 'display_errors=1', '-d', 'log_errors=0', '-d', 'error_reporting=-1', '-l', $r['fixture']];
            $lint = Support::run($command, $this->root); $native = $d->native($r); $expected = $r['expected'];
            $matches = $lint['exit_code'] === $expected['lintExit'] && $d::diagnostics($lint['output']) === $expected['diagnostics'] && $native === array_intersect_key($expected, array_flip(['lexicalAccepted', 'structuralAccepted']));
            if (!$matches) $errors[] = 'Ordinary mismatch: ' . $r['fixture'];
            $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'expected' => $expected, 'native' => $native, 'command' => $command, 'lint' => $lint, 'matches' => $matches];
        }
        return ['inventory' => $path, 'inventorySha256' => hash_file('sha256', $this->root . '/' . $path), 'observations' => $rows, 'errors' => $errors];
    }
    public function replay(string $name): array {
        $prior = 'docs/8.6/ga-finalization/' . $name . '.json';
        $rows = []; $errors = $this->profileErrors();
        if ($errors) return ['errors' => $errors];
        foreach ($this->read($prior)['observations'] as $r) {
            if (isset($r['fixture'], $r['sha256'])) $errors = [...$errors, ...$this->hashErrors([$r['fixture'] => $r['sha256']])];
            if ($name === 'writable') {
                $result = Support::run($r['command'], $this->root);
                if ($result['exit_code'] !== $r['result']['exit_code'] || $result['output'] !== $r['result']['output']) $errors[] = 'Writable-stage outcome changed: ' . $r['id'];
                $r['result'] = $result;
            } else {
                foreach ($r as $key => $value) {
                    if (!is_array($value) || !isset($value['command'], $value['exit_code'], $value['output'])) continue;
                    $result = Support::run($value['command'], $this->root);
                    if ($result['exit_code'] !== $value['exit_code'] || $result['output'] !== $value['output']) $errors[] = 'Focused outcome changed: ' . $r['id'] . '/' . $key;
                    $r[$key] = ['command' => $value['command'], ...$result];
                }
            }
            $rows[] = $r;
        }
        return ['predecessor' => $prior, 'predecessorSha256' => hash_file('sha256', $this->root . '/' . $prior), 'observations' => $rows, 'errors' => $errors];
    }
    public static function gaDiagnostic(array $row): ?string {
        $old = $row['expectedDiagnostic'];
        if ($row['id'] === 'reconciliation/hook-retained' && $row['stage'] === 'lint'
            && $row['fixture'] === 'tests/fixtures/php/8.6/reconciliation/hook-retained.php'
            && $old === 'Unknown hook "unknown" for property class@anonymous::$x, expected "get" or "set"') {
            return str_replace('class@anonymous::', "class@anonymous\0" . $row['fixture'] . ':2$0::', $old);
        }
        return $old;
    }
    public static function stageMatches(array $prior, int $exit, string $output): bool {
        $expected = $prior['expectedAcceptance']; $diagnostic = self::gaDiagnostic($prior);
        return ($expected === null || (($exit === 0) === $expected))
            && ($diagnostic === null || $diagnostic === '' || str_contains($output, $diagnostic));
    }
    public function stagedContext(): array {
        $prior = $this->read('docs/8.6/ga-finalization/observations-exact.json');
        $errors = [...$this->profileErrors(), ...$this->hashErrors($prior['inputs'])];
        if ($errors) return ['errors' => $errors];
        $rows = [];
        foreach ($prior['observations'] as $old) {
            $r = Support::run($old['command'], $this->root);
            $matches = self::stageMatches($old, $r['exit_code'], $r['output']);
            if (!$matches || $r['exit_code'] !== $old['exitCode']) $errors[] = 'GA contextual observation differs: ' . $old['id'] . ':' . $old['stage'];
            $rows[] = ['id' => $old['id'], 'fixture' => $old['fixture'], 'stage' => $old['stage'], 'command' => $old['command'],
                'exitCode' => $r['exit_code'], 'output' => $r['output'], 'expectedAcceptance' => $old['expectedAcceptance'],
                'historicalExpectedDiagnostic' => $old['expectedDiagnostic'], 'gaExpectedDiagnostic' => self::gaDiagnostic($old),
                'historicalMatches' => $old['matches'], 'matches' => $matches];
        }
        return ['sourceCommit' => self::PIN, 'inputs' => $prior['inputs'], 'observations' => $rows,
            'diagnosticTransition' => ['fixture' => 'tests/fixtures/php/8.6/reconciliation/hook-retained.php',
                'source' => 'Zend/zend_compile.c', 'sourceSha256' => 'd2b57b75a6a5585a71be3d244428a8a13b7632b02e933ee9db44274b86c74f5f',
                'locations' => [9426, 9936], 'reason' => 'The hook diagnostic uses %pS and anonymous names encode NUL, source filename, line and counter. This single pinned fixture has its class on line 2 and counter 0. Retain the old failed display predicate; require the exact GA display name, unknown hook, property and get/set diagnostic. Acceptance and all fixture expectations are unchanged.'],
            'scope' => 'Fresh complete retained 410-stage GA observation inventory; legacy compiler-group regression assertions remain in the full suite. No predicate-completeness or folding-order closure.',
            'errors' => $errors];
    }
    public function stageReportErrors(array $r): array {
        $prior = $this->read('docs/8.6/ga-finalization/observations-exact.json'); $errors = [];
        if (($r['sourceCommit'] ?? null) !== self::PIN || ($r['inputs'] ?? null) !== $prior['inputs']
            || count($r['observations'] ?? []) !== count($prior['observations'])) return ['Incomplete/stale GA staged inventory'];
        foreach ($prior['observations'] as $i => $old) {
            $row = $r['observations'][$i];
            foreach (['id', 'fixture', 'stage', 'command', 'expectedAcceptance'] as $key) if (($row[$key] ?? null) !== $old[$key]) $errors[] = 'Altered staged obligation: ' . $old['id'];
            if ($row['historicalExpectedDiagnostic'] !== $old['expectedDiagnostic'] || $row['gaExpectedDiagnostic'] !== self::gaDiagnostic($old)
                || $row['historicalMatches'] !== $old['matches'] || $row['matches'] !== self::stageMatches($old, $row['exitCode'], $row['output'])) $errors[] = 'Altered staged predicate/result: ' . $old['id'];
            if (!$row['matches'] || $row['exitCode'] !== $old['exitCode']) $errors[] = 'GA contextual observation differs: ' . $old['id'] . ':' . $old['stage'];
        }
        if ($r['errors'] !== $errors) $errors[] = 'Staged result summary differs';
        return [...$errors, ...$this->hashErrors($prior['inputs'])];
    }
    public function gate(string $id): array {
        $gates = $this->gates(); if (!isset($gates[$id])) throw new \RuntimeException('Unknown GA obligation');
        [$kind] = $gates[$id]; $details = []; $errors = [];
        if ($id === 'manifest-schema' || $id === 'source-lock-integrity') $errors = [...$this->profileErrors(), ...$this->runnerErrors()];
        elseif ($kind === 'traceability') { $details = ChangeTraceability::validate($this->root, $this->read('tools/8.6/change-map-ga-v2.json')); $errors = $details['errors']; }
        elseif ($id === 'historical-preservation' || $id === 'evidence-freshness') $errors = [...$this->preservation(), ...$this->runnerErrors()];
        elseif ($id === 'ordinary-differential') { $details = $this->ordinary(); $errors = $details['errors']; }
        elseif ($id === 'compiler-contextual-reconciliation') { $details = $this->stagedContext(); $errors = $details['errors']; }
        elseif ($id === 'blocker-dispositions') {
            $details = $this->read(self::OUT . '/blockers-v2.json');
            $original = $this->read('docs/8.6/source-audit-dispositions.json')['blockers'];
            $errors = (new Certification86($this->root))->blockerErrors($original);
            if (array_column($details['blockers'], 'originalRequirement') !== $original || array_column($details['blockers'], 'id') !== Certification86::BLOCKERS) $errors[] = 'Lost original blocker obligation';
            foreach ($details['blockers'] as $row) if ($row['blocksCertification'] !== true || $row['boundedDispositionPermittedNow'] !== false || !$row['remainingRequirement']) $errors[] = 'Unjustified GA blocker closure';
        } elseif ($id === 'documentation-json') {
            foreach (array_keys($this->inventory()) as $p) if (str_ends_with($p, '.json') && !str_starts_with($p, 'vendor/')) $this->read($p);
            $errors = $this->runnerErrors();
        } elseif ($id === 'parser-scanner-correspondence') {
            $details = (new GaLocalObligations($this->root))->derive();
            $errors = $details['errors'];
            if ($details !== $this->read(self::OUT . '/local-obligations-v1.json')) $errors[] = 'Stale local correspondence evidence';
        } elseif ($kind === 'audit') {
            $errors = $this->profileErrors();
            $details['retainedGaSourceCounts'] = $this->read('docs/8.6/ga-finalization/source-counts.json');
            $details['remainingInventory'] = 'docs/8.6/local-source-context-remediation/source-review.json';
            $details['retainedCandidateDispositions'] = $this->read('docs/8.6/source-completeness-ledger.json')['rfcs'];
            $errors[] = 'PHP86-013: retained RFC candidates have unresolved implementation/component/absence/reversion obligations at GA; no RFC-only permission is granted.';
        } elseif (str_starts_with($kind, 'blocked:')) {
            $blocker = substr($kind, 8);
            if (in_array($id, ['ast-binding', 'scanner-state-closure'], true)) $details = $this->replay('focused');
            if ($id === 'writable-target-validity') $details = $this->replay('writable');
            $errors = $details['errors'] ?? [];
            $details['original'] = array_values(array_filter($this->read('docs/8.6/source-audit-dispositions.json')['blockers'], static fn($r) => $r['id'] === $blocker));
            $details['gaDisposition'] = array_values(array_filter($this->read(self::OUT . '/blockers-v2.json')['blockers'], static fn($r) => $r['id'] === $blocker));
            $errors[] = $blocker . ': material closure obligations remain; see reviewed GA disposition. Observations do not waive closure.';
        } elseif ($id === 'current-compatibility85') return $this->compatibility();
        else throw new \RuntimeException('Obligation requires its external command: ' . $id);
        return ['contract' => self::CONTRACT, 'sourceCommit' => self::PIN, 'gate' => $id, 'details' => $details, 'errors' => $errors, 'certificationGranted' => false];
    }
    public function compatibilityInventory(): array {
        $inputs = (new CompatibilityEvidence($this->root))->inventory();
        // Like the existing certification successor, bind the generated summary
        // as an independently derived output, not a circular execution input.
        // The legacy compatibility-v1 inventory and checker remain unchanged.
        unset($inputs[self::OUT . '.md']);
        // Historical snapshot dependencies are computed paths in the legacy
        // tests; literal-reference discovery alone cannot find them.
        foreach (Support::files($this->root . '/' . self::OUT) as $file) {
            $path = substr($file, strlen($this->root) + 1);
            if (str_starts_with($path, self::OUT . '/runs/')) continue;
            $inputs[$path] ??= ['sha256' => hash_file('sha256', $file), 'reason' => 'GA successor authority, preservation, source proof or historical test snapshot dependency'];
        }
        ksort($inputs); return $inputs;
    }
    public function environment(): array {
        $hashes = []; $absences = []; $errors = [];
        $sources = $this->read('tools/8.6/source-lock.json')['files']['8.5']
            + $this->read('tools/8.6/context-source-lock.json')['files']['8.5']
            + $this->read('tests/fixtures/php/8.6/deprecation-cases.json')['sourceHashes']['8.5'];
        foreach ($sources as $path => $expected) {
            $p = '.audit/phase8d/sources85/' . $path;
            if ($expected === null) {
                $absences[$p] = !file_exists($this->root . '/' . $p);
                if (!$absences[$p]) $errors[] = 'Unexpected PHP 8.5 source: ' . $path;
            } else {
                $hashes[$p] = is_file($this->root . '/' . $p) ? hash_file('sha256', $this->root . '/' . $p) : null;
                if ($hashes[$p] !== $expected) $errors[] = 'PHP 8.5 source mismatch: ' . $path;
            }
        }
        $comparison = $this->read('docs/8.5/source-correspondence.json');
        foreach ($comparison['files'] as $row) foreach (['source_pin' => 'pin_sha256', 'release_revision' => 'release_sha256'] as $pin => $field) {
            $p = '.audit/phase8d/source-cache/' . $comparison[$pin] . '/' . $row['file'];
            $hashes[$p] = is_file($this->root . '/' . $p) ? hash_file('sha256', $this->root . '/' . $p) : null;
            if ($hashes[$p] !== $row[$field]) $errors[] = 'PHP 8.5 comparison cache mismatch: ' . $p;
        }
        foreach ([self::PHP, self::GA] as $exe) {
            $paths = [$this->root . '/' . $exe, ...glob($this->root . '/' . dirname($exe) . '/*.dll')];
            if ($exe === self::PHP) foreach (['mbstring', 'openssl'] as $extension) $paths[] = $this->root . '/.audit/php85/ext/php_' . $extension . '.dll';
            foreach ($paths as $file) {
                $p = substr(str_replace('\\', '/', $file), strlen($this->root) + 1);
                $hashes[$p] = is_file($file) ? hash_file('sha256', $file) : null;
                if ($hashes[$p] === null) $errors[] = 'Missing runtime input: ' . $p;
            }
        }
        ksort($hashes); ksort($absences);
        return ['hashes' => $hashes, 'absences' => $absences, 'errors' => [...$errors, ...$this->profileErrors()]];
    }
    public function compatibility(): array {
        // The same bounded current-v1 inventory and command obligations, with an
        // explicit GA environment/source-lock and paired-matrix transition.
        $e = new CompatibilityEvidence($this->root);
        $config = CompatibilityEvidence::defaults($this->root);
        $config['php86'] = $this->root . '/' . self::GA;
        $config['sources85'] = $this->root . '/.audit/phase8d/sources85';
        $config['sources86'] = $this->root . '/' . self::SOURCES;
        $config['source-cache'] = $this->root . '/.audit/phase8d/source-cache';
        $directory = $this->root . '/' . self::OUT . '/runs/' . ($GLOBALS['gaRevision'] ?? 'invalid') . '/compatibility';
        if (file_exists($directory)) throw new \RuntimeException('Compatibility revision exists');
        mkdir($directory, 0777, true);
        $inputs = $this->compatibilityInventory(); $environment = $this->environment();
        $commands = $e->commands($config, $directory); $results = []; $errors = $environment['errors'];
        $commands['source-lock86'] = [$config['php85'], '-n', 'tools/8.6/certification-ga.php', '--gate', 'source-lock-integrity'];
        $offset = array_search('tools/8.6/integration-check.php', $commands['paired-boundaries'], true);
        if ($offset === false) throw new \RuntimeException('Missing predecessor boundary command');
        $commands['paired-boundaries'][$offset] = 'tools/8.6/integration-check-ga-v2.php';
        foreach ($commands as $id => $command) {
            $log = $directory . '/' . $id . '.log';
            $r = Support::run($command, $this->root, $log);
            $results[$id] = ['command' => $command, 'exit_code' => $r['exit_code'], 'log' => substr($log, strlen($this->root) + 1), 'sha256' => hash_file('sha256', $log), 'totals' => CompatibilityEvidence::totals($r['output'])];
            if ($r['exit_code'] !== 0) $errors[] = 'Current compatibility obligation failed: ' . $id;
        }
        if ($inputs !== $this->compatibilityInventory()) $errors[] = 'Current compatibility dependencies changed during execution';
        if ($environment !== $this->environment()) $errors[] = 'Current compatibility environment changed during execution';
        return ['contract' => 'current-compatibility-ga-v2', 'predecessorContract' => CompatibilityEvidence::CONTRACT,
            'claim' => CompatibilityEvidence::CLAIM, 'configuration' => $config, 'dependencies' => $inputs, 'environment' => $environment,
            'commands' => $results, 'errors' => $errors, 'certificationGranted' => false];
    }
    /** Read-only verification reconstructs inventories, commands and decisions. */
    public function verify(array $r, string $path): array {
        $errors = [];
        if (($r['contract'] ?? null) !== self::CONTRACT || ($r['sourceCommit'] ?? null) !== self::PIN
            || ($r['originalObligations'] ?? null) !== 36 || ($r['hybridObligations'] ?? null) !== 2
            || ($r['certificationGranted'] ?? null) !== false || ($r['decision'] ?? null) !== 'NOT SIGNED OFF — RELEASE BLOCKED'
            || ($r['blockers'] ?? null) !== Certification86::BLOCKERS) return ['Invalid GA contract, accounting, blockers or claim'];
        if (array_keys($r['results'] ?? []) !== array_keys($this->gates())) return ['Missing/reordered GA obligations'];
        if (!preg_match('~^' . self::OUT . '/runs/[a-z0-9][a-z0-9_-]*/evidence\.json$~D', $path)
            || dirname($path) !== ($r['output'] ?? null)) return ['Invalid revision path'];
        $receipt = $this->read($r['output'] . '/run-receipt.json');
        if ($receipt !== ['contract' => self::CONTRACT, 'sha256' => hash('sha256', Support::json($r))]
            || hash_file('sha256', $this->root . '/' . $path) !== $receipt['sha256']) $errors[] = 'Evidence differs from run receipt';
        if (($r['inputsUnchanged'] ?? null) !== true || ($r['inputs'] ?? null) !== $this->inventory()) $errors[] = 'Stale/incomplete current dependency scope';
        $expected = $this->commands($r['output']);
        if (array_keys($r['results'] ?? []) !== array_keys($expected)) return [...$errors, 'Missing/reordered GA obligations'];
        foreach ($expected as $id => $command) {
            $row = $r['results'][$id]; $kind = $this->gates()[$id][0];
            $status = $row['exit_code'] === 0 ? 'passed' : (str_starts_with($kind, 'blocked:') ? 'blocked' : 'failed');
            if ($row['command'] !== $command || $row['cwd'] !== $this->root || $row['originalObligation'] !== $this->gates()[$id]
                || $row['status'] !== $status || !is_int($row['exit_code']) || $row['log'] !== $r['output'] . '/' . $id . '.log') $errors[] = 'Execution identity mismatch: ' . $id;
            if (str_starts_with($kind, 'blocked:') && $row['status'] !== 'blocked') $errors[] = 'Unresolved closure falsely passed: ' . $id;
            $errors = [...$errors, ...$this->hashErrors([$row['log'] => $row['logSha256']])];
            if ($this->read($r['output'] . '/' . $id . '.receipt.json') !== $row) $errors[] = 'Execution receipt mismatch: ' . $id;
            $log = file_get_contents($this->root . '/' . $row['log']);
            if ($kind === 'test-all' && $row['exit_code'] === 0 && CompatibilityEvidence::totals($log) === null) $errors[] = 'Missing full PHPUnit totals';
            $artifact = $r['output'] . '/' . $id . '.json';
            if (is_file($this->root . '/' . $artifact)) {
                $data = $this->read($artifact);
                if (isset($data['errors']) && (($data['errors'] === []) !== ($row['exit_code'] === 0))) $errors[] = 'Report/result inconsistency: ' . $id;
                if ($id === 'compiler-contextual-reconciliation' && isset($data['details']['observations'])) $errors = [...$errors, ...$this->stageReportErrors($data['details'])];
            }
        }
        $actual = [];
        foreach (Support::files($this->root . '/' . $r['output']) as $file) {
            $relative = substr($file, strlen($this->root) + 1);
            if (in_array($relative, [$path, $r['output'] . '/run-receipt.json'], true)) continue;
            $actual[$relative] = hash_file('sha256', $file);
        }
        if ($actual !== $r['artifacts']) $errors[] = 'Run artifact inventory changed';
        $summary = $r['summary'] ?? [];
        if ($summary !== ['path' => self::OUT . '.md', 'sha256' => hash('sha256', $this->reportMarkdown($r))]
            || !is_file($this->root . '/' . self::OUT . '.md')
            || hash_file('sha256', $this->root . '/' . self::OUT . '.md') !== ($summary['sha256'] ?? null)) $errors[] = 'Missing, stale or falsified certification summary';
        $compatibility = $r['output'] . '/current-compatibility85.json';
        if (is_file($this->root . '/' . $compatibility)) $errors = [...$errors, ...$this->compatibilityErrors($this->read($compatibility), $r['output'] . '/compatibility')];
        $local = (new GaLocalObligations($this->root))->derive();
        if ($local !== $this->read(self::OUT . '/local-obligations-v1.json')) $errors[] = 'Local source proof is stale';
        return [...$errors, ...$local['errors'], ...$this->preservation(), ...$this->profileErrors(), ...$this->runnerErrors()];
    }
    public function compatibilityErrors(array $r, string $directory): array {
        $e = new CompatibilityEvidence($this->root); $errors = [];
        if (($r['contract'] ?? null) !== 'current-compatibility-ga-v2' || ($r['predecessorContract'] ?? null) !== CompatibilityEvidence::CONTRACT
            || ($r['claim'] ?? null) !== CompatibilityEvidence::CLAIM || ($r['certificationGranted'] ?? null) !== false) return ['Invalid compatibility successor identity'];
        if (($r['dependencies'] ?? null) !== $this->compatibilityInventory()) $errors[] = 'Stale compatibility dependencies';
        $environment = $this->environment();
        if (($r['environment'] ?? null) !== $environment) $errors[] = 'Changed compatibility source/runtime environment';
        $errors = [...$errors, ...$environment['errors']];
        $config = CompatibilityEvidence::defaults($this->root);
        $config['php86'] = $this->root . '/' . self::GA;
        $config['sources85'] = $this->root . '/.audit/phase8d/sources85';
        $config['sources86'] = $this->root . '/' . self::SOURCES;
        $config['source-cache'] = $this->root . '/.audit/phase8d/source-cache';
        if ($r['configuration'] !== $config) $errors[] = 'Unapproved compatibility environment';
        $commands = $e->commands($config, $this->root . '/' . $directory);
        $commands['source-lock86'] = [$config['php85'], '-n', 'tools/8.6/certification-ga.php', '--gate', 'source-lock-integrity'];
        $offset = array_search('tools/8.6/integration-check.php', $commands['paired-boundaries'], true);
        $commands['paired-boundaries'][$offset] = 'tools/8.6/integration-check-ga-v2.php';
        if (array_keys($r['commands']) !== array_keys($commands)) return [...$errors, 'Incomplete compatibility command inventory'];
        $failures = [];
        foreach ($commands as $id => $command) {
            $row = $r['commands'][$id]; $log = $directory . '/' . $id . '.log';
            if ($row['command'] !== $command || $row['log'] !== $log) $errors[] = 'Compatibility command changed: ' . $id;
            $errors = [...$errors, ...$this->hashErrors([$log => $row['sha256']])];
            $output = file_get_contents($this->root . '/' . $log);
            if ($row['totals'] !== CompatibilityEvidence::totals($output)) $errors[] = 'Compatibility totals changed';
            if ($row['exit_code'] !== 0) $failures[] = 'Current compatibility obligation failed: ' . $id;
        }
        if ($r['errors'] !== $failures) $errors[] = 'Unexplained compatibility outcome';
        $boundaries = $this->read($directory . '/boundaries.json');
        if ($r['commands']['paired-boundaries']['exit_code'] === 0 && ($boundaries['errors'] !== [] || count($boundaries['observations']) < 72)) $errors[] = 'Missing paired-boundary evidence';
        return $errors;
    }
    public function reportMarkdown(array $r): string {
        $text = rtrim(file_get_contents($this->root . '/' . self::OUT . '/report-template.md')) . "\n\n";
        $counts = array_count_values(array_column($r['results'], 'status'));
        $text .= "## Final executed gate results\n\n";
        $text .= 'The current **' . self::CONTRACT . '** run executed all **36 original + 2 hybrid = 38** obligations: **'
            . ($counts['passed'] ?? 0) . ' passed, ' . ($counts['failed'] ?? 0) . ' failed, ' . ($counts['blocked'] ?? 0) . " blocked**.\n\n";
        $prefix = 'ga-certification-remediation/runs/' . basename($r['output']);
        $text .= '[Machine-readable evidence](' . $prefix . '/evidence.json) retains every original obligation, command, exit code, log, input digest and artifact digest. '
            . "A passing bounded gate does not close a separate material blocker.\n\n";
        $text .= "| Obligation | Result | Exit |\n|---|---|---|\n";
        foreach ($r['results'] as $id => $row) $text .= '| [' . $id . '](' . $prefix . '/' . $id . '.log) | ' . $row['status'] . ' | ' . $row['exit_code'] . " |\n";
        $text .= "\n## Full regression and independent verification\n\n";
        $row = $r['results']['full-regression'];
        $totals = CompatibilityEvidence::totals(file_get_contents($this->root . '/' . $row['log']));
        $text .= $totals !== null && $row['exit_code'] === 0
            ? '**PHPUnit passed: ' . number_format($totals['tests']) . ' tests, ' . number_format($totals['assertions']) . " assertions.**\n\n"
            : "**The full PHPUnit regression did not pass.** Its complete output is linked above.\n\n";
        $text .= 'Verify this exact revision without writing evidence using `php tools/8.6/certification-ga.php --check --report=' . $r['output'] . '/evidence.json`. '
            . 'The independent verifier reconstructs the 38-command inventory, source and runtime identities, preservation transition, current compatibility dependencies, parser/scanner mappings, local source proof and this generated report. '
            . "Evidence verification authenticates failed and blocked outcomes too; it does not grant certification.\n\n";
        $text .= 'The separate `--claims --report=' . $r['output'] . '/evidence.json` check must refuse sign-off while any mandatory obligation or material blocker remains. '
            . "This generated summary is an output bound by the evidence receipt; its template and all execution dependencies are protected inputs.\n";
        return $text;
    }
}
