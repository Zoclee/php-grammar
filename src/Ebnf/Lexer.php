<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final class Lexer
{
    /** @return list<Token> */
    public function tokenize(string $source): array
    {
        $tokens = [];
        $length = strlen($source);
        $offset = 0;
        $line = 1;
        $column = 1;

        while ($offset < $length) {
            $char = $source[$offset];

            if ($char === "\r" || $char === "\n" || $char === "\t" || $char === ' ' || $char === "\f" || $char === "\v") {
                $this->advance($source, $offset, $line, $column);
                continue;
            }

            if (substr($source, $offset, 2) === '(*') {
                $this->consumeComment($source, $offset, $line, $column);
                continue;
            }

            if (preg_match('/[A-Za-z_]/', $char) === 1) {
                $startLine = $line;
                $startColumn = $column;
                $value = '';
                while ($offset < $length && preg_match('/[A-Za-z0-9_-]/', $source[$offset]) === 1) {
                    $value .= $source[$offset];
                    $this->advance($source, $offset, $line, $column);
                }

                $tokens[] = new Token('identifier', $value, $startLine, $startColumn);
                continue;
            }

            if ($char === '"') {
                $tokens[] = $this->consumeLiteral($source, $offset, $line, $column);
                continue;
            }

            if (str_contains('=;|,[]{}()', $char)) {
                $tokens[] = new Token($char, $char, $line, $column);
                $this->advance($source, $offset, $line, $column);
                continue;
            }

            throw new LexerException(sprintf(
                'Unexpected character %s at line %d, column %d.',
                var_export($char, true),
                $line,
                $column,
            ));
        }

        $tokens[] = new Token('eof', '', $line, $column);
        return $tokens;
    }

    private function consumeComment(string $source, int &$offset, int &$line, int &$column): void
    {
        $startLine = $line;
        $startColumn = $column;
        $this->advance($source, $offset, $line, $column);
        $this->advance($source, $offset, $line, $column);

        while ($offset < strlen($source)) {
            if (substr($source, $offset, 2) === '*)') {
                $this->advance($source, $offset, $line, $column);
                $this->advance($source, $offset, $line, $column);
                return;
            }

            $this->advance($source, $offset, $line, $column);
        }

        throw new LexerException(sprintf(
            'Unterminated comment starting at line %d, column %d.',
            $startLine,
            $startColumn,
        ));
    }

    private function consumeLiteral(string $source, int &$offset, int &$line, int &$column): Token
    {
        $startLine = $line;
        $startColumn = $column;
        $value = '';
        $this->advance($source, $offset, $line, $column);

        while ($offset < strlen($source)) {
            $char = $source[$offset];

            if ($char === '"') {
                $this->advance($source, $offset, $line, $column);
                return new Token('literal', $value, $startLine, $startColumn);
            }

            if ($char === '\\') {
                $this->advance($source, $offset, $line, $column);
                if ($offset >= strlen($source)) {
                    break;
                }

                $value .= $this->unescape($source[$offset]);
                $this->advance($source, $offset, $line, $column);
                continue;
            }

            $value .= $char;
            $this->advance($source, $offset, $line, $column);
        }

        throw new LexerException(sprintf(
            'Unterminated literal starting at line %d, column %d.',
            $startLine,
            $startColumn,
        ));
    }

    private function unescape(string $char): string
    {
        return match ($char) {
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'f' => "\f",
            'v' => "\v",
            default => $char,
        };
    }

    private function advance(string $source, int &$offset, int &$line, int &$column): void
    {
        $char = $source[$offset];
        $offset++;

        if ($char === "\r") {
            if (($source[$offset] ?? null) === "\n") {
                $offset++;
            }
            $line++;
            $column = 1;
            return;
        }

        if ($char === "\n") {
            $line++;
            $column = 1;
            return;
        }

        $column++;
    }
}
