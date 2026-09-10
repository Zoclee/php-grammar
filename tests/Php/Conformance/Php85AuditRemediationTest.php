<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85AuditRemediationTest extends TestCase
{
    #[DataProvider('validSourceProvider')]
    public function testAcceptsAuditRemediationValidSource(string $source): void
    {
        $result = self::matcher()->matches('8.5', $source);

        self::assertTrue(
            $result->matched,
            "furthest token offset: {$result->furthestOffset}\nexpected: " . implode(', ', $result->expected),
        );
    }

    #[DataProvider('invalidSourceProvider')]
    public function testRejectsAuditRemediationInvalidSource(string $source): void
    {
        self::assertFalse(self::matcher()->matches('8.5', $source)->matched);
    }

    #[DataProvider('validRuleProvider')]
    public function testAcceptsAuditRemediationValidRuleFragments(string $rule, string $source): void
    {
        $result = self::matcher()->matchesRule('8.5', $rule, $source);

        self::assertTrue(
            $result->matched,
            $rule . "\nfurthest token offset: {$result->furthestOffset}\nexpected: " . implode(', ', $result->expected),
        );
    }

    #[DataProvider('invalidRuleProvider')]
    public function testRejectsAuditRemediationInvalidRuleFragments(string $rule, string $source): void
    {
        self::assertFalse(self::matcher()->matchesRule('8.5', $rule, $source)->matched, $rule . ': ' . $source);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validSourceProvider(): iterable
    {
        yield 'echo tag expression list' => ['<?= $a, $b ?>'];
        yield 'close tag terminates echo' => ['<?php echo 1 ?>tail'];
        yield 'die exit alias' => ['<?php die("x");'];
        yield 'right-associative exponentiation' => ['<?php $x = 2 ** 3 ** 4;'];
        yield 'textual logical operators and <> inequality' => ['<?php $x = $a or $b xor $c and 1 <> 2;'];
        yield 'cast aliases' => ['<?php $x = (integer) 1 + (double) 2 + (boolean) 3 + (binary) "x";'];
        yield 'typed class constants' => ['<?php class A { public const int X = 1, Y = 2; }'];
        yield 'hooked property' => ['<?php class A { public int $x { get => 1; } }'];
        yield 'interface hooked property' => ['<?php interface I { public string $name { get; } }'];
        yield 'trait adaptation block without outer semicolon' => ['<?php trait T { public function m() {} } class A { use T { m as public n; } }'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSourceProvider(): iterable
    {
        yield 'old typed class constant placement' => ['<?php class A { const X: int = 1; }'];
        yield 'removed unset cast' => ['<?php $x = (unset) 1;'];
        yield 'empty closure use list' => ['<?php $x = function () use () {};'];
        yield 'literal object dereference' => ['<?php 1->foo;'];
        yield 'literal call' => ['<?php 1();'];
        yield 'mixed first-class-callable marker' => ['<?php foo(..., 1);'];
        yield 'unset expression' => ['<?php unset(1);'];
        yield 'foreach expression key' => ['<?php foreach ($xs as $a + $b => $c) {}'];
        yield 'void cast in for condition' => ['<?php for (; (void) $x; ) {}'];
        yield 'unmodified class property' => ['<?php class A { $foo; }'];
        yield 'hooked property trailing semicolon' => ['<?php class A { public int $x { get; }; }'];
        yield 'trait adaptation outer semicolon' => ['<?php class A { use T { m as n; }; }'];
        yield 'parenthesized intersection nullable type' => ['<?php function f(?(A&B) $x) {}'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validRuleProvider(): iterable
    {
        yield 'decimal zero' => ['decimal-integer-literal', '0'];
        yield 'uppercase hexadecimal prefix' => ['hexadecimal-integer-literal', '0XCAFE'];
        yield 'uppercase binary prefix' => ['binary-integer-literal', '0B1010'];
        yield 'uppercase octal prefix' => ['explicit-octal-integer-literal', '0O755'];
        yield 'recursive variable variable' => ['variable-expression', '$$$$x'];
        yield 'array destructuring assignment' => ['expression', '[$a, $b] = $value'];
        yield 'list destructuring assignment' => ['expression', 'list($a, $b) = $value'];
        yield 'match comma before arrow' => ['match-expression', 'match ($x) { 1, 2, => "small" }'];
        yield 'switch optional leading semicolon' => ['switch-statement', 'switch ($x) { ; case 1: break; }'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidRuleProvider(): iterable
    {
        yield 'consecutive decimal separators' => ['decimal-integer-literal', '1__2'];
        yield 'trailing decimal separator' => ['decimal-integer-literal', '1_'];
        yield 'trailing exponent separator' => ['floating-literal', '1e1_'];
        yield 'chained equality' => ['expression', '$a == $b == $c'];
        yield 'chained relational' => ['expression', '$a < $b < $c'];
        yield 'bare list expression value' => ['expression', 'list($a, $b)'];
        yield 'named by-reference argument special case' => ['argument-list', '(x: &$value)'];
    }

    private static function matcher(): PhpGrammarMatcher
    {
        return PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3));
    }
}
