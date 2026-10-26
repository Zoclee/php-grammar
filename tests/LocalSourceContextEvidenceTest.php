<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PhpGrammar\Tools\LocalSourceContextEvidence as E;
require_once __DIR__ . '/../tools/lib/LocalSourceContextEvidence.php';

final class LocalSourceContextEvidenceTest extends TestCase
{
    private function evidence(): E { return new E(str_replace('\\', '/', dirname(__DIR__))); }

    public function testLostIdentitiesAndUnsupportedClosureAreRejected(): void
    {
        $e = $this->evidence(); $r = $e->read('source-review.json'); $c = $e->read('context-inventory.json');
        self::assertSame([], $e->ledgerErrors($r, $c));
        $bad = $r; array_pop($bad['inventory']);
        self::assertContains('Changed or lost original hunk identity/disposition', $e->ledgerErrors($bad, $c));
        $bad = $r; $bad['inventory'][1]['effectiveDisposition'] = 'resolved';
        self::assertContains('Changed or lost original hunk identity/disposition', $e->ledgerErrors($bad, $c));
        $bad = $r; $bad['reviewQueue'][0]['diff'] .= 'tamper';
        self::assertNotEmpty($e->ledgerErrors($bad, $c));
        $bad = $c; $bad['rows'][0]['wholeDescriptionClosed'] = true;
        self::assertContains('Context description identity or predecessor evidence changed', $e->ledgerErrors($r, $bad));
        $bad = $r; $bad['discoveryComplete'] = true;
        self::assertContains('Unsupported closure claim', $e->ledgerErrors($bad, $c));
    }

    public function testObservationClaimsAndMissingMembershipAreRejected(): void
    {
        $errors = $this->evidence()->recordErrors(['certificationGranted' => true]);
        self::assertContains('Wrong contract/source profile', $errors);
        self::assertContains('Runtime identity/custody changed', $errors);
        self::assertContains('Unsupported evidence claim', $errors);
        self::assertContains('Observation membership mismatch', $errors);
    }

    public function testCompletionCriteriaAndOriginalBlockersArePreserved(): void
    {
        $e = $this->evidence(); $r = $e->read('work-items.json');
        self::assertSame([], $e->workItemErrors($r));
        $r['workItems'][0]['completionCriterion'] = 'One passing probe is enough';
        self::assertContains('Original backlog/blocker/gate requirements changed', $e->workItemErrors($r));
    }

    public function testAuthenticRowsCannotBeRelabeledOrGivenAnotherCommand(): void
    {
        $e = $this->evidence();
        $r = json_decode(file_get_contents(__DIR__ . '/../docs/8.6/local-source-context-remediation/observations.json'), true, 512, JSON_THROW_ON_ERROR);
        // Snapshot runs may relocate the repository; adjust only in-memory command paths.
        foreach ($r['observations'] as &$row) $row['command'] = $e->command($row['case']);
        unset($row);
        self::assertSame([], $e->recordErrors($r));
        $bad = $r; $bad['observations'][1]['exit_code'] = 0;
        self::assertContains('Observation expectation: ' . $bad['observations'][1]['id'], $e->recordErrors($bad));
        $bad = $r; $bad['observations'][1]['output'] = 'Unrelated failure';
        self::assertNotEmpty($e->recordErrors($bad));
        $bad = $r; $bad['observations'][0]['command'][0] = 'different-php';
        self::assertContains('Case/command mismatch: ' . $bad['observations'][0]['id'], $e->recordErrors($bad));
        $bad = $r; $bad['runtimeAfter']['coreDll'] = 'changed';
        self::assertContains('Runtime identity/custody changed', $e->recordErrors($bad));
    }
}
