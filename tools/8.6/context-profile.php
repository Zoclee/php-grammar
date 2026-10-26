<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/ContextEvidence.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ContextEvidence as E;
use PhpGrammar\Tools\Certification86 as C;
$root = str_replace('\\', '/', realpath(S::ROOT));
$e = new E($root); $c = new C($root);
$args = S::args($e->defaults() + ['report' => ''], ['generate', 'check']);
try {
    if ((int)$args['generate'] + (int)$args['check'] !== 1) throw new RuntimeException('Select --generate or --check');
    $e->profile($args['profile']); // No implicit executable profile.
    if ($args['report'] === '') throw new RuntimeException('Explicit report path required');
    if ($args['check']) {
        $errors = $e->verify($args['report'], $args['profile']);
    } else {
        $path = $c->path($args['report']);
        if (file_exists($path) || file_exists($path . '.receipt.json')) throw new RuntimeException('Never overwrite contextual evidence');
        // Output must already have a directory. Refuse writes through symlink parents.
        if (!is_dir(dirname($path))) throw new RuntimeException('Create a new evidence directory first');
        for ($dir = dirname($path); strlen($dir) >= strlen($root); $dir = dirname($dir)) {
            if (is_link($dir)) throw new RuntimeException('Symlink output directory');
            if ($dir === dirname($dir)) break;
        }
        $r = $e->generate(array_intersect_key($args, $e->defaults()));
        $stream = fopen($path, 'x');
        if ($stream === false) throw new RuntimeException('Cannot create evidence');
        fwrite($stream, S::json($r)); fclose($stream);
        $stream = fopen($path . '.receipt.json', 'x');
        if ($stream === false) throw new RuntimeException('Cannot create receipt');
        fwrite($stream, S::json(['reportSha256' => hash_file('sha256', $path),
            'scope' => 'Trusted local run custody; not a signature or certification.'])); fclose($stream);
        $errors = $r['errors'];
    }
    echo S::json(['contract' => E::CONTRACT, 'profile' => $args['profile'], 'errors' => $errors,
        'contextEvidenceVerified' => $errors === [], 'certificationGranted' => false]);
    exit($errors === [] ? 0 : 1);
} catch (Throwable $ex) { echo S::json(['errors' => [$ex->getMessage()], 'certificationGranted' => false]); exit(1); }
