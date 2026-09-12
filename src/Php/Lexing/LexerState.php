<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

final class LexerState
{
    public int $offset = 0;
    public int $line = 1;
    public int $column = 1;

    public function __construct(
        public readonly string $source,
    ) {
    }

    public function atEnd(int $lookahead = 0): bool
    {
        return $this->offset + $lookahead >= strlen($this->source);
    }

    public function charAt(int $lookahead): ?string
    {
        return $this->source[$this->offset + $lookahead] ?? null;
    }

    public function startsWith(string $prefix): bool
    {
        return substr($this->source, $this->offset, strlen($prefix)) === $prefix;
    }

    public function consume(int $length, TokenType $type): Token
    {
        $lexeme = substr($this->source, $this->offset, $length);
        $token = new Token($type, $lexeme, $this->offset, $length, $this->line, $this->column);

        for ($index = 0; $index < $length; $index++) {
            $char = $this->source[$this->offset + $index];
            if ($char === "\r") {
                $this->line++;
                $this->column = 1;
                continue;
            }

            if ($char === "\n") {
                if ($this->offset + $index === 0 || $this->source[$this->offset + $index - 1] !== "\r") {
                    $this->line++;
                }
                $this->column = 1;
                continue;
            }

            $this->column++;
        }

        $this->offset += $length;
        return $token;
    }

    public function error(string $message): LexerException
    {
        return new LexerException(sprintf('%s At offset %d, line %d, column %d.', $message, $this->offset, $this->line, $this->column));
    }
}
