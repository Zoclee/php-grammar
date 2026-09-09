<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Grammar;
use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PhpGrammar\Repository\RepositoryManifest;

final class GrammarRepository
{
    /** @var array<string, Grammar> */
    private array $cache = [];

    private readonly RepositoryManifest $manifest;

    public function __construct(
        string|RepositoryManifest $repositoryRootOrManifest,
    ) {
        $this->manifest = is_string($repositoryRootOrManifest)
            ? RepositoryManifest::fromRepositoryRoot($repositoryRootOrManifest)
            : $repositoryRootOrManifest;
    }

    public function load(string $version): Grammar
    {
        if (isset($this->cache[$version])) {
            return $this->cache[$version];
        }

        $package = $this->manifest->package($version);
        $path = $this->manifest->absolutePath($package->grammarPath);
        if (!is_file($path)) {
            throw new ConformanceException(sprintf('Unknown grammar version "%s".', $version));
        }

        $source = file_get_contents($path);
        if (!is_string($source)) {
            throw new ConformanceException(sprintf('Failed to read grammar file "%s".', $path));
        }

        $grammar = (new Parser())->parse($source);
        $result = (new GrammarValidator(
            requiredRoot: $package->rootProduction,
            reachabilityRoots: ['source-file', 'whitespace', 'comment'],
            allowedEmptyProductions: self::allowedEmptyProductions(),
            lexicalPrimitives: $this->manifest->lexicalPrimitives(),
        ))->validate($grammar);

        if (!$result->isValid()) {
            throw new ConformanceException(implode("\n", array_map(
                static fn ($error): string => $error->code . ': ' . $error->message,
                $result->errors,
            )));
        }

        return $this->cache[$version] = $grammar;
    }

    public function manifest(): RepositoryManifest
    {
        return $this->manifest;
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
