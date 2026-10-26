<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../lib/ManifestValidator.php';
require_once __DIR__ . '/../lib/Certification86.php';

use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\Support as S;

$args = S::args(['php85' => S::ROOT . '/.audit/php85/php.exe', 'php86' => S::ROOT . '/.audit/php86/php.exe',
    'sources85' => S::ROOT . '/.audit/php-php-src-7a4c627', 'sources86' => S::ROOT . '/.audit/php-php-src-f23b4c2',
    'source-cache' => S::ROOT . '/.audit/phase7-sources', 'composer' => 'composer',
    'report' => C::REPORT, 'phpunit-extension' => '', 'contract' => 'legacy', 'compatibility-report' => '',
    'historical-snapshot' => S::ROOT . '/.audit/phase7c1-historical',
    'historical-supplement' => S::ROOT . '/.audit/phase7b-certification',
    'provenance-record' => 'docs/8.6/exact-pin-build/build.json', 'context-profile' => '', 'context-report' => ''], ['prepare', 'check', 'claims', 'run', 'inventory', 'provenance', 'context-evidence']);
$c = new C(realpath(S::ROOT));
chdir($c->root);
$modes = array_filter(array_intersect_key($args, array_flip(['prepare', 'check', 'claims', 'run', 'inventory', 'provenance', 'context-evidence'])));
if (count($modes) !== 1) throw new RuntimeException('Select exactly one mode: --prepare, --check, --claims, --run, --inventory, --provenance, --context-evidence');
if ($args['context-evidence']) {
    if ($args['contract'] !== 'legacy') throw new RuntimeException('Context inspection does not select a certification contract');
    require_once __DIR__ . '/../lib/ContextEvidence.php';
    $errors = (new \PhpGrammar\Tools\ContextEvidence(str_replace('\\', '/', $c->root)))->verify($args['context-report'], $args['context-profile']);
    echo S::json(['mode' => 'context-evidence', 'contextEvidenceVerified' => $errors === [], 'errors' => $errors,
        'certificationGranted' => false, 'blockers' => C::BLOCKERS,
        'note' => 'Additive context prerequisite inspection only; all mandatory release gates remain required.']);
    exit($errors === [] ? 0 : 1);
}
if ($args['context-profile'] !== '' || $args['context-report'] !== '') throw new RuntimeException('Context options require --context-evidence');
if ($args['provenance']) {
    if ($args['contract'] !== 'legacy') throw new RuntimeException('Provenance inspection does not select a certification contract');
    $errors = $c->provenanceErrors($c->read($args['provenance-record']), $args['php86']);
    echo S::json(['mode' => 'provenance', 'exactSourceVerified' => $errors === [], 'errors' => $errors,
        'certificationGranted' => false, 'note' => 'Standalone build evidence only; mandatory gates and finding dispositions are unchanged.']);
    exit($errors === [] ? 0 : 1);
}
if ($args['contract'] === 'historical-plus-current-v1') { require __DIR__ . '/../evidence/hybrid-check.php'; exit; }
if ($args['contract'] !== 'legacy') throw new RuntimeException('Unknown certification contract');
if ($args['inventory']) {
    echo S::json(['schema' => 1, 'version' => '8.6', 'categories' => C::CATEGORIES, 'gates' => C::gates()]);
    exit(0);
}
if ($args['check'] || $args['claims']) {
    $report = $c->read($args['report']);
    $errors = $c->check($report);
    if ($args['claims']) $errors = [...$errors, ...$c->claimErrors($report)];
    echo S::json(['mode' => $args['claims'] ? 'claims' : 'check', 'errors' => $errors,
        'certificationGranted' => false, 'note' => 'A valid preparation ledger is not certification.']);
    exit($errors === [] ? 0 : 1);
}
// A full release attempt is a separate mode and cannot bypass preparation's
// prerequisites. This phase cannot award certification, even with forged passes.
if ($args['run']) {
    $report = $c->read($args['report']);
    $errors = [...$c->check($report), ...$c->claimErrors($report)];
    echo S::json(['mode' => 'run', 'status' => 'blocked', 'errors' => $errors, 'certificationGranted' => false]);
    exit(1);
}

$startingInputs = $c->inputs();
$php = realpath($args['php85']) ?: $args['php85'];
$php86 = realpath($args['php86']) ?: $args['php86'];
$p = [$php, '-n', '-d', 'zend.multibyte=0', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];
$unit = $p;
$extension = $args['phpunit-extension'];
if ($extension === '' && PHP_OS_FAMILY === 'Windows') $extension = dirname($php) . '/ext/php_mbstring.dll';
if ($extension !== '') array_push($unit, '-d', 'extension=' . $extension);
array_push($unit, 'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress');
$pair = ['--php85=' . $php, '--php86=' . $php86];
$sources = ['--sources85=' . $args['sources85'], '--sources86=' . $args['sources86']];
$out = C::OUTPUT;
if (!is_dir($c->path($out . '/logs'))) mkdir($c->path($out . '/logs'), 0777, true);
$composer = S::composer($args['composer'], $php);
if ($composer[0] === $php) {
    $composer = [...$p, ...array_slice($composer, 1)];
    if (PHP_OS_FAMILY === 'Windows' && is_file(dirname($php) . '/ext/php_openssl.dll')) {
        array_splice($composer, count($p), 0, ['-d', 'extension=' . dirname($php) . '/ext/php_openssl.dll']);
    }
}
$commands = [
    'test-manifest' => [...$unit, '--filter', 'ToolManifestTest'],
    'test-integration' => [...$unit, '--filter', 'Php86IntegrationTest|VersionPackageConsistencyTest|RepositoryManifestTest'],
    'test-fixtures' => [...$unit, '--filter', 'PhpConformanceFixtureTest'],
    'test-compiler' => [...$unit, '--filter', 'Php86CompilerContextualTest'],
    'test-consumer' => [...$unit, '--filter', 'Consumer|ConformanceVersionSupportTest'],
    'test-certification' => [...$unit, '--filter', 'Certification86Test'],
    'test-all' => $unit,
    'boundaries' => [...$p, 'tools/8.6/certification-boundaries.php', '--php86', $php86, '--report', $out . '/boundaries.json'],
    'integration' => [...$p, 'tools/8.6/integration-check.php', ...$pair, ...$sources, '--report=' . $out . '/integration.json'],
    'pfa' => [...$p, 'tools/8.6/pfa-check.php', ...$pair, '--sources=' . $args['sources86'], '--report=' . $out . '/pfa.json'],
    'context' => [...$p, 'tools/8.6/context-check.php', ...$pair, ...$sources, '--report=' . $out . '/context.json'],
    'deprecation' => [...$p, 'tools/8.6/deprecation-check.php', ...$pair, ...$sources, '--report=' . $out . '/deprecation.json'],
    'coverage' => [...$p, 'tools/8.6/final-coverage-expansion.php', ...$pair, ...$sources,
        '--report=' . $out . '/coverage-oracles.json', '--coverage=' . $out . '/coverage.json'],
    'audit' => [...$p, 'tools/8.6/source-completeness-audit.php', ...$sources, '--check'],
    'traceability' => [...$p, 'tools/8.6/change-traceability.php', '--report=' . $out . '/traceability.json'],
    'historical' => [...$p, 'tools/8.5/certification.php', '--source-cache', $args['source-cache'], '--check'],
    'composer' => [...$composer, 'validate', '--strict'],
];
foreach (['manifest', 'source', 'preservation', 'blockers', 'freshness', 'documentation'] as $kind) {
    $commands[$kind] = [...$p, 'tools/8.6/certification-gate.php', '--gate', $kind, '--sources86', $args['sources86']];
}
$interpreters = [];
foreach (['8.5' => $php, '8.6' => $php86] as $version => $binary) {
    $command = [$binary, '-n', '-d', 'zend.multibyte=0', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0', '-i'];
    $r = S::run($command, $c->root);
    // phpinfo also prints environment variables. Persist only an allowlist of
    // build/runtime fields, never arbitrary process environment or credentials.
    $fields = ['PHP Version', 'Build Date', 'Build System', 'Build Provider', 'Compiler', 'Architecture',
        'Configure Command', 'Server API', 'Thread Safety', 'Debug Build', 'PHP Extension Build',
        'Zend Extension Build', 'Loaded Configuration File', 'Scan this dir for additional .ini files',
        'Additional .ini files parsed', 'zend.multibyte', 'short_open_tag', 'opcache.enable_cli', 'opcache.jit'];
    $metadata = [];
    foreach (preg_split('/\r?\n/', $r['output']) as $line) {
        $key = explode(' => ', $line, 2)[0];
        if (in_array($key, $fields, true)) $metadata[] = $line;
    }
    $r['output'] = implode("\n", $metadata) . "\n";
    file_put_contents($c->path($out . '/logs/interpreter-' . $version . '.log'), $r['output']);
    $interpreters[$version] = ['path' => $binary, 'sha256' => is_file($binary) ? hash_file('sha256', $binary) : null,
        'command' => $command, 'exit_code' => $r['exit_code'], 'metadata' => $r['output'],
        'sourceCommit' => null, 'exactPinExecutableVerified' => false,
        'difference' => 'Distribution binary; build ancestry, generated sources and build configuration not attested to the normative pin.'];
}
$c->write($out . '/interpreters.json', $interpreters);
$gates = [];
foreach (C::gates() as $id => [$kind, $obligation]) {
    echo 'Checking ' . $id . "\n";
    if (str_starts_with($kind, 'blocked:') || $kind === 'provenance') {
        $gates[$id] = ['obligation' => $obligation, 'status' => 'blocked', 'exit_code' => null,
            'reason' => $kind === 'provenance'
                ? 'C6-PROVENANCE: ' . implode('; ', $c->provenanceErrors($interpreters['8.6'], $php86))
                : substr($kind, 8) . ': Phase 6 mandatory finding remains unresolved.'];
        continue;
    }
    $command = $commands[$kind];
    $log = $out . '/logs/' . $id . '.log';
    try {
        $r = S::run($command, $c->root, $c->path($log));
    } catch (Throwable $e) {
        $r = ['exit_code' => 127, 'output' => $e->getMessage()];
        file_put_contents($c->path($log), $r['output']);
    }
    $gates[$id] = ['obligation' => $obligation, 'status' => $r['exit_code'] === 0 ? 'passed' : 'failed',
        'exit_code' => $r['exit_code'], 'command' => $command, 'log' => $log, 'logSha256' => hash_file('sha256', $c->path($log))];
    $c->write($out . '/logs/' . $id . '.json', $gates[$id]);
    echo $id . ': ' . strtoupper($gates[$id]['status']) . "\n";
    flush();
}
$ledger = $c->read('docs/8.6/source-completeness-ledger.json');
$coverage = is_file($c->path($out . '/coverage.json')) ? $c->read($out . '/coverage.json') : [];
$areas = [];
foreach (C::CATEGORIES as $category) {
    $areas[] = ['area' => $category, 'categories' => [$category => 'bounded evidence; mandatory closure obligations remain'],
        'evidence' => ['docs/8.6/source-completeness-ledger.json', C::OUTPUT],
        'limitation' => 'Command passes validate only their stated matrices; see each gate obligation and blocker disposition.'];
}
$fixtures = [];
foreach (['valid', 'invalid', 'contextual-invalid', 'known-discrepancies/valid', 'runtime'] as $kind) $fixtures[$kind] = count(glob('tests/fixtures/php/8.6/' . $kind . '/*.php'));
$artifactPaths = [];
foreach (S::files($c->path($out)) as $file) $artifactPaths[] = substr($file, strlen(str_replace('\\', '/', $c->root)) + 1);
$report = ['schema' => 1, 'phase' => '7A', 'version' => '8.6', 'source_pin' => C::PIN,
    'claim' => 'Independent certification preparation only; PHP 8.6 remains draft. No certification or completeness claim.',
    'grammar_complete' => false, 'whole_source_validator_complete' => false, 'exhaustively_equivalent' => false,
    'certificationGranted' => false, 'exactPinExecutableVerified' => false,
    'profile' => 'Byte source, zend.multibyte=0, -n, no CLI opcache/JIT; explicit short-tag profiles in paired matrices. Lint, declaration-link and controlled-runtime stages are distinct.',
    'categories' => C::CATEGORIES, 'areas' => $areas, 'grammar_coverage' => $coverage['current'] ?? null,
    'coverage_classifications' => array_count_values(array_column($coverage['remaining'] ?? [], 'classification')),
    'remaining_coverage' => $coverage['remaining'] ?? [], 'fixtures' => $fixtures,
    'parser' => ['productions' => $ledger['parser']['productions'], 'alternatives' => $ledger['parser']['alternatives'],
        'evidence' => 'docs/8.6/source-completeness-ledger.json#/parser', 'limit' => 'Production-family mapping, not branch-equivalence proof.'],
    'scanner' => ['rules' => count($ledger['scanner']['rules']), 'states' => $ledger['scanner']['states'],
        'evidence' => 'docs/8.6/source-completeness-ledger.json#/scanner', 'limits' => $ledger['scanner']['limitations']],
    'restriction_inventory' => $ledger['contextual']['restrictions'],
    'blockers' => $c->read('docs/8.6/source-audit-dispositions.json')['blockers'],
    'interpreters' => $interpreters, 'gates' => $gates,
    'sourceDirectories' => ['8.5' => realpath($args['sources85']) ?: $args['sources85'], '8.6' => realpath($args['sources86']) ?: $args['sources86']],
    'artifacts' => $c->hashes($artifactPaths), 'inputs' => $startingInputs,
    'generation' => ['command' => $argv, 'phpVersion' => PHP_VERSION,
        'note' => 'Generation is not validation; run --check separately, then --claims to evaluate eligibility. Failed/blocked commands remain recorded.']];
$suiteLog = file_get_contents($c->path($out . '/logs/full-regression.log'));
preg_match('/OK \((\d+) tests, (\d+) assertions\)/', $suiteLog, $totals);
$report['validation'] = ['tests' => isset($totals[1]) ? (int) $totals[1] : null,
    'assertions' => isset($totals[2]) ? (int) $totals[2] : null,
    'exit_code' => $gates['full-regression']['exit_code'], 'log' => $out . '/logs/full-regression.log'];
$unchanged = $startingInputs === $c->inputs();
$report['inputsUnchangedDuringPreparation'] = $unchanged;
$c->write($args['report'], $report);
echo S::json(['report' => $args['report'], 'gates' => array_count_values(array_column($gates, 'status')),
    'certificationGranted' => false, 'inputsUnchangedDuringPreparation' => $unchanged,
    'next' => $unchanged ? 'Run --check independently. --claims must refuse certification.' : 'Inputs changed during preparation; review and rerun before checking.']);
// Preparation reports faithfully include failing gates. Generation success is
// intentionally distinct from the nonzero --claims/--run certification verdict.
exit($unchanged ? 0 : 1);
