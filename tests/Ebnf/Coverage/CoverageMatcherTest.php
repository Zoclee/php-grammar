<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf\Coverage;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Ebnf\Coverage\CoverageIdentityMap;
use PhpGrammar\Ebnf\Coverage\CoverageReport;
use PhpGrammar\Ebnf\Coverage\CoverageReportFormatter;
use PhpGrammar\Ebnf\Matching\Matcher;
use PhpGrammar\Ebnf\Parser;
use PHPUnit\Framework\TestCase;

final class CoverageMatcherTest extends TestCase
{
    public function testCoverageIsDisabledByDefault(): void
    {
        $grammar = (new Parser())->parse('source-file = "a" ;');
        $result = Matcher::withDefaultPrimitives()->matches($grammar, 'a');

        self::assertTrue($result->matched);
    }

    public function testTracksProductionsAlternativesOptionalsRepetitionsAndPrimitives(): void
    {
        $grammar = (new Parser())->parse(<<<'EBNF'
            source-file = item , [ "?" ] , { item } , code-unit ;
            item = "a" | "b" ;
            unused = "x" ;
            EBNF);
        $collector = new CoverageCollector();
        $matcher = new Matcher(
            primitiveMatchers: ['code-unit' => static fn ($input, int $offset): array => $offset < $input->length() ? [$offset + 1] : []],
            coverage: $collector,
        );

        $result = $matcher->matches($grammar, 'a?bc');

        self::assertTrue($result->matched);
        self::assertContains('source-file', $collector->enteredProductions());
        self::assertContains('item', $collector->matchedProductions());
        self::assertContains('item/alternative:1', $collector->matchedAlternatives());
        self::assertContains('item/alternative:2', $collector->matchedAlternatives());
        self::assertContains('source-file/optional:1', $collector->optionalTaken());
        self::assertContains('source-file/optional:1', $collector->optionalSkipped());
        self::assertContains('source-file/repetition:1', $collector->repetitionEntered());
        self::assertContains('source-file/repetition:1', $collector->repetitionExercised());
        self::assertContains('code-unit', $collector->matchedPrimitives());
    }

    public function testTracksOptionalSkippedWithoutTaken(): void
    {
        $grammar = (new Parser())->parse('source-file = "a" , [ "b" ] ;');
        $collector = new CoverageCollector();

        $result = (new Matcher(coverage: $collector))->matches($grammar, 'a');

        self::assertTrue($result->matched);
        self::assertSame(['source-file/optional:1'], $collector->optionalSkipped());
        self::assertSame([], $collector->optionalTaken());
    }

    public function testAggregatesCoverageAndReportsUncoveredElements(): void
    {
        $grammar = (new Parser())->parse(<<<'EBNF'
            source-file = "a" | "b" ;
            unused = "x" ;
            EBNF);
        $first = new CoverageCollector();
        $second = new CoverageCollector();
        (new Matcher(coverage: $first))->matches($grammar, 'a');
        (new Matcher(coverage: $second))->matches($grammar, 'b');
        $first->merge($second);

        $report = new CoverageReport(CoverageIdentityMap::fromGrammar($grammar), $first, $first);

        self::assertSame(2, $report->totalProductions());
        self::assertSame(1, $report->exercisedProductions());
        self::assertSame(['unused'], $report->unexercisedProductions());
        self::assertSame(2, $report->totalAlternatives());
        self::assertSame(2, $report->exercisedAlternatives());
        self::assertSame([], $report->unexercisedAlternatives());

        $formatted = (new CoverageReportFormatter())->format('Test Coverage', $report);
        self::assertStringContainsString('Productions:', $formatted);
        self::assertStringContainsString('- unused', $formatted);
    }

    public function testCoverageIdentitiesAreDeterministicAcrossParses(): void
    {
        $source = 'source-file = ( "a" | "b" ) , [ "c" ] , { "d" } ;';

        $first = CoverageIdentityMap::fromGrammar((new Parser())->parse($source));
        $second = CoverageIdentityMap::fromGrammar((new Parser())->parse($source));

        self::assertSame($first->alternatives(), $second->alternatives());
        self::assertSame(['source-file/alternative:1', 'source-file/alternative:2'], $first->alternatives());
        self::assertSame(['source-file/optional:1'], $first->optionals());
        self::assertSame(['source-file/repetition:1'], $first->repetitions());
    }

    public function testMatcherBehaviorRemainsUnchangedWithCoverageEnabled(): void
    {
        $grammar = (new Parser())->parse('source-file = "a" | "b" ;');

        $withoutCoverage = Matcher::withDefaultPrimitives()->matches($grammar, 'b');
        $withCoverage = (new Matcher(coverage: new CoverageCollector()))->matches($grammar, 'b');

        self::assertSame($withoutCoverage->matched, $withCoverage->matched);
        self::assertSame($withoutCoverage->furthestOffset, $withCoverage->furthestOffset);
        self::assertSame($withoutCoverage->expected, $withCoverage->expected);
    }
}
