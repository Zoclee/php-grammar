<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpConformanceFixtureTest extends TestCase
{
    #[DataProvider('validFixtureProvider')]
    public function testPhp85ValidFixturesMatchCanonicalGrammar(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $result = self::matcher()->matches('8.5', $source);

        self::assertTrue(
            $result->matched,
            $path . "\nfurthest token offset: " . $result->furthestOffset . "\nexpected: " . implode(', ', $result->expected),
        );
    }

    #[DataProvider('invalidFixtureProvider')]
    public function testPhp85InvalidFixturesDoNotMatchCanonicalGrammar(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $result = self::matcher()->matches('8.5', $source);

        self::assertFalse($result->matched, $path);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validFixtureProvider(): iterable
    {
        yield from self::fixtures('valid');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidFixtureProvider(): iterable
    {
        yield from self::fixtures('invalid');
    }

    private static function matcher(): PhpGrammarMatcher
    {
        return PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3));
    }

    /**
     * @return iterable<string, array{string}>
     */
    private static function fixtures(string $kind): iterable
    {
        $root = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . '8.5' . DIRECTORY_SEPARATOR . $kind;

        foreach (glob($root . DIRECTORY_SEPARATOR . '*.php') ?: [] as $path) {
            yield basename($path) => [$path];
        }
    }
}
