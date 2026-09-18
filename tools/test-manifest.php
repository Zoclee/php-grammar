<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
S::args();
$result = S::run([PHP_BINARY, S::ROOT . '/vendor/phpunit/phpunit/phpunit', '--filter', 'ToolManifestTest', '--no-progress'], S::ROOT);
echo $result['output'];
exit($result['exit_code']);
