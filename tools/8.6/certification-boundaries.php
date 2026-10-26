<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/bootstrap.php';

use PhpGrammar\Tools\Support as S;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;

$args = S::args(['php86' => S::ROOT . '/.audit/php86/php.exe', 'report' => 'docs/8.6/certification/boundaries.json']);
chdir(realpath(S::ROOT));
$expected = S::read('docs/8.6/source-audit-boundaries.json');
$matcher = PhpGrammarMatcher::forManifest(RepositoryManifest::fromRepositoryRoot(S::ROOT));
$errors = []; $rows = []; $inputs = [];
$paths = [];
foreach (['valid', 'invalid', 'contextual-invalid', 'known-discrepancies/valid'] as $kind) array_push($paths, ...glob('tests/fixtures/php/8.6/' . $kind . '/*.php'));
$recorded = array_column($expected['cases'], 'fixture');
sort($paths); sort($recorded);
if ($paths !== $recorded) $errors[] = 'Ordinary corpus differs from reviewed boundary inventory; review every new/missing case.';
foreach ($expected['cases'] as $case) {
    $path = $case['fixture'];
    if (!is_file($path) || hash_file('sha256', $path) !== $case['sha256']) {
        $errors[] = 'Missing/stale reviewed fixture: ' . $path;
        continue;
    }
    $inputs[$path] = $case['sha256'];
    $structural = $matcher->matches('8.6', file_get_contents($path))->matched;
    $command = [$args['php86'], '-n', '-d', 'zend.multibyte=0', '-d', 'short_open_tag=1',
        '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0', '-d', 'error_reporting=-1', '-l', $path];
    $r = S::run($command, S::ROOT);
    if ($structural !== $case['structuralAccepted'] || $r['exit_code'] !== $case['lintExit']) $errors[] = 'Boundary outcome changed: ' . $path;
    $rows[] = ['fixture' => $path, 'sha256' => $inputs[$path], 'kind' => $case['kind'],
        'structuralAccepted' => $structural, 'command' => $command, 'exit_code' => $r['exit_code'],
        'output' => $r['output'], 'classification' => $case['classification'], 'metadata' => $case['metadata']];
}
$inputs['docs/8.6/source-audit-boundaries.json'] = hash_file('sha256', 'docs/8.6/source-audit-boundaries.json');
$report = ['version' => '8.6', 'source_pin' => 'f23b4c222cebe33be93ee8ecaaa14390c5507a08',
    'scope' => 'Fresh lint and structural comparison with every reviewed Phase 6 boundary expectation. Contextual/link cases are not required to fail lint; no arbitrary fixture execution.',
    'exactPinExecutableVerified' => false, 'inputs' => $inputs, 'cases' => $rows, 'errors' => $errors];
file_put_contents($args['report'], S::json($report));
echo S::json(['cases' => count($rows), 'errors' => $errors]);
exit($errors === [] ? 0 : 1);
