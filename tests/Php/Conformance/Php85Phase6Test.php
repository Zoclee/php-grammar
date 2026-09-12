<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Tests\Support\{DerivationForest, Php85Phase6Cases};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85Phase6Test extends TestCase
{
    #[DataProvider('ambiguityCases')]
    public function testDerivationRegression(string $entry, string $source): void
    {
        $grammar = (new Parser())->parse(file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.ebnf'));
        $trees = (new DerivationForest($grammar, $source))->trees($entry);
        $helperOverlap = $entry === 'class-member' && str_starts_with($source, 'public int $x');
        self::assertCount($helperOverlap ? 2 : 1, $trees);
    }

    public static function ambiguityCases(): iterable { yield from Php85Phase6Cases::ambiguity(); }

    #[DataProvider('sourceCases')]
    public function testSourceBoundaries(string $source, bool $expected): void
    {
        self::assertSame($expected, PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))->matches('8.5', $source)->matched);
    }

    public static function sourceCases(): iterable
    {
        foreach (Php85Phase6Cases::recursive() as [$id, $source, $valid]) yield $id => [$source, $valid];
        // Folding negatives are still parser-valid; compiler outcomes are
        // checked independently by the PHP 8.5 differential matrix.
        foreach (Php85Phase6Cases::folding() as [$id, $source]) yield $id => [$source, true];
        foreach (Php85Phase6Cases::malformed() as [$id, $source]) yield $id => [$source, false];
    }

    public function testEvidenceIsFreshAndEveryParserProductionIsMapped(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['phase6-matrices.json', 'phase6-boundary-folding.json', 'phase6-evidence.json', 'phase6-reconciliation.json'] as $file) {
            $data = json_decode(file_get_contents($root . '/docs/8.5/' . $file), true, flags: JSON_THROW_ON_ERROR);
            foreach ($data['hashes'] as $path => $hash) self::assertSame($hash, hash_file('sha256', $root . '/' . $path), $path);
            if (isset($data['failures'])) self::assertSame([], $data['failures']);
        }
        $inventory = json_decode(file_get_contents($root . '/docs/8.5/source-inventory.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(array_column($inventory['files']['zend_language_parser.y']['entries'], 'name'), array_column($data['reconciliation'], 'zend'));
        $grammar = (new Parser())->parse(file_get_contents($root . '/grammar/8.5/php.ebnf'));
        foreach ($data['reconciliation'] as $row) {
            foreach ($row['ebnf'] as $anchor) self::assertTrue($grammar->hasProduction($anchor), $anchor);
            foreach ($row['alternatives'] as $alternative) {
                if (!in_array($alternative['layer'], ['internal-state', 'error-only'], true)) self::assertNotEmpty($alternative['ebnf'], $alternative['id']);
            }
        }
    }
}
