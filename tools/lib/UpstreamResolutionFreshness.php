<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Certification86.php';

/** Closed, reviewed Phase 7H/7H.1 transition. No caller-provided hashes. */
final class UpstreamResolutionFreshness
{
    public const CONTRACT = 'php86-upstream-resolution-freshness-v1';
    public const ROOT = 'docs/8.6/upstream-resolution-freshness';
    public const ORIGINAL = 'docs/8.6/correctness-remediation-probes.json';
    public const ORIGINAL_SHA = 'c9618823346494c4c8eada5ff8718356d3599daa6aa2666cf398eb47e2ab58bc';
    public const PROBES = self::ROOT . '/compiler-contextual.json';
    public const TEST = 'tests/Php/Conformance/Php86CompilerContextualTest.php';
    public const CHANGES = [
        'grammar/8.6/php.md' => ['45688b7d36ff16d823360f9072f43aa8e52c4dbd9189c890a6fdeef592bca521', '1aa67b423e391c55f92f5b65ffba81bdd9d0dd8ab4c49201e66fab9eff1d100e'],
        'grammar/8.6/changes.md' => ['27754b1eb2f9806753e94243e1c25bba6c1039aed0c43943d13f5c0655643d9d', '627be3da6c3dfdb5d431fea82f486fe5e42794b1c16cf0cc276bd1bb79261611'],
        'tools/8.6/change-map.json' => ['1396186ea1a2d022a22fdaf36090b9d523db77ab16590c453c9a01e4002b9903', 'b609105cf760075d9dea32d97d0689c228840cc9c37b3401bf9b63510652c38f'],
        self::TEST => ['e3a9a8f01ff94250e272db888ce71e3ed8deb5a3cc790af81e3185da035b1a89', '86c38e1cc4364499134602d44787a046e316edd7ad92090732642c06eae73259'],
    ];
    public const ANCHORS = [
        self::ORIGINAL => self::ORIGINAL_SHA,
        'docs/8.6/upstream-maintainer-resolution/baseline.json' => '517c299f8cbdc1363a7ba1531c8c3a224aa5b9033d67a1f1d6246b17c4b6bb19',
        'docs/8.6/certification-contract-remediation/phase7h-validation/evidence-files.json' => '851c22a51d1761ceaa76c0b4431051b3bdce545efc47c1b27918ac640dec226e',
        self::ROOT . '/baseline.json' => '7c3d0937d7118d8eb68c2d8def0b006057acaba93fab9871a682aaa0befc01c7',
    ];
    public function __construct(private readonly string $root) {}

    public function authorityErrors(): array
    {
        $c = new Certification86($this->root);
        $errors = $c->verifyHashes(self::ANCHORS);
        if ($errors) return $errors;
        // Bind the whole Phase 7H bundle, including original requirements and replies.
        $bundle = $c->read('docs/8.6/certification-contract-remediation/phase7h-validation/evidence-files.json')['files'];
        foreach (array_keys(self::CHANGES) as $path) unset($bundle[$path]);
        $errors = [...$errors, ...$c->verifyHashes($bundle)];
        foreach (self::CHANGES as $path => [$before, $after]) {
            $errors = [...$errors, ...$c->verifyHashes([self::ROOT . '/predecessor/' . $path => $before, $path => $after])];
        }
        if ($errors) return array_values(array_unique($errors));
        // These map fields drive the source audit. Only documentary hashes/reasons changed.
        $before = $c->read(self::ROOT . '/predecessor/tools/8.6/change-map.json');
        $after = $c->read('tools/8.6/change-map.json');
        foreach (['## Parser/compiler boundary rules', '## Deprecated but accepted syntax'] as $section) {
            $after['documentation'][$section]['after'] = $before['documentation'][$section]['after'];
            $after['documentation'][$section]['reason'] = $before['documentation'][$section]['reason'];
        }
        $after['changesSha256'] = $before['changesSha256'];
        if ($after !== $before) $errors[] = 'Source-audit map semantics changed outside documentary review';
        return array_values(array_unique($errors));
    }

    /** Validate every original dependency, and every successor observation, independently. */
    public function probeErrors(array $report): array
    {
        $c = new Certification86($this->root);
        $errors = $this->authorityErrors();
        if ($errors) return $errors;
        $original = $c->read(self::ORIGINAL);
        $expected = $original;
        if (($original['inputHashes']['grammar/8.6/php.md'] ?? '') !== self::CHANGES['grammar/8.6/php.md'][0]) return ['Unexpected predecessor dependency'];
        $expected['inputHashes']['grammar/8.6/php.md'] = self::CHANGES['grammar/8.6/php.md'][1];
        if ($report !== $expected) $errors[] = 'Successor contextual evidence differs beyond the authorized document dependency';
        $errors = [...$errors, ...$c->verifyHashes($expected['inputHashes'] + $expected['fixtureHashes'])];
        return $errors;
    }

    public function transition(): array
    {
        return ['contract' => self::CONTRACT, 'predecessor' => ['path' => self::ORIGINAL, 'sha256' => self::ORIGINAL_SHA],
            'successor' => self::PROBES, 'changes' => self::CHANGES, 'anchors' => self::ANCHORS,
            'authorization' => 'Phase 7H informative maintainer clarification; Phase 7H.1 explicitly authorizes authenticated successor evidence and the minimal test dependency correction.',
            'preserved' => 'All original hashes, observations, stages, expectations, source/executable pins and blocker requirements.',
            'certificationGranted' => false];
    }
}
