<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Lexing\{Lexer, LexerException};
use PhpGrammar\Tests\Support\{Php85LexicalAudit, Php85LexicalCases};

$root = dirname(__DIR__);
$failures = [];
$cases = Php85LexicalCases::all();
foreach ($cases as $id => $case) {
    try {
        $tokens = Lexer::forPhp85()->withShortOpenTag($case['short'])->tokenize($case['source'])->all();
        if (isset($case['error']) || array_map(static fn ($t) => [$t->type->value, $t->lexeme], $tokens) !== $case['tokens']) $failures[] = $id;
    } catch (LexerException $e) {
        if (!isset($case['error']) || !str_contains($e->getMessage(), $case['error'])) $failures[] = $id . ': ' . $e->getMessage();
    }
}
$matcher = PhpGrammarMatcher::forRepositoryRoot($root);
$primitiveCount = 0;
foreach (Php85LexicalCases::primitives() as $family => [$rule, $positive, $negative]) {
    foreach ([1 => $positive, 0 => $negative] as $accepted => $sources) foreach ($sources as $i => $source) {
        $primitiveCount++;
        if ($matcher->matchesRule('8.5', $rule, $source)->matched !== (bool) $accepted) $failures[] = "primitive:$family/$accepted/$i";
    }
}
$data = Php85LexicalAudit::report($root);
foreach (Php85LexicalCases::syntax() as $id => [$source, $expected]) {
    if ($matcher->matches('8.5', $source)->matched !== $expected) $failures[] = 'syntax:' . $id;
}
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
$path = $root . '/docs/8.5/phase5-lexical-evidence.json';
if (in_array('--write', $argv, true) && $failures === []) file_put_contents($path, $json);
elseif (!is_file($path) || file_get_contents($path) !== $json) $failures[] = 'Ledger stale: run composer lexer:coverage -- --write';
foreach ($data['families'] as $name => $family) {
    printf("%-18s %-24s positive=%d boundary=%d\n", $name, $family['status'], count($family['positive']), count($family['boundary']));
}
printf("Scanner rules: %d; direct cases: %d; primitive cases: %d; syntax cases: %d; grammar bypass entries: %d; unexpected failures: %d\n",
    count($data['rules']), count($cases), $primitiveCount, count(Php85LexicalCases::syntax()), count($data['grammar_bypasses']), count($failures));
echo "Unproven: exhaustive recursive state combinations and exact malformed-input recovery; see Phase 5 report.\n";
foreach ($failures as $failure) fwrite(STDERR, $failure . "\n");
exit($failures === [] ? 0 : 1);
