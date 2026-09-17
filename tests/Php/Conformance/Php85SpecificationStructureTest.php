<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PHPUnit\Framework\TestCase;

final class Php85SpecificationStructureTest extends TestCase
{
    private static function documentation(): string
    {
        return str_replace("\r\n", "\n", file_get_contents(dirname(__DIR__, 3) . '/grammar/8.5/php.md'));
    }

    public function testNormativeNavigationAndEvidenceOrder(): void
    {
        $document = self::documentation();
        $headings = ['Status and scope', 'Conformance model', 'Source and lexical model',
            'Scanner state transitions', 'Names and tokens', 'Literals and strings', 'Types',
            'Expressions and precedence', 'Variables, calls and dereferencing', 'Arguments and arrays',
            'Statements and control flow', 'Functions, closures and parameters',
            'Classes, interfaces, traits and enums', 'Namespaces and imports', 'Attributes',
            'Constant expressions', 'Contextual syntax constraints', 'Parser/compiler boundary rules',
            'Deprecated but accepted syntax', 'Known abstractions and conformance limits',
            'Syntactic productions', 'Production index', 'Canonical EBNF', 'Conformance evidence'];
        preg_match_all('/^## (.+)$/m', $document, $matches);
        self::assertSame($headings, $matches[1]);
        $introduction = substr($document, 0, strpos($document, '## Source and lexical model'));
        self::assertStringNotContainsString('Grammar Completeness Phase', $introduction);
        foreach (['Layer 1 — Source/lexical rules', 'Layer 2 — Syntactic EBNF',
            'Layer 3 — Contextual syntax constraints'] as $layer) {
            self::assertStringContainsString('### ' . $layer, $introduction);
        }
        self::assertStringContainsString('7a4c62795365ed6a97a0184c96375b9fb4d53b1e', $introduction);
        foreach (['Structural parse acceptance', 'Parser-action validation', 'Compiler validation',
            'Constant-folding/discard behavior', 'Constant-expression validation order',
            'Unambiguous prefix and statement structure'] as $boundary) {
            self::assertStringContainsString("\n### " . $boundary . "\n", $document);
        }
        self::assertGreaterThan(strpos($document, '<!-- END GENERATED EBNF -->'),
            strpos($document, '## Conformance evidence'));
    }

    public function testLocalSpecificationLinksResolve(): void
    {
        $document = self::documentation();
        // Ignore generated grammar contents when collecting Markdown headings.
        $prose = preg_replace('/```.*?```/s', '', $document);
        preg_match_all('/^#{1,6} (.+)$/m', $prose, $headings);
        $anchors = [];
        foreach ($headings[1] as $heading) {
            $anchors[] = str_replace(' ', '-', preg_replace('/[^\p{L}\p{N}_ -]/u', '', strtolower($heading)));
        }
        preg_match_all('/\]\(#([^)]*)\)/', $prose, $links);
        self::assertNotEmpty($links[1]);
        foreach (array_unique($links[1]) as $link) {
            self::assertContains($link, $anchors, 'Unresolved specification anchor: ' . $link);
        }
    }

    public function testSynchronizationPreservesAppendixAndDetectsGeneratedDrift(): void
    {
        $root = dirname(__DIR__, 3);
        $temporary = sys_get_temp_dir() . '/php-grammar-doc-' . bin2hex(random_bytes(8));
        mkdir($temporary . '/tools/8.5', 0777, true);
        mkdir($temporary . '/grammar/8.5', 0777, true);
        try {
            foreach (['tools/8.5/sync-documentation.php', 'tools/8.5/grammar-sections.php',
                'grammar/8.5/php.ebnf'] as $file) {
                copy($root . '/' . $file, $temporary . '/' . $file);
            }
            $path = $temporary . '/grammar/8.5/php.md';
            $document = self::documentation();
            $appendix = substr($document, strpos($document, '<!-- END GENERATED EBNF -->'));
            // Drift both generated areas; hand-written evidence must survive regeneration.
            $stale = preg_replace('/(<!-- BEGIN GENERATED PRODUCTION INDEX -->).*?(<!-- END GENERATED PRODUCTION INDEX -->)/s', '$1 stale $2', $document);
            $stale = preg_replace('/(<!-- BEGIN GENERATED EBNF -->).*?(<!-- END GENERATED EBNF -->)/s', '$1 stale $2', $stale);
            file_put_contents($path, $stale);
            self::assertSame(1, self::synchronize($temporary, true));
            self::assertSame(0, self::synchronize($temporary, false));
            self::assertSame($document, file_get_contents($path));
            self::assertStringEndsWith($appendix, file_get_contents($path));
            self::assertSame(0, self::synchronize($temporary, true));
            self::assertSame(0, self::synchronize($temporary, false));
            self::assertSame($document, file_get_contents($path));
            // Reject an incomplete marker pair without overwriting any prose.
            $broken = str_replace('<!-- END GENERATED EBNF -->', '', $document);
            file_put_contents($path, $broken);
            self::assertNotSame(0, self::synchronize($temporary, false));
            self::assertSame($broken, file_get_contents($path));
        } finally {
            foreach (['tools/8.5/sync-documentation.php', 'tools/8.5/grammar-sections.php',
                'grammar/8.5/php.ebnf', 'grammar/8.5/php.md'] as $file) {
                if (is_file($temporary . '/' . $file)) {
                    unlink($temporary . '/' . $file);
                }
            }
            foreach (['tools/8.5', 'grammar/8.5', 'tools', 'grammar', ''] as $directory) {
                rmdir($temporary . '/' . $directory);
            }
        }
    }

    private static function synchronize(string $root, bool $check): int
    {
        $command = [PHP_BINARY, $root . '/tools/8.5/sync-documentation.php'];
        if ($check) {
            $command[] = '--check';
        }
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to run documentation synchronizer.');
        }
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process);
    }
}
