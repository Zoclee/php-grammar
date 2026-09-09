<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\ConformanceException;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class ConformanceVersionSupportTest extends TestCase
{
    public function testUnsupportedVersionFailsClearlyWhenLoadingGrammar(): void
    {
        $this->expectException(\PhpGrammar\Repository\RepositoryManifestException::class);

        (new GrammarRepository(RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3))))->load('8.4');
    }

    public function testUnsupportedLexerVersionFailsClearly(): void
    {
        $manifest = RepositoryManifest::fromArray(dirname(__DIR__, 3), [
            'versions' => [
                [
                    'version' => '8.5',
                    'rootProduction' => 'source-file',
                    'grammar' => 'grammar/8.5/php.ebnf',
                    'documentation' => 'grammar/8.5/php.md',
                    'conformanceFixtures' => 'tests/fixtures/php/8.5',
                    'lexer' => '9.9',
                ],
            ],
            'lexicalPrimitives' => ['code-unit'],
        ]);

        $this->expectException(\PhpGrammar\Php\Lexing\LexerException::class);

        PhpGrammarMatcher::forManifest($manifest)->matches('8.5', '<?php echo 1;');
    }
}
