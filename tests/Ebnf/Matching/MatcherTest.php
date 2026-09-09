<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf\Matching;

use PhpGrammar\Ebnf\Matching\Matcher;
use PhpGrammar\Ebnf\Matching\StringInput;
use PhpGrammar\Ebnf\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MatcherTest extends TestCase
{
    #[DataProvider('matchingGrammarProvider')]
    public function testMatchesEbnfConstructs(string $ebnf, string $input): void
    {
        $result = $this->match($ebnf, $input);

        self::assertTrue($result->matched, implode(', ', $result->expected));
        self::assertSame(strlen($input), $result->furthestOffset);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function matchingGrammarProvider(): iterable
    {
        yield 'literal' => ['source-file = "abc" ;', 'abc'];
        yield 'sequence' => ['source-file = "a" , "b" , "c" ;', 'abc'];
        yield 'alternative-first' => ['source-file = "a" | "b" ;', 'a'];
        yield 'alternative-second' => ['source-file = "a" | "b" ;', 'b'];
        yield 'group' => ['source-file = "a" , ( "b" | "c" ) ;', 'ac'];
        yield 'optional-present' => ['source-file = "a" , [ "b" ] , "c" ;', 'abc'];
        yield 'optional-absent' => ['source-file = "a" , [ "b" ] , "c" ;', 'ac'];
        yield 'repetition-many' => ['source-file = "a" , { "b" } , "c" ;', 'abbbc'];
        yield 'repetition-zero' => ['source-file = "a" , { "b" } , "c" ;', 'ac'];
        yield 'nested' => ['source-file = { "a" , [ "b" | ( "c" , "d" ) ] } ;', 'abacdaa'];
        yield 'production-reference' => ["source-file = item , item ;\nitem = \"a\" | \"b\" ;", 'ab'];
        yield 'right-recursion' => ['source-file = "a" , [ source-file ] ;', 'aaa'];
        yield 'explicit-empty-literal' => ['source-file = "" ;', ''];
    }

    public function testRequiresCompleteInputConsumption(): void
    {
        $result = $this->match('source-file = "a" ;', 'ab');

        self::assertFalse($result->matched);
        self::assertSame(1, $result->furthestOffset);
        self::assertSame(['end of input'], $result->expected);
    }

    public function testAcceptsStringInputObject(): void
    {
        $grammar = (new Parser())->parse('source-file = "a" , "b" ;');
        $result = Matcher::withDefaultPrimitives()->matches($grammar, new StringInput('ab'));

        self::assertTrue($result->matched);
    }

    public function testMatchesSelectedRule(): void
    {
        $grammar = (new Parser())->parse(<<<'EBNF'
            source-file = "root" ;
            other-rule = "other" ;
            EBNF);

        $matcher = Matcher::withDefaultPrimitives();

        self::assertTrue($matcher->matchesRule($grammar, 'other-rule', 'other')->matched);
        self::assertFalse($matcher->matches($grammar, 'other')->matched);
    }

    public function testReportsFailedMatchDiagnostics(): void
    {
        $result = $this->match('source-file = "abc" | "abd" ;', 'abe');

        self::assertFalse($result->matched);
        self::assertSame(2, $result->furthestOffset);
        self::assertSame(['"abc"', '"abd"'], $result->expected);
    }

    public function testReportsFurthestFailurePosition(): void
    {
        $result = $this->match('source-file = "a" , ( "b" | "c" ) ;', 'ad');

        self::assertFalse($result->matched);
        self::assertSame(1, $result->furthestOffset);
        self::assertSame(['"b"', '"c"'], $result->expected);
    }

    public function testBacktracksAcrossAlternativesForCompleteMatch(): void
    {
        $result = $this->match('source-file = "a" | "a" , "b" ;', 'ab');

        self::assertTrue($result->matched);
    }

    public function testBacktracksAcrossRepetitionForFollowingSequence(): void
    {
        $result = $this->match('source-file = { "a" } , "a" ;', 'aa');

        self::assertTrue($result->matched);
    }

    public function testPreventsInfiniteLoopForZeroWidthRepetition(): void
    {
        $result = $this->match('source-file = { [ "a" ] } ;', 'a');

        self::assertTrue($result->matched);
    }

    public function testPreventsInfiniteLoopForRecursiveProduction(): void
    {
        $result = $this->match('source-file = source-file | "a" ;', 'a');

        self::assertTrue($result->matched);
    }

    public function testSupportsCodeUnitPrimitiveOutsideGrammarProductions(): void
    {
        $grammar = (new Parser())->parse('source-file = code-unit , code-unit ;');
        $matcher = Matcher::withDefaultPrimitives();

        self::assertTrue($matcher->matches($grammar, 'ab')->matched);
        self::assertTrue($matcher->matches($grammar, 'é')->matched);
        self::assertFalse($matcher->matches($grammar, 'a')->matched);
    }

    private function match(string $ebnf, string $input): \PhpGrammar\Ebnf\Matching\MatchResult
    {
        $grammar = (new Parser())->parse($ebnf);

        return Matcher::withDefaultPrimitives()->matches($grammar, $input);
    }
}
