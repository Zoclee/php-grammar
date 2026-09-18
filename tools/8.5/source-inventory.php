<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$args = S::args(min: 1, max: 1);
$inventory = ['branch' => 'PHP-8.5', 'revision' => S::PIN, 'files' => []];
$counts = [];
foreach (S::sources($args['positionals'][0], 'source hash differs from the pinned revision') as $name => $data) {
    $entries = [];
    foreach (preg_split('/\r\n|\n|\r/', $data) as $i => $line) {
        if (str_ends_with($name, '.y') && preg_match('/^([a-z][a-z_]*):/', $line, $m)) {
            $entries[] = ['name' => $m[1], 'line' => $i + 1];
        } elseif (str_ends_with($name, '.l') && preg_match('/^<[A-Z_,]+>/', $line)) {
            $end = strrpos($line, ' {');
            $entries[] = ['rule' => $end === false ? $line : substr($line, 0, $end), 'line' => $i + 1];
        } elseif (str_ends_with($name, '.c') && preg_match('/^(?:static )?(?:\w+\s+)+(zend_(?:compile|is_allowed|eval_const)[a-z_]+)\(/', $line, $m)) {
            $entries[] = ['name' => $m[1], 'line' => $i + 1];
        }
    }
    $inventory['files'][$name] = ['sha256' => hash('sha256', $data), 'entries' => $entries];
    $counts[$name] = count($entries);
}
$inventory['ebnf_productions'] = S::matches('/^([a-z][a-z0-9-]*) =/m', S::text(S::ROOT . '/grammar/8.5/php.ebnf'));
S::emit('docs/8.5/source-inventory.json', $inventory, false, '');
S::summary($counts);
echo 'EBNF productions: ' . count($inventory['ebnf_productions']) . "\n";
