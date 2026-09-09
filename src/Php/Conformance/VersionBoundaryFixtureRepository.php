<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

final readonly class VersionBoundaryFixtureRepository
{
    public function __construct(
        private string $rootDirectory,
    ) {
    }

    /**
     * @return list<VersionBoundaryCase>
     */
    public function load(): array
    {
        if (!is_dir($this->rootDirectory)) {
            return [];
        }

        $cases = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $this->rootDirectory,
            \FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $json = file_get_contents($file->getPathname());
            if (!is_string($json)) {
                throw new ConformanceException(sprintf('Unable to read boundary fixture metadata: %s', $file->getPathname()));
            }

            $data = json_decode($json, true);
            if (!is_array($data)) {
                throw new ConformanceException(sprintf('Boundary fixture metadata must be a JSON object: %s', $file->getPathname()));
            }

            $cases[] = VersionBoundaryCase::fromArray($data, $file->getPath());
        }

        usort($cases, static fn (VersionBoundaryCase $a, VersionBoundaryCase $b): int => $a->name <=> $b->name);

        return $cases;
    }
}
