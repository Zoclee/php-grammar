<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Lexing;

use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Php\Lexing\LexerException;
use PhpGrammar\Php\Lexing\TokenType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LexerTest extends TestCase
{
    public function testTokenizesInlineHtmlAndPhpTags(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<h1>x</h1><?php echo $x; ?>tail')->all();

        self::assertSame(TokenType::InlineHtml, $tokens[0]->type);
        self::assertSame('<h1>x</h1>', $tokens[0]->lexeme);
        self::assertSame(TokenType::OpenTag, $tokens[1]->type);
        self::assertSame('<?php', $tokens[1]->lexeme);
        self::assertSame(TokenType::Keyword, $tokens[3]->type);
        self::assertSame('echo', $tokens[3]->lexeme);
        self::assertSame(TokenType::Variable, $tokens[5]->type);
        self::assertSame('$x', $tokens[5]->lexeme);
        self::assertSame(TokenType::CloseTag, $tokens[8]->type);
        self::assertSame(TokenType::InlineHtml, $tokens[9]->type);
    }

    public function testTokenizesEchoOpenTag(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?= $value ?>')->all();

        self::assertSame(TokenType::EchoOpenTag, $tokens[0]->type);
        self::assertSame(TokenType::Variable, $tokens[2]->type);
    }

    public function testTokenizesIdentifiersVariablesAndKeywords(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?php function example($value, $_x) {}')->withoutTrivia()->all();

        self::assertSame(TokenType::Keyword, $tokens[1]->type);
        self::assertSame('function', $tokens[1]->lexeme);
        self::assertSame(TokenType::Identifier, $tokens[2]->type);
        self::assertSame('example', $tokens[2]->lexeme);
        self::assertSame(TokenType::Variable, $tokens[4]->type);
        self::assertSame('$value', $tokens[4]->lexeme);
        self::assertSame(TokenType::Variable, $tokens[6]->type);
    }

    public function testTokenizesContextualFromAsIdentifierAndDieAsKeyword(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?php from die')->withoutTrivia()->all();

        self::assertSame(TokenType::Identifier, $tokens[1]->type);
        self::assertSame('from', $tokens[1]->lexeme);
        self::assertSame(TokenType::Keyword, $tokens[2]->type);
        self::assertSame('die', $tokens[2]->lexeme);
    }

    #[DataProvider('numericLiteralProvider')]
    public function testTokenizesNumericLiterals(string $source, TokenType $expectedType): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?php ' . $source)->withoutTrivia()->all();

        self::assertSame($expectedType, $tokens[1]->type);
        self::assertSame($source, $tokens[1]->lexeme);
    }

    /**
     * @return iterable<string, array{string, TokenType}>
     */
    public static function numericLiteralProvider(): iterable
    {
        yield 'decimal integer' => ['123_456', TokenType::IntegerLiteral];
        yield 'binary integer' => ['0b1010_0101', TokenType::IntegerLiteral];
        yield 'explicit octal integer' => ['0o755', TokenType::IntegerLiteral];
        yield 'hex integer' => ['0xCAFE', TokenType::IntegerLiteral];
        yield 'uppercase binary integer' => ['0B1010', TokenType::IntegerLiteral];
        yield 'uppercase explicit octal integer' => ['0O755', TokenType::IntegerLiteral];
        yield 'uppercase hex integer' => ['0XCAFE', TokenType::IntegerLiteral];
        yield 'decimal float' => ['1.25', TokenType::FloatingLiteral];
        yield 'leading-dot float' => ['.5', TokenType::FloatingLiteral];
        yield 'exponent float' => ['1e-3', TokenType::FloatingLiteral];
    }

    public function testTokenizesOperatorsAndPunctuation(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?php $x ??= $a |> trim(...);')->withoutTrivia()->all();
        $lexemes = array_map(static fn ($token): string => $token->lexeme, $tokens);
        $types = array_map(static fn ($token): TokenType => $token->type, $tokens);

        self::assertSame(['<?php', '$x', '??=', '$a', '|>', 'trim', '(', '...', ')', ';'], $lexemes);
        self::assertSame(TokenType::Operator, $types[2]);
        self::assertSame(TokenType::Operator, $types[4]);
        self::assertSame(TokenType::Punctuation, $types[6]);
        self::assertSame(TokenType::Punctuation, $types[8]);
    }

    public function testTokenizesWhitespaceCommentsAndDocComments(): void
    {
        $tokens = Lexer::forPhp85()->tokenize("<?php /** doc */\n// line\n# hash\n/* block */ echo")->all();

        self::assertContains(TokenType::Whitespace, array_map(static fn ($token): TokenType => $token->type, $tokens));
        self::assertContains(TokenType::DocComment, array_map(static fn ($token): TokenType => $token->type, $tokens));
        self::assertSame(3, count(array_filter($tokens, static fn ($token): bool => $token->type === TokenType::Comment)));
    }

    public function testLineCommentsEndBeforeCloseTag(): void
    {
        $tokens = Lexer::forPhp85()->tokenize("<?php // comment ?>tail")->all();

        self::assertSame(['<?php', ' ', '// comment ', '?>', 'tail'], array_map(static fn ($token): string => $token->lexeme, $tokens));
        self::assertSame(TokenType::CloseTag, $tokens[3]->type);
        self::assertSame(TokenType::InlineHtml, $tokens[4]->type);
    }

    public function testTokenizesCastAliasesAndParserLevelUnsetCast(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?php (integer) (double) (boolean) (binary) (unset)')->withoutTrivia()->all();

        self::assertSame(TokenType::Operator, $tokens[1]->type);
        self::assertSame(TokenType::Operator, $tokens[2]->type);
        self::assertSame(TokenType::Operator, $tokens[3]->type);
        self::assertSame(TokenType::Operator, $tokens[4]->type);
        self::assertSame(TokenType::Operator, $tokens[5]->type);
        self::assertSame('(unset)', $tokens[5]->lexeme);
    }

    public function testTokenizesRepresentativeStringForms(): void
    {
        $source = "<?php 'single\\'' \"double\\\"\"\n<<<TXT\nbody\nTXT;\n<<<'RAW'\nraw\nRAW;";
        $tokens = Lexer::forPhp85()->tokenize($source)->withoutTrivia()->all();

        self::assertSame(TokenType::StringLiteral, $tokens[1]->type);
        self::assertSame("'single\\''", $tokens[1]->lexeme);
        self::assertSame(TokenType::StringLiteral, $tokens[2]->type);
        self::assertSame(TokenType::HeredocString, $tokens[3]->type);
        self::assertSame(TokenType::Punctuation, $tokens[4]->type);
        self::assertSame(';', $tokens[4]->lexeme);
        self::assertSame(TokenType::NowdocString, $tokens[5]->type);
    }

    public function testTracksSourceOffsetsLengthsAndLines(): void
    {
        $tokens = Lexer::forPhp85()->tokenize("<?php\n  \$x")->all();

        self::assertSame(0, $tokens[0]->offset);
        self::assertSame(5, $tokens[0]->length);
        self::assertSame(1, $tokens[1]->line);
        self::assertSame(6, $tokens[1]->column);
        self::assertSame(3, $tokens[1]->endOffset() - $tokens[1]->offset);
        self::assertSame(2, $tokens[2]->line);
        self::assertSame(3, $tokens[2]->column);
        self::assertSame(8, $tokens[2]->offset);
    }

    public function testTokenizesAdjacentTokens(): void
    {
        $tokens = Lexer::forPhp85()->tokenize('<?php $a+$b;')->withoutTrivia()->all();
        $lexemes = array_map(static fn ($token): string => $token->lexeme, $tokens);

        self::assertSame(['<?php', '$a', '+', '$b', ';'], $lexemes);
    }

    #[DataProvider('malformedInputProvider')]
    public function testRejectsMalformedLexicalInput(string $source): void
    {
        $this->expectException(LexerException::class);

        Lexer::forPhp85()->tokenize($source);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedInputProvider(): iterable
    {
        yield 'unterminated single quote' => ["<?php 'unterminated"];
        yield 'unterminated double quote' => ['<?php "unterminated'];
        yield 'unterminated block comment' => ['<?php /* comment'];
        yield 'malformed heredoc label' => ["<?php <<<123\nbody\n123;"];
        yield 'unterminated heredoc' => ["<?php <<<TXT\nbody\n"];
    }
}
