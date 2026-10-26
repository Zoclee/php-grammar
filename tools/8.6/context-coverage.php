<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/ContextCoverageEvidence.php';
use PhpGrammar\Tools\{Support as S, Certification86 as C, ContextCoverageEvidence as E};
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
$args = S::args(['report' => ''], ['generate', 'check']);
try {
    if ((int)$args['generate'] + (int)$args['check'] !== 1 || $args['report'] === '') throw new RuntimeException('Select --generate or --check and a new/report path');
    $c = new C($root); $e = new E($root);
    if ($args['generate']) {
        if (is_file($c->path($args['report'])) || is_file($c->path($args['report'] . '.receipt.json'))) throw new RuntimeException('Never overwrite evidence');
        $report = $e->generate(); $c->write($args['report'], $report);
        $c->write($args['report'] . '.receipt.json', ['sha256' => hash_file('sha256', $c->path($args['report'])), 'scope' => 'Reviewed local controller custody; not a signature.']);
        $errors = $report['errors'];
    } else $errors = $e->verify($args['report']);
    echo S::json(['contract' => E::CONTRACT, 'errors' => $errors, 'certificationGranted' => false]); exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()], 'certificationGranted' => false]); exit(1); }
