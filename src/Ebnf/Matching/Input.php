<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Matching;

interface Input
{
    public function length(): int;

    public function valueAt(int $offset): mixed;
}
