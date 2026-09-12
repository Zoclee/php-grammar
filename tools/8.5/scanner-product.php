<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5) exit(2);
$root = dirname(__DIR__, 2);
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
// Each body is legal in a function when its scanner/parser structure is legal;
// the matrix deliberately avoids unrelated compile-time declaration failures.
$bodies = [
    'enum-lookahead' => 'enum E {}',
    'enum-name' => 'enum();',
    'enum-comment' => 'enum/*x*/E {}',
    'enum-excluded-prefix' => 'enum extendsName {}',
    'yield-from' => 'yield from [];',
    'yield-comment' => 'yield/*x*/from [];',
    'yield-close-comment' => "yield // ?>\n from [];",
    'yield-nul-comment' => "yield /*\0*/ from [0];",
    'yield-boundary' => 'yield fromName;',
    'property-comment' => '$a->/*x*/class;',
    'property-hash' => '$a->#[x' . "\n" . 'class;',
    'property-nullsafe' => '$a?-> /*x*/ class;',
    'varname' => 'echo "${class[0]}";',
    'varname-expression' => 'echo "${name + 1}";',
    'offset-numeric' => 'echo "$a[08] $a[0xFF] $a[-1_2]";',
    'offset-invalid' => 'echo "$a[1.2]";',
    'nested-interpolation' => 'echo "{$a["{$b[0]}"]}";',
    'nested-property' => 'echo "{$a->/*x*/class}";',
    'nested-tag' => 'echo "{$a[static function() { ?>html<?php }] }";',
    'nul-string' => "echo \"a\0b\";",
    'nul-code' => "\0;",
    'ampersand-comment' => '$a = &/*x*/$b;',
    'ampersand-type' => 'function f(A&/*x*/B $a) {}',
    'qualified' => '\\A\\match(); namespace\\match();',
    'qualified-gap' => 'A /*x*/ \\ B();',
    'heredoc' => "echo <<<END\n\$a[08]\nEND;",
    'nowdoc' => "echo <<<'END'\n\$a[08]\nEND;",
    'heredoc-nested' => 'echo <<<END' . "\n" . '{$a[<<<INNER' . "\ntext\nINNER\n" . ']}' . "\nEND;",
    'heredoc-indent' => "echo <<<END\n x\n  END;",
    'comment-close' => "// ?>\nhtml<?php ;",
    'block-close' => '/* ?> */;',
];
$wrappers = [
    'function' => '<?php function product() { %s }',
    'html' => 'before<?php function product() { %s } ?>after',
    'short' => '<? function product() { %s } ?>after',
    'shebang' => "#!/usr/bin/php\n<?php function product() { %s }",
    'alternative' => '<?php function product() { if (true): %s endif; }',
    'loop' => '<?php function product() { while (false) { %s } }',
    'interpolation' => '<?php echo "{$a[static function() { %s }]}";',
];
$file = tempnam(sys_get_temp_dir(), 'php85-product-');
$counts = $failures = [];
try {
    foreach ([false, true] as $short) {
        foreach ($wrappers as $wrapper => $template) foreach ($bodies as $name => $body) {
            $source = sprintf($template, $body);
            file_put_contents($file, $source);
            $process = proc_open([PHP_BINARY, '-n', '-d', 'short_open_tag=' . (int)$short, '-l', $file],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $lint = proc_close($process) === 0;
            $accepted = $matcher->withShortOpenTag($short)->matches('8.5', $source)->matched;
            $counts[$wrapper] = ($counts[$wrapper] ?? 0) + 1;
            if ($accepted !== $lint) $failures[] = compact('name', 'wrapper', 'short', 'source', 'lint', 'accepted', 'output');
        }
        // Closing labels require a following source byte; vary exact EOF and
        // line endings outside a wrapper, which would otherwise supply one.
        foreach (["\n", "\r\n", "\r"] as $newline) foreach (['END', "'END'"] as $label) foreach (['', ';', "\n", '; ?>'] as $tail) {
            $source = '<?php echo <<<' . $label . $newline . 'text' . $newline . 'END' . $tail;
            file_put_contents($file, $source);
            $process = proc_open([PHP_BINARY, '-n', '-l', $file], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $lint = proc_close($process) === 0;
            $accepted = $matcher->withShortOpenTag($short)->matches('8.5', $source)->matched;
            $counts['heredoc-eof'] = ($counts['heredoc-eof'] ?? 0) + 1;
            if ($accepted !== $lint) $failures[] = compact('source', 'short', 'lint', 'accepted', 'output');
        }
    }
} finally { unlink($file); }
$report = ['php' => PHP_VERSION, 'grammar_sha256' => hash_file('sha256', $root . '/grammar/8.5/php.ebnf'),
    'generator_sha256' => hash_file('sha256', __FILE__), 'families' => $counts, 'total' => array_sum($counts), 'failures' => $failures];
file_put_contents($root . '/docs/8.5/scanner-product.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode(['total' => $report['total'], 'failures' => count($failures)], JSON_PRETTY_PRINT) . "\n";
exit($failures ? 1 : 0);
