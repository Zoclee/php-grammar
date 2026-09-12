<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Ebnf\Parser;
use PhpGrammar\Php\Conformance\{PhpGrammarMatcher, Php85ModifierValidator};
use PhpGrammar\Tests\Support\{DerivationForest, Php85Phase6Cases};

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5) {
    fwrite(STDERR, "Run with PHP 8.5.\n");
    exit(2);
}
$root = dirname(__DIR__, 2);
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
$grammar = (new Parser())->parse(file_get_contents($root . '/grammar/8.5/php.ebnf'));
$validator = new Php85ModifierValidator();
$file = tempnam(sys_get_temp_dir(), 'php85-phase6-');
$rows = $failures = [];
$lint = static function (string $source, bool $short = false) use ($file): bool {
    file_put_contents($file, $source);
    $process = proc_open([PHP_BINARY, '-n', '-d', 'short_open_tag=' . (int)$short, '-l', $file],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot launch PHP oracle');
    fclose($pipes[0]);
    stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return proc_close($process) === 0;
};
try {
    foreach (Php85Phase6Cases::folding() as [$id, $source, $expected]) {
        $accepted = $lint($source);
        $matched = $matcher->matches('8.5', $source)->matched;
        $row = ['matrix' => 'direct-folding', 'id' => $id, 'expected' => $expected, 'lint' => $accepted, 'matched' => $matched];
        $rows[] = $row;
        if ($accepted !== $expected || !$matched) $failures[] = $row + ['source' => $source];
    }
    foreach (Php85Phase6Cases::modifiers() as [$target, $tokens, $declaration]) {
        $category = $validator->validate($target, $tokens);
        $source = '<?php const X = true ? 1 : static function() { ' . $declaration . ' };';
        $accepted = $lint($source);
        $row = ['matrix' => 'modifiers', 'target' => $target, 'tokens' => $tokens, 'category' => $category, 'lint' => $accepted];
        $rows[] = $row;
        if ($accepted !== ($category === null)) $failures[] = $row + ['source' => $source];
    }
    foreach (Php85Phase6Cases::ambiguity() as [$entry, $source]) {
        $trees = (new DerivationForest($grammar, $source))->trees($entry);
        $count = count($trees);
        // int is both an explicit simple-type literal and a name. Normalize
        // only this documented lexical/helper overlap, retaining every span.
        $normalize = function (array $tree) use (&$normalize): array {
            if (in_array($tree['name'], ['simple-type', 'simple-type-without-static'], true)) $tree['children'] = [];
            else $tree['children'] = array_map($normalize, $tree['children']);
            // Synthetic EBNF expansion names are not semantic identities.
            if (str_starts_with($tree['name'], '@')) $tree['name'] = '@helper';
            return $tree;
        };
        $fingerprints = array_unique(array_map(static fn ($tree) => json_encode($normalize($tree)), $trees));
        $helperOverlap = $entry === 'class-member' && str_starts_with($source, 'public int $x');
        $matched = $matcher->matchesRule('8.5', $entry, $source)->matched;
        $row = ['matrix' => 'ambiguity', 'entry' => $entry, 'source' => $source, 'derivations' => $count,
            'normalized_structures' => count($fingerprints), 'helper_overlap' => $helperOverlap, 'matched' => $matched];
        $rows[] = $row;
        if ($count !== ($helperOverlap ? 2 : 1) || count($fingerprints) !== 1 || !$matched) $failures[] = $row;
    }
    foreach ([false, true] as $short) {
        foreach (Php85Phase6Cases::recursive() as [$id, $source, $expected]) {
            $accepted = $lint($source, $short);
            $matched = $matcher->withShortOpenTag($short)->matches('8.5', $source)->matched;
            $row = ['matrix' => 'recursive', 'id' => $id, 'short' => $short, 'expected' => $expected, 'lint' => $accepted, 'matched' => $matched];
            $rows[] = $row;
            if ($accepted !== $expected || $matched !== $expected) $failures[] = $row + ['source' => $source];
        }
        foreach (Php85Phase6Cases::malformed() as [$id, $source]) {
            $accepted = $lint($source, $short);
            $matched = $matcher->withShortOpenTag($short)->matches('8.5', $source)->matched;
            $row = ['matrix' => 'malformed', 'id' => $id, 'short' => $short, 'lint' => $accepted, 'matched' => $matched];
            $rows[] = $row;
            if ($accepted || $matched) $failures[] = $row;
        }
    }
} finally { unlink($file); }
$hashes = [];
foreach (['grammar/8.5/php.ebnf', 'src/Php/Conformance/Php85ModifierValidator.php', 'tests/Support/Php85Phase6Cases.php', 'tests/Support/DerivationForest.php', 'tools/8.5/phase6.php'] as $path) $hashes[$path] = hash_file('sha256', $root . '/' . $path);
$counts = array_count_values(array_column($rows, 'matrix'));
$report = ['php' => PHP_VERSION, 'hashes' => $hashes, 'counts' => $counts, 'total' => count($rows), 'cases' => $rows, 'failures' => $failures];
$path = $root . '/docs/8.5/phase6-matrices.json';
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (in_array('--check', $argv, true)) {
    if (!is_file($path) || file_get_contents($path) !== $json) {
        fwrite(STDERR, "Phase 6 matrix evidence is stale.\n");
        exit(1);
    }
} else file_put_contents($path, $json);
echo json_encode(['counts' => $counts, 'failures' => $failures], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failures ? 1 : 0);
