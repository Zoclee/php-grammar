<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Repository;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class VersionPackageConsistencyTest extends TestCase
{
    public function testEveryManifestVersionIsACompletePackage(): void
    {
        $manifest = self::manifest();
        $repository = new GrammarRepository($manifest);

        foreach ($manifest->packages() as $package) {
            self::assertMatchesRegularExpression('/^\d+\.\d+$/', $package->version);
            self::assertSame('source-file', $package->rootProduction);
            self::assertSame('grammar/' . $package->version . '/php.ebnf', $package->grammarPath);
            self::assertSame('grammar/' . $package->version . '/php.md', $package->documentationPath);
            self::assertSame('tests/fixtures/php/' . $package->version, $package->conformanceFixturePath);

            self::assertFileExists($manifest->absolutePath($package->grammarPath));
            self::assertFileExists($manifest->absolutePath($package->documentationPath));
            self::assertDirectoryExists($manifest->absolutePath($package->conformanceFixturePath));
            self::assertDirectoryExists($manifest->absolutePath($package->conformanceFixturePath) . DIRECTORY_SEPARATOR . 'valid');
            self::assertDirectoryExists($manifest->absolutePath($package->conformanceFixturePath) . DIRECTORY_SEPARATOR . 'invalid');

            $grammar = $repository->load($package->version);
            self::assertTrue($grammar->hasProduction($package->rootProduction));
            self::assertInstanceOf(Lexer::class, Lexer::forVersion($package->lexerVersion));
        }
    }

    public function testGrammarDirectoriesExactlyMatchManifestVersions(): void
    {
        $manifestVersions = self::manifest()->versions();
        $grammarRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'grammar';
        $directoryVersions = [];

        foreach (glob($grammarRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $directory) {
            $version = basename($directory);
            self::assertMatchesRegularExpression('/^\d+\.\d+$/', $version, 'Malformed grammar directory: ' . $directory);
            $directoryVersions[] = $version;
        }

        usort($directoryVersions, 'version_compare');
        self::assertSame($manifestVersions, $directoryVersions);
    }

    public function testEveryVersionGrammarParsesAndValidatesIndependently(): void
    {
        $manifest = self::manifest();
        foreach ($manifest->packages() as $package) {
            $source = file_get_contents($manifest->absolutePath($package->grammarPath));
            self::assertIsString($source);

            $grammar = (new Parser())->parse($source);
            $result = (new GrammarValidator(
                requiredRoot: $package->rootProduction,
                reachabilityRoots: [$package->rootProduction, 'whitespace', 'comment'],
                allowedEmptyProductions: self::allowedEmptyProductions(),
                lexicalPrimitives: $manifest->lexicalPrimitives(),
            ))->validate($grammar);

            self::assertTrue($result->isValid(), $package->version . ': ' . implode(', ', $result->codes()));
        }
    }

    public function testDocumentationReferencesPointToExistingVersionPaths(): void
    {
        $root = dirname(__DIR__, 2);
        $files = array_merge(
            glob($root . DIRECTORY_SEPARATOR . '*.md') ?: [],
            glob($root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . '*.md') ?: [],
            glob($root . DIRECTORY_SEPARATOR . 'grammar' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.md') ?: [],
        );

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            self::assertIsString($contents);

            preg_match_all('~(?:grammar/(\d+\.\d+(?:\.\d+)?)/php\.(?:ebnf|md)|tests/fixtures/php/(\d+\.\d+(?:\.\d+)?)(?:/(?:valid|invalid))?)~', str_replace('\\', '/', $contents), $matches);
            foreach ($matches[0] as $index => $pathPrefix) {
                $version = $matches[1][$index] !== '' ? $matches[1][$index] : $matches[2][$index];
                self::assertContains($version, self::manifest()->versions(), $file . ' references unsupported version ' . $version);
                self::assertFileExists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pathPrefix), $file . ' references missing path ' . $pathPrefix);
            }
        }
    }

    public function testGrammarFilesDoNotReferenceOtherVersionPackages(): void
    {
        $manifest = self::manifest();
        foreach ($manifest->packages() as $package) {
            $contents = file_get_contents($manifest->absolutePath($package->grammarPath));
            self::assertIsString($contents);

            self::assertDoesNotMatchRegularExpression('~grammar/\d+\.\d+/~', str_replace('\\', '/', $contents), $package->grammarPath);
        }
    }

    private static function manifest(): RepositoryManifest
    {
        return RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 2));
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
