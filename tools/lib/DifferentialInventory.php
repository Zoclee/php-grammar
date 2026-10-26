<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\{Lexer, LexerException};
require_once __DIR__ . '/ContextCoverageEvidence.php';
require_once __DIR__ . '/CertificationCurrent.php';

/** Closed, additive transition; never infer expected outcomes from a fresh execution. */
final class DifferentialInventory
{
    public const CONTRACT = 'php86-ordinary-differential-v2';
    public const REVISION = 'phase7c10-rc4';
    public const PREDECESSOR = 'docs/8.6/source-audit-boundaries.json';
    public const REVIEW = 'tests/fixtures/php/8.6/context-coverage-cases.json';
    public const OBSERVATIONS = 'docs/8.6/context-coverage-closure/observations-verified.json';
    public const INVENTORY = 'docs/8.6/differential-inventory-reconciliation/inventory.json';
    public const ANCHORS = [
        self::PREDECESSOR => '262e9549c65c36d8e75930bd6c14e9c8b7dbbdfe50f98a8c62e732f9a59e0f04',
        self::REVIEW => 'eb4dc310cf27e429d56f96dcd141ab51805b0d7f27283805f5ce8ab663084188',
        self::OBSERVATIONS => '267bf3fd1fbd04173f8a0c37a0b650ef445cb45a46a9c346bc60904b646c462e',
    ];
    private Certification86 $files;
    public function __construct(public readonly string $root) { $this->files = new Certification86($root); }
    public function read(string $path): array { return $this->files->read($path); }
    public function binary(): string { return (new ContextCoverageEvidence($this->root))->binary(); }

    public function originalErrors(): array
    {
        try {
            $errors = $this->files->verifyHashes([self::PREDECESSOR => self::ANCHORS[self::PREDECESSOR]]);
            if ($errors) return $errors;
            $old = $this->read(self::PREDECESSOR);
            if ($old['sourcePin'] !== Certification86::PIN || $old['interpreterSha256'] !== CertificationCurrent::profiles()['historical-rc4']
                || $old['exactPinExecutableVerified'] !== false || $old['errors'] !== []) $errors[] = 'Incorrect historical identity';
            $hashes = $old['inputs']; foreach ($old['cases'] as $r) $hashes[$r['fixture']] = $r['sha256'];
            return [...$errors, ...$this->files->verifyHashes($hashes)];
        } catch (\Throwable $e) { return ['Historical inventory unavailable: ' . $e->getMessage()]; }
    }

    /** Primary diagnostic messages only; discard transport paths, line numbers and stack frames. */
    public static function diagnostics(string $output): array
    {
        preg_match_all('/^(?:PHP )?(Deprecated|Warning|Notice|Fatal error|Parse error): (.*)$/m', str_replace("\r\n", "\n", $output), $matches, PREG_SET_ORDER);
        return array_map(static function($m): string {
            $message = trim($m[2]); $at = strrpos($message, ' in ');
            if ($at !== false && preg_match('/\.php(?: on line \d+|:\d+)$/', substr($message, $at + 4))) $message = substr($message, 0, $at);
            return $m[1] . ': ' . $message;
        }, $matches);
    }

    public function derive(): array
    {
        $errors = $this->files->verifyHashes(self::ANCHORS);
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $old = $this->read(self::PREDECESSOR); $review = $this->read(self::REVIEW); $observations = $this->read(self::OBSERVATIONS);
        $rows = [];
        foreach ($old['cases'] as $i => $r) {
            $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'origin' => 'phase6', 'original' => $r,
                'kind' => $r['kind'], 'classification' => $r['classification'],
                'stage' => $r['classification'] === 'deferred-declaration-link-rejection' ? 'lint-accepted; declaration-link outcome remains in dedicated matrices' : 'lint (parser/compiler combined)',
                'expected' => ['lexicalAccepted' => $r['lexicalAccepted'], 'structuralAccepted' => $r['structuralAccepted'], 'lintExit' => $r['lintExit'], 'diagnostics' => self::diagnostics($r['lintOutput'])],
                'sourceEvidence' => ['pin' => $old['sourcePin'], 'inventory' => self::PREDECESSOR . '#/cases/' . $i, 'metadata' => $r['metadata'], 'precision' => 'Inherited reviewed source/finding associations; no new predicate-completeness claim.'],
                'inclusion' => 'Preserve every original ordinary/contextual/discrepancy case and its expectation.'];
        }
        foreach ($review['coverage'] as $i => $r) {
            $found = array_values(array_filter($observations['observations'], static fn($o) => $o['group'] === 'coverage' && $o['id'] === $r['id']));
            if (count($found) !== 1 || $found[0]['sha256'] !== $r['sha256']) throw new \RuntimeException('Missing authenticated addition observation');
            $o = $found[0]; $discarded = $r['id'] === 'discarded-types';
            $source = []; foreach ($r['sourceIds'] as $id) foreach ($review['sources'] as $s) if ($s['id'] === $id) $source[] = $s;
            if (count($source) !== count($r['sourceIds']) || !$source) throw new \RuntimeException('Missing source evidence');
            $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'origin' => 'phase7c9', 'id' => $r['id'],
                'kind' => $r['structural'] ? 'valid' : 'invalid',
                'classification' => !$r['structural'] ? 'structural-or-lexical-adapter-rejection' : ($discarded ? 'accepted-discarded-declaration' : 'accepted-in-lint-and-structure'),
                'stage' => $discarded ? 'lint; enclosing declaration discarded before parameter constraints' : 'lint (parser/compiler combined)',
                'expected' => ['lexicalAccepted' => true, 'structuralAccepted' => $r['structural'], 'lintExit' => $r['accepted'] ? 0 : 255, 'diagnostics' => self::diagnostics($o['lint']['output'])],
                'sourceEvidence' => ['pin' => Certification86::PIN, 'manifest' => self::REVIEW . '#/coverage/' . $i, 'sources' => $source, 'observationReport' => self::OBSERVATIONS, 'observationId' => $r['id']],
                'inclusion' => $discarded ? 'Ordinary whole-source structural witness with exact lint acceptance after discard; not evidence that live never/void/self/parent parameters are generally valid.' : 'Existing ordinary corpus member with reviewed structural and exact-source lint expectation.'];
        }
        $separate = []; foreach (['context', 'scanner', 'binding'] as $group) foreach ($review[$group] as $r) $separate[] = ['group' => $group, 'id' => $r['id'], 'fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'stage' => $r['stage'], 'reason' => 'Dedicated stage/lexer/operand experiment; not an ordinary corpus addition.'];
        return ['contract' => self::CONTRACT, 'revision' => self::REVISION, 'sourcePin' => Certification86::PIN,
            'profile' => 'exact-pin-rc4', 'executableSha256' => CertificationCurrent::profiles()['exact-pin-rc4'],
            'predecessor' => ['path' => self::PREDECESSOR, 'sha256' => self::ANCHORS[self::PREDECESSOR], 'date' => $old['date'], 'profileSha256' => $old['interpreterSha256'], 'outcomesChanged' => false],
            'authority' => 'Phase 7C.10 authorizes only the 26 reviewed Phase 7C.9 ordinary additions; earlier inventories and gates retain their identities.',
            'anchors' => self::ANCHORS, 'cases' => $rows, 'separateInventories' => $separate,
            'nativeContextualEnforcement' => false, 'runtimeExecutionAuthorized' => false, 'certificationGranted' => false];
    }

    public function corpus(): array
    {
        $paths = []; foreach (['valid', 'invalid', 'contextual-invalid', 'known-discrepancies/valid'] as $kind)
            foreach (glob($this->root . '/tests/fixtures/php/8.6/' . $kind . '/*.php') as $p) $paths[] = substr(str_replace('\\', '/', $p), strlen($this->root) + 1);
        sort($paths); return $paths;
    }

    public function inventoryErrors(array $inventory): array
    {
        try {
            if ($inventory !== $this->derive()) return ['Incorrect inventory revision, predecessor, classification, source evidence or expectations'];
            $paths = array_column($inventory['cases'], 'fixture'); sort($paths);
            $errors = $paths === $this->corpus() ? [] : ['Unexplained corpus additions or removals'];
            return [...$errors, ...$this->originalErrors(), ...$this->files->verifyHashes(array_column($inventory['cases'], 'sha256', 'fixture'))];
        } catch (\Throwable $e) { return ['Invalid inventory: ' . $e->getMessage()]; }
    }

    public function inputs(): array
    {
        $paths = [self::INVENTORY, ...array_keys(self::ANCHORS), 'tools/lib/DifferentialInventory.php', 'tools/8.6/differential-inventory.php',
            'tools/lib/Support.php', 'tools/lib/Certification86.php', 'tools/lib/CertificationCurrent.php', 'tools/lib/ContextCoverageEvidence.php', 'tools/lib/ExactPinProvenance.php',
            'tests/DifferentialInventoryTest.php', 'grammar/8.6/php.ebnf', 'grammar/8.6/php.md', 'php-grammar.json', 'composer.json', 'composer.lock',
            'tools/8.6/source-lock.json', 'tools/8.6/certification-source-lock.json', 'docs/8.6/exact-pin-build/build.json'];
        foreach (Support::files($this->root . '/src') as $f) $paths[] = substr($f, strlen($this->root) + 1);
        return $this->files->hashes(array_unique([...$paths, ...$this->corpus(), ...array_keys($this->read(self::PREDECESSOR)['inputs'])]));
    }

    public function command(array $r): array { return (new ContextCoverageEvidence($this->root))->lintCommand(['fixture' => $r['fixture']]); }
    public function native(array $r): array
    {
        $source = file_get_contents($this->root . '/' . $r['fixture']);
        try { Lexer::forVersion('8.6')->withShortOpenTag(true)->tokenize($source); $lexical = true; }
        catch (LexerException) { $lexical = false; }
        return ['lexicalAccepted' => $lexical, 'structuralAccepted' => PhpGrammarMatcher::forRepositoryRoot($this->root)->matches('8.6', $source)->matched];
    }

    public function generate(): array
    {
        $inventory = $this->read(self::INVENTORY); $errors = $this->inventoryErrors($inventory);
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $auth = new ContextCoverageEvidence($this->root); $errors = $auth->authenticate();
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $inputs = $this->inputs(); $runtime = $auth->runtimeIdentity(); $rows = [];
        foreach ($inventory['cases'] as $r) $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'classification' => $r['classification'], 'stage' => $r['stage'],
            'native' => $this->native($r), 'lint' => ['command' => $this->command($r), ...Support::run($this->command($r), $this->root)]];
        $report = ['contract' => self::CONTRACT, 'revision' => self::REVISION, 'inventorySha256' => hash_file('sha256', $this->root . '/' . self::INVENTORY),
            'sourcePin' => Certification86::PIN, 'profile' => 'exact-pin-rc4', 'generatedAt' => gmdate('c'), 'inputs' => $inputs, 'inputsUnchanged' => $inputs === $this->inputs(),
            'runtimeBefore' => $runtime, 'runtimeAfter' => $auth->runtimeIdentity(), 'sourceErrorsAfter' => $auth->sourceErrors(), 'observations' => $rows,
            'nativeContextualEnforcement' => false, 'certificationGranted' => false];
        $report['errors'] = $this->recordErrors($report, $inventory); return $report;
    }

    public function recordErrors(array $report, array $inventory): array
    {
        $errors = [];
        if (($report['contract'] ?? '') !== self::CONTRACT || ($report['revision'] ?? '') !== self::REVISION || ($report['profile'] ?? '') !== 'exact-pin-rc4'
            || ($report['sourcePin'] ?? '') !== Certification86::PIN) $errors[] = 'Incorrect differential identity/profile';
        foreach (['nativeContextualEnforcement', 'certificationGranted'] as $key) if (($report[$key] ?? null) !== false) $errors[] = 'Unsupported claim: ' . $key;
        if (($report['inputsUnchanged'] ?? false) !== true || ($report['runtimeBefore'] ?? null) !== ($report['runtimeAfter'] ?? [])
            || ($report['runtimeBefore']['executable'] ?? '') !== CertificationCurrent::profiles()['exact-pin-rc4']
            || ($report['runtimeBefore']['coreDll'] ?? '') !== '1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f'
            || ($report['sourceErrorsAfter'] ?? null) !== []) $errors[] = 'Changed inputs/runtime/source custody';
        if (count($report['observations'] ?? []) !== count($inventory['cases'])) $errors[] = 'Incomplete observation membership';
        foreach ($inventory['cases'] as $i => $case) {
            $r = $report['observations'][$i] ?? [];
            foreach (['fixture', 'sha256', 'classification', 'stage'] as $key) if (($r[$key] ?? null) !== $case[$key]) $errors[] = 'Changed observation ' . $key . ': ' . $case['fixture'];
            $expected = $case['expected'];
            if (($r['native'] ?? []) !== array_intersect_key($expected, array_flip(['lexicalAccepted', 'structuralAccepted']))) $errors[] = 'Native mismatch: ' . $case['fixture'];
            if (($r['lint']['command'] ?? []) !== $this->command($case) || ($r['lint']['exit_code'] ?? null) !== $expected['lintExit']
                || self::diagnostics($r['lint']['output'] ?? '') !== $expected['diagnostics']
                || ($expected['lintExit'] === 0 && !str_contains($r['lint']['output'] ?? '', 'No syntax errors detected in '))) $errors[] = 'Lint/diagnostic mismatch: ' . $case['fixture'];
        }
        return $errors;
    }

    public static function freshnessErrors(array $report, array $inputs, string $inventoryHash, array $receipt, string $reportHash): array
    {
        $errors = [];
        if (($receipt['sha256'] ?? '') !== $reportHash) $errors[] = 'Report receipt mismatch';
        if (($report['inputs'] ?? []) !== $inputs || ($report['inventorySha256'] ?? '') !== $inventoryHash) $errors[] = 'Stale differential inputs';
        if (($report['errors'] ?? null) !== []) $errors[] = 'Recorded differential failures';
        return $errors;
    }

    public function verify(string $path): array
    {
        try {
            $before = $this->inputs(); $r = $this->read($path); $inventory = $this->read(self::INVENTORY);
            $hash = hash_file('sha256', $this->root . '/' . $path); $receipt = $this->read($path . '.receipt.json');
            $errors = [...$this->inventoryErrors($inventory), ...$this->recordErrors($r, $inventory)];
            $errors = [...$errors, ...self::freshnessErrors($r, $before, hash_file('sha256', $this->root . '/' . self::INVENTORY), $receipt, $hash)];
            if ($errors) return $errors;
            $errors = (new ContextCoverageEvidence($this->root))->authenticate();
            foreach ($inventory['cases'] as $i => $case) if ($r['observations'][$i]['native'] !== $this->native($case)) $errors[] = 'Changed native evidence: ' . $case['fixture'];
            if ($before !== $this->inputs() || $hash !== hash_file('sha256', $this->root . '/' . $path) || $receipt !== $this->read($path . '.receipt.json')) $errors[] = 'Read-only verification changed inputs/evidence';
            return $errors;
        } catch (\Throwable $e) { return ['Missing/invalid differential evidence: ' . $e->getMessage()]; }
    }
}
