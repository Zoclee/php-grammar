<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;

$binary = $argv[1] ?? getenv('PHP85_BINARY');
if (!$binary) {
    fwrite(STDERR, "Usage: php tools/php85-boundary-folding.php /path/to/php-8.5\n");
    exit(2);
}
function oracle(array $command): array {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot launch PHP oracle');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output];
}
[$status, $version] = oracle([$binary, '-n', '-v']);
if ($status !== 0 || !preg_match('/PHP 8\.5\.\d+/', $version, $versionMatch)) {
    fwrite(STDERR, "The oracle must be PHP 8.5.x.\n");
    exit(2);
}
$root = dirname(__DIR__);
$data = json_decode(file_get_contents($root . '/tests/fixtures/php/8.5/parser-compiler-boundaries.json'), true, flags: JSON_THROW_ON_ERROR);
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
$templates = [
    'ternary' => ['true ? 1 : %s', true],
    'and' => ['false && %s', true],
    'or' => ['true || %s', true],
    'word-and' => ['false and %s', true],
    'word-or' => ['true or %s', true],
    'coalesce' => ['1 ?? %s', true],
    'retained-ternary' => ['false ? 1 : %s', false],
    'retained-and' => ['true && %s', false],
    'retained-or' => ['false || %s', false],
    'retained-word-and' => ['true and %s', false],
    'retained-word-or' => ['false or %s', false],
    'retained-coalesce' => ['null ?? %s', false],
    'xor-does-not-discard' => ['true xor %s', false],
];
$file = tempnam(sys_get_temp_dir(), 'php85-boundary-');
$count = $failures = 0;
try {
    foreach ($data['cases'] as $case) {
        foreach ($templates as $operator => [$template, $discards]) {
            $source = '<?php const X = ' . sprintf($template, 'static function() { ' . $case['source'] . ' }') . ';';
            file_put_contents($file, $source);
            [$status, $output] = oracle([$binary, '-n', '-l', $file]);
            $matched = $matcher->matches('8.5', $source)->matched;
            $count++;
            $expectedLint = $discards || $case['live_valid'];
            if (!$matched || ($status === 0) !== $expectedLint) {
                $failures++;
                echo $case['id'] . '/' . $operator . ': EBNF=' . (int)$matched . ', lint=' . $status . "\n" . $output;
            }
        }
    }
} finally {
    unlink($file);
}
echo json_encode(['php' => $versionMatch[0], 'folding_cases' => $count, 'failures' => $failures], JSON_PRETTY_PRINT) . "\n";
exit($failures ? 1 : 0);
