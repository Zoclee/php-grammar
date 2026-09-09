<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\Coverage\CoverageCollector;
use PhpGrammar\Ebnf\Coverage\CoverageIdentityMap;
use PhpGrammar\Ebnf\Coverage\CoverageReport;
use PhpGrammar\Repository\RepositoryManifest;

final readonly class GrammarCoverageAnalyzer
{
    public function __construct(
        private RepositoryManifest $manifest,
    ) {
    }

    /**
     * @param list<RuleLevelCase> $ruleLevelCases
     */
    public function analyze(string $version, array $ruleLevelCases = []): CoverageReport
    {
        $repository = new GrammarRepository($this->manifest);
        $grammar = $repository->load($version);
        $identityMap = CoverageIdentityMap::fromGrammar($grammar);
        $attempted = new CoverageCollector();
        $matched = new CoverageCollector();

        $package = $this->manifest->package($version);
        foreach ($this->fixtureFiles($package->conformanceFixturePath, 'valid') as $path) {
            $coverage = new CoverageCollector();
            $source = file_get_contents($path);
            if (!is_string($source)) {
                throw new ConformanceException(sprintf('Unable to read conformance fixture: %s', $path));
            }

            PhpGrammarMatcher::forManifest($this->manifest)->withCoverage($coverage)->matches($version, $source);
            $attempted->merge($coverage);
            $matched->merge($coverage);
        }

        foreach ($this->fixtureFiles($package->conformanceFixturePath, 'invalid') as $path) {
            $coverage = new CoverageCollector();
            $source = file_get_contents($path);
            if (!is_string($source)) {
                throw new ConformanceException(sprintf('Unable to read conformance fixture: %s', $path));
            }

            PhpGrammarMatcher::forManifest($this->manifest)->withCoverage($coverage)->matches($version, $source);
            $attempted->merge($coverage);
        }

        foreach ($ruleLevelCases as $case) {
            $coverage = new CoverageCollector();
            PhpGrammarMatcher::forManifest($this->manifest)->withCoverage($coverage)->matchesRule($version, $case->rule, $case->source);
            $attempted->merge($coverage);
            $matched->merge($coverage);
        }

        return new CoverageReport($identityMap, $attempted, $matched);
    }

    /**
     * @return list<string>
     */
    private function fixtureFiles(string $fixturePath, string $kind): array
    {
        $files = glob($this->manifest->absolutePath($fixturePath) . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        return $files;
    }
}
