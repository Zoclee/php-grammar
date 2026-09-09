<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Matching;

final readonly class StringInput implements Input
{
    public function __construct(
        public string $value,
    ) {
    }

    public function length(): int
    {
        return strlen($this->value);
    }

    public function valueAt(int $offset): string
    {
        if ($offset < 0 || $offset >= $this->length()) {
            throw new \OutOfBoundsException(sprintf('Input offset %d is outside the string input.', $offset));
        }

        return $this->value[$offset];
    }
}
