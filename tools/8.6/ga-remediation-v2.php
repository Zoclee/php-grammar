<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
chdir(S::ROOT);
$out = 'docs/8.6/ga-certification-remediation';
$mode = $argv[1] ?? '';
function writeNew(string $p, string $bytes): void {
    if (is_file($p) && file_get_contents($p) === $bytes) return;
    if (file_exists($p)) throw new RuntimeException('Refusing evidence overwrite: ' . $p);
    if (!is_dir(dirname($p))) mkdir(dirname($p), 0777, true);
    file_put_contents($p, $bytes);
}
if ($mode === '--snapshot') {
    $r = S::read($out . '/runs/phase8d-ga-r1/evidence.json'); $files = [];
    foreach (['php-grammar.json', 'grammar/8.6/changes.md', 'tests/UpstreamResolutionFreshnessTest.php',
        'tests/Php/Conformance/Php86CompilerContextualTest.php', 'tests/GaCertificationTest.php',
        'tools/lib/GaCertification.php', 'tools/8.6/certification-ga.php'] as $p) {
        if (hash_file('sha256', $p) !== $r['inputs'][$p]) throw new RuntimeException('R1 predecessor changed before preservation: ' . $p);
        writeNew($out . '/revisions/r1/' . $p, file_get_contents($p)); $files[$p] = $r['inputs'][$p];
    }
    writeNew($out . '/revisions/r1/snapshot.json', S::json(['contract' => 'php86-ga-r1-predecessor-v1', 'run' => $out . '/runs/phase8d-ga-r1/evidence.json',
        'runSha256' => hash_file('sha256', $out . '/runs/phase8d-ga-r1/evidence.json'), 'files' => $files,
        'note' => 'Exact consumed inputs preserved before successor edits. Other R1 inputs remain unchanged. R1 was independently verified before this transition; it is not fresh current evidence.']));
    echo "R1 predecessor bytes preserved.\n"; exit;
}
if ($mode === '--snapshot-summary') {
    $r = S::read($out . '/runs/phase8d-ga-r1/current-compatibility85.json');
    $path = $out . '.md'; $hash = hash_file('sha256', $path);
    if (($r['dependencies'][$path]['sha256'] ?? null) !== $hash) throw new RuntimeException('R1 summary dependency changed');
    writeNew($out . '/revisions/r1/report.md', file_get_contents($path));
    writeNew($out . '/revisions/r1/summary-snapshot.json', S::json(['original' => $path, 'sha256' => $hash,
        'authority' => $out . '/runs/phase8d-ga-r1/current-compatibility85.json',
        'retained' => $out . '/revisions/r1/report.md']));
    exit;
}
if ($mode === '--profile') {
    $base = S::read('tools/8.6/source-lock-ga-v1.json');
    $base['contract'] = 'php86-ga-source-profile-v2';
    $base['predecessor'] = ['path' => 'tools/8.6/source-lock-ga-v1.json', 'sha256' => hash_file('sha256', 'tools/8.6/source-lock-ga-v1.json')];
    $base['historicalRecords'] = $base['records'];
    $base['records'] = S::hashes(['tools/8.6/pfa-source-lock-ga-v1.json', 'tools/8.6/context-source-lock-ga-v1.json', 'tools/8.6/deprecation-cases-ga-v1.json']);
    writeNew('tools/8.6/source-lock-ga-v2.json', S::json($base));
    $extra = S::read('tools/8.6/final-coverage-source-lock-ga-v1.json');
    $extra['baseLockSha256'] = hash_file('sha256', 'tools/8.6/source-lock-ga-v2.json');
    writeNew('tools/8.6/final-coverage-source-lock-ga-v2.json', S::json($extra));
    $replacements = ['source-lock.json' => 'source-lock-ga-v2.json', 'pfa-source-lock.json' => 'pfa-source-lock-ga-v1.json',
        'context-source-lock.json' => 'context-source-lock-ga-v1.json', 'final-coverage-source-lock.json' => 'final-coverage-source-lock-ga-v2.json',
        'tests/fixtures/php/8.6/deprecation-cases.json' => 'tools/8.6/deprecation-cases-ga-v1.json'];
    $runners = [];
    foreach (['integration-check', 'pfa-check', 'context-check', 'deprecation-check', 'final-coverage-expansion'] as $name) {
        $old = 'tools/8.6/' . $name . '.php'; $new = 'tools/8.6/' . $name . '-ga-v2.php';
        $bytes = strtr(file_get_contents($old), $replacements); writeNew($new, $bytes);
        $runners[$new] = ['predecessor' => $old, 'before' => hash_file('sha256', $old), 'after' => hash('sha256', $bytes)];
    }
    writeNew('tools/8.6/ga-runner-transition-v2.json', S::json(['contract' => 'php86-ga-runner-transition-v2', 'replacements' => $replacements, 'runners' => $runners,
        'predecessor' => ['path' => 'tools/8.6/ga-runner-transition-v1.json', 'sha256' => hash_file('sha256', 'tools/8.6/ga-runner-transition-v1.json')],
        'meaning' => 'Original check algorithms and fixture expectations; source/profile paths explicitly select GA successors. Legacy runners and R1 remain intact.']));
    $changes = file_get_contents($out . '/revisions/r1/grammar/8.6/changes.md');
    $changes = str_replace('f23b4c222cebe33be93ee8ecaaa14390c5507a08', '8aaada3f9605d81a38b8240f93c7389dfbc77ef9', $changes);
    $changes = strtr($changes, ['PHP 8.5 versus RC4' => 'PHP 8.5 versus GA',
        'All new implementation references use the immutable RC4 tree:' => 'Current implementation references use the immutable GA tree:',
        'Immutable implementation sources at the RC4 pin:' => 'Immutable implementation sources at the GA pin:',
        '../../tools/8.6/source-lock-ga-v1.json' => '../../tools/8.6/source-lock-ga-v2.json',
        '../../tools/8.6/pfa-source-lock.json' => '../../tools/8.6/pfa-source-lock-ga-v1.json',
        '../../tools/8.6/context-source-lock.json' => '../../tools/8.6/context-source-lock-ga-v1.json',
        '../../tests/fixtures/php/8.6/deprecation-cases.json' => '../../tools/8.6/deprecation-cases-ga-v1.json']);
    $needle = 'recorded in the GA remediation report; earlier pass counts are not GA sign-off.';
    $changes = str_replace($needle, $needle . "\n\n" .
        'Current GA certification uses `php tools/8.6/certification-ga.php --run' . "\n" .
        '--revision=<new-revision>`, followed by separate `--check --report=<evidence>`' . "\n" .
        'and `--claims --report=<evidence>` invocations. The fixed local profile and' . "\n" .
        'prerequisites are recorded in the source lock. The phase-specific RC4 commands' . "\n" .
        'and results below remain historical instructions, not GA certification.', $changes);
    file_put_contents('grammar/8.6/changes.md', $changes);
    $map = S::read('tools/8.6/change-map-ga-v1.json');
    $map['predecessor'] = ['path' => 'tools/8.6/change-map-ga-v1.json', 'sha256' => hash_file('sha256', 'tools/8.6/change-map-ga-v1.json')];
    $map['changesSha256'] = hash('sha256', $changes);
    writeNew('tools/8.6/change-map-ga-v2.json', S::json($map));
    $old = S::read($out . '/predecessor/php-grammar.json');
    $current = S::read('php-grammar.json');
    $manifest = strtr(file_get_contents($out . '/predecessor/php-grammar.json'), [
        $old['versions'][1]['sourceProfile'] => $current['versions'][1]['sourceProfile'],
        'f23b4c222cebe33be93ee8ecaaa14390c5507a08' => '8aaada3f9605d81a38b8240f93c7389dfbc77ef9',
        'tools/8.6/source-lock.json' => 'tools/8.6/source-lock-ga-v2.json',
        'tools/8.6/change-map.json' => 'tools/8.6/change-map-ga-v2.json',
        'docs/8.6/source-evidence.json' => $out . '/adoption-v2.json']);
    file_put_contents('php-grammar.json', $manifest);
    $adoption = S::read($out . '/adoption-v1.json');
    $adoption['contract'] = 'php86-ga-adoption-v2';
    $adoption['sourceLock'] = 'tools/8.6/source-lock-ga-v2.json';
    $adoption['changeTraceability'] = 'tools/8.6/change-map-ga-v2.json';
    $adoption['certificationContract'] = 'php86-ga-certification-v2';
    $adoption['localEvidence'] = $out . '/local-obligations-v1.json';
    $adoption['historicalRecords'] = 'The metadata pfaValidation, contextValidation, deprecationValidation and integration records retain their original RC4 scope. Current GA observations are emitted by the explicit GA certification runner.';
    writeNew($out . '/adoption-v2.json', S::json($adoption));
    $blockers = S::read($out . '/blockers-v1.json');
    $blockers['contract'] = 'php86-ga-blocker-adjudication-v2';
    $blockers['predecessor'] = ['path' => $out . '/blockers-v1.json', 'sha256' => hash_file('sha256', $out . '/blockers-v1.json')];
    $local = S::read($out . '/local-obligations-v1.json');
    if ($local['errors'] !== []) throw new RuntimeException('Unverified local source proof');
    $blockers['sourceQueue']['effectiveCounts'] = $local['currentDiscoveryCounts'];
    $blockers['sourceQueue']['gaProof'] = ['path' => $out . '/local-obligations-v1.json', 'sha256' => hash_file('sha256', $out . '/local-obligations-v1.json')];
    $blockers['blockers'][0]['gaEvidenceConclusion'] .= ' One exact redundant-include hunk is independently discharged; no other hunk classification changes.';
    $blockers['blockers'][0]['remainingRequirement'] = str_replace('198 pending', '197 pending', $blockers['blockers'][0]['remainingRequirement']);
    writeNew($out . '/blockers-v2.json', S::json($blockers));
    echo "GA profile v2 and reconciled change document prepared.\n"; exit;
}
if ($mode === '--seal') {
    require_once __DIR__ . '/../lib/GaCertification.php';
    $baseline = S::read($out . '/baseline.json')['files']; $changes = [];
    foreach (PhpGrammar\Tools\GaCertification::CHANGED as $p) $changes[$p] = ['before' => $baseline[$p], 'after' => hash_file('sha256', $p)];
    $additions = [];
    foreach (S::files('tools/8.6') as $p) if (str_contains($p, '-ga-v1.') || str_contains($p, '-ga-v2.') || str_contains($p, 'ga-runner-transition')) $additions[$p] = hash_file('sha256', $p);
    foreach (['adoption-v1.json', 'adoption-v2.json', 'blockers-v1.json', 'blockers-v2.json', 'local-obligations-v1.json', 'upstream-status.json', 'transition-v1.json'] as $p) $additions[$out . '/' . $p] = hash_file('sha256', $out . '/' . $p);
    writeNew($out . '/transition-v2.json', S::json(['contract' => 'php86-ga-preservation-v2', 'baseline' => PhpGrammar\Tools\GaCertification::BASELINE,
        'predecessor' => ['path' => $out . '/transition-v1.json', 'sha256' => '6174edb66e11d6d87d12d612ba2446897d5d36e5f8f204c2a7b26eb75250597e'],
        'changes' => $changes, 'additions' => $additions,
        'authorization' => 'Phase 8D current evidence successor. Restore original manifest formatting; finish GA documentary reconciliation; historical test snapshots authenticate exact original dependencies. No policy, legacy checker, grammar, lexer or fixture expectation changes.']));
    echo hash_file('sha256', $out . '/transition-v2.json') . "\n"; exit;
}
throw new RuntimeException('Select --snapshot, --profile or --seal');
