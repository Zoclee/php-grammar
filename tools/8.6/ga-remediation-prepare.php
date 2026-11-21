<?php
declare(strict_types=1);
// One-time, non-overwriting preparation of the explicitly versioned GA transition.
require __DIR__ . '/../lib/bootstrap.php';
use PhpGrammar\Tools\Support as S;
chdir(S::ROOT);
$out = 'docs/8.6/ga-certification-remediation';
$mode = $argv[1] ?? '';
function putNew(string $path, string $bytes): void {
    if (is_file($path) && file_get_contents($path) === $bytes) return;
    if (file_exists($path)) throw new RuntimeException('Refusing overwrite: ' . $path);
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    file_put_contents($path, $bytes);
}
function gitBytes(array $args, string $cwd): array {
    $stdout = tempnam(sys_get_temp_dir(), 'ga-git-out-');
    $stderr = tempnam(sys_get_temp_dir(), 'ga-git-err-');
    try {
        $p = proc_open(['git', '-c', 'gc.auto=0', '-c', 'maintenance.auto=false', ...$args],
            [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'wb'], 2 => ['file', $stderr, 'wb']], $pipes, $cwd);
        if (!is_resource($p)) throw new RuntimeException('Cannot read Git object');
        fclose($pipes[0]); $exit = proc_close($p);
        return ['exit_code' => $exit, 'output' => file_get_contents($stdout), 'stderr' => file_get_contents($stderr)];
    } finally { unlink($stdout); unlink($stderr); }
}
if ($mode === '--baseline') {
    $paths = ['AGENTS.md', '.gitattributes', '.gitignore', 'composer.json', 'composer.lock', 'phpunit.xml.dist', 'php-grammar.json', 'README.md'];
    foreach (['src', 'tests', 'grammar', 'tools', 'docs', 'bin', 'schema', 'vendor'] as $tree) {
        foreach (S::files($tree) as $path) {
            if (!str_starts_with($path, $out . '/') && $path !== __FILE__) $paths[] = $path;
        }
    }
    $hashes = S::hashes($paths);
    foreach (['php-grammar.json', 'grammar/8.6/changes.md', 'tests/Certification86Test.php',
        'tests/Php/Conformance/Php86TraceabilityTest.php', 'tests/Repository/ConsumerCliTest.php'] as $path) {
        putNew($out . '/predecessor/' . $path, file_get_contents($path));
    }
    putNew($out . '/baseline.json', S::json(['contract' => 'php86-ga-adoption-baseline-v1',
        'repositoryCommit' => 'afc4cfdda723ddb0abd453ab0eb69e187a75a599', 'files' => $hashes]));
    echo hash_file('sha256', $out . '/baseline.json') . "\n";
    exit;
}
if ($mode === '--adopt') {
    $ga = '8aaada3f9605d81a38b8240f93c7389dfbc77ef9';
    $rc4 = 'f23b4c222cebe33be93ee8ecaaa14390c5507a08';
    $seal = 'docs/8.6/ga-finalization/artifacts.json';
    if (hash_file('sha256', $seal) !== '05b43c14e487160b5451d34bbd330276feaffacedebbe3249d415e576563bfb7') throw new RuntimeException('GA evidence seal mismatch');
    foreach (S::read($seal)['files'] as $p => $h) if (hash_file('sha256', $p) !== $h) throw new RuntimeException('Retained GA evidence changed: ' . $p);
    $lock = S::read('tools/8.6/source-lock.json');
    $lock['contract'] = 'php86-ga-source-profile-v1';
    $lock['predecessor'] = ['path' => 'tools/8.6/source-lock.json', 'sha256' => hash_file('sha256', 'tools/8.6/source-lock.json')];
    $lock['phpSource'] = ['branch' => 'PHP-8.6', 'tag' => 'php-8.6.0', 'tagObject' => '48a5ee8a311db69103a6fa25b330e5ff3847af3d', 'commit' => $ga];
    $lock['exactPinExecutableVerified'] = true;
    $lock['scope'] = 'Authenticated GA source and locally built exact-Git executable; draft package; no certification grant.';
    $lock['executables']['8.6'] = '880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b';
    $lock['runtime'] = ['path' => '.audit/phase8b/source/x64/Release/php.exe', 'dll' => '.audit/phase8b/source/x64/Release/php8.dll',
        'dllSha256' => '9b2a8bad3ff00c1aa57c83676cff79a830df63ed5af1fc3eb6d55de8b8ab57f9',
        'receipt' => 'docs/8.6/ga-finalization/build-receipt.json', 'verification' => 'tools/8.6/ga-finalization.php --verify-build',
        'trust' => 'Trusted local controller and host custody; not hermetic or bit-reproducible attestation.'];
    $auth = S::read('docs/8.6/ga-finalization/build-source.json');
    foreach ($lock['files']['8.6'] as $p => &$h) {
        $h = hash_file('sha256', '.audit/phase8b/source/' . $p);
        if (($auth['sourceHashes'][$p] ?? null) !== $h) throw new RuntimeException('Source outside authenticated build: ' . $p);
    }
    unset($h);
    $lock['retainedEvidenceSeal'] = ['path' => $seal, 'sha256' => hash_file('sha256', $seal)];
    putNew('tools/8.6/source-lock-ga-v1.json', S::json($lock));
    $changes = file_get_contents($out . '/predecessor/grammar/8.6/changes.md');
    $changes = str_replace('`php-8.6.0RC4`, at `' . $rc4 . '`.', '`php-8.6.0`, at `' . $ga . '`.', $changes);
    $changes = str_replace('The audited profile here is byte-oriented ordinary PHP source and lint of the' . "\n" . 'focused PFA corpus, with `-n` and no opcache/JIT. Executables are PHP 8.5.10' . "\n" . 'and PHP 8.6.0RC4 Windows distributions, not proven exact-pin builds.',
        'The active GA profile uses the authenticated exact-Git local PHP 8.6.0 build' . "\n" . 'and the retained PHP 8.5.10 distribution. The byte-oriented profile uses `-n`,' . "\n" . '`zend.multibyte=0`, explicit short-tag settings, and no opcache/JIT except in' . "\n" . 'named opcode-generation probes. Build custody is local, not hermetic attestation.' . "\n\n" .
        'Phase 8D adopts the [versioned GA source lock](../../tools/8.6/source-lock-ga-v1.json).' . "\n" .
        'The [RC7-to-GA review](../../docs/8.6/ga-finalization/review.json) identifies no' . "\n" .
        'additional structural, lexical, or contextual change; this does not close the' . "\n" .
        'remaining discovery queue. RC4 and RC7 observations remain historical evidence.' . "\n" .
        'The [GA remediation report](../../docs/8.6/ga-certification-remediation.md)' . "\n" .
        'records current gate outcomes and limitations. PHP 8.6 remains draft.', $changes);
    // Cite GA files without carrying potentially shifted RC4 line numbers forward.
    $changes = preg_replace_callback('~(https://github.com/php/php-src/blob/)' . $rc4 . '/([^\s)#]+)(?:#L[0-9]+(?:-L[0-9]+)?)?~', static fn($m) => $m[1] . $ga . '/' . $m[2], $changes);
    $changes = str_replace('## 9. Conformance and regression evidence', '## 9. Conformance and regression evidence' . "\n\n" .
        'The original phase results below describe their recorded source profiles.' . "\n" .
        'The GA successor independently executes the mandatory 36 original plus two' . "\n" .
        'hybrid obligations. Freshness, gate results, and unresolved requirements are' . "\n" .
        'recorded in the GA remediation report; earlier pass counts are not GA sign-off.', $changes);
    file_put_contents('grammar/8.6/changes.md', $changes);
    $map = S::read('tools/8.6/change-map.json');
    $map['pin'] = $ga; $map['changesSha256'] = hash('sha256', $changes);
    $map['predecessor'] = ['path' => 'tools/8.6/change-map.json', 'sha256' => hash_file('sha256', 'tools/8.6/change-map.json')];
    foreach ($map['findings'] as &$finding) foreach ($finding['sources'] as &$url) $url = preg_replace('/#L[0-9]+(?:-L[0-9]+)?$/', '', str_replace($rc4, $ga, $url));
    unset($finding, $url);
    putNew('tools/8.6/change-map-ga-v1.json', S::json($map));
    $manifest = S::read('php-grammar.json');
    $manifest['versions'][1]['phpSource']['commit'] = $ga;
    $manifest['versions'][1]['sourceProfile'] = 'Draft GA profile: authenticated PHP 8.6.0 exact-Git local build; byte source, zend.multibyte=0, explicit short tags; structural matching with external contextual constraints and unresolved certification blockers. No final conformance claim.';
    $manifest['versions'][1]['metadata']['sourceLock'] = 'tools/8.6/source-lock-ga-v1.json';
    $manifest['versions'][1]['metadata']['changeTraceability'] = 'tools/8.6/change-map-ga-v1.json';
    $manifest['versions'][1]['metadata']['sourceEvidence'] = 'docs/8.6/ga-certification-remediation/adoption-v1.json';
    // Preserve formatting and bytes of the PHP 8.5 entry and all other manifest data.
    $bytes = file_get_contents('php-grammar.json');
    $changed = preg_replace_callback('/\{\s*"version"\s*:\s*"8\.6".*?\n        \}/s',
        static fn() => str_replace("\n", "\n        ", rtrim(S::json($manifest['versions'][1]))), $bytes, 1, $count);
    if ($count !== 1) throw new RuntimeException('Unexpected manifest serialization');
    file_put_contents('php-grammar.json', $changed);
    echo "GA draft adopted; historical profiles retained.\n";
    exit;
}
if ($mode === '--successors') {
    $base = S::read('tools/8.6/source-lock-ga-v1.json');
    $auth = S::read('docs/8.6/ga-finalization/build-source.json')['sourceHashes'];
    $hashes = static function(array $files) use ($auth): array {
        foreach ($files as $p => &$h) {
            if (!isset($auth[$p])) throw new RuntimeException('Unauthenticated GA input: ' . $p);
            $h = $auth[$p];
        }
        return $files;
    };
    $pfa = S::read('tools/8.6/pfa-source-lock.json');
    $pfa['phpSource'] = $base['phpSource']; $pfa['files'] = $hashes($pfa['files']);
    putNew('tools/8.6/pfa-source-lock-ga-v1.json', S::json($pfa));
    $context = S::read('tools/8.6/context-source-lock.json');
    $context['phpSource']['8.6'] = $base['phpSource'];
    $context['executables']['8.6'] = $base['executables']['8.6'];
    $context['files']['8.6'] = $hashes($context['files']['8.6']);
    putNew('tools/8.6/context-source-lock-ga-v1.json', S::json($context));
    $extra = S::read('tools/8.6/final-coverage-source-lock.json');
    $extra['sourcePins']['8.6'] = $base['phpSource']['commit'];
    $extra['baseLockSha256'] = hash_file('sha256', 'tools/8.6/source-lock-ga-v1.json');
    $extra['extraRc4Files'] = $hashes($extra['extraRc4Files']);
    $extra['specificationSha256'] = hash_file('sha256', 'grammar/8.6/php.md');
    $extra['scope'] = 'GA successor of Phase 5E checks. Historical extraRc4Files key retained for algorithm parity; values authenticate GA files. Current specification includes the previously reviewed documentary scope correction.';
    putNew('tools/8.6/final-coverage-source-lock-ga-v1.json', S::json($extra));
    $deprecation = S::read('tests/fixtures/php/8.6/deprecation-cases.json');
    $deprecation['sourcePins']['8.6'] = $base['phpSource']['commit'];
    $deprecation['sourceHashes']['8.6'] = $hashes($deprecation['sourceHashes']['8.6']);
    putNew('tools/8.6/deprecation-cases-ga-v1.json', S::json($deprecation));
    $replacements = ['source-lock.json' => 'source-lock-ga-v1.json',
        'pfa-source-lock.json' => 'pfa-source-lock-ga-v1.json',
        'context-source-lock.json' => 'context-source-lock-ga-v1.json',
        'final-coverage-source-lock.json' => 'final-coverage-source-lock-ga-v1.json',
        'tests/fixtures/php/8.6/deprecation-cases.json' => 'tools/8.6/deprecation-cases-ga-v1.json'];
    $rows = [];
    foreach (['integration-check', 'pfa-check', 'context-check', 'deprecation-check', 'final-coverage-expansion'] as $name) {
        $old = 'tools/8.6/' . $name . '.php'; $new = 'tools/8.6/' . $name . '-ga-v1.php';
        // strtr applies the longest match once, never cascading replacements.
        $bytes = strtr(file_get_contents($old), $replacements);
        putNew($new, $bytes);
        $rows[$new] = ['predecessor' => $old, 'before' => hash_file('sha256', $old), 'after' => hash('sha256', $bytes)];
    }
    putNew('tools/8.6/ga-runner-transition-v1.json', S::json(['contract' => 'php86-ga-runner-transition-v1',
        'replacements' => $replacements, 'runners' => $rows,
        'meaning' => 'Identical check algorithms and case expectations, explicit GA source/profile inputs. No legacy runner is overwritten.']));
    echo "Five versioned GA runners created with unchanged checks.\n"; exit;
}
if ($mode === '--recover') {
    require_once __DIR__ . '/../lib/HistoricalEvidence.php';
    $h = new PhpGrammar\Tools\HistoricalEvidence();
    $root = str_replace('\\', '/', realpath(S::ROOT));
    $ledger = gitBytes(['-c', 'safe.directory=' . $root, 'show', $h::COMMIT . ':' . $h::LEDGER], $root);
    if ($ledger['exit_code'] !== 0 || hash('sha256', $ledger['output']) !== $h::BLOB_SHA256) throw new RuntimeException('Historical anchor unavailable');
    $rows = []; $missing = [];
    foreach (json_decode($ledger['output'], true, 512, JSON_THROW_ON_ERROR)['inputs'] as $p => $expected) {
        $target = '.audit/phase8d/historical/' . $p;
        if (is_file($target) && hash_file('sha256', $target) === $expected) {
            $rows[$p] = ['sha256' => $expected, 'source' => 'retained byte-authenticated recovery']; continue;
        }
        $source = 'current byte-identical file';
        $bytes = is_file($p) ? file_get_contents($p) : '';
        if (hash('sha256', $bytes) !== $expected) {
            $source = 'immutable Git blob';
            $r = gitBytes(['-c', 'safe.directory=' . $root, 'show', $h::COMMIT . ':' . $p], $root);
            $bytes = $r['output'];
        }
        if (hash('sha256', $bytes) !== $expected) { $missing[] = $p; continue; }
        putNew($target, $bytes); $rows[$p] = ['sha256' => $expected, 'source' => $source];
    }
    $sources = S::read('tools/8.6/source-lock.json')['files']['8.5'];
    $sources += S::read('tools/8.6/context-source-lock.json')['files']['8.5'];
    $sources += S::read('tests/fixtures/php/8.6/deprecation-cases.json')['sourceHashes']['8.5'];
    foreach ($sources as $p => $expected) {
        if ($expected === null) continue;
        $r = gitBytes(['--git-dir=.audit/rc7-git', 'show', S::PIN . ':' . $p], $root);
        if ($r['exit_code'] || hash('sha256', $r['output']) !== $expected) throw new RuntimeException('PHP 8.5 source mismatch: ' . $p . ' exit=' . $r['exit_code'] . ' expected=' . $expected . ' actual=' . hash('sha256', $r['output']) . ' prefix=' . substr($r['output'], 0, 100));
        putNew('.audit/phase8d/sources85/' . $p, $r['output']);
    }
    foreach ([S::PIN, '34308a6666b2d489c509541ea9befea9e2b42348'] as $pin) {
        foreach (['zend_language_parser.y', 'zend_language_scanner.l', 'zend_compile.c', 'zend_ast.c', 'zend_inheritance.c', 'zend_enum.c', 'zend_attributes.c'] as $name) {
            $r = gitBytes(['--git-dir=.audit/rc7-git', 'show', $pin . ':Zend/' . $name], $root);
            if ($r['exit_code'] !== 0) throw new RuntimeException('Source comparison object missing: ' . $pin);
            putNew('.audit/phase8d/source-cache/' . $pin . '/Zend/' . $name, $r['output']);
        }
    }
    putNew($out . '/recovery.json', S::json(['contract' => 'php86-ga-historical-recovery-v1',
        'anchor' => $h::COMMIT, 'ledgerBlobSha256' => $h::BLOB_SHA256, 'recovered' => $rows,
        'missing' => $missing, 'normalizationPerformed' => false, 'claim' => 'Content recovery only; no historical execution or custody attestation.']));
    echo S::json(['recovered' => count($rows), 'missing' => $missing]); exit;
}
if ($mode === '--adjudicate') {
    $rows = S::read('docs/8.6/source-audit-dispositions.json')['blockers'];
    $review = S::read('docs/8.6/local-source-context-remediation/source-review.json');
    $coverage = S::read('docs/8.6/scanner-grammar-binding-closure/coverage.json');
    $scanner = S::read('docs/8.6/scanner-grammar-binding-closure/scanner.json');
    $dispositions = [
        'PHP86-013' => ['GA adds no reviewed new syntax difference from RC7, but the existing PHP 8.5-to-8.6 discovery queue remains.', 'Review the retained 198 pending and 17 examined-unresolved hunk identities, bounded groups, transitive paths and test associations. Unexamined source risk cannot be excluded.'],
        'PHP86-014' => ['GA zend_compile_var_inner dispatches assignment expressions in write context; lint and opcode generation succeed but controlled execution reaches Invalid opcode 23/2/1. PR #25046 remains open and unmerged, outside GA ancestry.', 'The compiler/VM inconsistency is independently evidenced. Intended language validity and an enforceable bounded exclusion are not established by the proposal; preserve the blocker and grammar.'],
        'PHP86-015' => ['Issue #24794 explains skipped/ordered compiler visitation; GA retains the corresponding compiler behavior.', 'Complete construct-specific source correspondence and live/retained/discarded visitation evidence. The explanation resolves cause, not the full validity/scope obligation.'],
        'CA86-001' => ['Issue #24795 confirms skipped contextual hook checks in eliminated ASTs. The EBNF still rejects the unknown hook before elimination.', 'An in-scope false rejection blocks release. Prepare a separately evidenced Gate B correction or an explicit enforceable scope restriction; issue closure alone does not justify exclusion.'],
        'C6-CONTEXT' => ['External enforcement is labeled; the inventory contains 73 descriptions, not 73 isolated predicates.', 'Retain per-predicate source, stage, live/discarded controls and scope decisions; no claim of a complete PHP validator.'],
        'C6-COVERAGE' => ['GA observations reproduce focused scanner/binding cases; current ordinary coverage retains material uncovered identities.', 'Classify each remaining material production/alternative, scanner state/helper and binding obligation using the existing ledgers. No arbitrary percentage gate.'],
        'C6-PROVENANCE' => ['Authenticated final source, generated files, local build configuration, binary and DLL are adopted through a versioned GA profile.', 'The exact-build sub-obligation is satisfied under local custody. Complete successful release evidence and independent historical/current compatibility are still required; no hermetic build claim.'],
    ];
    $result = [];
    foreach ($rows as $row) $result[] = ['id' => $row['id'], 'originalRequirement' => $row,
        'gaEvidenceConclusion' => $dispositions[$row['id']][0], 'remainingRequirement' => $dispositions[$row['id']][1],
        'boundedDispositionPermittedNow' => false, 'blocksCertification' => true];
    putNew($out . '/blockers-v1.json', S::json(['contract' => 'php86-ga-blocker-adjudication-v1', 'sourceCommit' => '8aaada3f9605d81a38b8240f93c7389dfbc77ef9',
        'policy' => ['path' => 'docs/conformance-policy.md', 'sha256' => hash_file('sha256', 'docs/conformance-policy.md')],
        'sourceQueue' => ['path' => 'docs/8.6/local-source-context-remediation/source-review.json', 'sha256' => hash_file('sha256', 'docs/8.6/local-source-context-remediation/source-review.json'), 'counts' => $review['counts']],
        'coverage' => ['path' => 'docs/8.6/scanner-grammar-binding-closure/coverage.json', 'sha256' => hash_file('sha256', 'docs/8.6/scanner-grammar-binding-closure/coverage.json'),
            'productionWitnesses' => $coverage['after']['productions'], 'alternativeWitnesses' => $coverage['after']['alternatives'], 'complete' => $coverage['complete']],
        'scanner' => ['path' => 'docs/8.6/scanner-grammar-binding-closure/scanner.json', 'sha256' => hash_file('sha256', 'docs/8.6/scanner-grammar-binding-closure/scanner.json'), 'remaining' => $scanner['remaining'], 'complete' => $scanner['complete']],
        'blockers' => $result, 'certificationGranted' => false]));
    putNew($out . '/adoption-v1.json', S::json(['contract' => 'php86-ga-adoption-v1', 'activeCertificationProfile' => true,
        'sourceCommit' => '8aaada3f9605d81a38b8240f93c7389dfbc77ef9', 'status' => 'draft',
        'sourceLock' => 'tools/8.6/source-lock-ga-v1.json', 'changeTraceability' => 'tools/8.6/change-map-ga-v1.json',
        'executableReceipt' => 'docs/8.6/ga-finalization/build-receipt.json', 'retainedGaSeal' => '05b43c14e487160b5451d34bbd330276feaffacedebbe3249d415e576563bfb7',
        'preservationBaseline' => 'b2a0d55a7cec36ef78d9b7e307af04f407643965c5b33553fe2137bcdcd6f6ed',
        'releaseGateAccounting' => ['original' => 36, 'hybrid' => 2], 'certificationGranted' => false]));
    echo S::json(['sourceQueue' => $review['counts'], 'blockers' => count($result)]); exit;
}
if ($mode === '--seal-transition') {
    require_once __DIR__ . '/../lib/GaCertification.php';
    $baseline = S::read($out . '/baseline.json')['files']; $changes = [];
    foreach (PhpGrammar\Tools\GaCertification::CHANGED as $p) $changes[$p] = ['before' => $baseline[$p], 'after' => hash_file('sha256', $p)];
    $additions = [];
    foreach (S::files('tools/8.6') as $p) if (str_contains($p, '-ga-v1.') || str_contains($p, 'ga-runner-transition')) $additions[$p] = hash_file('sha256', $p);
    foreach (['adoption-v1.json', 'blockers-v1.json', 'upstream-status.json'] as $p) $additions[$out . '/' . $p] = hash_file('sha256', $out . '/' . $p);
    putNew($out . '/transition-v1.json', S::json(['contract' => 'php86-ga-preservation-v1', 'baseline' => PhpGrammar\Tools\GaCertification::BASELINE,
        'changes' => $changes, 'additions' => $additions,
        'authorization' => 'Phase 8D explicit GA adoption. Only manifest/source-reference updates and version-selected/historical-snapshot regression checks; old contract checkers, policies, grammars and fixture expectations are unchanged.']));
    echo hash_file('sha256', $out . '/transition-v1.json') . "\n"; exit;
}
if ($mode === '--retain-test-predecessors') {
    $baseline = S::read($out . '/baseline.json')['files'];
    foreach (['tests/UpstreamResolutionFreshnessTest.php', 'tests/Php/Conformance/Php86CompilerContextualTest.php'] as $p) {
        if (hash_file('sha256', $p) !== $baseline[$p]) throw new RuntimeException('Test predecessor changed: ' . $p);
        putNew($out . '/predecessor/' . $p, file_get_contents($p));
    }
    exit;
}
if ($mode === '--recover-historical-manifest') {
    require_once __DIR__ . '/../lib/HistoricalEvidence.php';
    $h = new PhpGrammar\Tools\HistoricalEvidence();
    $root = str_replace('\\', '/', realpath(S::ROOT));
    $r = gitBytes(['show', $h::COMMIT . ':' . $h::LEDGER], $root);
    if (hash('sha256', $r['output']) !== $h::BLOB_SHA256) throw new RuntimeException('Historical anchor mismatch');
    $expected = json_decode($r['output'], true, 512, JSON_THROW_ON_ERROR)['inputs']['php-grammar.json'];
    $history = gitBytes(['rev-list', '--all', '--', 'php-grammar.json'], $root); $rows = []; $found = null;
    foreach (preg_split('/\s+/', trim($history['output'])) as $commit) {
        if (!preg_match('/^[a-f0-9]{40}$/D', $commit)) throw new RuntimeException('Invalid Git history response');
        $r = gitBytes(['show', $commit . ':php-grammar.json'], $root);
        $rows[$commit] = hash('sha256', $r['output']);
        if ($rows[$commit] === $expected) { $found = $commit; putNew('.audit/phase8d/historical/php-grammar.json', $r['output']); break; }
    }
    putNew($out . '/historical-manifest-recovery.json', S::json(['expected' => $expected, 'examinedGitObjects' => $rows,
        'recoveredFrom' => $found, 'normalizationPerformed' => false]));
    echo S::json(['examined' => count($rows), 'recoveredFrom' => $found]); exit;
}
if ($mode === '--upstream-status') {
    $url = 'https://api.github.com/repos/php/php-src/pulls/25046';
    $c = curl_init($url); $headers = '';
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERAGENT => 'php-grammar-ga-certification', CURLOPT_TIMEOUT => 60,
        CURLOPT_HEADERFUNCTION => static function($c, $line) use (&$headers) { $headers .= $line; return strlen($line); }]);
    $body = curl_exec($c); $status = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
    if ($body === false || $status !== 200) throw new RuntimeException('Upstream status request failed: ' . $status);
    $pr = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    putNew($out . '/pr25046.body', $body); putNew($out . '/pr25046.headers', $headers);
    $ancestry = S::run(['git', '--git-dir=.audit/rc7-git', 'merge-base', '--is-ancestor', $pr['head']['sha'], '8aaada3f9605d81a38b8240f93c7389dfbc77ef9'], str_replace('\\', '/', realpath(S::ROOT)));
    putNew($out . '/upstream-status.json', S::json(['url' => $url, 'httpStatus' => $status, 'retrievedAt' => gmdate('c'),
        'bodySha256' => hash('sha256', $body), 'headersSha256' => hash('sha256', $headers),
        'state' => $pr['state'], 'merged' => $pr['merged'], 'head' => $pr['head']['sha'],
        'gaAncestry' => $ancestry, 'limitation' => 'A proposal is not implemented GA syntax; ancestry errors are not negative ancestry evidence.']));
    echo S::json(['state' => $pr['state'], 'merged' => $pr['merged'], 'ancestryExit' => $ancestry['exit_code']]); exit;
}
throw new RuntimeException('Select --baseline, --adopt, --successors, --recover or --upstream-status');
