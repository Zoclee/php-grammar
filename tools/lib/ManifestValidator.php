<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;

/** Evaluates the schema vocabulary used by the package manifest, failing closed on additions. */
final class ManifestValidator
{
    private array $schema;

    public function __construct()
    {
        $this->schema = Support::read('schema/php-grammar.schema.json');
        $this->checkVocabulary($this->schema);
    }

    private function checkVocabulary(array $schema): void
    {
        foreach ($schema as $key => $value) {
            if (!in_array($key, ['$schema', 'title', 'type', 'required', 'properties', '$defs', '$ref', 'const', 'enum', 'minItems', 'uniqueItems', 'items', 'pattern', 'propertyNames', 'additionalProperties', 'minLength', 'allOf', 'if', 'then'], true)) {
                throw new \RuntimeException('Unsupported manifest schema keyword: ' . $key);
            }
            if (in_array($key, ['properties', '$defs'], true)) {
                foreach ($value as $child) {
                    $this->checkVocabulary($child);
                }
            } elseif ($key === 'allOf') {
                foreach ($value as $child) {
                    $this->checkVocabulary($child);
                }
            } elseif (in_array($key, ['items', 'propertyNames', 'additionalProperties', 'if', 'then'], true) && is_array($value)) {
                $this->checkVocabulary($value);
            }
        }
    }

    public function validate(mixed $manifest, string $root = Support::ROOT): array
    {
        $errors = $this->evaluate($this->schema, $manifest, '$');
        usort($errors, static fn(array $a, array $b): int => strcmp($a[0], $b[0]));
        if ($errors) {
            return array_map(static fn(array $e): string => $e[0] . ': ' . $e[1], $errors);
        }
        $versions = [];
        foreach ($manifest->versions as $i => $package) {
            if (isset($versions[$package->version])) {
                $errors[] = '$.versions[' . $i . '].version: duplicate version ' . $package->version;
            }
            $versions[$package->version] = true;
            $paths = [];
            foreach (['grammar', 'documentation', 'conformanceFixtures'] as $field) {
                $paths[$field] = $package->$field;
            }
            foreach ($package->metadata ?? new \stdClass() as $field => $value) {
                $paths['metadata.' . $field] = $value;
            }
            foreach ($paths as $field => $relative) {
                $path = realpath($root . '/' . $relative);
                $base = realpath($root);
                $prefix = '$.versions[' . $i . '].' . $field;
                if ($path === false || $base === false || !self::inside($path, $base)) {
                    $errors[] = $prefix . ': missing or outside package: ' . $relative;
                } elseif ($field !== 'conformanceFixtures' && !is_file($path)) {
                    $errors[] = $prefix . ': expected a file: ' . $relative;
                } elseif ($field === 'conformanceFixtures' && !is_dir($path)) {
                    $errors[] = $prefix . ': expected a directory: ' . $relative;
                }
            }
        }
        $primitives = $manifest->lexicalPrimitives ?? ['code-unit'];
        $definitions = array_keys((array) ($manifest->lexicalPrimitiveDefinitions ?? new \stdClass()));
        if (array_diff($definitions, $primitives) || (property_exists($manifest, 'schemaVersion') && array_diff($primitives, $definitions))) {
            $errors[] = '$.lexicalPrimitiveDefinitions: definitions must correspond to declared primitives';
        }
        return $errors;
    }

    public static function inside(string $path, string $root): bool
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $root = strtolower($root);
        }
        return $path === $root || str_starts_with($path, $root . '/');
    }

    private function evaluate(array $schema, mixed $value, string $path): array
    {
        $errors = [];
        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];
            if (!str_starts_with($ref, '#/$defs/') || !isset($this->schema['$defs'][substr($ref, 8)])) {
                throw new \RuntimeException('Unsupported schema reference: ' . $ref);
            }
            array_push($errors, ...$this->evaluate($this->schema['$defs'][substr($ref, 8)], $value, $path));
        }
        if (isset($schema['type'])) {
            $valid = match ($schema['type']) {
                'object' => $value instanceof \stdClass,
                'array' => is_array($value),
                'string' => is_string($value),
                'boolean' => is_bool($value),
                default => throw new \RuntimeException('Unsupported schema type: ' . $schema['type']),
            };
            if (!$valid) {
                $errors[] = [$path, 'expected ' . $schema['type']];
            }
        }
        if (array_key_exists('const', $schema) && $value !== $schema['const']) {
            $errors[] = [$path, 'expected constant ' . Support::repr($schema['const'])];
        }
        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) {
            $errors[] = [$path, 'value is not an allowed enum member'];
        }
        if (is_string($value)) {
            if (isset($schema['pattern']) && !preg_match('~' . str_replace('~', '\\~', $schema['pattern']) . '~u', $value)) {
                $errors[] = [$path, 'does not match ' . Support::repr($schema['pattern'])];
            }
            if (isset($schema['minLength']) && preg_match_all('/./us', $value) < $schema['minLength']) {
                $errors[] = [$path, 'string is too short'];
            }
        }
        if (is_array($value)) {
            if (isset($schema['minItems']) && count($value) < $schema['minItems']) {
                $errors[] = [$path, 'array is too short'];
            }
            if (($schema['uniqueItems'] ?? false) && count(array_unique(array_map('serialize', $value))) !== count($value)) {
                $errors[] = [$path, 'array items are not unique'];
            }
            if (isset($schema['items'])) {
                foreach ($value as $i => $item) {
                    array_push($errors, ...$this->evaluate($schema['items'], $item, $path . '[' . $i . ']'));
                }
            }
        }
        if ($value instanceof \stdClass) {
            foreach ($schema['required'] ?? [] as $field) {
                if (!property_exists($value, $field)) {
                    $errors[] = [$path, Support::repr($field) . ' is a required property'];
                }
            }
            foreach ($value as $field => $item) {
                if (isset($schema['propertyNames'])) {
                    array_push($errors, ...$this->evaluate($schema['propertyNames'], $field, $path));
                }
                $child = $schema['properties'][$field] ?? $schema['additionalProperties'] ?? null;
                if (is_array($child)) {
                    $childPath = $path . (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field) ? '.' . $field : '[' . Support::repr($field) . ']');
                    array_push($errors, ...$this->evaluate($child, $item, $childPath));
                }
            }
        }
        foreach ($schema['allOf'] ?? [] as $child) {
            array_push($errors, ...$this->evaluate($child, $value, $path));
        }
        if (isset($schema['if'], $schema['then']) && !$this->evaluate($schema['if'], $value, $path)) {
            array_push($errors, ...$this->evaluate($schema['then'], $value, $path));
        }
        return $errors;
    }
}
