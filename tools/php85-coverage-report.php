<?php

declare(strict_types=1);

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Php\Conformance\{GrammarCoverageAnalyzer, GrammarRepository, Php85CoverageCases, Php85CoverageClassification, PhpGrammarMatcher};
use PhpGrammar\Repository\RepositoryManifest;

require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
$manifest = RepositoryManifest::fromRepositoryRoot($root);
$witnesses = $primitiveWitnesses = $phase3Witnesses = [];
$baselineCoverage = new CoverageCollector();
$cases = Php85CoverageCases::ruleLevelCases();
$report = (new GrammarCoverageAnalyzer($manifest))->analyze('8.5', $cases,
    static function (string $witness, CoverageCollector $coverage) use (&$witnesses, &$primitiveWitnesses, &$phase3Witnesses, $baselineCoverage): void {
        $phase6 = str_starts_with($witness, 'fixture:phase6-') || str_starts_with($witness, 'fixture:boundary-parameter-never-');
        if ((str_starts_with($witness, 'fixture:') && !str_starts_with($witness, 'fixture:phase3-')
                && !str_starts_with($witness, 'fixture:phase4-') && !str_starts_with($witness, 'fixture:phase5-') && !$phase6)
            || (str_starts_with($witness, 'rule:') && (int)substr($witness, strrpos($witness, '#') + 1) < 10)) {
            $baselineCoverage->merge($coverage);
        }
        foreach ([...$coverage->matchedProductions(), ...$coverage->matchedAlternatives()] as $id) {
            $witnesses[$id] ??= $witness;
            if (!str_starts_with($witness, 'fixture:phase4-') && !str_starts_with($witness, 'fixture:phase5-') && !$phase6) $phase3Witnesses[$id] ??= $witness;
        }
        foreach ($coverage->matchedPrimitives() as $id) $primitiveWitnesses[$id] ??= $witness;
    });
$grammar = (new GrammarRepository($manifest))->load('8.5');
$classifier = new Php85CoverageClassification($grammar, PhpGrammarMatcher::forManifest($manifest)->lexicalPrimitiveNames());
$remaining = $counts = [];
foreach (['production' => $report->unexercisedProductions(), 'alternative' => $report->unexercisedAlternatives()] as $kind => $identities) {
    foreach ($identities as $identity) {
        $row = ['kind' => $kind, 'identity' => $identity] + $classifier->classify($identity);
        $remaining[] = $row;
        $counts[$kind][$row['classification']] = ($counts[$kind][$row['classification']] ?? 0) + 1;
    }
    ksort($counts[$kind]);
}
ksort($witnesses);
ksort($primitiveWitnesses);
$baselineBacklog = [];
foreach (['production' => [$report->identityMap->productions(), $baselineCoverage->matchedProductions()],
    'alternative' => [$report->identityMap->alternatives(), $baselineCoverage->matchedAlternatives()]] as $kind => [$all, $covered]) {
    foreach (array_diff($all, $covered) as $identity) {
        $baselineBacklog[] = ['kind' => $kind, 'identity' => $identity] + (isset($phase3Witnesses[$identity])
            ? ['classification' => 'meaningful-valid-syntax', 'area' => explode('/', $identity)[0], 'evidence' => $phase3Witnesses[$identity], 'reason' => 'Positive witness added in Phase 3.']
            : $classifier->classify($identity));
    }
}
$data = [
    'grammar_sha256' => hash_file('sha256', $root . '/grammar/8.5/php.ebnf'),
    'method' => 'Completed chart items during accepted positive inputs, not unique successful derivations or AST equivalence. Primitive successes are separate. Invalid inputs affect attempted coverage only.',
    'baseline' => ['productions' => 236, 'alternatives' => 277, 'attempted_productions' => 255, 'attempted_alternatives' => 563],
    'current' => ['productions' => $report->exercisedProductions(), 'total_productions' => $report->totalProductions(),
        'alternatives' => $report->exercisedAlternatives(), 'total_alternatives' => $report->totalAlternatives(),
        'attempted_productions' => $report->attemptedProductions(), 'attempted_alternatives' => $report->attemptedAlternatives(),
        'valid_fixtures_default_profile' => count(glob($root . '/tests/fixtures/php/8.5/valid/*.php')),
        'rule_cases' => count($cases)],
    'remaining_counts' => $counts, 'remaining' => $remaining,
    'baseline_backlog' => $baselineBacklog,
    'first_positive_witness' => $witnesses, 'primitive_witnesses' => $primitiveWitnesses,
    'rule_cases' => array_map(static fn ($case) => ['rule' => $case->rule, 'source' => $case->source], $cases),
];
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
$path = $root . '/docs/8.5/phase3-coverage.json';
if (in_array('--check', $argv, true)) {
    if (!is_file($path) || file_get_contents($path) !== $json) {
        fwrite(STDERR, "Phase 3 coverage report is stale. Run php tools/php85-coverage-report.php\n");
        exit(1);
    }
} else {
    file_put_contents($path, $json);
}
echo json_encode(['current' => $data['current'], 'remaining_counts' => $counts], JSON_PRETTY_PRINT) . "\n";
exit(isset($counts['production']['meaningful-gap']) || isset($counts['alternative']['meaningful-gap']) ? 1 : 0);
