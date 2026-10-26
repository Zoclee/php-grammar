<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$args = S::args(['php' => PHP_BINARY, 'composer' => 'composer', 'source-directory' => S::ROOT . '/.audit', 'source-cache' => S::ROOT . '/.audit/phase7-sources', 'logs' => S::ROOT . '/.audit/phase7-validation']);
$php = realpath($args['php']) ?: $args['php'];
if (is_file($php)) {
    putenv('PATH=' . dirname($php) . PATH_SEPARATOR . getenv('PATH'));
}
$composer = S::composer($args['composer'], $php);
$p = [$php, '-d', 'zend.multibyte=0'];
$commands = [
    'composer-validate' => [...$composer, 'validate', '--strict'],
    'manifest-schema' => [...$p, 'tools/validate-manifest.php'],
    'manifest-regressions' => [...$p, 'tools/test-manifest.php'],
    'consumer-contract' => [...$p, 'vendor/phpunit/phpunit/phpunit', '--testsuite', 'php-grammar', '--filter', 'Consumer', '--no-progress'],
    'expression-generation' => [...$p, 'tools/8.5/generate-expressions.php', '--check'],
    'phpunit' => [...$composer, 'test', '--', '--no-progress'],
    'grammar-coverage' => [...$composer, 'grammar:coverage'],
    'lexer-coverage' => [...$composer, 'lexer:coverage'],
    'ordinary-differential' => [...$p, 'bin/php85-conformance.php', $php],
    'explicit-ast' => [...$p, 'tools/8.5/ast-conformance.php'],
    'systematic-structure' => [...$p, 'tools/8.5/systematic-structure.php'],
    'declaration-folding' => [...$p, 'tools/8.5/boundary-folding.php', $php],
    'phase6-matrices' => [...$composer, 'conformance:phase6'],
    'phase6-matrix-freshness' => [...$p, 'tools/8.5/phase6.php', '--check'],
    'scanner-product' => [...$p, 'tools/8.5/scanner-product.php'],
    'lexer-short-enabled' => [...$p, '-d', 'short_open_tag=1', 'tools/8.5/lexer-differential.php'],
    'lexer-short-disabled' => [...$p, '-d', 'short_open_tag=0', 'tools/8.5/lexer-differential.php'],
    'positive-report-freshness' => [...$p, 'tools/8.5/coverage-report.php', '--check'],
    'negative-report-freshness' => [...$p, 'tools/8.5/negative-report.php', '--check'],
    'compiler-source-hashes' => [...$p, 'tools/8.5/compiler-boundaries.php', $args['source-directory'], '--check'],
    'parser-reconciliation' => [...$p, 'tools/8.5/reconcile.php', $args['source-directory'], '--check'],
    'phase6-evidence-freshness' => [...$p, 'tools/8.5/phase6-evidence.php', '--check'],
    'interpolation-binding' => [...$p, 'tools/8.5/interpolation-binding.php', '--check'],
    'diagnostic-predicates' => [...$p, 'tools/8.5/diagnostic-witnesses.php', '--check'],
    'source-correspondence' => [...$p, 'tools/8.5/source-correspondence.php', '--cache', $args['source-cache'], '--check'],
    'final-certification' => [...$p, 'tools/8.5/certification.php', '--source-cache', $args['source-cache'], '--check'],
    'diff-whitespace' => ['git', 'diff', '--check'],
];
if (!is_dir($args['logs'])) {
    mkdir($args['logs'], 0777, true);
}
$results = [];
foreach ($commands as $name => $command) {
    echo 'Running ' . $name . "\n";
    try {
        $code = S::run($command, S::ROOT, $args['logs'] . '/' . $name . '.log')['exit_code'];
    } catch (Throwable $error) {
        echo $error->getMessage() . "\n";
        $code = 127;
    }
    $results[] = ['gate' => $name, 'exit_code' => $code];
    echo $name . ': ' . ($code === 0 ? 'PASS' : 'FAIL') . "\n";
    flush();
}
file_put_contents($args['logs'] . '/results.json', S::json($results));
$passed = count(array_filter($results, static fn(array $r): bool => $r['exit_code'] === 0));
echo $passed . '/' . count($results) . ' gates passed; logs: ' . $args['logs'] . "\n";
exit($passed === count($results) ? 0 : 1);
