<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;

$binary = $argv[1] ?? getenv('PHP85_BINARY');
if (!$binary) {
    fwrite(STDERR, "Usage: php bin/php85-conformance.php /path/to/php-8.5\n");
    exit(2);
}
function run(array $command): array {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot launch PHP 8.5.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output];
}
[$status, $version] = run([$binary, '-n', '-v']);
if ($status !== 0 || !preg_match('/PHP 8\.5\.\d+/', $version, $match)) {
    fwrite(STDERR, "The oracle must be PHP 8.5.x.\n" . $version);
    exit(2);
}
$root = dirname(__DIR__);
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
$counts = ['valid' => 0, 'invalid' => 0, 'contextual-invalid' => 0, 'mismatches' => 0];
echo $match[0] . "; short_open_tag=1 and 0; zend.multibyte=0\n";
foreach (['' => true, 'short-tags-disabled/' => false] as $profile => $shortTags) {
foreach (['valid', 'invalid', 'contextual-invalid'] as $category) {
    foreach (glob($root . '/tests/fixtures/php/8.5/' . $profile . $category . '/*.php') as $file) {
        $counts[$category]++;
        $result = $matcher->withShortOpenTag($shortTags)->matches('8.5', file_get_contents($file));
        [$status, $output] = run([$binary, '-n', '-d', 'short_open_tag=' . (int)$shortTags, '-d', 'zend.multibyte=0', '-l', $file]);
        $expectedGrammar = $category !== 'invalid';
        $expectedLint = $category === 'valid';
        if ($result->matched !== $expectedGrammar || ($status === 0) !== $expectedLint) {
            $counts['mismatches']++;
            echo $profile . $category . '/' . basename($file) . ': EBNF=' . (int)$result->matched . ', PHP=' . (int)($status === 0) . "\n";
            if ($status !== 0) echo $output;
            if (!$result->matched && $expectedGrammar) echo 'Expected at ' . $result->furthestOffset . ': ' . implode(', ', $result->expected) . "\n";
        }
    }
}
}
echo json_encode($counts, JSON_PRETTY_PRINT) . "\n";
echo "Contextual-invalid fixtures intentionally pass structural EBNF and fail PHP compilation.\n";
exit($counts['mismatches'] === 0 ? 0 : 1);
