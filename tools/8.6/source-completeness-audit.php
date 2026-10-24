<?php
declare(strict_types=1);

// Independent research ledger. This is not a release or certification gate.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__) . '/lib/ParserReconciliation.php';

use PhpGrammar\Ebnf\Coverage\CoverageIdentityMap;
use PhpGrammar\Php\Conformance\GrammarRepository;
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Tools\ParserReconciliation;

$root = dirname(__DIR__, 2);
chdir($root);
$options = getopt('', ['check', 'report:', 'sources85:', 'sources86:']);
$directories = ['8.5' => $options['sources85'] ?? '.audit/php-php-src-7a4c627', '8.6' => $options['sources86'] ?? '.audit/php-php-src-f23b4c2'];
$inputs = []; $errors = []; $checks = 0;
$read = static function (string $path) use (&$inputs): array {
    $inputs[$path] = hash_file('sha256', $path);
    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
};
$check = static function (bool $condition, string $message) use (&$errors, &$checks): void {
    ++$checks; if (!$condition) $errors[] = $message;
};
$baseline = $read('docs/8.6/source-audit-baseline.json');
$lock = $read('tools/8.6/source-lock.json');
$manifest = RepositoryManifest::fromRepositoryRoot($root);
$repository = new GrammarRepository($manifest);
$grammar = $repository->load('8.6');
$identity = CoverageIdentityMap::fromGrammar($grammar);
$index = $repository->productionIndex('8.6');
$coverage = $read('docs/8.6/source-audit-coverage.json');
$policy = $read('tools/8.5/data/parser-anchors.json');
$oldInventory = $read('docs/8.6/completeness-source-inventory.json');
$legacyScanner = $read('docs/8.5/phase5-lexical-evidence.json');
$compiler = $read('docs/8.6/compiler-change-reconciliation.json');
$groups = $read('docs/8.6/compiler-contextual-groups.json');
$rfc = $read('docs/8.6/rfc-review.json');
$reconciliation = $read('docs/8.6/compiler-contextual-reconciliation.json');
$sourceEvidence = $read('docs/8.6/source-evidence.json');
$dispositions = $read('docs/8.6/source-audit-dispositions.json');
foreach (['source-audit-boundaries', 'source-audit-upstream', 'source-audit-validation', 'source-audit-traceability', 'source-audit-probes', 'source-audit-context', 'source-audit-integration', 'source-audit-pfa', 'source-audit-deprecation', 'source-audit-oracles'] as $record) $read('docs/8.6/' . $record . '.json');

$sourceChecks = [];
foreach ($lock['files'] as $version => $files) foreach ($files as $path => $expected) {
    $actual = is_file($directories[$version] . '/' . $path) ? hash_file('sha256', $directories[$version] . '/' . $path) : null;
    $sourceChecks[$version][$path] = ['expected' => $expected, 'actual' => $actual, 'matches' => $actual === $expected];
    $check($actual === $expected, "Source lock: $version $path");
}
$source = static function (string $version, string $path) use ($directories): string {
    $text = file_get_contents($directories[$version] . '/' . $path);
    if ($text === false) throw new RuntimeException('Missing source ' . $path);
    return $text;
};
$extendedSourceChecks = [];
foreach ($sourceEvidence['files'] as $path => $hashes) foreach (['old' => '8.5', 'new' => '8.6'] as $side => $version) {
    $expected = $hashes[$side]; $file = $directories[$version] . '/' . $path;
    $actual = is_file($file) ? hash_file('sha256', $file) : null;
    $check($actual === $expected, "Discovery source/absence: $version $path");
    $extendedSourceChecks[$version][$path] = ['expected' => $expected, 'actual' => $actual];
}
$functionInventory = [];
foreach (['Zend/zend_language_scanner.l', 'Zend/zend_compile.c', 'Zend/zend_ast.c', 'Zend/zend_inheritance.c', 'Zend/zend_attributes.c', 'Zend/zend_enum.c', 'Zend/zend_constants.c'] as $path) {
    foreach (['8.5', '8.6'] as $version) {
        $text = $source($version, $path);
        preg_match_all('~^[A-Za-z_][^;{}\n]*?\b(zend_[A-Za-z0-9_]+)\s*\([^;{}]*?\)\s*(?:/\*[^*]*\*/\s*)?\{~m', $text, $definitions, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($definitions as $definition) {
            $name = $definition[1][0]; $start = $definition[0][1];
            $brace = $start + strlen($definition[0][0]) - 1;
            preg_match_all('~/\*[\s\S]*?\*/|//[^\n]*|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|[{}]~', substr($text, $brace), $tokens, PREG_OFFSET_CAPTURE);
            $depth = 0; $end = null;
            foreach ($tokens[0] as [$token, $offset]) {
                if ($token === '{') ++$depth;
                if ($token === '}' && --$depth === 0) { $end = $brace + $offset + 1; break; }
            }
            if ($end === null) continue;
            $body = substr($text, $start, $end - $start);
            $functionInventory[$path][$name][$version] = ['line' => substr_count(substr($text, 0, $start), "\n") + 1, 'sha256' => hash('sha256', $body)];
        }
    }
    foreach ($functionInventory[$path] as &$entry) $entry['sameText'] = isset($entry['8.5'], $entry['8.6']) && $entry['8.5']['sha256'] === $entry['8.6']['sha256'];
    unset($entry);
}
$parse = static function (string $text): array {
    preg_match_all('/^([a-z][a-z_]*):/m', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $rows = [];
    foreach ($matches as $i => $match) {
        $start = $match[0][1] + strlen($match[0][0]);
        $end = $matches[$i + 1][0][1] ?? strpos($text, "\n%%", $start);
        $rows[$match[1][0]] = ['line' => substr_count(substr($text, 0, $match[0][1]), "\n") + 1,
            'branches' => ParserReconciliation::branches(substr($text, $start, $end - $start))];
    }
    return $rows;
};
$parser85 = $parse($source('8.5', 'Zend/zend_language_parser.y'));
$parser86 = $parse($source('8.6', 'Zend/zend_language_parser.y'));
$changeReasons = [
    'parameter' => 'PHP86-009: doccomment capture moves after default expression; empty metadata action changes attachment, not terminals.',
    'argument_list' => 'PHP86-001: callable ellipsis now represented by argument placeholder; empty/nonempty list AST construction uses arg-list helpers.',
    'non_empty_argument_list' => 'PHP86-001: arg-list helper validates placeholder ordering during subsequent compilation, not Bison reduction.',
    'clone_argument_list' => 'PHP86-001: same clone-specific expression ambiguity boundaries; dedicated callable alternative folds into placeholder arguments.',
    'non_empty_clone_argument_list' => 'PHP86-001: new argument-list AST helpers retain clone expression/list distinctions.',
    'argument_no_expr' => 'PHP86-001/002: required ?, named ?, variadic ... placeholders; unpack ... expr retained; optional ?= placeholder absent.',
    'trait_method_reference' => 'AST kind rename to ZEND_AST_TRAIT_METHOD_REFERENCE; source RHS unchanged.',
    'absolute_trait_method_reference' => 'AST kind rename; source RHS unchanged.',
    'inline_function' => 'Removed empty backup_lex_pos metadata action; closure/arrow terminals and flag preservation retained.',
];
$parserRows = []; $reverse = []; $changed = []; $actionCalls = [];
foreach ($parser86 as $name => $row) {
    $internal = in_array($name, $policy['internal'], true);
    $anchors = $internal ? [] : ($policy['aliases'][$name] ?? [str_replace('_', '-', $name)]);
    foreach ($anchors as $anchor) { $check($grammar->hasProduction($anchor), "Parser anchor $name: $anchor"); $reverse[$anchor][] = $name; }
    $same = isset($parser85[$name]) && $parser85[$name]['branches'] === $row['branches'];
    if (!$same) { $changed[] = $name; $check(isset($changeReasons[$name]), 'Unreviewed parser change: ' . $name); }
    $branches = [];
    foreach ($row['branches'] as $i => [$rhs, $action]) {
        preg_match_all('/\b(zend_\w+)\s*\(/', $action, $calls);
        $calls = array_values(array_unique($calls[1]));
        foreach ($calls as $call) $actionCalls[$call][] = $name . ':' . ($i + 1);
        $branches[] = ['id' => $name . ':' . ($i + 1), 'rhs' => $rhs, 'actionSha256' => hash('sha256', $action), 'actionCalls' => $calls,
            'stage' => $internal ? 'parser-metadata' : (($name === 'inner_statement' && str_contains($rhs, 'T_HALT_COMPILER')) ? 'parser-action-rejection' : 'parser-reduction/AST-construction'),
            'isolatedAlternativeWitness' => null];
    }
    $witnesses = [];
    foreach ($anchors as $anchor) $witnesses[$anchor] = array_slice($coverage['positiveWitnesses'][$anchor] ?? $coverage['primitiveWitnesses'][$anchor] ?? [], 0, 3);
    $parserRows[$name] = ['line' => $row['line'], 'source' => 'Zend/zend_language_parser.y', 'ebnf' => $anchors,
        'branches' => $branches, 'sameRhsAndActionsAs85' => $same,
        'disposition' => $changeReasons[$name] ?? ($policy['notes'][$name] ?? ($internal ? 'Empty metadata action; no consumed terminal.' : 'Source-corresponding production family; list recursion/options use canonical repetition/options. AST allocation is not a language rule.')),
        'positiveWitnesses' => $witnesses, 'limitations' => 'Production-family correspondence, not individual upstream alternative coverage or AST-equivalence proof.',
        'blocker' => $name === 'property_hook' ? 'CA86-001' : null];
}
$check(count($parserRows) === 176, 'Parser production denominator');
$check(array_sum(array_map(static fn($r) => count($r['branches']), $parserRows)) === 623, 'Parser alternative denominator');
$check(array_keys($parser86) === array_keys($oldInventory['parser']['rows']), 'Existing parser inventory identities');
$check(array_values(array_diff(array_keys($parser85), array_keys($parser86))) === ['backup_lex_pos'], 'Removed empty parser helper');
$precedence = [];
foreach (['8.5', '8.6'] as $version) {
    preg_match_all('/^%(?:left|right|nonassoc|precedence|token)\b[^\r\n]*/m', $source($version, 'Zend/zend_language_parser.y'), $matches);
    $precedence[$version] = $matches[0];
}
$check($precedence['8.5'] === $precedence['8.6'], 'Token, precedence and associativity declarations unchanged');

$lexicalCases = [];
foreach (['coverage-lexical', 'final-coverage-lexical'] as $file) foreach ($read('tests/fixtures/php/8.6/' . $file . '.json')['cases'] as $i => $case) {
    $lexicalCases[] = ['id' => $case['id'], 'record' => "tests/fixtures/php/8.6/$file.json#/cases/$i", 'evidence' => $case['sourceEvidence'] ?? null];
}
$scannerRows = []; $scannerRules = [];
foreach (['8.5', '8.6'] as $version) {
    $lines = explode("\n", $source($version, 'Zend/zend_language_scanner.l'));
    foreach ($lines as $i => $line) if (preg_match('/^<[A-Z_,]+>/', $line)) {
        $end = strrpos($line, ' {');
        $scannerRules[$version][] = ['line' => $i + 1, 'rule' => rtrim($end === false ? $line : substr($line, 0, $end))];
    }
}
$check(array_column($scannerRules['8.5'], 'rule') === array_column($scannerRules['8.6'], 'rule'), 'Scanner rule headers unchanged');
$check(count($scannerRules['8.6']) === 190, 'Scanner rule denominator');
$scannerLines = explode("\n", $source('8.6', 'Zend/zend_language_scanner.l'));
$scannerStates = [];
foreach ($scannerRules['8.6'] as $i => $row) {
    $legacy = $legacyScanner['rules'][$i];
    $check($legacy['rule'] === $row['rule'], 'Scanner inherited identity ' . $i);
    $end = ($scannerRules['8.6'][$i + 1]['line'] ?? count($scannerLines) + 1) - 1;
    $text = implode("\n", array_slice($scannerLines, $row['line'] - 1, $end - $row['line'] + 1));
    $direct = [];
    foreach ($lexicalCases as $case) {
        $e = $case['evidence'];
        if ($e !== null && $e['path'] === 'Zend/zend_language_scanner.l' && $e['startLine'] <= $end && $e['endLine'] >= $row['line']) $direct[] = $case['record'];
    }
    preg_match('/^<([^>]+)>/', $row['rule'], $state);
    $states = explode(',', $state[1]); foreach ($states as $s) $scannerStates[$s] = true;
    $scannerRows[] = ['id' => 'S86-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), ...$row, 'endLine' => $end,
        'sourceSliceSha256' => hash('sha256', $text), 'states' => $states, 'family' => $legacy['family'],
        'implementation' => str_replace('PhpVersion::php85', 'PhpVersion::php86', $legacy['implementation']),
        'representation' => $legacy['status'], 'disposition' => $legacy['disposition'],
        'inheritedEvidence' => 'docs/8.5/phase5-lexical-evidence.json#/families/' . $legacy['family'],
        'directSourceRangeAssociations' => $direct,
        'classification' => $direct ? 'direct-source-range-associated' : 'inherited-family-evidence; isolated-RC4-rule-witness-unestablished',
        'instrumentedRuleHit' => false, 'limitation' => 'Range overlap is source association, not proof that a scanner branch executed. State products and helper/error paths remain bounded.'
    ];
}

$remaining = []; foreach ($coverage['remaining'] as $r) $remaining[$r['identity']] = $r;
$sectionSources = [
    'source' => ['start', 'top_statement'], 'lexical' => [], 'names' => ['name', 'identifier'], 'literals' => ['scalar', 'encaps_var'],
    'types' => ['type_expr', 'type_expr_without_static'], 'expressions' => ['expr'], 'dereferencing' => ['variable', 'function_call', 'new_variable'],
    'arguments' => ['argument_list', 'argument_no_expr', 'array_pair'], 'statements' => ['statement'], 'functions' => ['function_declaration_statement', 'inline_function', 'parameter'],
    'classes' => ['class_declaration_statement', 'class_statement', 'property_hook'], 'namespaces' => ['top_statement', 'use_declaration'],
    'attributes' => ['attribute_decl'], 'termination' => ['top_statement', 'inner_statement'], 'primitives' => [], 'reserved' => ['reserved_non_modifiers', 'semi_reserved'],
    'prefix' => ['expr'], 'initializers' => ['expr'], 'matched' => ['statement', 'if_stmt'], 'precedence' => ['expr'], 'yield' => ['expr']
];
$elements = []; $classCounts = [];
foreach (['production' => $identity->productions(), 'alternative' => $identity->alternatives()] as $kind => $ids) foreach ($ids as $id) {
    $name = explode('/', $id)[0]; $section = $index->sectionForRule($name); $covered = isset($coverage['positiveWitnesses'][$id]);
    $direct = $reverse[$name] ?? [];
    $check(array_key_exists($section, $sectionSources), 'Reviewed section ' . $section);
    $anchors = $direct ?: $sectionSources[$section];
    $prior = $remaining[$id] ?? null;
    $lexical = in_array($prior['classification'] ?? '', ['primitive-bypassed', 'trivia-removed'], true) || in_array($section, ['lexical', 'primitives'], true);
    $classification = $lexical ? 'context-dependent-lexical-abstraction' : ($direct ? ($covered ? 'source-corroborated-and-covered' : 'source-corroborated-but-unexercised') : ($covered ? 'covered-with-family-correspondence-only' : 'unexercised-with-family-correspondence-only'));
    if (($prior['classification'] ?? '') === 'unresolved') $classification = 'unresolved';
    $classCounts[$kind][$classification] = ($classCounts[$kind][$classification] ?? 0) + 1;
    $obligation = $lexical ? 'Validate token/scanner primitive in recursive state and delimiter profiles; ordinary EBNF-body coverage is bypassed.' : ($covered ? 'Retain witness; isolate upstream alternatives and AST binding where relevant.' : 'Add a source-backed accepted witness and nearby rejection; do not remove the production or alternative.');
    $elements[$id] = ['kind' => $kind, 'production' => $name, 'section' => $section, 'canonicalLine' => $grammar->production($name)->line,
        'covered' => $covered, 'classification' => $classification, 'sourceGranularity' => $direct ? 'production-family (not branch equivalence)' : ($lexical ? 'scanner-family' : 'section-family; individual mapping remains an obligation'),
        'upstreamParser' => $anchors, 'source' => $lexical ? 'Zend/zend_language_scanner.l' : 'Zend/zend_language_parser.y',
        'coverageEvidence' => $covered ? 'docs/8.6/source-audit-coverage.json#/positiveWitnesses/' . str_replace('/', '~1', $id) : null,
        'priorCoverageDisposition' => $prior, 'necessity' => 'Retained canonical construct; no removal or redundancy demonstrated by absence of chart coverage.',
        'risk' => $lexical ? 'Scanner/profile equivalence remains bounded.' : 'Family correspondence does not establish alternative-specific acceptance or binding.',
        'remainingObligation' => $obligation];
}
$check(count($elements) === 1161, 'All canonical identities accounted for');
$check(count($remaining) === 382, 'Uncovered identities preserved');

$context = [];
$changeMap = $read('tools/8.6/change-map.json');
foreach ($oldInventory['contextualInventory'] as $i => $row) {
    preg_match_all('/\bzend_\w+/', $row['sourceFunctionFamily'], $functions);
    $locations = [];
    foreach ($functions[0] as $function) foreach ($functionInventory as $path => $entries) if (isset($entries[$function]['8.6'])) {
        $locations[] = ['path' => $path, 'function' => $function, ...$entries[$function]['8.6'], 'sameTextAs85' => $entries[$function]['sameText']];
    }
    $findings = [];
    foreach ($changeMap['findings'] as $finding => $entry) if (array_intersect($row['productionFamily'], $entry['productions'])) $findings[] = $finding;
    $context[] = ['id' => 'C6-C' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
        'restriction' => $row['inheritedRule']['restriction'], 'area' => $row['inheritedRule']['area'],
        'productions' => $row['productionFamily'], 'normativeClause' => $row['normativeClause'],
        'sourceFunctionFamily' => $row['sourceFunctionFamily'],
        'sourceLocations' => $locations, 'sourceLocationLimit' => 'Family entry points; not individual diagnostic predicates or transitive callees.',
        'inheritedEvidence' => 'docs/8.6/completeness-source-inventory.json#/contextualInventory/' . $i,
        'relatedFindings' => $findings, 'changedRuleEvidence' => 'tools/8.6/change-map.json#/findings',
        'inheritedLabelLimit' => 'Historical restriction label is not an unchanged PHP 8.6 rule: notably readonly defaults are relaxed by PHP86-003. Current normative clause and finding take precedence.',
        'validationStage' => $row['inheritedRule']['area'] === 'modifiers' ? 'parser-action plus target-specific compiler checks' : 'compiler/contextual; declaration linking or evaluation may be deferred',
        'localAndUpstreamEvidence' => 'Related finding records contain pinned sources and hashed local fixtures; inherited record contains historical family evidence. Missing predicate-specific upstream PHPT or paired local evidence remains an explicit obligation.',
        'nativeWholeSourceEnforcement' => false,
        'disposition' => 'Inherited normative restriction; family evidence retained, predicate-specific RC4 isolation remains unresolved unless separately covered by finding matrices.',
        'requiredEvidence' => 'RC4 source predicate, upstream test, local positive/negative and discarded/retained witnesses at actual validation stage.', 'blocker' => 'C6-CONTEXT'];
    foreach ($row['productionFamily'] as $p) $check($grammar->hasProduction($p), 'Context production ' . $p);
}
$hunks = []; $hunkCounts = [];
foreach ($compiler['hunks'] as $i => $hunk) {
    $linked = [];
    foreach ($groups['groups'] as $group) if (in_array($hunk['id'], array_column($group['historicalHunks'], 'id'), true)) $linked[] = $group['id'];
    $disposition = $linked ? 'see-bounded-semantic-group' : (isset($hunk['phase3d']) ? 'source-reviewed-phase3d; retain-record-limitations' : ($hunk['phase22']['disposition'] ?? 'unresolved'));
    $hunkCounts[$disposition] = ($hunkCounts[$disposition] ?? 0) + 1;
    $hunks[] = ['id' => $hunk['id'], 'source' => $hunk['path'], 'line' => $hunk['newLine'], 'anchor' => $hunk['anchor'],
        'risk' => $hunk['phase22']['risk'] ?? 'unresolved', 'disposition' => $disposition, 'groups' => $linked,
        'evidence' => 'docs/8.6/compiler-change-reconciliation.json#/hunks/' . $i,
        'blocker' => in_array($disposition, ['pending-direct-review', 'examined-unresolved', 'unresolved'], true) ? 'PHP86-013' : null];
}
$rfcs = [];
foreach ($rfc['records'] as $i => $row) {
    $review = $row['phase22'] ?? [];
    $rfcs[] = ['slug' => $row['slug'], 'reportedStatus' => $rfc['phase3d']['statusMoves'][$row['slug']] ?? $row['reportedStatus'],
        'finding' => $review['finding'] ?? null, 'disposition' => $review['disposition'] ?? 'unresolved',
        'sourceAnchors' => $review['sourceAnchors'] ?? null, 'determination' => $review['determination'] ?? $row['disposition'],
        'ancestry' => $review['ancestry'] ?? $row['phase21']['ancestry']['status'] ?? 'unresolved',
        'record' => 'docs/8.6/rfc-review.json#/records/' . $i, 'remaining' => $review['remaining'] ?? 'Full component/follow-up/reversion review not established.',
        'freshOnlineStatusClaim' => false];
}
foreach ($rfc['phase3d']['additionalCandidates'] as $i => $row) $rfcs[] = ['slug' => $row['slug'], 'reportedStatus' => $row['reportedStatus'],
    'finding' => $row['finding'], 'disposition' => $row['disposition'], 'sourceAnchors' => $row['sourceAnchors'], 'upstreamTests' => $row['tests'],
    'record' => 'docs/8.6/rfc-review.json#/phase3d/additionalCandidates/' . $i, 'remaining' => 'Source-backed selected-pin runtime exclusion; draft/index disagreement retained.'];

$preservation = [];
foreach ($baseline['hashes'] as $path => $expected) {
    $actual = is_file($path) ? hash_file('sha256', $path) : null;
    if ($actual !== $expected) $preservation[] = $path;
}
$check($preservation === [], 'All pre-audit files preserved');
$check($manifest->package('8.6')->status === 'draft', 'Draft retained');
$check($manifest->package('8.5')->status === 'stable', '8.5 stable retained');
foreach (['grammar/8.6/php.ebnf', 'grammar/8.6/php.md', 'grammar/8.6/changes.md', 'php-grammar.json', 'tools/lib/ParserReconciliation.php', __FILE__] as $path) {
    $relative = str_replace('\\', '/', $path); if (str_starts_with($relative, str_replace('\\', '/', $root) . '/')) $relative = substr($relative, strlen($root) + 1);
    $inputs[$relative] = hash_file('sha256', $path);
}
ksort($inputs);
$report = ['schema' => 1, 'phase' => 6, 'date' => '2026-10-24', 'version' => '8.6', 'claim' => 'Source-correspondence audit with explicit unresolved completeness obligations; not certification.',
    'sourcePins' => $baseline['sourcePins'], 'baseline' => 'docs/8.6/source-audit-baseline.json', 'inputs' => $inputs,
    'sourceLockVerification' => $sourceChecks, 'discoverySourceVerification' => $extendedSourceChecks,
    'functionInventory' => $functionInventory, 'functionInventoryLimit' => 'Named C function definitions recognized by balanced lexical braces; macros and indirect dispatch are not a complete compiler call graph.',
    'exactPinExecutableVerified' => false,
    'parser' => ['productions' => count($parserRows), 'alternatives' => 623, 'changedBodies' => $changed, 'removed' => ['backup_lex_pos'],
        'removedDisposition' => 'Empty lex-position metadata action; no terminal removed.', 'declarationsUnchanged' => $precedence['8.5'] === $precedence['8.6'], 'declarations' => $precedence['8.6'], 'rows' => $parserRows, 'actionCallIndex' => $actionCalls],
    'scanner' => ['rules' => $scannerRows, 'stateCount' => count($scannerStates), 'states' => array_keys($scannerStates), 'sourceProfileEntries' => count($lexicalCases),
        'sourceRangeAssociatedRules' => count(array_filter($scannerRows, static fn($r) => $r['directSourceRangeAssociations'] !== [])),
        'instrumentedCoveragePercentage' => null, 'helperReview' => 'source-completeness-audit.md#scanner-correspondence',
        'limitations' => ['No instrumented upstream rule-hit numerator.', 'Recursive state/error products, reentrant heredoc exceptions, ZTS and multibyte profiles are not certified.', 'Inherited family evidence is independently applicable only to retained byte/profile behavior; not a fresh RC4 oracle result.']],
    'canonical' => ['totals' => $coverage['current'], 'classifications' => $classCounts, 'elements' => $elements,
        'unreachableOrRedundantEstablished' => [], 'limitation' => 'No mechanical name/section mapping is treated as proof of branch equivalence.'],
    'contextual' => ['restrictions' => $context, 'focusedFindings' => 'source-audit-context.json', 'groups' => 'compiler-contextual-groups.json', 'diagnosticAndTraversalEvidence' => 'source-audit-probes.json', 'nativeWholeSourceValidatorComplete' => false],
    'discovery' => ['hunks' => $hunks, 'counts' => $hunkCounts, 'semanticGroups' => array_map(static fn($g) => ['id' => $g['id'], 'name' => $g['name'], 'disposition' => $g['disposition'], 'remaining' => $g['remaining']], $groups['groups']),
        'phase5cPendingHunksNotDischarged' => $groups['phase22PendingNotDischarged'], 'outsideLedgerDisposition' => 'No harmless default: optimizer, VM, reentrant linking and test/component associations remain PHP86-013 obligations.'],
    'rfcs' => $rfcs, 'findingDispositions' => $reconciliation['dispositions'], 'blockers' => $dispositions['blockers'],
    'phase7Readiness' => $dispositions['phase7Readiness'], 'negativeBoundaryEvidence' => 'source-audit-boundaries.json',
    'preservation' => ['baselineFiles' => count($baseline['hashes']), 'changed' => $preservation, 'status86' => $manifest->package('8.6')->status, 'status85' => $manifest->package('8.5')->status],
    'validation' => ['checks' => $checks, 'errors' => $errors], 'certificationGranted' => false, 'phase7Begun' => false];
$path = $options['report'] ?? 'docs/8.6/source-completeness-ledger.json';
$encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
if (isset($options['check'])) $check(is_file($path) && file_get_contents($path) === $encoded, 'Audit ledger freshness');
elseif ($errors === []) file_put_contents($path, $encoded);
echo json_encode(['checks' => $checks, 'errors' => $errors, 'parser' => count($parserRows), 'scanner' => count($scannerRows), 'canonical' => count($elements), 'classifications' => $classCounts, 'hunks' => $hunkCounts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
exit($errors === [] ? 0 : 1);
