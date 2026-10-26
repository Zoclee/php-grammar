<?php
declare(strict_types=1);

// Run the existing 36-gate implementation without overwriting historical evidence.
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/Certification86.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;

$root = str_replace('\\', '/', realpath(S::ROOT));
$args = S::args(['snapshot' => '.audit/phase7c6-certification', 'output' => 'docs/8.6/certification-blocker-closure/run',
    'php85' => $root . '/.audit/php85/php.exe', 'php86' => $root . '/.audit/php86/php.exe',
    'sources85' => $root . '/.audit/php-php-src-7a4c627', 'sources86' => $root . '/.audit/php-php-src-f23b4c2',
    'source-cache' => $root . '/.audit/phase7-sources', 'composer' => 'composer']);
$c = new C($root);
if (!preg_match('~^\.audit/[A-Za-z0-9_-]+$~D', $args['snapshot'])) throw new RuntimeException('Require a new direct child of .audit');
if (!preg_match('~^docs/8\.6/certification-blocker-closure/[A-Za-z0-9_-]+$~D', $args['output'])) throw new RuntimeException('Require a new triage evidence subdirectory');
$snapshot = $root . '/' . $args['snapshot']; $out = $args['output'];
if (file_exists($snapshot) || file_exists($c->path($out))) throw new RuntimeException('Never overwrite a snapshot or evidence revision');
foreach ([$root . '/.audit', $c->path('docs/8.6/certification-blocker-closure')] as $parent) if (is_link($parent)) throw new RuntimeException('Symlink destination parent');
// Explicit distribution profile is mandatory for the unchanged legacy checker.
$lock = $c->read('tools/8.6/context-source-lock.json');
foreach (['85' => '8.5', '86' => '8.6'] as $suffix => $version) {
    if (!is_file($args['php' . $suffix]) || hash_file('sha256', $args['php' . $suffix]) !== $lock['executables'][$version]) throw new RuntimeException('Legacy gate interpreter identity mismatch: ' . $version);
}
$paths = ['AGENTS.md', 'README.md', 'composer.json', 'composer.lock', 'phpunit.xml.dist', 'php-grammar.json', '.gitattributes', '.gitignore'];
foreach (['docs', 'tools', 'tests', 'grammar', 'src', 'schema', 'bin', 'vendor'] as $dir) foreach (S::files($root . '/' . $dir) as $file) $paths[] = substr($file, strlen($root) + 1);
$inputs = $c->hashes(array_unique($paths));
mkdir($snapshot, 0777, true); mkdir($c->path($out), 0777, true);
foreach ($inputs as $path => $hash) {
    $target = $snapshot . '/' . $path;
    if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
    if (!copy($c->path($path), $target) || hash_file('sha256', $target) !== $hash) throw new RuntimeException('Snapshot copy mismatch: ' . $path);
}
$c->write($out . '/snapshot.json', ['snapshot' => $snapshot, 'inputs' => $inputs, 'createdAt' => gmdate('c'),
    'profile' => 'Original hash-locked RC4 distribution; exact-source prerequisite inspections remain separate.']);
$rows = [];
$run = static function(string $id, array $command) use (&$rows, $c, $out, $snapshot): array {
    echo 'Running ' . $id . "\n"; flush();
    $log = $out . '/' . $id . '.log'; $r = S::run($command, $snapshot, $c->path($log));
    $rows[$id] = ['command' => $command, 'cwd' => $snapshot, 'exit_code' => $r['exit_code'], 'log' => $log, 'sha256' => hash_file('sha256', $c->path($log))];
    $c->write($out . '/commands.json', $rows); echo $id . ': ' . $r['exit_code'] . "\n"; flush(); return $r;
};
$base = [$args['php85'], '-n', 'tools/8.6/certification.php']; $options = [];
foreach (['php85', 'php86', 'sources85', 'sources86', 'source-cache', 'composer'] as $key) array_push($options, '--' . $key, $args[$key]);
$run('prepare', [...$base, '--prepare', ...$options]);
$sc = new C($snapshot); $report = $sc->read(C::REPORT);
$before = $sc->hashes(array_unique([C::REPORT, ...array_keys($report['inputs']), ...array_keys($report['artifacts'])]));
$run('check', [...$base, '--check']); $run('claims', [...$base, '--claims']); $run('run-refusal', [...$base, '--run']);
$export = [C::REPORT]; foreach (S::files($snapshot . '/' . C::OUTPUT) as $file) $export[] = substr($file, strlen($snapshot) + 1);
foreach ($export as $path) {
    $target = $c->path($out . '/artifacts/' . $path); if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
    if (!copy($snapshot . '/' . $path, $target)) throw new RuntimeException('Export failed');
}
$errors = $sc->verifyHashes($before);
foreach (['prepare' => 0, 'check' => 0, 'claims' => 1, 'run-refusal' => 1] as $id => $exit) if ($rows[$id]['exit_code'] !== $exit) $errors[] = 'Unexpected runner exit: ' . $id;
// The main workspace's original inputs must remain untouched by this snapshot run.
$errors = [...$errors, ...$c->verifyHashes($inputs)];
$c->write($out . '/execution.json', ['runnerSha256' => $inputs['tools/8.6/certification.php'], 'snapshot' => $snapshot,
    'commands' => $rows, 'gateCounts' => array_count_values(array_column($report['gates'], 'status')),
    'totalGates' => count($report['gates']), 'executedGates' => count(array_filter($report['gates'], static fn($r) => isset($r['command']))),
    'validation' => $report['validation'], 'readOnlyPaths' => count($before), 'errors' => $errors,
    'certificationGranted' => false, 'scope' => 'Fresh existing-runner execution. Blocked obligations are not executed tests. Gate failures remain failures.']);
echo S::json(['gates' => array_count_values(array_column($report['gates'], 'status')), 'validation' => $report['validation'], 'errors' => $errors, 'certificationGranted' => false]);
exit($errors ? 1 : 0);
