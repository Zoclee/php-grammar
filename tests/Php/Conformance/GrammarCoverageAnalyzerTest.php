<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\GrammarCoverageAnalyzer;
use PhpGrammar\Php\Conformance\Php85CoverageCases;
use PhpGrammar\Php\Conformance\ConformanceException;
use PhpGrammar\Php\Conformance\RuleLevelCase;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class GrammarCoverageAnalyzerTest extends TestCase
{
    public function testRejectsInvalidPositiveRuleInsteadOfReportingPartialCoverage(): void
    {
        $this->expectException(ConformanceException::class);
        $this->expectExceptionMessage('Positive coverage rule rejected: expression #0');
        (new GrammarCoverageAnalyzer(RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3))))
            ->analyze('8.5', [new RuleLevelCase('expression', '1 +')]);
    }

    public function testAnalyzesPhp85CoverageAgainstRepositoryGrammar(): void
    {
        $report = (new GrammarCoverageAnalyzer(RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3))))
            ->analyze('8.5', Php85CoverageCases::ruleLevelCases());

        self::assertGreaterThan(0, $report->totalProductions());
        self::assertLessThanOrEqual($report->totalProductions(), $report->attemptedProductions());
        self::assertGreaterThan(0, $report->exercisedProductions());
        self::assertGreaterThan(0, $report->totalAlternatives());
        self::assertGreaterThan(0, $report->exercisedAlternatives());
        self::assertLessThan($report->totalProductions(), count($report->unexercisedProductions()));
        self::assertLessThan($report->totalAlternatives(), count($report->unexercisedAlternatives()));
    }
}
