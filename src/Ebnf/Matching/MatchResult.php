<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Matching;

final readonly class MatchResult
{
    /**
     * @param list<string> $expected
     */
    public function __construct(
        public bool $matched,
        public string $rule,
        public Input $input,
        public int $furthestOffset,
        public array $expected,
    ) {
    }

    public function consumedInput(): bool
    {
        return $this->matched;
    }
}
