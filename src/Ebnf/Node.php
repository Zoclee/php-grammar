<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

interface Node
{
    /**
     * @return list<string>
     */
    public function references(): array;

    public function allowsEmpty(): bool;
}
