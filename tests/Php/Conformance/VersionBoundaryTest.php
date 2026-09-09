<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Php\Conformance;

use PhpGrammar\Php\Conformance\ConformanceException;
use PhpGrammar\Php\Conformance\PhpGrammarMatcher;
use PhpGrammar\Php\Conformance\VersionBoundaryCase;
use PhpGrammar\Php\Conformance\VersionBoundaryFixtureRepository;
use PhpGrammar\Php\Conformance\VersionBoundaryRunner;
use PhpGrammar\Repository\RepositoryManifest;
use PHPUnit\Framework\TestCase;

final class VersionBoundaryTest extends TestCase
{
    public function testBoundaryCaseComputesIntroductionAndRemovalExpectations(): void
    {
        $introduced = new VersionBoundaryCase('feature', '8.5', null, '<?php echo 1;');
        $removed = new VersionBoundaryCase('removed-feature', '8.0', '8.4', '<?php echo 1;');

        self::assertFalse($introduced->shouldAccept('8.4'));
        self::assertTrue($introduced->shouldAccept('8.5'));
        self::assertTrue($introduced->shouldAccept('8.6'));
        self::assertTrue($removed->shouldAccept('8.4'));
        self::assertFalse($removed->shouldAccept('8.5'));
    }

    public function testBoundaryRunnerHandlesSingleVersionRepository(): void
    {
        $manifest = RepositoryManifest::fromRepositoryRoot(dirname(__DIR__, 3));
        $runner = new VersionBoundaryRunner($manifest, PhpGrammarMatcher::forManifest($manifest));
        $case = new VersionBoundaryCase('current-valid-source', '8.5', null, '<?php echo 1;');

        self::assertSame(['8.5' => true], $runner->expected($case));
        self::assertSame(['8.5' => true], $runner->evaluate($case));
    }

    public function testBoundaryFixtureRepositoryAllowsEmptyDirectory(): void
    {
        $cases = (new VersionBoundaryFixtureRepository(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'version-boundaries',
        ))->load();

        self::assertSame([], $cases);
    }

    public function testBoundaryFixtureCanLoadInlineSourceMetadata(): void
    {
        $directory = $this->temporaryDirectory();
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'pipe.json', json_encode([
            'name' => 'pipe operator',
            'firstSupportedVersion' => '8.5',
            'source' => '<?php $value = $input |> trim(...);',
            'reference' => 'https://wiki.php.net/rfc/pipe-operator-v3',
        ], JSON_THROW_ON_ERROR));

        $cases = (new VersionBoundaryFixtureRepository($directory))->load();

        self::assertCount(1, $cases);
        self::assertSame('pipe operator', $cases[0]->name);
        self::assertTrue($cases[0]->shouldAccept('8.5'));
        self::assertFalse($cases[0]->shouldAccept('8.4'));
    }

    public function testBoundaryFixtureFailsClearlyForMissingSourceFile(): void
    {
        $directory = $this->temporaryDirectory();
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'missing.json', json_encode([
            'name' => 'missing',
            'firstSupportedVersion' => '8.5',
            'fixture' => 'missing.php',
        ], JSON_THROW_ON_ERROR));

        $this->expectException(ConformanceException::class);

        (new VersionBoundaryFixtureRepository($directory))->load();
    }

    public function testBoundaryEvaluationFailsClearlyWhenClaimedVersionCannotBeLoaded(): void
    {
        $manifest = RepositoryManifest::fromArray(dirname(__DIR__, 3), [
            'versions' => [
                [
                    'version' => '8.4',
                    'rootProduction' => 'source-file',
                    'grammar' => 'grammar/8.4/php.ebnf',
                    'documentation' => 'grammar/8.4/php.md',
                    'conformanceFixtures' => 'tests/fixtures/php/8.4',
                    'lexer' => '8.5',
                ],
            ],
            'lexicalPrimitives' => ['code-unit'],
        ]);
        $runner = new VersionBoundaryRunner($manifest, PhpGrammarMatcher::forManifest($manifest));

        $this->expectException(ConformanceException::class);

        $runner->evaluate(new VersionBoundaryCase('missing-version', '8.4', null, '<?php echo 1;'));
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-grammar-' . bin2hex(random_bytes(8));
        mkdir($directory);

        return $directory;
    }
}
