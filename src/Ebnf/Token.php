<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final readonly class Token
{
    public function __construct(
        public string $type,
        public string $value,
        public int $line,
        public int $column,
    ) {
    }
}
