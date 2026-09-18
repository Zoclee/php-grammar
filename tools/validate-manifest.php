<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/ManifestValidator.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ManifestValidator;
$args = S::args(max: 1);
$path = $args['positionals'][0] ?? S::ROOT . '/php-grammar.json';
$manifest = json_decode(file_get_contents($path), false, 512, JSON_THROW_ON_ERROR);
$errors = (new ManifestValidator())->validate($manifest, dirname(realpath($path)));
if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
echo "Manifest schema 1.0 and package paths: PASS\n";
