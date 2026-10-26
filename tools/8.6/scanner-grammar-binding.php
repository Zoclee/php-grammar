<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/tests/Support/DerivationForest.php';
require __DIR__ . '/../lib/ScannerGrammarBindingEvidence.php';
use PhpGrammar\Tools\{Support as S, Certification86 as C, ScannerGrammarBindingEvidence as E};
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
$args = S::args(['report' => '', 'profile' => ''], ['generate', 'check']);
try {
    if ($args['profile'] !== 'exact-pin-rc4' || $args['report'] === '' || (int)$args['generate'] + (int)$args['check'] !== 1) throw new RuntimeException('Select exact-pin-rc4, report, and one operation');
    $e = new E($root); $c = new C($root);
    if ($args['generate']) {
        if (file_exists($c->path($args['report'])) || file_exists($c->path($args['report'] . '.receipt.json'))) throw new RuntimeException('Never overwrite evidence');
        $r = $e->generate(); $c->write($args['report'], $r);
        $c->write($args['report'] . '.receipt.json', ['sha256' => hash_file('sha256', $c->path($args['report'])), 'scope' => 'Reviewed local controller custody; not a signature or certification.']); $errors = $r['errors'];
    } else $errors = $e->verify($args['report']);
    echo S::json(['contract' => E::CONTRACT, 'errors' => $errors, 'certificationGranted' => false]); exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()]]); exit(1); }
