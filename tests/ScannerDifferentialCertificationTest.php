<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;

use PHPUnit\Framework\TestCase;
use PhpGrammar\Tools\{Certification86, ScannerDifferentialCertification as V4, ScannerDifferentialInventory as Inventory};
require_once dirname(__DIR__) . '/tools/lib/ScannerDifferentialCertification.php';

final class ScannerDifferentialCertificationTest extends TestCase
{
    public function testCompositionPreservesEveryOtherGateAndTheOriginalObligation(): void
    {
        $base = ['configuration' => ['php85' => 'driver'], 'gates' => []];
        foreach (Certification86::gates() as $id => [$kind, $obligation]) $base['gates'][$id] = [
            'id' => $id, 'obligation' => $obligation, 'requiredInputs' => ['historical'], 'result' => 'blocked', 'legacy' => ['status' => 'failed']];
        $rows = V4::gates($base, 'observations.json');
        self::assertCount(36, $rows);
        foreach ($base['gates'] as $id => $row) {
            if ($id === 'ordinary-differential') {
                self::assertSame($row, $rows[$id]['predecessorCurrentRow']);
                self::assertSame($row['obligation'], $rows[$id]['obligation']);
                self::assertContains(Inventory::INVENTORY, $rows[$id]['requiredInputs']);
                self::assertContains('tools/8.6/scanner-differential-inventory.php', $rows[$id]['command']);
                self::assertSame('ordinary-differential@v3', $rows[$id]['successorIdentity']);
            } else self::assertSame($row, $rows[$id]);
        }
    }

    public function testSuccessorCannotHideAnAdditionalHistoricalFailure(): void
    {
        self::assertSame([], V4::predecessorFailureErrors(['cases' => 313, 'errors' => [V4::MEMBERSHIP_FAILURE]]));
        self::assertNotEmpty(V4::predecessorFailureErrors(['cases' => 313, 'errors' => [V4::MEMBERSHIP_FAILURE, 'Changed original outcome']]));
        self::assertNotEmpty(V4::predecessorFailureErrors(['cases' => 312, 'errors' => [V4::MEMBERSHIP_FAILURE]]));
    }

    public function testChangedStageAndLostPredecessorRowsAreRejected(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__)); $e = new Inventory($root); $r = $e->derive();
        self::assertCount(353, $r['cases']);
        $bad = $r; $bad['cases'][0]['expected']['lintExit'] = 123;
        self::assertNotEmpty($e->inventoryErrors($bad));
        $bad = $r; $bad['cases'][339]['stage'] = 'complete runtime validation';
        self::assertNotEmpty($e->inventoryErrors($bad));
        $bad = $r; array_shift($bad['cases']);
        self::assertNotEmpty($e->inventoryErrors($bad));
    }
}
