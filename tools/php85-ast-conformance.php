<?php

declare(strict_types=1);

// ext-ast exposes the running Zend parser's AST before declaration compilation.
if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5 || !extension_loaded('ast')) {
    fwrite(STDERR, "Run with PHP 8.5 and ext-ast enabled.\n");
    exit(2);
}
$root = dirname(__DIR__);
$cases = json_decode(file_get_contents($root . '/tests/fixtures/php/8.5/parser-structure.json'), true, flags: JSON_THROW_ON_ERROR);
function normalize(mixed $node): mixed {
    if (!$node instanceof ast\Node) return $node;
    $children = [];
    foreach ($node->children as $key => $child) {
        if ($key !== '__declId') $children[$key] = normalize($child);
    }
    // Explicit parentheses set this flag without changing conditional grouping.
    $flags = $node->kind === ast\AST_CONDITIONAL ? $node->flags & ~ast\flags\PARENTHESIZED_CONDITIONAL : $node->flags;
    return [ast\get_kind_name($node->kind), $flags, $children];
}
$version = max(ast\get_supported_versions());
$failures = 0;
foreach ($cases as $case) {
    $suffix = $case['root'] === 'expression' ? ';' : '';
    try {
        $actual = normalize(ast\parse_code('<?php ' . $case['source'] . $suffix, $version));
        $expected = normalize(ast\parse_code('<?php ' . $case['expected'] . $suffix, $version));
        if ($actual !== $expected) {
            $failures++;
            echo $case['source'] . "\n" . json_encode(['actual' => $actual, 'expected' => $expected]) . "\n";
        }
    } catch (Throwable $e) {
        $failures++;
        echo $case['source'] . ': ' . $e->getMessage() . "\n";
    }
}
$invalid = json_decode(file_get_contents($root . '/tests/fixtures/php/8.5/parser-structure-invalid.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($invalid as $source) {
    try {
        ast\parse_code('<?php ' . $source, $version);
        $failures++;
        echo 'Unexpected Zend reduction: ' . $source . "\n";
    } catch (ParseError) {
        // T_ELSE/T_ELSEIF shift toward the unmatched inner if, whose body cannot start with ':'.
    }
}
echo json_encode(['php' => PHP_VERSION, 'ast' => phpversion('ast'), 'schema' => $version,
    'cases' => count($cases), 'negative_cases' => count($invalid), 'failures' => $failures], JSON_PRETTY_PRINT) . "\n";
exit($failures ? 1 : 0);
