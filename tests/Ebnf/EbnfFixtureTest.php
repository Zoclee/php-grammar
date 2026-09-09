<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf;

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\LexerException;
use PhpGrammar\Ebnf\ParserException;
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

final class EbnfFixtureTest extends TestCase
{
    #[DataProvider('validFixtureProvider')]
    public function testValidEbnfFixturesParseAndValidate(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $grammar = (new Parser())->parse($source);
        $result = (new GrammarValidator(
            requiredRoot: 'source-file',
            reachabilityRoots: ['source-file'],
        ))->validate($grammar);

        self::assertTrue($result->isValid(), implode(', ', $result->codes()));
    }

    #[DataProvider('malformedFixtureProvider')]
    public function testMalformedEbnfFixturesFailToParse(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        try {
            (new Parser())->parse($source);
        } catch (Throwable $exception) {
            self::assertContains($exception::class, [LexerException::class, ParserException::class]);
            return;
        }

        self::fail(sprintf('Expected malformed EBNF fixture to fail parsing: %s', $path));
    }

    #[DataProvider('invalidFixtureProvider')]
    public function testStructurallyInvalidEbnfFixturesFailValidation(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        $grammar = (new Parser())->parse($source);
        $result = (new GrammarValidator(
            requiredRoot: 'source-file',
            reachabilityRoots: ['source-file'],
        ))->validate($grammar);

        self::assertFalse($result->isValid(), sprintf('Expected validation errors for %s.', $path));
    }

    public static function validFixtureProvider(): iterable
    {
        yield from self::fixtureProvider('valid');
    }

    public static function malformedFixtureProvider(): iterable
    {
        yield from self::fixtureProvider('malformed');
    }

    public static function invalidFixtureProvider(): iterable
    {
        yield from self::fixtureProvider('invalid');
    }

    private static function fixtureProvider(string $kind): iterable
    {
        $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'ebnf' . DIRECTORY_SEPARATOR . $kind;
        foreach (glob($root . DIRECTORY_SEPARATOR . '*.ebnf') ?: [] as $path) {
            yield basename($path) => [$path];
        }
    }
}
