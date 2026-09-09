<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class Production
{
    public function __construct(
        public string $name,
        public Node $expression,
        public int $line,
    ) {
    }

    /**
     * @return list<string>
     */
    public function references(): array
    {
        return $this->expression->references();
    }
}
