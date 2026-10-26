<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/CertificationCurrent.php';
require_once __DIR__ . '/HistoricalReleaseDependency.php';

/** Immutable revision orchestration and separate, non-writing verification. */
final class CertificationCurrentRun
{
    public function __construct(private readonly string $root) {}

    private function runtimeHashes(array $config): array
    {
        $paths = [$config['php85'], $config['php86'], $config['exact']];
        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([$config['php85'], $config['php86'], $config['exact']] as $binary) foreach (glob(dirname($binary) . '/*.dll') ?: [] as $dll) $paths[] = str_replace('\\', '/', $dll);
            foreach (['mbstring', 'openssl', 'ast'] as $extension) $paths[] = dirname($config['php85']) . '/ext/php_' . $extension . '.dll';
        }
        $hashes = [];
        foreach (array_unique($paths) as $path) {
            if (!is_file($path)) throw new \RuntimeException('Missing runtime dependency: ' . $path);
            $hashes[$path] = hash_file('sha256', $path);
        }
        ksort($hashes); return $hashes;
    }

    public static function release85Ids(): array
    {
        return ['composer-validate', 'manifest-schema', 'manifest-regressions', 'consumer-contract', 'expression-generation', 'phpunit',
            'grammar-coverage', 'lexer-coverage', 'ordinary-differential', 'explicit-ast', 'systematic-structure', 'declaration-folding',
            'phase6-matrices', 'phase6-matrix-freshness', 'scanner-product', 'lexer-short-enabled', 'lexer-short-disabled',
            'positive-report-freshness', 'negative-report-freshness', 'compiler-source-hashes', 'parser-reconciliation',
            'phase6-evidence-freshness', 'interpolation-binding', 'diagnostic-predicates', 'source-correspondence', 'final-certification', 'diff-whitespace'];
    }

    /** Narrow versioned aggregate transition; any other failure remains a blocker. */
    public static function release85Errors(array $rows, string $finalLog, string $lexerLog): array
    {
        if (array_column($rows, 'gate') !== self::release85Ids()) return ['Incomplete PHP 8.5 release obligation inventory'];
        $errors = [];
        foreach ($rows as $row) {
            $expected = in_array($row['gate'], ['final-certification', 'lexer-coverage'], true) ? 1 : 0;
            if (($row['exit_code'] ?? null) !== $expected) $errors[] = 'Unmet PHP 8.5 obligation: ' . $row['gate'];
        }
        if (trim($finalLog) !== 'Stale certification artifact: docs/8.5/final-evidence.json') $errors[] = 'Historical aggregate has an unexplained failure';
        return [...$errors, ...HistoricalReleaseDependency::executionErrors($lexerLog)];
    }

    private function snapshot(string $path, array $inputs, bool $projection): void
    {
        if (file_exists($path) || is_link(dirname($path))) throw new \RuntimeException('Snapshot exists or unsafe parent');
        mkdir($path, 0777, true);
        foreach ($inputs as $p => $hash) {
            $source = $this->root . '/' . $p;
            if ($projection && isset(Preservation86::CHANGES[$p])) {
                $source = $this->root . '/.audit/phase7b-certification/' . $p;
                $hash = Preservation86::CHANGES[$p][0];
            }
            if (!is_file($source) || hash_file('sha256', $source) !== $hash) throw new \RuntimeException('Snapshot input mismatch: ' . $p);
            $target = $path . '/' . $p;
            if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
            if (!copy($source, $target) || hash_file('sha256', $target) !== $hash) throw new \RuntimeException('Snapshot copy mismatch: ' . $p);
        }
    }

    private function command(array $config, string $mode): array
    {
        $base = [$config['php85'], '-n', 'tools/8.6/certification.php', '--' . $mode];
        if ($mode === 'prepare') foreach (['php85', 'php86', 'sources85', 'sources86', 'source-cache', 'composer'] as $key) array_push($base, '--' . $key, $config[$key]);
        return $base;
    }

    private function auditCommand(array $config): array
    {
        return [$config['php85'], '-n', 'tools/8.6/source-completeness-audit.php', '--check', '--sources85', $config['sources85'], '--sources86', $config['sources86']];
    }

    public static function auditErrors(array $audit, array $inputs, string $snapshot, array $original): array
    {
        if (($audit['contract'] ?? '') !== 'php86-current-source-audit-v2' || ($audit['inputs'] ?? []) !== $inputs
            || ($audit['projection'] ?? []) !== Preservation86::CHANGES || ($audit['snapshot'] ?? '') !== $snapshot
            || ($audit['correspondence'] ?? []) !== $original || ($audit['certificationGranted'] ?? null) !== false) return ['Missing, modified or stale full correspondence'];
        return [];
    }

    public function commandErrors(array $report): array
    {
        $c = new Certification86($this->root); $config = $report['configuration']; $out = $report['output']; $snap = $report['snapshots']; $errors = [];
        $expected = [];
        foreach (['prepare', 'check', 'claims', 'run'] as $mode) $expected['legacy-' . $mode] = [$this->command($config, $mode), $snap['legacy'], in_array($mode, ['prepare', 'check'], true) ? 0 : 1];
        $expected['current-audit'] = [$this->auditCommand($config), $snap['audit'], 0];
        $expected['release85'] = [[$config['php85'], 'tools/grammar-release.php', '--php', $config['php85'], '--composer', $config['composer'],
            '--source-directory', $this->root . '/.audit', '--source-cache', $config['source-cache'], '--logs', $c->path($out . '/release85')], $snap['release85'], 1];
        if (array_keys($report['commands'] ?? []) !== array_keys($expected)) $errors[] = 'Missing execution receipt';
        foreach ($expected as $id => [$command, $cwd, $exit]) {
            $row = $report['commands'][$id] ?? []; $log = $out . '/' . $id . '.log';
            if (($row['command'] ?? null) !== $command || ($row['cwd'] ?? null) !== $cwd || ($row['log'] ?? null) !== $log
                || !is_file($c->path($log)) || ($row['sha256'] ?? null) !== hash_file('sha256', $c->path($log))) $errors[] = 'Incorrect command execution evidence: ' . $id;
            if (($row['exit_code'] ?? null) !== $exit) $errors[] = 'Unsuccessful required execution: ' . $id;
        }
        $results = $c->read($out . '/release85/results.json'); $log = file_get_contents($c->path($out . '/release85.log'));
        foreach ($results as $row) if (!str_contains($log, $row['gate'] . ': ' . ($row['exit_code'] === 0 ? 'PASS' : 'FAIL'))) $errors[] = 'Release outcome lacks controller observation: ' . $row['gate'];
        return $errors;
    }

    public function generate(string $revision, array $config): string
    {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/D', $revision)) throw new \RuntimeException('Safe new revision required');
        $c = new Certification86($this->root); $e = new CertificationCurrent($this->root); $p = new Preservation86($this->root);
        $transition = $p->derive(); $errors = [...$p->verify($transition), ...CertificationCurrent::profileErrors($config)];
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $out = CertificationCurrent::OUT . '/runs/' . $revision;
        $snap = $this->root . '/.audit/certification-v2-' . $revision;
        if (file_exists($c->path($out)) || file_exists($snap) || file_exists($snap . '-audit') || file_exists($snap . '-85')) throw new \RuntimeException('Revision exists; never overwrite evidence');
        $inputs = $e->inventory(); $runtime = $this->runtimeHashes($config);
        mkdir($c->path($out), 0777, true);
        $c->write($out . '/transition.json', $transition);
        foreach (['legacy' => [$snap, false], 'audit' => [$snap . '-audit', true], 'release85' => [$snap . '-85', false]] as $kind => [$path, $projection]) {
            echo 'Copying ' . $kind . "\n"; flush(); $this->snapshot($path, $inputs, $projection);
        }
        $commands = [];
        $run = function(string $id, array $command, string $cwd) use ($c, $out, &$commands): array {
            echo 'Running ' . $id . "\n"; flush();
            $log = $out . '/' . $id . '.log'; $r = Support::run($command, $cwd, $c->path($log));
            $commands[$id] = ['command' => $command, 'cwd' => $cwd, 'exit_code' => $r['exit_code'], 'log' => $log, 'sha256' => hash_file('sha256', $c->path($log))];
            $c->write($out . '/commands.json', $commands); echo $id . ': ' . $r['exit_code'] . "\n"; flush(); return $r;
        };
        $run('legacy-prepare', $this->command($config, 'prepare'), $snap);
        $run('legacy-check', $this->command($config, 'check'), $snap);
        $run('legacy-claims', $this->command($config, 'claims'), $snap);
        $run('legacy-run', $this->command($config, 'run'), $snap);
        $run('current-audit', $this->auditCommand($config), $snap . '-audit');
        // Configure only this release process and its children; no machine ini or PATH mutation.
        $ini = $c->path($out . '/release85.ini');
        $settings = "date.timezone=UTC\n";
        if (PHP_OS_FAMILY === 'Windows') foreach (['mbstring', 'openssl', 'ast'] as $extension) $settings .= 'extension="' . dirname($config['php85']) . '/ext/php_' . $extension . '.dll"' . "\n";
        file_put_contents($ini, $settings);
        $oldIni = getenv('PHPRC'); $oldCount = getenv('GIT_CONFIG_COUNT'); $oldKey = getenv('GIT_CONFIG_KEY_0'); $oldValue = getenv('GIT_CONFIG_VALUE_0');
        try {
            putenv('PHPRC=' . $ini); putenv('GIT_CONFIG_COUNT=1'); putenv('GIT_CONFIG_KEY_0=safe.directory'); putenv('GIT_CONFIG_VALUE_0=' . $this->root);
            $run('release85', [$config['php85'], 'tools/grammar-release.php', '--php', $config['php85'], '--composer', $config['composer'],
                '--source-directory', $this->root . '/.audit', '--source-cache', $config['source-cache'], '--logs', $c->path($out . '/release85')], $snap . '-85');
        } finally {
            foreach (['PHPRC' => $oldIni, 'GIT_CONFIG_COUNT' => $oldCount, 'GIT_CONFIG_KEY_0' => $oldKey, 'GIT_CONFIG_VALUE_0' => $oldValue] as $key => $value) putenv($value === false ? $key : $key . '=' . $value);
        }
        // Export actual legacy outputs; all old workspace records remain untouched.
        $sc = new Certification86($snap); $legacy = $sc->read(Certification86::REPORT);
        foreach ([Certification86::REPORT, ...array_keys($legacy['artifacts'])] as $file) {
            $dest = $c->path($out . '/legacy/' . $file); if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
            if (!copy($snap . '/' . $file, $dest)) throw new \RuntimeException('Evidence export failed');
        }
        $audit = $c->read('docs/8.6/source-completeness-ledger.json');
        $c->write($out . '/audit.json', ['contract' => 'php86-current-source-audit-v2', 'inputs' => $inputs, 'snapshot' => $snap . '-audit',
            'projection' => Preservation86::CHANGES, 'correspondence' => $audit, 'certificationGranted' => false,
            'scope' => 'Recomputed complete original correspondence ledger, including every unresolved obligation; current dependency/preservation transition is independently checked. Three restored documents affect only the historical preservation comparison.']);
        $draft = ['contract' => CertificationCurrent::CONTRACT, 'evidenceKind' => 'current-assessment', 'revision' => $revision, 'output' => $out,
            'configuration' => $config, 'inputs' => $inputs, 'runtime' => $runtime, 'runtimeUnchanged' => $runtime === $this->runtimeHashes($config),
            'snapshots' => ['legacy' => $snap, 'audit' => $snap . '-audit', 'release85' => $snap . '-85'],
            'commands' => $commands, 'release85Snapshot' => (new CertificationCurrent($snap . '-85'))->inventory(),
            'generatedAt' => gmdate('c'), 'inputsUnchanged' => $inputs === $e->inventory(),
            'certificationGranted' => false, 'grammarComplete' => false, 'wholeSourceValidatorComplete' => false, 'historicalCertificationRenewed' => false];
        $c->write($out . '/lexer-transition.json', (new HistoricalReleaseDependency($this->root))->derive($snap . '-85'));
        $verification = $this->checks($draft);
        $draft['checks'] = $verification['checks']; $draft['verificationErrors'] = $verification['errors'];
        $draft['blockers'] = $c->read(BlockerClosure::ROOT . '/dispositions.json')['blockers'];
        $draft['gates'] = CertificationCurrent::gates($legacy['gates'], $draft['checks'], $draft['blockers'], CertificationCurrent::verificationCommand($draft));
        $draft['prerequisites'] = ['historical-artifact-integrity85' => $draft['checks']['historical-integrity'], 'current-compatibility85' => $draft['checks']['current-compatibility']];
        $paths = []; foreach (Support::files($c->path($out)) as $file) $paths[] = substr($file, strlen($this->root) + 1);
        // Bind the selected compatibility revision and its independent receipt/logs too.
        foreach (Support::files(dirname($c->path($config['compatibility-report']))) as $file) $paths[] = substr($file, strlen($this->root) + 1);
        $draft['artifacts'] = $c->hashes($paths);
        $c->write($out . '/evidence.json', $draft);
        $c->write($out . '/receipt.json', ['sha256' => hash_file('sha256', $c->path($out . '/evidence.json')), 'scope' => 'Local controller receipt, not a signature. Generation is not independent verification.']);
        return $out . '/evidence.json';
    }

    public function checks(array $report): array
    {
        $c = new Certification86($this->root); $p = new Preservation86($this->root); $out = $report['output']; $config = $report['configuration']; $errors = [];
        $errors['preservation'] = $p->verify($c->read($out . '/transition.json'));
        $errors['audit'] = $p->projectionErrors($report['snapshots']['audit'], $report['inputs']);
        $audit = $c->read($out . '/audit.json');
        $errors['audit'] = [...$errors['audit'], ...self::auditErrors($audit, $report['inputs'], $report['snapshots']['audit'], $c->read('docs/8.6/source-completeness-ledger.json'))];
        $projected = (new CertificationCurrent($report['snapshots']['audit']))->inventory();
        $expectedProjection = $report['inputs']; foreach (Preservation86::CHANGES as $path => [$before]) $expectedProjection[$path] = $before;
        if ($projected !== $expectedProjection) $errors['audit'][] = 'Projection dependency membership changed';
        if ($errors['audit'] === []) {
            $r = Support::run($this->auditCommand($config), $report['snapshots']['audit']);
            if ($r['exit_code'] !== 0) $errors['audit'][] = 'Current projected audit recomputation failed: ' . $r['output'];
        }
        $historical = HistoricalEvidence::verify($this->root, $config['historical-snapshot'], $config['historical-supplement']);
        $errors['historical-integrity'] = $historical['complete'] ? [] : ($historical['errors'] ?: ['Historical integrity incomplete']);
        $compat = new CompatibilityEvidence($this->root);
        $errors['current-compatibility'] = $compat->verify($c->read($config['compatibility-report']), dirname($c->path($config['compatibility-report'])));
        // Exact context verification already invokes the complete build verifier.
        // Share that successful proof only for the identical selected executable.
        $contextErrors = (new ContextEvidence($this->root))->verify($config['context-report'], $config['profile']);
        $contextRecord = $c->read($config['context-report']); $build = $c->read('docs/8.6/exact-pin-build/build.json');
        $sameBinary = realpath($config['exact']) !== false && realpath($config['exact']) === realpath($contextRecord['configuration']['php86'] ?? '')
            && realpath($config['exact']) === realpath($build['binary']);
        if (!$sameBinary) $contextErrors[] = 'Context does not authenticate the selected exact executable';
        $errors['exact-provenance'] = $contextErrors === [] && $config['profile'] === ContextEvidence::EXACT
            ? [] : $c->provenanceErrors($build, $config['exact']);
        $errors['exact-context'] = $contextErrors;
        $errors['blockers'] = (new BlockerClosure($this->root))->errors();
        $sc = new Certification86($report['snapshots']['legacy']); $legacy = $sc->read(Certification86::REPORT);
        $errors['legacy-ledger'] = $sc->check($legacy);
        // No arbitrary historical snapshot may be substituted for a current execution.
        $expected = $report['inputs']; unset($expected[Certification86::REPORT]);
        foreach (array_keys($expected) as $path) if (str_starts_with($path, Certification86::OUTPUT . '/')) unset($expected[$path]);
        $errors['legacy-ledger'] = [...$errors['legacy-ledger'], ...$sc->verifyHashes($expected), ...$this->commandErrors($report)];
        $actual = (new CertificationCurrent($report['snapshots']['legacy']))->inventory(); unset($actual[Certification86::REPORT]);
        foreach (array_keys($actual) as $path) if (str_starts_with($path, Certification86::OUTPUT . '/')) unset($actual[$path]);
        if ($actual !== $expected) $errors['legacy-ledger'][] = 'Legacy snapshot is not the current dependency set';
        if ($legacy !== $c->read($out . '/legacy/' . Certification86::REPORT)) $errors['legacy-ledger'][] = 'Legacy export differs from execution';
        $lexerLog = file_get_contents($c->path($out . '/release85/lexer-coverage.log'));
        $errors['release85'] = [...self::release85Errors($c->read($out . '/release85/results.json'), file_get_contents($c->path($out . '/release85/final-certification.log')), $lexerLog),
            ...(new HistoricalReleaseDependency($this->root))->verify($c->read($out . '/lexer-transition.json'), $report['snapshots']['release85'], $lexerLog)];
        $releaseSnapshot = (new CertificationCurrent($report['snapshots']['release85']))->inventory();
        if ($releaseSnapshot !== ($report['release85Snapshot'] ?? [])) $errors['release85'][] = 'PHP 8.5 execution snapshot changed';
        foreach ($report['inputs'] as $path => $hash) if (!str_starts_with($path, 'docs/') && ($releaseSnapshot[$path] ?? null) !== $hash) $errors['release85'][] = 'PHP 8.5 execution used different computational input: ' . $path;
        $checks = []; foreach (CertificationCurrent::CHECKS as $id) $checks[$id] = $errors[$id] === [];
        return ['checks' => $checks, 'errors' => $errors];
    }

    public function verify(string $path): array
    {
        try {
            $c = new Certification86($this->root); $e = new CertificationCurrent($this->root); $r = $c->read($path);
            foreach (['output', 'configuration', 'inputs', 'inputsUnchanged', 'snapshots', 'commands', 'release85Snapshot', 'artifacts', 'gates', 'checks', 'blockers', 'prerequisites', 'verificationErrors'] as $field) if (!array_key_exists($field, $r)) return ['Missing current evidence field: ' . $field];
            $schema = $c->read('schema/certification-current.schema.json');
            if (array_diff($schema['required'], array_keys($r)) || array_diff(array_keys($r), array_keys($schema['properties']))) return ['Current evidence schema membership mismatch'];
            foreach ($schema['properties'] as $field => $rule) if (array_key_exists('const', $rule) && ($r[$field] ?? null) !== $rule['const']) return ['Current evidence schema identity mismatch: ' . $field];
            $out = $r['output'];
            if (!preg_match('~^' . preg_quote(CertificationCurrent::OUT, '~') . '/runs/[a-zA-Z0-9][a-zA-Z0-9_-]*$~D', $out) || $path !== $out . '/evidence.json') return ['Invalid revision path'];
            $errors = CertificationCurrent::profileErrors($r['configuration']);
            if (($r['runtime'] ?? []) !== $this->runtimeHashes($r['configuration']) || ($r['runtimeUnchanged'] ?? false) !== true) $errors[] = 'Changed executable, core DLL or release extension dependency';
            if (($c->read($out . '/receipt.json')['sha256'] ?? '') !== hash_file('sha256', $c->path($path))) $errors[] = 'Tampered report/controller receipt';
            if (($r['inputs'] ?? []) !== $e->inventory() || ($r['inputsUnchanged'] ?? false) !== true) $errors[] = 'Stale or incomplete current dependency set';
            $errors = [...$errors, ...$c->verifyHashes($r['artifacts'] ?? [])];
            $required = []; foreach (Support::files($c->path($out)) as $file) { $p = substr($file, strlen($this->root) + 1); if (!in_array($p, [$path, $out . '/receipt.json'], true)) $required[] = $p; }
            foreach (Support::files(dirname($c->path($r['configuration']['compatibility-report']))) as $file) $required[] = substr($file, strlen($this->root) + 1);
            if (($r['artifacts'] ?? []) !== $c->hashes($required)) $errors[] = 'Incomplete artifact membership';
            if ($errors) return $errors;
            $before = $e->inventory(); $artifactBefore = $c->hashes([...$required, $path, $out . '/receipt.json']);
            $verified = $this->checks($r); $legacy = $c->read($out . '/legacy/' . Certification86::REPORT)['gates'];
            $blockers = $c->read(BlockerClosure::ROOT . '/dispositions.json')['blockers'];
            $errors = CertificationCurrent::assessmentErrors($r, $legacy, $verified['checks'], $blockers);
            if (($r['verificationErrors'] ?? null) !== $verified['errors']) $errors[] = 'Verification outcomes changed';
            foreach ($verified['checks'] as $id => $ok) if (!$ok) $errors[] = 'Unverified current prerequisite: ' . $id;
            $errors = [...$errors, ...CertificationCurrent::readOnlyErrors($before, $e->inventory(), $c->verifyHashes($artifactBefore))];
            if ($r['runtime'] !== $this->runtimeHashes($r['configuration'])) $errors[] = 'Runtime dependencies changed during verification';
            return $errors;
        } catch (\Throwable $e) { return ['Missing or invalid current evidence: ' . $e->getMessage()]; }
    }
}
