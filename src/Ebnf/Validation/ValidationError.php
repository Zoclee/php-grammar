<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Validation;

final readonly class ValidationError
{
    public function __construct(
        public string $code,
        public string $message,
    ) {
    }
}
