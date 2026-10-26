<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../lib/Certification86.php';
require_once __DIR__ . '/../lib/ExactPinProvenance.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\ExactPinProvenance as P;
use PhpGrammar\Php\Conformance\VersionBoundaryFixtureRepository;

$args = S::args(['record' => 'docs/8.6/exact-pin-build/build.json', 'php86' => '',
    'php85' => S::ROOT . '/.audit/php85/php.exe', 'distribution' => S::ROOT . '/.audit/php86/php.exe', 'report' => '']);
$root = str_replace('\\', '/', realpath(S::ROOT));
$c = new C($root);
$record = $c->read($args['record']);
$binary = $args['php86'] ?: ($record['binary'] ?? '');
$errors = (new P($root))->errors($record, $binary);
if ($errors) { echo S::json(['errors' => $errors, 'executed' => false]); exit(1); }
if ($args['report'] === '' || file_exists($c->path($args['report']))) throw new RuntimeException('Supply a new report path; existing observations are immutable');
$lock = $c->read('tools/8.6/source-lock.json');
foreach (['8.5' => $args['php85'], '8.6' => $args['distribution']] as $version => $exe) {
    if (!is_file($exe) || hash_file('sha256', $exe) !== $lock['executables'][$version]) throw new RuntimeException('Distribution hash mismatch: ' . $version);
}
$cases = []; $inputs = $c->hashes(['tools/8.6/exact-pin-differential.php', 'tools/lib/ExactPinProvenance.php']);
foreach (['pfa', 'context', 'coverage', 'final-coverage'] as $group) {
    $path = 'tests/fixtures/php/8.6/' . $group . '-cases.json';
    $data = $c->read($path); $inputs[$path] = hash_file('sha256', $c->path($path));
    foreach ($data['cases'] ?? $data as $case) {
        $stage = $case['stage'] ?? 'lint';
        if (!in_array($stage, ['lint', 'declaration-link', 'controlled-runtime'], true)) throw new RuntimeException('Unreviewed execution stage: ' . $stage);
        $file = $c->path($case['fixture']);
        $inputs[$case['fixture']] = hash_file('sha256', $file);
        if (isset($case['sha256']) && $case['sha256'] !== $inputs[$case['fixture']]) throw new RuntimeException('Fixture changed: ' . $case['id']);
        $cases[] = ['id' => $group . '/' . $case['id'], 'finding' => $case['finding'] ?? ($group === 'pfa' ? 'PHP86-001' : null),
            'file' => $file, 'stage' => $stage, 'accept85' => $case['accept85'] ?? $case['lint85'] ?? null,
            'accept86' => $case['accept86'] ?? $case['lint86'] ?? null, 'diagnostic86' => $case['diagnostic86'] ?? null,
            'output86' => $case['output86'] ?? null];
    }
}
$temporary = tempnam($root . '/.audit', 'exact-pin-probe-');
$path = 'tests/fixtures/php/8.6/reconciliation-cases.json';
$inputs[$path] = hash_file('sha256', $c->path($path));
foreach ($c->read($path)['cases'] as $case) {
    $file = $c->path($case['fixture']);
    $inputs[$case['fixture']] = hash_file('sha256', $file);
    if ($inputs[$case['fixture']] !== $case['sha256'] || ($case['executeReviewed'] && empty($case['executionReview']))) throw new RuntimeException('Unreviewed compiler probe');
    $cases[] = ['id' => 'reconciliation/' . $case['id'], 'finding' => match ($case['family']) {
        'writable' => 'PHP86-014', 'traversal' => 'PHP86-015', default => 'PHP86-013'}, 'file' => $file,
        'stage' => $case['executeReviewed'] ? 'controlled-runtime' : 'lint', 'expect' => $case['expect'], 'accept85' => null, 'accept86' => null];
}
$observations = []; $differences = []; $checks = 0;
$run = static function(array $command) use ($root): array {
    $out = tempnam($root . '/.audit', 'exact-out-'); $err = tempnam($root . '/.audit', 'exact-err-');
    try {
        $p = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, $root);
        fclose($pipes[0]); $exit = proc_close($p);
        return ['command' => $command, 'exit' => $exit, 'stdout' => file_get_contents($out), 'stderr' => file_get_contents($err)];
    } finally { unlink($out); unlink($err); }
};
try {
    $cases[] = ['id' => 'basic/declarations', 'finding' => null, 'source' => '<?php function answer(){return 42;} class Example {} echo answer();',
        'stage' => 'controlled-runtime', 'accept85' => true, 'accept86' => true, 'output86' => '42'];
    $cases[] = ['id' => 'basic/pfa-runtime', 'finding' => 'PHP86-001', 'source' => '<?php function add($a,$b){return $a+$b;} $partial=add(?,2); echo $partial(3);',
        'stage' => 'controlled-runtime', 'accept85' => false, 'accept86' => true, 'output86' => '5'];
    $cases[] = ['id' => 'basic/invalid', 'finding' => null, 'source' => '<?php function broken( {',
        'stage' => 'lint', 'accept85' => false, 'accept86' => false];
    foreach ((new VersionBoundaryFixtureRepository($root . '/tests/fixtures/version-boundaries'))->load() as $boundary) {
        if (!in_array($boundary->validationStage, ['structural', 'lint', 'declaration-link'], true)) throw new RuntimeException('Unreviewed boundary stage');
        if ($boundary->validationStage === 'declaration-link') {
            $reviewed = array_filter($cases, static fn(array $case): bool => isset($case['file']) && $case['stage'] === 'declaration-link'
                && $case['finding'] === $boundary->finding && file_get_contents($case['file']) === $boundary->source);
            if (count($reviewed) !== 1) throw new RuntimeException('Boundary execution is not uniquely reviewed');
        }
        // Structural-only expectations do not establish compiler acceptance.
        $cases[] = ['id' => 'boundary/' . $boundary->name, 'finding' => $boundary->finding, 'source' => $boundary->source,
            'stage' => $boundary->validationStage === 'structural' ? 'lint' : $boundary->validationStage,
            'accept85' => $boundary->validationStage === 'structural' ? null : $boundary->shouldAccept('8.5'),
            'accept86' => $boundary->validationStage === 'structural' ? null : $boundary->shouldAccept('8.6')];
    }
    $interpreters = ['8.5' => $args['php85'], 'distribution' => $args['distribution'], 'exact' => $binary];
    $metadata = [];
    foreach ($interpreters as $label => $exe) {
        $metadata[$label] = ['sha256' => hash_file('sha256', $exe), 'version' => $run([$exe, '-n', '-v']),
            'configuration' => $run([$exe, '-n', '-r', 'echo json_encode(["version"=>PHP_VERSION,"os"=>PHP_OS,"bits"=>PHP_INT_SIZE*8,"zts"=>PHP_ZTS,"debug"=>PHP_DEBUG,"sapi"=>PHP_SAPI,"extensions"=>get_loaded_extensions()]);'])];
        foreach ([0, 1] as $short) {
            $probe = $run([$exe, '-n', '-d', 'short_open_tag=' . $short, '-r', 'echo token_name(token_get_all("<? echo 1; ?>")[0][0]);']);
            $metadata[$label]['shortTagTokenization'][$short] = $probe;
            $checks++;
            if ($probe['exit'] !== 0 || $probe['stdout'] !== ($short ? 'T_OPEN_TAG' : 'T_INLINE_HTML')) $errors[] = 'Tag/tokenization ' . $label . '/' . $short;
        }
    }
    foreach ($cases as $case) {
        if (isset($case['source'])) { file_put_contents($temporary, $case['source']); $file = $temporary; }
        else $file = $case['file'];
        $row = ['id' => $case['id'], 'finding' => $case['finding'], 'stage' => $case['stage'], 'fixtureSha256' => hash_file('sha256', $file), 'results' => []];
        foreach ($interpreters as $label => $exe) {
            $prefix = [$exe, '-n', '-d', 'short_open_tag=1', '-d', 'zend.multibyte=0', '-d', 'error_reporting=-1', '-d', 'max_execution_time=5', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];
            $lint = $run([...$prefix, '-l', $file]);
            $result = $case['stage'] === 'lint' || (isset($case['expect']) && $lint['exit'] !== 0) ? $lint : $run([...$prefix, $file]);
            $row['results'][$label] = ['lint' => $lint, 'stageResult' => $result];
            if (isset($case['expect'])) {
                $expectedStages = $case['expect'][$label === '8.5' ? '8.5' : '8.6'];
                foreach (['lint' => $lint, 'execution' => $result] as $stage => $observed) {
                    $expectedStage = $expectedStages[$stage];
                    if ($expectedStage === null) continue;
                    $checks++;
                    if ($observed['exit'] !== $expectedStage['exit']) $errors[] = $case['id'] . '/' . $label . '/' . $stage . ': exit differs';
                    if (isset($expectedStage['contains'])) { $checks++; if (!str_contains($observed['stdout'] . $observed['stderr'], $expectedStage['contains'])) $errors[] = $case['id'] . '/' . $label . '/' . $stage . ': diagnostic differs'; }
                    if (isset($expectedStage['stdout'])) { $checks++; if (trim($observed['stdout']) !== $expectedStage['stdout']) $errors[] = $case['id'] . '/' . $label . '/' . $stage . ': stdout differs'; }
                }
            }
            $expected = $case[$label === '8.5' ? 'accept85' : 'accept86'];
            if ($expected !== null) { $checks++; if (($result['exit'] === 0) !== $expected) $errors[] = $case['id'] . '/' . $label . ': acceptance differs'; }
            if ($label !== '8.5' && !empty($case['diagnostic86'])) { $checks++; if (stripos($result['stdout'] . $result['stderr'], $case['diagnostic86']) === false) $errors[] = $case['id'] . '/' . $label . ': diagnostic differs'; }
            if ($label !== '8.5' && isset($case['output86'])) { $checks++; if (trim($result['stdout'] . $result['stderr']) !== $case['output86']) $errors[] = $case['id'] . '/' . $label . ': output differs'; }
        }
        $a = $row['results']['distribution']['stageResult']; $b = $row['results']['exact']['stageResult'];
        unset($a['command'], $b['command']);
        if ($a !== $b) $differences[] = $case['id'];
        $observations[] = $row;
    }
    $report = ['scope' => 'Finite stage-specific comparison; no finding closure or certification', 'exactSourceVerified' => true, 'record' => $args['record'],
        'recordSha256' => hash_file('sha256', $c->path($args['record'])), 'interpreters' => $metadata,
        'inputs' => $inputs, 'checks' => $checks, 'observations' => $observations, 'distributionDifferences' => $differences,
        'errors' => $errors, 'openFindings' => ['PHP86-013', 'PHP86-014', 'PHP86-015', 'CA86-001']];
    $c->write($args['report'], $report);
    echo S::json(['checks' => $checks, 'cases' => count($cases), 'differences' => $differences, 'errors' => $errors]);
} finally { unlink($temporary); }
exit($errors === [] ? 0 : 1);
