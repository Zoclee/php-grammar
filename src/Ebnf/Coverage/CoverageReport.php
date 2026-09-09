<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Coverage;

final readonly class CoverageReport
{
    public function __construct(
        public CoverageIdentityMap $identityMap,
        public CoverageCollector $attempted,
        public CoverageCollector $matched,
    ) {
    }

    public function totalProductions(): int
    {
        return count($this->identityMap->productions());
    }

    public function attemptedProductions(): int
    {
        return count($this->attempted->enteredProductions());
    }

    public function exercisedProductions(): int
    {
        return count($this->matched->matchedProductions());
    }

    /**
     * @return list<string>
     */
    public function unexercisedProductions(): array
    {
        return $this->difference($this->identityMap->productions(), $this->matched->matchedProductions());
    }

    public function productionCoveragePercentage(): float
    {
        return $this->percentage($this->exercisedProductions(), $this->totalProductions());
    }

    public function totalAlternatives(): int
    {
        return count($this->identityMap->alternatives());
    }

    public function attemptedAlternatives(): int
    {
        return count($this->attempted->visitedAlternatives());
    }

    public function exercisedAlternatives(): int
    {
        return count($this->matched->matchedAlternatives());
    }

    /**
     * @return list<string>
     */
    public function unexercisedAlternatives(): array
    {
        return $this->difference($this->identityMap->alternatives(), $this->matched->matchedAlternatives());
    }

    public function alternativeCoveragePercentage(): float
    {
        return $this->percentage($this->exercisedAlternatives(), $this->totalAlternatives());
    }

    /**
     * @param list<string> $all
     * @param list<string> $covered
     * @return list<string>
     */
    private function difference(array $all, array $covered): array
    {
        $difference = array_values(array_diff($all, $covered));
        sort($difference, SORT_STRING);

        return $difference;
    }

    private function percentage(int $covered, int $total): float
    {
        return $total === 0 ? 100.0 : ($covered / $total) * 100;
    }
}
