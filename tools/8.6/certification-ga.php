<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/../lib/GaCertification.php';
use PhpGrammar\Tools\GaCertification as G;
use PhpGrammar\Tools\Support as S;
$root = str_replace('\\', '/', realpath(S::ROOT)); chdir($root);
$g = new G($root);
$args = S::args(['gate' => '', 'output' => '', 'revision' => '', 'report' => ''], ['run', 'check', 'claims']);
if ($args['gate'] !== '') {
    if ($args['gate'] === 'current-compatibility85') {
        if (!preg_match('~/runs/([a-z0-9_-]+)/current-compatibility85\.json$~D', $args['output'], $m)) throw new RuntimeException('Explicit compatibility revision required');
        $GLOBALS['gaRevision'] = $m[1];
    }
    $result = $g->gate($args['gate']);
    if ($args['output'] !== '') G::writeNew($args['output'], $result);
    echo S::json(['gate' => $args['gate'], 'errors' => $result['errors'], 'certificationGranted' => false]);
    exit($result['errors'] === [] ? 0 : 1);
}
if ($args['run']) {
    if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $args['revision'])) throw new RuntimeException('Safe new revision required');
    $out = G::OUT . '/runs/' . $args['revision'];
    if (file_exists($out)) throw new RuntimeException('Revision exists; never overwrite');
    $errors = [...$g->preservation(), ...$g->runnerErrors(), ...$g->environment()['errors']];
    if ($errors) throw new RuntimeException(implode('; ', $errors));
    $inputs = $g->inventory(); mkdir($out, 0777, true);
    $summaryBefore = is_file(G::OUT . '.md') ? hash_file('sha256', G::OUT . '.md') : null;
    $rows = [];
    foreach ($g->commands($out) as $id => $command) {
        echo 'Executing ' . $id . "\n"; flush();
        $log = $out . '/' . $id . '.log';
        try { $r = S::run($command, $root, $log); }
        catch (Throwable $e) { $r = ['exit_code' => 127, 'output' => $e->getMessage()]; file_put_contents($log, $r['output']); }
        $kind = $g->gates()[$id][0];
        $rows[$id] = ['originalObligation' => $g->gates()[$id], 'command' => $command, 'cwd' => $root,
            'exit_code' => $r['exit_code'], 'status' => $r['exit_code'] === 0 ? 'passed' : (str_starts_with($kind, 'blocked:') ? 'blocked' : 'failed'),
            'log' => $log, 'logSha256' => hash_file('sha256', $log)];
        G::writeNew($out . '/' . $id . '.receipt.json', $rows[$id]);
        echo $id . ': ' . $rows[$id]['status'] . "\n"; flush();
    }
    $artifacts = [];
    foreach (S::files($out) as $p) $artifacts[$p] = hash_file('sha256', $p);
    $report = ['contract' => G::CONTRACT, 'sourceCommit' => G::PIN, 'originalObligations' => 36, 'hybridObligations' => 2,
        'output' => $out, 'inputs' => $inputs, 'inputsUnchanged' => $inputs === $g->inventory(),
        'results' => $rows, 'artifacts' => $artifacts, 'blockers' => \PhpGrammar\Tools\Certification86::BLOCKERS,
        'certificationGranted' => false, 'decision' => 'NOT SIGNED OFF — RELEASE BLOCKED'];
    if ((is_file(G::OUT . '.md') ? hash_file('sha256', G::OUT . '.md') : null) !== $summaryBefore) throw new RuntimeException('Summary edited during execution; refusing overwrite');
    $summary = $g->reportMarkdown($report);
    file_put_contents(G::OUT . '.md', $summary);
    $report['summary'] = ['path' => G::OUT . '.md', 'sha256' => hash('sha256', $summary)];
    G::writeNew($out . '/evidence.json', $report);
    G::writeNew($out . '/run-receipt.json', ['contract' => G::CONTRACT, 'sha256' => hash_file('sha256', $out . '/evidence.json')]);
    echo S::json(['report' => $out . '/evidence.json', 'statuses' => array_count_values(array_column($rows, 'status')), 'inputsUnchanged' => $report['inputsUnchanged']]);
    exit(1); // Preserved material blockers prohibit a certification grant.
}
if ($args['check'] || $args['claims']) {
    $r = $g->read($args['report']); $errors = $g->verify($r, $args['report']);
    if ($args['claims']) {
        foreach ($r['results'] as $id => $row) if ($row['status'] !== 'passed') $errors[] = 'Unsatisfied obligation: ' . $id;
        foreach ($r['blockers'] as $id) $errors[] = 'Unresolved material blocker: ' . $id;
    }
    echo S::json(['contract' => G::CONTRACT, 'evidenceVerified' => $errors === [], 'errors' => $errors, 'certificationGranted' => false]);
    exit($errors === [] ? 0 : 1);
}
throw new RuntimeException('Select --run, --check, --claims, or --gate');
