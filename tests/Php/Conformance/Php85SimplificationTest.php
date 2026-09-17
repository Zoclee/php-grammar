<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\{Grammar, GroupNode, Node, OptionalNode, Parser, RepetitionNode, SequenceNode};
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Tests\Support\DerivationForest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85SimplificationTest extends TestCase
{
    private const REMOVED = ['interface-member-list', 'interface-member', 'enum-member-list', 'enum-member'];

    private static function source(): string
    {
        return file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.ebnf');
    }

    private static function baseline(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/simplification-baseline.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /** Undo only the three approved body edits and four alias removals. */
    private static function restoredSource(): string
    {
        $source = self::source();
        foreach (['interface', 'enum'] as $kind) {
            $source = preg_replace_callback('/^' . $kind . '-declaration =\R.*?;/ms',
                static fn ($match) => str_replace('class-member-list', $kind . '-member-list', $match[0]), $source);
            $source .= "\n$kind-member-list = { $kind-member } ;\n$kind-member = class-member ;\n";
        }
        return preg_replace_callback('/^double-quoted-string =\R.*?;/ms',
            static fn ($match) => str_replace('{ encapsulated-string-part }',
                '[ encapsulated-string-part , { encapsulated-string-part } ]', $match[0]), $source);
    }

    public function testEveryOriginalProductionIsUnchangedExceptApprovedRewrites(): void
    {
        $grammar = (new Parser())->parse(self::restoredSource());
        $expected = self::baseline()['productions'];
        self::assertCount(364, $expected);
        self::assertCount(count($expected), $grammar->productions());
        foreach ($grammar->productions() as $production) {
            self::assertArrayHasKey($production->name, $expected);
            self::assertSame($expected[$production->name], hash('sha256', serialize($production->expression)), $production->name);
        }
        $current = (new Parser())->parse(self::source());
        self::assertSame(self::REMOVED, array_values(array_diff(array_keys($expected), array_keys($current->productionMap()))));
        self::assertCount(360, $current->productions());
    }

    public function testNullabilityAndIntegrityArePreserved(): void
    {
        $nullable = [];
        foreach ((new GrammarValidator())->validate((new Parser())->parse(self::source()))->errors as $error) {
            self::assertSame('empty-production', $error->code, $error->message);
            preg_match('/"([^"]+)"/', $error->message, $match);
            $nullable[] = $match[1];
        }
        sort($nullable);
        self::assertSame(array_values(array_diff(self::baseline()['nullable'], self::REMOVED)), $nullable);
    }

    public function testGeneratedFamiliesAreExactDeterministicAndDetectDrift(): void
    {
        require_once dirname(__DIR__, 3) . '/tools/8.5/generate-expressions.php';
        $source = self::source();
        self::assertCount(40, \Php85ExpressionFamilies::productions());
        self::assertSame($source, \Php85ExpressionFamilies::render($source));
        self::assertSame($source, \Php85ExpressionFamilies::render(\Php85ExpressionFamilies::render($source)));
        foreach (\Php85ExpressionFamilies::productions() as $name => $production) {
            $expression = (new Parser())->parse($production)->productionMap()[$name]->expression;
            self::assertSame(self::baseline()['productions'][$name], hash('sha256', serialize($expression)), $name);
        }
        $changed = preg_replace('/^power-expression =\R[^;]*;/m', "power-expression =\n    \"wrong\" ;", $source);
        self::assertNotSame($source, $changed);
        self::assertSame($source, \Php85ExpressionFamilies::render($changed));
        // Both repository LF checkouts and CRLF checkouts remain byte-stable.
        $lf = str_replace("\r\n", "\n", $source);
        self::assertSame($lf, \Php85ExpressionFamilies::render($lf));
        $crlf = str_replace("\n", "\r\n", $lf);
        self::assertSame($crlf, \Php85ExpressionFamilies::render($crlf));
    }

    public function testGeneratorRejectsMissingOwnedProduction(): void
    {
        require_once dirname(__DIR__, 3) . '/tools/8.5/generate-expressions.php';
        $this->expectException(\RuntimeException::class);
        \Php85ExpressionFamilies::render('source-file = "x" ;');
    }

    public function testCompleteAstAuditFindsNoRemainingOptionalOneOrMore(): void
    {
        $candidates = [];
        $ungroup = static function (Node $node): Node {
            while ($node instanceof GroupNode) $node = $node->expression;
            return $node;
        };
        $walk = function (Node $node, string $name) use (&$walk, $ungroup, &$candidates): void {
            if ($node instanceof OptionalNode) {
                $body = $ungroup($node->expression);
                if ($body instanceof SequenceNode && count($body->elements) >= 2) {
                    $elements = $body->elements;
                    $last = $ungroup(array_pop($elements));
                    if ($last instanceof RepetitionNode) {
                        $head = count($elements) === 1 ? $ungroup($elements[0]) : new SequenceNode($elements);
                        if ($head == $ungroup($last->expression)) $candidates[] = $name;
                    }
                }
            }
            foreach (get_object_vars($node) as $value) {
                foreach (is_array($value) ? $value : [$value] as $child) {
                    if ($child instanceof Node) $walk($child, $name);
                }
            }
        };
        foreach ((new Parser())->parse(self::source())->productions() as $production) {
            $walk($production->expression, $production->name);
        }
        self::assertSame([], $candidates);
        foreach ((new Parser())->parse(self::restoredSource())->productions() as $production) {
            $walk($production->expression, $production->name);
        }
        self::assertSame(['double-quoted-string'], $candidates);
    }

    #[DataProvider('memberBodies')]
    public function testSharedMemberAcceptance(string $source, bool $expected): void
    {
        $matcher = PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3));
        self::assertSame($expected, $matcher->matches('8.5', '<?php ' . $source)->matched, $source);
    }

    public static function memberBodies(): iterable
    {
        foreach (['class', 'interface', 'trait', 'enum'] as $kind) {
            // Contextual legality deliberately differs across declaration kinds.
            foreach (['', 'public $x;', 'function f();', 'function f() {}', 'const A = 1;',
                'case A;', 'use T;', 'const A = 1; function f();'] as $i => $body) {
                yield "$kind valid $i" => ["$kind C { $body }", true];
            }
            foreach ([';', 'public;', 'const A;', 'case;', 'function f(;', 'use;'] as $i => $body) {
                yield "$kind malformed $i" => ["$kind C { $body }", false];
            }
        }
    }

    public function testMemberDerivationsPreserveSpansAfterAliasErasure(): void
    {
        $old = (new Parser())->parse(self::restoredSource());
        $new = (new Parser())->parse(self::source());
        $spans = function (array $tree) use (&$spans): array {
            $name = $tree['name'];
            $result = [];
            if (in_array($name, ['interface-member-list', 'enum-member-list'], true)) $name = 'class-member-list';
            if (!str_starts_with($name, '@') && !in_array($name, ['interface-member', 'enum-member'], true)) {
                $result[] = [$name, $tree['start'], $tree['end']];
            }
            foreach ($tree['children'] as $child) $result = [...$result, ...$spans($child)];
            return $result;
        };
        foreach (['interface I {}', 'interface I { function f(); }', 'enum E {}',
            'enum E { case A; case B; }', 'interface I { public $x; }'] as $source) {
            $rule = str_starts_with($source, 'interface') ? 'interface-declaration' : 'enum-declaration';
            $before = (new DerivationForest($old, $source))->trees($rule);
            $after = (new DerivationForest($new, $source))->trees($rule);
            self::assertCount(1, $before, $source);
            self::assertCount(1, $after, $source);
            self::assertSame($spans($before[0]), $spans($after[0]), $source);
        }
    }

    public function testStringRepetitionHasSameOrderedPartsAndDerivationCount(): void
    {
        // Traverse the actual EBNF body, avoiding the aggregate string primitive.
        // Use variable tokens as nonnullable, distinguishable part witnesses.
        $grammarFor = static function (string $source): Grammar {
            $body = (new Parser())->parse($source)->productionMap()['double-quoted-string'];
            return new Grammar([$body, ...(new Parser())->parse('encapsulated-string-part = variable ;')->productions()]);
        };
        // Raw quote tokens are not exposed by the host scanner for strings, so
        // replace delimiters with parentheses only in this test projection.
        $project = static function (Grammar $grammar): Grammar {
            $body = $grammar->productionMap()['double-quoted-string']->expression;
            $elements = $body->elements;
            $elements[1] = new \PhpGrammar\Ebnf\LiteralNode('(');
            $elements[count($elements) - 1] = new \PhpGrammar\Ebnf\LiteralNode(')');
            return new Grammar([new \PhpGrammar\Ebnf\Production('double-quoted-string', new SequenceNode($elements), 1),
                $grammar->productionMap()['encapsulated-string-part']]);
        };
        $old = $project($grammarFor(self::restoredSource()));
        $new = $project($grammarFor(self::source()));
        foreach (['()', '($a)', '($a $b)', 'b($a $b $c)', 'B()'] as $source) {
            $before = (new DerivationForest($old, $source))->trees('double-quoted-string');
            $after = (new DerivationForest($new, $source))->trees('double-quoted-string');
            self::assertCount(1, $before, $source);
            self::assertCount(1, $after, $source);
            $parts = function (array $tree) use (&$parts): array {
                if ($tree['name'] === 'encapsulated-string-part') return [[$tree['start'], $tree['end']]];
                $result = [];
                foreach ($tree['children'] as $child) $result = [...$result, ...$parts($child)];
                return $result;
            };
            self::assertSame($parts($before[0]), $parts($after[0]), $source);
        }
        foreach (['(', '($a', '($a,)', '(1)'] as $source) {
            self::assertSame([], (new DerivationForest($old, $source))->trees('double-quoted-string'));
            self::assertSame([], (new DerivationForest($new, $source))->trees('double-quoted-string'));
        }
    }

    #[DataProvider('strings')]
    public function testStringSourceIntegration(string $source, bool $expected): void
    {
        self::assertSame($expected, PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))
            ->matches('8.5', '<?php ' . $source . ';')->matched);
    }

    public static function strings(): iterable
    {
        foreach (['""', 'b""', 'B""', '"plain"', '"$a"', '"$a $b"', '"{$a[0]} text $b"'] as $source) yield [$source, true];
        foreach (['"', '"{$a"', '"{$a[}"'] as $source) yield [$source, false];
    }
}
