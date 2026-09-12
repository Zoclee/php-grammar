<?php

declare(strict_types=1);

use PhpGrammar\Ebnf\Coverage\CoverageReportFormatter;
use PhpGrammar\Php\Conformance\GrammarCoverageAnalyzer;
use PhpGrammar\Php\Conformance\Php85CoverageCases;
use PhpGrammar\Php\Conformance\Php85CoverageClassification;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;

require dirname(__DIR__) . '/vendor/autoload.php';

$version = $argv[1] ?? '8.5';
$root = dirname(__DIR__);

try {
    $manifest = RepositoryManifest::fromRepositoryRoot($root);
    $ruleLevelCases = $version === '8.5' ? Php85CoverageCases::ruleLevelCases() : [];
    $report = (new GrammarCoverageAnalyzer($manifest))->analyze($version, $ruleLevelCases);
    echo (new CoverageReportFormatter())->format('PHP ' . $version . ' Grammar Coverage', $report);
    if ($version === '8.5') {
        $classifier = new Php85CoverageClassification(
            (new GrammarRepository($manifest))->load($version),
            PhpGrammarMatcher::forManifest($manifest)->lexicalPrimitiveNames(),
        );
        echo "\nRemaining coverage classifications (raw totals above are unchanged):\n";
        foreach (['Productions' => $report->unexercisedProductions(), 'Alternatives' => $report->unexercisedAlternatives()] as $kind => $identities) {
            $counts = [];
            foreach ($identities as $identity) {
                $category = $classifier->classify($identity)['classification'];
                $counts[$category] = ($counts[$category] ?? 0) + 1;
            }
            ksort($counts);
            echo $kind . ': ' . json_encode($counts, JSON_THROW_ON_ERROR) . "\n";
        }
        echo "Evidence and regeneration: docs/8.5/phase3-coverage.md\n";
    }
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
