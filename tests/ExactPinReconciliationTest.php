<?php
declare(strict_types=1);
namespace PhpGrammar\Tests;
use PhpGrammar\Tools\ExactPinReconciliation as R;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;
require_once __DIR__ . '/../tools/lib/ExactPinReconciliation.php';
final class ExactPinReconciliationTest extends TestCase
{
    public static function newControls(): iterable
    {
        $root = dirname(__DIR__);
        $manifest = json_decode(file_get_contents($root . '/tests/fixtures/php/8.6/exact-pin-reconciliation-cases.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest['cases'] as $case) foreach (['8.5', '8.6'] as $version) yield $case['id'] . '/' . $version => [$case, $version];
    }
    #[DataProvider('newControls')]
    public function testNewControlStructuralBoundaries(array $case, string $version): void
    {
        $root = dirname(__DIR__); $source = file_get_contents($root . '/' . $case['fixture']);
        self::assertSame($case['sha256'], hash('sha256', $source));
        $matcher = PhpGrammarMatcher::forManifest(RepositoryManifest::fromRepositoryRoot($root));
        self::assertSame($case['expect'][$version]['structural'], $matcher->matches($version, $source)->matched);
    }
    public function testLintAndRuntimeFailureRemainSeparateFromLanguageIntent(): void
    {
        $lint = ['exit' => 0, 'stdout' => 'No syntax errors', 'stderr' => ''];
        $run = ['exit' => 255, 'stdout' => '', 'stderr' => 'Invalid opcode 23/2/1.'];
        $dump = ['exit' => 0, 'stdout' => '', 'stderr' => "0000 T1 = ASSIGN CV0(\$a) array(...)\r\n0001 ASSIGN_DIM T1 int(0)\r\n"];
        self::assertSame([], R::resultErrors($run, ['exit' => 255, 'contains' => 'Invalid opcode 23/2/1.']));
        $summary = R::stageSummary($lint, $run, $dump);
        self::assertTrue($summary['lintAccepted']); self::assertFalse($summary['executionSucceeded']);
        self::assertTrue($summary['opcodeDumpObserved']); self::assertFalse($summary['intendedLanguageValidityEstablished']);
        self::assertFalse($summary['independentZendParserVerdict']);
        self::assertSame(['0000 T1 = ASSIGN CV0($a) array(...)', '0001 ASSIGN_DIM T1 int(0)'], R::opcodeLines($dump['stderr']));
    }
    public function testMissingDumpAndUnexecutedCodeAreNotSuccessfulStageEvidence(): void
    {
        $r = ['exit' => 0, 'stdout' => '', 'stderr' => ''];
        $summary = R::stageSummary($r, null, $r);
        self::assertFalse($summary['executionObserved']); self::assertNull($summary['executionSucceeded']);
        self::assertFalse($summary['opcodeDumpObserved']);
        self::assertSame(['stdout'], R::resultErrors($r, ['exit' => 0, 'stdout' => '42']));
        self::assertSame(['exit', 'diagnostic'], R::resultErrors($r, ['exit' => 255, 'contains' => 'Invalid opcode']));
    }
}
