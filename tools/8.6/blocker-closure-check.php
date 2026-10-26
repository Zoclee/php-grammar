<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/BlockerClosure.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\BlockerClosure as B;
S::args([]);
$errors = (new B(str_replace('\\', '/', realpath(S::ROOT))))->errors();
echo S::json(['contract' => B::CONTRACT, 'errors' => $errors, 'certificationGranted' => false,
    'scope' => 'Identity, source-range, evidence-digest and disposition integrity; not an automated proof of semantic equivalence or a release gate.']);
exit($errors ? 1 : 0);
