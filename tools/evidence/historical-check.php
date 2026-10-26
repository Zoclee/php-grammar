<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/HistoricalEvidence.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\HistoricalEvidence as H;
$args = S::args(['snapshot' => S::ROOT . '/.audit/phase7c1-historical', 'supplement' => S::ROOT . '/.audit/phase7b-certification']);
try {
    $report = H::verify(realpath(S::ROOT), $args['snapshot'], $args['supplement']);
    echo S::json($report); exit($report['complete'] ? 0 : 1);
} catch (Throwable $e) { echo S::json(['complete' => false, 'errors' => [$e->getMessage()]]); exit(1); }
