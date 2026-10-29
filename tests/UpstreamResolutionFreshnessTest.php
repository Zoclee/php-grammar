<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpGrammar\Tools\{UpstreamResolutionFreshness as E, Preservation86 as P, Certification86 as C, CertificationCurrent as A, CertificationCurrentRun as R};
require_once dirname(__DIR__) . '/tools/lib/CertificationCurrentRun.php';

final class UpstreamResolutionFreshnessTest extends TestCase
{
    private string $temporary;
    protected function setUp(): void
    {
        $this->temporary = str_replace('\\', '/', sys_get_temp_dir()) . '/php-upstream-freshness-' . bin2hex(random_bytes(8));
        mkdir($this->temporary);
        $root = dirname(__DIR__); $c = new C($root);
        $paths = [...array_keys(E::ANCHORS), ...array_keys(P::ANCHORS), ...array_keys(E::CHANGES), E::PROBES];
        $paths = [...$paths, ...array_keys($c->read('docs/8.6/certification-contract-remediation/phase7h-validation/evidence-files.json')['files'])];
        $old = $c->read(E::ORIGINAL);
        $paths = [...$paths, ...array_keys($old['inputHashes']), ...array_keys($old['fixtureHashes'])];
        foreach (array_keys(E::CHANGES) as $path) $paths[] = E::ROOT . '/predecessor/' . $path;
        foreach (array_unique($paths) as $p) {
            $target = $this->temporary . '/' . $p;
            if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
            copy($root . '/' . $p, $target);
        }
    }
    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            if ($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname());
        }
        rmdir($this->temporary);
    }
    private function report(): array { return (new C($this->temporary))->read(E::PROBES); }

    public function testCurrentSnapshotInventoryIncludesConsumedHistoricalAuthority(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__));
        $inventory = (new A($root))->inventory();
        $authority = 'docs/8.6/certification-contract-remediation/phase7h-validation/evidence-files.json';
        $required = E::ANCHORS + (new C($root))->read($authority)['files'];
        foreach ($required as $path => $hash) self::assertSame($hash, $inventory[$path] ?? null, $path);
    }

    public function testSuccessorPreservesEveryOriginalObservationAndDependency(): void
    {
        $e = new E($this->temporary);
        self::assertSame([], $e->probeErrors($this->report()));
        $v2 = (new P($this->temporary))->derive();
        $v3 = (new P($this->temporary, P::UPSTREAM_CONTRACT))->derive();
        self::assertSame($v2, $v3['predecessorTransition']);
        self::assertSame($e->transition(), $v3['upstreamTransition']);
        foreach ($v2['baselines'] as $path => $row) {
            self::assertSame($row['original'], $v3['baselines'][$path]['original']);
            $expected = $row['successor'];
            foreach (E::CHANGES as $p => [$before, $after]) $expected[$p] = $after;
            self::assertSame($expected, $v3['baselines'][$path]['successor']);
        }
        // Default v2 still refuses the Phase 7H documentary bytes.
        self::assertContains('Stale evidence digest: grammar/8.6/php.md', (new P($this->temporary))->verify($v2));
    }

    public static function evidenceMutations(): iterable
    {
        foreach (['observation', 'missing-observation', 'missing-dependency', 'fixture', 'source', 'oracle', 'claim'] as $kind) yield $kind => [$kind];
    }
    #[DataProvider('evidenceMutations')]
    public function testSuccessorCannotInventOrDropEvidence(string $kind): void
    {
        $r = $this->report();
        switch ($kind) {
            case 'observation': $r['observations'][0]['lint']['exit'] = 99; break;
            case 'missing-observation': array_pop($r['observations']); break;
            case 'missing-dependency': unset($r['inputHashes']['grammar/8.6/php.md']); break;
            case 'fixture': array_pop($r['fixtureHashes']); break;
            case 'source': $r['pins']['8.6'] = str_repeat('0', 40); break;
            case 'oracle': $r['interpreters']['8.6']['sha256'] = str_repeat('0', 64); break;
            case 'claim': $r['certificationGranted'] = true; break;
        }
        self::assertNotEmpty((new E($this->temporary))->probeErrors($r));
    }

    public static function changedFiles(): iterable
    {
        foreach ([E::ORIGINAL, 'grammar/8.6/php.md', 'grammar/8.6/php.ebnf', 'tools/8.6/change-map.json',
            E::ROOT . '/predecessor/grammar/8.6/php.md', 'docs/8.6/upstream-maintainer-resolution/24794-comments.json',
            'docs/8.6/upstream-maintainer-resolution/dispositions.json'] as $path) yield $path => [$path];
    }
    #[DataProvider('changedFiles')]
    public function testChangedInputsFailEvenWithAnUnchangedSuccessor(string $path): void
    {
        file_put_contents($this->temporary . '/' . $path, 'unauthorized');
        self::assertNotEmpty((new E($this->temporary))->probeErrors($this->report()));
    }

    public function testMissingAuthorityAndCallerSuppliedTransitionsFailClosed(): void
    {
        $p = new P($this->temporary, P::UPSTREAM_CONTRACT); $t = $p->derive();
        $t['changes']['grammar/8.6/php.ebnf'] = ['before', 'after'];
        self::assertSame(['Incorrect or tampered preservation transition'], $p->verify($t));
        unlink($this->temporary . '/' . E::ROOT . '/predecessor/grammar/8.6/php.md');
        self::assertNotEmpty($p->verify($t));
        self::assertContains('Unknown preservation contract', A::profileErrors(['preservation-contract' => 'future']));
    }

    public function testAuditProjectionRequiresTheExactVersionAndFullCorrespondence(): void
    {
        $original = ['parser' => ['all branches'], 'scanner' => ['all states'], 'unresolved' => ['PHP86-014']];
        $inputs = ['current' => 'digest'];
        $audit = ['contract' => 'php86-current-source-audit-v3', 'inputs' => $inputs, 'snapshot' => 'snapshot',
            'projection' => P::CHANGES + E::CHANGES, 'correspondence' => $original, 'certificationGranted' => false];
        self::assertSame([], R::auditErrors($audit, $inputs, 'snapshot', $original, P::UPSTREAM_CONTRACT));
        self::assertNotEmpty(R::auditErrors($audit, $inputs, 'snapshot', $original));
        unset($audit['correspondence']['unresolved']);
        self::assertNotEmpty(R::auditErrors($audit, $inputs, 'snapshot', $original, P::UPSTREAM_CONTRACT));
    }

    public function testVersionedDependencyCannotCloseAnyOriginalGateOrBlocker(): void
    {
        $legacy = []; foreach (C::gates() as $id => [$kind, $obligation]) $legacy[$id] = ['status' => 'passed'];
        $checks = array_fill_keys(A::CHECKS, true); $blockers = [];
        foreach (C::BLOCKERS as $id) $blockers[] = ['id' => $id, 'blocksCertification' => true, 'remainingRequirements' => ['Original requirements']];
        $gates = A::gates($legacy, $checks, $blockers, ['check'], P::UPSTREAM_CONTRACT);
        self::assertCount(36, $gates);
        self::assertSame('historical-preservation@v3', $gates['historical-preservation']['successorIdentity']);
        self::assertSame('blocked', $gates['folding-order']['result']);
        self::assertSame('blocked', $gates['discarded-hook-disposition']['result']);
        self::assertSame('blocked', $gates['writable-target-validity']['result']);
        $r = ['gates' => $gates, 'blockers' => $blockers, 'prerequisites' => ['historical-artifact-integrity85' => true, 'current-compatibility85' => true]];
        self::assertCount(16, A::claimErrors($r));
    }
}
