<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpConformanceFixtureTest extends TestCase
{
    #[DataProvider('validFixtureProvider')]
    public function testValidFixturesMatchCanonicalGrammar(string $version, string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $result = self::matcher()->matches($version, $source);

        self::assertTrue(
            $result->matched,
            $path . "\nfurthest token offset: " . $result->furthestOffset . "\nexpected: " . implode(', ', $result->expected),
        );
    }

    #[DataProvider('invalidFixtureProvider')]
    public function testInvalidFixturesDoNotMatchCanonicalGrammar(string $version, string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $result = self::matcher()->matches($version, $source);

        self::assertFalse($result->matched, $path);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validFixtureProvider(): iterable
    {
        yield from self::fixtures('valid');
    }

    /**
     * @return iterable<string, array{string, string}>
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
        $manifest = RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3));
        foreach ($manifest->packages() as $package) {
            $root = $manifest->absolutePath($package->conformanceFixturePath) . DIRECTORY_SEPARATOR . $kind;

            foreach (glob($root . DIRECTORY_SEPARATOR . '*.php') ?: [] as $path) {
                yield $package->version . '/' . basename($path) => [$package->version, $path];
            }
        }
    }
}
