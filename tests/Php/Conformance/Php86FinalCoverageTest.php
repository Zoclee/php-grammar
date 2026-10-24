<?php
declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\{Lexer, LexerException};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php86FinalCoverageTest extends TestCase
{
    private static function root(): string { return dirname(__DIR__, 3); }
    private static function read(string $path): array
    {
        return json_decode(file_get_contents(self::root() . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function cases(): iterable
    {
        foreach (self::read('tests/fixtures/php/8.6/final-coverage-cases.json')['cases'] as $case) {
            yield $case['batch'] . ':' . $case['id'] => [$case];
        }
    }

    #[DataProvider('cases')]
    public function testSelectedAlternativesAndStructuralStage(array $case): void
    {
        $source = file_get_contents(self::root() . '/' . $case['fixture']);
        self::assertSame($case['sha256'], hash('sha256', $source));
        self::assertNotEmpty($case['sourceReferences']);
        $matcher = PhpGrammarMatcher::forRepositoryRoot(self::root());
        foreach (['8.5' => '85', '8.6' => '86'] as $version => $suffix) {
            $coverage = new CoverageCollector();
            self::assertSame($case['structural' . $suffix], $matcher->withCoverage($coverage)->matches($version, $source)->matched);
            if ($suffix === '86' && $case['structural86']) {
                foreach ($case['targets'] as $target) self::assertContains($target, [...$coverage->matchedProductions(), ...$coverage->matchedPrimitives()]);
                foreach ($case['selectedAlternatives'] as $id) self::assertContains($id, $coverage->matchedAlternatives());
            }
        }
    }

    public static function scannerCases(): iterable
    {
        foreach (self::read('tests/fixtures/php/8.6/final-coverage-lexical.json')['cases'] as $case) yield $case['id'] => [$case];
    }

    #[DataProvider('scannerCases')]
    public function testScannerTransitionsAndSourceValidity(array $case): void
    {
        self::assertSame($case['sha256'], hash('sha256', $case['source']));
        foreach (['8.5' => '85', '8.6' => '86'] as $version => $suffix) {
            $tokens = null;
            try { $tokens = Lexer::forVersion($version)->withShortOpenTag($case['shortOpenTag'])->tokenize($case['source']); }
            catch (LexerException $error) { self::assertFalse($case['lexerAccepted'], $error->getMessage()); }
            self::assertSame($case['lexerAccepted'], $tokens !== null);
            if ($tokens !== null) {
                self::assertSame($case['source'], implode('', array_map(static fn($t) => $t->lexeme, $tokens->all())));
                foreach ($case['requiredTokenTypes'] as $type) self::assertContains($type, array_map(static fn($t) => $t->type->value, $tokens->all()));
            }
            self::assertSame($case['accept' . $suffix], PhpGrammarMatcher::forRepositoryRoot(self::root())->withShortOpenTag($case['shortOpenTag'])->matches($version, $case['source'])->matched);
        }
    }

    public function testFreshExternalEvidenceKeepsCompilerAndGrammarVerdictsSeparate(): void
    {
        $report = self::read('docs/8.6/final-coverage-validation.json');
        self::assertSame([], $report['errors']);
        self::assertCount(156, $report['observations']);
        foreach ($report['inputHashes'] + $report['fixtureHashes'] as $path => $hash) self::assertSame($hash, hash_file('sha256', self::root() . '/' . $path), $path);
        $observations = [];
        foreach ($report['observations'] as $row) $observations[$row['id']][$row['version']] = $row;
        foreach (self::cases() as [$case]) foreach (['8.5' => '85', '8.6' => '86'] as $version => $suffix) {
            $row = $observations[$case['id']][$version];
            self::assertSame($case['stage'], $row['stage']);
            self::assertSame($case['structural' . $suffix], $row['structural']);
            self::assertSame($case['accept' . $suffix], $row['result']['exit'] === 0);
            if ($suffix === '86' && $case['diagnostic86'] !== null) self::assertStringContainsString($case['diagnostic86'], $row['result']['stdout'] . $row['result']['stderr']);
            if (isset($case['warning' . $suffix])) self::assertSame($case['warning' . $suffix], str_contains($row['result']['stdout'] . $row['result']['stderr'], 'Deprecated:'));
        }
        // Linking may reject a declaration even after lint successfully emitted opcodes.
        self::assertSame(0, $observations['override-trait-missing']['8.6']['lint']['exit']);
        self::assertNotSame(0, $observations['override-trait-missing']['8.6']['result']['exit']);
    }
}
