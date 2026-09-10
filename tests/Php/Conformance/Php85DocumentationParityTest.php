<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PHPUnit\Framework\TestCase;

final class Php85DocumentationParityTest extends TestCase
{
    public function testCompleteGrammarBlockMatchesEbnf(): void
    {
        $root = dirname(__DIR__, 3);
        $documentation = file_get_contents($root . '/grammar/8.5/php.md');
        self::assertSame(1, preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s', $documentation, $match));
        self::assertSame(trim(file_get_contents($root . '/grammar/8.5/php.ebnf')), trim($match[1]));
    }
}
