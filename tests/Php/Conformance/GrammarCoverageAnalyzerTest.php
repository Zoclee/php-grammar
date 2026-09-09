<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\GrammarCoverageAnalyzer;
use PhpGrammar\Php\Conformance\Php85CoverageCases;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class GrammarCoverageAnalyzerTest extends TestCase
{
    public function testAnalyzesPhp85CoverageAgainstRepositoryGrammar(): void
    {
        $report = (new GrammarCoverageAnalyzer(RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3))))
            ->analyze('8.5', Php85CoverageCases::ruleLevelCases());

        self::assertGreaterThan(0, $report->totalProductions());
        self::assertGreaterThan(0, $report->exercisedProductions());
        self::assertGreaterThan(0, $report->totalAlternatives());
        self::assertGreaterThan(0, $report->exercisedAlternatives());
        self::assertLessThan($report->totalProductions(), count($report->unexercisedProductions()));
        self::assertLessThan($report->totalAlternatives(), count($report->unexercisedAlternatives()));
    }
}
