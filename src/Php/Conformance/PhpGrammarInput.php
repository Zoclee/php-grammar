<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Matching\Input;
use PhpGrammar\Php\Lexing\Token;
use PhpGrammar\Php\Lexing\TokenStream;

final readonly class PhpGrammarInput implements Input
{
    public function __construct(
        private TokenStream $tokens,
    ) {
    }

    public function length(): int
    {
        return $this->tokens->length();
    }

    public function valueAt(int $offset): string
    {
        return $this->tokenAt($offset)->lexeme;
    }

    public function tokenAt(int $offset): Token
    {
        return $this->tokens->tokenAt($offset);
    }

    public function tokenStream(): TokenStream
    {
        return $this->tokens;
    }
}
