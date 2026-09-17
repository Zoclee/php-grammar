<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final class Grammar
{
    /** @var list<Production> */
    private array $productions;

    /**
     * @param list<Production> $productions
     */
    public function __construct(array $productions)
    {
        $this->productions = $productions;
    }

    /**
     * @return list<Production>
     */
    public function productions(): array
    {
        return $this->productions;
    }

    /**
     * @return array<string, Production>
     */
    public function productionMap(): array
    {
        $map = [];
        foreach ($this->productions as $production) {
            $map[$production->name] ??= $production;
        }

        return $map;
    }

    public function hasProduction(string $name): bool
    {
        return array_key_exists($name, $this->productionMap());
    }

    public function production(string $name): Production
    {
        return $this->productionMap()[$name]
            ?? throw new \OutOfBoundsException(sprintf('Unknown production "%s".', $name));
    }

    /** @return list<string> Names in canonical source order. */
    public function productionNames(): array
    {
        return array_keys($this->productionMap());
    }

    /** @return list<string> Direct users in source order, including self references. */
    public function referencesTo(string $name): array
    {
        $references = [];
        foreach ($this->productions as $production) {
            if (in_array($name, $production->references(), true)) {
                $references[] = $production->name;
            }
        }
        return array_values(array_unique($references));
    }
}
