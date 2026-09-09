<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

final readonly class VersionBoundaryCase
{
    public function __construct(
        public string $name,
        public string $firstSupportedVersion,
        public ?string $lastSupportedVersion,
        public string $source,
        public ?string $reference = null,
    ) {
    }

    public function shouldAccept(string $version): bool
    {
        if (version_compare($version, $this->firstSupportedVersion, '<')) {
            return false;
        }

        return $this->lastSupportedVersion === null
            || version_compare($version, $this->lastSupportedVersion, '<=');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $baseDirectory): self
    {
        foreach (['name', 'firstSupportedVersion'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || $data[$field] === '') {
                throw new ConformanceException(sprintf('Boundary fixture is missing string field "%s".', $field));
            }
        }

        if (isset($data['source']) && is_string($data['source'])) {
            $source = $data['source'];
        } elseif (isset($data['fixture']) && is_string($data['fixture'])) {
            $path = $baseDirectory . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $data['fixture']);
            if (!is_file($path)) {
                throw new ConformanceException(sprintf('Boundary fixture source file does not exist: %s', $path));
            }

            $source = file_get_contents($path);
            if (!is_string($source)) {
                throw new ConformanceException(sprintf('Unable to read boundary fixture source file: %s', $path));
            }
        } else {
            throw new ConformanceException('Boundary fixture must define either source or fixture.');
        }

        $lastSupportedVersion = $data['lastSupportedVersion'] ?? null;
        if ($lastSupportedVersion !== null && !is_string($lastSupportedVersion)) {
            throw new ConformanceException('Boundary fixture lastSupportedVersion must be a string when present.');
        }

        $reference = $data['reference'] ?? null;
        if ($reference !== null && !is_string($reference)) {
            throw new ConformanceException('Boundary fixture reference must be a string when present.');
        }

        return new self($data['name'], $data['firstSupportedVersion'], $lastSupportedVersion, $source, $reference);
    }
}
