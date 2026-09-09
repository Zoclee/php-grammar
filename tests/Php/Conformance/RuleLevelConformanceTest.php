<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Conformance\Php85CoverageCases;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RuleLevelConformanceTest extends TestCase
{
    #[DataProvider('validRuleProvider')]
    public function testMatchesSelectedPhp85Rules(string $rule, string $source): void
    {
        $result = self::matcher()->matchesRule('8.5', $rule, $source);

        self::assertTrue(
            $result->matched,
            $rule . "\nfurthest token offset: " . $result->furthestOffset . "\nexpected: " . implode(', ', $result->expected),
        );
    }

    #[DataProvider('invalidRuleProvider')]
    public function testRejectsSelectedPhp85Rules(string $rule, string $source): void
    {
        $result = self::matcher()->matchesRule('8.5', $rule, $source);

        self::assertFalse($result->matched, $rule);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validRuleProvider(): iterable
    {
        foreach (Php85CoverageCases::ruleLevelCases() as $case) {
            yield $case->rule => [$case->rule, $case->source];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidRuleProvider(): iterable
    {
        yield 'function missing name' => ['function-declaration', 'function (): void {}'];
        yield 'class missing name' => ['class-declaration', 'class {}'];
        yield 'empty attribute group' => ['attribute-groups', '#[]'];
        yield 'match arm missing condition' => ['match-expression', 'match ($x) { => "bad" }'];
        yield 'arrow missing expression' => ['arrow-function', 'fn ($x) =>'];
    }

    private static function matcher(): PhpGrammarMatcher
    {
        return PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3));
    }
}
