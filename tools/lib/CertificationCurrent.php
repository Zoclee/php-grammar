<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/Preservation86.php';
require_once __DIR__ . '/BlockerClosure.php';
require_once __DIR__ . '/CompatibilityEvidence.php';
require_once __DIR__ . '/ContextEvidence.php';
require_once __DIR__ . '/ManifestValidator.php';

/** Additive current assessment. Legacy generation and claim meanings are untouched. */
final class CertificationCurrent
{
    public const CONTRACT = 'php86-certification-v2';
    public const OUT = 'docs/8.6/certification-contract-remediation';
    public const CHECKS = ['preservation', 'audit', 'historical-integrity', 'current-compatibility', 'exact-provenance', 'exact-context', 'blockers', 'legacy-ledger', 'release85'];
    public function __construct(public readonly string $root) {}

    public function inventory(): array
    {
        $c = new Certification86($this->root); $paths = ['AGENTS.md', 'README.md', '.gitattributes', '.gitignore', 'composer.json', 'composer.lock', 'php-grammar.json', 'phpunit.xml.dist'];
        foreach (['src', 'tests', 'grammar', 'tools', 'bin', 'schema', 'docs', 'vendor'] as $dir) foreach (Support::files($this->root . '/' . $dir) as $file) {
            $p = substr($file, strlen($this->root) + 1);
            // Revision outputs are bound separately; old compatibility authority is anchored.
            if ($p === self::OUT . '.md' || str_starts_with($p, self::OUT . '/') || str_starts_with($p, 'docs/8.5/compatibility/')) continue;
            if (HistoricalEvidence::safeFile($this->root, $p) === null) throw new \RuntimeException('Unsafe current dependency: ' . $p);
            $paths[] = $p;
        }
        $paths = [...$paths, ...array_keys(Preservation86::ANCHORS)];
        return $c->hashes(array_unique($paths));
    }

    public static function profiles(): array
    {
        return ['historical-rc4' => 'c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a',
            'exact-pin-rc4' => '445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6'];
    }

    public static function profileErrors(array $config): array
    {
        $errors = [];
        if (($config['profile'] ?? '') !== 'exact-pin-rc4' || ($config['legacyProfile'] ?? '') !== 'historical-rc4') $errors[] = 'Explicit exact and legacy profile selection required';
        foreach (['exact' => 'exact-pin-rc4', 'php86' => 'historical-rc4'] as $key => $profile) {
            $path = $config[$key] ?? '';
            if (!is_file($path) || hash_file('sha256', $path) !== self::profiles()[$profile]) $errors[] = 'Incorrect executable identity: ' . $profile;
        }
        $php85 = $config['php85'] ?? '';
        if (!is_file($php85) || hash_file('sha256', $php85) !== '32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9') $errors[] = 'Incorrect tooling interpreter identity';
        return $errors;
    }

    /** Stable mapping preserves every original obligation and the original result. */
    public static function gates(array $legacy, array $checks, array $dispositions, array $verificationCommand = ['CertificationCurrentRun::verify']): array
    {
        $rows = []; $byId = array_column($dispositions, null, 'id');
        foreach (Certification86::gates() as $id => [$kind, $obligation]) {
            $old = $legacy[$id] ?? []; $dependency = null;
            $row = ['id' => $id, 'contract' => self::CONTRACT, 'obligation' => $obligation,
                'legacy' => $old, 'requiredInputs' => ['current-dependencies', 'legacy-snapshot'],
                'sourcePin' => Certification86::PIN, 'sourceRequirements' => ['8.5' => Support::PIN, '8.6' => Certification86::PIN],
                'executableProfile' => 'historical-rc4', 'verificationCommand' => $verificationCommand,
                'command' => $old['command'] ?? null, 'expectedEvidence' => 'Independently verified command outcome and complete input membership',
                'actualEvidence' => $old['log'] ?? null, 'freshness' => 'current-verified',
                'execution' => isset($old['command']) ? 'executed' : 'not-executed',
                'blockingDependencies' => [], 'result' => $old['status'] ?? 'not-executed'];
            if (in_array($id, ['parser-scanner-correspondence', 'rfc-implementation-traceability'], true)) $dependency = 'audit';
            if ($id === 'historical-preservation') $dependency = 'preservation';
            if ($id === 'exact-pin-executable') { $dependency = 'exact-provenance'; $row['executableProfile'] = 'exact-pin-rc4'; }
            if ($dependency !== null) {
                $row['successorIdentity'] = $id . '@v2'; $row['requiredInputs'][] = $dependency;
                $row['actualEvidence'] = 'checks/' . $dependency; $row['command'] = $verificationCommand;
                $row['execution'] = 'verified-retained-evidence'; $row['result'] = ($checks[$dependency] ?? false) ? 'passed' : 'failed';
            }
            if (str_starts_with($kind, 'blocked:')) {
                $blocker = substr($kind, 8); $b = $byId[$blocker] ?? [];
                $row['requiredInputs'][] = 'blockers'; $row['actualEvidence'] = 'dispositions/' . $blocker;
                $row['blockingDependencies'] = [$blocker];
                $row['execution'] = in_array($blocker, ['PHP86-014', 'PHP86-015'], true) ? 'unresolved-language-validity' : 'blocked-by-prerequisite';
                // A future closure format needs an independent verifier. File existence cannot close it.
                $row['result'] = ($checks['blockers'] ?? false) && ($b['blocksCertification'] ?? false) && !empty($b['remainingRequirements']) ? 'blocked' : 'failed';
            }
            if ($id === 'historical-certification-check') {
                $row['successorIdentity'] = 'historical-release-dependency@v2';
                $row['requiredInputs'] = ['historical-integrity', 'current-compatibility', 'all-27-policy-release-obligations', 'lexer-coverage-shared-selector@v2'];
                $row['command'] = $verificationCommand; $row['actualEvidence'] = 'checks/release85';
                $row['historicalFreshness'] = $old['status'] ?? 'not-executed';
                $row['boundedDependencyVerified'] = ($checks['historical-integrity'] ?? false) && ($checks['current-compatibility'] ?? false);
                $row['result'] = $row['boundedDependencyVerified'] && ($checks['release85'] ?? false) ? 'passed' : 'blocked';
                $row['execution'] = $row['result'] === 'passed' ? 'verified-current-dependency' : 'blocked-by-prerequisite';
                $row['blockingDependencies'] = $row['result'] === 'passed' ? [] : ['H85-current-release-obligations'];
                $row['expectedEvidence'] = 'All 27 PHP 8.5 commands retained: 25 successful obligations, independently authenticated shared-selector lexer transition with fresh cases and sole ledger-freshness refusal, and final aggregate refusing solely historical byte freshness; anchored historical integrity, complete current dependencies and bounded compatibility independently verified. This is dependency eligibility, not renewed PHP 8.5 certification.';
            }
            if (!($checks['legacy-ledger'] ?? false)) { $row['freshness'] = 'unverified'; if ($row['result'] === 'passed') $row['result'] = 'failed'; }
            if ($id === 'contextual-differential' && !($checks['exact-context'] ?? false)) { $row['result'] = 'failed'; $row['blockingDependencies'][] = 'exact-context'; }
            $rows[$id] = $row;
        }
        return $rows;
    }

    public static function assessmentErrors(array $report, array $legacy, array $checks, array $dispositions): array
    {
        $errors = [];
        if (($report['contract'] ?? null) !== self::CONTRACT || ($report['evidenceKind'] ?? null) !== 'current-assessment') $errors[] = 'Incorrect current evidence identity';
        foreach (['certificationGranted', 'grammarComplete', 'wholeSourceValidatorComplete', 'historicalCertificationRenewed'] as $key) if (($report[$key] ?? null) !== false) $errors[] = 'Unsupported certification claim: ' . $key;
        if (($report['gates'] ?? null) !== self::gates($legacy, $checks, $dispositions, self::verificationCommand($report))) $errors[] = 'Gate inventory, dependency, status or evidence differs from independently derived assessment';
        if (($report['blockers'] ?? null) !== $dispositions) $errors[] = 'Unsupported blocker removal or disposition change';
        if (($report['checks'] ?? null) !== $checks || array_keys($checks) !== self::CHECKS) $errors[] = 'Incorrect independent verification inventory';
        $prerequisites = ['historical-artifact-integrity85' => $checks['historical-integrity'] ?? false, 'current-compatibility85' => $checks['current-compatibility'] ?? false];
        if (($report['prerequisites'] ?? null) !== $prerequisites) $errors[] = 'Incorrect hybrid prerequisites';
        return $errors;
    }

    public static function claimErrors(array $report): array
    {
        $errors = [];
        foreach ($report['gates'] ?? [] as $id => $row) if (($row['result'] ?? '') !== 'passed') $errors[] = 'Mandatory obligation unmet: ' . $id;
        foreach ($report['blockers'] ?? [] as $row) if ($row['blocksCertification'] ?? true) $errors[] = 'Unresolved blocker: ' . ($row['id'] ?? '?');
        foreach ($report['prerequisites'] ?? [] as $id => $ok) if (!$ok) $errors[] = 'Unverified prerequisite: ' . $id;
        if (count($report['gates'] ?? []) !== 36 || count($report['prerequisites'] ?? []) !== 2) $errors[] = 'Incomplete 38-obligation inventory';
        return $errors;
    }

    public static function verificationCommand(array $report): array
    {
        if (!isset($report['configuration']['php85'], $report['output'])) return ['CertificationCurrentRun::verify'];
        return [$report['configuration']['php85'], '-n', 'tools/8.6/certification-current.php', '--contract', self::CONTRACT, '--check', '--report', $report['output'] . '/evidence.json'];
    }

    public static function readOnlyErrors(array $before, array $after, array $artifactErrors): array
    {
        return $before === $after && $artifactErrors === [] ? [] : ['Read-only validation modified artifacts'];
    }
}
