<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\GrammarRepository;
use PHPUnit\Framework\TestCase;

final class Php85NegativeBoundaryLedgerTest extends TestCase
{
    public function testBoundariesHaveDistinctRepairsAndTraceableClassifications(): void
    {
        $root = dirname(__DIR__, 3);
        $data = json_decode(file_get_contents($root . '/docs/8.5/negative-boundaries.json'), true, flags: JSON_THROW_ON_ERROR);
        $grammar = (new GrammarRepository($root))->load('8.5');
        $fixtureRoot = $root . '/tests/fixtures/php/8.5/';
        $errors = $ids = $fixtures = $categories = [];
        foreach ($data['cases'] as $row) {
            $id = $row['id'];
            if (isset($ids[$id])) $errors[] = "Duplicate boundary: $id";
            $ids[$id] = true;
            if (!$grammar->hasProduction($row['production'])) $errors[] = "Unknown production: $id";
            if (!in_array($row['category'], $data['categories'], true)) $errors[] = "Unknown category: $id";
            $categories[$row['category']] = true;
            $expectedDirectory = match ($row['classification']) {
                'structural-negative' => 'invalid',
                'contextual-negative' => 'contextual-invalid',
                default => 'UNKNOWN',
            };
            if ($row['negative'] !== "$expectedDirectory/$id.php" || $row['positive'] !== "valid/$id.php") {
                $errors[] = "Classification/path disagreement: $id";
            }
            if ($row['boundary'] === '' || $row['evidence'] === '') $errors[] = "Missing rationale/source: $id";
            $contents = [];
            foreach (['negative', 'positive'] as $side) {
                $file = $row[$side];
                if (!is_file($fixtureRoot . $file)) {
                    $errors[] = "Missing fixture: $file";
                    continue;
                }
                $fixtures[] = $file;
                $contents[$side] = file_get_contents($fixtureRoot . $file);
            }
            if (count($contents) === 2 && $contents['negative'] === $contents['positive']) $errors[] = "Identical pair: $id";
        }
        self::assertSame([], $errors);
        self::assertEqualsCanonicalizing($data['categories'], array_keys($categories));
        $actual = [];
        foreach (['valid', 'invalid', 'contextual-invalid'] as $kind) {
            foreach (glob($fixtureRoot . $kind . '/phase4-*.php') as $file) $actual[] = $kind . '/' . basename($file);
        }
        self::assertEqualsCanonicalizing($actual, $fixtures, 'Every Phase 4 fixture must have a ledger entry, without duplicates.');
    }

    public function testEveryContextualNegativeAndKnownDiscrepancyHasAReview(): void
    {
        $root = dirname(__DIR__, 3);
        $data = json_decode(file_get_contents($root . '/docs/8.5/negative-boundaries.json'), true, flags: JSON_THROW_ON_ERROR);
        $fixtureRoot = $root . '/tests/fixtures/php/8.5/';
        $actual = [];
        foreach (['', 'short-tags-disabled/'] as $profile) {
            foreach (glob($fixtureRoot . $profile . 'contextual-invalid/*.php') as $file) {
                $actual[] = $profile . 'contextual-invalid/' . basename($file);
            }
        }
        self::assertEqualsCanonicalizing($actual, array_column($data['contextual_review'], 'fixture'));
        foreach ($data['contextual_review'] as $row) {
            self::assertNotEmpty($row['reason']);
            self::assertNotEmpty($row['evidence']);
        }
        $known = array_map(static fn (string $file): string => 'known-discrepancies/valid/' . basename($file), glob($fixtureRoot . 'known-discrepancies/valid/*.php'));
        self::assertEqualsCanonicalizing($known, array_column($data['known_discrepancies'], 'fixture'));
        foreach ($data['known_discrepancies'] as $row) {
            self::assertNotEmpty($row['disposition']);
            self::assertNotEmpty($row['owner']);
            self::assertNotEmpty($row['evidence']);
        }
    }

    public function testGeneratedReportMatchesLedgerAndFixtureBytes(): void
    {
        $root = dirname(__DIR__, 3);
        $process = proc_open([PHP_BINARY, $root . '/tools/php85-negative-report.php', '--check'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $output);
    }

    public function testPhase3BacklogRetainsHistoricalWitnesses(): void
    {
        $path = dirname(__DIR__, 3) . '/docs/8.5/phase3-coverage.json';
        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $phase4Witnesses = array_filter($data['baseline_backlog'],
            static fn (array $row): bool => preg_match('/^fixture:(phase[456]-|boundary-parameter-never-)/', $row['evidence'] ?? '') === 1);
        self::assertSame([], $phase4Witnesses, 'Current repairs must not rewrite historical Phase 3 evidence.');
    }
}
