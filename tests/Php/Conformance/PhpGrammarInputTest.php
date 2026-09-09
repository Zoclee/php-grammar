<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarInput;
use PhpGrammar\Php\Lexing\Lexer;
use PHPUnit\Framework\TestCase;

final class PhpGrammarInputTest extends TestCase
{
    public function testExposesTokenLexemesToGenericMatcher(): void
    {
        $stream = Lexer::forPhp85()->tokenize('<?php echo $value;')->withoutTrivia();
        $input = new PhpGrammarInput($stream);

        self::assertSame($stream->length(), $input->length());
        self::assertSame('<?php', $input->valueAt(0));
        self::assertSame('echo', $input->valueAt(1));
        self::assertSame('$value', $input->tokenAt(2)->lexeme);
        self::assertSame($stream, $input->tokenStream());
    }
}
