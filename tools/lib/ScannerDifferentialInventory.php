<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/ScannerGrammarBindingEvidence.php';

/** Phase 7F successor: retain all 339 predecessor rows, add only reviewed ordinary witnesses. */
final class ScannerDifferentialInventory
{
    public const CONTRACT = 'php86-ordinary-differential-v3';
    public const REVISION = 'phase7f-rc4';
    public const INVENTORY = 'docs/8.6/scanner-grammar-binding-closure/inventory.json';
    private DifferentialInventory $old;
    private Certification86 $files;
    public function __construct(public readonly string $root) { $this->old = new DifferentialInventory($root); $this->files = new Certification86($root); }
    public function derive(): array
    {
        $previous = $this->files->read(DifferentialInventory::INVENTORY);
        if ($previous !== $this->old->derive() || count($previous['cases']) !== 339) throw new \RuntimeException('Predecessor inventory changed');
        $review = $this->files->read(ScannerGrammarBindingEvidence::MANIFEST); $cases = $previous['cases'];
        foreach ($review['coverage'] as $r) {
            if (!str_starts_with($r['fixture'], 'tests/fixtures/php/8.6/valid/coverage7f-')) continue;
            if (!$r['structural'] || !$r['accepted'] || !$r['selectedIdentities'] || !$r['sourceIds']) throw new \RuntimeException('Unreviewed ordinary addition');
            $sources = array_values(array_filter($review['sources'], static fn($s) => in_array($s['id'], $r['sourceIds'], true)));
            if (count($sources) !== count($r['sourceIds'])) throw new \RuntimeException('Missing source mapping');
            $cases[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'origin' => 'phase7f', 'id' => $r['id'], 'kind' => 'valid',
                'classification' => 'accepted-in-lint-and-structure', 'stage' => $r['stage'],
                'expected' => ['lexicalAccepted' => true, 'structuralAccepted' => true, 'lintExit' => 0, 'diagnostics' => $r['diagnostics']],
                'sourceEvidence' => ['pin' => Certification86::PIN, 'manifest' => ScannerGrammarBindingEvidence::MANIFEST, 'sources' => $sources, 'selectedIdentities' => $r['selectedIdentities']],
                'inclusion' => 'Reviewed whole-source alternative witness; compiler/runtime completeness is not inferred.'];
        }
        return ['contract' => self::CONTRACT, 'revision' => self::REVISION, 'predecessor' => ['path' => DifferentialInventory::INVENTORY,
            'sha256' => hash_file('sha256', $this->root . '/' . DifferentialInventory::INVENTORY), 'cases' => 339, 'outcomesChanged' => false],
            'reviewSha256' => hash_file('sha256', $this->root . '/' . ScannerGrammarBindingEvidence::MANIFEST), 'cases' => $cases,
            'separateInventories' => $previous['separateInventories'], 'additionalSeparateEvidence' => ScannerGrammarBindingEvidence::MANIFEST,
            'nativeContextualEnforcement' => false, 'runtimeExecutionAuthorized' => false, 'certificationGranted' => false];
    }
    public function inputs(): array { return $this->files->hashes(array_unique([...array_keys($this->old->inputs()), self::INVENTORY, ScannerGrammarBindingEvidence::MANIFEST, 'tools/lib/ScannerDifferentialInventory.php', 'tools/8.6/scanner-differential-inventory.php'])); }
    public function inventoryErrors(array $r): array
    {
        try {
            if ($r !== $this->derive()) return ['Incorrect inventory successor'];
            $paths = array_column($r['cases'], 'fixture'); sort($paths);
            return [...($paths === $this->old->corpus() ? [] : ['Unexplained corpus membership']), ...$this->old->originalErrors(),
                ...$this->files->verifyHashes(array_column($r['cases'], 'sha256', 'fixture'))];
        } catch (\Throwable $e) { return [$e->getMessage()]; }
    }
    public function recordErrors(array $r, array $inventory): array
    {
        if (($r['contract'] ?? '') !== self::CONTRACT || ($r['revision'] ?? '') !== self::REVISION) return ['Incorrect successor identity'];
        // Reuse the unchanged observation validator for arbitrary reviewed case rows;
        // inventory authority and membership are checked separately above.
        $r['contract'] = DifferentialInventory::CONTRACT; $r['revision'] = DifferentialInventory::REVISION;
        return $this->old->recordErrors($r, $inventory);
    }
    public function generate(): array
    {
        $inventory = $this->files->read(self::INVENTORY); $auth = new ContextCoverageEvidence($this->root);
        $errors = [...$this->inventoryErrors($inventory), ...$auth->authenticate(), ...(new ScannerGrammarBindingEvidence($this->root))->sourceErrors()];
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $inputs = $this->inputs(); $runtime = $auth->runtimeIdentity(); $rows = [];
        foreach ($inventory['cases'] as $r) $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'classification' => $r['classification'], 'stage' => $r['stage'],
            'native' => $this->old->native($r), 'lint' => ['command' => $this->old->command($r), ...Support::run($this->old->command($r), $this->root)]];
        $r = ['contract' => self::CONTRACT, 'revision' => self::REVISION, 'inventorySha256' => hash_file('sha256', $this->root . '/' . self::INVENTORY),
            'sourcePin' => Certification86::PIN, 'profile' => 'exact-pin-rc4', 'generatedAt' => gmdate('c'), 'inputs' => $inputs, 'inputsUnchanged' => $inputs === $this->inputs(),
            'runtimeBefore' => $runtime, 'runtimeAfter' => $auth->runtimeIdentity(), 'sourceErrorsAfter' => $auth->sourceErrors(), 'observations' => $rows,
            'nativeContextualEnforcement' => false, 'certificationGranted' => false];
        $r['errors'] = $this->recordErrors($r, $inventory); return $r;
    }
    public function verify(string $path): array
    {
        try {
            $inputs = $this->inputs(); $r = $this->files->read($path); $inventory = $this->files->read(self::INVENTORY);
            $errors = [...$this->inventoryErrors($inventory), ...$this->recordErrors($r, $inventory),
                ...DifferentialInventory::freshnessErrors($r, $inputs, hash_file('sha256', $this->root . '/' . self::INVENTORY), $this->files->read($path . '.receipt.json'), hash_file('sha256', $this->root . '/' . $path)),
                ...(new ContextCoverageEvidence($this->root))->authenticate(), ...(new ScannerGrammarBindingEvidence($this->root))->sourceErrors()];
            foreach ($inventory['cases'] as $i => $case) if ($r['observations'][$i]['native'] !== $this->old->native($case)) $errors[] = 'Native replay: ' . $case['fixture'];
            if ($inputs !== $this->inputs()) $errors[] = 'Inputs changed during check';
            return $errors;
        } catch (\Throwable $e) { return ['Successor unavailable: ' . $e->getMessage()]; }
    }
}
