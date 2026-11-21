<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/GaLocalObligations.php';
use PhpGrammar\Tools\Support as S;
$root = str_replace('\\', '/', realpath(S::ROOT));
$path = 'docs/8.6/ga-certification-remediation/local-obligations-v1.json';
$r = (new \PhpGrammar\Tools\GaLocalObligations($root))->derive();
if (($argv[1] ?? '') === '--generate') {
    if (file_exists($root . '/' . $path)) throw new RuntimeException('Refusing overwrite');
    file_put_contents($root . '/' . $path, S::json($r));
} elseif (($argv[1] ?? '') === '--check') {
    if (file_get_contents($root . '/' . $path) !== S::json($r)) throw new RuntimeException('Stale or altered local source evidence');
} else throw new RuntimeException('Select --generate or --check');
echo S::json(['errors' => $r['errors'], 'parserProductions' => $r['parser']['productions'], 'parserAlternatives' => $r['parser']['alternatives'],
    'scannerRules' => $r['scanner']['ruleCount'], 'sourceQueue' => $r['currentDiscoveryCounts'], 'certificationGranted' => false]);
exit($r['errors'] === [] ? 0 : 1);
