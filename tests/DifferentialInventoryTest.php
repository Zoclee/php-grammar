<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpGrammar\Tools\{DifferentialInventory as E, DifferentialCertification as V3, Certification86 as C, CertificationCurrent as V2};
require_once dirname(__DIR__) . '/tools/lib/DifferentialCertification.php';

final class DifferentialInventoryTest extends TestCase
{
    private string $temporary;
    protected function setUp(): void
    {
        $this->temporary = str_replace('\\', '/', sys_get_temp_dir()) . '/php-grammar-differential-' . bin2hex(random_bytes(8));
        mkdir($this->temporary);
        foreach (array_keys(E::ANCHORS) as $p) $this->put($p, file_get_contents(dirname(__DIR__) . '/' . $p));
    }
    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) { if ($file->isDir()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
        rmdir($this->temporary);
    }
    private function put(string $path, string $bytes): void
    {
        $file = $this->temporary . '/' . $path; if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true); file_put_contents($file, $bytes);
    }
    public static function mutations(): iterable
    {
        foreach (['missing-addition', 'predecessor', 'revision', 'classification', 'source', 'profile', 'expectation', 'stage', 'original-outcome'] as $m) yield $m => [$m];
    }
    #[DataProvider('mutations')]
    public function testUnauthorizedInventoryChangesAreRejected(string $mutation): void
    {
        $e = new E($this->temporary); $inventory = $e->derive();
        self::assertCount(339, $inventory['cases']); self::assertCount(54, $inventory['separateInventories']);
        switch ($mutation) {
            case 'missing-addition': array_pop($inventory['cases']); break;
            case 'predecessor': $inventory['predecessor']['sha256'] = str_repeat('0', 64); break;
            case 'revision': $inventory['revision'] = 'unreviewed'; break;
            case 'classification': $inventory['cases'][313]['classification'] = 'runtime-failure'; break;
            case 'source': $inventory['cases'][313]['sourceEvidence'] = []; break;
            case 'profile': $inventory['profile'] = 'historical-rc4'; break;
            case 'expectation': $inventory['cases'][313]['expected']['lintExit'] = 255; break;
            case 'stage': $inventory['cases'][317]['stage'] = 'valid in every live context'; break;
            case 'original-outcome': $inventory['cases'][0]['original']['lintExit'] = 255; break;
        }
        self::assertNotEmpty($e->inventoryErrors($inventory));
    }
    public function testCorpusChangesAndFixtureContentAreRejected(): void
    {
        $e = new E($this->temporary); $inventory = $e->derive();
        $paths = [...array_column($inventory['cases'], 'fixture'), ...array_keys($e->read(E::PREDECESSOR)['inputs'])];
        foreach (array_unique($paths) as $p) $this->put($p, file_get_contents(dirname(__DIR__) . '/' . $p));
        self::assertSame([], $e->inventoryErrors($inventory));
        foreach ([$inventory['cases'][0]['fixture'], $inventory['cases'][313]['fixture']] as $path) {
            $bytes = file_get_contents($this->temporary . '/' . $path);
            unlink($this->temporary . '/' . $path);
            self::assertContains('Unexplained corpus additions or removals', $e->inventoryErrors($inventory));
            $this->put($path, $bytes . '// modified');
            self::assertContains('Stale evidence digest: ' . $path, $e->inventoryErrors($inventory));
            $this->put($path, $bytes);
        }
        $this->put('tests/fixtures/php/8.6/valid/unreviewed.php', '<?php echo 1;');
        self::assertContains('Unexplained corpus additions or removals', $e->inventoryErrors($inventory));
    }
    public function testReplacementAuthorityCannotAuthorizeDifferentExpectations(): void
    {
        $this->put(E::REVIEW, '{}');
        $this->expectException(\RuntimeException::class);
        (new E($this->temporary))->derive();
    }
    private function report(E $e, array $inventory): array
    {
        $runtime = ['executable' => V2::profiles()['exact-pin-rc4'], 'coreDll' => '1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f'];
        $rows = []; foreach ($inventory['cases'] as $r) {
            $expected = $r['expected']; $output = implode("\n", $expected['diagnostics']) . "\n";
            if ($expected['lintExit'] === 0) $output .= 'No syntax errors detected in ' . $r['fixture'];
            $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'classification' => $r['classification'], 'stage' => $r['stage'],
                'native' => array_intersect_key($expected, array_flip(['lexicalAccepted', 'structuralAccepted'])),
                'lint' => ['command' => $e->command($r), 'exit_code' => $expected['lintExit'], 'output' => $output]];
        }
        return ['contract' => E::CONTRACT, 'revision' => E::REVISION, 'sourcePin' => C::PIN, 'profile' => 'exact-pin-rc4',
            'inputsUnchanged' => true, 'runtimeBefore' => $runtime, 'runtimeAfter' => $runtime, 'sourceErrorsAfter' => [],
            'observations' => $rows, 'nativeContextualEnforcement' => false, 'certificationGranted' => false];
    }
    public function testIncorrectProfileLostObservationsAndUnsupportedOutcomesFail(): void
    {
        $e = new E($this->temporary); $inventory = $e->derive(); $r = $this->report($e, $inventory);
        self::assertSame([], $e->recordErrors($r, $inventory));
        $bad = $r; $bad['profile'] = 'historical-rc4'; self::assertNotEmpty($e->recordErrors($bad, $inventory));
        $bad = $r; array_pop($bad['observations']); self::assertContains('Incomplete observation membership', $e->recordErrors($bad, $inventory));
        $bad = $r; $bad['observations'][313]['lint']['exit_code'] = 255; self::assertNotEmpty($e->recordErrors($bad, $inventory));
        $bad = $r; $bad['observations'][0]['lint']['output'] = 'No syntax errors detected in x.php'; self::assertNotEmpty($e->recordErrors($bad, $inventory));
        $bad = $r; $bad['nativeContextualEnforcement'] = true; self::assertNotEmpty($e->recordErrors($bad, $inventory));
        $bad = $r; $bad['runtimeAfter']['coreDll'] = 'changed'; self::assertNotEmpty($e->recordErrors($bad, $inventory));
    }
    public function testStaleInputsAndTamperedOutputReceiptsFail(): void
    {
        $inputs = ['fixture' => 'a']; $r = ['inputs' => $inputs, 'inventorySha256' => 'inventory', 'errors' => []];
        self::assertSame([], E::freshnessErrors($r, $inputs, 'inventory', ['sha256' => 'report'], 'report'));
        self::assertContains('Stale differential inputs', E::freshnessErrors($r, ['fixture' => 'changed'], 'inventory', ['sha256' => 'report'], 'report'));
        self::assertContains('Report receipt mismatch', E::freshnessErrors($r, $inputs, 'inventory', ['sha256' => 'report'], 'changed'));
        self::assertContains('Stale differential inputs', E::freshnessErrors($r, $inputs, 'new revision', ['sha256' => 'report'], 'report'));
    }
    public function testDiagnosticNormalizationDoesNotDropSubstantiveInClauses(): void
    {
        self::assertSame(['Fatal error: Cannot use yield in constant expression'], E::diagnostics("Fatal error: Cannot use yield in constant expression in C:/temp/case.php on line 2\n"));
    }
    public function testSuccessorChangesOnlyTheReviewedGateAndRetainsItsOriginal(): void
    {
        $base = ['configuration' => ['php85' => 'driver'], 'gates' => []];
        foreach (C::gates() as $id => [$kind, $obligation]) $base['gates'][$id] = ['id' => $id, 'obligation' => $obligation, 'requiredInputs' => ['original'], 'legacy' => ['status' => 'failed'], 'result' => 'blocked'];
        $next = V3::gates($base, 'report.json'); self::assertCount(36, $next);
        foreach ($base['gates'] as $id => $r) if ($id !== 'ordinary-differential') self::assertSame($r, $next[$id]);
        self::assertSame($base['gates']['ordinary-differential'], $next['ordinary-differential']['predecessorCurrentRow']);
        self::assertSame($base['gates']['ordinary-differential']['obligation'], $next['ordinary-differential']['obligation']);
        self::assertSame('exact-pin-rc4', $next['ordinary-differential']['executableProfile']);
        self::assertSame([], V3::predecessorFailureErrors(['cases' => 313, 'errors' => [V3::MEMBERSHIP_FAILURE]]));
        self::assertNotEmpty(V3::predecessorFailureErrors(['cases' => 313, 'errors' => [V3::MEMBERSHIP_FAILURE, 'Outcome mismatch']]));
        self::assertNotEmpty(V3::predecessorFailureErrors(['cases' => 312, 'errors' => [V3::MEMBERSHIP_FAILURE]]));
    }
    public function testInvalidReadOnlyCheckLeavesEvidenceUntouched(): void
    {
        $this->put('bad.json', '{}'); $this->put('sentinel', 'unchanged'); $c = new C($this->temporary);
        $before = $c->hashes(['bad.json', 'sentinel']);
        self::assertNotEmpty((new E($this->temporary))->verify('bad.json'));
        self::assertSame([], $c->verifyHashes($before));
        self::assertNotEmpty((new V3($this->temporary))->verify('bad.json'));
        self::assertSame([], $c->verifyHashes($before));
    }
}
