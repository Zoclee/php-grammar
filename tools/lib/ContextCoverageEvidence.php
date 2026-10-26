<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Php\Conformance\{GrammarCoverageAnalyzer, GrammarRepository, PhpGrammarMatcher};
use PhpGrammar\Php\Lexing\Lexer;
use PhpGrammar\Repository\RepositoryManifest;
use PhpGrammar\Tests\Support\DerivationForest;

require_once __DIR__ . '/Certification86.php';
require_once __DIR__ . '/Support.php';
require_once __DIR__ . '/ExactPinProvenance.php';
require_once __DIR__ . '/ExactPinReconciliation.php';

/** Bounded research evidence. No native compiler or release-gate replacement. */
final class ContextCoverageEvidence
{
    public const MANIFEST = 'tests/fixtures/php/8.6/context-coverage-cases.json';
    public const CONTRACT = 'php86-context-coverage-evidence-1';
    private Certification86 $files;

    public function __construct(private readonly string $root)
    {
        $this->files = new Certification86($root);
    }

    public function manifest(): array { return $this->files->read(self::MANIFEST); }
    public function binary(): string { return $this->root . '/.audit/php86-exact/source/x64/Release/php.exe'; }

    public function authenticate(): array
    {
        $build = $this->files->read('docs/8.6/exact-pin-build/build.json');
        return [...$this->files->sourceErrors($this->files->read('tools/8.6/certification-source-lock.json'), $this->root . '/.audit/php-php-src-f23b4c2'),
            ...(new ExactPinProvenance($this->root))->errors($build, $this->binary()), ...$this->sourceErrors()];
    }

    public function sourceErrors(): array
    {
        $errors = [];
        foreach ($this->manifest()['sources'] as $row) {
            foreach (['.audit/php-php-src-f23b4c2/', '.audit/php86-exact/source/'] as $base) {
                $file = $this->root . '/' . $base . $row['path'];
                if (!is_file($file) || hash_file('sha256', $file) !== $row['fileSha256']) { $errors[] = 'Pinned source mismatch: ' . $row['id']; continue; }
                if (isset($row['start'])) {
                    $slice = implode('', array_slice(file($file), $row['start'] - 1, $row['end'] - $row['start'] + 1));
                    if (hash('sha256', $slice) !== $row['sliceSha256']) $errors[] = 'Source range mismatch: ' . $row['id'];
                }
            }
        }
        return $errors;
    }

    public function inputs(): array
    {
        $paths = [self::MANIFEST, 'tools/lib/ContextCoverageEvidence.php', 'tools/8.6/context-coverage.php',
            'tests/ContextCoverageEvidenceTest.php', 'tests/Support/DerivationForest.php',
            'tools/lib/Certification86.php', 'tools/lib/ExactPinProvenance.php', 'tools/lib/ExactPinReconciliation.php',
            'tools/lib/Support.php', 'tools/8.6/source-lock.json', 'tools/8.6/certification-source-lock.json',
            'docs/8.6/exact-pin-build/build.json', 'docs/8.6/final-coverage.json',
            'docs/8.6/conformance-scope-adjudication/restrictions.json', 'docs/conformance-policy.md',
            'docs/8.6/source-completeness-ledger.json', 'grammar/8.6/php.ebnf', 'grammar/8.6/php.md',
            'php-grammar.json', 'composer.json', 'composer.lock'];
        foreach (Support::files($this->root . '/src') as $p) $paths[] = substr($p, strlen($this->root) + 1);
        foreach (['context', 'coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $row) {
            $paths[] = $row['fixture'];
            if (isset($row['forcedFixture'])) $paths[] = $row['forcedFixture'];
        }
        foreach (glob($this->root . '/tests/fixtures/php/8.6/valid/*.php') as $p) $paths[] = substr(str_replace('\\', '/', $p), strlen($this->root) + 1);
        return $this->files->hashes(array_values(array_unique($paths)));
    }

    public function prefix(bool $short = true): array
    {
        return [$this->binary(), '-n', '-d', 'zend.multibyte=0', '-d', 'short_open_tag=' . (int)$short,
            '-d', 'error_reporting=-1', '-d', 'memory_limit=64M', '-d', 'max_execution_time=5',
            '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];
    }

    public function lintCommand(array $case, bool $dump = false, ?string $fixture = null): array
    {
        $command = $this->prefix($case['shortOpenTag'] ?? true);
        if ($dump) $command = [...$command, '-d', 'opcache.enable_cli=1', '-d', 'opcache.optimization_level=0',
            '-d', 'opcache.opt_debug_level=65536', '-d', 'opcache.file_update_protection=0'];
        return [...$command, '-l', $this->root . '/' . ($fixture ?? $case['fixture'])];
    }

    public function tokenCommand(array $case): array
    {
        $program = 'try {$r=[]; foreach(token_get_all(file_get_contents($argv[1])) as $t) $r[]=is_array($t)?[token_name($t[0]),$t[1]]:[null,$t]; echo json_encode(["tokens"=>$r],JSON_THROW_ON_ERROR);} catch(Throwable $e){echo json_encode(["error"=>$e->getMessage()],JSON_THROW_ON_ERROR);}';
        return [...$this->prefix($case['shortOpenTag']), '-r', $program, $this->root . '/' . $case['fixture']];
    }

    private function run(array $command): array
    {
        return ['command' => $command, ...Support::run($command, $this->root)];
    }

    /** Complete precedence operands only; pending contexts and targets are not parenthesized. */
    public static function derive(string $root, string $expression): array
    {
        $grammar = (new GrammarRepository($root))->load('8.6');
        $trees = (new DerivationForest($grammar, $expression))->trees('expression');
        if (count($trees) !== 1) throw new \RuntimeException('Binding witness requires one native derivation');
        $layers = array_fill_keys(explode(' ', 'expression throw-expression arrow-expression include-expression logical-or-expression logical-xor-expression logical-and-expression print-expression yield-expression yield-key-expression yield-from-expression assignment-expression conditional-expression coalesce-expression boolean-or-expression boolean-and-expression bitwise-or-expression bitwise-xor-expression bitwise-and-expression equality-expression relational-expression pipe-expression concatenation-expression shift-expression additive-expression multiplicative-expression boolean-not-expression instanceof-expression unary-expression cast-expression power-expression clone-expression'), true);
        $spans = [];
        $walk = function(array $tree) use (&$walk, &$spans, $layers): void {
            if (isset($layers[$tree['name']]) && $tree['end'] - $tree['start'] > 1) $spans[$tree['start'] . ':' . $tree['end']] = [$tree['start'], $tree['end']];
            foreach ($tree['children'] as $child) $walk($child);
        };
        $walk($trees[0]); ksort($spans);
        $tokens = array_map(static fn($t) => is_array($t) ? $t[1] : $t, DerivationForest::tokens($expression));
        $opens = $closes = [];
        foreach ($spans as [$a, $b]) { $opens[$a] = ($opens[$a] ?? 0) + 1; $closes[$b] = ($closes[$b] ?? 0) + 1; }
        $forced = '';
        for ($i = 0; $i <= count($tokens); ++$i) {
            $forced .= str_repeat(') ', $closes[$i] ?? 0) . str_repeat('( ', $opens[$i] ?? 0);
            if (isset($tokens[$i])) $forced .= $tokens[$i] . ' ';
        }
        return ['uniqueDerivations' => 1, 'hostTokenization' => 'PHP 8.5 test-only adapter', 'tokens' => $tokens, 'operandSpans' => array_values($spans), 'forced' => trim($forced)];
    }

    public function native(string $group, array $case): array
    {
        $source = file_get_contents($this->root . '/' . $case['fixture']);
        if (hash('sha256', $source) !== $case['sha256']) throw new \RuntimeException('Fixture hash: ' . $case['id']);
        if ($group === 'binding') {
            $derived = self::derive($this->root, $case['expression']);
            $forced = file_get_contents($this->root . '/' . $case['forcedFixture']);
            if ($source !== self::wrapper($case['expression']) || $forced !== self::wrapper($derived['forced'])
                || hash('sha256', $forced) !== $case['forcedSha256']) throw new \RuntimeException('Binding fixture derivation mismatch: ' . $case['id']);
            return $derived;
        }
        if ($group === 'scanner') {
            try {
                $tokens = Lexer::forVersion('8.6')->withShortOpenTag($case['shortOpenTag'])->tokenize($source);
                return ['accepted' => true, 'bytePreserved' => implode('', array_map(static fn($t) => $t->lexeme, $tokens->all())) === $source,
                    'lexemes' => array_map(static fn($t) => trim($t->lexeme), $tokens->withoutTrivia()->all()), 'error' => null];
            } catch (\PhpGrammar\Php\Lexing\LexerException $e) { return ['accepted' => false, 'bytePreserved' => null, 'lexemes' => [], 'error' => $e->getMessage()]; }
        }
        $coverage = new CoverageCollector();
        $matched = PhpGrammarMatcher::forRepositoryRoot($this->root)->withCoverage($coverage)->matches('8.6', $source)->matched;
        return ['matched' => $matched, 'productions' => $coverage->matchedProductions(), 'alternatives' => $coverage->matchedAlternatives()];
    }

    public function coverage(): array
    {
        $witnesses = [];
        $r = (new GrammarCoverageAnalyzer(RepositoryManifest::fromRepositoryRoot($this->root)))->analyze('8.6', [], static function($id, $c) use (&$witnesses): void {
            foreach ([...$c->matchedProductions(), ...$c->matchedAlternatives()] as $target) $witnesses[$target][] = $id;
        });
        ksort($witnesses);
        return ['method' => 'Accepted ordinary valid/*.php sources; completed chart items; zero extra rule-level cases. No unique-derivation claim.',
            'productions' => $r->exercisedProductions(), 'totalProductions' => $r->totalProductions(),
            'alternatives' => $r->exercisedAlternatives(), 'totalAlternatives' => $r->totalAlternatives(),
            'matchedProductions' => $r->matched->matchedProductions(), 'matchedAlternatives' => $r->matched->matchedAlternatives(),
            'remainingProductions' => $r->unexercisedProductions(), 'remainingAlternatives' => $r->unexercisedAlternatives(), 'positiveWitnesses' => $witnesses];
    }

    public function generate(): array
    {
        $errors = $this->authenticate();
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $inputs = $this->inputs(); $identity = $this->runtimeIdentity(); $rows = [];
        foreach (['context', 'coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $case) {
            $row = ['group' => $group, 'id' => $case['id'], 'fixture' => $case['fixture'], 'sha256' => $case['sha256'], 'native' => $this->native($group, $case)];
            if ($group === 'binding') {
                $row['originalDump'] = $this->run($this->lintCommand($case, true));
                $row['forcedDump'] = $this->run($this->lintCommand($case, true, $case['forcedFixture']));
            } else {
                $row['lint'] = $this->run($this->lintCommand($case));
                if ($group === 'scanner') $row['tokenization'] = $this->run($this->tokenCommand($case));
            }
            $rows[] = $row;
        }
        $report = ['contract' => self::CONTRACT, 'sourceCommit' => Certification86::PIN, 'profile' => 'exact-pin-rc4',
            'executableSha256' => hash_file('sha256', $this->binary()), 'generatedAt' => gmdate('c'), 'inputs' => $inputs,
            'inputsUnchanged' => $inputs === $this->inputs(), 'runtimeBefore' => $identity,
            'runtimeAfter' => $this->runtimeIdentity(), 'sourceErrorsAfter' => $this->sourceErrors(),
            'observations' => $rows, 'coverage' => $this->coverage(),
            'certificationGranted' => false, 'nativeContextualValidatorComplete' => false, 'instrumentedScannerHits' => false, 'astEquivalenceClaim' => false];
        $report['errors'] = $this->recordErrors($report);
        return $report;
    }

    public static function wrapper(string $expression): string
    {
        return '<?php' . "\n" . 'function audit($a,$b,$c,$d){ ' . $expression . "; }\n";
    }

    public function runtimeIdentity(): array
    {
        return ['executable' => hash_file('sha256', $this->binary()),
            'coreDll' => hash_file('sha256', dirname($this->binary()) . '/php8.dll'),
            'driver' => hash_file('sha256', PHP_BINARY), 'driverVersion' => PHP_VERSION];
    }

    /** Check retained observations against reviewed expectations; do not rerun oracle probes. */
    public function recordErrors(array $report): array
    {
        $errors = [];
        if (($report['contract'] ?? '') !== self::CONTRACT || ($report['sourceCommit'] ?? '') !== Certification86::PIN
            || ($report['profile'] ?? '') !== 'exact-pin-rc4' || ($report['executableSha256'] ?? '') !== '445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6') $errors[] = 'Wrong evidence profile';
        foreach (['certificationGranted', 'nativeContextualValidatorComplete', 'instrumentedScannerHits', 'astEquivalenceClaim'] as $flag) if (($report[$flag] ?? null) !== false) $errors[] = 'Unsupported claim: ' . $flag;
        if (($report['inputsUnchanged'] ?? false) !== true) $errors[] = 'Inputs changed during generation';
        if (!isset($report['runtimeBefore'], $report['runtimeAfter']) || $report['runtimeBefore'] !== $report['runtimeAfter']
            || ($report['runtimeBefore']['executable'] ?? '') !== ($report['executableSha256'] ?? null)
            || ($report['runtimeBefore']['coreDll'] ?? '') !== '1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f'
            || ($report['sourceErrorsAfter'] ?? null) !== []) $errors[] = 'Runtime/source custody changed';
        $i = 0;
        foreach (['context', 'coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $case) {
            $row = $report['observations'][$i++] ?? [];
            if (($row['group'] ?? '') !== $group || ($row['id'] ?? '') !== $case['id'] || ($row['fixture'] ?? '') !== $case['fixture'] || ($row['sha256'] ?? '') !== $case['sha256']) { $errors[] = 'Missing/reordered observation: ' . $case['id']; continue; }
            $native = $row['native'] ?? [];
            if ($group === 'binding') {
                foreach (['originalDump' => $case['fixture'], 'forcedDump' => $case['forcedFixture']] as $key => $path) {
                    if (($row[$key]['command'] ?? []) !== $this->lintCommand($case, true, $path) || ($row[$key]['exit_code'] ?? -1) !== 0) $errors[] = 'Invalid opcode observation: ' . $case['id'];
                }
                $opcodes = ExactPinReconciliation::opcodeLines($row['originalDump']['output'] ?? '');
                if ($opcodes === [] || $opcodes !== ExactPinReconciliation::opcodeLines($row['forcedDump']['output'] ?? '')) $errors[] = 'Operand correspondence: ' . $case['id'];
                if (($native['uniqueDerivations'] ?? 0) !== 1 || empty($native['operandSpans'])) $errors[] = 'Missing native operand evidence: ' . $case['id'];
                continue;
            }
            $lint = $row['lint'] ?? [];
            if (($lint['command'] ?? []) !== $this->lintCommand($case) || !isset($lint['exit_code']) || (($lint['exit_code'] === 0) !== $case['accepted'])) $errors[] = 'Lint expectation: ' . $case['id'];
            if (($case['diagnostic'] ?? null) !== null && !str_contains($lint['output'] ?? '', $case['diagnostic'])) $errors[] = 'Predicate diagnostic: ' . $case['id'];
            if ($group !== 'scanner') {
                if (($native['matched'] ?? null) !== $case['structural']) $errors[] = 'Structural expectation: ' . $case['id'];
                foreach ($case['selectedIdentities'] ?? [] as $id) if (!in_array($id, [...($native['productions'] ?? []), ...($native['alternatives'] ?? [])], true)) $errors[] = 'Unexercised selected identity: ' . $id;
            } else {
                if (($native['accepted'] ?? null) !== $case['lexerAccepted'] || ($case['lexerAccepted'] && ($native['bytePreserved'] ?? false) !== true)) $errors[] = 'Scanner expectation: ' . $case['id'];
                $tokenization = $row['tokenization'] ?? [];
                if (($tokenization['command'] ?? []) !== $this->tokenCommand($case) || ($tokenization['exit_code'] ?? -1) !== 0) $errors[] = 'Token transport: ' . $case['id'];
                $tokens = json_decode($tokenization['output'] ?? '', true);
                if (!is_array($tokens) || (!isset($tokens['tokens']) && !isset($tokens['error']))) $errors[] = 'Missing token observation: ' . $case['id'];
                if ($case['compareTokenBoundaries']) {
                    $lexemes = [];
                    foreach ($tokens['tokens'] ?? [] as [$kind, $text]) {
                        if (in_array($kind, ['T_WHITESPACE', 'T_COMMENT', 'T_DOC_COMMENT'], true)) continue;
                        if ($kind === 'T_YIELD_FROM') { $lexemes[] = 'yield'; $lexemes[] = 'from'; } else $lexemes[] = trim($text);
                    }
                    if ($lexemes !== ($native['lexemes'] ?? [])) $errors[] = 'Token-boundary correspondence: ' . $case['id'];
                }
            }
        }
        if (count($report['observations'] ?? []) !== $i) $errors[] = 'Unexpected observation membership';
        if (($report['coverage']['totalProductions'] ?? 0) !== 361 || ($report['coverage']['totalAlternatives'] ?? 0) !== 800) $errors[] = 'Coverage denominator changed';
        return $errors;
    }

    public function verify(string $path): array
    {
        $report = $this->files->read($path);
        $receipt = $this->files->read($path . '.receipt.json');
        $errors = $this->recordErrors($report);
        if (($receipt['sha256'] ?? '') !== hash_file('sha256', $this->root . '/' . $path)) $errors[] = 'Retained receipt mismatch';
        if (($report['inputs'] ?? []) !== $this->inputs()) $errors[] = 'Stale/incomplete input inventory';
        if (($report['errors'] ?? null) !== []) $errors[] = 'Recorded execution failures';
        if ($errors !== []) return $errors;
        $errors = $this->authenticate();
        $i = 0;
        foreach (['context', 'coverage', 'scanner', 'binding'] as $group) foreach ($this->manifest()[$group] as $case) {
            if ($report['observations'][$i++]['native'] !== $this->native($group, $case)) $errors[] = 'Native evidence differs: ' . $case['id'];
        }
        if ($report['coverage'] !== $this->coverage()) $errors[] = 'Current coverage differs';
        return $errors;
    }
}
