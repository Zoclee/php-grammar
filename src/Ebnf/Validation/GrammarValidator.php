<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Validation;

use PhpGrammar\Ebnf\Grammar;
use PhpGrammar\Ebnf\Production;

final readonly class GrammarValidator
{
    private const PRODUCTION_NAME_PATTERN = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/';

    /**
     * @param non-empty-list<string> $reachabilityRoots
     * @param list<string> $allowedEmptyProductions
     */
    public function __construct(
        private string $requiredRoot = 'source-file',
        private array $reachabilityRoots = ['source-file', 'whitespace', 'comment'],
        private array $allowedEmptyProductions = [],
    ) {
    }

    public function validate(Grammar $grammar): ValidationResult
    {
        $errors = [];
        $productions = $grammar->productions();
        $productionMap = $grammar->productionMap();
        $counts = [];

        foreach ($productions as $production) {
            $counts[$production->name] = ($counts[$production->name] ?? 0) + 1;

            if (preg_match(self::PRODUCTION_NAME_PATTERN, $production->name) !== 1) {
                $errors[] = new ValidationError(
                    'invalid-production-name',
                    sprintf('Production "%s" does not follow repository naming conventions.', $production->name),
                );
            }

            if ($production->expression->allowsEmpty() && !in_array($production->name, $this->allowedEmptyProductions, true)) {
                $errors[] = new ValidationError(
                    'empty-production',
                    sprintf('Production "%s" can match an empty sequence.', $production->name),
                );
            }
        }

        foreach ($counts as $name => $count) {
            if ($count > 1) {
                $errors[] = new ValidationError(
                    'duplicate-production',
                    sprintf('Production "%s" is defined %d times.', $name, $count),
                );
            }
        }

        if (!array_key_exists($this->requiredRoot, $productionMap)) {
            $errors[] = new ValidationError(
                'missing-root-production',
                sprintf('Required root production "%s" is missing.', $this->requiredRoot),
            );
        }

        foreach ($productions as $production) {
            foreach ($production->references() as $reference) {
                if (!array_key_exists($reference, $productionMap)) {
                    $errors[] = new ValidationError(
                        'undefined-production',
                        sprintf('Production "%s" references undefined production "%s".', $production->name, $reference),
                    );
                }
            }
        }

        foreach ($this->unreachableProductions($productionMap) as $name) {
            $errors[] = new ValidationError(
                'unreachable-production',
                sprintf('Production "%s" is not reachable from the configured root production set.', $name),
            );
        }

        return new ValidationResult($errors);
    }

    /**
     * @param array<string, Production> $productionMap
     * @return list<string>
     */
    private function unreachableProductions(array $productionMap): array
    {
        $reachable = [];
        $queue = array_values(array_filter(
            $this->reachabilityRoots,
            static fn (string $root): bool => array_key_exists($root, $productionMap),
        ));

        while ($queue !== []) {
            $name = array_shift($queue);
            if (isset($reachable[$name])) {
                continue;
            }

            $reachable[$name] = true;
            foreach ($productionMap[$name]->references() as $reference) {
                if (array_key_exists($reference, $productionMap) && !isset($reachable[$reference])) {
                    $queue[] = $reference;
                }
            }
        }

        return array_values(array_diff(array_keys($productionMap), array_keys($reachable)));
    }
}
