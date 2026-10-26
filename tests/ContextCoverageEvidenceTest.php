<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PhpGrammar\Tools\ContextCoverageEvidence as E;
require_once __DIR__ . '/../tools/lib/ContextCoverageEvidence.php';
final class ContextCoverageEvidenceTest extends TestCase
{
    public function testBindingFixturesAreDerivedFromNativeOperands(): void
    {
        $e = new E(str_replace('\\', '/', dirname(__DIR__)));
        foreach ($e->manifest()['binding'] as $case) {
            $native = $e->native('binding', $case);
            self::assertSame(1, $native['uniqueDerivations']);
            self::assertNotEmpty($native['operandSpans']);
        }
    }

    public function testProfileAndUnsupportedClaimsAreRejected(): void
    {
        $e = new E(str_replace('\\', '/', dirname(__DIR__)));
        $errors = $e->recordErrors(['certificationGranted' => true]);
        self::assertContains('Wrong evidence profile', $errors);
        self::assertContains('Unsupported claim: certificationGranted', $errors);
        self::assertContains('Unexpected observation membership', $errors);
        self::assertContains('Runtime/source custody changed', $errors);
    }

    public function testSourceFixturesAreImmutableInputs(): void
    {
        $e = new E(str_replace('\\', '/', dirname(__DIR__)));
        $case = $e->manifest()['binding'][0];
        $case['expression'] = '$a + $b';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Binding fixture derivation mismatch');
        $e->native('binding', $case);
    }

    public function testRetainedObservationTamperingIsRejected(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__));
        $e = new E($root);
        $path = $root . '/docs/8.6/context-coverage-closure/observations.json';
        self::assertFileExists($path);
        $report = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        // Test the record validator inside isolated certification snapshots too.
        // Rebase only this in-memory test copy; retained custody is checked by --check.
        $index = 0;
        foreach (['context', 'coverage', 'scanner', 'binding'] as $group) foreach ($e->manifest()[$group] as $case) {
            $row = &$report['observations'][$index++];
            if ($group === 'binding') {
                $row['originalDump']['command'] = $e->lintCommand($case, true);
                $row['forcedDump']['command'] = $e->lintCommand($case, true, $case['forcedFixture']);
            } else {
                $row['lint']['command'] = $e->lintCommand($case);
                if ($group === 'scanner') $row['tokenization']['command'] = $e->tokenCommand($case);
            }
            unset($row);
        }
        self::assertSame([], $e->recordErrors($report));
        $bad = $report; array_shift($bad['observations']);
        self::assertNotEmpty($e->recordErrors($bad));
        $bad = $report; $bad['observations'][0]['lint']['exit_code'] = 1;
        self::assertContains('Lint expectation: ' . $bad['observations'][0]['id'], $e->recordErrors($bad));
        $bad = $report;
        foreach ($bad['observations'] as &$row) if ($row['group'] === 'binding') $row['originalDump']['output'] = $row['forcedDump']['output'] = '';
        unset($row);
        self::assertNotEmpty($e->recordErrors($bad));
        $bad = $report;
        foreach ($bad['observations'] as &$row) if ($row['group'] === 'coverage') $row['native']['alternatives'] = $row['native']['productions'] = [];
        unset($row);
        self::assertNotEmpty($e->recordErrors($bad));
        $bad = $report; $bad['runtimeAfter']['coreDll'] = 'changed';
        self::assertContains('Runtime/source custody changed', $e->recordErrors($bad));
        $bad = $report; $bad['coverage']['totalAlternatives']--;
        self::assertContains('Coverage denominator changed', $e->recordErrors($bad));
    }
}
