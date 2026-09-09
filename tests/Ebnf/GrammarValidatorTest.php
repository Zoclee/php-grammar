<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GrammarValidatorTest extends TestCase
{
    public function testAcceptsStructurallyValidGrammar(): void
    {
        $result = $this->validate(<<<'EBNF'
            source-file = statement ;
            statement = "echo" , expression , ";" ;
            expression = identifier | literal ;
            identifier = "id" ;
            literal = "1" ;
            EBNF);

        self::assertTrue($result->isValid(), implode(', ', $result->codes()));
    }

    public function testAllowsConfiguredLexicalPrimitiveReferences(): void
    {
        $result = $this->validate('source-file = code-unit ;');

        self::assertTrue($result->isValid(), implode(', ', $result->codes()));
    }

    #[DataProvider('invalidGrammarProvider')]
    public function testReportsStructuralValidationErrors(string $source, string $expectedCode): void
    {
        $result = $this->validate($source);

        self::assertContains($expectedCode, $result->codes());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidGrammarProvider(): iterable
    {
        yield 'duplicate-production' => [
            "source-file = item ;\nitem = \"a\" ;\nitem = \"b\" ;",
            'duplicate-production',
        ];

        yield 'undefined-reference' => [
            'source-file = missing-rule ;',
            'undefined-production',
        ];

        yield 'unreachable-production' => [
            "source-file = item ;\nitem = \"a\" ;\nunused = \"b\" ;",
            'unreachable-production',
        ];

        yield 'missing-root' => [
            'other-root = "a" ;',
            'missing-root-production',
        ];

        yield 'invalid-production-name' => [
            'Source_File = "a" ;',
            'invalid-production-name',
        ];

        yield 'empty-production' => [
            'source-file = [ item ] ; item = "a" ;',
            'empty-production',
        ];
    }

    private function validate(string $source): \PhpGrammar\Ebnf\Validation\ValidationResult
    {
        $grammar = (new Parser())->parse($source);

        return (new GrammarValidator(
            requiredRoot: 'source-file',
            reachabilityRoots: ['source-file'],
        ))->validate($grammar);
    }
}
