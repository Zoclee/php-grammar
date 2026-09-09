<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class ReferenceNode implements Node
{
    public function __construct(
        public string $name,
    ) {
    }

    public function references(): array
    {
        return [$this->name];
    }

    public function allowsEmpty(): bool
    {
        return false;
    }
}
