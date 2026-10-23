<?php

declare(strict_types=1);

// Focused PHP86-001 evidence; not a PHP 8.6 release or registration gate.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
use PhpGrammar\Ebnf\Parser;

$root = dirname(__DIR__, 2);
$options = getopt('', ['php85:', 'php86:', 'sources:', 'report:']);
$cases = json_decode(file_get_contents($root . '/tests/fixtures/php/8.6/pfa-cases.json'), true, 512, JSON_THROW_ON_ERROR);
$lock = json_decode(file_get_contents(__DIR__ . '/pfa-source-lock.json'), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
$report = ['scope' => 'PHP86-001 only; lint is not runtime or exact-pin certification', 'checks' => [], 'observations' => []];
$check = static function (bool $ok, string $message) use (&$errors, &$report): void {
    $report['checks'][] = ['check' => $message, 'pass' => $ok];
    if (!$ok) { $errors[] = $message; }
};
$parser = new Parser();
$oldSource = file_get_contents($root . '/grammar/8.5/php.ebnf');
$newSource = file_get_contents($root . '/grammar/8.6/php.ebnf');
$old = $parser->parse($oldSource);
$new = $parser->parse($newSource);
$check(hash('sha256', $oldSource) === $lock['baseline']['ebnf'], 'Protected PHP 8.5 EBNF hash');
$check(hash_file('sha256', $root . '/grammar/8.5/php.md') === $lock['baseline']['markdown'], 'Protected PHP 8.5 Markdown hash');
$check($old->productionNames() === $new->productionNames(), 'No production addition, removal, rename or reorder');
$changed = [];
foreach ($old->productions() as $production) {
    if (serialize($production->expression) !== serialize($new->production($production->name)->expression)) {
        $changed[] = $production->name;
    }
}
$check($changed === $lock['modifiedProductions'], 'Every structural difference mapped to PHP86-001');
$document = file_get_contents($root . '/grammar/8.6/php.md');
preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s', $document, $match);
$check(isset($match[1]) && trim($match[1]) === trim($newSource), 'Copied specification production parity');
$changes = file_get_contents($root . '/grammar/8.6/changes.md');
foreach ($changed as $name) { $check(str_contains($changes, '`' . $name . '`'), 'Change record names ' . $name); }
preg_match_all('/^## (\d+)\. /m', $changes, $sections);
$check($sections[1] === array_map('strval', range(1, 10)), 'Ten change-document sections in order');
foreach ($cases as $case) {
    $check(is_file($root . '/' . $case['fixture']) && $case['evidence'] !== '', 'Fixture and source mapping: ' . $case['id']);
}
if (isset($options['sources'])) {
    foreach ($lock['files'] as $file => $hash) {
        $path = $options['sources'] . '/' . $file;
        $check(is_file($path) && hash_file('sha256', $path) === $hash, 'Pinned source hash: ' . $file);
    }
} else {
    $report['sourceVerification'] = 'NOT RUN: supply --sources with the pinned RC4 tree';
}
foreach (['85' => '8.5', '86' => '8.6'] as $suffix => $version) {
    if (!isset($options['php' . $suffix])) {
        $report['interpreters'][$version] = 'NOT RUN: supply --php' . $suffix;
        continue;
    }
    $exe = $options['php' . $suffix];
    $run = static function(array $args) use ($exe): array {
        $process = proc_open(array_merge([$exe, '-n'], $args), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) { throw new RuntimeException('Cannot start PHP interpreter'); }
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        return ['exit' => proc_close($process), 'output' => trim($output)];
    };
    $report['interpreters'][$version] = $run(['-v']);
    $check(str_contains($report['interpreters'][$version]['output'], 'PHP ' . $version . '.'), 'Interpreter version ' . $version);
    foreach ($cases as $case) {
        $result = $run(['-l', $root . '/' . $case['fixture']]);
        $result['output'] = str_replace([$root, str_replace('\\', '/', $root)], '<repository>', $result['output']);
        $report['observations'][] = ['id' => $case['id'], 'version' => $version] + $result;
        $check(($result['exit'] === 0) === $case['lint' . $suffix], 'Lint ' . $version . ': ' . $case['id']);
        if ($version === '8.6') {
            if ($case['diagnostic86'] !== null) {
                $check(str_contains($result['output'], $case['diagnostic86']), 'RC4 diagnostic: ' . $case['id']);
            }
        }
    }
}
$report['errors'] = $errors;
if (isset($options['report'])) {
    file_put_contents($options['report'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}
printf("PHP86-001: %d checks; %d paired-version observations; %d failures\n", count($report['checks']), count($report['observations']), count($errors));
foreach ($errors as $error) { fwrite(STDERR, $error . "\n"); }
exit($errors === [] ? 0 : 1);
