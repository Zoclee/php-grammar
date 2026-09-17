<?php

declare(strict_types=1);

/** Maintenance only: consumers always read the standalone canonical EBNF. */
final class Php85ExpressionFamilies
{
    /** name, child, operator EBNF, associativity, also emit closed-yield pair */
    public const LEVELS = [
        ['logical-or', 'logical-xor', '"or"', 'left', true],
        ['logical-xor', 'logical-and', '"xor"', 'left', true],
        ['logical-and', 'print', '"and"', 'left', true],
        ['coalesce', 'boolean-or', '"??"', 'right', false],
        ['boolean-or', 'boolean-and', '"||"', 'left', false],
        ['boolean-and', 'bitwise-or', '"&&"', 'left', false],
        ['bitwise-or', 'bitwise-xor', '"|"', 'left', false],
        ['bitwise-xor', 'bitwise-and', '"^"', 'left', false],
        ['bitwise-and', 'equality', '"&"', 'left', false],
        ['equality', 'relational', '( "==" | "!=" | "===" | "!==" | "<=>" | "<>" )', 'none', false],
        ['relational', 'pipe', '( "<" | "<=" | ">" | ">=" )', 'none', false],
        ['pipe', 'concatenation', '"|>"', 'left', false],
        ['concatenation', 'shift', '"."', 'left', false],
        ['shift', 'additive', '( "<<" | ">>" )', 'left', false],
        ['additive', 'multiplicative', '( "+" | "-" )', 'left', false],
        ['multiplicative', 'boolean-not', '( "*" | "/" | "%" )', 'left', false],
        ['power', 'clone', '"**"', 'right', false],
    ];

    /** @return array<string, string> Complete productions, without trailing newline. */
    public static function productions(): array
    {
        $productions = [];
        foreach (self::LEVELS as [$name, $child, $operator, $associativity, $closedYield]) {
            foreach ($closedYield ? ['', 'closed-yield-'] : [''] as $prefix) {
                $expression = $prefix . $name . '-expression';
                $operand = $prefix . $child . '-expression';
                $context = $prefix . $name . '-prefix-context';
                $childContext = $prefix . $child . '-prefix-context';
                $tail = match ($associativity) {
                    'left' => "{ $operator , $operand }",
                    'none' => "[ $operator , $operand ]",
                    'right' => "[ $operator , $expression ]",
                };
                // A pending right-associative chain is a sequence of CHILD
                // operands, not the recursive complete expression. Preserve
                // this distinction and the non-associative child-only case.
                $pending = match ($associativity) {
                    'left' => "$expression , $operator",
                    'none' => "$operand , $operator",
                    'right' => "$operand , $operator , { $operand , $operator }",
                };
                $productions[$expression] = "$expression =\n    $operand , $tail ;";
                $productions[$context] = "$context =\n    $childContext | $pending , [ $childContext ] ;";
            }
        }
        return $productions;
    }

    /** Replace only owned productions, preserving order and surrounding bytes. */
    public static function render(string $source): string
    {
        $newline = str_contains($source, "\r\n") ? "\r\n" : "\n";
        foreach (self::productions() as $name => $production) {
            // These owned rules contain no semicolon terminal or comments.
            $pattern = '/^' . preg_quote($name, '/') . ' =\R[^;]*;/m';
            $replacement = str_replace("\n", $newline, $production);
            $source = preg_replace_callback($pattern, static fn () => $replacement, $source, -1, $count);
            if ($count !== 1) {
                throw new RuntimeException("Expected exactly one production: $name; found $count");
            }
        }
        return $source;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $path = dirname(__DIR__, 2) . '/grammar/8.5/php.ebnf';
    $source = file_get_contents($path);
    $output = Php85ExpressionFamilies::render($source);
    if (in_array('--check', $argv, true)) {
        if ($source !== $output) {
            fwrite(STDERR, "Expression families are stale. Run php tools/8.5/generate-expressions.php\n");
            exit(1);
        }
    } elseif ($source !== $output) {
        file_put_contents($path, $output);
    }
    echo count(Php85ExpressionFamilies::productions()) . " generated productions synchronized.\n";
}
