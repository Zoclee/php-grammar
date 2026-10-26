<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../lib/ManifestValidator.php';
require_once __DIR__ . '/../lib/Certification86.php';

use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\Support as S;

$args = S::args(['gate' => '', 'sources86' => S::ROOT . '/.audit/php-php-src-f23b4c2']);
$c = new C(realpath(S::ROOT));
$errors = [];
$details = [];
switch ($args['gate']) {
    case 'manifest':
        $errors = $c->manifestErrors();
        break;
    case 'source':
        $errors = $c->sourceErrors($c->read('tools/8.6/certification-source-lock.json'), $args['sources86']);
        break;
    case 'preservation':
        $baseline = $c->read('docs/8.6/certification-baseline.json')['hashes'];
        $errors = $c->verifyHashes($baseline);
        $details['preservedFiles'] = count($baseline);
        $details['historicalFreshnessDifferencesAlreadyInBaseline'] = [];
        foreach ($c->read('docs/8.5/final-evidence.json')['inputs'] as $path => $hash) {
            if (isset($baseline[$path]) && $baseline[$path] !== $hash) $details['historicalFreshnessDifferencesAlreadyInBaseline'][] = $path;
        }
        break;
    case 'blockers':
        $errors = $c->blockerErrors($c->read('docs/8.6/source-audit-dispositions.json')['blockers']);
        break;
    case 'freshness':
        $ledger = $c->read('docs/8.6/source-completeness-ledger.json');
        $errors = $c->verifyHashes($ledger['inputs']);
        foreach (['source-audit-coverage', 'source-audit-context', 'source-audit-integration', 'source-audit-pfa',
            'source-audit-deprecation', 'source-audit-probes', 'source-audit-boundaries'] as $name) {
            $report = $c->read('docs/8.6/' . $name . '.json');
            $errors = [...$errors, ...$c->verifyHashes($report['inputs'] ?? $report['hashes'] ?? [])];
            if (($report['errors'] ?? $report['failures'] ?? []) !== []) $errors[] = 'Failing historical audit: ' . $name;
        }
        break;
    case 'documentation':
        $text = file_get_contents($c->path('docs/8.6/certification-preparation.md'));
        foreach (['PHP86-013', 'PHP86-014', 'PHP86-015', 'draft', 'Phase 7B'] as $word) {
            if (!str_contains($text, $word)) $errors[] = 'Missing scope statement: ' . $word;
        }
        foreach (['docs/8.6/certification-preparation.md', 'docs/8.6/certification-build.md'] as $path) {
            $text = file_get_contents($c->path($path));
            if (preg_match('/[\t ]+$/m', $text)) $errors[] = 'Trailing whitespace: ' . $path;
            preg_match_all('~\[[^\]]+\]\(([^)]+)\)~', $text, $matches);
            foreach ($matches[1] as $link) {
                if (preg_match('~^https?://~', $link)) continue;
                $target = explode('#', $link)[0];
                if ($target !== '' && !file_exists(dirname($c->path($path)) . '/' . $target)) $errors[] = 'Broken documentation link: ' . $link;
            }
        }
        foreach (['tools/8.6/certification-source-lock.json', 'docs/8.6/certification-baseline.json', 'docs/8.6/certification-gates.json'] as $path) $c->read($path);
        $inventory = $c->read('docs/8.6/certification-gates.json');
        if ($inventory['gates'] !== C::gates() || $inventory['categories'] !== C::CATEGORIES) $errors[] = 'Stale gate inventory';
        break;
    default:
        throw new RuntimeException('Unknown certification gate: ' . $args['gate']);
}
echo S::json(['gate' => $args['gate'], 'errors' => $errors, 'details' => $details]);
exit($errors === [] ? 0 : 1);
