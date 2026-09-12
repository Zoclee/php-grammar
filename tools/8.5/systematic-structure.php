<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Tests\Support\DerivationForest;

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5 || !extension_loaded('ast')) {
    fwrite(STDERR, "Run with PHP 8.5 and ext-ast.\n");
    exit(2);
}
$root = dirname(__DIR__, 2);
$grammar = (new Parser())->parse(file_get_contents($root . '/grammar/8.5/php.ebnf'));
$schema = max(ast\get_supported_versions());
error_reporting(E_ALL & ~E_DEPRECATED);
function structure(mixed $node): mixed {
    if (!$node instanceof ast\Node) return $node;
    $children = [];
    foreach ($node->children as $key => $child) {
        if ($key !== '__declId') $children[$key] = structure($child);
    }
    $flags = $node->kind === ast\AST_CONDITIONAL ? $node->flags & ~ast\flags\PARENTHESIZED_CONDITIONAL : $node->flags;
    return [ast\get_kind_name($node->kind), $flags, $children];
}
// Every spelling at each Bison expression precedence level, including all
// compound assignments. Variable operands keep parser-valid assignment targets.
$binary = explode(' ', 'or xor and = += -= *= /= %= .= &= |= ^= <<= >>= **= ??= ?? || && | ^ & == != <> === !== <=> < <= > >= |> . << >> + - * / % ** instanceof');
$prefix = ['throw ', 'fn() => ', 'include ', 'include_once ', 'require ', 'require_once ', 'print ', 'yield ', 'yield $k => ', 'yield from ', '!', '+', '-', '~', '@', '(int) ', '(integer) ', '(float) ', '(double) ', '(string) ', '(binary) ', '(array) ', '(object) ', '(bool) ', '(boolean) ', '(unset) ', 'clone ', '++', '--'];
$cases = [];
$add = static function (string $source, string $family = 'expression', string $root = 'expression') use (&$cases): void {
    $cases[$root . ':' . $source] = [$source, $family, $root];
};
foreach ($binary as $a) foreach ($binary as $b) $add('$a ' . $a . ' $b ' . $b . ' $c', 'binary-pair');
foreach ($prefix as $p) {
    foreach ($binary as $b) {
        $add($p . '$a ' . $b . ' $b', 'prefix-binary');
        $add('$a ' . $b . ' ' . $p . '$b', 'binary-prefix');
        $add('$a ' . $b . ' ' . $p . '$b ' . $b . ' $c', 'pending-prefix');
    }
    foreach ($prefix as $q) $add($p . $q . '$a', 'prefix-pair');
    foreach (['$a ? $b : $c', '$a ?: $b', '$a++', '$a--'] as $tail) $add($p . $tail, 'prefix-ternary-postfix');
    $add('$a ? ' . $p . '$b : $c', 'ternary-middle');
    $add('$a ? $b : ' . $p . '$c', 'ternary-right');
    $add('$a instanceof $b ** ' . $p . '$c', 'instanceof-power-prefix');
    $add('yield ' . $p . '$a => $b', 'yield-key-prefix');
    $add('yield $a => ' . $p . '$b', 'yield-value-prefix');
    $add('yield yield ' . $p . '$a => $b => $c', 'nested-yield-key');
    $add($p . '$a = &$b', 'reference-assignment-prefix');
}
foreach ($binary as $b) {
    $add('$a ' . $b . ' $b = &$c', 'reference-assignment-binary');
    $add('$a ' . $b . ' $b ' . $b . ' $c ' . $b . ' $d', 'binary-chain');
    foreach (['$a ? $b : $c', '$a ?: $b', '$a++', '$a--'] as $head) {
        $add($head . ' ' . $b . ' $d', 'ternary-postfix-binary');
        $add('$d ' . $b . ' ' . $head, 'binary-ternary-postfix');
    }
}
foreach (['!$x instanceof Foo', '-2 ** 2', '2 ** -2 ** 2', '$x = print $y = 1', 'throw $a ?? $b', 'yield yield 1 => 2', 'yield 1 => yield 2', 'yield yield 1 => 2 => 3', '[, $a] = $b', 'list($a,,$b) = $c', 'clone($a)', 'clone($a,)', 'clone($a, $b)'] as $source) $add($source, 'regression');
foreach (['$a()', '$a->b()', '$a?->b()', '$a[0]', '($a)()', 'new C()', 'clone($a, $b)', 'static function() { return 1; }', 'fn() => $a'] as $operand) {
    foreach ($binary as $operator) {
        $add($operand . ' ' . $operator . ' $b', 'phase6-call-left');
        $add('$b ' . $operator . ' ' . $operand, 'phase6-call-right');
    }
}
$wrappers = [
    'if ($a) %s', 'if ($a) ; elseif ($b) %s', 'if ($a) ; else %s',
    'while ($a) %s', 'for (;;) %s', 'foreach ($a as $b) %s', 'declare(ticks=1) %s',
    'do %s while ($a);', '{ %s }', 'if ($a): %s endif;',
    'while ($a): %s endwhile;', 'for (;;): %s endfor;',
    'foreach ($a as $b): %s endforeach;', 'declare(ticks=1): %s enddeclare;',
    'switch ($a) { case 1: %s default: ; }',
    'switch ($a): case 1: %s endswitch;',
    'try { %s } catch (E $e) {}',
    'try {} catch (E $e) { %s } finally {}',
    'try {} finally { %s }',
];
foreach ($wrappers as $outer) foreach ($wrappers as $inner) {
    foreach ([';', 'if ($c) ;', 'if ($c) ; else ;', '{ function f() {} }', '{ ?>html<?php }'] as $body) {
        $source = sprintf($outer, sprintf($inner, $body));
        $add($source, 'statement-pair', 'statement');
        $add('if ($d) ' . $source . ' else ;', 'else-binding', 'statement');
        $add('if ($d): ' . $source . ' else: ; endif;', 'alternative-else', 'statement');
        $add('if ($d): ' . $source . ' elseif ($e): ; endif;', 'alternative-elseif', 'statement');
    }
}
// Only complete precedence expressions are parenthesized. Context productions
// are pending operations, and variable target productions cannot be wrapped.
$layers = array_fill_keys(explode(' ', 'expression throw-expression arrow-expression include-expression logical-or-expression logical-xor-expression logical-and-expression print-expression yield-expression yield-key-expression yield-from-expression assignment-expression conditional-expression coalesce-expression boolean-or-expression boolean-and-expression bitwise-or-expression bitwise-xor-expression bitwise-and-expression equality-expression relational-expression pipe-expression concatenation-expression shift-expression additive-expression multiplicative-expression boolean-not-expression instanceof-expression unary-expression cast-expression power-expression clone-expression'), true);
$leftFolds = array_fill_keys(explode(' ', 'logical-or-expression logical-xor-expression logical-and-expression conditional-expression boolean-or-expression boolean-and-expression bitwise-or-expression bitwise-xor-expression bitwise-and-expression pipe-expression concatenation-expression shift-expression additive-expression multiplicative-expression instanceof-expression'), true);
$counts = $failures = [];
$outcomes = ['zend_accepted' => 0, 'zend_rejected' => 0, 'unique_derivations' => 0, 'ast_comparisons' => 0];
foreach ($cases as [$source, $family, $entry]) {
    $counts[$family] = ($counts[$family] ?? 0) + 1;
    $suffix = $entry === 'expression' ? ';' : '';
    try { $zend = structure(ast\parse_code('<?php ' . $source . $suffix, $schema)); }
    catch (ParseError) { $zend = null; }
    $outcomes[$zend === null ? 'zend_rejected' : 'zend_accepted']++;
    $trees = (new DerivationForest($grammar, $source))->trees($entry);
    if (count($trees) !== ($zend === null ? 0 : 1)) {
        $failures[] = compact('source', 'family') + ['problem' => 'acceptance/uniqueness', 'zend' => $zend !== null, 'trees' => count($trees)];
        continue;
    }
    if ($zend === null) continue;
    $outcomes['unique_derivations']++;
    $tokens = array_map(static fn ($t) => is_array($t) ? ($t[0] === T_INLINE_HTML ? 'echo ' . var_export($t[1], true) . ';' : $t[1]) : $t, DerivationForest::tokens($source));
    $spans = [];
    $walk = function (array $tree) use (&$walk, &$spans, $layers, $leftFolds, $entry): void {
        if (($entry === 'expression' && isset($layers[$tree['name']]) && $tree['end'] - $tree['start'] > 1)
            || ($entry === 'statement' && $tree['end'] - $tree['start'] > 1 && in_array($tree['name'], ['statement', 'matched-statement', 'unmatched-statement'], true))) {
            $spans[$tree['start'] . ':' . $tree['end']] = [$tree['start'], $tree['end']];
        }
        if ($entry === 'expression' && isset($leftFolds[$tree['name']])) {
            $children = [];
            $flatten = function (array $node) use (&$flatten, &$children): void {
                foreach ($node['children'] as $child) {
                    if (str_starts_with($child['name'], '@')) $flatten($child);
                    else $children[] = $child;
                }
            };
            $flatten($tree);
            foreach ($children as $child) {
                // The ternary middle expression is not a completed left fold.
                if ($tree['name'] === 'conditional-expression' && $child['name'] === 'expression') continue;
                if ($child['end'] - $tree['start'] > 1) $spans[$tree['start'] . ':' . $child['end']] = [$tree['start'], $child['end']];
            }
        }
        if ($entry === 'expression' && isset($layers[$tree['name']])) {
            $contexts = function (array $node) use (&$contexts, &$spans, $tree): void {
                foreach ($node['children'] as $child) {
                    if (str_starts_with($child['name'], '@')) $contexts($child);
                    elseif (str_ends_with($child['name'], '-prefix-context') && $child['end'] < $tree['end']) {
                        $spans[$child['end'] . ':' . $tree['end']] = [$child['end'], $tree['end']];
                    }
                }
            };
            $contexts($tree);
        }
        foreach ($tree['children'] as $child) $walk($child);
    };
    $walk($trees[0]);
    // A single witness forces every complete EBNF operand/body boundary at
    // once. Zend must preserve the entire AST, not merely accept the witness.
    $opens = $closes = [];
    foreach ($spans as [$start, $end]) { $opens[$start] = ($opens[$start] ?? 0) + 1; $closes[$end] = ($closes[$end] ?? 0) + 1; }
    $witness = '';
    for ($i = 0; $i <= count($tokens); $i++) {
        $witness .= str_repeat($entry === 'expression' ? ')' : '}', $closes[$i] ?? 0);
        $witness .= str_repeat($entry === 'expression' ? '(' : '{', $opens[$i] ?? 0);
        if (isset($tokens[$i])) $witness .= $tokens[$i] . ' ';
    }
    try { $forced = structure(ast\parse_code('<?php ' . $witness . $suffix, $schema)); }
    catch (ParseError $e) { $forced = ['error' => $e->getMessage()]; }
    $outcomes['ast_comparisons']++;
    if ($forced !== $zend) $failures[] = compact('source', 'family', 'witness') + ['problem' => 'operand/body structure'];
    if (array_sum($counts) % 500 === 0) echo array_sum($counts) . " cases\n";
}
$report = ['php' => PHP_VERSION, 'ast' => phpversion('ast'), 'schema' => $schema,
    'grammar_sha256' => hash_file('sha256', $root . '/grammar/8.5/php.ebnf'),
    'generator_sha256' => hash_file('sha256', __FILE__),
    'forest_sha256' => hash_file('sha256', $root . '/tests/Support/DerivationForest.php'),
    'families' => $counts, 'total' => array_sum($counts), 'outcomes' => $outcomes, 'failures' => $failures,
    'limits' => 'Exhaustive within the generated finite pairwise/nested matrix; operand/body spans, implicit left folds and derivation counts. Not a formal proof for arbitrary nesting depth.'];
file_put_contents($root . '/docs/8.5/systematic-structure.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode(['total' => $report['total'], 'failures' => count($failures)], JSON_PRETTY_PRINT) . "\n";
exit($failures ? 1 : 0);
