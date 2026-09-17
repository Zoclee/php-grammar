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
        private readonly array $lexicalPrimitiveDefinitions = [],
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
        if (isset($data['schemaVersion']) && $data['schemaVersion'] !== '1.0') {
            throw new RepositoryManifestException('schemaVersion: supported manifest schema is "1.0".');
        }
        if (!isset($data['versions']) || !is_array($data['versions']) || !array_is_list($data['versions']) || $data['versions'] === []) {
            throw new RepositoryManifestException('Repository manifest must define at least one version.');
        }

        $packages = [];
        foreach ($data['versions'] as $index => $packageData) {
            if (!is_array($packageData)) {
                throw new RepositoryManifestException(sprintf('Version package at index %d must be an object.', $index));
            }

            if (isset($data['schemaVersion'])) {
                foreach (['status', 'sourceProfile', 'phpSource'] as $field) {
                    if (!isset($packageData[$field])) {
                        throw new RepositoryManifestException("versions.$index.$field: required by schemaVersion 1.0.");
                    }
                }
            }

            $package = self::packageFromArray($packageData, $index);
            if (isset($packages[$package->version])) {
                throw new RepositoryManifestException(sprintf('Duplicate version "%s" in repository manifest.', $package->version));
            }

            $packages[$package->version] = $package;
        }

        uksort($packages, 'version_compare');

        $lexicalPrimitives = $data['lexicalPrimitives'] ?? ['code-unit'];
        if (!is_array($lexicalPrimitives) || !array_is_list($lexicalPrimitives) || array_values(array_filter($lexicalPrimitives, 'is_string')) !== array_values($lexicalPrimitives)) {
            throw new RepositoryManifestException('Repository manifest lexicalPrimitives must be a list of strings.');
        }
        if (count(array_unique($lexicalPrimitives)) !== count($lexicalPrimitives)) {
            throw new RepositoryManifestException('lexicalPrimitives: names must be unique.');
        }

        $definitions = $data['lexicalPrimitiveDefinitions'] ?? [];
        if (!is_array($definitions)) {
            throw new RepositoryManifestException('lexicalPrimitiveDefinitions: expected an object.');
        }
        foreach ($definitions as $name => $definition) {
            if (!in_array($name, $lexicalPrimitives, true) || !is_array($definition)
                || !is_string($definition['meaning'] ?? null)
                || !is_string($definition['scannerContext'] ?? null)
                || !is_bool($definition['independentMatcher'] ?? null)) {
                throw new RepositoryManifestException(sprintf('lexicalPrimitiveDefinitions.%s: expected a declared primitive with meaning, scannerContext and independentMatcher.', $name));
            }
        }
        if (isset($data['schemaVersion']) && (!isset($data['lexicalPrimitives'], $data['lexicalPrimitiveDefinitions'])
            || array_diff($lexicalPrimitives, array_keys($definitions)) !== [])) {
            throw new RepositoryManifestException('lexicalPrimitiveDefinitions: schemaVersion 1.0 requires definitions for every declared primitive.');
        }
        return new self($repositoryRoot, $packages, array_values($lexicalPrimitives), $definitions);
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
            ?? throw new RepositoryManifestException(sprintf(
                'Unsupported grammar version "%s". Available versions: %s. Use versions() to discover packages.',
                $version, implode(', ', $this->versions()),
            ));
    }

    public function hasVersion(string $version): bool
    {
        return isset($this->packages[$version]);
    }

    /** Highest numeric version, including draft/historical packages. Pin a version for reproducibility. */
    public function latestVersion(): string
    {
        return array_key_last($this->packages);
    }

    /**
     * @return list<string>
     */
    public function lexicalPrimitives(): array
    {
        return $this->lexicalPrimitives;
    }

    /** Null means a legacy manifest declares the primitive without descriptive metadata. */
    public function lexicalPrimitive(string $name): ?array
    {
        if (!in_array($name, $this->lexicalPrimitives, true)) {
            throw new RepositoryManifestException(sprintf('Unknown lexical primitive "%s".', $name));
        }
        return $this->lexicalPrimitiveDefinitions[$name] ?? null;
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

        foreach (['grammar', 'documentation', 'conformanceFixtures'] as $field) {
            self::validatePath($data[$field], "versions.$index.$field");
        }
        if (isset($data['status']) && !in_array($data['status'], ['draft', 'candidate', 'stable', 'historical', 'deprecated'], true)) {
            throw new RepositoryManifestException("versions.$index.status: unknown status.");
        }
        if (isset($data['sourceProfile']) && !is_string($data['sourceProfile'])) {
            throw new RepositoryManifestException("versions.$index.sourceProfile: expected a string.");
        }
        if (isset($data['phpSource']) && (!is_array($data['phpSource'])
            || !is_string($data['phpSource']['branch'] ?? null)
            || !is_string($data['phpSource']['commit'] ?? null)
            || preg_match('/^[0-9a-f]{40}$/D', $data['phpSource']['commit']) !== 1)) {
            throw new RepositoryManifestException("versions.$index.phpSource: expected branch and 40 lowercase hexadecimal commit characters.");
        }
        if (isset($data['metadata']) && !is_array($data['metadata'])) {
            throw new RepositoryManifestException("versions.$index.metadata: expected an object of paths.");
        }
        foreach ($data['metadata'] ?? [] as $name => $path) {
            self::validatePath($path, "versions.$index.metadata.$name");
        }

        return new VersionPackage(
            version: $data['version'],
            rootProduction: $data['rootProduction'],
            grammarPath: $data['grammar'],
            documentationPath: $data['documentation'],
            conformanceFixturePath: $data['conformanceFixtures'],
            lexerVersion: $data['lexer'],
            status: $data['status'] ?? 'unspecified',
            sourceProfile: $data['sourceProfile'] ?? null,
            phpSource: $data['phpSource'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    private static function validatePath(mixed $path, string $field): void
    {
        if (!is_string($path) || preg_match('~^(?!/)(?!.*(?:^|/)\.{1,2}(?:/|$))[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*$~D', $path) !== 1) {
            throw new RepositoryManifestException($field . ': expected a relative, forward-slash package path without dot segments.');
        }
    }
}
