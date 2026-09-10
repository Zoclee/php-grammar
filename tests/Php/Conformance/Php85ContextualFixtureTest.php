<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** These examples require the documented contextual layer, beyond structural EBNF. */
final class Php85ContextualFixtureTest extends TestCase
{
    #[DataProvider('cases')]
    public function testContextualRejectionIsNotMisreportedAsAStructuralRejection(string $file): void
    {
        $result = PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))->matches('8.5', file_get_contents($file));
        self::assertTrue($result->matched, implode(', ', $result->expected));
    }

    public static function cases(): iterable
    {
        foreach (glob(dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/contextual-invalid/*.php') as $file) {
            yield basename($file) => [$file];
        }
    }
}
