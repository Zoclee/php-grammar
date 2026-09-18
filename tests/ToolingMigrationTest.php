<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PHPUnit\Framework\TestCase;
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ParserReconciliation;
use PhpGrammar\Tools\UnifiedDiff;
require_once __DIR__ . '/../tools/lib/Support.php';
require_once __DIR__ . '/../tools/lib/ParserReconciliation.php';
require_once __DIR__ . '/../tools/lib/UnifiedDiff.php';

final class ToolingMigrationTest extends TestCase
{
    public function testBisonAlternativesRespectActionsCommentsAndQuotes(): void
    {
        $body = <<<'BISON'
  '|' item { zend_one("};|"); /* ignored } */ if (ok) { zend_two(); } }
| /* empty */ { zend_empty(); } // ignored | ;
| ';' ; trailing ignored
BISON;
        self::assertSame([
            ["'|' item", '{ zend_one("};|");  if (ok) { zend_two(); } }'],
            ['', '{ zend_empty(); }'],
            ["';'", ''],
        ], ParserReconciliation::branches($body));
    }

    public function testUnclosedActionFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unclosed C action');
        ParserReconciliation::branches('item { if (ok) { call(); }');
    }

    public function testUnifiedDiffRangesAndContext(): void
    {
        self::assertSame([], UnifiedDiff::compare(['same'], ['same'], 'a', 'b'));
        self::assertSame(['--- a', '+++ b', '@@ -0,0 +1 @@', '+new'], UnifiedDiff::compare([], ['new'], 'a', 'b'));
        self::assertSame(['--- a', '+++ b', '@@ -1 +0,0 @@', '-old'], UnifiedDiff::compare(['old'], [], 'a', 'b'));
        self::assertSame(['--- a', '+++ b', '@@ -1,3 +1,3 @@', ' one', '-old', '+new', ' three'], UnifiedDiff::compare(['one', 'old', 'three'], ['one', 'new', 'three'], 'a', 'b'));
        $a = array_map('strval', range(1, 20));
        $b = $a;
        $b[0] = 'first';
        $b[19] = 'last';
        self::assertSame(['--- a', '+++ b', '@@ -1,4 +1,4 @@', '-1', '+first', ' 2', ' 3', ' 4', '@@ -17,4 +17,4 @@', ' 17', ' 18', ' 19', '-20', '+last'], UnifiedDiff::compare($a, $b, 'a', 'b'));
    }

    public function testUnifiedDiffPopularLineSuppressionMatchesOriginal(): void
    {
        $a = array_fill(0, 220, 'repeat');
        $a[110] = 'anchor';
        $b = $a;
        $b[111] = 'changed';
        // With automatic popular-line suppression, the repeated suffix has no anchor.
        $expected = ['--- a', '+++ b', '@@ -109,112 +109,112 @@', ' repeat', ' repeat', ' anchor'];
        $expected = [...$expected, ...array_fill(0, 109, '-repeat'), '+changed', ...array_fill(0, 108, '+repeat')];
        self::assertSame($expected, UnifiedDiff::compare($a, $b, 'a', 'b'));
    }

    public function testReportSerializationPreservesObjectsEscapesAndUnicode(): void
    {
        self::assertSame("{\n  \"empty\": {},\n  \"list\": [],\n  \"value\": \"\\u00e9/a\\n\"\n}\n", S::json(['empty' => (object) [], 'list' => [], 'value' => "é/a\n"]));
        self::assertSame("{\n  \"value\": \"é\"\n}\n", S::json(['value' => 'é'], true));
    }

    public function testCliErrorsAndSubprocessExitCodes(): void
    {
        $result = S::run([PHP_BINARY, 'tools/validate-manifest.php', '--unknown'], S::ROOT);
        self::assertSame(2, $result['exit_code']);
        self::assertStringContainsString('Unknown argument', $result['output']);
        $result = S::run([PHP_BINARY, 'tools/8.5/source-inventory.php'], S::ROOT);
        self::assertSame(2, $result['exit_code']);
        $result = S::run([PHP_BINARY, 'tools/8.5/source-correspondence.php', '--cache'], S::ROOT);
        self::assertSame(2, $result['exit_code']);
        $result = S::run([PHP_BINARY, '-r', 'fwrite(STDERR, "failure\n"); exit(7);'], S::ROOT);
        self::assertSame(['exit_code' => 7, 'output' => "failure\n"], $result);
        $result = S::run([PHP_BINARY, 'tools/validate-manifest.php', 'does-not-exist.json'], S::ROOT);
        self::assertSame(1, $result['exit_code']);
    }

    public function testCertificationRejectsBadEvidenceBeforeGeneration(): void
    {
        $root = sys_get_temp_dir() . '/php-grammar-certification-' . bin2hex(random_bytes(6));
        mkdir($root);
        $paths = ['tools/lib/bootstrap.php', 'tools/lib/Support.php', 'tools/8.5/certification.php', 'tools/8.5/data/certification-policy.json',
            'docs/8.5/phase6-evidence.json', 'docs/8.5/phase6-reconciliation.json', 'docs/8.5/phase3-coverage.json', 'docs/8.5/phase5-lexical-evidence.json',
            'docs/8.5/negative-boundaries.json', 'docs/8.5/source-correspondence.json', 'tests/fixtures/php/8.5/diagnostic-predicates.json',
            'docs/8.5/diagnostic-witnesses.json', 'docs/8.5/interpolation-binding.json', 'docs/8.5/phase6-matrices.json', 'docs/8.5/systematic-structure.json'];
        try {
            foreach ($paths as $path) {
                if (!is_dir(dirname($root . '/' . $path))) {
                    mkdir(dirname($root . '/' . $path), 0777, true);
                }
                copy(S::ROOT . '/' . $path, $root . '/' . $path);
            }
            foreach (['diagnostic-witnesses', 'interpolation-binding', 'phase6-matrices', 'systematic-structure'] as $name) {
                $path = $root . '/docs/8.5/' . $name . '.json';
                $data = json_decode(file_get_contents($path), true);
                $data['hashes'] = (object) [];
                file_put_contents($path, S::json($data));
            }
            $mutations = [
                ['diagnostic-witnesses', static function (array &$v): void { $v['failures'] = ['bad']; }, 'An evidence matrix has failures'],
                ['phase3-coverage', static function (array &$v): void { $v['remaining'][0]['classification'] = 'unknown'; }, 'Unclassified meaningful grammar coverage gap'],
                ['phase5-lexical-evidence', static function (array &$v): void { array_pop($v['rules']); }, 'Scanner audit denominator changed'],
                ['phase6-reconciliation', static function (array &$v): void { $v['source_pin'] = 'other'; }, 'Source pins disagree'],
                ['diagnostic-witnesses', static function (array &$v): void { ++$v['sites']; }, 'Witness execution count differs'],
                ['diagnostic-witnesses', static function (array &$v): void { $v['hashes'] = ['tools/lib/Support.php' => 'bad']; }, 'Stale matrix input'],
            ];
            foreach ($mutations as [$name, $mutate, $message]) {
                $path = $root . '/docs/8.5/' . $name . '.json';
                $original = file_get_contents($path);
                $value = json_decode($original, true);
                $mutate($value);
                file_put_contents($path, S::json($value));
                $result = S::run([PHP_BINARY, 'tools/8.5/certification.php', '--check'], $root);
                self::assertSame(1, $result['exit_code'], $message);
                self::assertStringContainsString($message, $result['output']);
                file_put_contents($path, $original);
            }
        } finally {
            // Only this test's freshly created, fixed-prefix directory is removed.
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($root);
        }
    }
}
