<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Grammar;
use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\Validation\GrammarValidator;

final class GrammarRepository
{
    /** @var array<string, Grammar> */
    private array $cache = [];

    public function __construct(
        private readonly string $repositoryRoot,
    ) {
    }

    public function load(string $version): Grammar
    {
        if (isset($this->cache[$version])) {
            return $this->cache[$version];
        }

        $path = $this->repositoryRoot . DIRECTORY_SEPARATOR . 'grammar' . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . 'php.ebnf';
        if (!is_file($path)) {
            throw new ConformanceException(sprintf('Unknown grammar version "%s".', $version));
        }

        $source = file_get_contents($path);
        if (!is_string($source)) {
            throw new ConformanceException(sprintf('Failed to read grammar file "%s".', $path));
        }

        $grammar = (new Parser())->parse($source);
        $result = (new GrammarValidator(
            requiredRoot: 'source-file',
            reachabilityRoots: ['source-file', 'whitespace', 'comment'],
            allowedEmptyProductions: self::allowedEmptyProductions(),
            lexicalPrimitives: ['code-unit'],
        ))->validate($grammar);

        if (!$result->isValid()) {
            throw new ConformanceException(implode("\n", array_map(
                static fn ($error): string => $error->code . ': ' . $error->message,
                $result->errors,
            )));
        }

        return $this->cache[$version] = $grammar;
    }

    /**
     * @return list<string>
     */
    private static function allowedEmptyProductions(): array
    {
        return [
            'whitespace',
            'source-file',
            'top-statement-list',
            'optional-type',
            'optional-type-without-static',
            'return-type',
            'parameter-list',
            'array-pair-list',
            'backtick-string-part-list',
            'inner-statement-list',
            'expression-statement',
            'for-expression-list',
            'switch-case-list',
            'catch-list',
            'parameter-modifiers',
            'class-modifiers',
            'class-member-list',
            'property-hook-list',
            'property-modifiers',
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
