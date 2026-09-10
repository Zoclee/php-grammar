<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$path = $root . '/grammar/8.5/php.md';
$contents = file_get_contents($path);
$marker = '<!-- BEGIN GENERATED EBNF -->';
$start = strpos($contents, $marker);
if ($start === false) {
    throw new RuntimeException('Missing generated EBNF marker.');
}
file_put_contents($path, substr($contents, 0, $start) . $marker . "\n```ebnf\n"
    . rtrim(file_get_contents($root . '/grammar/8.5/php.ebnf'))
    . "\n```\n<!-- END GENERATED EBNF -->\n");
