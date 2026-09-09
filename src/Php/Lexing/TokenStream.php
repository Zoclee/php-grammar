<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

use PhpGrammar\Ebnf\Matching\Input;

final readonly class TokenStream implements Input
{
    /**
     * @param list<Token> $tokens
     */
    public function __construct(
        private array $tokens,
    ) {
    }

    public function length(): int
    {
        return count($this->tokens);
    }

    public function valueAt(int $offset): Token
    {
        return $this->tokenAt($offset);
    }

    public function tokenAt(int $offset): Token
    {
        if (!array_key_exists($offset, $this->tokens)) {
            throw new \OutOfBoundsException(sprintf('Token offset %d is outside the token stream.', $offset));
        }

        return $this->tokens[$offset];
    }

    /**
     * @return list<Token>
     */
    public function all(): array
    {
        return $this->tokens;
    }

    public function withoutTrivia(): self
    {
        return new self(array_values(array_filter(
            $this->tokens,
            static fn (Token $token): bool => !$token->isTrivia(),
        )));
    }
}
