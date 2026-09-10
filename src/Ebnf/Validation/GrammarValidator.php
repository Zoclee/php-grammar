<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Validation;

use PhpGrammar\Ebnf\Grammar;
use PhpGrammar\Ebnf\Production;
use PhpGrammar\Ebnf\{AlternativeNode, GroupNode, LiteralNode, Node, OptionalNode, ReferenceNode, RepetitionNode, SequenceNode};

final readonly class GrammarValidator
{
    private const PRODUCTION_NAME_PATTERN = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/';

    /**
     * @param non-empty-list<string> $reachabilityRoots
     * @param list<string> $allowedEmptyProductions
     * @param list<string> $lexicalPrimitives
     */
    public function __construct(
        private string $requiredRoot = 'source-file',
        private array $reachabilityRoots = ['source-file', 'whitespace', 'comment'],
        private array $allowedEmptyProductions = [],
        private array $lexicalPrimitives = ['code-unit', 'non-ascii-code-unit', 'html-code-unit', 'line-comment-code-unit', 'block-comment-code-unit', 'single-quoted-code-unit', 'encapsed-code-unit', 'nowdoc-code-unit'],
    ) {
    }

    public function validate(Grammar $grammar): ValidationResult
    {
        $errors = [];
        $productions = $grammar->productions();
        $productionMap = $grammar->productionMap();
        $counts = [];
        $nullable = [];
        do {
            $previous = $nullable;
            foreach ($productions as $production) {
                if ($this->isNullable($production->expression, $nullable)) {
                    $nullable[$production->name] = true;
                }
            }
        } while ($nullable !== $previous);

        foreach ($productions as $production) {
            $counts[$production->name] = ($counts[$production->name] ?? 0) + 1;

            if (preg_match(self::PRODUCTION_NAME_PATTERN, $production->name) !== 1) {
                $errors[] = new ValidationError(
                    'invalid-production-name',
                    sprintf('Production "%s" does not follow repository naming conventions.', $production->name),
                );
            }

            if (isset($nullable[$production->name]) && !in_array($production->name, $this->allowedEmptyProductions, true)) {
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
                if (!array_key_exists($reference, $productionMap) && !in_array($reference, $this->lexicalPrimitives, true)) {
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

    private function isNullable(Node $node, array $nullable): bool
    {
        if ($node instanceof ReferenceNode) return isset($nullable[$node->name]);
        if ($node instanceof LiteralNode) return $node->value === '';
        if ($node instanceof OptionalNode || $node instanceof RepetitionNode) return true;
        if ($node instanceof GroupNode) return $this->isNullable($node->expression, $nullable);
        if ($node instanceof AlternativeNode) {
            foreach ($node->alternatives as $child) {
                if ($this->isNullable($child, $nullable)) return true;
            }
            return false;
        }
        if ($node instanceof SequenceNode) {
            foreach ($node->elements as $child) {
                if (!$this->isNullable($child, $nullable)) return false;
            }
            return true;
        }
        return false;
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
