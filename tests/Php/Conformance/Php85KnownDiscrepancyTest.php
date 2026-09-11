<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Known PHP-valid inputs still rejected structurally; never count these as conformance passes. */
final class Php85KnownDiscrepancyTest extends TestCase
{
    #[DataProvider('cases')]
    public function testRecordedStructuralGapHasNotChangedWithoutReclassification(string $file): void
    {
        self::assertFalse(PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))
            ->matches('8.5', file_get_contents($file))->matched,
            'If corrected, move this PHP-valid witness into the ordinary valid corpus and remove the discrepancy.');
    }

    public static function cases(): iterable
    {
        foreach (glob(dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/known-discrepancies/valid/*.php') as $file) {
            yield basename($file) => [$file];
        }
    }
}
