<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;

use PhpGrammar\Tools\ContextEvidence as E;
use PhpGrammar\Tools\Support as S;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/ContextEvidence.php';

final class ContextEvidenceTest extends TestCase
{
    private string $root;
    private string $temporary;
    protected function setUp(): void
    {
        $this->root = str_replace('\\', '/', dirname(__DIR__));
        $this->temporary = str_replace('\\', '/', sys_get_temp_dir()) . '/context-evidence-' . bin2hex(random_bytes(8));
        mkdir($this->temporary);
    }
    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->temporary);
    }
    private function put(string $path, string $bytes): void
    {
        $target = $this->temporary . '/' . $path;
        if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
        file_put_contents($target, $bytes);
    }
    private function fixture(): E
    {
        $paths = [E::HISTORY, E::CASES, 'tools/8.6/context-source-lock.json', E::BUILD, 'docs/8.6/exact-pin-build/build-receipt.json'];
        foreach (json_decode(file_get_contents($this->root . '/' . E::CASES), true) as $case) $paths[] = $case['fixture'];
        foreach (array_unique($paths) as $path) $this->put($path, file_get_contents($this->root . '/' . $path));
        return new E($this->temporary);
    }
    private function config(E $e, string $profile = E::EXACT): array
    {
        return array_replace($e->defaults(), ['profile' => $profile, 'php86' => $this->temporary . '/candidate.exe', 'sources86' => $this->temporary . '/source']);
    }
    /** Synthetic receipts exercise validation only; they never attest actual execution. */
    private function observations(E $e): array
    {
        $history = json_decode(file_get_contents($this->root . '/' . E::HISTORY), true)['observations'];
        $cases = array_column(json_decode(file_get_contents($this->root . '/' . E::CASES), true), null, 'id');
        $config = $this->config($e);
        foreach ($history as &$row) {
            $case = $cases[$row['id']]; $version = $row['version'];
            $row['fixture'] = $case['fixture'];
            $row['expected'] = ['accepted' => $case['accept' . str_replace('.', '', $version)],
                'diagnostic' => $version === '8.6' ? $case['diagnostic86'] : null, 'output' => $version === '8.6' ? $case['output86'] : null];
            foreach (['lint', 'result'] as $step) {
                $row[$step] = ['command' => [$config['php' . str_replace('.', '', $version)], ...E::FLAGS,
                    ...($step === 'lint' || $case['stage'] === 'lint' ? ['-l'] : []), $this->root . '/' . $case['fixture']],
                    'exit' => $row[$step]['exit'], 'stdout' => $row[$step]['output'], 'stderr' => ''];
            }
        }
        return $history;
    }
    public function testProfileSelectionIsExplicitAndCannotEquateDistributionWithExactSource(): void
    {
        $e = new E($this->root);
        self::assertSame('', $e->defaults()['profile']);
        self::assertNotSame($e->profile(E::HISTORICAL)['executableSha256'], $e->profile(E::EXACT)['executableSha256']);
        self::assertStringContainsString('not attested', $e->profile(E::HISTORICAL)['sourceBinding']);
        foreach (['', 'latest', '8.6.0RC4'] as $profile) self::assertNotSame([], $e->preflight(['profile' => $profile]));
    }
    public function testWrongAndModifiedExecutableCannotBeExecuted(): void
    {
        $e = $this->fixture(); $config = $this->config($e);
        foreach (['not an executable', 'not an executable modified'] as $bytes) {
            $this->put('candidate.exe', $bytes);
            self::assertContains('Wrong executable identity/profile: 8.6', $e->preflight($config));
            try { $e->generate($config); self::fail('Untrusted candidate was run'); }
            catch (\RuntimeException $ex) { self::assertStringContainsString('Wrong executable identity/profile', $ex->getMessage()); }
        }
    }
    public function testMissingAndTamperedBuildProvenanceAndWrongCommitAreRejected(): void
    {
        $e = $this->fixture(); $config = $this->config($e);
        $original = file_get_contents($this->temporary . '/' . E::BUILD);
        unlink($this->temporary . '/' . E::BUILD);
        self::assertContains('Missing evidence artifact: ' . E::BUILD, $e->preflight($config));
        foreach (['sourceCommit' => str_repeat('0', 40), 'configuration' => '--enable-everything', 'sha256' => str_repeat('0', 64)] as $key => $value) {
            $record = json_decode($original, true); $record[$key] = $value; $this->put(E::BUILD, S::json($record));
            self::assertContains('Stale evidence digest: ' . E::BUILD, $e->preflight($config));
        }
        $this->put(E::BUILD, $original);
        $this->put('docs/8.6/exact-pin-build/build-receipt.json', '{}');
        self::assertContains('Stale evidence digest: docs/8.6/exact-pin-build/build-receipt.json', $e->preflight($config));
        $config['provenance-record'] = 'unreviewed.json';
        self::assertContains('Unreviewed provenance record', $e->preflight($config));
    }
    public function testHistoricalMissingStaleObservationsAndFixtureChangesFailClosed(): void
    {
        $e = $this->fixture(); $config = $this->config($e, E::HISTORICAL);
        $original = file_get_contents($this->temporary . '/' . E::HISTORY);
        unlink($this->temporary . '/' . E::HISTORY);
        self::assertContains('Missing evidence artifact: ' . E::HISTORY, $e->preflight($config));
        $this->put(E::HISTORY, '{}');
        self::assertContains('Stale evidence digest: ' . E::HISTORY, $e->preflight($config));
        $this->put(E::HISTORY, $original);
        $cases = json_decode(file_get_contents($this->root . '/' . E::CASES), true);
        $this->put($cases[0]['fixture'], '<?php // modified');
        self::assertContains('Missing/stale contextual input: ' . $cases[0]['id'], $e->preflight($config));
    }
    public function testCompleteMembershipAndRecordedOutcomesAreMandatory(): void
    {
        $e = new E($this->root); $config = $this->config($e); $rows = $this->observations($e);
        self::assertCount(126, $rows); self::assertSame([], $e->observationErrors($rows, $config));
        $missing = $rows; array_pop($missing);
        self::assertContains('Missing or extra contextual observations', $e->observationErrors($missing, $config));
        $duplicate = $rows; $duplicate[1] = $duplicate[0];
        self::assertNotSame([], $e->observationErrors($duplicate, $config));
        foreach (['stage', 'fixtureSha256', 'finding'] as $key) {
            $changed = $rows; $changed[0][$key] = 'tampered'; self::assertNotSame([], $e->observationErrors($changed, $config));
        }
        foreach (['exit' => 0, 'stdout' => 'tampered', 'command' => ['untrusted.exe']] as $key => $value) {
            $changed = $rows; $changed[0]['result'][$key] = $value; self::assertNotSame([], $e->observationErrors($changed, $config));
        }
    }
    public function testReadOnlyVerificationRejectsTamperingAndWrongProfile(): void
    {
        $e = new E($this->temporary); $path = 'observations.json';
        $r = ['profile' => $e->profile(E::EXACT), 'observations' => []];
        $this->put($path, S::json($r));
        $this->put($path . '.receipt.json', S::json(['reportSha256' => hash_file('sha256', $this->temporary . '/' . $path)]));
        $before = file_get_contents($this->temporary . '/' . $path);
        self::assertNotSame([], $e->verify($path, E::HISTORICAL));
        self::assertSame($before, file_get_contents($this->temporary . '/' . $path));
        $this->put($path, $before . ' ');
        self::assertSame(['Changed contextual evidence receipt'], $e->verify($path, E::EXACT));
        unlink($this->temporary . '/' . $path . '.receipt.json');
        self::assertNotSame([], $e->verify($path, E::EXACT));
        self::assertFileDoesNotExist($this->temporary . '/' . $path . '.receipt.json');
    }
}
