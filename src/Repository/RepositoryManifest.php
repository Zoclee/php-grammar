<?php

declare(strict_types=1);

namespace PhpGrammar\Repository;

final class RepositoryManifest
{
    /** @var array<string, VersionPackage> */
    private array $packages;

    /**
     * @param array<string, VersionPackage> $packages
     * @param list<string> $lexicalPrimitives
     */
    private function __construct(
        private readonly string $repositoryRoot,
        array $packages,
        private readonly array $lexicalPrimitives,
    ) {
        $this->packages = $packages;
    }

    public static function fromRepositoryRoot(string $repositoryRoot): self
    {
        $path = $repositoryRoot . DIRECTORY_SEPARATOR . 'php-grammar.json';
        if (!is_file($path)) {
            throw new RepositoryManifestException('Missing php-grammar.json repository manifest.');
        }

        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new RepositoryManifestException('Unable to read php-grammar.json repository manifest.');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RepositoryManifestException('Invalid php-grammar.json: expected a JSON object.');
        }

        return self::fromArray($repositoryRoot, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $repositoryRoot, array $data): self
    {
        if (!isset($data['versions']) || !is_array($data['versions']) || $data['versions'] === []) {
            throw new RepositoryManifestException('Repository manifest must define at least one version.');
        }

        $packages = [];
        foreach ($data['versions'] as $index => $packageData) {
            if (!is_array($packageData)) {
                throw new RepositoryManifestException(sprintf('Version package at index %d must be an object.', $index));
            }

            $package = self::packageFromArray($packageData, $index);
            if (isset($packages[$package->version])) {
                throw new RepositoryManifestException(sprintf('Duplicate version "%s" in repository manifest.', $package->version));
            }

            $packages[$package->version] = $package;
        }

        uksort($packages, 'version_compare');

        $lexicalPrimitives = $data['lexicalPrimitives'] ?? ['code-unit'];
        if (!is_array($lexicalPrimitives) || array_values(array_filter($lexicalPrimitives, 'is_string')) !== array_values($lexicalPrimitives)) {
            throw new RepositoryManifestException('Repository manifest lexicalPrimitives must be a list of strings.');
        }

        return new self($repositoryRoot, $packages, array_values($lexicalPrimitives));
    }

    /**
     * @return list<string>
     */
    public function versions(): array
    {
        return array_keys($this->packages);
    }

    /**
     * @return list<VersionPackage>
     */
    public function packages(): array
    {
        return array_values($this->packages);
    }

    public function package(string $version): VersionPackage
    {
        return $this->packages[$version]
            ?? throw new RepositoryManifestException(sprintf('Unsupported grammar version "%s".', $version));
    }

    /**
     * @return list<string>
     */
    public function lexicalPrimitives(): array
    {
        return $this->lexicalPrimitives;
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->repositoryRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function packageFromArray(array $data, int $index): VersionPackage
    {
        foreach (['version', 'rootProduction', 'grammar', 'documentation', 'conformanceFixtures', 'lexer'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || $data[$field] === '') {
                throw new RepositoryManifestException(sprintf('Version package at index %d is missing string field "%s".', $index, $field));
            }
        }

        if (preg_match('/^\d+\.\d+$/', $data['version']) !== 1) {
            throw new RepositoryManifestException(sprintf('Version "%s" must use major.minor format.', $data['version']));
        }

        return new VersionPackage(
            version: $data['version'],
            rootProduction: $data['rootProduction'],
            grammarPath: $data['grammar'],
            documentationPath: $data['documentation'],
            conformanceFixturePath: $data['conformanceFixtures'],
            lexerVersion: $data['lexer'],
        );
    }
}
