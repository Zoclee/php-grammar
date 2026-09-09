<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RepositoryGrammarTest extends TestCase
{
    /**
     * @param non-empty-string $path
     */
    #[DataProvider('grammarFileProvider')]
    public function testRepositoryGrammarFilesParseAndPassIntegrityValidation(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $grammar = (new Parser())->parse($source);
        $validator = new GrammarValidator(
            requiredRoot: 'source-file',
            reachabilityRoots: ['source-file', 'whitespace', 'comment'],
            allowedEmptyProductions: self::allowedEmptyProductions(),
        );
        $result = $validator->validate($grammar);

        self::assertTrue(
            $result->isValid(),
            implode("\n", array_map(
                static fn ($error): string => $error->code . ': ' . $error->message,
                $result->errors,
            )),
        );
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function grammarFileProvider(): iterable
    {
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'grammar';
        foreach (glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'php.ebnf') ?: [] as $path) {
            yield basename(dirname($path)) => [$path];
        }
    }

    /**
     * These productions intentionally model optional or zero-or-more syntax.
     *
     * @return list<string>
     */
    private static function allowedEmptyProductions(): array
    {
        return [
            'whitespace',
            'source-file',
            'top-statement-list',
            'optional-type-without-static',
            'return-type',
            'parameter-list',
            'array-pair-list',
            'constant-array-pair-list',
            'backtick-string-part-list',
            'inner-statement-list',
            'expression-statement',
            'for-expression-list',
            'for-condition-expression-list',
            'switch-case-list',
            'catch-list',
            'parameter-modifiers',
            'class-modifiers',
            'class-member-list',
            'property-hook-list',
            'property-hook-modifiers',
            'method-modifiers',
            'class-constant-modifiers',
            'interface-member-list',
            'enum-member-list',
            'line-comment-text',
            'block-comment-text',
            'doc-comment-text',
            'single-quoted-string-content',
            'heredoc-body',
            'nowdoc-body',
            'halt-compiler-data',
        ];
    }
}
