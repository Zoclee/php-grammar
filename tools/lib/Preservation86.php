<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/Certification86.php';
require_once __DIR__ . '/HistoricalEvidence.php';
require_once __DIR__ . '/UpstreamResolutionFreshness.php';

/** Reviewed, closed transition: no caller-supplied allowlist or learned hashes. */
final class Preservation86
{
    public const CONTRACT = 'php86-preservation-v2';
    public const UPSTREAM_CONTRACT = 'php86-preservation-v3';
    public const ANCHORS = [
        'docs/8.6/certification-baseline.json' => 'd48a8d06f372e7d97377b4fd5f070abb13c5d2071f9c1bad046bc614ce6acd49',
        'docs/8.6/source-audit-baseline.json' => '45adabc6d0890584e546a7c480dcd855a9049c556f14e94deca495e1277621d0',
        'docs/8.5/compatibility/phase7c2-verified/evidence.json' => '90bbd7d24c1e4c53089f215d63c6f45baf6fbffc1a9a79a08bb742fd830b6145',
        'docs/8.6/source-completeness-ledger.json' => 'c5b0d2e2e56f0bcbcfe2423b291072feca0c7b2ddebf124b5076fc01f2b4d827',
        'docs/8.6/certification-blocker-closure/dispositions.json' => 'fa5e029e31f74e8a0a8115dc1e298e02254e2b498d207b7628a0ebf30ceefb87',
    ];
    public const CHANGES = [
        'docs/api.md' => ['6a3f837b52f626f6bf6844990846ffd830691ca527c496cb1fe32143e2d2b630', '251c58bafc4d8097c8f462d133b062bc0c390724af3312cc032a508d394eab15'],
        'docs/conformance-policy.md' => ['4be4aa15365d1c2bc11936d479f9a10f138283da6be627ca47b94d83046297e8', '058d638b95c66cd5bd54fc8ea1f27a3dc59a50785e5a9d8047f9a0d719b4af2f'],
        'docs/versioning.md' => ['60a5e6f3faf0fb49a857cace8c665eb94ff8f90e656925cf943d26936b51272a', '3a741eb85bc8c49e9453c75471912f083a429b61fb68742fdf6ae38402c3563e'],
    ];
    public function __construct(private readonly string $root, private readonly string $contract = self::CONTRACT)
    {
        if (!in_array($contract, [self::CONTRACT, self::UPSTREAM_CONTRACT], true)) throw new \RuntimeException('Unknown preservation contract');
    }

    public function changes(): array
    {
        return self::CHANGES + ($this->contract === self::UPSTREAM_CONTRACT ? UpstreamResolutionFreshness::CHANGES : []);
    }

    public function predecessorPath(string $path): string
    {
        if (!isset($this->changes()[$path])) throw new \RuntimeException('Unreviewed projection path');
        return $this->root . '/' . (isset(self::CHANGES[$path]) ? '.audit/phase7b-certification/' : UpstreamResolutionFreshness::ROOT . '/predecessor/') . $path;
    }

    public function derive(): array
    {
        $c = new Certification86($this->root);
        $errors = $c->verifyHashes(self::ANCHORS);
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $prior = $c->read('docs/8.5/compatibility/phase7c2-verified/evidence.json');
        $baselines = [];
        foreach (['docs/8.6/certification-baseline.json', 'docs/8.6/source-audit-baseline.json'] as $path) {
            $original = $c->read($path)['hashes']; $successor = $original;
            foreach (self::CHANGES as $p => [$before, $after]) {
                if (($original[$p] ?? null) !== $before || ($prior['dependencies'][$p]['sha256'] ?? null) !== $after) throw new \RuntimeException('Unauthenticated transition: ' . $p);
                $successor[$p] = $after;
            }
            $baselines[$path] = ['original' => $original, 'successor' => $successor];
        }
        $result = ['contract' => self::CONTRACT, 'authority' => 'Phase 7C.2 hybrid policy/API additions; Phase 7C.7 restricted successor transition',
            'anchors' => self::ANCHORS, 'changes' => self::CHANGES, 'baselines' => $baselines,
            'historicalCertificationRenewed' => false, 'certificationGranted' => false];
        if ($this->contract === self::UPSTREAM_CONTRACT) {
            $freshness = new UpstreamResolutionFreshness($this->root);
            $errors = $freshness->authorityErrors();
            if ($errors) throw new \RuntimeException(implode('; ', $errors));
            $result['predecessorTransition'] = $result;
            $result['contract'] = self::UPSTREAM_CONTRACT;
            $result['authority'] = 'Phase 7H.1: exact Phase 7H informative bytes and minimal current-evidence test transition; no caller-extensible exceptions.';
            $result['upstreamTransition'] = $freshness->transition();
            $result['changes'] = $this->changes();
            foreach ($result['baselines'] as &$row) foreach (UpstreamResolutionFreshness::CHANGES as $path => [$before, $after]) {
                if (($row['original'][$path] ?? null) !== $before) throw new \RuntimeException('Unexpected original preservation identity: ' . $path);
                $row['successor'][$path] = $after;
            }
            unset($row);
        }
        return $result;
    }

    public function verify(array $transition): array
    {
        try {
            if ($transition !== $this->derive()) return ['Incorrect or tampered preservation transition'];
            $c = new Certification86($this->root); $errors = [];
            foreach ($transition['baselines'] as $row) $errors = [...$errors, ...$c->verifyHashes($row['successor'])];
            return array_values(array_unique($errors));
        } catch (\Throwable $e) { return [$e->getMessage()]; }
    }

    /** A projection is allowed only at the three preservation-only document reads. */
    public function projectionErrors(string $snapshot, array $inputs): array
    {
        $errors = $this->verify($this->derive()); $c = new Certification86($snapshot);
        foreach ($this->changes() as $path => [$before, $after]) {
            if (($inputs[$path] ?? null) !== $after) $errors[] = 'Projection lacks current policy dependency: ' . $path;
            $inputs[$path] = $before;
        }
        return [...$errors, ...$c->verifyHashes($inputs)];
    }
}
