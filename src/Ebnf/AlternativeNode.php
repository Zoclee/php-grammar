<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class AlternativeNode implements Node
{
    /**
     * @param non-empty-list<Node> $alternatives
     */
    public function __construct(
        public array $alternatives,
    ) {
    }

    public function references(): array
    {
        return array_values(array_unique(array_merge(...array_map(
            static fn (Node $node): array => $node->references(),
            $this->alternatives,
        ))));
    }

    public function allowsEmpty(): bool
    {
        foreach ($this->alternatives as $alternative) {
            if ($alternative->allowsEmpty()) {
                return true;
            }
        }

        return false;
    }
}
