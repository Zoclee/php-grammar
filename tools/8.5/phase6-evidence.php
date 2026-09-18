<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$args = S::args(flags: ['check']);
$policy = S::read('tools/8.5/data/phase6-policy.json');
$reconciliation = S::read('docs/8.5/phase6-reconciliation.json');
$boundaries = S::read('tests/fixtures/php/8.5/parser-compiler-boundaries.json')['cases'];
$negative = S::read('docs/8.5/negative-boundaries.json');
$lexical = S::read('docs/8.5/phase5-lexical-evidence.json');
$inventory = [];
foreach ($policy['areas'] as $area => [$pattern, $restrictions]) {
    $cases = array_filter($boundaries, static fn(array $c): bool => (bool) preg_match('~' . $pattern . '~', $c['id'] . ' ' . $c['evidence']));
    $sites = array_values(array_column(array_filter($reconciliation['contextual_diagnostic_sites'], static fn(array $s): bool => (bool) preg_match('~' . $pattern . '~', $s['function'])), 'id'));
    $inventory[] = ['area' => $area, 'restrictions' => $restrictions,
        'classifications' => $area === 'modifiers' ? ['contextual-validator-enforced', 'EBNF-enforced'] : ['EBNF-enforced', 'folding-sensitive', 'documented semantic/out-of-scope'],
        'implementation' => $area === 'modifiers' ? 'Php85ModifierValidator: early modifier-list rules only' : 'Structural EBNF plus contextual documentation and PHP differential oracle; no complete repository rule implementation',
        'positive' => array_values(array_map(static fn(array $c): string => $c['fixtures']['discarded'], $cases)),
        'structural_negative' => array_values(array_column(array_filter($negative['cases'], static fn(array $c): bool => (bool) preg_match('~' . $pattern . '~', $c['area'] . ' ' . $c['production'])), 'negative')),
        'contextual' => array_values(array_map(static fn(array $c): string => $c['fixtures']['live'], array_filter($cases, static fn(array $c): bool => !$c['live_valid']))),
        'folding_families' => array_values(array_column($cases, 'id')), 'compiler_sites' => $sites,
        'lexical' => 'phase5-lexical-evidence.json: all cases pass through the repository lexer',
        'binding' => 'systematic-structure.json; phase6-matrices.json/ambiguity',
        'source' => 'phase6-reconciliation.json (pinned parser alternatives/compiler diagnostic locations)',
        'scope_note' => 'Links select area-related evidence and may overlap. Positive discarded witnesses prove parser acceptance, not live declaration validity.'];
}
$text = S::text(S::ROOT . '/docs/8.5/audit-remediation.md');
$table = explode('## Contextual constraints', explode('## Issues and regressions', $text, 2)[1], 2)[0];
$history = [];
foreach (explode("\n", $table) as $line) {
    if (!str_starts_with($line, '| ') || str_starts_with($line, '| Issue')) {
        continue;
    }
    $issue = trim(explode('|', $line)[1]);
    $superseded = (bool) array_filter($policy['superseded'], static fn(string $prefix): bool => str_starts_with($issue, $prefix));
    $history[] = ['issue' => $issue, 'original_report' => 'audit-remediation.md#issues-and-regressions',
        'disposition' => $superseded ? 'superseded with explanation' : 'fixed', 'evidence' => $line,
        'current' => $superseded ? 'The original proposed structural restriction was subsequently corrected or moved to the ordered contextual/folding layer; current fixtures, canonical anchors and function-family dispositions are indexed in phase6-reconciliation.json.' : 'Retained regression fixtures and current ordinary/structure/scanner runs exercise the correction; historical counts do not describe the current corpus.'];
}
foreach ($policy['history'] as [$issue, $status, $evidence]) {
    $history[] = ['issue' => $issue, 'disposition' => $status, 'evidence' => $evidence];
}
foreach ($lexical['families'] as $name => $family) {
    $history[] = ['issue' => 'Phase 5 scanner family: ' . $name, 'disposition' => $family['status'] === 'fixed' ? 'fixed' : 'scanner-context-only and tested',
        'evidence' => 'phase5-lexical-evidence.json/families/' . $name,
        'current' => 'Direct byte/token cases plus scanner-product.json; represented-differently dispositions remain intentional token abstractions.'];
}
$blockers = $policy['blockers'];
$blockers[1]['site_ids'] = array_values(array_column(array_filter($reconciliation['contextual_diagnostic_sites'], static fn(array $s): bool => $s['blocker'] === 'C2'), 'id'));
$paths = [...$policy['hash_paths'], 'tools/lib/Support.php', 'tools/8.5/data/phase6-policy.json'];
$report = ['source_pin' => $reconciliation['source_pin'],
    'layers' => ['source bytes', 'scanner/tokenization', 'parser acceptance and early parser actions', 'parse/AST binding', 'constant folding/discarded branches', 'surviving contextual declaration/write/type checks', 'source validity within the configured PHP profile'],
    'architecture' => 'Rule-level Php85ModifierValidator accepts identified canonical modifier spellings/target, never raw source. It runs before folding. Null certifies only the modifier-list rule; source-level contextual closure remains C1.',
    'inventory' => $inventory, 'history' => $history, 'blockers' => $blockers, 'hashes' => S::hashes($paths)];
S::emit('docs/8.5/phase6-evidence.json', $report, $args['check'], 'Phase 6 evidence index is stale', true);
S::summary(['areas' => count($inventory), 'history_dispositions' => count($history), 'blockers' => count($blockers)]);
