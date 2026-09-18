<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

final class ParserReconciliation
{
    /** Split Bison alternatives outside quoted terminals, C actions and comments. */
    public static function branches(string $body): array
    {
        preg_match_all('~/\*[\s\S]*?\*/|//[^\n]*|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|.~s', $body, $tokens);
        $result = $actions = [];
        $rhs = $action = '';
        $depth = 0;
        foreach ($tokens[0] as $token) {
            if (str_starts_with($token, '/*') || str_starts_with($token, '//')) {
                continue;
            }
            if ($token === '{') {
                ++$depth;
            }
            if ($depth) {
                $action .= $token;
                if ($token === '}' && --$depth === 0) {
                    $actions[] = $action;
                    $action = '';
                }
                continue;
            }
            if ($token === '|' || $token === ';') {
                $result[] = [preg_replace('/\s+/', ' ', trim($rhs)), implode("\n", $actions)];
                $rhs = '';
                $actions = [];
                if ($token === ';') {
                    break;
                }
            } else {
                $rhs .= $token;
            }
        }
        if ($depth) {
            throw new \RuntimeException('Unclosed C action');
        }
        return $result;
    }

    public static function generate(string $directory): array
    {
        $sources = Support::sources($directory);
        $policy = Support::read('tools/8.5/data/parser-anchors.json');
        $names = Support::matches('/^([a-z][a-z0-9-]*) =/m', Support::text(Support::ROOT . '/grammar/8.5/php.ebnf'));
        $coverage = Support::read('docs/8.5/phase3-coverage.json');
        $negative = Support::read('docs/8.5/negative-boundaries.json')['cases'];
        $boundaries = Support::read('tests/fixtures/php/8.5/parser-compiler-boundaries.json')['cases'];
        $compiler = Support::read('docs/8.5/compiler-boundaries.json');
        $text = $sources['zend_language_parser.y'];
        preg_match_all('/^([a-z][a-z_]*):/m', $text, $definitions, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $rows = [];
        foreach ($definitions as $i => $match) {
            $name = $match[1][0];
            $internal = in_array($name, $policy['internal'], true);
            $anchors = $internal ? [] : ($policy['aliases'][$name] ?? [str_replace('_', '-', $name)]);
            if ($unknown = array_diff($anchors, $names)) {
                throw new \RuntimeException($name . ': unknown anchors ' . implode(', ', $unknown));
            }
            $start = $match[0][1] + strlen($match[0][0]);
            $end = $definitions[$i + 1][0][1] ?? strpos($text, "\n%%", $start);
            $alternatives = [];
            foreach (self::branches(substr($text, $start, $end - $start)) as $number => [$rhs, $action]) {
                $calls = Support::sortedUnique(Support::matches('/\b(zend_\w+)\s*\(/', $action));
                $phase = $internal ? 'internal-state' : ($name === 'inner_statement' && str_contains($rhs, 'T_HALT_COMPILER') ? 'error-only' :
                    (array_intersect($calls, $compiler['parser_reachable_functions']) ? 'parser-action/scanner-context' : 'syntax/AST-construction'));
                $alternatives[] = ['id' => $name . ':' . ($number + 1), 'rhs' => $rhs, 'action_calls' => $calls, 'layer' => $phase, 'ebnf' => $phase === 'error-only' ? [] : $anchors];
            }
            $positives = $lexical = [];
            foreach ($anchors as $anchor) {
                $positives[$anchor] = $coverage['first_positive_witness'][$anchor] ?? $coverage['primitive_witnesses'][$anchor] ?? null;
                if ($positives[$anchor] === null) {
                    $lexical[$anchor] = 'phase5-lexical-evidence.json: lexical body bypass; families identifiers/keywords for names, interpolation/backticks for encapsulated forms';
                }
            }
            $rows[] = ['zend' => $name, 'line' => substr_count(substr($text, 0, $match[0][1]), "\n") + 1,
                'ebnf' => $anchors, 'alternatives' => $alternatives,
                'abstraction' => $policy['notes'][$name] ?? ($internal ? 'Internal metadata only; no source terminal. Generator flags are subsequently consumed by contextual compilation.' : 'List recursion/options are represented by EBNF repetition/options; AST allocation and source locations are not syntax constraints.'),
                'positive_witnesses' => (object) $positives, 'lexical_evidence' => (object) $lexical,
                'negative_boundaries' => array_values(array_column(array_filter($negative, static fn(array $n): bool => in_array($n['production'], $anchors, true)), 'id')),
                'contextual_families' => self::families($boundaries, $name),
                'evidence_scope' => 'Canonical production witnesses and boundary families; not isolated witnesses for every Zend alternative.'];
        }
        $sites = [];
        foreach ($compiler['sites'] as $site) {
            $classification = $site['phase'] === 'parser-action' && str_contains($site['function'], 'modifier') ? 'contextual-validator-enforced' :
                ($site['phase'] === 'implementation-resource-limit' ? 'documented semantic/out-of-scope' : ($site['phase'] !== 'parser-action' ? 'folding-sensitive' : 'unimplemented-contextual'));
            $sites[] = ['id' => 'compile:' . $site['line'], ...$site, 'classification' => $classification,
                'family_evidence' => self::families($boundaries, $site['function']),
                'evidence_scope' => 'Function-family evidence only; individual diagnostic predicates require isolation.',
                'blocker' => $classification === 'contextual-validator-enforced' || $site['phase'] === 'implementation-resource-limit' ? null : 'C2'];
        }
        return ['source_pin' => Support::PIN, 'source_hashes' => Support::read('tools/8.5/source-lock.json'),
            'hashes' => Support::hashes(['grammar/8.5/php.ebnf', 'docs/8.5/phase3-coverage.json', 'docs/8.5/negative-boundaries.json', 'tests/fixtures/php/8.5/parser-compiler-boundaries.json', 'docs/8.5/compiler-boundaries.json', 'tools/8.5/reconcile.php', 'tools/lib/ParserReconciliation.php', 'tools/lib/Support.php', 'tools/8.5/data/parser-anchors.json']),
            'method' => 'Exact-name or explicit reviewed production anchors; every RHS and action call retained. Evidence links are indexed at production/function-family granularity, not proofs of branch coverage.',
            'parser_productions' => count($rows), 'parser_alternatives' => array_sum(array_map(static fn(array $r): int => count($r['alternatives']), $rows)),
            'reconciliation' => $rows, 'contextual_diagnostic_sites' => $sites,
            'remaining_classifications' => array_values(array_filter($coverage['remaining'], static fn(array $r): bool => in_array($r['classification'], ['contextual-only', 'scanner-context-only'], true)))];
    }

    private static function families(array $cases, string $name): array
    {
        return array_values(array_column(array_filter($cases, static fn(array $c): bool => (bool) preg_match('/\b' . preg_quote($name, '/') . '\b/', $c['evidence'])), 'id'));
    }
}
