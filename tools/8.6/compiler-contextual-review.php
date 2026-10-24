<?php

declare(strict_types=1);

// Phase 5C evidence/preservation review, not a new release gate.
$root = dirname(__DIR__, 2);
$options = getopt('', ['logs:', 'report:']);
if (!isset($options['logs'], $options['report'])) {
    fwrite(STDERR, "Required: --logs=<phase5c-log-directory> --report=<output.json>\n");
    exit(1);
}
$read = static fn(string $path): array => json_decode(file_get_contents($root . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
$baseline = $read('docs/8.6/compiler-contextual-baseline.json');
$changed = [];
foreach ($baseline['files'] as $path => $hash) {
    if (!is_file($root . '/' . $path) || hash_file('sha256', $root . '/' . $path) !== $hash) $changed[] = $path;
}
if ($changed !== []) $errors[] = 'Changed protected files: ' . implode(', ', $changed);
$probes = $read('docs/8.6/compiler-contextual-probes.json');
foreach ($probes['inputHashes'] + $probes['fixtureHashes'] as $path => $hash) {
    if (hash_file('sha256', $root . '/' . $path) !== $hash) $errors[] = 'Stale probe input: ' . $path;
}
if ($probes['errors'] !== []) $errors[] = 'Probe validation failed';
$manifest = $read('php-grammar.json');
$draft = array_values(array_filter($manifest['versions'], static fn($v) => $v['version'] === '8.6'))[0];
if ($draft['status'] !== 'draft') $errors[] = 'Changed draft status';
$links = 0;
$reportPath = 'docs/8.6/compiler-contextual-reconciliation.md';
$body = file_get_contents($root . '/' . $reportPath);
if (preg_match('/[ \t]+$/m', $body)) $errors[] = 'Trailing report whitespace';
preg_match_all('/\[[^\]]*\]\(([^)]+)\)/', $body, $matches);
foreach ($matches[1] as $url) {
    if (preg_match('~^https?://~', $url)) continue;
    ++$links;
    $destination = $root . '/' . dirname($reportPath) . '/' . explode('#', $url)[0];
    $destinationKey = str_replace('\\', '/', realpath(dirname($destination)) . '/' . basename($destination));
    $outputKey = str_replace('\\', '/', realpath(dirname($options['report'])) . '/' . basename($options['report']));
    if (!is_file($destination) && $destinationKey !== $outputKey) $errors[] = 'Broken local link: ' . $url;
}
$jsonArtifacts = glob($root . '/docs/8.6/compiler-contextual-*.json');
foreach ($jsonArtifacts as $path) json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$logs = [];
$expect = ['focused' => 'OK (84 tests, 1204 assertions)', 'suite' => 'OK (6019 tests, 56847 assertions)', 'pfa' => '225 checks; 114 paired-version observations; 0 failures', 'context' => '278 checks; 126 paired-version stage observations; 0 failures', 'deprecation' => '712 checks; 268 observations; 0 failures', 'integration' => '498 checks; 160 observations; 0 failures', 'manifest' => 'Manifest schema 1.0 and package paths: PASS'];
foreach (array_merge(array_keys($expect), ['research', 'traceability', 'coverage']) as $name) {
    $path = $options['logs'] . '/phase5c-' . $name . '.log';
    if (!is_file($path)) { $errors[] = 'Missing log: ' . $name; continue; }
    $raw = file_get_contents($path);
    $text = str_starts_with($raw, "\xff\xfe") ? mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE') : $raw;
    $logs[$name] = ['sha256' => hash('sha256', $raw), 'output' => $text];
    if (isset($expect[$name]) && !str_contains($text, $expect[$name])) $errors[] = 'Missing passing result: ' . $name;
}
$trace = $read('docs/8.6/compiler-contextual-traceability.json');
if (($trace['errors'] ?? null) !== []) $errors[] = 'Traceability did not pass';
if (!str_contains($logs['coverage']['output'] ?? '', '280 / 361') || !str_contains($logs['coverage']['output'] ?? '', '425 / 800')) $errors[] = 'Coverage baseline changed';
$dispositions = $read('docs/8.6/compiler-contextual-reconciliation.json');
if ($dispositions['nextPhaseBegun'] !== false) $errors[] = 'Unexpected phase progression';
$result = ['phase' => '5C', 'scope' => 'Integrity and actual logs; not certification.', 'baselineCommit' => $baseline['commit'], 'protectedFilesChecked' => count($baseline['files']), 'historical85FilesChecked' => $baseline['historical85FileCount'], 'changedProtectedFiles' => $changed, 'localLinks' => $links, 'jsonArtifactsChecked' => count($jsonArtifacts), 'reportSha256' => hash('sha256', $body), 'probeChecks' => $probes['checks'], 'observations' => count($probes['observations']), 'logs' => $logs, 'errors' => $errors];
file_put_contents($options['report'], json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
echo json_encode(['protectedFiles' => count($baseline['files']), 'historical85Files' => $baseline['historical85FileCount'], 'links' => $links, 'errors' => $errors], JSON_PRETTY_PRINT) . "\n";
exit($errors === [] ? 0 : 1);
