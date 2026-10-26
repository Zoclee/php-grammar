<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/LocalSourceContextEvidence.php';
use PhpGrammar\Tools\{LocalSourceContextEvidence as E, Support};
try {
    $args = Support::args(['report' => E::DIR . '/observations.json'], ['observe', 'check']);
    if ((int)$args['observe'] + (int)$args['check'] !== 1) throw new RuntimeException('Select exactly one of --observe or --check');
    $e = new E(str_replace('\\', '/', realpath(__DIR__ . '/../..')));
    $errors = $args['observe'] ? $e->observe($args['report']) : $e->check($args['report']);
    echo Support::json(['contract' => E::CONTRACT, 'errors' => $errors, 'certificationGranted' => false]);
    exit($errors ? 1 : 0);
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
