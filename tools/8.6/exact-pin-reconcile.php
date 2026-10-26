<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../lib/Certification86.php';
require_once __DIR__ . '/../lib/ExactPinProvenance.php';
require_once __DIR__ . '/../lib/ExactPinReconciliation.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\ExactPinProvenance as P;
use PhpGrammar\Tools\ExactPinReconciliation as R;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Php\Lexing\Lexer;
$args = S::args(['report' => '', 'additional-cases' => '']);
$root = str_replace('\\', '/', realpath(S::ROOT)); $c = new C($root);
if ($args['report'] === '' || file_exists($c->path($args['report']))) throw new RuntimeException('Require a new report path');
$build = $c->read('docs/8.6/exact-pin-build/build.json');
$errors = (new P($root))->errors($build, $build['binary']);
if ($errors) throw new RuntimeException(implode('; ', $errors));
$casePath = 'tests/fixtures/php/8.6/reconciliation-cases.json';
$cases = $c->read($casePath)['cases'];
if ($args['additional-cases'] !== '') $cases = [...$cases, ...$c->read($args['additional-cases'])['cases']];
$inputs = $c->hashes([$casePath, 'tools/8.6/exact-pin-reconcile.php', 'tools/lib/ExactPinReconciliation.php',
    'tools/lib/ExactPinProvenance.php', 'docs/8.6/exact-pin-build/build.json', 'docs/8.6/compiler-contextual-groups.json']);
if ($args['additional-cases'] !== '') $inputs += $c->hashes([$args['additional-cases']]);
$matcher = PhpGrammarMatcher::forManifest(RepositoryManifest::fromRepositoryRoot($root));
$run = static function(array $command) use ($root): array {
    $out = tempnam($root . '/.audit', 'c4-out-'); $err = tempnam($root . '/.audit', 'c4-err-');
    try {
        $p = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, $root);
        if (!is_resource($p)) throw new RuntimeException('Cannot launch reviewed probe');
        fclose($pipes[0]); $exit = proc_close($p);
        return ['command' => $command, 'exit' => $exit, 'stdout' => file_get_contents($out), 'stderr' => file_get_contents($err)];
    } finally { unlink($out); unlink($err); }
};
$checks = 0; $observations = []; $invocations = ['lint' => 0, 'execution' => 0, 'opcode' => 0];
$binaries = ['8.5' => $root . '/.audit/php85/php.exe', '8.6' => $build['binary']];
if (hash_file('sha256', $binaries['8.5']) !== $c->read('tools/8.6/source-lock.json')['executables']['8.5']) throw new RuntimeException('PHP 8.5 distribution hash changed');
foreach ($cases as $case) {
    $file = $c->path($case['fixture']); $source = file_get_contents($file);
    if (hash('sha256', $source) !== $case['sha256']) throw new RuntimeException('Unreviewed fixture bytes: ' . $case['id']);
    if ($case['executeReviewed'] && empty($case['executionReview'])) throw new RuntimeException('Missing execution review');
    $inputs[$case['fixture']] = $case['sha256'];
    foreach ($binaries as $version => $binary) {
        $expected = $case['expect'][$version];
        $tokens = Lexer::forVersion($version)->tokenize($source);
        $lexical = implode('', array_map(static fn($t) => $t->lexeme, $tokens->all())) === $source;
        $structural = $matcher->matches($version, $source)->matched;
        $checks += 2;
        if ($lexical !== $expected['lexerAccepted']) $errors[] = $case['id'] . '/' . $version . '/lexical';
        if ($structural !== $expected['structural']) $errors[] = $case['id'] . '/' . $version . '/structural';
        $prefix = [$binary, '-n', '-d', 'zend.multibyte=0', '-d', 'short_open_tag=1', '-d', 'error_reporting=-1',
            '-d', 'memory_limit=64M', '-d', 'max_execution_time=5', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];
        $lint = $run([...$prefix, '-l', $file]); $invocations['lint']++; $checks++;
        foreach (R::resultErrors($lint, $expected['lint']) as $error) $errors[] = $case['id'] . '/' . $version . '/lint/' . $error;
        $execution = null;
        if ($case['executeReviewed'] && $lint['exit'] === 0) {
            $execution = $run([...$prefix, $file]); $invocations['execution']++; $checks++;
            if ($expected['execution'] === null) $errors[] = $case['id'] . '/' . $version . '/unplanned-execution';
            else foreach (R::resultErrors($execution, $expected['execution']) as $error) $errors[] = $case['id'] . '/' . $version . '/execution/' . $error;
        } elseif ($expected['execution'] !== null) $errors[] = $case['id'] . '/' . $version . '/missing-execution';
        $dump = ['stdout' => '', 'stderr' => '', 'exit' => null];
        if ($version === '8.6') {
            // Separate non-executing diagnostic profile; optimizer and JIT disabled.
            $dump = $run([...$prefix, '-d', 'opcache.enable_cli=1', '-d', 'opcache.optimization_level=0',
                '-d', 'opcache.opt_debug_level=65536', '-d', 'opcache.file_update_protection=0', '-l', $file]);
            $invocations['opcode']++; $checks++;
            if ($dump['exit'] !== $lint['exit']) $errors[] = $case['id'] . '/opcode-profile-acceptance-difference';
            if ($lint['exit'] === 0 && R::opcodeLines($dump['stdout'] . $dump['stderr']) === []) $errors[] = $case['id'] . '/missing-opcode-dump';
        }
        $observations[] = ['id' => $case['id'], 'family' => $case['family'], 'groups' => $case['groups'], 'version' => $version,
            'fixture' => $case['fixture'], 'input' => $source, 'sha256' => $case['sha256'], 'expected' => $expected,
            'lexical' => $lexical, 'structural' => $structural, 'lint' => $lint, 'execution' => $execution,
            'executionStage' => $execution === null ? null : $case['executionStage'], 'opcodeDiagnostic' => $dump,
            'opcodes' => R::opcodeLines($dump['stdout'] . $dump['stderr']), 'stageSummary' => R::stageSummary($lint, $execution, $dump)];
    }
}
$c->write($args['report'], ['phase' => '7C.4', 'sourceCommit' => C::PIN, 'exactSourceVerified' => true,
    'interpreters' => array_map(static fn($binary) => ['path' => $binary, 'sha256' => hash_file('sha256', $binary)], $binaries),
    'coreDll' => $build['runtimeFiles'], 'buildRecordSha256' => hash_file('sha256', $c->path('docs/8.6/exact-pin-build/build.json')),
    'inputs' => $inputs, 'cases' => count($cases), 'checks' => $checks, 'invocations' => $invocations,
    'observations' => $observations, 'errors' => $errors,
    'limits' => ['php -l combines Zend parsing, contextual compilation and emission.', 'Repository structural matching is not an independent Zend parser oracle.',
        'The separate OPcache dump profile observes pre-optimizer instructions without execution; no optimized/JIT/debug-build matrix.', 'No language-intent or completeness conclusion follows from stage success.']]);
echo S::json(['cases' => count($cases), 'checks' => $checks, 'invocations' => $invocations, 'errors' => $errors]);
exit($errors ? 1 : 0);
