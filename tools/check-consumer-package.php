<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/ManifestValidator.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ManifestValidator;
$args = S::args(['php' => PHP_BINARY, 'composer' => 'composer']);
$php = realpath($args['php']) ?: $args['php'];
$composer = S::composer($args['composer'], $php);
$work = realpath(S::ROOT) . '/.audit/consumer-package-' . bin2hex(random_bytes(6));
mkdir($work, 0777, true);
$run = static function (array $command, string $cwd): string {
    $result = S::run($command, $cwd);
    if ($result['exit_code'] !== 0) {
        throw new RuntimeException($result['output']);
    }
    return $result['output'];
};
$run([...$composer, 'archive', '--format=zip', '--dir=' . $work, '--file=package'], S::ROOT);
$archive = new ZipArchive();
if ($archive->open($work . '/package.zip') !== true) {
    throw new RuntimeException('Unable to open consumer archive');
}
$names = [];
for ($i = 0; $i < $archive->numFiles; ++$i) {
    $names[] = $archive->getNameIndex($i);
}
$manifest = json_decode($archive->getFromName('php-grammar.json'), true, 512, JSON_THROW_ON_ERROR);
$required = ['composer.json', 'LICENSE', 'php-grammar.json', 'schema/php-grammar.schema.json', 'docs/api.md', 'docs/usage.md', 'docs/versioning.md', 'docs/grammar-conventions.md', 'bin/php-grammar'];
$root = str_replace('\\', '/', realpath(S::ROOT));
foreach (S::files($root . '/src') as $path) {
    if (str_ends_with($path, '.php')) {
        $required[] = substr($path, strlen($root) + 1);
    }
}
foreach ($manifest['versions'] as $version) {
    array_push($required, $version['grammar'], $version['documentation'], ...array_values($version['metadata'] ?? []));
}
if ($missing = array_diff($required, $names)) {
    throw new RuntimeException('Archive missing public files: ' . S::repr(S::sortedUnique($missing)));
}
foreach ($names as $name) {
    if (str_starts_with($name, '.audit/') || str_starts_with($name, '.phpunit.cache/') || str_starts_with($name, 'vendor/')) {
        throw new RuntimeException('Archive contains local scratch/cache/vendor files');
    }
    if (str_starts_with($name, '/') || str_contains($name, '\\') || str_contains($name, ':') || in_array('..', explode('/', $name), true)) {
        throw new RuntimeException('Archive contains an unsafe extraction path');
    }
}
$package = $work . '/package';
mkdir($package);
$archive->extractTo($package);
$archive->close();
$app = $work . '/app';
mkdir($app);
file_put_contents($app . '/composer.json', S::json(['name' => 'php-grammar/consumer-example',
    'repositories' => [['type' => 'path', 'url' => $package, 'options' => ['symlink' => false, 'versions' => ['php-grammar/php-grammar' => 'dev-main']]]],
    'require' => ['php-grammar/php-grammar' => '@dev']]));
$run([...$composer, 'install', '--no-dev', '--no-interaction', '--no-plugins', '--no-scripts'], $app);
file_put_contents($app . '/example.php', <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
use Composer\InstalledVersions;
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
$root = InstalledVersions::getInstallPath('php-grammar/php-grammar');
$manifest = RepositoryManifest::fromRepositoryRoot($root);
$repository = new GrammarRepository($manifest);
foreach ($manifest->versions() as $version) {
    $grammar = $repository->load($version);
    if (!$grammar->hasProduction('expression') || !$repository->productionIndex($version)->sections()) {
        throw new RuntimeException('Missing production/section metadata');
    }
    $package = $manifest->package($version);
    if (!is_file($manifest->absolutePath($package->documentationPath))) {
        throw new RuntimeException('Missing specification');
    }
}
$matcher = PhpGrammarMatcher::forManifest($manifest);
if (!$matcher->matchesRule('8.5', 'expression', '$a + 1')->matched
    || $matcher->matchesRule('8.5', 'expression', '$a +')->matched
    || !$matcher->matches('8.5', '<?php echo 1;')->matched) {
    throw new RuntimeException('Unexpected match result');
}
echo "Installed consumer workflow: PASS\n";
PHP);
echo trim($run([$php, 'example.php'], $app)) . "\n";
echo trim($run([$php, $app . '/vendor/bin/php-grammar', 'versions'], $app)) . "\n";
echo 'Archive contents and installed Composer CLI: PASS; artifacts: ' . $work . "\n";
