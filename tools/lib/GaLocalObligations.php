<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/ParserReconciliation.php';

/** Bounded, independently recomputed source obligations; never discovery closure. */
final class GaLocalObligations
{
    public function __construct(private readonly string $root) {}
    private function read(string $p): array { return json_decode(file_get_contents($this->root . '/' . $p), true, 512, JSON_THROW_ON_ERROR); }
    public static function includeErrors(array $sources): array
    {
        $errors = [];
        $compiler = $sources['Zend/zend_compile.c'];
        $zend = $sources['Zend/zend.h'];
        $types = $sources['Zend/zend_types.h'];
        foreach (['zend_types.h', 'zend_portability.h', 'zend_string.h'] as $name) {
            $first = strpos($compiler, '#include "zend.h"');
            $second = strpos($compiler, '#include "' . $name . '"');
            if ($first === false || $second === false || $first >= $second) $errors[] = 'Missing prior zend.h include: ' . $name;
        }
        foreach (['zend_types.h', 'zend_string.h'] as $name) {
            if (!str_contains($zend, '#include "' . $name . '"')) $errors[] = 'Missing transitive include: ' . $name;
        }
        if (!str_contains($types, '#include "zend_portability.h"')) $errors[] = 'Missing portability include';
        foreach (['zend.h' => 'ZEND_H', 'zend_types.h' => 'ZEND_TYPES_H', 'zend_portability.h' => 'ZEND_PORTABILITY_H', 'zend_string.h' => 'ZEND_STRING_H'] as $name => $guard) {
            if (!preg_match('/#ifndef ' . $guard . '\R#define ' . $guard . '\b/', $sources['Zend/' . $name])
                || !preg_match('/#endif[^\n]*\s*$/', $sources['Zend/' . $name])) $errors[] = 'Missing include guard: ' . $name;
        }
        foreach ($sources as $path => $source) {
            if (preg_match('/#\s*undef\s+ZEND_(?:H|TYPES_H|PORTABILITY_H|STRING_H)\b/', $source)) $errors[] = 'Include guard reset: ' . $path;
        }
        return $errors;
    }
    public function derive(): array
    {
        $baselinePath = 'docs/8.6/ga-certification-remediation/baseline.json';
        if (hash_file('sha256', $this->root . '/' . $baselinePath) !== 'b2a0d55a7cec36ef78d9b7e307af04f407643965c5b33553fe2137bcdcd6f6ed') throw new \RuntimeException('Untrusted correspondence baseline');
        $baseline = $this->read($baselinePath)['files'];
        foreach (['docs/8.6/source-completeness-ledger.json', 'docs/8.6/local-source-context-remediation/source-review.json', 'tools/8.6/certification-source-lock.json'] as $p) {
            if (hash_file('sha256', $this->root . '/' . $p) !== $baseline[$p]) throw new \RuntimeException('Historical source authority changed: ' . $p);
        }
        $authPath = 'docs/8.6/ga-finalization/build-source.json';
        if (hash_file('sha256', $this->root . '/' . $authPath) !== 'f5aaf11a1674cf81c249f5414cd9c5ad67bfc598cbcf6c755608781b588b347c') throw new \RuntimeException('Untrusted GA source inventory');
        $auth = $this->read($authPath)['sourceHashes']; $files = []; $sourceHashes = [];
        foreach ($auth as $path => $hash) {
            if ((!str_starts_with($path, 'Zend/') || !str_ends_with($path, '.h'))
                && !in_array($path, ['Zend/zend_compile.c', 'Zend/zend_language_parser.y', 'Zend/zend_language_scanner.l'], true)) continue;
            $source = file_get_contents($this->root . '/.audit/phase8b/source/' . $path);
            if (hash('sha256', $source) !== $hash) throw new \RuntimeException('Changed authenticated source: ' . $path);
            $files[$path] = $source; $sourceHashes[$path] = $hash;
        }
        $errors = self::includeErrors($files);
        $review = $this->read('docs/8.6/local-source-context-remediation/source-review.json');
        $matches = array_values(array_filter($review['reviewQueue'], static fn($r) => $r['id'] === 'Zend/zend_compile.c:27:26'));
        if (count($matches) !== 1 || hash('sha256', $matches[0]['diff']) !== '131b79cb7dae4b8f349520a5c37223757346320b23158e63c41f282f5411d9f5'
            || $matches[0]['effectiveDisposition'] !== 'pending-direct-review') throw new \RuntimeException('Unexpected source review identity');
        $sourceProof = [
            'id' => $matches[0]['id'], 'original' => $matches[0],
            'disposition' => $errors === [] ? 'ga-source-reviewed-redundant-includes' : 'pending-direct-review',
            'reason' => 'The only additions are three guarded headers. zend.h is included earlier and already includes zend_types.h and zend_string.h; zend_types.h already includes zend_portability.h. No relevant guard is undefined in the authenticated Zend headers or compiler translation unit. Repeated guarded inclusions introduce no parser, scanner, compiler predicate or runtime operation in this build profile.',
            'scope' => 'Only this exact include hunk. Header-body changes, new partial-application dependencies and all other transitive compiler obligations remain in their existing records.',
            'regression' => 'Source-only preprocessing equivalence; no syntax fixture is meaningful for these redundant inclusions. Retain the exact GA build verification and full regression gates.',
            'errors' => $errors,
        ];
        $counts = $review['counts'];
        if ($errors === []) { --$counts['pending-direct-review']; $counts['ga-source-reviewed-redundant-includes'] = 1; }
        $ledger = $this->read('docs/8.6/source-completeness-ledger.json');
        $lock = $this->read('tools/8.6/certification-source-lock.json')['files'];
        $parser = $files['Zend/zend_language_parser.y']; $scanner = $files['Zend/zend_language_scanner.l'];
        foreach (['Zend/zend_language_parser.y', 'Zend/zend_language_scanner.l'] as $path) {
            if ($lock[$path] !== $sourceHashes[$path]) $errors[] = 'Historical correspondence source changed: ' . $path;
        }
        preg_match_all('/^([a-z][a-z_]*):/m', $parser, $headers, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $parserRows = []; $branchTotal = 0;
        $grammar = (new \PhpGrammar\Ebnf\Parser())->parse(file_get_contents($this->root . '/grammar/8.6/php.ebnf'));
        foreach ($headers as $i => $header) {
            $name = $header[1][0]; $start = $header[0][1] + strlen($header[0][0]);
            $end = $headers[$i + 1][0][1] ?? strpos($parser, "\n%%", $start);
            $branches = ParserReconciliation::branches(substr($parser, $start, $end - $start));
            $old = $ledger['parser']['rows'][$name] ?? null;
            if ($old === null) { $errors[] = 'Unmapped parser production: ' . $name; continue; }
            if ($old['line'] !== substr_count(substr($parser, 0, $header[0][1]), "\n") + 1 || count($old['branches']) !== count($branches)) $errors[] = 'Parser identity changed: ' . $name;
            foreach ($branches as $b => [$rhs, $action]) {
                preg_match_all('/\b(zend_\w+)\s*\(/', $action, $calls);
                $prior = $old['branches'][$b] ?? [];
                if (($prior['rhs'] ?? null) !== $rhs || ($prior['actionSha256'] ?? null) !== hash('sha256', $action)
                    || ($prior['actionCalls'] ?? null) !== array_values(array_unique($calls[1]))) $errors[] = 'Parser branch/action mapping changed: ' . $name . ':' . ($b + 1);
            }
            foreach ($old['ebnf'] as $anchor) if (!$grammar->hasProduction($anchor)) $errors[] = 'Missing canonical parser anchor: ' . $anchor;
            $parserRows[$name] = $old; $branchTotal += count($branches);
        }
        if (array_keys($parserRows) !== array_keys($ledger['parser']['rows'])) $errors[] = 'Incomplete parser correspondence';
        $lines = explode("\n", $scanner); $rules = [];
        foreach ($lines as $i => $line) if (preg_match('/^<[A-Z_,]+>/', $line)) {
            $end = strrpos($line, ' {');
            $rules[] = ['line' => $i + 1, 'rule' => rtrim($end === false ? $line : substr($line, 0, $end))];
        }
        if (count($rules) !== count($ledger['scanner']['rules'])) $errors[] = 'Scanner rule inventory changed';
        foreach ($rules as $i => $rule) {
            $end = ($rules[$i + 1]['line'] ?? count($lines) + 1) - 1;
            $text = implode("\n", array_slice($lines, $rule['line'] - 1, $end - $rule['line'] + 1));
            $old = $ledger['scanner']['rules'][$i] ?? [];
            if (($old['rule'] ?? null) !== $rule['rule'] || ($old['line'] ?? null) !== $rule['line']
                || ($old['endLine'] ?? null) !== $end || ($old['sourceSliceSha256'] ?? null) !== hash('sha256', $text)) $errors[] = 'Scanner slice correspondence changed: ' . $i;
        }
        return ['contract' => 'php86-ga-local-obligations-v1', 'sourceCommit' => '8aaada3f9605d81a38b8240f93c7389dfbc77ef9',
            'sources' => $sourceHashes, 'sourceProof' => $sourceProof, 'priorDiscoveryCounts' => $review['counts'], 'currentDiscoveryCounts' => $counts,
            'parser' => ['productions' => count($parserRows), 'alternatives' => $branchTotal, 'rows' => $parserRows],
            'scanner' => ['ruleCount' => count($rules), 'rules' => $ledger['scanner']['rules'], 'states' => $ledger['scanner']['states']],
            'retainedMapping' => ['path' => 'docs/8.6/source-completeness-ledger.json', 'sha256' => hash_file('sha256', $this->root . '/docs/8.6/source-completeness-ledger.json')],
            'claim' => 'Reverified full retained parser/scanner correspondence at byte-identical GA sources, including action hashes, scanner slices and EBNF anchors. Original witness and instrumentation limitations remain. One redundant-include hunk excluded; no broader discovery closure.',
            'errors' => $errors, 'discoveryComplete' => false, 'certificationGranted' => false];
    }
}
