<?php
declare(strict_types=1);

namespace PhpGrammar\Tools;

use RuntimeException;

require_once __DIR__ . '/Certification86.php';
require_once __DIR__ . '/ExactPinProvenance.php';

/** Additive observations; never a replacement for contextual completeness gates. */
final class ContextEvidence
{
    public const CONTRACT = 'php86-context-profiles-v1';
    public const HISTORICAL = 'historical-rc4';
    public const EXACT = 'exact-pin-rc4';
    public const CASES = 'tests/fixtures/php/8.6/context-cases.json';
    public const HISTORY = 'docs/8.6/context-validation.json';
    public const BUILD = 'docs/8.6/exact-pin-build/build.json';
    public const EXACT_SHA = '445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6';
    private const ANCHORS = [
        self::HISTORY => '8ee2b856d0df535eeb0dd07f8f49e92cb48aebca4b90de8682df0a64a1146eed',
        self::CASES => '59d291190a1b5947956c897729e3022152613eb67b1607aa7a15d6e00e35fa6a',
        'tools/8.6/context-source-lock.json' => 'd792bd32971e11e89497e85cca455d7c657756f91704a611bd06d08f77625167',
    ];
    private const BUILD_ANCHORS = [
        self::BUILD => '67bba1eb5fd3c49fb3330cb0cf28550cb51d8f03dbbf607009f17d95af9f9d0c',
        'docs/8.6/exact-pin-build/build-receipt.json' => 'a31edcfbfc1858c08a69e40e96ce9f026e67044919f0304d8d60b8e51f071220',
    ];
    public const FLAGS = ['-n', '-d', 'error_reporting=-1', '-d', 'zend.multibyte=0',
        '-d', 'short_open_tag=1', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];

    private Certification86 $c;
    public function __construct(private readonly string $root) { $this->c = new Certification86($root); }

    public function profile(string $id): array
    {
        if (!in_array($id, [self::HISTORICAL, self::EXACT], true)) throw new RuntimeException('Explicit known context profile required');
        return ['id' => $id, 'contract' => self::CONTRACT, 'sourceCommit' => Certification86::PIN,
            'executableSha256' => $id === self::EXACT ? self::EXACT_SHA : 'c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a',
            'sourceBinding' => $id === self::EXACT ? 'verified-local-build' : 'reviewed-source-lock; distribution ancestry not attested',
            'provenance' => $id === self::EXACT ? self::BUILD : 'tools/8.6/context-source-lock.json',
            'historicalObservations' => self::HISTORY, 'fixtureSet' => self::CASES,
            'stages' => ['lint', 'declaration-link', 'controlled-runtime'], 'flags' => self::FLAGS,
            'configuration' => 'No ini; CLI OPcache/JIT disabled; inherited host environment is not hermetic'];
    }

    public function defaults(): array
    {
        return ['profile' => '', 'php85' => $this->root . '/.audit/php85/php.exe', 'php86' => '',
            'sources85' => $this->root . '/.audit/php-php-src-7a4c627', 'sources86' => '',
            'provenance-record' => self::BUILD];
    }

    /** This entire check must finish before even asking an interpreter for its version. */
    public function preflight(array $config): array
    {
        try {
            $profile = $this->profile($config['profile'] ?? '');
            $errors = $this->c->verifyHashes(self::ANCHORS);
            if ($errors) return $errors;
            $lock = $this->c->read('tools/8.6/context-source-lock.json');
            if ($lock['phpSource']['8.6']['commit'] !== Certification86::PIN) $errors[] = 'Context source pin mismatch';
            foreach (['85' => '8.5', '86' => '8.6'] as $suffix => $version) {
                $file = $config['php' . $suffix] ?? '';
                $hash = $version === '8.6' ? $profile['executableSha256'] : $lock['executables']['8.5'];
                if (!is_file($file) || hash_file('sha256', $file) !== $hash) $errors[] = 'Wrong executable identity/profile: ' . $version;
                foreach ($lock['files'][$version] as $path => $expected) {
                    $source = ($config['sources' . $suffix] ?? '') . '/' . $path;
                    if (!is_file($source) || hash_file('sha256', $source) !== $expected) $errors[] = 'Context source mismatch: ' . $version . '/' . $path;
                }
            }
            // Authenticate each fixture against the preserved original observations.
            $history = $this->c->read(self::HISTORY);
            $cases = $this->c->read(self::CASES);
            if (count($history['observations'] ?? []) !== count($cases) * 2 || ($history['errors'] ?? null) !== []) $errors[] = 'Missing historical observations';
            $index = [];
            foreach ($history['observations'] ?? [] as $row) $index[$row['version'] . '/' . $row['id']] = $row;
            foreach ($cases as $case) foreach (['8.5', '8.6'] as $version) {
                $row = $index[$version . '/' . $case['id']] ?? [];
                $path = $this->c->path($case['fixture']);
                if (!is_file($path) || hash_file('sha256', $path) !== ($row['fixtureSha256'] ?? null)
                    || ($row['stage'] ?? null) !== $case['stage']) $errors[] = 'Missing/stale contextual input: ' . $case['id'];
            }
            if ($profile['id'] === self::EXACT) {
                if (($config['provenance-record'] ?? '') !== self::BUILD) $errors[] = 'Unreviewed provenance record';
                $errors = [...$errors, ...$this->c->verifyHashes(self::BUILD_ANCHORS)];
                if ($errors) return $errors;
                $record = $this->c->read(self::BUILD);
                if (realpath($config['sources86']) !== realpath($record['sourceDirectory'])) $errors[] = 'Source directory differs from authenticated build';
                $errors = [...$errors, ...(new ExactPinProvenance($this->root))->errors($record, $config['php86'])];
            }
            return array_values(array_unique($errors));
        } catch (\Throwable $e) { return ['Context preflight: ' . $e->getMessage()]; }
    }

    /** Reconstruct membership independently of a report's offered dependency map. */
    public function inputs(string $profile): array
    {
        $this->profile($profile);
        $paths = [...array_keys(self::ANCHORS), 'tools/lib/ContextEvidence.php', 'tools/lib/Support.php',
            'tools/lib/Certification86.php', 'tools/lib/ExactPinProvenance.php', 'tools/lib/bootstrap.php',
            'tools/8.6/context-profile.php', 'tools/8.6/context-check.php', 'tools/8.6/certification.php',
            'schema/context-evidence.schema.json', 'php-grammar.json'];
        if ($profile === self::EXACT) {
            $paths = [...$paths, ...array_keys(self::BUILD_ANCHORS), 'tools/8.6/certification-source-lock.json'];
        }
        foreach ($this->c->read(self::CASES) as $case) $paths[] = $case['fixture'];
        return $this->c->hashes(array_unique($paths));
    }

    private function command(array $config, string $version, string $fixture, bool $lint): array
    {
        return [$config['php' . str_replace('.', '', $version)], ...self::FLAGS,
            ...($lint ? ['-l'] : []), $this->c->path($fixture)];
    }

    private function run(array $command): array
    {
        $out = tmpfile(); $err = tmpfile();
        if ($out === false || $err === false) throw new RuntimeException('Cannot allocate probe streams');
        try {
            $p = proc_open($command, [0 => ['pipe', 'r'], 1 => $out, 2 => $err], $pipes, $this->root);
            if (!is_resource($p)) throw new RuntimeException('Cannot start authenticated interpreter');
            fclose($pipes[0]); $exit = proc_close($p); rewind($out); rewind($err);
            return ['command' => $command, 'exit' => $exit, 'stdout' => stream_get_contents($out), 'stderr' => stream_get_contents($err)];
        } finally { fclose($out); fclose($err); }
    }

    private function normalized(array $run): array
    {
        return ['exit' => $run['exit'], 'output' => trim(str_replace([$this->root, str_replace('/', '\\', $this->root)],
            '<repository>', $run['stdout'] . $run['stderr']))];
    }

    /** Checks membership, commands, outcomes and original observations; never executes PHP. */
    public function observationErrors(array $rows, array $config): array
    {
        $errors = []; $expected = []; $history = [];
        foreach ($this->c->read(self::HISTORY)['observations'] as $row) $history[$row['version'] . '/' . $row['id']] = $row;
        foreach (['8.5', '8.6'] as $version) foreach ($this->c->read(self::CASES) as $case) $expected[$version . '/' . $case['id']] = [$version, $case];
        if (count($rows) !== count($expected)) $errors[] = 'Missing or extra contextual observations';
        $seen = [];
        foreach ($rows as $row) {
            $key = ($row['version'] ?? '') . '/' . ($row['id'] ?? '');
            if (!isset($expected[$key]) || isset($seen[$key])) { $errors[] = 'Unknown/duplicate observation: ' . $key; continue; }
            $seen[$key] = true; [$version, $case] = $expected[$key];
            $expectation = ['accepted' => $case['accept' . str_replace('.', '', $version)],
                'diagnostic' => $version === '8.6' ? $case['diagnostic86'] : null,
                'output' => $version === '8.6' ? $case['output86'] : null];
            if (($row['fixture'] ?? null) !== $case['fixture'] || ($row['fixtureSha256'] ?? null) !== hash_file('sha256', $this->c->path($case['fixture']))
                || ($row['stage'] ?? null) !== $case['stage'] || ($row['finding'] ?? null) !== $case['finding']
                || ($row['expected'] ?? null) !== $expectation) $errors[] = 'Context metadata/expectation mismatch: ' . $key;
            foreach (['lint', 'result'] as $step) {
                $run = $row[$step] ?? [];
                if (($run['command'] ?? null) !== $this->command($config, $version, $case['fixture'], $step === 'lint' || $case['stage'] === 'lint')
                    || !is_int($run['exit'] ?? null) || !is_string($run['stdout'] ?? null) || !is_string($run['stderr'] ?? null)) {
                    $errors[] = 'Missing/invalid stage receipt: ' . $key . '/' . $step; continue;
                }
                $actual = $this->normalized($run);
                if ($actual !== $history[$key][$step]) $errors[] = 'Historical stage difference: ' . $key . '/' . $step;
                if ($step === 'result') {
                    if (($actual['exit'] === 0) !== $expectation['accepted']) $errors[] = 'Acceptance mismatch: ' . $key;
                    if ($expectation['diagnostic'] !== null && !str_contains(strtolower($actual['output']), strtolower($expectation['diagnostic']))) $errors[] = 'Diagnostic mismatch: ' . $key;
                    if ($expectation['output'] !== null && $actual['output'] !== $expectation['output']) $errors[] = 'Output mismatch: ' . $key;
                }
            }
        }
        if (array_diff_key($expected, $seen)) $errors[] = 'Missing contextual observation identities';
        return $errors;
    }

    public function generate(array $config): array
    {
        $errors = $this->preflight($config);
        if ($errors) throw new RuntimeException(implode('; ', $errors));
        $inputs = $this->inputs($config['profile']); $rows = [];
        foreach (['8.5', '8.6'] as $version) foreach ($this->c->read(self::CASES) as $case) {
            $lint = $this->run($this->command($config, $version, $case['fixture'], true));
            $result = $case['stage'] === 'lint' ? $lint : $this->run($this->command($config, $version, $case['fixture'], false));
            $rows[] = ['id' => $case['id'], 'finding' => $case['finding'], 'version' => $version, 'stage' => $case['stage'],
                'fixture' => $case['fixture'], 'fixtureSha256' => hash_file('sha256', $this->c->path($case['fixture'])),
                'expected' => ['accepted' => $case['accept' . str_replace('.', '', $version)],
                    'diagnostic' => $version === '8.6' ? $case['diagnostic86'] : null, 'output' => $version === '8.6' ? $case['output86'] : null],
                'lint' => $lint, 'result' => $result];
        }
        $errors = $this->observationErrors($rows, $config);
        // Detect changed inputs, interpreter, build or source during execution.
        $errors = [...$errors, ...$this->preflight($config)];
        if ($inputs !== $this->inputs($config['profile'])) $errors[] = 'Inputs changed during execution';
        return ['schemaVersion' => 1, 'contract' => self::CONTRACT, 'evidenceKind' => 'contextual-observations',
            'generatedAt' => gmdate('Y-m-d\TH:i:s\Z'), 'profile' => $this->profile($config['profile']),
            'configuration' => $config, 'inputs' => $inputs, 'observations' => $rows,
            'historicalComparison' => $errors === [] ? 'identical' : 'failed; see errors', 'errors' => $errors,
            'certificationGranted' => false, 'blockers' => Certification86::BLOCKERS];
    }

    /** No report repair, generation, fixture execution or evidence writes. */
    public function verify(string $path, string $profile): array
    {
        try {
            $expectedProfile = $this->profile($profile); $r = $this->c->read($path);
            $receipt = $this->c->read($path . '.receipt.json');
            if (($receipt['reportSha256'] ?? null) !== hash_file('sha256', $this->c->path($path))) return ['Changed contextual evidence receipt'];
            $errors = [];
            if (($r['schemaVersion'] ?? null) !== 1 || ($r['contract'] ?? null) !== self::CONTRACT
                || ($r['evidenceKind'] ?? null) !== 'contextual-observations' || ($r['profile'] ?? null) !== $expectedProfile
                || ($r['certificationGranted'] ?? null) !== false || ($r['blockers'] ?? null) !== Certification86::BLOCKERS
                || ($r['historicalComparison'] ?? null) !== 'identical' || ($r['errors'] ?? null) !== []) $errors[] = 'Context evidence contract/profile/claim mismatch';
            $config = $r['configuration'] ?? [];
            if (($config['profile'] ?? null) !== $profile) $errors[] = 'Context configuration/profile mismatch';
            if ($errors) return $errors;
            $errors = $this->preflight($config);
            if ($errors) return $errors;
            if (($r['inputs'] ?? null) !== $this->inputs($profile)) $errors[] = 'Stale or incomplete context evidence inputs';
            return [...$errors, ...$this->observationErrors($r['observations'] ?? [], $config)];
        } catch (\Throwable $e) { return ['Context evidence unavailable: ' . $e->getMessage()]; }
    }
}
