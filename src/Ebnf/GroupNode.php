<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class GroupNode implements Node
{
    public function __construct(
        public Node $expression,
    ) {
    }

    public function references(): array
    {
        return $this->expression->references();
    }

    public function allowsEmpty(): bool
    {
        return $this->expression->allowsEmpty();
    }
}
