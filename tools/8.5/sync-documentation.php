<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once __DIR__ . '/grammar-sections.php';
$path = $root . '/grammar/8.5/php.md';
$contents = file_get_contents($path);
$source = file_get_contents($root . '/grammar/8.5/php.ebnf');
$marker = '<!-- BEGIN GENERATED EBNF -->';
$start = strpos($contents, $marker);
if ($start === false) {
    throw new RuntimeException('Missing generated EBNF marker.');
}
$prefix = substr($contents, 0, $start);
$indexMarker = '<!-- BEGIN GENERATED PRODUCTION INDEX -->';
$indexStart = strpos($prefix, $indexMarker);
if ($indexStart !== false) {
    $indexEndMarker = '<!-- END GENERATED PRODUCTION INDEX -->';
    $indexEnd = strpos($prefix, $indexEndMarker, $indexStart);
    if ($indexEnd === false) {
        throw new RuntimeException('Missing generated production index end marker.');
    }
    $prefix = substr($prefix, 0, $indexStart) . Php85GrammarSections::index($source)
        . substr($prefix, $indexEnd + strlen($indexEndMarker));
} else {
    $prefix .= Php85GrammarSections::index($source) . "\n\n## Canonical EBNF\n\n";
}
$output = $prefix . $marker . "\n```ebnf\n" . rtrim($source)
    . "\n```\n<!-- END GENERATED EBNF -->\n";
if (in_array('--check', $argv, true)) {
    if (str_replace("\r\n", "\n", $contents) !== str_replace("\r\n", "\n", $output)) {
        fwrite(STDERR, "Documentation is stale. Run php tools/8.5/sync-documentation.php\n");
        exit(1);
    }
} elseif ($contents !== $output) {
    file_put_contents($path, $output);
}
