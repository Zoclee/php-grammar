<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
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
        yield 'function declaration' => ['function-declaration', 'function f(int|string $value): ?string { return "x"; }'];
        yield 'class declaration' => ['class-declaration', 'final class User extends Person implements Named { public string $name; }'];
        yield 'interface declaration' => ['interface-declaration', 'interface Named { public function name(): string; }'];
        yield 'trait declaration' => ['trait-declaration', 'trait T { public function f(): int { return 1; } }'];
        yield 'enum declaration' => ['enum-declaration', 'enum Status: string { case Active = "active"; }'];
        yield 'attribute group' => ['attribute-groups', '#[Example("value")]'];
        yield 'match expression' => ['match-expression', 'match ($x) { 1 => "one", default => "other", }'];
        yield 'closure expression' => ['closure-expression', 'static function (&$x): int { return 1; }'];
        yield 'arrow function' => ['arrow-function', 'fn ($x): int => $x'];
        yield 'array creation' => ['array-creation-expression', '["a" => 1, ...$items]'];
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
