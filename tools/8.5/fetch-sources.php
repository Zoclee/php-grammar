<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$args = S::args(min: 1, max: 1);
$destination = $args['positionals'][0];
if (!is_dir($destination)) {
    mkdir($destination, 0777, true);
}
foreach (['zend_language_parser.y', 'zend_language_scanner.l', 'zend_compile.c'] as $name) {
    $url = 'https://api.github.com/repos/php/php-src/contents/Zend/' . $name . '?ref=' . S::PIN;
    $data = json_decode(S::download($url), true, 512, JSON_THROW_ON_ERROR);
    $bytes = base64_decode($data['content'], true);
    if ($bytes === false) {
        throw new RuntimeException('Invalid source encoding: ' . $name);
    }
    file_put_contents($destination . '/' . $name, $bytes);
}
echo S::PIN . "\n";
