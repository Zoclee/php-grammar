<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Lexing;

use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\TokenType;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class RepositoryLexerSmokeTest extends TestCase
{
    public function testLexesCurrentPhp85FixturesWithoutInterpreterDependency(): void
    {
        $manifest = RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3));
        foreach ($manifest->packages() as $package) {
            $root = $manifest->absolutePath($package->conformanceFixturePath) . DIRECTORY_SEPARATOR . 'valid';
            $paths = glob($root . DIRECTORY_SEPARATOR . '*.php') ?: [];
            self::assertNotSame([], $paths, $package->version);

            $lexer = Lexer::forVersion($package->lexerVersion);
            foreach ($paths as $path) {
                $source = file_get_contents($path);
                self::assertIsString($source);

                $stream = $lexer->tokenize($source);
                self::assertGreaterThan(0, $stream->length(), $path);
                self::assertContains(TokenType::OpenTag, array_map(static fn ($token): TokenType => $token->type, $stream->all()), $path);
            }
        }
    }
}
