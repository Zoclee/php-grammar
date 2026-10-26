<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

require_once __DIR__ . '/Support.php';
require_once __DIR__ . '/Certification86.php';

/** Additive research verification; never changes a release-gate disposition. */
final class LocalSourceContextEvidence
{
    public const CONTRACT = 'php86-local-source-context-v1';
    public const DIR = 'docs/8.6/local-source-context-remediation';
    public const PREDECESSOR = 'docs/8.6/certification-blocker-closure/source-review.json';
    public const EXCLUSIONS = [
        'Zend/zend_compile.c:814:851', 'Zend/zend_compile.c:1262:1295',
        'Zend/zend_compile.c:1344:1377', 'Zend/zend_compile.c:2649:2704',
        'Zend/zend_compile.c:2933:3009', 'Zend/zend_compile.c:8767:9470',
        'Zend/zend_compile.c:11577:12362',
    ];
    private Certification86 $files;

    public function __construct(private readonly string $root)
    {
        $this->files = new Certification86($root);
    }

    public function read(string $name): array { return $this->files->read(self::DIR . '/' . $name); }

    public function ledgerErrors(array $review, array $context): array
    {
        $errors = [];
        $prior = $this->files->read(self::PREDECESSOR);
        $expected = $prior['inventory'];
        foreach ($expected as &$row) {
            if (in_array($row['id'], self::EXCLUSIONS, true)) $row['effectiveDisposition'] = 'phase7e-excluded-no-language-change';
        }
        unset($row);
        if (($review['inventory'] ?? null) !== $expected) $errors[] = 'Changed or lost original hunk identity/disposition';
        if (($review['sourcePins'] ?? null) !== $prior['sourcePins']) $errors[] = 'Source pins changed';
        if (($review['counts'] ?? null) !== array_count_values(array_column($expected, 'effectiveDisposition'))) $errors[] = 'Hunk counts mismatch';
        if (array_column($review['groups'] ?? [], 'id') !== self::EXCLUSIONS) $errors[] = 'Unsupported exclusion membership';
        $queue = array_values(array_filter($prior['inventory'], static fn($r) => in_array($r['effectiveDisposition'], ['pending-direct-review', 'examined-unresolved'], true)));
        if (array_column($review['reviewQueue'] ?? [], 'id') !== array_column($queue, 'id')) $errors[] = 'Review queue membership mismatch';
        $raw = array_column($this->files->read('docs/8.6/compiler-change-reconciliation.json')['hunks'], null, 'id');
        foreach ($review['reviewQueue'] ?? [] as $row) {
            if (($row['hunk'] ?? null) !== ($raw[$row['id']] ?? null)
                || hash('sha256', $row['diff'] ?? '') !== ($raw[$row['id']]['diffSha256'] ?? null)) $errors[] = 'Raw hunk changed: ' . $row['id'];
        }
        $oldContext = $this->files->read('docs/8.6/context-coverage-closure/context-inventory.json')['rows'];
        $rows = $context['rows'] ?? [];
        foreach ($rows as &$row) unset($row['phase7ePredicates'], $row['phase7eDisposition']);
        unset($row);
        if ($rows !== $oldContext) $errors[] = 'Context description identity or predecessor evidence changed';
        if (($review['certificationGranted'] ?? true) !== false || ($review['discoveryComplete'] ?? true) !== false
            || ($context['certificationGranted'] ?? true) !== false) $errors[] = 'Unsupported closure claim';
        return $errors;
    }

    public function sourceErrors(): array
    {
        $errors = [];
        $old = $this->files->read('docs/8.6/source-evidence.json')['files'];
        $new = $this->files->read('docs/8.6/exact-pin-build/authentication.json')['source']['files'];
        $dirs = ['8.5' => '.audit/php-php-src-7a4c627', '8.6' => '.audit/php-php-src-f23b4c2'];
        foreach ($this->read('source-slices.json') as $id => $row) {
            if ($row['commit'] !== ($row['version'] === '8.6' ? Certification86::PIN : Support::PIN)) $errors[] = 'Source reference pin mismatch: ' . $id;
            $expected = $row['version'] === '8.6' ? ($new[$row['path']] ?? null) : ($old[$row['path']]['old'] ?? null);
            $path = $this->root . '/' . $dirs[$row['version']] . '/' . $row['path'];
            if ($expected !== $row['fileSha256'] || !is_file($path) || hash_file('sha256', $path) !== $expected) {
                $errors[] = 'Pinned source mismatch: ' . $id; continue;
            }
            $text = isset($row['start']) ? implode('', array_slice(file($path), $row['start'] - 1, $row['end'] - $row['start'] + 1)) : file_get_contents($path);
            if ($row['text'] !== $text || (isset($row['sliceSha256']) && hash('sha256', $text) !== $row['sliceSha256'])) $errors[] = 'Source slice mismatch: ' . $id;
        }
        foreach ($this->read('source-review.json')['reviewQueue'] as $row) {
            $h = $row['hunk'];
            $lines = explode("\n", rtrim($row['diff'], "\n")); array_shift($lines);
            foreach (['old' => '8.5', 'new' => '8.6'] as $side => $version) {
                $selected = [];
                foreach ($lines as $line) {
                    if ($line === '' || $line[0] === '\\' || $line[0] === ($side === 'old' ? '+' : '-')) continue;
                    $selected[] = substr($line, 1);
                }
                $file = $this->root . '/' . $dirs[$version] . '/' . $h['path'];
                $expected = $old[$h['path']][$side];
                if (hash_file('sha256', $file) !== $expected) $errors[] = 'Hunk source file mismatch: ' . $row['id'];
                $actual = array_slice(explode("\n", Support::text($file)), $h[$side . 'Line'] - 1, $h[$side . 'Count']);
                if ($selected !== $actual) $errors[] = 'Hunk source range mismatch: ' . $row['id'] . ':' . $side;
            }
        }
        return array_values(array_unique($errors));
    }

    public function predicateErrors(): array
    {
        $errors = []; $manifest = $this->read('predicates.json'); $sources = $this->read('source-slices.json');
        $descriptions = array_column($this->read('context-inventory.json')['rows'], null, 'id');
        foreach ($manifest['predicates'] as $id => $p) {
            $source = $sources[$p['source']] ?? [];
            $original = $descriptions[$p['description']]['original'] ?? [];
            if (($source['version'] ?? null) !== '8.6' || $p['start'] < ($source['start'] ?? PHP_INT_MAX)
                || $p['end'] > ($source['end'] ?? 0) || $p['end'] < $p['start']) $errors[] = 'Predicate source range: ' . $id;
            if ($p['productions'] !== ($original['constructs'] ?? null) || $p['normativeClause'] !== ($original['normativeClause'] ?? null)) $errors[] = 'Predicate normative correspondence: ' . $id;
            if ($p['nativeEnforcement'] !== false || $p['externalCompilerRequired'] !== true || $p['wholeDescriptionClosed'] !== false) $errors[] = 'Unsupported predicate claim: ' . $id;
            if ($p['upstreamTest'] !== null && !isset($sources[$p['upstreamTest']])) $errors[] = 'Missing upstream association: ' . $id;
        }
        foreach ($manifest['cases'] as $case) {
            if (!isset($manifest['predicates'][$case['predicate']])) $errors[] = 'Unknown predicate: ' . $case['id'];
            if (!is_file($this->root . '/' . $case['fixture']) || hash_file('sha256', $this->root . '/' . $case['fixture']) !== $case['sha256']) $errors[] = 'Fixture identity mismatch: ' . $case['id'];
        }
        return $errors;
    }

    public function workItemErrors(array $record): array
    {
        $prior = $this->files->read('docs/8.6/final-certification-readiness/remaining-work.json');
        $rows = $record['workItems'] ?? [];
        foreach ($rows as &$row) unset($row['phase7e']);
        unset($row);
        return $rows === $prior['workItems'] && ($record['originalBlockerRows'] ?? null) === $prior['originalBlockerRows']
            && ($record['blockedGates'] ?? null) === $prior['blockedGates'] ? [] : ['Original backlog/blocker/gate requirements changed'];
    }

    public function retainedErrors(): array
    {
        $errors = [];
        foreach ($this->read('retained-evidence.json')['observations'] as $r) {
            [$path, $pointer] = explode('#', $r['pointer'], 2);
            if (hash_file('sha256', $this->root . '/' . $path) !== $r['reportSha256']) $errors[] = 'Retained report changed: ' . $path;
            $value = $this->files->read($path);
            foreach (explode('/', ltrim($pointer, '/')) as $key) $value = $value[$key] ?? [];
            if (($value['id'] ?? null) !== $r['id']) $errors[] = 'Retained observation membership: ' . $r['pointer'];
            if (isset($r['fixtureSha256']) && (!is_file($this->root . '/' . $r['fixture'])
                || hash_file('sha256', $this->root . '/' . $r['fixture']) !== $r['fixtureSha256'])) $errors[] = 'Retained fixture changed: ' . $r['id'];
        }
        return $errors;
    }

    public function inputs(): array
    {
        // Literal paths also let the existing compatibility inventory discover test dependencies.
        $paths = [self::PREDECESSOR, 'docs/8.6/local-source-context-remediation/source-review.json',
            'docs/8.6/local-source-context-remediation/source-slices.json', 'docs/8.6/local-source-context-remediation/predicates.json',
            'docs/8.6/local-source-context-remediation/context-inventory.json', 'tools/lib/LocalSourceContextEvidence.php',
            'tools/8.6/local-source-context.php', 'tools/lib/Support.php', 'tools/lib/Certification86.php',
            'tests/LocalSourceContextEvidenceTest.php', 'docs/8.6/local-source-context-remediation/work-items.json',
            'docs/8.6/local-source-context-remediation/retained-evidence.json',
            'docs/8.6/final-certification-readiness/remaining-work.json',
            'docs/8.6/exact-pin-build/build.json', 'docs/8.6/exact-pin-build/authentication.json',
            'docs/8.6/source-evidence.json', 'docs/8.6/compiler-change-reconciliation.json',
            'docs/8.6/context-coverage-closure/context-inventory.json', 'grammar/8.6/php.ebnf', 'grammar/8.6/php.md'];
        foreach ($this->read('predicates.json')['cases'] as $case) $paths[] = $case['fixture'];
        return $this->files->hashes($paths);
    }

    public function runtime(): array
    {
        $dir = $this->root . '/.audit/php86-exact/source/x64/Release/';
        return ['executable' => hash_file('sha256', $dir . 'php.exe'), 'coreDll' => hash_file('sha256', $dir . 'php8.dll')];
    }

    public function command(array $case): array
    {
        $cmd = [$this->root . '/.audit/php86-exact/source/x64/Release/php.exe', '-n', '-d', 'zend.multibyte=0',
            '-d', 'short_open_tag=1', '-d', 'error_reporting=-1', '-d', 'memory_limit=64M', '-d', 'max_execution_time=5',
            '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0'];
        if ($case['mode'] === 'lint') $cmd[] = '-l';
        return [...$cmd, $this->root . '/' . $case['fixture']];
    }

    public function recordErrors(array $report): array
    {
        $errors = [];
        if (($report['contract'] ?? null) !== self::CONTRACT || ($report['sourceCommit'] ?? null) !== Certification86::PIN) $errors[] = 'Wrong contract/source profile';
        $expectedRuntime = ['executable' => '445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6', 'coreDll' => '1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f'];
        if (($report['runtimeBefore'] ?? null) !== $expectedRuntime || ($report['runtimeAfter'] ?? null) !== $expectedRuntime) $errors[] = 'Runtime identity/custody changed';
        if (($report['certificationGranted'] ?? true) !== false || ($report['nativeEnforcement'] ?? true) !== false) $errors[] = 'Unsupported evidence claim';
        $cases = $this->read('predicates.json')['cases'];
        if (array_column($report['observations'] ?? [], 'id') !== array_column($cases, 'id')) $errors[] = 'Observation membership mismatch';
        foreach ($cases as $i => $case) {
            $row = $report['observations'][$i] ?? [];
            if (($row['case'] ?? null) !== $case || ($row['command'] ?? null) !== $this->command($case)) $errors[] = 'Case/command mismatch: ' . $case['id'];
            $output = str_replace("\r\n", "\n", $row['output'] ?? '');
            if (!isset($row['exit_code']) || (($row['exit_code'] === 0) !== $case['accept'])
                || (!$case['accept'] && !str_contains($output, $case['diagnostic']))
                || (isset($case['expectedOutput']) && $output !== $case['expectedOutput'])
                || ($case['accept'] && $case['mode'] === 'lint' && !str_contains($output, 'No syntax errors detected')))
                $errors[] = 'Observation expectation: ' . $case['id'];
        }
        if (($report['inputsBefore'] ?? null) !== ($report['inputsAfter'] ?? null) || !isset($report['inputsBefore'])) $errors[] = 'Inputs changed during observation';
        return $errors;
    }

    public function observe(string $path): array
    {
        if (file_exists($this->root . '/' . $path)) throw new \RuntimeException('Refusing to overwrite retained observations');
        $errors = [...$this->sourceErrors(), ...$this->predicateErrors(), ...$this->retainedErrors(), ...$this->workItemErrors($this->read('work-items.json')),
            ...$this->ledgerErrors($this->read('source-review.json'), $this->read('context-inventory.json'))];
        if ($errors) throw new \RuntimeException(implode("\n", $errors));
        $report = ['contract' => self::CONTRACT, 'sourceCommit' => Certification86::PIN, 'generatedAt' => gmdate(DATE_ATOM),
            'runtimeBefore' => $this->runtime(), 'inputsBefore' => $this->inputs(), 'certificationGranted' => false, 'nativeEnforcement' => false, 'observations' => []];
        foreach ($this->read('predicates.json')['cases'] as $case) {
            $command = $this->command($case);
            $report['observations'][] = ['id' => $case['id'], 'case' => $case, 'command' => $command, ...Support::run($command, $this->root)];
        }
        $report['runtimeAfter'] = $this->runtime(); $report['inputsAfter'] = $this->inputs();
        $report['errors'] = $this->recordErrors($report);
        $this->files->write($path, $report);
        return $report['errors'];
    }

    public function check(string $path): array
    {
        $report = $this->files->read($path);
        return [...$this->recordErrors($report), ...$this->sourceErrors(), ...$this->predicateErrors(), ...$this->retainedErrors(), ...$this->workItemErrors($this->read('work-items.json')),
            ...$this->ledgerErrors($this->read('source-review.json'), $this->read('context-inventory.json')),
            ...(($report['inputsBefore'] ?? null) === $this->inputs() ? [] : ['Stale evidence inputs']),
            ...(($report['runtimeAfter'] ?? null) === $this->runtime() ? [] : ['Current runtime mismatch'])];
    }
}
