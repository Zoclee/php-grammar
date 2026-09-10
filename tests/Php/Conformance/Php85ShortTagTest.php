<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php85ShortTagTest extends TestCase
{
    #[DataProvider('fixtures')]
    public function testDisabledShortTagProfile(string $file, bool $expected): void
    {
        $result = PhpGrammarMatcher::forRepositoryRoot(dirname(__DIR__, 3))->withShortOpenTag(false)
            ->matches('8.5', file_get_contents($file));
        self::assertSame($expected, $result->matched, implode(', ', $result->expected));
    }

    public static function fixtures(): iterable
    {
        foreach (['valid' => true, 'invalid' => false] as $directory => $expected) {
            foreach (glob(dirname(__DIR__, 3) . '/tests/fixtures/php/8.5/short-tags-disabled/' . $directory . '/*.php') as $file) {
                yield $directory . '/' . basename($file) => [$file, $expected];
            }
        }
    }
}
