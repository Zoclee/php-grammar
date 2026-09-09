<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Validation;

final readonly class ValidationResult
{
    /**
     * @param list<ValidationError> $errors
     */
    public function __construct(
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_map(static fn (ValidationError $error): string => $error->code, $this->errors);
    }
}
