<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
$args = S::args(['source-cache' => S::ROOT . '/.audit/phase7-sources'], ['check']);
$policy = S::read('tools/8.5/data/certification-policy.json');
$previous = S::read('docs/8.5/phase6-evidence.json');
$reconciliation = S::read('docs/8.5/phase6-reconciliation.json');
$coverage = S::read('docs/8.5/phase3-coverage.json');
$lexical = S::read('docs/8.5/phase5-lexical-evidence.json');
$negative = S::read('docs/8.5/negative-boundaries.json');
$correspondence = S::read('docs/8.5/source-correspondence.json');
$witnesses = S::read('tests/fixtures/php/8.5/diagnostic-predicates.json');
$diagnosticResults = S::read('docs/8.5/diagnostic-witnesses.json');
$binding = S::read('docs/8.5/interpolation-binding.json');
$matrices = S::read('docs/8.5/phase6-matrices.json');
$structure = S::read('docs/8.5/systematic-structure.json');
foreach ([$diagnosticResults, $binding, $matrices, $structure] as $report) {
    if ($report['failures']) {
        throw new RuntimeException('An evidence matrix has failures');
    }
    foreach ($report['hashes'] ?? [] as $path => $expected) {
        if (hash_file('sha256', S::ROOT . '/' . $path) !== $expected) {
            throw new RuntimeException('Stale matrix input: ' . $path);
        }
    }
}
foreach ($coverage['remaining'] as $row) {
    if (!in_array($row['classification'], ['primitive-bypassed', 'trivia-removed', 'contextual-only', 'scanner-context-only'], true)) {
        throw new RuntimeException('Unclassified meaningful grammar coverage gap');
    }
}
if (count($lexical['rules']) !== 190 || count($lexical['families']) !== 25) {
    throw new RuntimeException('Scanner audit denominator changed; review certification policy');
}
$states = S::sortedUnique(array_merge(...array_column($lexical['rules'], 'states')));
if (count($states) !== 11) {
    throw new RuntimeException('Scanner state inventory changed; review certification policy');
}
if ($reconciliation['source_pin'] !== S::PIN || $correspondence['source_pin'] !== S::PIN) {
    throw new RuntimeException('Source pins disagree');
}
if (array_diff(array_keys($witnesses), array_column($reconciliation['contextual_diagnostic_sites'], 'id'))) {
    throw new RuntimeException('Witness names an unknown diagnostic site');
}
if ($diagnosticResults['sites'] !== count($witnesses)) {
    throw new RuntimeException('Witness execution count differs');
}
$sources = [];
foreach ($correspondence['files'] as $row) {
    $path = $args['source-cache'] . '/' . S::PIN . '/' . $row['file'];
    $data = file_get_contents($path);
    if (hash('sha256', $data) !== $row['pin_sha256']) {
        throw new RuntimeException('Delegated source hash mismatch: ' . $path);
    }
    $sources[basename($row['file'])] = $data;
}
$sites = [];
$lines = preg_split('/\r\n|\n|\r/', $sources['zend_compile.c']);
foreach ($reconciliation['contextual_diagnostic_sites'] as $site) {
    $line = $site['line'];
    $early = $site['classification'] === 'contextual-validator-enforced';
    $excluded = isset($policy['excluded'][$line]);
    $witness = isset($witnesses[$site['id']]);
    $status = $excluded ? 'out of scope with justification' : ($witness ? 'proven by direct evidence' : ($early ? 'systematically sampled' : 'unresolved'));
    $sites[] = [...$site, 'source' => 'https://github.com/php/php-src/blob/' . S::PIN . '/Zend/zend_compile.c#L' . $line,
        'trigger' => $site['diagnostic'], 'source_context_start' => max(1, $line - 6),
        'source_context' => implode("\n", array_slice($lines, max(0, $line - 7), $line + 5 - max(0, $line - 7))),
        'syntax_relevance' => $excluded ? 'out of scope' : 'source-context rule or unresolved mixed predicate',
        'final_disposition' => $status,
        'enforcement_layer' => $excluded ? 'not applicable' : ($early ? 'contextual validator' : 'explicit documented limitation'),
        'isolated_witness' => $witnesses[$site['id']] ?? null,
        'evidence' => $witness ? 'diagnostic-witnesses.json/' . $site['id'] : ($early ? 'phase6-matrices.json/modifiers' : 'phase6-reconciliation.json: function-family links only'),
        'reason' => $policy['excluded'][$line] ?? ($witness ? 'Observed diagnostic and repaired positive; no instrumented branch proof.' : ($early ? '584-case target/modifier matrix; rule API requires caller-supplied context.' : 'Exact guard/traversal is not isolated; this site is not enforced by the repository source matcher.')),
        'final_blocker' => $excluded || $early ? null : ($witness ? 'C1' : 'C1/C2')];
}
$delegated = [];
foreach (['zend_ast.c', 'zend_inheritance.c', 'zend_enum.c', 'zend_attributes.c'] as $name) {
    $source = $sources[$name];
    $definitions = S::definitions($source);
    $pattern = '\b(?:zend_error(?:_noreturn(?:_unchecked)?)?|zend_throw_error|zend_type_error|zend_throw_exception(?:_ex)?)\s*\(';
    if ($name === 'zend_attributes.c') {
        $pattern .= '|\breturn\s+zend_(?:strpprintf|string_init)\s*\(';
    }
    preg_match_all('~' . $pattern . '~', $source, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as [$match, $start]) {
        $line = substr_count(substr($source, 0, $start), "\n") + 1;
        $function = S::functionAt($definitions, $start, '(macro/global)');
        $call = substr($source, $start, strpos($source, ';', $start) - $start + 1);
        $outside = null;
        if (str_contains($call, 'E_DEPRECATED') || str_contains($call, 'E_WARNING')) {
            $outside = 'Nonfatal diagnostic does not determine source acceptance.';
        }
        if ($name === 'zend_attributes.c' && in_array($function, ['zend_internal_attribute_register', 'zend_mark_internal_attribute'], true)) {
            $outside = 'Internal extension registration API, not a source declaration validator.';
        }
        if ($name === 'zend_enum.c' && $function === 'zend_enum_build_backed_enum_table') {
            $outside = 'Resolved case value/type/duplicate-value semantics, not case initializer syntax.';
        }
        $delegated[] = ['id' => $name . ':' . $line, 'function' => $function, 'line' => $line,
            'source' => 'https://github.com/php/php-src/blob/' . S::PIN . '/Zend/' . $name . '#L' . $line,
            'call' => $call, 'trigger' => S::messages($call),
            'final_disposition' => $outside ? 'out of scope with justification' : 'unresolved',
            'enforcement_layer' => $outside ? 'not applicable' : 'explicit documented limitation',
            'reason' => $outside ?? 'Delegated guard may combine source shape with resolved class/value context; no isolated predicate witness or source-only reachability proof.',
            'final_blocker' => $outside ? null : 'C2'];
    }
}
$diagnostics = ['source_pin' => S::PIN,
    'scope' => '244 inventoried direct compiler fatal sites plus explicitly enumerated delegated diagnostic/attribute-return candidates. Context excerpts are navigation aids, not complete predicates. Unresolved candidates are not certified.',
    'direct_counts' => array_count_values(array_column($sites, 'final_disposition')),
    'delegated_counts' => array_count_values(array_column($delegated, 'final_disposition')), 'direct_sites' => $sites, 'delegated_candidates' => $delegated];
S::emit('docs/8.5/diagnostic-dispositions.json', $diagnostics, $args['check'], 'Stale certification artifact: docs/8.5/diagnostic-dispositions.json', true);
$blockers = $policy['blockers'];
$blockers[0]['affected_syntax'] = array_map(static fn(array $r): string => $r['area'] . ': ' . implode('; ', array_diff($r['restrictions'], $policy['outside_restrictions'])), $previous['inventory']);
$areas = [];
foreach ($policy['families'] as $name => [$negativeArea, $lexicalFamily, $anchor]) {
    if (!isset($coverage['first_positive_witness'][$anchor])) {
        throw new RuntimeException('Missing positive family anchor: ' . $anchor);
    }
    $negatives = array_values(array_column(array_filter($negative['cases'], static fn(array $r): bool => $r['area'] === $negativeArea), 'id'));
    if (!$negatives) {
        throw new RuntimeException('Missing negative family evidence: ' . $negativeArea);
    }
    $extraArea = ['classes/interfaces/traits/enums' => 'traits-enums', 'dereferencing/calls' => 'dereference'][$name] ?? null;
    if ($extraArea !== null) {
        array_push($negatives, ...array_column(array_filter($negative['cases'], static fn(array $r): bool => $r['area'] === $extraArea), 'id'));
    }
    $extra = [];
    foreach ($policy['extra_anchors'][$name] ?? [] as $additional) {
        if (!isset($coverage['first_positive_witness'][$additional])) {
            throw new RuntimeException('Missing additional family anchor: ' . $additional);
        }
        $extra[$additional] = $coverage['first_positive_witness'][$additional];
    }
    $statuses = array_combine($policy['categories'], ['proven by direct evidence', 'bounded evidence', 'systematically sampled', 'systematically sampled', 'bounded evidence', 'represented elsewhere', 'known limitation', 'systematically sampled', 'bounded evidence']);
    $areas[] = ['area' => $name, 'categories' => $statuses,
        'evidence' => ['positive' => $coverage['first_positive_witness'][$anchor], 'canonical_anchor' => $anchor,
            'additional_positive_anchors' => (object) $extra, 'negative_boundary_ids' => $negatives, 'scanner_family' => $lexicalFamily,
            'binding' => 'systematic-structure.json; interpolation-binding.json (only its declared subset)',
            'folding' => 'phase6-boundary-folding.json; phase6-matrices.json/direct-folding',
            'contextual' => 'restriction_inventory and diagnostic-dispositions.json; C1/C2', 'upstream' => 'phase6-reconciliation.json', 'differential' => 'bin/php85-conformance.php; C4']];
}
$restrictions = [];
foreach ($previous['inventory'] as $area) {
    foreach ($area['restrictions'] as $restriction) {
        $outside = in_array($restriction, $policy['outside_restrictions'], true);
        $restrictions[] = ['area' => $area['area'], 'restriction' => $restriction,
            'status' => $outside ? 'not applicable' : ($area['area'] === 'modifiers' ? 'systematically sampled' : 'known limitation'),
            'enforcement_layer' => $outside ? 'out of scope: resolved symbol/value/type behavior' : ($area['area'] === 'modifiers' ? 'rule-level contextual validator; target identification external' : 'EBNF shape plus external ordered contextual constraints'),
            'evidence' => ['source' => $area['source'], 'function_family_sites' => $area['compiler_sites'], 'folding_families' => $area['folding_families']],
            'scope' => 'Restriction-description inventory, not an assertion that every individual predicate has a witness.'];
    }
}
$history = [];
$prefixLayers = $exactLayers = [];
foreach ($policy['history_layers'] as $mapping) {
    if (isset($mapping['Try without handler'])) {
        $prefixLayers = $mapping;
    } else {
        $exactLayers = $mapping;
    }
}
foreach ($previous['history'] as $row) {
    $status = match ($row['issue']) {
        'source-level contextual validation' => 'accepted limitation C1; predicate residue C2',
        'parse binding inside aggregated strings' => 'partially closed; exact C3 residue listed',
        'oracle build differs from pin' => 'partially closed; exact C4 residue listed',
        'int literal/name helper ambiguity' => 'intentionally retained; equivalent type spans and normalized structure',
        default => $row['disposition'],
    };
    $evidence = $row['evidence'];
    if (str_starts_with($evidence, '| ')) {
        $layer = trim(explode('|', $evidence)[2]);
        foreach ($prefixLayers as $prefix => $currentLayer) {
            if (str_starts_with($row['issue'], $prefix)) {
                $layer = $currentLayer;
            }
        }
    } elseif (str_starts_with($row['issue'], 'Phase 5 scanner family:')) {
        $layer = 'lexer/scanner adapter';
    } else {
        $layer = $exactLayers[$row['issue']];
    }
    $history[] = ['issue' => $row['issue'], 'source' => $row['original_report'] ?? 'phase6-evidence.json/history',
        'original_severity' => 'not assigned in historical issue index', 'final_disposition' => $status,
        'enforcement_layer' => $layer, 'regression_evidence' => $evidence];
}
$historyText = "# PHP 8.5 consolidated historical dispositions\n\nGenerated by `tools/8.5/certification.php`. Original reports remain historical; [completeness.md](completeness.md) defines the current claim.\n\n";
$historyText .= "| Issue | Source | Original severity | Final disposition | Enforcement layer | Regression evidence |\n|---|---|---|---|---|---|\n";
foreach ($history as $row) {
    $historyText .= '| ' . implode(' | ', array_map(static fn(string $v): string => str_replace(['|', "\n"], ['&#124;', ' '], $v), array_values($row))) . " |\n";
}
S::emitText('docs/8.5/historical-dispositions.md', $historyText, $args['check'], 'Historical disposition table is stale');
$paths = $policy['input_paths'];
$root = str_replace('\\', '/', realpath(S::ROOT));
foreach (['src', 'tests', 'grammar', 'bin', 'tools'] as $directory) {
    foreach (S::files($root . '/' . $directory) as $path) {
        $paths[] = substr($path, strlen($root) + 1);
    }
}
$fixtures = [];
foreach (['valid', 'invalid', 'contextual-invalid'] as $kind) {
    $fixtures[$kind] = count(glob(S::ROOT . '/tests/fixtures/php/8.5/' . $kind . '/*.php')) + count(glob(S::ROOT . '/tests/fixtures/php/8.5/short-tags-disabled/' . $kind . '/*.php'));
}
$report = ['schema' => 1, 'version' => '8.5', 'source_pin' => S::PIN,
    'claim' => 'Grammar-complete, with documented external contextual constraints; conformance evidence is bounded and uses PHP 8.5.10.',
    'grammar_complete' => true, 'whole_source_validator_complete' => false, 'exhaustively_equivalent' => false,
    'profile' => 'Byte-oriented PHP source; zend.multibyte=0; short_open_tag enabled and disabled; lint acceptance, not runtime success.',
    'grammar_coverage' => $coverage['current'], 'coverage_classifications' => $coverage['remaining_counts'], 'unclassified_meaningful_gaps' => 0,
    'scanner' => ['rules' => count($lexical['rules']), 'families' => count($lexical['families']), 'states' => $states, 'direct' => 2700, 'primitive' => 101, 'syntax' => 144],
    'parser' => ['productions' => $reconciliation['parser_productions'], 'alternatives' => $reconciliation['parser_alternatives']],
    'fixtures' => $fixtures, 'diagnostics' => $diagnostics['direct_counts'], 'delegated_diagnostics' => $diagnostics['delegated_counts'],
    'binding' => array_intersect_key($binding, array_flip(['positive', 'malformed', 'operand_comparisons', 'limits'])),
    'ambiguities' => array_values(array_filter($matrices['cases'], static fn(array $r): bool => $r['matrix'] === 'ambiguity' && $r['derivations'] > 1)),
    'areas' => $areas, 'restriction_inventory' => $restrictions, 'historical_dispositions' => $history, 'blockers' => $blockers,
    'inputs' => S::hashes(S::sortedUnique($paths))];
S::emit('docs/8.5/final-evidence.json', $report, $args['check'], 'Stale certification artifact: docs/8.5/final-evidence.json', true);
S::summary(['areas' => count($areas), 'restriction_descriptions' => count($restrictions), 'historical_dispositions' => count($history),
    'direct_diagnostics' => $diagnostics['direct_counts'], 'delegated_candidates' => count($delegated),
    'blocker_dispositions' => array_column($blockers, 'disposition', 'id'), 'unclassified_meaningful_gaps' => 0]);
