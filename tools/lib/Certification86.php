<?php

declare(strict_types=1);

namespace PhpGrammar\Tools;

use RuntimeException;

/** Maintainer-only Phase 7A infrastructure. Never changes a package's status. */
final class Certification86
{
    public const PIN = 'f23b4c222cebe33be93ee8ecaaa14390c5507a08';
    public const REPORT = 'docs/8.6/final-evidence.json';
    public const OUTPUT = 'docs/8.6/certification';
    public const CATEGORIES = ['grammar integrity', 'positive conformance', 'negative boundary',
        'lexical/scanner', 'AST/binding', 'folding', 'contextual validation',
        'upstream source mapping', 'differential executable'];
    public const BLOCKERS = ['PHP86-013', 'PHP86-014', 'PHP86-015', 'CA86-001',
        'C6-CONTEXT', 'C6-COVERAGE', 'C6-PROVENANCE'];
    public const CORE = ['Zend/zend_language_parser.y', 'Zend/zend_language_scanner.l',
        'Zend/zend_compile.c', 'Zend/zend_compile.h', 'Zend/zend_ast.c', 'Zend/zend_ast.h',
        'Zend/zend_inheritance.c', 'Zend/zend_attributes.c', 'Zend/zend_enum.c',
        'Zend/zend_constants.c', 'Zend/zend_vm_def.h'];

    public function __construct(public readonly string $root) {}

    public function read(string $path): array
    {
        $file = $this->path($path);
        if (!is_file($file)) {
            throw new RuntimeException('Missing evidence artifact: ' . $path);
        }
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Expected JSON object: ' . $path);
        }
        return $data;
    }

    public function path(string $path): string
    {
        if ($path === '' || str_contains($path, '\\') || str_starts_with($path, '/')
            || str_contains($path, ':') || in_array('..', explode('/', $path), true)) {
            throw new RuntimeException('Expected repository-relative path: ' . $path);
        }
        return $this->root . '/' . $path;
    }

    public function hashes(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            $file = $this->path($path);
            if (!is_file($file)) {
                throw new RuntimeException('Missing evidence artifact: ' . $path);
            }
            $result[$path] = hash_file('sha256', $file);
        }
        ksort($result);
        return $result;
    }

    public function verifyHashes(array $hashes): array
    {
        $errors = [];
        foreach ($hashes as $path => $expected) {
            $file = $this->path($path);
            if (!is_file($file)) {
                $errors[] = 'Missing evidence artifact: ' . $path;
            } elseif (!is_string($expected) || !preg_match('/^[a-f0-9]{64}$/D', $expected)
                || hash_file('sha256', $file) !== $expected) {
                $errors[] = 'Stale evidence digest: ' . $path;
            }
        }
        return $errors;
    }

    /** Include additions and deletions, not just paths the old report happened to name. */
    public function inputs(): array
    {
        $paths = ['.gitattributes', 'AGENTS.md', 'README.md', 'composer.json', 'composer.lock',
            'php-grammar.json', 'phpunit.xml.dist'];
        foreach (['src', 'tests', 'grammar', 'bin', 'tools', 'schema', 'docs'] as $dir) {
            foreach (Support::files($this->root . '/' . $dir) as $file) {
                $path = substr($file, strlen(str_replace('\\', '/', $this->root)) + 1);
                if ($path !== self::REPORT && !str_starts_with($path, self::OUTPUT . '/')) {
                    $paths[] = $path;
                }
            }
        }
        return $this->hashes($paths);
    }

    public function sourceErrors(array $lock, string $directory): array
    {
        $errors = [];
        if (($lock['phpSource']['commit'] ?? null) !== self::PIN
            || ($lock['phpSource']['tag'] ?? null) !== 'php-8.6.0RC4'
            || ($lock['version'] ?? null) !== '8.6') {
            $errors[] = 'Source pin/release mismatch';
        }
        // The required set is independently derived from the reviewed records.
        $required = [];
        foreach ($this->read('docs/8.6/source-evidence.json')['files'] as $path => $row) {
            if ($row['new'] !== null) $required[$path] = $row['new'];
        }
        foreach ($this->read('tools/8.6/source-lock.json')['files']['8.6'] as $path => $hash) {
            if (isset($required[$path]) && $required[$path] !== $hash) $errors[] = 'Conflicting source record: ' . $path;
            $required[$path] = $hash;
        }
        foreach (self::CORE as $path) {
            if (!isset($required[$path])) $errors[] = 'Core source absent from reviewed records: ' . $path;
        }
        foreach ($required as $path => $expected) {
            if (!isset($lock['files'][$path])) {
                $errors[] = 'Missing source-lock entry: ' . $path;
                continue;
            }
            if ($lock['files'][$path] !== $expected) $errors[] = 'Source lock differs from reviewed record: ' . $path;
            $file = $directory . '/' . $path;
            if (!is_file($file) || hash_file('sha256', $file) !== $expected) $errors[] = 'Source hash mismatch: ' . $path;
        }
        if (array_diff(array_keys($lock['files'] ?? []), array_keys($required))) $errors[] = 'Unreviewed source-lock entries';
        $provenance = ['docs/8.6/source-evidence.json', 'tools/8.6/source-lock.json', 'docs/8.6/source-audit-upstream.json'];
        if (array_diff($provenance, array_keys($lock['provenance'] ?? []))) $errors[] = 'Missing source provenance record';
        return [...$errors, ...$this->verifyHashes($lock['provenance'] ?? [])];
    }

    public function manifestErrors(?object $manifest = null): array
    {
        $manifest ??= json_decode(file_get_contents($this->path('php-grammar.json')));
        $errors = (new ManifestValidator())->validate($manifest, $this->root);
        if ($errors !== []) return $errors;
        $packages = \PhpGrammar\Repository\RepositoryManifest::fromArray($this->root, json_decode(json_encode($manifest), true));
        $p = $packages->package('8.6');
        if ($p->status !== 'draft' || $p->phpSource['commit'] !== self::PIN
            || $p->grammarPath !== 'grammar/8.6/php.ebnf' || $p->lexerVersion !== '8.6') {
            $errors[] = 'Invalid PHP 8.6 draft metadata/source/package';
        }
        if ($packages->package('8.5')->status !== 'stable'
            || $packages->package('8.5')->phpSource['commit'] !== Support::PIN) $errors[] = 'PHP 8.5 stable baseline changed';
        return $errors;
    }

    public function blockerErrors(array $rows): array
    {
        $errors = [];
        $ids = array_column($rows, 'id');
        if (count($ids) !== count(array_unique($ids)) || array_diff(self::BLOCKERS, $ids)) $errors[] = 'Missing or duplicate blocker disposition';
        foreach ($rows as $row) {
            foreach (['id', 'classification', 'construct', 'evidence', 'missingEvidence', 'risk',
                'draftImpact', 'candidateImpact', 'stableImpact', 'remediation', 'priority'] as $key) {
                if (empty($row[$key])) $errors[] = 'Incomplete blocker disposition: ' . ($row['id'] ?? '?') . '/' . $key;
            }
            if (($row['blocksCertification'] ?? null) !== true) $errors[] = 'Unauthorized blocker reclassification: ' . ($row['id'] ?? '?');
        }
        return $errors;
    }

    /** Descriptions are obligations; a command pass proves only the stated bounded check. */
    public static function gates(): array
    {
        return [
            'manifest-schema' => ['manifest', 'Validate schema, metadata paths, RC4 pin and draft/stable status.'],
            'manifest-regressions' => ['test-manifest', 'Exercise valid and invalid manifest contracts.'],
            'standalone-structure-parity' => ['test-integration', 'Load only 8.6; check EBNF structure/references, parity, version selection and CLI.'],
            'positive-negative-fixtures' => ['test-fixtures', 'Run registered positive and structural-negative fixture providers.'],
            'ordinary-differential' => ['boundaries', 'Rerun every reviewed ordinary 8.6 positive, negative, contextual and discrepancy fixture through structure and RC4 lint.'],
            'lexer-profiles' => ['integration', 'Compare version-specific token boundaries and both short-tag profiles with both distributions.'],
            'pfa-differential' => ['pfa', 'Validate PFA positives, structural/contextual negatives and paired lint with pinned hashes.'],
            'contextual-differential' => ['context', 'Check compiler, declaration-link and controlled-runtime expectations at their declared stage.'],
            'diagnostic-differential' => ['deprecation', 'Separate warning presence/stage from rejection in both distributions.'],
            'version-boundaries' => ['integration', 'Discover structural/lint/link boundaries and verify paired outcomes and finding identity.'],
            'coverage-measurement' => ['coverage', 'Measure actual 8.6 production/alternative witnesses and compare source-backed lexical cases.'],
            'parser-scanner-correspondence' => ['audit', 'Recompute the 176-production/623-alternative and 190-header inventories, source locks and mappings.'],
            'compiler-contextual-reconciliation' => ['test-compiler', 'Verify bounded semantic groups, inventories, source references and preserved uncertainties.'],
            'rfc-implementation-traceability' => ['audit', 'Recompute all RFC candidate dispositions at the selected source pin; no RFC-only permission.'],
            'production-change-traceability' => ['traceability', 'Reject unexplained added/removed/modified productions, lexical/prose drift and stale witnesses.'],
            'changes-document-completeness' => ['traceability', 'Verify all ten sections, finding IDs, source pins and regression references.'],
            'historical-preservation' => ['preservation', 'Compare every pre-7A project file, including all 8.5 evidence/fixtures/locks and both 8.6 specifications.'],
            'public-api-cli' => ['test-consumer', 'Run consumer workflow, package contract and navigation CLI regressions.'],
            'source-lock-integrity' => ['source', 'Verify independent RC4 lock completeness and every required source SHA-256.'],
            'evidence-freshness' => ['freshness', 'Verify historical audit inputs; final --check also compares complete current input/output sets.'],
            'blocker-dispositions' => ['blockers', 'Require all seven complete, unchanged Phase 6 blocking dispositions.'],
            'documentation-json' => ['documentation', 'Validate new JSON, local documentation links, status wording and whitespace.'],
            'full-regression' => ['test-all', 'Run the entire repository PHPUnit suite without cached results.'],
            'infrastructure-negatives' => ['test-certification', 'Exercise isolated missing/stale/invalid inputs, failed gates and false certification claims.'],
            'historical-certification-check' => ['historical', 'Run unmodified 8.5 certification --check; record pre-existing freshness failures, never rewrite history.'],
            'composer-validation' => ['composer', 'Validate Composer metadata strictly.'],
            'production-coverage-closure' => ['blocked:C6-COVERAGE', 'Disposition every uncovered production with independent evidence; unresolved meaningful gaps block.'],
            'alternative-coverage-closure' => ['blocked:C6-COVERAGE', 'Disposition every uncovered alternative, including context-only from; percentages do not waive gaps.'],
            'scanner-state-closure' => ['blocked:C6-COVERAGE', 'Supply independent RC4 scanner state/helper/error products; header equality is insufficient.'],
            'ast-binding' => ['blocked:C6-COVERAGE', 'Supply independent RC4 operand/AST and interpolation binding evidence, including retained ambiguities.'],
            'folding-order' => ['blocked:PHP86-015', 'Resolve optimized-call traversal and independently reconcile live/retained/discarded validation order.'],
            'discovery-closure' => ['blocked:PHP86-013', 'Resolve outstanding syntax-relevant compiler/AST/linking/optimizer discovery records.'],
            'writable-target-validity' => ['blocked:PHP86-014', 'Establish intended writable-target validity and compiler/VM consistency.'],
            'contextual-enforcement-scope' => ['blocked:C6-CONTEXT', 'Adjudicate incomplete contextual enforcement and predicate-level evidence for the bounded claim.'],
            'discarded-hook-disposition' => ['blocked:CA86-001', 'Independently adjudicate the inherited get/set-only discrepancy; do not inherit an 8.5 conclusion.'],
            'exact-pin-executable' => ['provenance', 'Require reviewed source/build/generated-file/configuration/binary provenance; a version string is insufficient.'],
        ];
    }

    public function gateErrors(array $gates): array
    {
        $errors = [];
        if (array_keys($gates) !== array_keys(self::gates())) $errors[] = 'Missing required release gate, unknown gate, or changed order';
        foreach (self::gates() as $id => [$kind, $obligation]) {
            $row = $gates[$id] ?? [];
            if (($row['obligation'] ?? null) !== $obligation) $errors[] = 'Changed gate obligation: ' . $id;
            if (!in_array($row['status'] ?? '', ['passed', 'failed', 'blocked', 'not-executed'], true)) $errors[] = 'Invalid gate status: ' . $id;
            if (($row['status'] ?? '') === 'passed' && ($row['exit_code'] ?? null) !== 0) $errors[] = 'Failed release gate marked passed: ' . $id;
            if (($row['status'] ?? '') === 'failed' && (!is_int($row['exit_code'] ?? null) || $row['exit_code'] === 0)) $errors[] = 'Invalid failed gate exit: ' . $id;
            if ((str_starts_with($kind, 'blocked:') || $kind === 'provenance') && ($row['status'] ?? '') !== 'blocked') $errors[] = 'Unresolved obligation marked complete: ' . $id;
            if (in_array($row['status'] ?? '', ['blocked', 'not-executed'], true) && empty($row['reason'])) $errors[] = 'Missing prerequisite explanation: ' . $id;
        }
        return $errors;
    }

    public function claimErrors(array $report): array
    {
        $errors = $this->gateErrors($report['gates'] ?? []);
        foreach ($report['gates'] ?? [] as $id => $row) {
            if (($row['status'] ?? null) !== 'passed') $errors[] = 'Release gate not passed: ' . $id;
        }
        foreach ($report['blockers'] ?? [] as $row) {
            if ($row['blocksCertification'] ?? true) $errors[] = 'Unresolved blocking finding: ' . $row['id'];
        }
        if (($report['exactPinExecutableVerified'] ?? false) !== true) $errors[] = 'Exact-pin interpreter provenance unavailable';
        $errors[] = 'Phase 7A prepares evidence only; Phase 7B certification is not authorized by this report';
        return $errors;
    }

    public function provenanceErrors(array $record, string $binary): array
    {
        if (($record['contract'] ?? null) === 'php86-exact-source-v1') {
            require_once __DIR__ . '/ExactPinProvenance.php';
            return (new ExactPinProvenance($this->root))->errors($record, $binary);
        }
        $errors = [];
        if (!is_file($binary) || ($record['sha256'] ?? null) !== hash_file('sha256', $binary)) $errors[] = 'Interpreter/source provenance mismatch: binary digest';
        if (($record['sourceCommit'] ?? null) !== self::PIN) $errors[] = 'Interpreter/source provenance mismatch: source commit';
        // Phase 7A deliberately has no auto-attestation path. A later reviewed build
        // verifier must validate build records; a user-authored boolean is not proof.
        $errors[] = 'Exact-pin build attestation has not been independently verified';
        return $errors;
    }

    /** Summary numbers must agree with separately retained command artifacts. */
    public function summaryErrors(array $report): array
    {
        $errors = [];
        $coverage = $this->read(self::OUTPUT . '/coverage.json');
        if (($report['grammar_coverage'] ?? null) !== ($coverage['current'] ?? null)
            || ($report['remaining_coverage'] ?? null) !== ($coverage['remaining'] ?? null)
            || ($report['coverage_classifications'] ?? null) !== array_count_values(array_column($coverage['remaining'] ?? [], 'classification'))) {
            $errors[] = 'Coverage summary differs from measured evidence';
        }
        if (($report['interpreters'] ?? null) !== $this->read(self::OUTPUT . '/interpreters.json')) $errors[] = 'Interpreter metadata differs from retained observation';
        $log = self::OUTPUT . '/logs/full-regression.log';
        if (!is_file($this->path($log))) return [...$errors, 'Missing full regression log'];
        preg_match('/OK \((\d+) tests, (\d+) assertions\)/', file_get_contents($this->path($log)), $totals);
        $actual = ['tests' => isset($totals[1]) ? (int) $totals[1] : null,
            'assertions' => isset($totals[2]) ? (int) $totals[2] : null,
            'exit_code' => $report['gates']['full-regression']['exit_code'] ?? null, 'log' => $log];
        if (($report['validation'] ?? null) !== $actual) $errors[] = 'Test summary differs from executed command';
        return $errors;
    }

    public function check(array $report): array
    {
        $errors = [];
        if (($report['schema'] ?? null) !== 1 || ($report['version'] ?? null) !== '8.6'
            || ($report['phase'] ?? null) !== '7A' || ($report['source_pin'] ?? null) !== self::PIN) $errors[] = 'Invalid final evidence identity';
        foreach (['certificationGranted', 'grammar_complete', 'whole_source_validator_complete', 'exhaustively_equivalent', 'exactPinExecutableVerified'] as $field) {
            if (($report[$field] ?? null) !== false) $errors[] = 'Unsupported certification claim: ' . $field;
        }
        if (($report['categories'] ?? null) !== self::CATEGORIES) $errors[] = 'Missing final evidence category';
        if (($report['inputsUnchangedDuringPreparation'] ?? null) !== true) $errors[] = 'Inputs changed or were not snapshotted before command execution';
        if (($report['inputs'] ?? []) !== $this->inputs()) $errors[] = 'Stale evidence input set/digest; review changes and rerun --prepare';
        $errors = [...$errors, ...$this->verifyHashes($report['inputs'] ?? []), ...$this->verifyHashes($report['artifacts'] ?? []),
            ...$this->gateErrors($report['gates'] ?? []), ...$this->blockerErrors($report['blockers'] ?? []), ...$this->manifestErrors()];
        if (($report['blockers'] ?? []) !== $this->read('docs/8.6/source-audit-dispositions.json')['blockers']) $errors[] = 'Blocker dispositions differ from Phase 6';
        $requiredArtifacts = [];
        foreach ($report['gates'] ?? [] as $id => $row) {
            if (isset($row['command'])) {
                $log = self::OUTPUT . '/logs/' . $id . '.log';
                $requiredArtifacts[] = $log;
                if (($row['log'] ?? null) !== $log || ($row['logSha256'] ?? null) !== ($report['artifacts'][$log] ?? null)) $errors[] = 'Missing/mismatched command log: ' . $id;
                $outcome = self::OUTPUT . '/logs/' . $id . '.json';
                $requiredArtifacts[] = $outcome;
                if (is_file($this->path($outcome)) && $this->read($outcome) !== $row) $errors[] = 'Gate differs from recorded command outcome: ' . $id;
            } elseif (!str_starts_with(self::gates()[$id][0] ?? '', 'blocked:') && $id !== 'exact-pin-executable') {
                $errors[] = 'Missing command outcome: ' . $id;
            }
        }
        foreach (self::generatedReports() as $path) $requiredArtifacts[] = $path;
        if (array_diff($requiredArtifacts, array_keys($report['artifacts'] ?? []))) $errors[] = 'Missing required evidence artifact in final ledger';
        $present = [];
        if (is_dir($this->path(self::OUTPUT))) {
            foreach (Support::files($this->path(self::OUTPUT)) as $file) $present[] = substr($file, strlen(str_replace('\\', '/', $this->root)) + 1);
        }
        if (($report['artifacts'] ?? []) !== $this->hashes($present)) $errors[] = 'Stale evidence artifact set/digest';
        if (is_file($this->path(self::OUTPUT . '/coverage.json')) && is_file($this->path(self::OUTPUT . '/interpreters.json'))) {
            $errors = [...$errors, ...$this->summaryErrors($report)];
        }
        foreach ($report['interpreters'] ?? [] as $version => $record) {
            if (!is_file($record['path']) || hash_file('sha256', $record['path']) !== $record['sha256']) $errors[] = 'Interpreter/source provenance mismatch: ' . $version;
        }
        if (array_keys($report['interpreters'] ?? []) !== ['8.5', '8.6']) $errors[] = 'Missing interpreter metadata';
        if (!isset($report['sourceDirectories']['8.6'])) {
            $errors[] = 'Missing source-cache verification path';
        } else {
            $errors = [...$errors, ...$this->sourceErrors($this->read('tools/8.6/certification-source-lock.json'), $report['sourceDirectories']['8.6'])];
        }
        return array_values(array_unique($errors));
    }

    public static function generatedReports(): array
    {
        return array_map(static fn(string $name): string => self::OUTPUT . '/' . $name . '.json',
            ['integration', 'pfa', 'context', 'deprecation', 'coverage-oracles', 'coverage', 'traceability', 'interpreters', 'boundaries']);
    }

    public function write(string $path, array $data): void
    {
        $file = $this->path($path);
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        file_put_contents($file, Support::json($data));
    }
}
