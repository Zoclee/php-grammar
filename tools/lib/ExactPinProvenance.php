<?php
declare(strict_types=1);

namespace PhpGrammar\Tools;

/** Local build custody evidence, not a signature or a reproducible-build claim. */
final class ExactPinProvenance
{
    public const CONTRACT = 'php86-exact-source-v1';
    public const CONFIGURE = '--disable-all --enable-cli --enable-tokenizer --disable-zts --disable-cgi --disable-phpdbg';

    public function __construct(private readonly string $root) {}

    /** Validate the controller record before touching or executing its interpreter. */
    public function recordErrors(array $record): array
    {
        $errors = [];
        if (($record['contract'] ?? null) !== self::CONTRACT) $errors[] = 'Missing exact-source evidence contract';
        if (($record['sourceCommit'] ?? null) !== Certification86::PIN) $errors[] = 'Wrong source commit';
        if (($record['origin'] ?? null) !== 'https://github.com/php/php-src.git') $errors[] = 'Missing official source origin';
        if (($record['configuration'] ?? null) !== self::CONFIGURE) $errors[] = 'Unverified build configuration';
        if (($record['status'] ?? null) !== 'built') $errors[] = 'Exact-source build unavailable';
        foreach (['sourceTree', 'sourceDirectory', 'authentication', 'sourceLockSha256', 'toolchain', 'generatedFiles', 'runtimeFiles', 'binary', 'sha256'] as $field) {
            if (empty($record[$field])) $errors[] = 'Missing provenance: ' . $field;
        }
        if (!preg_match('/^[a-f0-9]{40}$/D', $record['sourceTree'] ?? '')) $errors[] = 'Invalid source tree identity';
        foreach (['sourceLockSha256', 'sha256'] as $field) {
            if (!preg_match('/^[a-f0-9]{64}$/D', $record[$field] ?? '')) $errors[] = 'Invalid digest: ' . $field;
        }
        foreach (['configure', 'compile'] as $step) {
            $row = $record['steps'][$step] ?? [];
            $body = $step === 'configure' ? "call buildconf.bat\r\nif errorlevel 1 exit /b %errorlevel%\r\ncall configure.bat " . self::CONFIGURE . "\r\nexit /b %errorlevel%"
                : "nmake\r\nexit /b %errorlevel%";
            $task = "@echo off\r\ncd /d \"" . ($record['sourceDirectory'] ?? '') . "\"\r\n" . $body . "\r\n";
            if (($row['taskContents'] ?? null) !== $task || ($row['taskSha256'] ?? null) !== hash('sha256', $task)) $errors[] = 'Unreviewed build task: ' . $step;
            if (($row['exit_code'] ?? null) !== 0 || empty($row['command']) || empty($row['cwd'])
                || empty($row['started']) || empty($row['finished'])) $errors[] = 'Incomplete build step: ' . $step;
            foreach (['stdout', 'stderr'] as $stream) {
                if (empty($row[$stream]['path']) || !preg_match('/^[a-f0-9]{64}$/D', $row[$stream]['sha256'] ?? '')) $errors[] = 'Incomplete build log: ' . $step . '/' . $stream;
            }
        }
        return $errors;
    }

    /** Read-only verification. A self-reported version string never participates. */
    public function errors(array $record, string $binary): array
    {
        $errors = $this->recordErrors($record);
        if ($errors !== []) return $errors;
        $c = new Certification86($this->root);
        try {
            $receipt = $c->read('docs/8.6/exact-pin-build/build-receipt.json');
            if (($receipt['recordSha256'] ?? null) !== hash('sha256', Support::json($record))
                || ($receipt['authenticationSha256'] ?? null) !== hash_file('sha256', $c->path($record['authentication']))) return ['Unverified controller custody receipt'];
            if (realpath($binary) !== realpath($record['binary']) || !is_file($binary)
                || hash_file('sha256', $binary) !== $record['sha256']) return ['Modified or substituted executable'];
            $lock = 'tools/8.6/certification-source-lock.json';
            if (!is_file($c->path($lock)) || hash_file('sha256', $c->path($lock)) !== $record['sourceLockSha256']) return ['Mismatched source lock'];
            $git = ['git', '-c', 'safe.directory=' . str_replace('\\', '/', $record['sourceDirectory']), '-C', $record['sourceDirectory']];
            foreach (['commit' => [['rev-parse', 'HEAD'], Certification86::PIN],
                'tree' => [['rev-parse', 'HEAD^{tree}'], $record['sourceTree']],
                'origin' => [['remote', 'get-url', 'origin'], $record['origin']]] as $name => [$command, $expected]) {
                $r = Support::run([...$git, ...$command], $this->root);
                if ($r['exit_code'] !== 0 || trim($r['output']) !== $expected) $errors[] = 'Git source identity mismatch: ' . $name;
            }
            $errors = [...$errors, ...$c->sourceErrors($c->read($lock), $record['sourceDirectory'])];
            $auth = $c->read($record['authentication']);
            if (($auth['source']['commit'] ?? null) !== Certification86::PIN
                || ($auth['source']['tree'] ?? null) !== $record['sourceTree']
                || ($auth['source']['origin'] ?? null) !== $record['origin']
                || empty($auth['source']['files']) || ($auth['sourceLockErrors'] ?? null) !== []) $errors[] = 'Source correspondence unavailable';
            foreach ($auth['source']['files'] ?? [] as $path => $hash) {
                $file = $record['sourceDirectory'] . '/' . $path;
                if (!is_file($file) || hash_file('sha256', $file) !== $hash) $errors[] = 'Changed upstream source: ' . $path;
            }
            foreach (['toolchain', 'generatedFiles', 'runtimeFiles'] as $group) foreach ($record[$group] as $path => $hash) {
                if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = 'Missing/changed ' . $group . ': ' . $path;
            }
            foreach ($record['steps'] as $step) foreach (['stdout', 'stderr'] as $stream) {
                $errors = [...$errors, ...$c->verifyHashes([$step[$stream]['path'] => $step[$stream]['sha256']])];
            }
            // The receipt is maintained by the trusted build controller, separately
            // from the candidate record. It binds all fields, including commands.
        } catch (\Throwable $e) {
            $errors[] = 'Incomplete provenance: ' . $e->getMessage();
        }
        return array_values(array_unique($errors));
    }
}
