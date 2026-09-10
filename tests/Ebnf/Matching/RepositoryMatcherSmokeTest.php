<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf\Matching;

use PhpGrammar\Ebnf\Matching\Matcher;
use PhpGrammar\Ebnf\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RepositoryMatcherSmokeTest extends TestCase
{
    #[DataProvider('repositoryRuleProvider')]
    public function testMatchesSelectedRepositoryProductions(string $rule, string $input): void
    {
        $grammarPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'grammar' . DIRECTORY_SEPARATOR . '8.5' . DIRECTORY_SEPARATOR . 'php.ebnf';
        $source = file_get_contents($grammarPath);
        self::assertIsString($source);

        $grammar = (new Parser())->parse($source);
        $result = Matcher::withDefaultPrimitives()->matchesRule($grammar, $rule, $input);

        self::assertTrue($result->matched, implode(', ', $result->expected));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function repositoryRuleProvider(): iterable
    {
        yield 'octal separator' => ['octal-integer-literal', '0_7'];
        yield 'empty source stream' => ['source-file', ''];
        yield 'object-operator' => ['object-operator', '->'];
        yield 'nullsafe-object-operator' => ['nullsafe-object-operator', '?->'];
        yield 'integer-literal' => ['integer-literal', '123'];
        yield 'variable' => ['variable', '$name'];
        yield 'qualified-name' => ['qualified-name', 'Vendor\\Package'];
        yield 'whitespace' => ['whitespace', " \t\n"];
        yield 'non-ascii-byte' => ['non-ascii-byte', "\x80"];
    }
}
