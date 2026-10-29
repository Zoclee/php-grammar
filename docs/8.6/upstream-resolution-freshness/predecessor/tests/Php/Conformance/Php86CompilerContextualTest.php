<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\Lexer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php86CompilerContextualTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function read(string $path): array
    {
        return json_decode(file_get_contents(self::root() . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function cases(): iterable
    {
        foreach (self::read('tests/fixtures/php/8.6/reconciliation-cases.json')['cases'] as $case) {
            yield $case['family'] . ':' . $case['id'] => [$case];
        }
    }

    #[DataProvider('cases')]
    public function testResearchCasesPreserveLexicalAndStructuralStages(array $case): void
    {
        $source = file_get_contents(self::root() . '/' . $case['fixture']);
        self::assertSame($case['sha256'], hash('sha256', $source));
        $matcher = PhpGrammarMatcher::forRepositoryRoot(self::root());
        foreach (['8.5', '8.6'] as $version) {
            $tokens = Lexer::forVersion($version)->tokenize($source);
            self::assertSame($source, implode('', array_map(static fn($token) => $token->lexeme, $tokens->all())));
            self::assertSame($case['expect'][$version]['structural'], $matcher->matches($version, $source)->matched);
        }
    }

    public function testRecordedOracleEvidenceIsFreshAndStageSpecific(): void
    {
        $report = self::read('docs/8.6/correctness-remediation-probes.json');
        self::assertSame([], $report['errors']);
        self::assertCount(162, $report['observations']);
        foreach ($report['inputHashes'] + $report['fixtureHashes'] as $path => $hash) {
            self::assertSame($hash, hash_file('sha256', self::root() . '/' . $path), $path);
        }
        $cases = array_column(self::read('tests/fixtures/php/8.6/reconciliation-cases.json')['cases'], null, 'id');
        foreach ($report['observations'] as $row) {
            $case = $cases[$row['id']];
            $expected = $case['expect'][$row['version']];
            self::assertSame($expected['structural'], $row['structural']);
            self::assertSame($expected['lint']['exit'], $row['lint']['exit']);
            self::assertSame($expected['execution']['exit'] ?? null, $row['execution']['exit'] ?? null);
            if ($row['execution'] !== null) {
                self::assertTrue($case['executeReviewed']);
                self::assertSame(0, $row['lint']['exit']);
                self::assertNotEmpty($case['executionReview']);
                if (isset($expected['execution']['contains'])) self::assertStringContainsString($expected['execution']['contains'], $row['execution']['stdout'] . $row['execution']['stderr']);
                if (isset($expected['execution']['stdout'])) self::assertSame($expected['execution']['stdout'], $row['execution']['stdout']);
            }
        }
    }

    public function testKnownDiscrepanciesCannotBecomeStructuralNegatives(): void
    {
        $cases = array_column(self::read('tests/fixtures/php/8.6/reconciliation-cases.json')['cases'], null, 'id');
        foreach (['assign-write', 'assign-property', 'assign-increment', 'assign-unset', 'assign-reference-bind'] as $id) {
            $expected = $cases[$id]['expect']['8.6'];
            self::assertTrue($expected['structural']);
            self::assertSame(0, $expected['lint']['exit']);
            self::assertSame(255, $expected['execution']['exit']);
            self::assertStringContainsString('Invalid opcode', $expected['execution']['contains']);
        }
        self::assertSame(0, $cases['reference-write']['expect']['8.6']['execution']['exit']);
        self::assertSame(255, $cases['printf-or']['expect']['8.6']['lint']['exit']);
        self::assertSame(0, $cases['printf-named']['expect']['8.6']['lint']['exit']);
        foreach (['8.5', '8.6'] as $version) {
            self::assertFalse($cases['hook-discarded']['expect'][$version]['structural']);
            self::assertSame(0, $cases['hook-discarded']['expect'][$version]['lint']['exit']);
            self::assertSame(0, $cases['autoload-invalid']['expect'][$version]['lint']['exit']);
            self::assertSame(255, $cases['autoload-invalid']['expect'][$version]['execution']['exit']);
        }
    }

    public function testGroupLedgerRetainsHistoricalHunksAndOpenDiscovery(): void
    {
        $ledger = self::read('docs/8.6/compiler-contextual-groups.json');
        $historical = self::read('docs/8.6/compiler-change-reconciliation.json');
        $hunks = array_column($historical['hunks'], null, 'id');
        self::assertCount(578, $hunks);
        self::assertSame('UNRESOLVED — BLOCKING', $ledger['finding013']);
        self::assertCount(263, $ledger['phase22PendingNotDischarged']);
        foreach ($ledger['groups'] as $group) {
            self::assertNotEmpty($group['remaining']);
            foreach ($group['historicalHunks'] as $hunk) self::assertSame($hunks[$hunk['id']], $hunk);
        }
    }
}
