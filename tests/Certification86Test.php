<?php
declare(strict_types=1);

namespace PhpGrammar\Tests;

use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ChangeTraceability;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/ManifestValidator.php';
require_once __DIR__ . '/../tools/lib/Certification86.php';
require_once __DIR__ . '/../tools/lib/ChangeTraceability.php';

final class Certification86Test extends TestCase
{
    private string $temporary;
    private C $c;

    protected function setUp(): void
    {
        $this->temporary = sys_get_temp_dir() . '/php86-certification-' . bin2hex(random_bytes(8));
        mkdir($this->temporary);
        $this->c = new C($this->temporary);
    }

    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->temporary);
    }

    private function sourceFixture(): array
    {
        $files = [];
        foreach (C::CORE as $path) {
            $file = $this->temporary . '/source/' . $path;
            if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
            file_put_contents($file, 'isolated source ' . $path);
            $files[$path] = hash_file('sha256', $file);
        }
        $this->c->write('docs/8.6/source-evidence.json', ['files' => array_map(static fn($hash) => ['new' => $hash], $files)]);
        $this->c->write('tools/8.6/source-lock.json', ['files' => ['8.6' => $files]]);
        $this->c->write('docs/8.6/source-audit-upstream.json', ['fixture' => true]);
        return ['version' => '8.6', 'phpSource' => ['commit' => C::PIN, 'tag' => 'php-8.6.0RC4'],
            'files' => $files, 'provenance' => $this->c->hashes(['docs/8.6/source-evidence.json', 'tools/8.6/source-lock.json', 'docs/8.6/source-audit-upstream.json'])];
    }

    public function testSourceHashesAndMissingLockEntriesFailClosed(): void
    {
        $lock = $this->sourceFixture();
        self::assertSame([], $this->c->sourceErrors($lock, $this->temporary . '/source'));
        $bad = $lock;
        unset($bad['files'][C::CORE[0]]);
        self::assertStringContainsString('Missing source-lock entry', implode('\n', $this->c->sourceErrors($bad, $this->temporary . '/source')));
        $bad = $lock;
        $bad['files'][C::CORE[0]] = str_repeat('0', 64);
        self::assertStringContainsString('differs from reviewed record', implode('\n', $this->c->sourceErrors($bad, $this->temporary . '/source')));
        file_put_contents($this->temporary . '/source/' . C::CORE[0], 'mutated bytes');
        self::assertStringContainsString('Source hash mismatch', implode('\n', $this->c->sourceErrors($lock, $this->temporary . '/source')));
    }

    public function testSourcePinAndProvenanceCannotBeOmitted(): void
    {
        $lock = $this->sourceFixture();
        $lock['phpSource']['commit'] = S::PIN;
        $lock['provenance'] = [];
        $errors = implode('\n', $this->c->sourceErrors($lock, $this->temporary . '/source'));
        self::assertStringContainsString('Source pin/release mismatch', $errors);
        self::assertStringContainsString('Missing source provenance', $errors);
    }

    public function testMissingAndStaleEvidenceAreRejectedWithoutWriting(): void
    {
        $this->c->write('evidence.json', ['actual' => 1]);
        $hashes = $this->c->hashes(['evidence.json']);
        self::assertSame([], $this->c->verifyHashes($hashes));
        $before = file_get_contents($this->temporary . '/evidence.json');
        self::assertSame($before, file_get_contents($this->temporary . '/evidence.json'));
        $this->c->write('evidence.json', ['actual' => 2]);
        self::assertSame(['Stale evidence digest: evidence.json'], $this->c->verifyHashes($hashes));
        unlink($this->temporary . '/evidence.json');
        self::assertSame(['Missing evidence artifact: evidence.json'], $this->c->verifyHashes($hashes));
    }

    public function testInvalidManifestMetadataAndPromotionAreRejected(): void
    {
        $c = new C(realpath(__DIR__ . '/..'));
        $manifest = json_decode(file_get_contents($c->path('php-grammar.json')));
        self::assertSame([], $c->manifestErrors($manifest));
        $manifest->versions[1]->metadata->changes = '../outside.md';
        self::assertNotSame([], $c->manifestErrors($manifest));
        $manifest = json_decode(file_get_contents($c->path('php-grammar.json')));
        $manifest->versions[1]->status = 'candidate';
        self::assertContains('Invalid PHP 8.6 draft metadata/source/package', $c->manifestErrors($manifest));
    }

    private function gateFixture(): array
    {
        $gates = [];
        foreach (C::gates() as $id => [$kind, $obligation]) {
            $blocked = str_starts_with($kind, 'blocked:') || $kind === 'provenance';
            $gates[$id] = ['obligation' => $obligation, 'status' => $blocked ? 'blocked' : 'passed',
                'exit_code' => $blocked ? null : 0, 'reason' => 'Isolated test prerequisite'];
        }
        return $gates;
    }

    public function testMissingFailedAndForgedReleaseGatesAreRejected(): void
    {
        $gates = $this->gateFixture();
        self::assertSame([], $this->c->gateErrors($gates));
        $missing = $gates;
        unset($missing['source-lock-integrity']);
        self::assertStringContainsString('Missing required release gate', implode('\n', $this->c->gateErrors($missing)));
        $gates['manifest-schema']['exit_code'] = 1;
        self::assertStringContainsString('Failed release gate marked passed', implode('\n', $this->c->gateErrors($gates)));
        $gates['manifest-schema']['status'] = 'failed';
        self::assertStringContainsString('Release gate not passed: manifest-schema', implode('\n', $this->c->claimErrors(['gates' => $gates])));
        $gates['discovery-closure']['status'] = 'passed';
        $gates['discovery-closure']['exit_code'] = 0;
        self::assertStringContainsString('Unresolved obligation marked complete', implode('\n', $this->c->gateErrors($gates)));
    }

    public function testBlockersCannotBeDroppedOrWaivedAndPreventSuccess(): void
    {
        $c = new C(realpath(__DIR__ . '/..'));
        $rows = $c->read('docs/8.6/source-audit-dispositions.json')['blockers'];
        self::assertSame([], $c->blockerErrors($rows));
        $claims = implode('\n', $c->claimErrors(['gates' => $this->gateFixture(), 'blockers' => $rows]));
        foreach (C::BLOCKERS as $id) self::assertStringContainsString('Unresolved blocking finding: ' . $id, $claims);
        array_shift($rows);
        self::assertContains('Missing or duplicate blocker disposition', $c->blockerErrors($rows));
        $rows[0]['blocksCertification'] = false;
        self::assertStringContainsString('Unauthorized blocker reclassification', implode('\n', $c->blockerErrors($rows)));
    }

    public function testBinaryVersionOrAssertedCommitIsNotAnAttestation(): void
    {
        $file = $this->temporary . '/php.exe';
        file_put_contents($file, 'not an actual executable');
        $record = ['sha256' => str_repeat('0', 64), 'sourceCommit' => S::PIN, 'version' => '8.6.0RC4'];
        $errors = implode('\n', $this->c->provenanceErrors($record, $file));
        self::assertStringContainsString('binary digest', $errors);
        self::assertStringContainsString('source commit', $errors);
        $record['sha256'] = hash_file('sha256', $file);
        $record['sourceCommit'] = C::PIN;
        self::assertSame(['Exact-pin build attestation has not been independently verified'], $this->c->provenanceErrors($record, $file));
    }

    public function testInventedCountsAndInterpreterMetadataAreRejected(): void
    {
        $coverage = ['current' => ['productions' => 3], 'remaining' => [['classification' => 'unresolved']]];
        $this->c->write(C::OUTPUT . '/coverage.json', $coverage);
        $this->c->write(C::OUTPUT . '/interpreters.json', ['8.6' => ['sourceCommit' => null]]);
        mkdir($this->temporary . '/' . C::OUTPUT . '/logs');
        file_put_contents($this->temporary . '/' . C::OUTPUT . '/logs/full-regression.log', 'OK (2 tests, 4 assertions)');
        $report = ['grammar_coverage' => $coverage['current'], 'remaining_coverage' => $coverage['remaining'],
            'coverage_classifications' => ['unresolved' => 1], 'interpreters' => ['8.6' => ['sourceCommit' => null]],
            'gates' => ['full-regression' => ['exit_code' => 0]],
            'validation' => ['tests' => 2, 'assertions' => 4, 'exit_code' => 0, 'log' => C::OUTPUT . '/logs/full-regression.log']];
        self::assertSame([], $this->c->summaryErrors($report));
        $report['validation']['tests'] = 999;
        $report['grammar_coverage']['productions'] = 999;
        $report['interpreters']['8.6']['sourceCommit'] = C::PIN;
        self::assertSame(['Coverage summary differs from measured evidence', 'Interpreter metadata differs from retained observation',
            'Test summary differs from executed command'], $this->c->summaryErrors($report));
    }

    public function testCliIsStrictAndMissingReportCheckDoesNotCreateIt(): void
    {
        $root = realpath(__DIR__ . '/..');
        $r = S::run([PHP_BINARY, '-n', 'tools/8.6/certification.php', '--unknown'], $root);
        self::assertSame(2, $r['exit_code']);
        $path = '.audit/nonexistent-certification-' . bin2hex(random_bytes(6)) . '.json';
        $r = S::run([PHP_BINARY, '-n', 'tools/8.6/certification.php', '--check', '--report', $path], $root);
        self::assertSame(1, $r['exit_code']);
        self::assertStringContainsString('Missing evidence artifact', $r['output']);
        self::assertFileDoesNotExist($root . '/' . $path);
        $r = S::run([PHP_BINARY, '-n', 'tools/8.6/certification.php', '--check', '--prepare'], $root);
        self::assertSame(1, $r['exit_code']);
    }

    public function testUnexplainedProductionChangeUsesExistingTraceabilityGate(): void
    {
        // The existing isolated traceability suite mutates production trees, not
        // historical grammar files. Keep that validator as the runner's authority.
        $root = realpath(__DIR__ . '/..');
        $map = json_decode(file_get_contents($root . '/tools/8.6/change-map.json'), true);
        $valid = ChangeTraceability::validate($root, $map);
        self::assertSame([], $valid['errors']);
        $map['productions'] = [];
        $invalid = ChangeTraceability::validate($root, $map);
        self::assertNotSame([], $invalid['errors']);
    }
}
