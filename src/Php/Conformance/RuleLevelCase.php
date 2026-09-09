<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

final readonly class RuleLevelCase
{
    public function __construct(
        public string $rule,
        public string $source,
    ) {
    }
}
