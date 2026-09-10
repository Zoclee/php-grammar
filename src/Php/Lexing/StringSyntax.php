<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

/** Source-level string boundaries; expression fragments are checked by the EBNF adapter. */
final class StringSyntax
{
    public static function closingBrace(string $source, int $opening): int
    {
        $depth = 1;
        for ($i = $opening + 1, $length = strlen($source); $i < $length; $i++) {
            if (in_array($source[$i], ["'", '"', '`'], true)) {
                $quote = $source[$i++];
                for (; $i < $length && $source[$i] !== $quote; $i++) {
                    if ($source[$i] === '\\') $i++;
                }
            } elseif (substr($source, $i, 2) === '/*') {
                $end = strpos($source, '*/', $i + 2);
                if ($end === false) break;
                $i = $end + 1;
            } elseif (substr($source, $i, 2) === '//' || ($source[$i] === '#' && ($source[$i + 1] ?? '') !== '[')) {
                $i += strcspn($source, "\r\n", $i);
            } elseif ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}' && --$depth === 0) {
                return $i;
            }
        }
        throw new LexerException('Unterminated braced string interpolation.');
    }

    /** @return list<array{string, string}> */
    public static function fragments(string $source): array
    {
        $fragments = [];
        for ($i = 0, $length = strlen($source); $i < $length; $i++) {
            if ($source[$i] === '\\') {
                if (substr($source, $i, 3) === '\\u{') {
                    if (preg_match('/^\\\\u\{([0-9a-fA-F]+)\}/', substr($source, $i), $unicode) !== 1
                        || hexdec($unicode[1]) > 0x10ffff) {
                        throw new LexerException('Invalid Unicode code point escape.');
                    }
                }
                $i++;
                continue;
            }
            $pair = substr($source, $i, 2);
            if ($pair === '{$' || $pair === '${') {
                $opening = $pair === '{$' ? $i : $i + 1;
                $closing = self::closingBrace($source, $opening);
                $fragments[] = [$pair === '{$' ? 'variable-expression' : 'expression', substr($source, $opening + 1, $closing - $opening - 1)];
                $i = $closing;
            } elseif ($source[$i] === '$' && preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\[/', substr($source, $i), $variable) === 1) {
                $start = $i + strlen($variable[0]);
                $end = strpos($source, ']', $start);
                if ($end === false || preg_match('/^(?:-?(?:[0-9](?:_?[0-9])*|0[xX][0-9a-fA-F](?:_?[0-9a-fA-F])*|0[bB][01](?:_?[01])*|0[oO][0-7](?:_?[0-7])*)|\$?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)$/D', substr($source, $start, $end - $start)) !== 1) {
                    throw new LexerException('Invalid simple interpolated offset.');
                }
                $i = $end;
            }
        }
        return $fragments;
    }
}
