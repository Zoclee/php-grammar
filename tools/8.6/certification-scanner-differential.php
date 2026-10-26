<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/ScannerDifferentialCertification.php';
use PhpGrammar\Tools\{Support as S, Certification86 as C, CertificationCurrent as V2, ScannerDifferentialCertification as E};
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
$args = S::args(['contract' => '', 'revision' => '', 'assessment' => '', 'differential-report' => '', 'report' => ''], ['generate', 'check', 'claims']);
try {
    if ($args['contract'] !== E::CONTRACT || (int)$args['generate'] + (int)$args['check'] + (int)$args['claims'] !== 1) throw new RuntimeException('Explicit php86-certification-v4 and one operation required');
    $e = new E($root);
    if ($args['generate']) {
        $path = $e->generate($args['revision'], $args['assessment'], $args['differential-report']);
        echo S::json(['report' => $path, 'independentlyVerified' => false, 'certificationGranted' => false]); exit(0);
    }
    $errors = $e->verify($args['report']);
    if ($args['claims'] && !$errors) $errors = V2::claimErrors((new C($root))->read($args['report']));
    echo S::json(['contract' => E::CONTRACT, 'errors' => $errors, 'certificationGranted' => false]); exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()], 'certificationGranted' => false]); exit(1); }
