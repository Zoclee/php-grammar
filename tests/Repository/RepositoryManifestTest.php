<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Repository;

use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Repository\RepositoryManifestException;
use PHPUnit\Framework\TestCase;

final class RepositoryManifestTest extends TestCase
{
    public function testLoadsSupportedVersionsFromManifest(): void
    {
        $manifest = RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 2));

        self::assertSame(['8.5'], $manifest->versions());
        self::assertSame(['code-unit'], $manifest->lexicalPrimitives());
        self::assertSame('grammar/8.5/php.ebnf', $manifest->package('8.5')->grammarPath);
    }

    public function testRejectsMissingManifest(): void
    {
        $this->expectException(RepositoryManifestException::class);

        RepositoryManifest::fromRepositoryRoot(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-grammar-missing-manifest');
    }

    public function testRejectsDuplicateVersions(): void
    {
        $this->expectException(RepositoryManifestException::class);

        RepositoryManifest::fromArray('repo', [
            'versions' => [
                [
                    'version' => '8.5',
                    'rootProduction' => 'source-file',
                    'grammar' => 'grammar/8.5/php.ebnf',
                    'documentation' => 'grammar/8.5/php.md',
                    'conformanceFixtures' => 'tests/fixtures/php/8.5',
                    'lexer' => '8.5',
                ],
                [
                    'version' => '8.5',
                    'rootProduction' => 'source-file',
                    'grammar' => 'grammar/8.5/php.ebnf',
                    'documentation' => 'grammar/8.5/php.md',
                    'conformanceFixtures' => 'tests/fixtures/php/8.5',
                    'lexer' => '8.5',
                ],
            ],
        ]);
    }

    public function testRejectsMalformedVersionNames(): void
    {
        $this->expectException(RepositoryManifestException::class);

        RepositoryManifest::fromArray('repo', [
            'versions' => [
                [
                    'version' => '8.5.1',
                    'rootProduction' => 'source-file',
                    'grammar' => 'grammar/8.5.1/php.ebnf',
                    'documentation' => 'grammar/8.5.1/php.md',
                    'conformanceFixtures' => 'tests/fixtures/php/8.5.1',
                    'lexer' => '8.5.1',
                ],
            ],
        ]);
    }
}
