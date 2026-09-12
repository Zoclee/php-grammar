<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5) { fwrite(STDERR, "Run with PHP 8.5.\n"); exit(2); }
$root = dirname(__DIR__, 2);
$path = 'tests/fixtures/php/8.5/diagnostic-predicates.json';
$cases = json_decode(file_get_contents($root . '/' . $path), true, flags: JSON_THROW_ON_ERROR);
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
$file = tempnam(sys_get_temp_dir(), 'php85-predicate-');
$rows = $failures = [];
try {
    foreach ($cases as $id => $case) {
        foreach (['positive', 'negative'] as $kind) {
            $source = '<?php ' . $case[$kind];
            file_put_contents($file, $source);
            $process = proc_open([PHP_BINARY, '-n', '-d', 'zend.multibyte=0', '-l', $file],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) throw new RuntimeException('Cannot start diagnostic oracle');
            fclose($pipes[0]);
            $message = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $accepted = proc_close($process) === 0;
            $matched = $matcher->matches('8.5', $source)->matched;
            $diagnostic = $kind === 'negative' && str_contains($message, $case['diagnostic']);
            $row = compact('id', 'kind', 'accepted', 'matched', 'diagnostic');
            $rows[] = $row;
            if ($accepted !== ($kind === 'positive') || !$matched || ($kind === 'negative' && !$diagnostic)) {
                $failures[] = $row + ['message' => $message];
            }
        }
    }
} finally { unlink($file); }
$hashes = [];
foreach ([$path, 'tools/8.5/diagnostic-witnesses.php', 'grammar/8.5/php.ebnf'] as $name) $hashes[$name] = hash_file('sha256', $root . '/' . $name);
$report = ['php' => PHP_VERSION, 'profile' => '-n zend.multibyte=0', 'hashes' => $hashes,
    'sites' => count($cases), 'comparisons' => count($rows), 'cases' => $rows, 'failures' => $failures,
    'scope' => 'Observed diagnostic text plus one repaired positive per site; not instrumented C branch coverage. All these contextual negatives remain structural matches.'];
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
$output = $root . '/docs/8.5/diagnostic-witnesses.json';
if (in_array('--check', $argv, true)) {
    if (!is_file($output) || file_get_contents($output) !== $json) { fwrite(STDERR, "Diagnostic witnesses stale.\n"); exit(1); }
} elseif (!$failures) file_put_contents($output, $json);
echo json_encode(['sites' => count($cases), 'comparisons' => count($rows), 'failures' => $failures], JSON_PRETTY_PRINT) . "\n";
exit($failures ? 1 : 0);
