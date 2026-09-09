<?php

declare(strict_types=1);

use PhpGrammar\Ebnf\Coverage\CoverageReportFormatter;
use PhpGrammar\Php\Conformance\GrammarCoverageAnalyzer;
use PhpGrammar\Php\Conformance\Php85CoverageCases;
use PhpGrammar\Repository\RepositoryManifest;

require dirname(__DIR__) . '/vendor/autoload.php';

$version = $argv[1] ?? '8.5';
$root = dirname(__DIR__);

try {
    $manifest = RepositoryManifest::fromRepositoryRoot($root);
    $ruleLevelCases = $version === '8.5' ? Php85CoverageCases::ruleLevelCases() : [];
    $report = (new GrammarCoverageAnalyzer($manifest))->analyze($version, $ruleLevelCases);
    echo (new CoverageReportFormatter())->format('PHP ' . $version . ' Grammar Coverage', $report);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
