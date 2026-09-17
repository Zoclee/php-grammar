<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Repository;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\ProductionIndex;
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Repository\RepositoryManifestException;
use PHPUnit\Framework\TestCase;

final class ConsumerWorkflowTest extends TestCase
{
    private function manifest(): RepositoryManifest
    {
        return RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 2));
    }

    public function testDiscoverLoadInspectAndMatch(): void
    {
        $manifest = $this->manifest();
        self::assertSame(['8.5'], $manifest->versions());
        self::assertTrue($manifest->hasVersion('8.5'));
        self::assertFalse($manifest->hasVersion('8.6'));
        self::assertSame('8.5', $manifest->latestVersion());
        $package = $manifest->package('8.5');
        self::assertSame('stable', $package->status);
        self::assertFileExists($manifest->absolutePath($package->grammarPath));
        self::assertFileExists($manifest->absolutePath($package->documentationPath));
        $repository = new GrammarRepository($manifest);
        $grammar = $repository->load('8.5');
        self::assertCount(360, $grammar->productionNames());
        self::assertTrue($grammar->hasProduction('expression'));
        self::assertSame('expression', $grammar->production('expression')->name);
        $matcher = PhpGrammarMatcher::forManifest($manifest);
        self::assertTrue($matcher->matchesRule('8.5', 'expression', '$a + 1')->matched);
        self::assertFalse($matcher->matchesRule('8.5', 'expression', '$a +')->matched);
        self::assertTrue($matcher->matches('8.5', '<?php echo 1;')->matched);
        self::assertFalse($matcher->matches('8.5', '<?php echo ;')->matched);
        self::assertFalse($manifest->lexicalPrimitive('encapsed-code-unit')['independentMatcher']);
        self::assertStringContainsString('heredoc', $manifest->lexicalPrimitive('encapsed-code-unit')['scannerContext']);
        $index = $repository->productionIndex('8.5');
        self::assertCount(21, $index->sections());
        self::assertSame('expressions', $index->sectionForRule('expression'));
        self::assertContains('expression', $index->rulesInSection('expressions'));
        self::assertSame($grammar->productionNames(), array_merge(...array_column(array_values($index->sections()), 'productions')));
        foreach ($grammar->productions() as $production) {
            foreach ($production->references() as $reference) {
                self::assertContains($production->name, $grammar->referencesTo($reference));
            }
        }
    }

    public function testVersionDiscoveryUsesManifestAndNumericOrder(): void
    {
        $data = json_decode(file_get_contents(dirname(__DIR__, 2) . '/php-grammar.json'), true);
        $template = $data['versions'][0];
        $data['versions'] = array_map(static fn (string $version): array => array_replace($template, ['version' => $version]), ['8.10', '8.5', '8.9']);
        $manifest = RepositoryManifest::fromArray('unused', $data);
        self::assertSame(['8.5', '8.9', '8.10'], $manifest->versions());
        self::assertSame('8.10', $manifest->latestVersion());
    }

    public function testUnknownVersionDiagnostic(): void
    {
        $this->expectException(RepositoryManifestException::class);
        $this->expectExceptionMessage('Unsupported grammar version "9.9". Available versions: 8.5. Use versions() to discover packages.');
        (new GrammarRepository($this->manifest()))->load('9.9');
    }

    public function testUnknownProductionLookup(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown production "missing".');
        (new GrammarRepository($this->manifest()))->load('8.5')->production('missing');
    }

    public function testUnknownProductionMatchRemainsAFailureResult(): void
    {
        $matcher = PhpGrammarMatcher::forManifest($this->manifest());
        $result = $matcher->matchesRule('8.5', 'missing', '"unterminated');
        self::assertFalse($result->matched);
        self::assertSame(['Unknown production "missing" for PHP 8.5. Use productionNames() to discover productions.'], $result->expected);
        $result = $matcher->matchesRule('8.5', 'nowdoc-code-unit', 'a');
        self::assertFalse($result->matched);
        self::assertStringContainsString('requires a byte matcher', $result->expected[0]);
    }

    public function testIndexHandlesUnsectionedGrammarAndUnknownSections(): void
    {
        $source = 'root = "x" ;';
        $grammar = (new Parser())->parse($source);
        $index = ProductionIndex::fromSource($grammar, $source);
        self::assertSame([], $index->sections());
        self::assertNull($index->sectionForRule('root'));
        self::assertSame([], $grammar->referencesTo('absent'));
        $this->expectException(\OutOfBoundsException::class);
        $index->rulesInSection('absent');
    }

    public function testSectionMembershipUsesParsedProductions(): void
    {
        $source = "(* SECTION 1 source: Source *)\r\n(* fake = ignored *)\r\nroot = \"x\" ;\r\n";
        $index = ProductionIndex::fromSource((new Parser())->parse($source), $source);
        self::assertSame(['root'], $index->rulesInSection('source'));
    }

    public function testLegacyManifestRemainsReadable(): void
    {
        $data = ['versions' => [[
            'version' => '8.5', 'rootProduction' => 'source-file', 'grammar' => 'grammar/8.5/php.ebnf',
            'documentation' => 'grammar/8.5/php.md', 'conformanceFixtures' => 'tests/fixtures/php/8.5', 'lexer' => '8.5',
        ]]];
        $manifest = RepositoryManifest::fromArray('repo', $data);
        self::assertSame('unspecified', $manifest->package('8.5')->status);
        self::assertSame(['code-unit'], $manifest->lexicalPrimitives());
        self::assertNull($manifest->lexicalPrimitive('code-unit'));
    }

    public function testInvalidManifestFieldDiagnostics(): void
    {
        $data = json_decode(file_get_contents(dirname(__DIR__, 2) . '/php-grammar.json'), true);
        $data['versions'][0]['grammar'] = '../outside.ebnf';
        $this->expectException(RepositoryManifestException::class);
        $this->expectExceptionMessage('versions.0.grammar:');
        RepositoryManifest::fromArray('repo', $data);
    }

    public function testUnknownPrimitiveDiagnostic(): void
    {
        $this->expectException(RepositoryManifestException::class);
        $this->expectExceptionMessage('Unknown lexical primitive "missing".');
        $this->manifest()->lexicalPrimitive('missing');
    }
}
