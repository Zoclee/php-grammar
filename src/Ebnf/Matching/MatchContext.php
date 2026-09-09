<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Matching;

final class MatchContext
{
    /** @var array<string, list<int>> */
    public array $memo = [];

    /** @var array<string, true> */
    public array $active = [];

    /** @var array<int, array<string, true>> */
    private array $expectedByOffset = [];

    public int $furthestOffset = 0;

    public function __construct(
        public readonly Input $input,
    ) {
    }

    public function recordFailure(int $offset, string $expected): void
    {
        if ($offset > $this->furthestOffset) {
            $this->furthestOffset = $offset;
            $this->expectedByOffset = [];
        }

        if ($offset === $this->furthestOffset) {
            $this->expectedByOffset[$offset][$expected] = true;
        }
    }

    /**
     * @return list<string>
     */
    public function expected(): array
    {
        $expected = array_keys($this->expectedByOffset[$this->furthestOffset] ?? []);
        sort($expected, SORT_STRING);

        return $expected;
    }
}
