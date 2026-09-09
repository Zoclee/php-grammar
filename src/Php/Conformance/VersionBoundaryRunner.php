<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Repository\RepositoryManifest;

final readonly class VersionBoundaryRunner
{
    public function __construct(
        private RepositoryManifest $manifest,
        private PhpGrammarMatcher $matcher,
    ) {
    }

    /**
     * @return array<string, bool>
     */
    public function evaluate(VersionBoundaryCase $case): array
    {
        $results = [];
        foreach ($this->manifest->versions() as $version) {
            $results[$version] = $this->matcher->matches($version, $case->source)->matched;
        }

        return $results;
    }

    /**
     * @return array<string, bool>
     */
    public function expected(VersionBoundaryCase $case): array
    {
        $expected = [];
        foreach ($this->manifest->versions() as $version) {
            $expected[$version] = $case->shouldAccept($version);
        }

        return $expected;
    }
}
