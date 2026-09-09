<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Coverage;

use PhpGrammar\Ebnf\AlternativeNode;
use PhpGrammar\Ebnf\Grammar;
use PhpGrammar\Ebnf\GroupNode;
use PhpGrammar\Ebnf\Node;
use PhpGrammar\Ebnf\OptionalNode;
use PhpGrammar\Ebnf\Production;
use PhpGrammar\Ebnf\RepetitionNode;
use PhpGrammar\Ebnf\SequenceNode;

final class CoverageIdentityMap
{
    /** @var array<int, list<string>> */
    private array $alternativeIds = [];

    /** @var array<int, string> */
    private array $optionalIds = [];

    /** @var array<int, string> */
    private array $repetitionIds = [];

    /** @var list<string> */
    private array $productions = [];

    /** @var list<string> */
    private array $alternatives = [];

    /** @var list<string> */
    private array $optionals = [];

    /** @var list<string> */
    private array $repetitions = [];

    public static function fromGrammar(Grammar $grammar): self
    {
        $map = new self();

        foreach ($grammar->productions() as $production) {
            $map->indexProduction($production);
        }

        return $map;
    }

    public function alternativeId(AlternativeNode $node, int $index): string
    {
        return $this->alternativeIds[spl_object_id($node)][$index]
            ?? throw new \LogicException('Unknown alternative node coverage identity.');
    }

    public function optionalId(OptionalNode $node): string
    {
        return $this->optionalIds[spl_object_id($node)]
            ?? throw new \LogicException('Unknown optional node coverage identity.');
    }

    public function repetitionId(RepetitionNode $node): string
    {
        return $this->repetitionIds[spl_object_id($node)]
            ?? throw new \LogicException('Unknown repetition node coverage identity.');
    }

    /**
     * @return list<string>
     */
    public function productions(): array
    {
        return $this->productions;
    }

    /**
     * @return list<string>
     */
    public function alternatives(): array
    {
        return $this->alternatives;
    }

    /**
     * @return list<string>
     */
    public function optionals(): array
    {
        return $this->optionals;
    }

    /**
     * @return list<string>
     */
    public function repetitions(): array
    {
        return $this->repetitions;
    }

    private function indexProduction(Production $production): void
    {
        $this->productions[] = $production->name;
        $counters = [
            'alternative' => 0,
            'optional' => 0,
            'repetition' => 0,
        ];

        $this->indexNode($production->expression, $production->name, $counters);
    }

    /**
     * @param array{alternative: int, optional: int, repetition: int} $counters
     */
    private function indexNode(Node $node, string $productionName, array &$counters): void
    {
        if ($node instanceof AlternativeNode) {
            $ids = [];
            foreach ($node->alternatives as $index => $alternative) {
                $id = $productionName . '/alternative:' . (++$counters['alternative']);
                $ids[$index] = $id;
                $this->alternatives[] = $id;
                $this->indexNode($alternative, $productionName, $counters);
            }

            $this->alternativeIds[spl_object_id($node)] = $ids;
            return;
        }

        if ($node instanceof OptionalNode) {
            $id = $productionName . '/optional:' . (++$counters['optional']);
            $this->optionalIds[spl_object_id($node)] = $id;
            $this->optionals[] = $id;
            $this->indexNode($node->expression, $productionName, $counters);
            return;
        }

        if ($node instanceof RepetitionNode) {
            $id = $productionName . '/repetition:' . (++$counters['repetition']);
            $this->repetitionIds[spl_object_id($node)] = $id;
            $this->repetitions[] = $id;
            $this->indexNode($node->expression, $productionName, $counters);
            return;
        }

        if ($node instanceof SequenceNode) {
            foreach ($node->elements as $element) {
                $this->indexNode($element, $productionName, $counters);
            }
            return;
        }

        if ($node instanceof GroupNode) {
            $this->indexNode($node->expression, $productionName, $counters);
        }
    }
}
