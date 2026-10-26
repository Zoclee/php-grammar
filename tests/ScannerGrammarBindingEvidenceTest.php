<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PhpGrammar\Tools\{Certification86, ScannerGrammarBindingEvidence as E, ScannerDifferentialInventory as D};
require_once dirname(__DIR__) . '/tools/lib/ScannerGrammarBindingEvidence.php';
require_once dirname(__DIR__) . '/tools/lib/ScannerDifferentialInventory.php';
final class ScannerGrammarBindingEvidenceTest extends TestCase
{
    private function root(): string { return str_replace('\\', '/', dirname(__DIR__)); }
    public function testInventoryPreservesEveryPredecessorRowAndRejectsLoss(): void
    {
        $e = new D($this->root()); $r = $e->derive();
        $old = json_decode(file_get_contents($this->root() . '/' . \PhpGrammar\Tools\DifferentialInventory::INVENTORY), true);
        self::assertSame($old['cases'], array_slice($r['cases'], 0, 339));
        self::assertSame([], $e->inventoryErrors($r));
        array_pop($r['cases']); self::assertNotEmpty($e->inventoryErrors($r));
    }
    public function testReviewedCasesCarrySourceMappingsAndRealAlternativeTargets(): void
    {
        $e = new E($this->root()); $m = $e->manifest();
        self::assertSame(Certification86::PIN, $m['sourcePin']);
        $ids = array_column($m['sources'], 'id');
        foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($m[$group] as $r) {
            self::assertNotEmpty($r['sourceIds']); self::assertSame([], array_values(array_diff($r['sourceIds'], $ids)));
            self::assertSame($r['sha256'], hash_file('sha256', $this->root() . '/' . $r['fixture']));
        }
    }
    public function testRejectsIncompleteAndInflatedEvidence(): void
    {
        $e = new E($this->root());
        foreach (['astEquivalenceClaim', 'instrumentedScannerHits', 'certificationGranted'] as $flag) {
            $r = ['contract' => E::CONTRACT, 'sourcePin' => Certification86::PIN, 'profile' => 'exact-pin-rc4', $flag => true];
            self::assertContains('Unsupported claim: ' . $flag, $e->recordErrors($r));
        }
        self::assertContains('Observation membership', $e->recordErrors([]));
        self::assertContains('Changed coverage denominator', $e->recordErrors([]));
    }
    public function testRecordedObservationsRejectTokenOrOperandTampering(): void
    {
        $path = $this->root() . '/docs/8.6/scanner-grammar-binding-closure/observations.json';
        self::assertFileExists($path, 'Authenticated observations are a mandatory test dependency.');
        $e = new E($this->root()); $r = json_decode(file_get_contents($path), true);
        // Snapshot tests rebase only this in-memory copy. The read-only CLI
        // verifies retained commands and custody against their original root.
        $engine = new \PhpGrammar\Tools\ContextCoverageEvidence($this->root());
        $i = 0;
        foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($e->manifest()[$group] as $case) {
            $row = &$r['observations'][$i++];
            if ($group === 'binding') foreach (['originalDump' => 'fixture', 'forcedDump' => 'forcedFixture', 'differentDump' => 'differentFixture'] as $key => $pathKey) $row[$key]['command'] = $engine->lintCommand($case, true, $case[$pathKey]);
            else { $row['lint']['command'] = $engine->lintCommand($case); if ($group === 'scanner') $row['tokenization']['command'] = $engine->tokenCommand($case); }
            unset($row);
        }
        self::assertSame([], $e->recordErrors($r));
        foreach ($r['observations'] as $i => $row) if ($row['group'] === 'binding') {
            $bad = $r; $bad['observations'][$i]['differentDump'] = $row['originalDump'];
            self::assertNotEmpty($e->recordErrors($bad)); break;
        }
        foreach ($r['observations'] as $i => $row) if ($row['group'] === 'scanner') {
            $bad = $r; $bad['observations'][$i]['tokenization']['output'] = '{"tokens":[]}';
            self::assertNotEmpty($e->recordErrors($bad)); break;
        }
        $r['observations'][0]['native']['alternatives'] = []; self::assertNotEmpty($e->recordErrors($r));
    }
}
