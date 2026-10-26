<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\ExactPinProvenance as P;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/Certification86.php';
require_once __DIR__ . '/../tools/lib/ExactPinProvenance.php';
final class ExactPinProvenanceTest extends TestCase
{
    private function record(): array
    {
        $stream = ['path' => 'log', 'sha256' => str_repeat('a', 64)];
        $step = ['command' => ['reviewed-command'], 'cwd' => 'isolated', 'exit_code' => 0,
            'started' => '2026-10-25T00:00:00Z', 'finished' => '2026-10-25T00:01:00Z', 'stdout' => $stream, 'stderr' => $stream];
        $steps = [];
        foreach (['configure', 'compile'] as $name) {
            $body = $name === 'configure' ? "call buildconf.bat\r\nif errorlevel 1 exit /b %errorlevel%\r\ncall configure.bat " . P::CONFIGURE . "\r\nexit /b %errorlevel%" : "nmake\r\nexit /b %errorlevel%";
            $task = "@echo off\r\ncd /d \"isolated\"\r\n" . $body . "\r\n";
            $steps[$name] = $step + ['taskContents' => $task, 'taskSha256' => hash('sha256', $task)];
        }
        return ['contract' => P::CONTRACT, 'sourceCommit' => C::PIN, 'origin' => 'https://github.com/php/php-src.git',
            'configuration' => P::CONFIGURE, 'status' => 'built', 'sourceTree' => str_repeat('a', 40), 'sourceDirectory' => 'isolated',
            'authentication' => 'auth.json', 'sourceLockSha256' => str_repeat('a', 64), 'toolchain' => ['cl' => 'hash'],
            'generatedFiles' => ['header' => 'hash'], 'runtimeFiles' => ['php8.dll' => 'hash'], 'binary' => 'php.exe', 'sha256' => str_repeat('a', 64),
            'steps' => $steps];
    }
    public function testCompleteRecordShapeIsNotSourceAttestation(): void
    {
        $p = new P(dirname(__DIR__));
        self::assertSame([], $p->recordErrors($this->record()));
        self::assertNotSame([], $p->errors($this->record(), 'absent-php.exe'));
    }
    public function testMaterialOmissionsAndFailedBuildAreRejected(): void
    {
        $p = new P(dirname(__DIR__));
        foreach (['sourceCommit', 'origin', 'configuration', 'toolchain', 'authentication', 'sourceLockSha256', 'generatedFiles', 'binary', 'sha256'] as $field) {
            $record = $this->record(); unset($record[$field]);
            self::assertNotSame([], $p->recordErrors($record), $field);
        }
        $record = $this->record(); $record['sourceCommit'] = str_repeat('0', 40);
        self::assertContains('Wrong source commit', $p->recordErrors($record));
        $record = $this->record(); unset($record['steps']['compile']['stderr']);
        self::assertContains('Incomplete build log: compile/stderr', $p->recordErrors($record));
        $record = $this->record(); $record['steps']['compile']['exit_code'] = 2;
        self::assertContains('Incomplete build step: compile', $p->recordErrors($record));
        self::assertNotSame([], $p->recordErrors(['version' => '8.6.0RC4']));
    }
    public function testSelfAssertedSuccessCannotBypassSeparateCustodyReceipt(): void
    {
        $root = sys_get_temp_dir() . '/exact-pin-negative-' . bin2hex(random_bytes(6));
        mkdir($root . '/docs/8.6/exact-pin-build', 0777, true);
        $receipt = $root . '/docs/8.6/exact-pin-build/build-receipt.json';
        file_put_contents($receipt, json_encode(['recordSha256' => str_repeat('0', 64)]));
        try {
            self::assertSame(['Unverified controller custody receipt'], (new P($root))->errors($this->record(), 'php.exe'));
        } finally {
            unlink($receipt);
            foreach (['/docs/8.6/exact-pin-build', '/docs/8.6', '/docs', ''] as $path) rmdir($root . $path);
        }
    }
    public function testModifiedExecutableAndMismatchedLockFailBeforeSourceInspection(): void
    {
        $root = sys_get_temp_dir() . '/exact-pin-bytes-' . bin2hex(random_bytes(6));
        mkdir($root . '/docs/8.6/exact-pin-build', 0777, true);
        $record = $this->record();
        $record['binary'] = $root . '/php.exe';
        file_put_contents($record['binary'], 'controlled executable stand-in');
        $record['sha256'] = hash_file('sha256', $record['binary']);
        file_put_contents($root . '/auth.json', '{}');
        $record['authentication'] = 'auth.json';
        $receipt = $root . '/docs/8.6/exact-pin-build/build-receipt.json';
        file_put_contents($receipt, json_encode(['recordSha256' => hash('sha256', \PhpGrammar\Tools\Support::json($record)),
            'authenticationSha256' => hash_file('sha256', $root . '/auth.json')]));
        try {
            $p = new P($root);
            self::assertSame(['Mismatched source lock'], $p->errors($record, $record['binary']));
            file_put_contents($record['binary'], 'altered executable');
            self::assertSame(['Modified or substituted executable'], $p->errors($record, $record['binary']));
        } finally {
            unlink($record['binary']); unlink($receipt); unlink($root . '/auth.json');
            foreach (['/docs/8.6/exact-pin-build', '/docs/8.6', '/docs', ''] as $path) rmdir($root . $path);
        }
    }
}
