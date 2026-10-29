<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/UpstreamResolutionFreshness.php';
require_once __DIR__ . '/../lib/Preservation86.php';
use PhpGrammar\Tools\{Support as S, Certification86 as C, UpstreamResolutionFreshness as E};
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
$args = S::args(['report' => E::ROOT . '/transition.json'], ['generate', 'check']);
try {
    if ((int)$args['generate'] + (int)$args['check'] !== 1) throw new RuntimeException('Select --generate or --check');
    $e = new E($root); $c = new C($root);
    $errors = $e->probeErrors($c->read(E::PROBES));
    $preservation = new \PhpGrammar\Tools\Preservation86($root, \PhpGrammar\Tools\Preservation86::UPSTREAM_CONTRACT);
    $errors = [...$errors, ...$preservation->verify($preservation->derive())];
    if ($args['generate']) {
        if ($errors) throw new RuntimeException(implode('; ', $errors));
        if (file_exists($c->path($args['report']))) throw new RuntimeException('Never overwrite a transition revision');
        $c->write($args['report'], $e->transition());
    } elseif ($c->read($args['report']) !== $e->transition()) $errors[] = 'Unauthenticated transition';
    echo S::json(['contract' => E::CONTRACT, 'errors' => $errors, 'certificationGranted' => false]); exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()], 'certificationGranted' => false]); exit(1); }
