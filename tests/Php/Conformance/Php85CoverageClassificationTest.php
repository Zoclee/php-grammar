<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Php\Conformance\Php85CoverageClassification;
use PHPUnit\Framework\TestCase;

final class Php85CoverageClassificationTest extends TestCase
{
    public function testSharedSyntacticRulesAreNotHiddenByPrimitiveDescendants(): void
    {
        $grammar = (new Parser())->parse('source-file = token | shared; token = leaf | shared; leaf = "x"; shared = "y"; whitespace = " ";');
        $classifier = new Php85CoverageClassification($grammar, ['token']);
        self::assertSame('primitive-bypassed', $classifier->classify('token')['classification']);
        self::assertSame('primitive-bypassed', $classifier->classify('leaf')['classification']);
        self::assertSame('meaningful-gap', $classifier->classify('shared')['classification']);
        self::assertSame('meaningful-gap', $classifier->classify('source-file/alternative:2')['classification']);
        self::assertSame('trivia-removed', $classifier->classify('whitespace')['classification']);
        self::assertSame('meaningful-gap', $classifier->classify('new-unreviewed-rule')['classification']);
    }

    public function testLiveTypeRestrictionsDoNotHidePossibleFoldingWitnesses(): void
    {
        $grammar = (new Parser())->parse('source-file = simple-type-without-static; simple-type-without-static = "void" | "int" | "never";');
        $classifier = new Php85CoverageClassification($grammar, []);
        self::assertSame('meaningful-gap', $classifier->classify('simple-type-without-static/alternative:1')['classification']);
        self::assertSame('meaningful-gap', $classifier->classify('simple-type-without-static/alternative:2')['classification']);
        self::assertSame('meaningful-gap', $classifier->classify('simple-type-without-static/alternative:3')['classification']);
    }

    public function testEnumAliasClassificationFollowsTheScannerSensitiveAlternative(): void
    {
        $grammar = (new Parser())->parse('source-file = reserved-non-modifiers; reserved-non-modifiers = "match" | "enum";');
        $classifier = new Php85CoverageClassification($grammar, []);
        self::assertSame('meaningful-gap', $classifier->classify('reserved-non-modifiers/alternative:1')['classification']);
        self::assertSame('scanner-context-only', $classifier->classify('reserved-non-modifiers/alternative:2')['classification']);
    }
}
