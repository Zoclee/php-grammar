<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class SequenceNode implements Node
{
    /**
     * @param non-empty-list<Node> $elements
     */
    public function __construct(
        public array $elements,
    ) {
    }

    public function references(): array
    {
        return array_values(array_unique(array_merge(...array_map(
            static fn (Node $node): array => $node->references(),
            $this->elements,
        ))));
    }

    public function allowsEmpty(): bool
    {
        foreach ($this->elements as $element) {
            if (!$element->allowsEmpty()) {
                return false;
            }
        }

        return true;
    }
}
