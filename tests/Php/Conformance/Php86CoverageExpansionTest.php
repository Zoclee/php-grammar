<?php
declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\{Lexer, LexerException};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php86CoverageExpansionTest extends TestCase
{
    private static function root(): string { return dirname(__DIR__, 3); }
    private static function read(string $path): array
    {
        return json_decode(file_get_contents(self::root() . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function cases(): iterable
    {
        foreach (self::read('tests/fixtures/php/8.6/coverage-cases.json')['cases'] as $case) yield $case['batch'] . ':' . $case['id'] => [$case];
    }

    #[DataProvider('cases')]
    public function testSourceStructureAndSelectedCoverage(array $case): void
    {
        $source = file_get_contents(self::root() . '/' . $case['fixture']);
        self::assertSame($case['sha256'], hash('sha256', $source));
        self::assertNotEmpty($case['sourceReferences']);
        $matcher = PhpGrammarMatcher::forRepositoryRoot(self::root());
        foreach (['8.5' => '85', '8.6' => '86'] as $version => $suffix) {
            $coverage = new CoverageCollector();
            self::assertSame($case['structural' . $suffix], $matcher->withCoverage($coverage)->matches($version, $source)->matched, $case['id'] . ' ' . $version);
            if ($suffix === '86' && $case['structural86']) {
                foreach ($case['targets'] as $target) self::assertContains($target, [...$coverage->matchedProductions(), ...$coverage->matchedPrimitives()]);
                foreach ($case['selectedAlternatives'] as $alternative) self::assertContains($alternative, $coverage->matchedAlternatives());
            }
        }
    }

    public static function lexicalCases(): iterable
    {
        foreach (self::read('tests/fixtures/php/8.6/coverage-lexical.json')['cases'] as $case) yield $case['id'] => [$case];
    }

    #[DataProvider('lexicalCases')]
    public function testLexicalStatesAndSourceValidationAreDistinct(array $case): void
    {
        self::assertSame($case['sha256'], hash('sha256', $case['source']));
        foreach (['8.5', '8.6'] as $version) {
            $tokens = null;
            try { $tokens = Lexer::forVersion($version)->withShortOpenTag($case['shortOpenTag'])->tokenize($case['source']); }
            catch (LexerException $error) { self::assertFalse($case['lexerAccepted'], $error->getMessage()); }
            self::assertSame($case['lexerAccepted'], $tokens !== null);
            if ($tokens !== null) {
                self::assertSame($case['source'], implode('', array_map(static fn($t) => $t->lexeme, $tokens->all())));
                foreach ($case['requiredTokenTypes'] as $type) self::assertContains($type, array_map(static fn($t) => $t->type->value, $tokens->all()));
            }
            $expected = $case['id'] === 'pfa' && $version === '8.5' ? false : $case['lintAccepted'];
            self::assertSame($expected, PhpGrammarMatcher::forRepositoryRoot(self::root())->withShortOpenTag($case['shortOpenTag'])->matches($version, $case['source'])->matched);
        }
    }

    public function testRecordedOracleEvidenceRetainsStagesAndInputs(): void
    {
        $report = self::read('docs/8.6/coverage-expansion-validation.json');
        self::assertSame([], $report['errors']);
        self::assertCount(310, $report['observations']);
        foreach ($report['inputHashes'] + $report['fixtureHashes'] as $path => $hash) self::assertSame($hash, hash_file('sha256', self::root() . '/' . $path), $path);
        $observed = [];
        foreach ($report['observations'] as $row) $observed[$row['id']][$row['version']] = $row;
        foreach (self::read('tests/fixtures/php/8.6/coverage-cases.json')['cases'] as $case) {
            foreach (['8.5' => '85', '8.6' => '86'] as $version => $suffix) {
                $row = $observed[$case['id']][$version];
                self::assertSame($case['stage'], $row['stage']);
                self::assertSame($case['accept' . $suffix], $row['result']['exit'] === 0);
                self::assertSame($case['structural' . $suffix], $row['structural']);
            }
        }
        // The deliberately narrower hook-name policy must not become ordinary valid coverage.
        self::assertFalse($observed['hook-discarded']['8.6']['structural']);
        self::assertSame(0, $observed['hook-discarded']['8.6']['lint']['exit']);
    }

    public function testBoundaryIndexAndDiagnosticBoundarySeparation(): void
    {
        $index = self::read('tests/fixtures/php/8.6/boundary-cases.json');
        self::assertCount(36, $index['files']);
        foreach ($index['files'] as $path => $hash) self::assertSame($hash, hash_file('sha256', self::root() . '/' . $path));
        $diagnostic = self::read('tests/fixtures/php/8.6/diagnostic-boundaries.json');
        self::assertCount(2, $diagnostic['cases']);
        foreach ($diagnostic['cases'] as $case) {
            self::assertTrue($case['accept85']); self::assertTrue($case['accept86']);
            self::assertSame('PHP86-010', $case['finding']);
            self::assertArrayHasKey('warning85', $case); self::assertArrayHasKey('warning86', $case);
        }
    }
}
