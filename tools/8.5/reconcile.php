<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/ParserReconciliation.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ParserReconciliation;
$args = S::args(flags: ['check'], min: 1, max: 1);
$report = ParserReconciliation::generate($args['positionals'][0]);
S::emit('docs/8.5/phase6-reconciliation.json', $report, $args['check'], 'Phase 6 reconciliation is stale');
S::summary(['productions' => $report['parser_productions'], 'alternatives' => $report['parser_alternatives'], 'diagnostic_sites' => count($report['contextual_diagnostic_sites'])]);
