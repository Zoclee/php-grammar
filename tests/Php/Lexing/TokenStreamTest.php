<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Lexing;

use OutOfBoundsException;
use PhpGrammar\Php\Lexing\Token;
use PhpGrammar\Php\Lexing\TokenStream;
use PhpGrammar\Php\Lexing\TokenType;
use PHPUnit\Framework\TestCase;

final class TokenStreamTest extends TestCase
{
    public function testExposesTokensAsInputValues(): void
    {
        $first = new Token(TokenType::Identifier, 'a', 0, 1, 1, 1);
        $second = new Token(TokenType::Whitespace, ' ', 1, 1, 1, 2);
        $third = new Token(TokenType::Identifier, 'b', 2, 1, 1, 3);
        $stream = new TokenStream([$first, $second, $third]);

        self::assertSame(3, $stream->length());
        self::assertSame($first, $stream->valueAt(0));
        self::assertSame($third, $stream->tokenAt(2));
        self::assertSame([$first, $second, $third], $stream->all());
        self::assertSame([$first, $third], $stream->withoutTrivia()->all());
    }

    public function testRejectsOutOfBoundsOffset(): void
    {
        $this->expectException(OutOfBoundsException::class);

        (new TokenStream([]))->tokenAt(0);
    }
}
