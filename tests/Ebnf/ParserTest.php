<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Ebnf;

use PhpGrammar\Ebnf\Lexer;
use PhpGrammar\Ebnf\LexerException;
use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Ebnf\ParserException;
use PhpGrammar\Ebnf\SequenceNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testParsesProductionsCommentsLiteralsGroupsOptionalsAndRepetition(): void
    {
        $grammar = (new Parser())->parse(<<<'EBNF'
            (* comment *)
            source-file =
                "start" , [ item | "fallback" ] , { "," , item } ;

            item =
                "(" , name , ")" ;

            name =
                "identifier" ;
            EBNF);

        self::assertCount(3, $grammar->productions());
        self::assertTrue($grammar->hasProduction('source-file'));
        self::assertTrue($grammar->hasProduction('item'));
        self::assertTrue($grammar->hasProduction('name'));
        self::assertSame(['item'], $grammar->productionMap()['source-file']->references());
        self::assertInstanceOf(SequenceNode::class, $grammar->productionMap()['source-file']->expression);
    }

    public function testLexerUnescapesLiteralText(): void
    {
        $tokens = (new Lexer())->tokenize('source-file = "\t" , "\"" ;');

        self::assertSame('literal', $tokens[2]->type);
        self::assertSame("\t", $tokens[2]->value);
        self::assertSame('literal', $tokens[4]->type);
        self::assertSame('"', $tokens[4]->value);
    }

    #[DataProvider('malformedEbnfProvider')]
    public function testRejectsMalformedEbnf(string $source, string $exception): void
    {
        $this->expectException($exception);

        (new Parser())->parse($source);
    }

    /**
     * @return iterable<string, array{string, class-string<\Throwable>}>
     */
    public static function malformedEbnfProvider(): iterable
    {
        yield 'missing-semicolon' => ['source-file = "x"', ParserException::class];
        yield 'missing-expression' => ['source-file = ;', ParserException::class];
        yield 'trailing-alternative' => ['source-file = "x" | ;', ParserException::class];
        yield 'trailing-sequence' => ['source-file = "x" , ;', ParserException::class];
        yield 'unterminated-group' => ['source-file = ( "x" ;', ParserException::class];
        yield 'unterminated-optional' => ['source-file = [ "x" ;', ParserException::class];
        yield 'unterminated-repetition' => ['source-file = { "x" ;', ParserException::class];
        yield 'unterminated-literal' => ['source-file = "x ;', LexerException::class];
        yield 'unterminated-comment' => ['(* comment', LexerException::class];
    }
}
