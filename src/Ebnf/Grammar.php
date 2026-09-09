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
}
