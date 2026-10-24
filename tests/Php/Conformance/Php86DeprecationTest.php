<?php
declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Php86DeprecationTest extends TestCase
{
    private static function root(): string { return dirname(__DIR__, 3); }
    private static function json(string $path): array {
        return json_decode(file_get_contents(self::root().'/'.$path), true, 512, JSON_THROW_ON_ERROR);
    }
    public static function cases(): iterable {
        foreach (self::json('tests/fixtures/php/8.6/deprecation-cases.json')['cases'] as $case) yield $case['id'] => [$case];
    }
    #[DataProvider('cases')]
    public function testDeprecationDoesNotRemoveAcceptedStructure(array $case): void {
        $matcher = PhpGrammarMatcher::forRepositoryRoot(self::root());
        foreach (['8.5', '8.6'] as $version) {
            self::assertSame($case['accepted'], $matcher->matches($version, $case['source'])->matched, $case['id'].' '.$version);
        }
    }
    #[DataProvider('cases')]
    public function testEvidenceSeparatesAcceptanceAndDiagnosticStage(array $case): void {
        $report = self::json('docs/8.6/deprecation-validation.json');
        $records = array_values(array_filter($report['observations'], static fn($r) => $r['id'] === $case['id']));
        self::assertCount(4, $records);
        foreach ($records as $record) {
            self::assertSame('PHP86-010', $record['finding']);
            self::assertSame(hash('sha256', $case['source']), $record['sourceSha256']);
            self::assertSame($case['accepted'], $record['result']['exit'] === 0);
            $expected = $case['expected'][$record['version']][$record['stage']];
            self::assertSame($expected !== '', str_contains($record['result']['output'], 'Deprecated:'));
            if ($expected !== '') self::assertStringContainsString($expected, $record['result']['output']);
            if (!$case['accepted']) self::assertStringContainsString('Parse error:', $record['result']['output']);
        }
    }
    public function testEvidenceManifestIsCurrent(): void {
        $report = self::json('docs/8.6/deprecation-validation.json');
        self::assertSame([], $report['failures']);
        self::assertSame(hash_file('sha256', self::root().'/tests/fixtures/php/8.6/deprecation-cases.json'), $report['caseManifestSha256']);
    }
}
