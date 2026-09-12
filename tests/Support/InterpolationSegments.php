<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Support;

use PhpGrammar\Php\Lexing\StringSyntax;

/** Audit-only view; deliberately supports unindented, ASCII, unescaped bodies.
 * Unsupported escape/dedent semantics fail explicitly, never silently normalize.
 */
final class InterpolationSegments
{
    public static function split(string $body): array
    {
        $segments = [];
        $literal = '';
        $flush = static function () use (&$segments, &$literal): void {
            if ($literal !== '') $segments[] = ['text', $literal];
            $literal = '';
        };
        for ($i = 0; $i < strlen($body);) {
            if ($body[$i] === '\\') throw new \LogicException('Escapes outside audit binding subset');
            $pair = substr($body, $i, 2);
            if ($pair === '{$' || $pair === '${') {
                $flush();
                $end = StringSyntax::closingBrace($body, $i + ($pair === '${' ? 1 : 0));
                $raw = substr($body, $i, $end - $i + 1);
                [[$rule, $expression]] = StringSyntax::fragments($raw);
                // ${name} has a scanner varname; ${expression} has variable-variable binding.
                if ($pair === '${' && !preg_match('/^\$\{[a-zA-Z_][a-zA-Z_0-9]*(?:\[|\})/', $raw)) {
                    $expression = '${' . $expression . '}';
                }
                $segments[] = ['expression', $expression];
                $i = $end + 1;
            } elseif (preg_match('/^\$[a-zA-Z_][a-zA-Z_0-9]*/', substr($body, $i), $m)) {
                $flush();
                $expression = $m[0];
                $i += strlen($m[0]);
                if (($body[$i] ?? '') === '[') {
                    $end = strpos($body, ']', $i);
                    if ($end === false) throw new \LogicException('Unclosed offset');
                    $offset = substr($body, $i + 1, $end - $i - 1);
                    // ST_VAR_OFFSET bare labels are string keys, not constant fetches.
                    if (preg_match('/^[a-zA-Z_][a-zA-Z_0-9]*$/D', $offset)) $offset = var_export($offset, true);
                    $expression .= '[' . $offset . ']';
                    $i = $end + 1;
                } elseif (preg_match('/^(?:\?->|->)[a-zA-Z_][a-zA-Z_0-9]*/', substr($body, $i), $m)) {
                    $expression .= $m[0];
                    $i += strlen($m[0]);
                }
                $segments[] = ['expression', $expression];
            } else {
                $literal .= $body[$i++];
            }
        }
        $flush();
        return $segments;
    }
}
