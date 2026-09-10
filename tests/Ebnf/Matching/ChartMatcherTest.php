<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf\Matching;

use PhpGrammar\Ebnf\Matching\{ChartMatcher, StringInput};
use PhpGrammar\Ebnf\Parser;
use PHPUnit\Framework\TestCase;

final class ChartMatcherTest extends TestCase
{
    public function testIndirectLeftRecursionConsumesTheWholeInput(): void
    {
        $grammar = (new Parser())->parse('s = a ; a = b | "x" ; b = a , "y" ;');
        $matcher = new ChartMatcher();
        self::assertTrue($matcher->matchesRule($grammar, 's', new StringInput('xyyy'))->matched);
        self::assertFalse($matcher->matchesRule($grammar, 's', new StringInput('xyyz'))->matched);
    }

    public function testNullableCyclesAndLatePredictionsReachAFixedPoint(): void
    {
        $grammar = (new Parser())->parse('s = a , a , "x" ; a = [ b ] ; b = a ;');
        self::assertTrue((new ChartMatcher())->matchesRule($grammar, 's', new StringInput('x'))->matched);
    }

    public function testEmptyTerminalDoesNotConsumeAToken(): void
    {
        $grammar = (new Parser())->parse('s = "" , "x" , "" ;');
        self::assertTrue((new ChartMatcher())->matchesRule($grammar, 's', new StringInput('x'))->matched);
    }
}
