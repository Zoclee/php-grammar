<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
require_once __DIR__ . '/../tools/lib/bootstrap.php';
require_once __DIR__ . '/../tools/lib/GaCertification.php';
use PhpGrammar\Tools\GaCertification as G;
use PhpGrammar\Tools\Certification86 as C;
use PHPUnit\Framework\TestCase;

final class GaCertificationTest extends TestCase
{
    private function g(): G { return new G(\PhpGrammar\Tests\Support\StructuralPredecessor::root()); }
    public function testAllOriginalObligationsArePreserved(): void {
        $g = $this->g(); $gates = $g->gates();
        self::assertCount(38, $gates);
        self::assertSame(C::gates(), array_slice($gates, 0, 36, true));
        self::assertSame(['historical-artifact-integrity85', 'current-compatibility85'], array_slice(array_keys($gates), 36));
        self::assertSame(array_keys($gates), array_keys($g->commands(G::OUT . '/runs/test')));
    }
    public function testGaCannotBeReplacedByRc4OrPromoted(): void {
        $g = $this->g(); $manifest = $g->read('php-grammar.json');
        self::assertSame([], $g->profileMetadataErrors());
        $manifest['versions'][1]['phpSource']['commit'] = C::PIN;
        self::assertContains('Invalid GA draft manifest', $g->profileMetadataErrors($manifest));
        $manifest = $g->read('php-grammar.json'); $manifest['versions'][1]['status'] = 'stable';
        self::assertContains('Invalid GA draft manifest', $g->profileMetadataErrors($manifest));
        $lock = $g->read('tools/8.6/source-lock-ga-v2.json'); $lock['executables']['8.6'] = $lock['executables']['8.5'];
        self::assertContains('Unapproved source/executable identity', $g->profileMetadataErrors(null, $lock));
    }
    public function testVersionedRunnersPreserveEveryCheckAndExpectation(): void {
        $g = $this->g(); self::assertSame([], $g->runnerErrors());
        $transition = $g->read('tools/8.6/ga-runner-transition-v2.json');
        self::assertCount(5, $transition['runners']);
        $old = $g->read('tools/8.6/change-map.json'); $new = $g->read('tools/8.6/change-map-ga-v2.json');
        foreach (['baseline', 'productions', 'implementation', 'documentation', 'openFindings', 'obligations'] as $key) self::assertSame($old[$key], $new[$key]);
        foreach ($old['findings'] as $id => $finding) {
            unset($finding['sources']); $current = $new['findings'][$id]; unset($current['sources']);
            self::assertSame($finding, $current);
        }
    }
    public function testMissingBlockersWrongPinAndFalseClaimsFail(): void {
        $g = $this->g();
        $r = ['contract' => G::CONTRACT, 'sourceCommit' => G::PIN, 'originalObligations' => 36, 'hybridObligations' => 2,
            'blockers' => C::BLOCKERS, 'certificationGranted' => false, 'decision' => 'NOT SIGNED OFF — RELEASE BLOCKED'];
        foreach (['sourceCommit' => C::PIN, 'originalObligations' => 35, 'hybridObligations' => 1,
            'blockers' => [], 'certificationGranted' => true, 'decision' => 'SIGNED OFF — READY FOR RELEASE'] as $key => $value) {
            self::assertSame(['Invalid GA contract, accounting, blockers or claim'], $g->verify(array_replace($r, [$key => $value]), 'invalid'));
        }
    }
    public function testHistoricalTamperingAndMissingEvidenceAreNotFreshnessExceptions(): void {
        $g = $this->g();
        self::assertNotSame([], $g->hashErrors(['grammar/8.5/php.ebnf' => str_repeat('0', 64)]));
        self::assertNotSame([], $g->hashErrors(['docs/8.6/nonexistent-ga-evidence.json' => str_repeat('0', 64)]));
        self::assertNotSame([], $g->hashErrors([G::PHP => '880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b']));
    }
    public function testOmittingEitherHybridObligationIsRejectedBeforeReadingReceipts(): void {
        $g = $this->g();
        $r = ['contract' => G::CONTRACT, 'sourceCommit' => G::PIN, 'originalObligations' => 36, 'hybridObligations' => 2,
            'blockers' => C::BLOCKERS, 'certificationGranted' => false, 'decision' => 'NOT SIGNED OFF — RELEASE BLOCKED',
            'results' => array_fill_keys(array_keys(C::gates()), [])];
        self::assertSame(['Missing/reordered GA obligations'], $g->verify($r, 'invalid'));
        $r['results']['historical-artifact-integrity85'] = [];
        self::assertSame(['Missing/reordered GA obligations'], $g->verify($r, 'invalid'));
    }
    public function testGaDiagnosticTransitionRetainsRejectionAndExactHookPredicate(): void {
        $row = ['id' => 'reconciliation/hook-retained', 'fixture' => 'tests/fixtures/php/8.6/reconciliation/hook-retained.php',
            'stage' => 'lint', 'expectedAcceptance' => false,
            'expectedDiagnostic' => 'Unknown hook "unknown" for property class@anonymous::$x, expected "get" or "set"'];
        $expected = G::gaDiagnostic($row);
        self::assertStringContainsString("\0" . $row['fixture'] . ':2$0', $expected);
        self::assertTrue(G::stageMatches($row, 255, $expected));
        self::assertFalse(G::stageMatches($row, 0, $expected));
        self::assertFalse(G::stageMatches($row, 255, str_replace('"unknown"', '"other"', $expected)));
        self::assertFalse(G::stageMatches($row, 255, str_replace('::$x', '::$y', $expected)));
        self::assertFalse(G::stageMatches($row, 255, str_replace("\0", '', $expected)));
        self::assertFalse(G::stageMatches($row, 255, $row['expectedDiagnostic']));
        $row['id'] = 'another-fixture'; self::assertSame($row['expectedDiagnostic'], G::gaDiagnostic($row));
    }
}
