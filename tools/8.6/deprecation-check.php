<?php
declare(strict_types=1);

// Focused PHP86-010 paired diagnostics; this is not a release gate.
$root = dirname(__DIR__, 2);
$options = getopt('', ['php85:', 'php86:', 'sources85:', 'sources86:', 'report:']);
foreach (['php85', 'php86', 'sources85', 'sources86', 'report'] as $key) {
    if (!isset($options[$key])) {
        fwrite(STDERR, "Required: --php85 --php86 --sources85 --sources86 --report\n");
        exit(2);
    }
}
$data = json_decode(file_get_contents($root.'/tests/fixtures/php/8.6/deprecation-cases.json'), true, 512, JSON_THROW_ON_ERROR);
$failures = [];
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks, &$failures): void {
    ++$checks;
    if (!$ok) $failures[] = $label;
};
$run = static function (array $command): array {
    $process = proc_open($command, [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start PHP');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit'=>proc_close($process), 'output'=>$output.$error];
};
$observations = [];
$check(count(array_unique(array_column($data['cases'], 'id'))) === count($data['cases']), 'unique case IDs');
$interpreters = [];
$temporary = tempnam(sys_get_temp_dir(), 'php86-deprecation-');
if ($temporary === false) throw new RuntimeException('Cannot create probe file');
try {
    foreach (['8.5'=>'85', '8.6'=>'86'] as $version=>$suffix) {
        $binary = realpath($options['php'.$suffix]);
        $sources = realpath($options['sources'.$suffix]);
        if ($binary === false || $sources === false) throw new RuntimeException('Missing interpreter/source tree');
        foreach ($data['sourceHashes'][$version] as $path=>$hash) {
            $actual = is_file($sources.'/'.$path) ? hash_file('sha256', $sources.'/'.$path) : null;
            $check($actual === $hash, "$version source $path");
        }
        $interpreters[$version] = ['sha256'=>hash_file('sha256', $binary), 'version'=>$run([$binary, '-n', '-v']), 'exactPinBuildVerified'=>false];
        $check(str_contains($interpreters[$version]['version']['output'], 'PHP '.$version.'.'), "$version interpreter version");
        foreach ($data['cases'] as $case) {
            file_put_contents($temporary, $case['source']);
            foreach (['lint', 'execution'] as $stage) {
                $command = [$binary, '-n', '-d', 'display_errors=1', '-d', 'log_errors=0', '-d', 'error_reporting=-1'];
                if ($stage === 'lint') $command[] = '-l';
                $command[] = $temporary;
                $result = $run($command);
                $result['output'] = str_replace($temporary, '<probe>', $result['output']);
                $expected = $case['expected'][$version][$stage];
                $label = $case['id'].' '.$version.' '.$stage;
                $check(($result['exit'] === 0) === $case['accepted'], $label.' acceptance');
                $check(str_contains($result['output'], 'Deprecated:') === ($expected !== ''), $label.' deprecation presence');
                if ($expected !== '') $check(str_contains($result['output'], $expected), $label.' diagnostic');
                if (!$case['accepted']) $check(str_contains($result['output'], 'Parse error:'), $label.' parse rejection');
                $observations[] = ['id'=>$case['id'], 'finding'=>'PHP86-010', 'version'=>$version, 'stage'=>$stage,
                    'sourceSha256'=>hash('sha256', $case['source']), 'result'=>$result];
            }
        }
    }
} finally {
    unlink($temporary);
}
$report = ['finding'=>'PHP86-010', 'sourcePins'=>$data['sourcePins'], 'caseManifestSha256'=>hash_file('sha256', $root.'/tests/fixtures/php/8.6/deprecation-cases.json'),
    'profile'=>'-n; display_errors=1; log_errors=0; error_reporting=-1; lint and isolated execution; no custom error handler or opcache',
    'interpreters'=>$interpreters, 'checks'=>$checks, 'failures'=>$failures, 'observations'=>$observations];
file_put_contents($options['report'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
echo $checks, ' checks; ', count($observations), ' observations; ', count($failures), " failures\n";
foreach ($failures as $failure) echo $failure, "\n";
exit($failures === [] ? 0 : 1);
