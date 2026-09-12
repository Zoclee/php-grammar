<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Tests\Support\DerivationForest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85ParseStructureTest extends TestCase
{
    #[DataProvider('cases')]
    public function testUniqueDerivationAndOperandSpans(string $root, string $source, array $spans): void
    {
        $grammar = (new Parser())->parse(file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.ebnf'));
        $trees = (new DerivationForest($grammar, $source))->trees($root);
        self::assertCount(1, $trees, $source);
        $actual = [];
        $walk = function (array $tree) use (&$walk, &$actual): void {
            $actual[] = [$tree['name'], $tree['start'], $tree['end']];
            foreach ($tree['children'] as $child) $walk($child);
        };
        $walk($trees[0]);
        foreach ($spans as $span) self::assertContains($span, $actual, $source);
    }

    public static function cases(): iterable
    {
        $cases = json_decode(file_get_contents(dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/parser-structure.json'), true, flags: JSON_THROW_ON_ERROR);
        $lexemes = static fn (string $source): array => array_map(static fn ($t) => is_array($t) ? $t[1] : $t, DerivationForest::tokens($source));
        foreach ($cases as $i => $case) {
            $tokens = $lexemes($case['source']);
            $spans = [];
            foreach ($case['spans'] as [$production, $fragment]) {
                $needle = $lexemes($fragment);
                $found = false;
                for ($start = 0; $start <= count($tokens) - count($needle); $start++) {
                    if (array_slice($tokens, $start, count($needle)) === $needle) {
                        $spans[] = [$production, $start, $start + count($needle)];
                        $found = true;
                        break;
                    }
                }
                if (!$found) throw new \LogicException('Operand not present: ' . $fragment);
            }
            yield 'Zend AST witness ' . $i => [$case['root'], $case['source'], $spans];
        }
        yield ['expression', '-2 ** 2', [['power-expression', 1, 4]]];
        yield ['expression', '!$x instanceof Foo', [['instanceof-expression', 1, 4]]];
        yield ['expression', '$a ?? $b ?? $c', [['coalesce-expression', 2, 5]]];
        yield ['expression', '2 ** 3 ** 4', [['power-expression', 2, 5]]];
        yield ['expression', 'print $x = 1', [['assignment-expression', 1, 4]]];
        yield ['expression', 'throw $a ?? $b', [['coalesce-expression', 1, 4]]];
        yield ['expression', '$x = print $y = 1', [['assignment-prefix-context', 0, 2], ['assignment-expression', 3, 6]]];
        yield ['expression', '-!$x instanceof Foo', [['unary-prefix-context', 0, 1], ['instanceof-expression', 2, 5]]];
        yield ['expression', 'clone $x ** 2', [['clone-expression', 0, 2]]];
        yield ['expression', '2 ** -2 ** 2', [['power-prefix-context', 0, 2], ['power-expression', 3, 6]]];
        yield ['expression', 'include $x or $y', [['logical-or-expression', 1, 4]]];
        yield ['expression', 'fn() => $x or $y', [['logical-or-expression', 4, 7]]];
        yield ['expression', 'fn() => throw $x', [['arrow-prefix-context', 0, 4]]];
        yield ['expression', '(int) 2 ** 2', [['power-expression', 1, 4]]];
        yield ['expression', '@2 ** 2', [['power-expression', 1, 4]]];
        yield ['expression', '~2 ** 2', [['power-expression', 1, 4]]];
        yield ['expression', '+2 ** 2', [['power-expression', 1, 4]]];
        yield ['expression', 'yield yield 1', [['yield-expression', 1, 3]]];
        yield ['expression', 'yield 1 => yield 2', [['yield-key-prefix-context', 0, 3]]];
        yield ['expression', 'yield yield 1 => 2', [['yield-key-expression', 1, 5]]];
        yield ['expression', 'yield yield 1 => 2 => 3', [['yield-key', 1, 5]]];
        yield ['expression', 'yield print 1 => 2', []];
        yield ['expression', 'yield 1 => print 2', []];
        yield ['expression', 'yield yield => 2', []];
        yield ['expression', 'yield 1 + yield => 2', []];
        yield ['expression', 'yield print yield 1 => 2 => 3', []];
        yield ['expression', '[1,]', []];
        yield ['expression', '[[]]', []];
        yield ['expression', '[, $a] = $x', []];
        yield ['statement', 'if ($a) if ($b) foo(); else bar();', [['unmatched-if-statement', 0, 17], ['matched-if-statement', 4, 17]]];
        yield ['statement', 'if ($a) while ($b) if ($c) foo(); else bar();', [['matched-if-statement', 8, 21]]];
        yield ['statement', ';', [['empty-statement', 0, 1]]];
        yield ['statement', 'throw $e;', [['expression-statement', 0, 3]]];
        yield ['statement', 'if ($a) foo(); elseif ($b) bar();', [['unmatched-if-statement', 0, 16]]];
    }

    public function testNonAssociativeChainsHaveNoDerivation(): void
    {
        $grammar = (new Parser())->parse(file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.ebnf'));
        foreach (['$a < $b < $c', '$a == $b == $c'] as $source) {
            self::assertSame([], (new DerivationForest($grammar, $source))->trees('expression'));
        }
        $sources = json_decode(file_get_contents(dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/parser-structure-invalid.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($sources as $source) {
            self::assertSame([], (new DerivationForest($grammar, $source))->trees('statement'), $source);
        }
    }

    public function testForestDetectsAnAmbiguousGrammar(): void
    {
        $grammar = (new Parser())->parse('root = a | b ; a = "x" ; b = "x" ;');
        self::assertCount(2, (new DerivationForest($grammar, 'x'))->trees('root'));
    }
}
