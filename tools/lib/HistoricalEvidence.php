<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

/** Raw-byte integrity relative to the reviewed immutable ledger; never a test rerun. */
final class HistoricalEvidence
{
    public const COMMIT = '7d8b6e5fa92f82e7e8c122a9a989a22727f61c3e';
    public const FIRST_COMMIT = 'db06a494d6e958e065371ee27cc301227314fd59';
    public const LEDGER = 'docs/8.5/final-evidence.json';
    public const BLOB_SHA256 = '04bb3abfa0635036759ceb4698525f2ed015d77f6937be460a7e8e134bea2257';
    public const CHECKOUT_SHA256 = 'cce919d8b848a9892c33d62b09d5b4d87d6f6a50bf551746cefce634fd47ac9c';
    public const SUPPLEMENT = [
        'src/Ebnf/Validation/GrammarValidator.php',
        'tests/fixtures/php/8.5/parser-structure.json',
        'tests/fixtures/php/8.5/valid/boundary-alt-closed-declaration.php',
        'tests/fixtures/php/8.5/valid/phase3-destructure-keyed-list.php',
        'tests/fixtures/php/8.5/valid/remaining-dangling-loop-bodies.php',
        'tests/fixtures/php/8.5/valid/remaining-scanner-nested-nowdoc.php',
        'tools/8.5/data/certification-policy.json', 'tools/8.5/data/phase6-policy.json',
    ];

    public static function safeFile(string $root, string $relative): ?string
    {
        if (!preg_match('~^[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*$~D', $relative)
            || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $relative)) return null;
        $base = realpath($root); $path = realpath($root . '/' . $relative);
        if ($base === false || $path === false || !is_file($path)) return null;
        $prefix = strtolower(str_replace('\\', '/', $base)) . '/';
        if (!str_starts_with(strtolower(str_replace('\\', '/', $path)), $prefix)) return null;
        $cursor = $base;
        foreach (explode('/', $relative) as $part) { $cursor .= '/' . $part; if (is_link($cursor)) return null; }
        return $path;
    }

    /** Expected hashes must come from the independently anchored ledger, never a supplied report. */
    public static function inspectInputs(array $expected, string $snapshot, string $supplement): array
    {
        $rows = []; $errors = [];
        foreach ($expected as $path => $hash) {
            $file = self::safeFile($snapshot, $path);
            $actual = $file === null ? null : hash_file('sha256', $file);
            $row = ['expectedSha256' => $hash, 'snapshotSha256' => $actual, 'source' => 'snapshot', 'verified' => $actual === $hash];
            if (in_array($path, self::SUPPLEMENT, true)) {
                $candidate = self::safeFile($supplement, $path);
                $candidateHash = $candidate === null ? null : hash_file('sha256', $candidate);
                $row['preservedSha256'] = $candidateHash;
                $row['provenance'] = 'Preserved checkout bytes, content-authenticated against the immutable Git ledger SHA-256; no normalization or execution-history attestation.';
                if (!$row['verified'] && $candidateHash === $hash) {
                    $row['source'] = 'preserved-supplement'; $row['verified'] = true;
                }
            }
            if (!$row['verified']) $errors[] = 'Unavailable or unauthenticated historical bytes: ' . $path;
            $rows[$path] = $row;
        }
        return ['inputs' => $rows, 'errors' => $errors];
    }

    public static function verify(string $root, string $snapshot, string $supplement): array
    {
        $errors = [];
        $r = Support::run(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $root),
            'show', self::COMMIT . ':' . self::LEDGER], $root);
        if ($r['exit_code'] !== 0 || hash('sha256', $r['output']) !== self::BLOB_SHA256) {
            $errors[] = 'Historical trust anchor unavailable or invalid';
            $ledger = [];
        } else $ledger = json_decode($r['output'], true, 512, JSON_THROW_ON_ERROR);
        $file = self::safeFile($root, self::LEDGER);
        if ($file === null || hash_file('sha256', $file) !== self::CHECKOUT_SHA256) $errors[] = 'Historical ledger tampered or missing';
        if (($ledger['schema'] ?? null) !== 1 || ($ledger['source_pin'] ?? null) !== Support::PIN
            || count($ledger['inputs'] ?? []) !== 1545) $errors[] = 'Historical schema/pin/input identity mismatch';
        $inspection = self::inspectInputs($ledger['inputs'] ?? [], $snapshot, $supplement);
        $errors = [...$errors, ...$inspection['errors']];
        return ['evidenceKind' => 'historical-artifact-integrity', 'contract' => 'historical-integrity-v1',
            'firstCertificationCommit' => self::FIRST_COMMIT, 'baselineCommit' => self::COMMIT,
            'ledgerBlobSha256' => self::BLOB_SHA256, 'ledgerCheckoutSha256' => self::CHECKOUT_SHA256,
            'sourcePin' => $ledger['source_pin'] ?? null, 'historicalClaim' => $ledger['claim'] ?? null,
            'historicalBlockers' => $ledger['blockers'] ?? [],
            'sourceLockInputSha256' => $ledger['inputs']['tools/8.5/source-lock.json'] ?? null,
            'snapshot' => str_replace('\\', '/', $snapshot), 'supplement' => str_replace('\\', '/', $supplement),
            'inputs' => $inspection['inputs'], 'complete' => $errors === [], 'errors' => $errors,
            'historicalTestsRerun' => false, 'currentCertification' => false,
            'limitation' => 'Authenticates content against the reviewed ledger, not original execution, archive custody, or exact-source executable provenance.'];
    }
}
