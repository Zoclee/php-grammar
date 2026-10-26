<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/ScannerGrammarBindingEvidence.php';
use PhpGrammar\Tools\{Support as S, Certification86 as C, ScannerGrammarBindingEvidence as E};
$root = str_replace('\\', '/', realpath(dirname(__DIR__, 2))); $c = new C($root); $out = E::OUT;
$args = S::args([], ['generate', 'check']);
try {
    if ((int)$args['generate'] + (int)$args['check'] !== 1) throw new RuntimeException('Select generate or check');
    $e = new E($root); $m = $e->manifest(); $observations = $c->read($out . '/observations.json');
    $errors = $e->recordErrors($observations); if ($errors) throw new RuntimeException(implode('; ', $errors));
    $base = $c->read($out . '/baseline-baseline.json'); $before = $c->read($out . '/baseline-coverage.json'); $after = $observations['coverage'];
    $errors = $c->verifyHashes($base['files']); if ($errors) throw new RuntimeException(implode('; ', $errors));
    $old = $base['coverage']; $rows = [];
    foreach ($old['rows'] as $r) {
        $witnesses = []; foreach ($m['coverage'] as $case) if (in_array($r['id'], $case['selectedIdentities'], true) && $case['accepted']) $witnesses[] = ['id' => $case['id'], 'fixture' => $case['fixture'], 'sha256' => $case['sha256'], 'sourceIds' => $case['sourceIds']];
        $covered = in_array($r['id'], [...$after['matchedProductions'], ...$after['matchedAlternatives']], true);
        $lexical = in_array($r['original']['classification'], ['primitive-bypassed', 'trivia-removed'], true);
        $production = $r['affectedProduction'];
        $support = [];
        if ($lexical) {
            if (preg_match('/digit|integer|float|numeric|exponent|separator/', $production)) $support = ['numbers', 'numeric-error', 'offset-property'];
            elseif (preg_match('/heredoc/', $production)) $support = ['heredoc-nested'];
            elseif (preg_match('/nowdoc/', $production)) $support = ['nowdoc-indent', 'nowdoc-indent-error'];
            elseif (preg_match('/comment/', $production)) $support = ['comment-eof'];
            elseif (preg_match('/name|identifier|ascii|variable/', $production)) $support = ['qualified-names', 'offset-property'];
            elseif (preg_match('/string|escape|encapsulated/', $production)) $support = ['unicode-escape', 'unicode-error', 'offset-property'];
            elseif (preg_match('/html|whitespace|newline|source-character/', $production)) $support = ['tags', 'shebang'];
        }
        $rows[] = ['id' => $r['id'], 'predecessor' => $r, 'classification' => $covered ? 'directly covered' : ($lexical ? 'intentionally abstracted; chart-uncovered' : 'unresolved'), 'scannerFamilySupport' => $support, 'scannerSupportPrecision' => 'Related primitive/state boundary only; no isolated helper-body or alternative hit claimed.',
            'newWitnesses' => $witnesses, 'directMatchingMeaningful' => $r['directMatchingMeaningful'],
            'remaining' => $covered ? 'Witness establishes the listed chart identity, not full source validity or branch/AST equivalence.' : ($lexical ? 'Retain denominator. Scanner family observations are supporting evidence only; isolated character-body traversal is inappropriate.' : 'Needs focused witness or identity-specific source-backed disposition; context-only from remains unresolved.')];
    }
    $coverage = ['contract' => E::CONTRACT, 'workItem' => 'D-S01', 'predecessor' => 'docs/8.6/context-coverage-closure/coverage-delta.json', 'before' => $before, 'after' => $after,
        'newProductionIdentities' => array_values(array_diff($after['matchedProductions'], $before['matchedProductions'])), 'newAlternativeIdentities' => array_values(array_diff($after['matchedAlternatives'], $before['matchedAlternatives'])), 'rows' => $rows, 'denominatorsChanged' => false, 'complete' => false];
    $scanner = $base['scanner']; $scannerRows = []; $associated = []; $states = $scanner['associatedStateNames'];
    foreach ($scanner['rows'] as $r) {
        $cases = []; foreach ($m['scanner'] as $case) if (in_array($r['id'], $case['sourceIds'], true)) { $cases[] = $case['id']; $states = [...$states, ...$case['states']]; }
        if ($cases || $r['scenarios']) $associated[] = $r['id'];
        $scannerRows[] = ['id' => $r['id'], 'predecessor' => $r, 'newScenarios' => $cases, 'classification' => $cases ? 'source-corresponding token/lint observations; execution uninstrumented' : 'retained predecessor obligation', 'instrumentedRuleHit' => false];
    }
    $scannerResult = ['contract' => E::CONTRACT, 'workItem' => 'D-S02', 'predecessor' => 'docs/8.6/context-coverage-closure/scanner-inventory.json', 'ruleCount' => 190, 'states' => $scanner['states'],
        'sourceAssociatedHeaders' => count($associated), 'associatedStateNames' => array_values(array_unique($states)), 'instrumentedRuleHits' => 0, 'instrumentedStateHits' => 0, 'newScenarios' => $m['scanner'], 'rows' => $scannerRows,
        'remaining' => ['All uninstrumented rule/helper branches remain bounded by source correspondence only.', 'Encoding/filter configurations, ZTS and reentrant exception restoration remain unexercised.', 'Shebang CLI initialization differs from token_get_all; source association is not a runtime trace.'], 'complete' => false];
    $binding = ['contract' => E::CONTRACT, 'workItem' => 'D-S03', 'predecessor' => $base['binding'], 'newObservations' => $m['binding'],
        'astEquivalenceClaim' => false, 'remaining' => ['No live Zend AST dump or general grammar-to-AST isomorphism is established.', 'Dangling-else, declaration/member and interpolation associations need further per-branch proof.', 'Reference/PFA propagation and writable operand result categories retain PHP86-014 upstream validity dependence.', 'Compiler validation-stage completeness remains under C6-CONTEXT.'], 'complete' => false];
    $work = []; foreach ($base['backlog']['workItems'] as $item) $work[] = ['original' => $item, 'phase7f' => in_array($item['id'], ['D-S01', 'D-S02', 'D-S03'], true) ? 'partial; new independently checked evidence; original completion criteria not met' : 'unchanged; outside Phase 7F workstream'];
    $workResult = ['contract' => E::CONTRACT, 'workItems' => $work, 'originalBlockers' => $base['backlog']['originalBlockerRows'], 'C6-COVERAGE' => 'blocking; material witness and scanner/operand progress does not discharge remaining meaningful gaps, scanner bounds or AST correspondence', 'blockersClosed' => [], 'certificationGranted' => false];
    $results = ['coverage.json' => $coverage, 'scanner.json' => $scannerResult, 'binding.json' => $binding, 'work-items.json' => $workResult];
    foreach ($results as $name => $r) {
        $p = $out . '/' . $name;
        if ($args['generate']) { if (file_exists($c->path($p))) throw new RuntimeException('Never overwrite ledger'); $c->write($p, $r); }
        elseif ($c->read($p) !== $r) $errors[] = 'Ledger mismatch: ' . $name;
    }
    echo S::json(['errors' => $errors, 'preservedBaselineFiles' => count($base['files']), 'originalCoverageRows' => count($rows), 'scannerRows' => count($scannerRows), 'workItems' => count($work), 'certificationGranted' => false]); exit($errors ? 1 : 0);
} catch (Throwable $e) { echo S::json(['errors' => [$e->getMessage()]]); exit(1); }
