<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

/** Source-level string boundaries; expression fragments are checked by the EBNF adapter. */
final class StringSyntax
{
    public static function closingBrace(string $source, int $opening, bool $shortOpenTag = true): int
    {
        return Lexer::forPhp85()->withShortOpenTag($shortOpenTag)->interpolationEnd($source, $opening);
    }

    /** @return list<array{string, string}> */
    public static function fragments(string $source, bool $shortOpenTag = true): array
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
                $closing = self::closingBrace($source, $opening, $shortOpenTag);
                $fragment = substr($source, $opening + 1, $closing - $opening - 1);
                if ($pair === '${' && preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(?:\[|$)/D', $fragment)) {
                    // ST_LOOKING_FOR_VARNAME emits T_STRING_VARNAME, including keywords.
                    $fragment = '$' . $fragment;
                }
                $fragments[] = [$pair === '{$' ? 'variable-expression' : 'expression', $fragment];
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
