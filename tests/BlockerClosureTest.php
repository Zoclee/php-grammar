<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PhpGrammar\Tools\BlockerClosure as B;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/BlockerClosure.php';

final class BlockerClosureTest extends TestCase
{
    private function fixture(): array
    {
        $original = [['id' => 'h1', 'disposition' => 'pending-direct-review'], ['id' => 'h2', 'disposition' => 'examined-unresolved']];
        $raw = [['id' => 'h1', 'diffSha256' => str_repeat('a', 64)], ['id' => 'h2', 'diffSha256' => str_repeat('b', 64)]];
        $group = ['id' => 'g1', 'disposition' => 'excluded-no-language-change', 'rationale' => 'Reviewed proof',
            'remainingScope' => 'Only this hunk', 'sourceEvidence' => ['source'], 'grammarOrContext' => ['type'], 'verification' => ['receipt'],
            'hunks' => [['id' => 'h1', 'originalDisposition' => 'pending-direct-review', 'diffSha256' => str_repeat('a', 64), 'diffFile' => 'h1.diff']]];
        return [['groups' => [$group], 'inventory' => [
            ['id' => 'h1', 'original' => $original[0], 'effectiveDisposition' => 'phase7c6-excluded-no-language-change'],
            ['id' => 'h2', 'original' => $original[1], 'effectiveDisposition' => 'examined-unresolved']],
            'counts' => ['phase7c6-excluded-no-language-change' => 1, 'examined-unresolved' => 1], 'discoveryComplete' => false, 'certificationGranted' => false], $original, $raw];
    }
    public function testScopedExclusionRetainsEveryOriginalIdentity(): void
    {
        [$r, $o, $raw] = $this->fixture(); self::assertSame([], B::inventoryErrors($r, $o, $raw));
        array_pop($r['inventory']); self::assertNotSame([], B::inventoryErrors($r, $o, $raw));
    }
    public function testDuplicateUnknownOrUnprovenHunksCannotBeDischarged(): void
    {
        [$r, $o, $raw] = $this->fixture();
        foreach (['id' => 'unknown', 'diffSha256' => 'changed', 'originalDisposition' => 'excluded', 'diffFile' => ''] as $field => $value) {
            $bad = $r; $bad['groups'][0]['hunks'][0][$field] = $value; self::assertNotSame([], B::inventoryErrors($bad, $o, $raw));
        }
        $r['groups'][] = $r['groups'][0]; self::assertNotSame([], B::inventoryErrors($r, $o, $raw));
    }
    public function testUnreviewedRiskAndInventedCountsRemainRejected(): void
    {
        [$r, $o, $raw] = $this->fixture();
        $bad = $r; $bad['inventory'][1]['effectiveDisposition'] = 'closed'; self::assertNotSame([], B::inventoryErrors($bad, $o, $raw));
        $bad = $r; $bad['counts']['examined-unresolved'] = 0; self::assertNotSame([], B::inventoryErrors($bad, $o, $raw));
        $r['discoveryComplete'] = true; self::assertNotSame([], B::inventoryErrors($r, $o, $raw));
    }
    public function testPassedPrerequisitesCannotEraseOriginalBlockingRequirements(): void
    {
        $old = [['id' => 'C6-PROVENANCE', 'missingEvidence' => 'Build and final release']];
        $row = ['id' => 'C6-PROVENANCE', 'original' => $old[0], 'blocksCertification' => true, 'status' => 'blocking', 'categories' => ['A', 'E'],
            'affectedGates' => ['exact-pin-executable'], 'existingEvidence' => ['build'], 'remainingRequirements' => ['release'],
            'localVerification' => 'Verified', 'upstreamClarification' => 'Not needed', 'finalReleaseDependency' => 'Required', 'releaseImpact' => 'Blocked',
            'obligations' => [['requirement' => 'Build', 'status' => 'verified', 'evidence' => ['receipt']], ['requirement' => 'Final release', 'status' => 'open']]];
        self::assertSame([], B::dispositionErrors([$row], $old));
        $changed = $row; $changed['original']['missingEvidence'] = 'Build only'; self::assertNotSame([], B::dispositionErrors([$changed], $old));
        $changed = $row; $changed['status'] = 'resolved'; $changed['blocksCertification'] = false; self::assertNotSame([], B::dispositionErrors([$changed], $old));
        $changed = $row; unset($changed['obligations'][0]['evidence']); self::assertNotSame([], B::dispositionErrors([$changed], $old));
        self::assertNotSame([], B::dispositionErrors([], $old));
    }
}
