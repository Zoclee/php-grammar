<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../lib/CertificationCurrentRun.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\CertificationCurrent as E;
use PhpGrammar\Tools\CertificationCurrentRun as R;
use PhpGrammar\Tools\Certification86 as C;
$root = str_replace('\\', '/', realpath(S::ROOT));
$args = S::args(['contract' => '', 'revision' => '', 'report' => '', 'profile' => '', 'legacyProfile' => '',
    'preservation-contract' => \PhpGrammar\Tools\Preservation86::CONTRACT,
    'php85' => $root . '/.audit/php85/php.exe', 'php86' => $root . '/.audit/php86/php.exe', 'exact' => $root . '/.audit/php86-exact/source/x64/Release/php.exe',
    'sources85' => $root . '/.audit/php-php-src-7a4c627', 'sources86' => $root . '/.audit/php-php-src-f23b4c2',
    'source-cache' => $root . '/.audit/phase7-sources', 'composer' => 'composer', 'compatibility-report' => '',
    'context-report' => 'docs/8.6/exact-pin-context-integration/exact.json',
    'historical-snapshot' => $root . '/.audit/phase7c1-historical', 'historical-supplement' => $root . '/.audit/phase7b-certification'], ['generate', 'check', 'claims', 'inventory']);
try {
    if ($args['contract'] !== E::CONTRACT) throw new RuntimeException('Explicit --contract ' . E::CONTRACT . ' required');
    if (count(array_filter(array_intersect_key($args, array_flip(['generate', 'check', 'claims', 'inventory'])))) !== 1) throw new RuntimeException('Select exactly one operation');
    if ($args['inventory']) {
        echo S::json(['contract' => E::CONTRACT, 'originalGates' => C::gates(), 'additionalPrerequisites' => ['historical-artifact-integrity85', 'current-compatibility85'], 'totalObligations' => 38, 'profiles' => E::profiles()]); exit(0);
    }
    $runner = new R($root);
    if ($args['generate']) {
        $config = array_diff_key($args, array_flip(['contract', 'revision', 'report', 'generate', 'check', 'claims', 'inventory']));
        foreach (['php85', 'php86', 'exact', 'sources85', 'sources86', 'source-cache', 'historical-snapshot', 'historical-supplement'] as $key) {
            $resolved = realpath($config[$key]);
            if ($resolved === false) throw new RuntimeException('Missing selected dependency: ' . $key);
            $config[$key] = str_replace('\\', '/', $resolved);
        }
        $path = $runner->generate($args['revision'], $config);
        echo S::json(['report' => $path, 'independentlyVerified' => false, 'certificationGranted' => false, 'next' => 'Use --check separately']); exit(0);
    }
    $errors = $runner->verify($args['report']);
    if ($args['claims'] && $errors === []) $errors = E::claimErrors((new C($root))->read($args['report']));
    echo S::json(['contract' => E::CONTRACT, 'operation' => $args['claims'] ? 'claims' : 'check', 'errors' => $errors, 'certificationGranted' => false]);
    exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()], 'certificationGranted' => false]); exit(1); }
