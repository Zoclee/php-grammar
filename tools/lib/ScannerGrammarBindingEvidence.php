<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/ContextCoverageEvidence.php';
require_once __DIR__ . '/DifferentialInventory.php';

/** Additive source/coverage observations; never a certification or AST-equivalence oracle. */
final class ScannerGrammarBindingEvidence
{
    public const MANIFEST = 'tests/fixtures/php/8.6/scanner-grammar-binding/cases.json';
    public const OUT = 'docs/8.6/scanner-grammar-binding-closure';
    public const CONTRACT = 'php86-scanner-grammar-binding-v1';
    private Certification86 $files;
    private ContextCoverageEvidence $engine;
    public function __construct(public readonly string $root) { $this->files = new Certification86($root); $this->engine = new ContextCoverageEvidence($root); }
    public function manifest(): array { return $this->files->read(self::MANIFEST); }
    public function inputs(): array
    {
        $paths = [self::MANIFEST, 'tools/lib/ScannerGrammarBindingEvidence.php', 'tools/8.6/scanner-grammar-binding.php', 'tests/ScannerGrammarBindingEvidenceTest.php',
            'docs/8.6/context-coverage-closure/coverage-delta.json', 'docs/8.6/context-coverage-closure/scanner-inventory.json',
            'docs/8.6/context-coverage-closure/binding-inventory.json', 'docs/8.6/final-certification-readiness/remaining-work.json'];
        foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $r) {
            $paths[] = $r['fixture']; foreach (['forcedFixture', 'differentFixture'] as $key) if (isset($r[$key])) $paths[] = $r[$key];
        }
        return $this->files->hashes(array_unique([...array_keys($this->engine->inputs()), ...$paths]));
    }
    public function sourceErrors(): array
    {
        $errors = []; $m = $this->manifest(); $ids = array_column($m['sources'], 'id');
        if ($m['sourcePin'] !== Certification86::PIN || $m['contract'] !== self::CONTRACT || count($ids) !== count(array_unique($ids))) $errors[] = 'Source identity';
        foreach ($m['sources'] as $s) foreach (['.audit/php-php-src-f23b4c2/', '.audit/php86-exact/source/'] as $base) {
            $p = $this->root . '/' . $base . $s['path'];
            if (!is_file($p) || hash_file('sha256', $p) !== $s['fileSha256']) { $errors[] = 'Source hash: ' . $s['id']; continue; }
            if (isset($s['start']) && hash('sha256', implode('', array_slice(file($p), $s['start'] - 1, $s['end'] - $s['start'] + 1))) !== $s['sliceSha256']) $errors[] = 'Source range: ' . $s['id'];
        }
        foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($m[$group] as $r) if (!$r['sourceIds'] || array_diff($r['sourceIds'], $ids)) $errors[] = 'Missing source mapping: ' . $r['id'];
        return $errors;
    }
    private function run(array $command): array { return ['command' => $command, ...Support::run($command, $this->root)]; }
    public function generate(): array
    {
        $errors = [...$this->engine->authenticate(), ...$this->sourceErrors()]; if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $inputs = $this->inputs(); $runtime = $this->engine->runtimeIdentity(); $rows = [];
        foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $case) {
            $r = ['group' => $group, 'id' => $case['id'], 'fixture' => $case['fixture'], 'sha256' => $case['sha256'], 'native' => $this->engine->native($group, $case)];
            if ($group === 'binding') foreach (['originalDump' => 'fixture', 'forcedDump' => 'forcedFixture', 'differentDump' => 'differentFixture'] as $key => $path) $r[$key] = $this->run($this->engine->lintCommand($case, true, $case[$path]));
            else { $r['lint'] = $this->run($this->engine->lintCommand($case)); if ($group === 'scanner') $r['tokenization'] = $this->run($this->engine->tokenCommand($case)); }
            $rows[] = $r;
        }
        $r = ['contract' => self::CONTRACT, 'sourcePin' => Certification86::PIN, 'profile' => 'exact-pin-rc4', 'generatedAt' => gmdate('c'),
            'inputs' => $inputs, 'inputsUnchanged' => $inputs === $this->inputs(), 'runtimeBefore' => $runtime, 'runtimeAfter' => $this->engine->runtimeIdentity(),
            'observations' => $rows, 'coverage' => $this->engine->coverage(), 'certificationGranted' => false, 'instrumentedScannerHits' => false, 'astEquivalenceClaim' => false];
        $r['errors'] = $this->recordErrors($r); return $r;
    }
    public function recordErrors(array $report): array
    {
        $errors = [];
        if (($report['contract'] ?? '') !== self::CONTRACT || ($report['sourcePin'] ?? '') !== Certification86::PIN || ($report['profile'] ?? '') !== 'exact-pin-rc4') $errors[] = 'Wrong evidence identity';
        foreach (['certificationGranted', 'instrumentedScannerHits', 'astEquivalenceClaim'] as $key) if (($report[$key] ?? null) !== false) $errors[] = 'Unsupported claim: ' . $key;
        if (($report['inputsUnchanged'] ?? false) !== true || ($report['runtimeBefore'] ?? null) !== ($report['runtimeAfter'] ?? [])
            || ($report['runtimeBefore']['executable'] ?? '') !== CertificationCurrent::profiles()['exact-pin-rc4']
            || ($report['runtimeBefore']['coreDll'] ?? '') !== '1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f') $errors[] = 'Changed runtime/inputs';
        $i = 0;
        foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $case) {
            $r = $report['observations'][$i++] ?? [];
            foreach (['id', 'fixture', 'sha256'] as $key) if (($r[$key] ?? null) !== $case[$key]) $errors[] = 'Observation identity: ' . $case['id'];
            if (($r['group'] ?? '') !== $group) $errors[] = 'Observation group';
            if ($group === 'binding') {
                foreach (['originalDump' => 'fixture', 'forcedDump' => 'forcedFixture', 'differentDump' => 'differentFixture'] as $key => $path) if (($r[$key]['command'] ?? []) !== $this->engine->lintCommand($case, true, $case[$path]) || ($r[$key]['exit_code'] ?? -1) !== 0) $errors[] = 'Binding execution: ' . $case['id'];
                $a = ExactPinReconciliation::opcodeLines($r['originalDump']['output'] ?? ''); $b = ExactPinReconciliation::opcodeLines($r['forcedDump']['output'] ?? ''); $d = ExactPinReconciliation::opcodeLines($r['differentDump']['output'] ?? '');
                if (!$a || $a !== $b || !$d || $a === $d) $errors[] = 'Non-discriminating operand observation: ' . $case['id'];
                if (($r['native']['uniqueDerivations'] ?? 0) !== 1 || empty($r['native']['operandSpans'])) $errors[] = 'Missing native derivation';
                continue;
            }
            if (($r['lint']['command'] ?? []) !== $this->engine->lintCommand($case) || ($r['lint']['exit_code'] ?? -1) !== ($case['accepted'] ? 0 : 255)
                || DifferentialInventory::diagnostics($r['lint']['output'] ?? '') !== $case['diagnostics']) $errors[] = 'Lint boundary: ' . $case['id'];
            if ($group === 'coverage') {
                if (($r['native']['matched'] ?? null) !== $case['structural']) $errors[] = 'Structural boundary: ' . $case['id'];
                foreach ($case['selectedIdentities'] as $id) if (!in_array($id, $r['native']['alternatives'] ?? [], true)) $errors[] = 'Missing alternative: ' . $id;
            } else {
                if (($r['native']['accepted'] ?? null) !== $case['lexerAccepted'] || ($case['lexerAccepted'] && ($r['native']['bytePreserved'] ?? false) !== true)) $errors[] = 'Scanner boundary: ' . $case['id'];
                if (($r['tokenization']['command'] ?? []) !== $this->engine->tokenCommand($case) || ($r['tokenization']['exit_code'] ?? -1) !== 0) $errors[] = 'Token transport';
                $tokens = json_decode($r['tokenization']['output'] ?? '', true);
                if ($tokens !== $case['expectedTokens']) $errors[] = 'Token sequence/error: ' . $case['id'];
                if (($r['native']['lexemes'] ?? null) !== $case['expectedNativeLexemes']) $errors[] = 'Native token boundaries: ' . $case['id'];
            }
        }
        if (count($report['observations'] ?? []) !== $i) $errors[] = 'Observation membership';
        if (($report['coverage']['totalProductions'] ?? 0) !== 361 || ($report['coverage']['totalAlternatives'] ?? 0) !== 800) $errors[] = 'Changed coverage denominator';
        return $errors;
    }
    public function verify(string $path): array
    {
        try {
            $before = $this->inputs(); $r = $this->files->read($path); $receipt = $this->files->read($path . '.receipt.json');
            $errors = [...$this->recordErrors($r), ...$this->sourceErrors(), ...$this->engine->authenticate()];
            if (($receipt['sha256'] ?? '') !== hash_file('sha256', $this->root . '/' . $path) || ($r['inputs'] ?? []) !== $before || ($r['errors'] ?? null) !== []) $errors[] = 'Stale report/receipt';
            $i = 0; foreach (['coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $case) if ($r['observations'][$i++]['native'] !== $this->engine->native($group, $case)) $errors[] = 'Native replay: ' . $case['id'];
            if ($r['coverage'] !== $this->engine->coverage() || $before !== $this->inputs()) $errors[] = 'Coverage/input freshness';
            return $errors;
        } catch (\Throwable $e) { return ['Evidence unavailable: ' . $e->getMessage()]; }
    }
}
