<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$args = S::args(flags: ['check'], min: 1, max: 1);
$sources = S::sources($args['positionals'][0], 'source differs from pin');
$compiler = $sources['zend_compile.c'];
$definitions = S::definitions($compiler);
$functions = [];
foreach ($definitions as $i => $definition) {
    $start = $definition[0][1];
    $functions[$definition[1][0]] = substr($compiler, $start, ($definitions[$i + 1][0][1] ?? strlen($compiler)) - $start);
}
$early = array_intersect(S::matches('/\b(zend_\w+)\s*\(/', $sources['zend_language_parser.y']), array_keys($functions));
do {
    $before = S::sortedUnique($early);
    foreach ($before as $name) {
        array_push($early, ...array_intersect(S::matches('/\b(zend_\w+)\s*\(/', $functions[$name]), array_keys($functions)));
    }
    $early = S::sortedUnique($early);
} while ($before !== $early);
preg_match_all('/\b(zend_error(?:_noreturn(?:_unchecked)?)?|zend_throw_exception(?:_ex)?)\s*\(/', $compiler, $calls, PREG_OFFSET_CAPTURE);
$rows = [];
foreach ($calls[0] as [$call, $start]) {
    $diagnostic = substr($compiler, $start, strpos($compiler, ';', $start) - $start + 1);
    if (!preg_match('/E_COMPILE_ERROR|E_ERROR|zend_ce_compile_error|error_level/', $diagnostic)) {
        continue;
    }
    $name = S::functionAt($definitions, $start, '(global)');
    $phase = in_array($name, $early, true) ? 'parser-action' : 'contextual-compilation';
    if (in_array($name, ['zend_try_ct_eval_array', 'zend_eval_const_expr'], true)) {
        $phase = 'constant-folding';
    }
    if ($name === 'zend_stack_limit_error') {
        $phase = 'implementation-resource-limit';
    }
    $rows[] = ['line' => substr_count(substr($compiler, 0, $start), "\n") + 1, 'function' => $name, 'phase' => $phase,
        'diagnostic' => S::messages($diagnostic) ?: '(computed diagnostic)'];
}
$result = ['source_pin' => S::PIN, 'sha256' => S::read('tools/8.5/source-lock.json')['zend_compile.c'],
    'scope' => 'Every direct fatal diagnostic call in zend_compile.c; parser-reachable helpers classified before folding. Runtime/deferred helpers in other translation units and individual-path fixture completeness remain outside this inventory.',
    'parser_reachable_functions' => $early, 'sites' => $rows];
S::emit('docs/8.5/compiler-boundaries.json', $result, $args['check'], 'Compiler boundary inventory is stale');
S::summary(['sites' => count($rows), 'parser_action_sites' => count(array_filter($rows, static fn(array $r): bool => $r['phase'] === 'parser-action')), 'functions' => count(array_unique(array_column($rows, 'function')))]);
