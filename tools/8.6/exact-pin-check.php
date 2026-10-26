<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/Certification86.php';
require_once __DIR__ . '/../lib/ExactPinProvenance.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\ExactPinProvenance as P;
$args = S::args(['record' => 'docs/8.6/exact-pin-build/build.json', 'php86' => '']);
try {
    $c = new C(realpath(S::ROOT));
    $record = $c->read($args['record']);
    $errors = (new P($c->root))->errors($record, $args['php86'] ?: ($record['binary'] ?? ''));
} catch (Throwable $e) {
    $errors = [$e->getMessage()];
}
echo S::json(['contract' => P::CONTRACT, 'exactSourceVerified' => $errors === [],
    'certificationGranted' => false, 'errors' => $errors]);
exit($errors === [] ? 0 : 1);
