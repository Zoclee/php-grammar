<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Matching\Input;
use PhpGrammar\Php\Lexing\Token;
use PhpGrammar\Php\Lexing\TokenStream;
use PhpGrammar\Php\Lexing\TokenType;

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
        $token = $this->tokenAt($offset);
        if ($token->type === TokenType::Identifier && in_array(strtolower($token->lexeme), ['enum', 'from'], true)) {
            // These spellings are keyword terminals only when scanner lookahead
            // selected them. Identifier primitives still consume the original token.
            return "\0identifier:" . $token->lexeme;
        }
        if ($token->type === TokenType::Keyword && str_starts_with($token->lexeme, '__')
            && strtolower($token->lexeme) !== '__halt_compiler') {
            return strtoupper($token->lexeme);
        }
        if (in_array($token->type, [TokenType::Keyword, TokenType::Operator], true)) {
            return strtolower(preg_replace('/[ \t]+/', '', $token->lexeme));
        }
        return $token->lexeme;
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
