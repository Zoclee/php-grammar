<?php

declare(strict_types=1);

final class TestResult
{
    public function __construct(
        public readonly string $version,
        public readonly string $kind,
        public readonly string $path,
        public readonly bool $expectedPass,
        public readonly bool $actualPass,
        public readonly string $output,
    ) {
    }

    public function passed(): bool
    {
        return $this->expectedPass === $this->actualPass;
    }
}

function usage(): string
{
    return <<<'TEXT'
Usage:
  php tools/run-fixtures.php [all|<version>] [--php=<php-binary>]
  php tools/run-fixtures.php --version=<version> [--php=<php-binary>]
  php tools/run-fixtures.php --list

Examples:
  php tools/run-fixtures.php
  php tools/run-fixtures.php all
  php tools/run-fixtures.php 8.5
  php tools/run-fixtures.php --version=8.5 --php=/usr/bin/php8.5
  php tools/run-fixtures.php --version=8.5 --php=C:\php\php.exe

TEXT;
}

function repositoryRoot(): string
{
    return dirname(__DIR__);
}

function fixtureRoot(): string
{
    return repositoryRoot() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures';
}

function normalizePath(string $path): string
{
    $root = repositoryRoot() . DIRECTORY_SEPARATOR;
    if (str_starts_with($path, $root)) {
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($root)));
    }

    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

function discoverVersions(): array
{
    $root = fixtureRoot();
    if (!is_dir($root)) {
        return [];
    }

    $versions = [];
    foreach (new DirectoryIterator($root) as $entry) {
        if ($entry->isDir() && !$entry->isDot() && preg_match('/^\d+\.\d+$/', $entry->getFilename())) {
            $versions[] = $entry->getFilename();
        }
    }

    usort($versions, 'version_compare');
    return $versions;
}

function parseArguments(array $argv): array
{
    $version = 'all';
    $php = PHP_BINARY;
    $list = false;

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '-h' || $arg === '--help') {
            echo usage();
            exit(0);
        }

        if ($arg === '--list') {
            $list = true;
            continue;
        }

        if (str_starts_with($arg, '--version=')) {
            $version = substr($arg, strlen('--version='));
            continue;
        }

        if (str_starts_with($arg, '--php=')) {
            $php = substr($arg, strlen('--php='));
            continue;
        }

        if ($arg === 'all' || preg_match('/^\d+\.\d+$/', $arg)) {
            $version = $arg;
            continue;
        }

        fwrite(STDERR, "Unknown argument: {$arg}\n\n" . usage());
        exit(2);
    }

    return [$version, $php, $list];
}

function collectFixtureFiles(string $version, string $kind): array
{
    $dir = fixtureRoot() . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . $kind;
    if (!is_dir($dir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $dir,
        FilesystemIterator::SKIP_DOTS
    ));

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files, SORT_STRING);
    return $files;
}

function runPhpLint(string $php, string $file): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open([$php, '-l', $file], $descriptorSpec, $pipes, repositoryRoot());
    if (!is_resource($process)) {
        throw new RuntimeException("Failed to start PHP binary: {$php}");
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [$exitCode === 0, trim($stdout . "\n" . $stderr)];
}

function detectPhpVersion(string $php): string
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open([$php, '-r', 'echo PHP_VERSION;'], $descriptorSpec, $pipes, repositoryRoot());
    if (!is_resource($process)) {
        return 'unknown';
    }

    fclose($pipes[0]);
    $stdout = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return $exitCode === 0 && $stdout !== '' ? $stdout : 'unknown';
}

function runVersion(string $version, string $php): array
{
    $results = [];
    foreach (['valid' => true, 'invalid' => false] as $kind => $expectedPass) {
        foreach (collectFixtureFiles($version, $kind) as $file) {
            [$actualPass, $output] = runPhpLint($php, $file);
            $results[] = new TestResult($version, $kind, $file, $expectedPass, $actualPass, $output);
        }
    }

    return $results;
}

[$selectedVersion, $phpBinary, $listOnly] = parseArguments($argv);
$versions = discoverVersions();

if ($listOnly) {
    echo "Available fixture versions:\n";
    foreach ($versions as $version) {
        echo "  {$version}\n";
    }
    exit(0);
}

if ($versions === []) {
    fwrite(STDERR, "No fixture versions found under " . normalizePath(fixtureRoot()) . "\n");
    exit(2);
}

if (!is_file($phpBinary) && $phpBinary !== basename($phpBinary)) {
    fwrite(STDERR, "PHP binary not found: {$phpBinary}\n");
    exit(2);
}

if ($selectedVersion !== 'all') {
    if (!in_array($selectedVersion, $versions, true)) {
        fwrite(STDERR, "Unknown fixture version: {$selectedVersion}\n\n");
        fwrite(STDERR, "Available versions: " . implode(', ', $versions) . "\n");
        exit(2);
    }

    $versions = [$selectedVersion];
}

echo "PHP binary: {$phpBinary}\n";
echo "PHP version: " . detectPhpVersion($phpBinary) . "\n\n";

$total = 0;
$passed = 0;

foreach ($versions as $version) {
    $results = runVersion($version, $phpBinary);
    $versionTotal = count($results);
    $versionPassed = 0;

    echo "PHP {$version} fixtures\n";

    foreach ($results as $result) {
        $ok = $result->passed();
        $total++;
        if ($ok) {
            $passed++;
            $versionPassed++;
        }

        $expected = $result->expectedPass ? 'valid' : 'invalid';
        $actual = $result->actualPass ? 'accepted' : 'rejected';
        $status = $ok ? 'PASS' : 'FAIL';

        echo sprintf(
            "  [%s] %s expected %s, got %s\n",
            $status,
            normalizePath($result->path),
            $expected,
            $actual
        );

        if (!$ok && $result->output !== '') {
            foreach (explode("\n", $result->output) as $line) {
                echo "        {$line}\n";
            }
        }
    }

    echo "  Result: {$versionPassed}/{$versionTotal} passed\n\n";
}

echo "Total: {$passed}/{$total} passed\n";
exit($passed === $total ? 0 : 1);
