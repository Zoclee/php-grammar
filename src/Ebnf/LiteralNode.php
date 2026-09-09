<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class LiteralNode implements Node
{
    public function __construct(
        public string $value,
    ) {
    }

    public function references(): array
    {
        return [];
    }

    public function allowsEmpty(): bool
    {
        return $this->value === '';
    }
}
