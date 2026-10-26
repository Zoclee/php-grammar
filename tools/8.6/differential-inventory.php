<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/DifferentialInventory.php';
use PhpGrammar\Tools\{Support as S, Certification86 as C, DifferentialInventory as E};
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
$args = S::args(['report' => '', 'profile' => ''], ['original-check', 'write-inventory', 'inventory-check', 'generate', 'check']);
try {
    if (count(array_filter(array_intersect_key($args, array_flip(['original-check', 'write-inventory', 'inventory-check', 'generate', 'check'])))) !== 1) throw new RuntimeException('Select exactly one operation');
    $e = new E($root); $c = new C($root);
    if ($args['original-check']) $errors = $e->originalErrors();
    elseif ($args['write-inventory']) {
        if (is_file($c->path(E::INVENTORY))) throw new RuntimeException('Never overwrite an inventory revision');
        $inventory = $e->derive(); $errors = $e->inventoryErrors($inventory);
        if (!$errors) $c->write(E::INVENTORY, $inventory);
    } elseif ($args['inventory-check']) $errors = $e->inventoryErrors($e->read(E::INVENTORY));
    else {
        if ($args['profile'] !== 'exact-pin-rc4' || $args['report'] === '') throw new RuntimeException('Select exact-pin-rc4 and a report path');
        if ($args['generate']) {
            if (is_file($c->path($args['report'])) || is_file($c->path($args['report'] . '.receipt.json'))) throw new RuntimeException('Never overwrite evidence');
            $report = $e->generate(); $c->write($args['report'], $report);
            $c->write($args['report'] . '.receipt.json', ['sha256' => hash_file('sha256', $c->path($args['report'])), 'scope' => 'Separate local controller custody receipt; not a signature.']);
            $errors = $report['errors'];
        } else $errors = $e->verify($args['report']);
    }
    echo S::json(['contract' => E::CONTRACT, 'errors' => $errors, 'historicalCheckScope' => 'Original bytes and recorded universe, not current corpus membership or a newly executed historical gate.', 'certificationGranted' => false]); exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()], 'certificationGranted' => false]); exit(1); }
