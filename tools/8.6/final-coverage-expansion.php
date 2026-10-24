<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Php\Conformance\{GrammarCoverageAnalyzer, GrammarRepository, Php85CoverageClassification, PhpGrammarMatcher};
use PhpGrammar\Php\Lexing\{Lexer, LexerException};
use PhpGrammar\Repository\RepositoryManifest;

// Finite source-backed coverage evidence. Only reviewed declaration-link cases execute.
$root = dirname(__DIR__, 2);
$options = getopt('', ['php85:', 'php86:', 'sources85:', 'sources86:', 'report:', 'coverage:']);
foreach (['php85', 'php86', 'sources85', 'sources86', 'report', 'coverage'] as $key) {
    if (!isset($options[$key])) { fwrite(STDERR, "Required: --php85 --php86 --sources85 --sources86 --report --coverage\n"); exit(1); }
}
$read = static fn(string $p): array => json_decode(file_get_contents($root . '/' . $p), true, 512, JSON_THROW_ON_ERROR);
$errors = []; $checks = 0; $observations = [];
$check = static function (bool $ok, string $label) use (&$errors, &$checks): void { ++$checks; if (!$ok) $errors[] = $label; };
$run = static function (array $command) use ($root): array {
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('Cannot launch oracle');
    $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
};
$temporary = tempnam(sys_get_temp_dir(), 'php86-coverage-');
try {
    $lock = $read('tools/8.6/source-lock.json');
    $extra = $read('tools/8.6/final-coverage-source-lock.json');
    $data = $read('tests/fixtures/php/8.6/final-coverage-cases.json');
    $lexical = $read('tests/fixtures/php/8.6/final-coverage-lexical.json');
    $manifest = RepositoryManifest::fromRepositoryRoot($root);
    $matcher = PhpGrammarMatcher::forManifest($manifest);
    $check($manifest->package('8.6')->status === 'draft', '8.6 remains draft');
    $check(hash_file('sha256', $root . '/grammar/8.6/php.ebnf') === $extra['grammarSha256'], 'Unchanged 8.6 grammar');
    $check(hash_file('sha256', $root . '/grammar/8.6/php.md') === $extra['specificationSha256'], 'Unchanged 8.6 specification');
    $check(hash_file('sha256', __DIR__ . '/source-lock.json') === $extra['baseLockSha256'], 'Unchanged primary source lock');
    $interpreters = []; $fixtureHashes = []; $sourceHashes = [];
    foreach (['8.5' => '85', '8.6' => '86'] as $version => $suffix) {
        $binary = realpath($options['php' . $suffix]); $sources = realpath($options['sources' . $suffix]);
        if ($binary === false || $sources === false) throw new RuntimeException('Missing oracle/source directory');
        $interpreters[$version] = ['sha256' => hash_file('sha256', $binary), 'version' => $run([$binary, '-n', '-v']), 'exactPinVerified' => false];
        $check($interpreters[$version]['sha256'] === $lock['executables'][$version], 'Oracle hash ' . $version);
        $files = $lock['files'][$version];
        if ($version === '8.6') $files = array_merge($files, $extra['extraRc4Files']);
        foreach ($files as $path => $hash) {
            $actual = is_file($sources . '/' . $path) ? hash_file('sha256', $sources . '/' . $path) : null;
            $sourceHashes[$version][$path] = $actual;
            $check($actual === $hash, 'Source hash ' . $version . ' ' . $path);
        }
        $prefix = [$binary, '-n', '-d', 'zend.multibyte=0', '-d', 'short_open_tag=1', '-d', 'error_reporting=-1'];
        foreach ($data['cases'] as $case) {
            $file = $root . '/' . $case['fixture']; $source = file_get_contents($file);
            $fixtureHashes[$case['fixture']] = hash('sha256', $source);
            $check($fixtureHashes[$case['fixture']] === $case['sha256'], 'Fixture hash ' . $case['id']);
            $coverage = new CoverageCollector();
            $structural = $matcher->withCoverage($coverage)->matches($version, $source)->matched;
            $check($structural === $case['structural' . $suffix], 'Structure ' . $version . ' ' . $case['id']);
            $lint = $run([...$prefix, '-l', $file]);
            if (!in_array($case['stage'], ['lint', 'declaration-link'], true)) throw new RuntimeException('Unreviewed execution stage');
            $result = $case['stage'] === 'declaration-link' ? $run([...$prefix, $file]) : $lint;
            $check(($result['exit'] === 0) === $case['accept' . $suffix], 'Oracle ' . $version . ' ' . $case['id']);
            if ($suffix === '86' && $case['diagnostic86'] !== null) $check(str_contains($result['stdout'] . $result['stderr'], $case['diagnostic86']), 'Diagnostic ' . $case['id']);
            if (isset($case['warning' . $suffix])) $check(str_contains($result['stdout'] . $result['stderr'], 'Deprecated:') === $case['warning' . $suffix], 'Warning boundary ' . $version . ' ' . $case['id']);
            if ($suffix === '86') {
                $check($case['sourceReferences'] !== [], 'Source correspondence ' . $case['id']);
                foreach ($case['sourceReferences'] as $reference) {
                    [$path, $anchor] = array_pad(explode(':', $reference, 2), 2, null);
                    $check(isset($files[$path]), 'Locked source reference ' . $reference);
                    if ($anchor !== null) $check(str_contains(file_get_contents($sources . '/' . $path), $anchor), 'Source anchor ' . $reference);
                }
                if ($case['structural86']) foreach ($case['targets'] as $target) $check(in_array($target, [...$coverage->matchedProductions(), ...$coverage->matchedPrimitives()], true), 'Target ' . $case['id'] . ' ' . $target);
                foreach ($case['selectedAlternatives'] as $id) $check(in_array($id, $coverage->matchedAlternatives(), true), 'Alternative ' . $case['id'] . ' ' . $id);
            }
            $observations[] = ['id' => $case['id'], 'version' => $version, 'stage' => $case['stage'], 'structural' => $structural, 'lint' => $lint, 'result' => $result];
        }
        foreach ($lexical['cases'] as $case) {
            $check(hash('sha256', $case['source']) === $case['sha256'], 'Lexical source hash ' . $case['id']);
            if ($suffix === '86') {
                $evidence = $case['sourceEvidence'];
                $lines = file($sources . '/' . $evidence['path']);
                $slice = implode('', array_slice($lines, $evidence['startLine'] - 1, $evidence['endLine'] - $evidence['startLine'] + 1));
                $check(hash('sha256', $slice) === $evidence['sha256'], 'Scanner source slice ' . $case['id']);
            }
            $tokens = null; $lexError = null;
            try { $tokens = Lexer::forVersion($version)->withShortOpenTag($case['shortOpenTag'])->tokenize($case['source']); }
            catch (LexerException $exception) { $lexError = $exception->getMessage(); }
            $check(($tokens !== null) === $case['lexerAccepted'], 'Lexical result ' . $version . ' ' . $case['id']);
            if ($tokens !== null) {
                $check(implode('', array_map(static fn($t) => $t->lexeme, $tokens->all())) === $case['source'], 'Source bytes ' . $case['id']);
                foreach ($case['requiredTokenTypes'] as $type) $check(in_array($type, array_map(static fn($t) => $t->type->value, $tokens->all()), true), 'Token type ' . $case['id'] . ' ' . $type);
            }
            file_put_contents($temporary, $case['source']);
            $lexPrefix = [$binary, '-n', '-d', 'zend.multibyte=0', '-d', 'short_open_tag=' . (int)$case['shortOpenTag']];
            $lint = $run([...$lexPrefix, '-l', $temporary]);
            $accepted = $case['accept' . $suffix];
            $check(($lint['exit'] === 0) === $accepted, 'Lexical lint ' . $version . ' ' . $case['id']);
            $check($matcher->withShortOpenTag($case['shortOpenTag'])->matches($version, $case['source'])->matched === $accepted, 'Lexical source recognition ' . $version . ' ' . $case['id']);
            $native = null;
            if ($tokens !== null && $case['compareNativeBoundaries']) {
                // The canonical adapter splits Zend's atomic T_YIELD_FROM into two terminals.
                // Normalize only that documented token identity, not arbitrary whitespace/comments.
                $native = $run([...$lexPrefix, '-r', '$out=[]; foreach(token_get_all(file_get_contents($argv[1])) as $t) { if(is_array($t) && in_array($t[0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true)) continue; if(is_array($t) && $t[0]===T_YIELD_FROM) { $out[]="yield"; $out[]="from"; } else $out[]=trim(is_array($t)?$t[1]:$t); } echo json_encode($out);', $temporary]);
                $own = array_map(static fn($t) => trim($t->lexeme), $tokens->withoutTrivia()->all());
                $check($native['exit'] === 0 && json_decode($native['stdout'], true) === $own, 'Token boundaries ' . $version . ' ' . $case['id']);
            }
            $observations[] = ['id' => 'lexical:' . $case['id'], 'version' => $version, 'stage' => 'lexical', 'lexerError' => $lexError, 'lint' => $lint, 'nativeTokenBoundaries' => $native];
        }
    }
    $witnesses = []; $primitives = [];
    $coverage = (new GrammarCoverageAnalyzer($manifest))->analyze('8.6', [], static function ($w, $c) use (&$witnesses, &$primitives): void {
        foreach ([...$c->matchedProductions(), ...$c->matchedAlternatives()] as $id) $witnesses[$id][] = $w;
        foreach ($c->matchedPrimitives() as $id) $primitives[$id][] = $w;
    });
    $baseline = $read('docs/8.6/final-coverage-initial.json');
    $classifier = new Php85CoverageClassification((new GrammarRepository($manifest))->load('8.6'), $matcher->lexicalPrimitiveNames());
    $remaining = [];
    foreach (['production' => $coverage->unexercisedProductions(), 'alternative' => $coverage->unexercisedAlternatives()] as $kind => $ids) foreach ($ids as $id) {
        $row = $classifier->classify($id);
        // Architecture exclusions only; no inherited scanner certification claim.
        if ($row['classification'] === 'scanner-context-only') $row['classification'] = 'unresolved';
        unset($row['evidence']);
        $remaining[] = ['kind' => $kind, 'identity' => $id] + $row;
    }
    $new = [];
    foreach (['production' => $coverage->matched->matchedProductions(), 'alternative' => $coverage->matched->matchedAlternatives()] as $kind => $ids) $new[$kind] = array_values(array_filter($ids, static fn($id) => !isset($baseline['positiveWitnesses'][$id])));
    $summary = ['productions' => $coverage->exercisedProductions(), 'totalProductions' => $coverage->totalProductions(), 'productionPercent' => $coverage->productionCoveragePercentage(), 'alternatives' => $coverage->exercisedAlternatives(), 'totalAlternatives' => $coverage->totalAlternatives(), 'alternativePercent' => $coverage->alternativeCoveragePercentage()];
    $check($summary['totalProductions'] === 361 && $summary['totalAlternatives'] === 800, 'Unchanged coverage denominators');
    $coverageReport = ['method' => 'Existing positive completed-chart-item method, no added rule cases; lexical primitives separate.', 'baseline' => $baseline['current'], 'current' => $summary, 'newIdentities' => $new, 'positiveWitnesses' => $witnesses, 'primitiveWitnesses' => $primitives, 'remaining' => $remaining];
    $hashes = []; foreach (['tools/8.6/final-coverage-expansion.php', 'tests/fixtures/php/8.6/final-coverage-cases.json', 'tests/fixtures/php/8.6/final-coverage-lexical.json', 'tools/8.6/final-coverage-source-lock.json'] as $p) $hashes[$p] = hash_file('sha256', $root . '/' . $p);
    $report = ['scope' => 'Phase 5E finite evidence, not completeness/certification or contextual enforcement.', 'sourcePins' => $extra['sourcePins'], 'inputHashes' => $hashes, 'fixtureHashes' => $fixtureHashes, 'sourceHashes' => $sourceHashes, 'interpreters' => $interpreters, 'checks' => $checks, 'observations' => $observations, 'errors' => $errors];
    array_walk_recursive($report, static function (&$v) use ($temporary, $root): void { if (is_string($v)) $v = str_replace([$temporary, $root, str_replace('\\', '/', $root)], ['<probe>', '<repository>', '<repository>'], $v); });
    foreach ([$options['report'] => $report, $options['coverage'] => $coverageReport] as $path => $value) file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    echo json_encode(['coverage' => $summary, 'checks' => $checks, 'observations' => count($observations), 'errors' => $errors], JSON_PRETTY_PRINT) . "\n";
    $exit = $errors === [] ? 0 : 1;
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); $exit = 1; }
finally { unlink($temporary); }
exit($exit);
