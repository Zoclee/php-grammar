<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__ . '/CertificationCurrentRun.php';
require_once __DIR__ . '/DifferentialInventory.php';

/** Compose the unchanged v2 assessment with one fully verified inventory successor. */
final class DifferentialCertification
{
    public const CONTRACT = 'php86-certification-v3';
    public const MEMBERSHIP_FAILURE = 'Ordinary corpus differs from reviewed boundary inventory; review every new/missing case.';
    public function __construct(public readonly string $root) {}

    public static function predecessorFailureErrors(array $log): array
    {
        return ($log['cases'] ?? null) === 313 && ($log['errors'] ?? null) === [self::MEMBERSHIP_FAILURE]
            ? [] : ['Unexplained predecessor ordinary-differential failure'];
    }

    public static function gates(array $base, string $report): array
    {
        $gates = $base['gates']; $old = $gates['ordinary-differential']; $row = $old;
        $row['contract'] = self::CONTRACT; $row['successorIdentity'] = 'ordinary-differential@v2';
        $row['predecessorCurrentRow'] = $old;
        $row['requiredInputs'] = [...$old['requiredInputs'], DifferentialInventory::INVENTORY, 'independently-verified-exact-differential'];
        $row['executableProfile'] = 'exact-pin-rc4'; $row['actualEvidence'] = $report;
        $row['command'] = [$base['configuration']['php85'], '-n', 'tools/8.6/differential-inventory.php', '--generate', '--profile', 'exact-pin-rc4', '--report', $report];
        $row['verificationCommand'] = [$base['configuration']['php85'], '-n', 'tools/8.6/differential-inventory.php', '--check', '--profile', 'exact-pin-rc4', '--report', $report];
        $row['expectedEvidence'] = 'All 313 predecessor expectations preserved; exactly 26 anchored additions; complete live corpus, source correspondence, exact-source structural/lexical/lint/diagnostic observations, stable inputs and independent verification.';
        $row['execution'] = 'executed-exact-source-and-independently-verified'; $row['result'] = 'passed';
        $gates['ordinary-differential'] = $row; return $gates;
    }

    public function dependencies(string $basePath, string $differentialPath): array
    {
        $c = new Certification86($this->root); $base = $c->read($basePath);
        $errors = (new CertificationCurrentRun($this->root))->verify($basePath);
        $errors = [...$errors, ...(new DifferentialInventory($this->root))->verify($differentialPath)];
        $old = $base['gates']['ordinary-differential']['legacy'] ?? [];
        if (($old['status'] ?? '') !== 'failed' || ($old['exit_code'] ?? null) !== 1) $errors[] = 'Incorrect predecessor gate identity';
        $logPath = ($base['output'] ?? '') . '/legacy/' . ($old['log'] ?? '');
        if (!is_file($c->path($logPath))) $errors[] = 'Missing predecessor ordinary observation';
        else $errors = [...$errors, ...self::predecessorFailureErrors(json_decode(file_get_contents($c->path($logPath)), true) ?? [])];
        return $errors;
    }

    public function body(string $revision, string $basePath, string $differentialPath): array
    {
        $c = new Certification86($this->root); $base = $c->read($basePath);
        return ['contract' => self::CONTRACT, 'revision' => $revision, 'evidenceKind' => 'current-assessment-with-versioned-differential',
            'baseAssessment' => ['path' => $basePath, 'sha256' => hash_file('sha256', $c->path($basePath)), 'contract' => CertificationCurrent::CONTRACT],
            'differential' => ['path' => $differentialPath, 'sha256' => hash_file('sha256', $c->path($differentialPath)), 'contract' => DifferentialInventory::CONTRACT],
            'inputs' => $base['inputs'], 'gates' => self::gates($base, $differentialPath), 'blockers' => $base['blockers'], 'prerequisites' => $base['prerequisites'],
            'checks' => $base['checks'] + ['ordinary-differential-successor' => true],
            'scope' => 'Only ordinary-differential receives an explicit inventory/profile successor; every other gate, original outcome and blocker is inherited without relaxation.',
            'certificationGranted' => false, 'grammarComplete' => false, 'wholeSourceValidatorComplete' => false, 'historicalCertificationRenewed' => false];
    }

    public function generate(string $revision, string $basePath, string $differentialPath): string
    {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/D', $revision)) throw new \RuntimeException('Safe new revision required');
        $c = new Certification86($this->root); $path = CertificationCurrent::OUT . '/runs/' . $revision . '/evidence.json';
        if (file_exists(dirname($c->path($path)))) throw new \RuntimeException('Never overwrite an evidence revision');
        $errors = $this->dependencies($basePath, $differentialPath);
        if ($errors) throw new \RuntimeException(implode('; ', $errors));
        $c->write($path, $this->body($revision, $basePath, $differentialPath));
        $c->write($path . '.receipt.json', ['sha256' => hash_file('sha256', $c->path($path)), 'scope' => 'Reviewed local custody, not a signature or certification.']);
        return $path;
    }

    public function verify(string $path): array
    {
        try {
            $c = new Certification86($this->root); $r = $c->read($path); $revision = $r['revision'] ?? '';
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/D', $revision) || $path !== CertificationCurrent::OUT . '/runs/' . $revision . '/evidence.json') return ['Invalid successor revision path'];
            $hash = hash_file('sha256', $c->path($path)); $receipt = $c->read($path . '.receipt.json');
            if (($receipt['sha256'] ?? '') !== $hash) return ['Successor receipt mismatch'];
            $base = $r['baseAssessment']['path']; $diff = $r['differential']['path'];
            if ($r !== $this->body($revision, $base, $diff)) return ['Tampered gate, predecessor, dependency or blocker assessment'];
            $errors = $this->dependencies($base, $diff);
            if ($hash !== hash_file('sha256', $c->path($path)) || $receipt !== $c->read($path . '.receipt.json')) $errors[] = 'Read-only successor check changed evidence';
            return $errors;
        } catch (\Throwable $e) { return ['Missing/invalid successor assessment: ' . $e->getMessage()]; }
    }
}
