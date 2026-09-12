<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Tests\Support\{DerivationForest, InterpolationSegments};

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5 || !extension_loaded('ast')) {
    fwrite(STDERR, "Run with PHP 8.5 and ext-ast.\n"); exit(2);
}
error_reporting(E_ALL & ~E_DEPRECATED);
$root = dirname(__DIR__, 2);
$grammar = (new Parser())->parse(file_get_contents($root . '/grammar/8.5/php.ebnf'));
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
$schema = max(ast\get_supported_versions());
function bindingNormalize(mixed $node): mixed {
    if (!$node instanceof ast\Node) return $node;
    // Stable literal value: encaps_var_offset creates a negative integer
    // directly, ordinary expression parsing retains unary minus on an integer.
    if ($node->kind === ast\AST_UNARY_OP && $node->flags === ast\flags\UNARY_MINUS
        && is_int($node->children['expr'])) return -$node->children['expr'];
    $children = [];
    foreach ($node->children as $key => $child) if ($key !== '__declId') $children[$key] = bindingNormalize($child);
    // VAR/DIM flags here only distinguish deprecated ${} surface spellings.
    $flags = in_array($node->kind, [ast\AST_VAR, ast\AST_DIM], true) ? 0 : $node->flags;
    return [ast\get_kind_name($node->kind), $flags, $children];
}
function bindingExpression(string $source): mixed {
    return ast\parse_code('<?php ' . $source . ';', max(ast\get_supported_versions()))->children[0];
}
// Literal/segment adjacency, simple versus complex offsets/property access,
// dynamic properties, nested braces, variable variables and arithmetic binding.
$bodies = [
    '$a', 'left $a right', '$a$b', '$a $b {$c}',
    '$a[0]', '$a[-1]', '$a[key]', '$a[$i]', '$a->p', '$a?->p',
    '{$a[0]}', '{$a[$i + $j * 2]}', '{$a->p}', '{$a?->p}',
    '{$a->{$b}}', '{$a->{$b + $c * 2}}', '{$a[${$b}]}',
    '${name}', '${name[0]}', '${$a + $b * 2}',
    'left {$a[$i + $j * 2]} middle $b->p end {$c}',
    '{$a[1 + 2 * 3]}{$b[($i + $j) * 2]}',
];
$rows = $failures = [];
$forestCount = 0;
foreach (['quoted', 'heredoc'] as $style) foreach ($bodies as $index => $body) {
    $source = $style === 'quoted' ? '"' . $body . '"' : "<<<TEXT\n" . $body . "\nTEXT";
    $segments = InterpolationSegments::split($body);
    $expected = [];
    $unforced = [];
    foreach ($segments as [$kind, $fragment]) {
        if ($kind === 'text') { $expected[] = $fragment; continue; }
        $expected[] = bindingNormalize(bindingExpression($fragment));
        // The existing forest does not adapt quoted literal primitives. These
        // segments still have boundary comparisons, but no EBNF operand proof.
        if (str_contains($fragment, "'")) { $unforced[] = $fragment; continue; }
        $trees = (new DerivationForest($grammar, $fragment))->trees('expression');
        if (count($trees) !== 1) { $failures[] = ['fragment' => $fragment, 'derivations' => count($trees)]; continue; }
        $spans = [];
        $walk = function (array $tree) use (&$walk, &$spans): void {
            if (in_array($tree['name'], ['expression', 'additive-expression', 'multiplicative-expression', 'power-expression'], true)
                && $tree['end'] - $tree['start'] > 1) $spans[$tree['start'] . ':' . $tree['end']] = [$tree['start'], $tree['end']];
            foreach ($tree['children'] as $child) $walk($child);
        };
        $walk($trees[0]);
        $tokens = array_map(static fn ($t) => is_array($t) ? $t[1] : $t, DerivationForest::tokens($fragment));
        $opens = $closes = [];
        foreach ($spans as [$start, $end]) { $opens[$start] = ($opens[$start] ?? 0) + 1; $closes[$end] = ($closes[$end] ?? 0) + 1; }
        $forced = '';
        for ($i = 0; $i <= count($tokens); $i++) {
            $forced .= str_repeat(')', $closes[$i] ?? 0) . str_repeat('(', $opens[$i] ?? 0);
            if (isset($tokens[$i])) $forced .= $tokens[$i] . ' ';
        }
        $forestCount++;
        if (bindingNormalize(bindingExpression($forced)) !== end($expected)) $failures[] = compact('fragment', 'forced');
    }
    $actual = bindingExpression($source);
    $actual = $actual instanceof ast\Node && $actual->kind === ast\AST_ENCAPS_LIST
        ? array_map(bindingNormalize(...), array_values($actual->children)) : [bindingNormalize($actual)];
    // Zend retains an empty text node after stripping the final heredoc newline.
    $actual = array_values(array_filter($actual, static fn ($part) => $part !== ''));
    $matched = $matcher->matches('8.5', '<?php ' . $source . ';')->matched;
    $rows[] = ['style' => $style, 'body' => $body, 'segments' => $segments, 'normalized' => $actual,
        'boundary_equal' => $actual === $expected, 'matched' => $matched, 'operand_check_exclusions' => $unforced];
    if ($actual !== $expected || !$matched) $failures[] = compact('source', 'actual', 'expected');
}
$malformed = ['"{$a[}"', '"{$a->}"', '"{$a[1 + ]}"', '"{$a->{$b}"', '"$a[ ]"', "<<<TEXT\n{\$a[}\nTEXT"];
foreach ($malformed as $source) {
    $zend = true;
    try { bindingExpression($source); } catch (ParseError) { $zend = false; }
    $matched = $matcher->matches('8.5', '<?php ' . $source . ';')->matched;
    $rows[] = ['malformed' => $source, 'zend' => $zend, 'matched' => $matched];
    if ($zend || $matched) $failures[] = compact('source', 'zend', 'matched');
}
$hashes = [];
foreach (['grammar/8.5/php.ebnf', 'src/Php/Lexing/StringSyntax.php', 'src/Php/Lexing/Lexer.php', 'tests/Support/DerivationForest.php', 'tests/Support/InterpolationSegments.php', 'tools/8.5/interpolation-binding.php'] as $path) $hashes[$path] = hash_file('sha256', $root . '/' . $path);
$report = ['php' => PHP_VERSION, 'ast' => phpversion('ast'), 'schema' => $schema, 'hashes' => $hashes,
    'positive' => count($bodies) * 2, 'malformed' => count($malformed), 'operand_comparisons' => $forestCount,
    'limits' => 'Unindented ASCII bodies without escapes; simple bare offset has boundary-only evidence. Nested aggregate strings, closures/classes in embedded expressions, dedent/escape-value normalization and arbitrary recursive binding remain unproven.',
    'cases' => $rows, 'failures' => $failures];
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
$path = $root . '/docs/8.5/interpolation-binding.json';
if (in_array('--check', $argv, true)) {
    if (!is_file($path) || file_get_contents($path) !== $json) { fwrite(STDERR, "Interpolation evidence stale.\n"); exit(1); }
} elseif (!$failures) file_put_contents($path, $json);
echo json_encode(['positive' => $report['positive'], 'malformed' => count($malformed), 'operand_comparisons' => $forestCount, 'failures' => $failures], JSON_PRETTY_PRINT) . "\n";
exit($failures ? 1 : 0);
