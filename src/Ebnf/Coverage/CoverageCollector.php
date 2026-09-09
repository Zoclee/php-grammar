<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Coverage;

final class CoverageCollector
{
    /** @var array<string, true> */
    private array $enteredProductions = [];

    /** @var array<string, true> */
    private array $matchedProductions = [];

    /** @var array<string, true> */
    private array $visitedAlternatives = [];

    /** @var array<string, true> */
    private array $matchedAlternatives = [];

    /** @var array<string, true> */
    private array $optionalTaken = [];

    /** @var array<string, true> */
    private array $optionalSkipped = [];

    /** @var array<string, true> */
    private array $repetitionEntered = [];

    /** @var array<string, true> */
    private array $repetitionExercised = [];

    /** @var array<string, true> */
    private array $matchedPrimitives = [];

    public function recordProductionEntered(string $id): void
    {
        $this->enteredProductions[$id] = true;
    }

    public function recordProductionMatched(string $id): void
    {
        $this->matchedProductions[$id] = true;
    }

    public function recordAlternativeVisited(string $id): void
    {
        $this->visitedAlternatives[$id] = true;
    }

    public function recordAlternativeMatched(string $id): void
    {
        $this->matchedAlternatives[$id] = true;
    }

    public function recordOptionalTaken(string $id): void
    {
        $this->optionalTaken[$id] = true;
    }

    public function recordOptionalSkipped(string $id): void
    {
        $this->optionalSkipped[$id] = true;
    }

    public function recordRepetitionEntered(string $id): void
    {
        $this->repetitionEntered[$id] = true;
    }

    public function recordRepetitionExercised(string $id): void
    {
        $this->repetitionExercised[$id] = true;
    }

    public function recordPrimitiveMatched(string $id): void
    {
        $this->matchedPrimitives[$id] = true;
    }

    /**
     * @return list<string>
     */
    public function enteredProductions(): array
    {
        return $this->keys($this->enteredProductions);
    }

    /**
     * @return list<string>
     */
    public function matchedProductions(): array
    {
        return $this->keys($this->matchedProductions);
    }

    /**
     * @return list<string>
     */
    public function visitedAlternatives(): array
    {
        return $this->keys($this->visitedAlternatives);
    }

    /**
     * @return list<string>
     */
    public function matchedAlternatives(): array
    {
        return $this->keys($this->matchedAlternatives);
    }

    /**
     * @return list<string>
     */
    public function optionalTaken(): array
    {
        return $this->keys($this->optionalTaken);
    }

    /**
     * @return list<string>
     */
    public function optionalSkipped(): array
    {
        return $this->keys($this->optionalSkipped);
    }

    /**
     * @return list<string>
     */
    public function repetitionEntered(): array
    {
        return $this->keys($this->repetitionEntered);
    }

    /**
     * @return list<string>
     */
    public function repetitionExercised(): array
    {
        return $this->keys($this->repetitionExercised);
    }

    /**
     * @return list<string>
     */
    public function matchedPrimitives(): array
    {
        return $this->keys($this->matchedPrimitives);
    }

    public function merge(self $other): void
    {
        foreach ($other->enteredProductions() as $id) {
            $this->recordProductionEntered($id);
        }
        foreach ($other->matchedProductions() as $id) {
            $this->recordProductionMatched($id);
        }
        foreach ($other->visitedAlternatives() as $id) {
            $this->recordAlternativeVisited($id);
        }
        foreach ($other->matchedAlternatives() as $id) {
            $this->recordAlternativeMatched($id);
        }
        foreach ($other->optionalTaken() as $id) {
            $this->recordOptionalTaken($id);
        }
        foreach ($other->optionalSkipped() as $id) {
            $this->recordOptionalSkipped($id);
        }
        foreach ($other->repetitionEntered() as $id) {
            $this->recordRepetitionEntered($id);
        }
        foreach ($other->repetitionExercised() as $id) {
            $this->recordRepetitionExercised($id);
        }
        foreach ($other->matchedPrimitives() as $id) {
            $this->recordPrimitiveMatched($id);
        }
    }

    /**
     * @param array<string, true> $values
     * @return list<string>
     */
    private function keys(array $values): array
    {
        $keys = array_keys($values);
        sort($keys, SORT_STRING);

        return $keys;
    }
}
