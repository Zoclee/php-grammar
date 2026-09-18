<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/UnifiedDiff.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\UnifiedDiff;
$args = S::args(['cache' => S::ROOT . '/.audit/phase7-sources'], ['fetch', 'check']);
$release = '34308a6666b2d489c509541ea9befea9e2b42348';
$lock = S::read('tools/8.5/source-lock.json');
$rows = [];
foreach (['zend_language_parser.y', 'zend_language_scanner.l', 'zend_compile.c', 'zend_ast.c', 'zend_inheritance.c', 'zend_enum.c', 'zend_attributes.c'] as $base) {
    $name = 'Zend/' . $base;
    $versions = [];
    foreach ([S::PIN, $release] as $revision) {
        $path = $args['cache'] . '/' . $revision . '/' . $name;
        if ($args['fetch']) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, S::download('https://raw.githubusercontent.com/php/php-src/' . $revision . '/' . $name));
        }
        $data = file_get_contents($path);
        if ($revision === S::PIN && isset($lock[$base]) && hash('sha256', $data) !== $lock[$base]) {
            throw new RuntimeException('Pinned hash mismatch: ' . $name);
        }
        $versions[] = $data;
    }
    $lines = static function (string $bytes): array {
        $parts = preg_split('/\r\n|[\n\r\v\f\x1c-\x1e]|\xC2\x85|\xE2\x80[\xA8\xA9]/', $bytes);
        if (end($parts) === '') {
            array_pop($parts);
        }
        return $parts;
    };
    $rows[] = ['file' => $name, 'pin_sha256' => hash('sha256', $versions[0]), 'release_sha256' => hash('sha256', $versions[1]),
        'identical' => $versions[0] === $versions[1],
        'diff_release_to_pin' => UnifiedDiff::compare($lines($versions[1]), $lines($versions[0]), $release . '/' . $name, S::PIN . '/' . $name)];
}
$report = ['source_pin' => S::PIN, 'release' => 'php-8.5.10', 'release_revision' => $release,
    'binary_revision' => 'unverified; PHP_VERSION reports 8.5.10', 'exact_pin_executable' => false,
    'scope' => 'Seven audited translation units only; headers, build options, generated files and binary provenance are not established by this comparison.', 'files' => $rows];
S::emit('docs/8.5/source-correspondence.json', $report, $args['check'], 'Source correspondence report is stale');
S::summary(['compared_files' => count($rows), 'identical' => count(array_filter($rows, static fn(array $r): bool => $r['identical'])), 'exact_pin_executable' => false]);
