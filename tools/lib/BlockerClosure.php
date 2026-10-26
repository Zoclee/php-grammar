<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

require_once __DIR__ . '/Certification86.php';

/** Validates the additive Phase 7C.6 review; does not decide language intent. */
final class BlockerClosure
{
    public const ROOT = 'docs/8.6/certification-blocker-closure';
    public const CONTRACT = 'php86-blocker-triage-v1';

    public static function inventoryErrors(array $review, array $original, array $raw): array
    {
        $errors = []; $known = array_column($original, null, 'id'); $raw = array_column($raw, null, 'id');
        $seen = []; $groups = []; $effective = [];
        foreach ($review['groups'] ?? [] as $group) {
            if (isset($groups[$group['id'] ?? ''])) $errors[] = 'Duplicate semantic group';
            $groups[$group['id'] ?? ''] = true;
            if (!in_array($group['disposition'] ?? '', ['excluded-no-language-change', 'mapped-existing-finding', 'reviewed-unresolved'], true)) $errors[] = 'Unknown review disposition';
            foreach (['rationale', 'remainingScope', 'sourceEvidence', 'grammarOrContext', 'verification'] as $field) if (empty($group[$field])) $errors[] = 'Missing review justification: ' . $field;
            if (empty($group['hunks'])) $errors[] = 'Empty review group';
            foreach ($group['hunks'] ?? [] as $h) {
                $id = $h['id'] ?? '';
                if (!isset($known[$id], $raw[$id]) || isset($seen[$id])) { $errors[] = 'Unknown/duplicate hunk: ' . $id; continue; }
                $seen[$id] = true;
                if (!in_array($known[$id]['disposition'], ['pending-direct-review', 'examined-unresolved'], true)) $errors[] = 'Already accounted hunk: ' . $id;
                if (($h['originalDisposition'] ?? null) !== $known[$id]['disposition'] || ($h['diffSha256'] ?? null) !== $raw[$id]['diffSha256'] || empty($h['diffFile'])) $errors[] = 'Lost original hunk identity: ' . $id;
                $effective[$id] = $group['disposition'] === 'reviewed-unresolved' ? 'examined-unresolved' : 'phase7c6-' . $group['disposition'];
            }
        }
        $rows = $review['inventory'] ?? [];
        if (array_column($rows, 'id') !== array_column($original, 'id')) $errors[] = 'Original discovery membership/order changed';
        $counts = [];
        foreach ($rows as $row) {
            $id = $row['id'] ?? ''; $status = $effective[$id] ?? ($known[$id]['disposition'] ?? null);
            if (($row['original'] ?? null) !== ($known[$id] ?? null) || ($row['effectiveDisposition'] ?? null) !== $status) $errors[] = 'Unreviewed disposition change: ' . $id;
            if ($status !== null) $counts[$status] = ($counts[$status] ?? 0) + 1;
        }
        ksort($counts); $reported = $review['counts'] ?? []; ksort($reported);
        if ($counts !== $reported) $errors[] = 'Discovery counts differ from identities';
        if (($review['discoveryComplete'] ?? null) !== false || ($review['certificationGranted'] ?? null) !== false) $errors[] = 'Unsupported completeness claim';
        return $errors;
    }

    public static function dispositionErrors(array $current, array $original): array
    {
        $errors = [];
        if (array_column($current, 'id') !== array_column($original, 'id')) $errors[] = 'Missing original blocker';
        foreach ($original as $index => $old) {
            $row = $current[$index] ?? [];
            if (($row['original'] ?? null) !== $old) $errors[] = 'Original blocker redefined: ' . $old['id'];
            // No original blocker is fully closed by this bounded review.
            if (($row['blocksCertification'] ?? null) !== true || ($row['status'] ?? null) !== 'blocking') $errors[] = 'Unsupported whole-blocker closure: ' . $old['id'];
            foreach (['categories', 'affectedGates', 'existingEvidence', 'remainingRequirements', 'localVerification', 'upstreamClarification', 'finalReleaseDependency', 'releaseImpact', 'obligations'] as $field) if (empty($row[$field])) $errors[] = 'Missing blocker assessment: ' . $old['id'] . '/' . $field;
            foreach ($row['categories'] ?? [] as $category) if (!in_array($category, ['A', 'B', 'C', 'D', 'E'], true)) $errors[] = 'Unknown resolution mechanism';
            foreach ($row['obligations'] ?? [] as $obligation) {
                if (!in_array($obligation['status'] ?? '', ['verified', 'open'], true) || empty($obligation['requirement'])) $errors[] = 'Invalid scoped obligation';
                if (($obligation['status'] ?? '') === 'verified' && empty($obligation['evidence'])) $errors[] = 'Verified obligation lacks evidence';
            }
        }
        return $errors;
    }

    public function __construct(private readonly string $root) {}

    public function errors(): array
    {
        $c = new Certification86($this->root);
        try {
            $review = $c->read(self::ROOT . '/source-review.json');
            $current = $c->read(self::ROOT . '/dispositions.json');
            $original = $c->read('docs/8.6/source-completeness-ledger.json')['discovery']['hunks'];
            $raw = $c->read('docs/8.6/compiler-change-reconciliation.json')['hunks'];
            $errors = [...self::inventoryErrors($review, $original, $raw),
                ...self::dispositionErrors($current['blockers'] ?? [], $c->read('docs/8.6/source-audit-dispositions.json')['blockers']),
                ...$c->verifyHashes($review['inputs'] ?? []), ...$c->verifyHashes($current['inputs'] ?? [])];
            foreach (['docs/8.6/source-completeness-ledger.json', 'docs/8.6/compiler-change-reconciliation.json',
                'docs/8.6/source-evidence.json', 'tools/lib/BlockerClosure.php', 'tools/8.6/blocker-closure-check.php',
                'tests/BlockerClosureTest.php', self::ROOT . '/upstream-refresh.json'] as $path) {
                if (!isset($review['inputs'][$path])) $errors[] = 'Missing mandatory review dependency: ' . $path;
            }
            if (!isset($current['inputs']['docs/8.6/source-audit-dispositions.json'])) $errors[] = 'Missing original blocker dependency';
            if (($review['contract'] ?? null) !== self::CONTRACT || ($current['contract'] ?? null) !== self::CONTRACT
                || ($review['sourcePins']['8.6'] ?? null) !== Certification86::PIN || ($review['sourcePins']['8.5'] ?? null) !== Support::PIN) $errors[] = 'Review contract/source mismatch';
            $rawIndex = array_column($raw, null, 'id');
            $sourceEvidence = $c->read('docs/8.6/source-evidence.json')['files'];
            foreach ($review['groups'] ?? [] as $group) foreach ($group['hunks'] as $h) {
                $bytes = file_get_contents($c->path($h['diffFile']));
                if (hash('sha256', $bytes) !== ($rawIndex[$h['id']]['diffSha256'] ?? null)) $errors[] = 'Hunk proof differs from original: ' . $h['id'];
                $sides = ['8.5' => [], '8.6' => []];
                foreach (array_slice(explode("\n", rtrim($bytes, "\n")), 1) as $line) {
                    if ($line === '' || $line[0] === '\\') continue;
                    if ($line[0] !== '+') $sides['8.5'][] = substr($line, 1);
                    if ($line[0] !== '-') $sides['8.6'][] = substr($line, 1);
                }
                $r = $rawIndex[$h['id']];
                foreach (['8.5' => 'old', '8.6' => 'new'] as $version => $side) {
                    if (($review['sourceFiles'][$version][$r['path']] ?? null) !== ($sourceEvidence[$r['path']][$side] ?? null)
                        || empty($review['sourceFiles'][$version][$r['path']])) $errors[] = 'Unanchored hunk source identity: ' . $h['id'];
                    $source = $review['sourceDirectories'][$version] . '/' . $r['path'];
                    $lines = explode("\n", str_replace("\r\n", "\n", file_get_contents($source)));
                    if (array_slice($lines, $r[$side . 'Line'] - 1, $r[$side . 'Count']) !== $sides[$version]) $errors[] = 'Hunk/source range mismatch: ' . $h['id'] . '/' . $version;
                }
            }
            foreach ($review['sourceFiles'] ?? [] as $version => $paths) foreach ($paths as $path => $hash) {
                $file = $review['sourceDirectories'][$version] . '/' . $path;
                if (!is_file($file) || hash_file('sha256', $file) !== $hash) $errors[] = 'Source bytes changed: ' . $version . '/' . $path;
            }
            return array_values(array_unique($errors));
        } catch (\Throwable $e) { return ['Missing/invalid closure evidence: ' . $e->getMessage()]; }
    }
}
