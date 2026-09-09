<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf\Matching;

use PhpGrammar\Ebnf\Matching\Matcher;
use PhpGrammar\Ebnf\Parser;
use PHPUnit\Framework\TestCase;

final class MatcherArchitectureTest extends TestCase
{
    public function testPrimitiveOverrideBehaviorIsExplicitAndDisabledByDefault(): void
    {
        $grammar = (new Parser())->parse('source-file = "grammar" ;');
        $primitive = ['source-file' => static fn (): array => [1]];

        $defaultMatcher = new Matcher(primitiveMatchers: $primitive);
        $overrideMatcher = new Matcher(primitiveMatchers: $primitive, primitiveMatchersOverrideProductions: true);

        self::assertTrue($defaultMatcher->matches($grammar, 'grammar')->matched);
        self::assertFalse($defaultMatcher->matches($grammar, 'x')->matched);
        self::assertTrue($overrideMatcher->matches($grammar, 'x')->matched);
    }

    public function testGenericMatcherDoesNotReferencePhpLexingClasses(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Ebnf' . DIRECTORY_SEPARATOR . 'Matching' . DIRECTORY_SEPARATOR . 'Matcher.php');
        self::assertIsString($source);

        self::assertStringNotContainsString('PhpGrammar\\Php\\Lexing', $source);
        self::assertStringNotContainsString('TokenType', $source);
    }
}
