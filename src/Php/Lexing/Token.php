<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $lexeme,
        public int $offset,
        public int $length,
        public int $line,
        public int $column,
    ) {
    }

    public function endOffset(): int
    {
        return $this->offset + $this->length;
    }

    public function isTrivia(): bool
    {
        return in_array($this->type, [TokenType::Whitespace, TokenType::Comment, TokenType::DocComment], true);
    }
}
