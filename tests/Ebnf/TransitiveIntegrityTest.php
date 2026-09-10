<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PHPUnit\Framework\TestCase;

final class TransitiveIntegrityTest extends TestCase
{
    public function testUnintendedEmptyAliasIsDetectedThroughACycle(): void
    {
        $grammar = (new Parser())->parse('source-file = token ; token = alias ; alias = [ token ] ;');
        $result = (new GrammarValidator(allowedEmptyProductions: ['source-file', 'alias']))->validate($grammar);
        self::assertContains('empty-production', $result->codes());
        self::assertStringContainsString('"token"', $result->errors[0]->message);
    }
}
