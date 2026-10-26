<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/Certification86.php';
require_once __DIR__ . '/../lib/ExactPinProvenance.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\ExactPinProvenance as P;
S::args();
$root = str_replace('\\', '/', realpath(S::ROOT)); $c = new C($root);
$out = 'docs/8.6/exact-pin-build'; $work = $root . '/.audit/php86-exact'; $source = $work . '/source';
foreach (['build.json', 'build-receipt.json'] as $file) if (file_exists($c->path($out . '/' . $file))) throw new RuntimeException('Refusing to replace sealed evidence');
$auth = $c->read($out . '/authentication.json');
$steps = ['configure' => $c->read($out . '/configure-native.json'), 'compile' => $c->read($out . '/compile-native.json')];
$artifacts = [];
foreach ($steps as $name => $step) {
    if ($step['exit_code'] !== 0 || $step['sourceCommit'] !== C::PIN) throw new RuntimeException('Unsuccessful build step: ' . $name);
    if (hash_file('sha256', $step['task']) !== $step['taskSha256']) throw new RuntimeException('Build task changed');
    foreach (['stdout', 'stderr'] as $stream) $artifacts[$step[$stream]['path']] = $step[$stream]['sha256'];
}
$errors = [...$c->verifyHashes($artifacts), ...$c->sourceErrors($c->read('tools/8.6/certification-source-lock.json'), $source)];
if ($errors) throw new RuntimeException(implode('; ', $errors));
$git = ['git', '-c', 'safe.directory=' . $source, '-C', $source];
$r = S::run([...$git, 'ls-tree', '-r', '-z', 'HEAD'], $root);
if ($r['exit_code'] !== 0) throw new RuntimeException('Cannot verify Git source tree');
$count = 0;
foreach (explode("\0", $r['output']) as $line) {
    if ($line === '') continue;
    if (!preg_match('/^\d+ blob ([a-f0-9]{40})\t(.+)$/s', $line, $m)) throw new RuntimeException('Unsupported tree entry');
    $bytes = file_get_contents($source . '/' . $m[2]);
    if (sha1('blob ' . strlen($bytes) . "\0" . $bytes) !== $m[1]
        || hash('sha256', $bytes) !== ($auth['source']['files'][$m[2]] ?? null)) throw new RuntimeException('Changed source: ' . $m[2]);
    $count++;
}
if ($count !== count($auth['source']['files'])) throw new RuntimeException('Incomplete source inventory');
$binary = $source . '/x64/Release/php.exe';
$runtime = [$source . '/x64/Release/php8.dll'];
foreach ([$binary, ...$runtime] as $file) {
    if (!is_file($file) || filemtime($file) < strtotime($steps['compile']['started'])) throw new RuntimeException('Fresh build output missing: ' . $file);
}
$generated = ['Makefile', 'config.nice.bat', 'configure.js', 'main/config.w32.h', 'main/internal_functions.c',
    'Zend/zend_language_parser.c', 'Zend/zend_language_parser.h', 'Zend/zend_language_scanner.c',
    'Zend/zend_ini_parser.c', 'Zend/zend_ini_parser.h', 'Zend/zend_ini_scanner.c'];
$generatedHashes = [];
foreach ($generated as $file) {
    if (!is_file($source . '/' . $file)) throw new RuntimeException('Generated build input missing: ' . $file);
    $generatedHashes[$source . '/' . $file] = hash_file('sha256', $source . '/' . $file);
}
$environment = $c->read($out . '/environment.json');
$toolchain = $environment['toolchain'];
foreach ($auth['sdk']['files'] as $file => $hash) $toolchain[$work . '/sdk/' . $file] = $hash;
foreach ($toolchain as $file => $hash) if (!is_file($file) || hash_file('sha256', $file) !== $hash) throw new RuntimeException('Toolchain changed: ' . $file);
$record = ['contract' => P::CONTRACT, 'status' => 'built', 'sourceCommit' => C::PIN,
    'origin' => $auth['source']['origin'], 'sourceTree' => $auth['source']['tree'], 'sourceDirectory' => $source,
    'sourceFilesChecked' => $count, 'authentication' => $out . '/authentication.json',
    'sourceLockSha256' => hash_file('sha256', $c->path('tools/8.6/certification-source-lock.json')),
    'configuration' => P::CONFIGURE, 'steps' => $steps, 'toolchain' => $toolchain,
    'generatedFiles' => $generatedHashes, 'runtimeFiles' => array_combine($runtime, array_map(static fn($f) => hash_file('sha256', $f), $runtime)),
    'binary' => $binary, 'sha256' => hash_file('sha256', $binary), 'bitForBitReproduced' => false,
    'trustBoundary' => 'Trusted local controller and host/toolchain; hashes detect alteration, not a malicious host or coordinated forgery. No independent second build or signing-key verification.'];
$c->write($out . '/build.json', $record);
$c->write($out . '/build-receipt.json', ['recordSha256' => hash('sha256', S::json($record)),
    'authenticationSha256' => hash_file('sha256', $c->path($record['authentication'])),
    'sealedAt' => gmdate('c'), 'controllerSha256' => hash_file('sha256', __FILE__)]);
echo S::json(['binary' => $binary, 'sha256' => $record['sha256'], 'sourceFilesChecked' => $count, 'certificationGranted' => false]);
