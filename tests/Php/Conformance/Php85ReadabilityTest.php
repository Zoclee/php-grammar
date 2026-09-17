<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Parser;
use PHPUnit\Framework\TestCase;

final class Php85ReadabilityTest extends TestCase
{
    private static function source(): string
    {
        require_once dirname(__DIR__, 3) . '/tools/8.5/grammar-sections.php';
        require_once dirname(__DIR__, 3) . '/tools/8.5/generate-expressions.php';
        return file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.ebnf');
    }

    public function testEveryProductionBodyMatchesThePreReadabilityBaseline(): void
    {
        $grammar = (new Parser())->parse(self::source());
        $hashes = [];
        foreach ($grammar->productions() as $production) {
            $hashes[$production->name] = hash('sha256', serialize($production->expression));
        }
        ksort($hashes);
        self::assertCount(360, $grammar->productions());
        self::assertSame('source-file', $grammar->productions()[0]->name);
        // Frozen before organization; excludes only definition order and source lines.
        self::assertSame('3febd80b8d88ecdfc5ec90ec12a4cce5db6d49270ee12cdc87386fbc84cf5f86',
            hash('sha256', json_encode($hashes)));
    }

    public function testSectionsPartitionTheGrammarAndIndexIsFresh(): void
    {
        $source = self::source();
        $sections = \Php85GrammarSections::read($source);
        self::assertSame(['source', 'lexical', 'names', 'literals', 'types', 'expressions',
            'dereferencing', 'arguments', 'statements', 'functions', 'classes', 'namespaces',
            'attributes', 'termination', 'primitives', 'reserved', 'prefix', 'initializers',
            'matched', 'precedence', 'yield'], array_keys($sections));
        $names = [];
        foreach ($sections as $section) {
            self::assertNotEmpty($section['productions']);
            array_push($names, ...$section['productions']);
        }
        self::assertSame(array_keys((new Parser())->parse($source)->productionMap()), $names);
        self::assertSame($names, array_values(array_unique($names)));
        $markdown = str_replace("\r\n", "\n", file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.md'));
        self::assertStringContainsString(\Php85GrammarSections::index($source), $markdown);
    }

    public function testProtectedFamiliesStayTogether(): void
    {
        $source = self::source();
        $sections = \Php85GrammarSections::read($source);
        foreach ((new Parser())->parse($source)->productionMap() as $name => $production) {
            if (str_starts_with($name, 'closed-yield-') || str_starts_with($name, 'yield-key')) {
                self::assertContains($name, $sections['yield']['productions']);
            }
            if (str_starts_with($name, 'matched-') || str_starts_with($name, 'unmatched-')) {
                self::assertContains($name, $sections['matched']['productions']);
            }
            if (str_contains($name, 'without-static')) {
                self::assertContains($name, $sections['types']['productions']);
            }
        }
        foreach (['type', 'simple-type', 'nullable-type', 'union-type', 'intersection-type', 'return-type'] as $name) {
            self::assertContains($name, $sections['types']['productions']);
        }
        foreach (['argument-list', 'ordinary-argument-list', 'first-class-callable-arguments',
            'constructor-argument-list', 'clone-argument-list', 'argument', 'argument-no-expression'] as $name) {
            self::assertContains($name, $sections['arguments']['productions']);
        }
        self::assertSame(['constant-expression', 'parameter-default', 'property-default',
            'class-constant-initializer', 'global-constant-initializer', 'enum-case-initializer'],
            $sections['initializers']['productions']);
    }

    public function testGeneratedRulesAreConfinedToTheirTwoContiguousRegions(): void
    {
        $source = self::source();
        $owned = [];
        foreach (['EXPRESSION PRECEDENCE', 'CLOSED-YIELD'] as $label) {
            $begin = '(* BEGIN GENERATED ' . $label . ' RULES *)';
            $end = '(* END GENERATED ' . $label . ' RULES *)';
            self::assertSame(1, substr_count($source, $begin));
            self::assertSame(1, substr_count($source, $end));
            $start = strpos($source, $begin) + strlen($begin);
            $finish = strpos($source, $end);
            self::assertGreaterThan($start, $finish);
            $names = array_keys((new Parser())->parse(substr($source, $start, $finish - $start))->productionMap());
            self::assertCount($label === 'CLOSED-YIELD' ? 6 : 34, $names);
            foreach (array_chunk($names, 2) as [$expression, $context]) {
                self::assertSame(str_replace('-expression', '-prefix-context', $expression), $context);
            }
            array_push($owned, ...$names);
        }
        $expected = array_keys(\Php85ExpressionFamilies::productions());
        sort($expected); sort($owned);
        self::assertSame($expected, $owned);
    }
}
