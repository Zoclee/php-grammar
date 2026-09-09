<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Lexing;

use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\TokenType;
use PHPUnit\Framework\TestCase;

final class RepositoryLexerSmokeTest extends TestCase
{
    public function testLexesCurrentPhp85FixturesWithoutInterpreterDependency(): void
    {
        $root = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . '8.5';
        $paths = glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php') ?: [];
        self::assertNotSame([], $paths);

        $lexer = Lexer::forPhp85();
        foreach ($paths as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            $stream = $lexer->tokenize($source);
            self::assertGreaterThan(0, $stream->length(), $path);
            self::assertContains(TokenType::OpenTag, array_map(static fn ($token): TokenType => $token->type, $stream->all()), $path);
        }
    }
}
