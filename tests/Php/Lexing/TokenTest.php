<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Lexing;

use PhpGrammar\Php\Lexing\Token;
use PhpGrammar\Php\Lexing\TokenType;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    public function testTokenRetainsLexemeLocationAndCategory(): void
    {
        $token = new Token(TokenType::Identifier, 'name', 5, 4, 2, 3);

        self::assertSame(TokenType::Identifier, $token->type);
        self::assertSame('name', $token->lexeme);
        self::assertSame(5, $token->offset);
        self::assertSame(4, $token->length);
        self::assertSame(9, $token->endOffset());
        self::assertFalse($token->isTrivia());
    }

    public function testTriviaClassification(): void
    {
        self::assertTrue((new Token(TokenType::Whitespace, ' ', 0, 1, 1, 1))->isTrivia());
        self::assertTrue((new Token(TokenType::Comment, '// x', 0, 4, 1, 1))->isTrivia());
        self::assertTrue((new Token(TokenType::DocComment, '/** x */', 0, 8, 1, 1))->isTrivia());
    }
}
