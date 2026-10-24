<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Repository;

use PHPUnit\Framework\TestCase;

final class ConsumerCliTest extends TestCase
{
    /** @return array{int, string, string} */
    private function runCli(array $args): array
    {
        $process = proc_open([PHP_BINARY, dirname(__DIR__, 2) . '/bin/php-grammar', ...$args],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $stdout, $stderr];
    }

    public function testJsonNavigationCommands(): void
    {
        foreach ([['versions'], ['rules', '8.5'], ['rule', '8.5', 'expression'], ['refs', '8.5', 'expression'], ['refs', '8.5', 'code-unit'], ['sections', '8.5'], ['section', '8.5', 'expressions']] as $args) {
            [$exit, $stdout, $stderr] = $this->runCli($args);
            self::assertSame(0, $exit, $stderr);
            self::assertSame('', $stderr);
            self::assertIsArray(json_decode($stdout, true, flags: JSON_THROW_ON_ERROR));
        }
    }

    public function testErrorsHaveNonzeroExitAndNoJsonOutput(): void
    {
        foreach ([['rules', '9.9'], ['rule', '8.5', 'missing'], ['section', '8.5', 'missing'], ['refs', '8.5', 'missing'], ['unknown'], ['versions', 'extra']] as $args) {
            [$exit, $stdout, $stderr] = $this->runCli($args);
            self::assertSame(1, $exit);
            self::assertSame('', $stdout);
            self::assertNotSame('', $stderr);
        }
    }

    public function testDraftNavigationAndMetadata(): void
    {
        [$exit, $stdout, $stderr] = $this->runCli(['versions']);
        self::assertSame(0, $exit, $stderr);
        $versions = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['stable','draft'], array_column($versions, 'status'));
        self::assertSame('f23b4c222cebe33be93ee8ecaaa14390c5507a08', $versions[1]['phpSource']['commit']);
        self::assertSame('grammar/8.6/changes.md', $versions[1]['metadata']['changes']);
        foreach ([['rules','8.6'],['rule','8.6','argument'],['refs','8.6','argument'],['sections','8.6'],['section','8.6','arguments']] as $args) {
            [$exit,$stdout,$stderr] = $this->runCli($args);
            self::assertSame(0,$exit,$stderr);
            self::assertIsArray(json_decode($stdout,true,flags: JSON_THROW_ON_ERROR));
        }
        [$exit,$stdout,$stderr] = $this->runCli(['rule','8.6','missing']);
        self::assertSame(1,$exit); self::assertSame('',$stdout); self::assertNotSame('',$stderr);
    }
}
