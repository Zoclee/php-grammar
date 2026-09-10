<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Matching;

use PhpGrammar\Ebnf\{AlternativeNode, Grammar, GroupNode, LiteralNode, Node, OptionalNode, ReferenceNode, RepetitionNode, SequenceNode};
use PhpGrammar\Ebnf\Coverage\{CoverageCollector, CoverageIdentityMap};

/** Earley recognition over token inputs, including indirect left recursion and nullable rules. */
final class ChartMatcher
{
    private array $rules = [];
    private array $byName = [];
    private array $alternatives = [];
    private ?CoverageIdentityMap $identities = null;

    public function __construct(private array $primitives = [], private ?CoverageCollector $coverage = null)
    {
    }

    public function matchesRule(Grammar $grammar, string $root, Input $input): MatchResult
    {
        $this->rules = $this->byName = $this->alternatives = [];
        $this->identities = $this->coverage === null ? null : CoverageIdentityMap::fromGrammar($grammar);
        $this->add('@start', [new ReferenceNode($root)]);
        foreach ($grammar->productions() as $production) {
            $this->expand($production->name, $production->expression);
        }
        $charts = $seen = $waiting = $completed = [];
        $enqueue = static function (int $position, array $item) use (&$charts, &$seen): void {
            $key = implode(':', $item);
            if (!isset($seen[$position][$key])) {
                $seen[$position][$key] = true;
                $charts[$position][] = $item;
            }
        };
        $enqueue(0, [0, 0, 0]);
        $furthest = 0;
        $expected = [];
        for ($position = 0; $position <= $input->length(); $position++) {
            for ($cursor = 0; $cursor < count($charts[$position] ?? []); $cursor++) {
                [$id, $dot, $origin] = $charts[$position][$cursor];
                [$name, $symbols] = $this->rules[$id];
                if ($dot === count($symbols)) {
                    $completed[$position][$name][$origin] = true;
                    if (!str_starts_with($name, '@')) {
                        $this->coverage?->recordProductionMatched($name);
                    }
                    if (isset($this->alternatives[$id])) {
                        $this->coverage?->recordAlternativeMatched($this->alternatives[$id]);
                    }
                    foreach ($waiting[$origin][$name] ?? [] as [$parentId, $parentDot, $parentOrigin]) {
                        $enqueue($position, [$parentId, $parentDot + 1, $parentOrigin]);
                    }
                    continue;
                }
                $symbol = $symbols[$dot];
                if ($symbol instanceof ReferenceNode && !isset($this->primitives[$symbol->name])) {
                    $waiting[$position][$symbol->name][] = [$id, $dot, $origin];
                    if (!str_starts_with($symbol->name, '@')) {
                        $this->coverage?->recordProductionEntered($symbol->name);
                    }
                    foreach ($this->byName[$symbol->name] ?? [] as $child) {
                        if (isset($this->alternatives[$child])) {
                            $this->coverage?->recordAlternativeVisited($this->alternatives[$child]);
                        }
                        $enqueue($position, [$child, 0, $position]);
                    }
                    // A nullable child may have completed before this parent was predicted.
                    if (isset($completed[$position][$symbol->name][$position])) {
                        $enqueue($position, [$id, $dot + 1, $origin]);
                    }
                    continue;
                }
                $ends = [];
                if ($symbol instanceof ReferenceNode) {
                    $ends = ($this->primitives[$symbol->name])($input, $position);
                    if ($ends !== []) {
                        $this->coverage?->recordPrimitiveMatched($symbol->name);
                    }
                } elseif ($symbol instanceof LiteralNode) {
                    if ($symbol->value === '') {
                        $ends = [$position];
                    } elseif ($position < $input->length() && $input->valueAt($position) === $symbol->value) {
                        $ends = [$position + 1];
                    }
                }
                if ($position > $furthest) {
                    $furthest = $position;
                    $expected = [];
                }
                if ($position === $furthest && $ends === []) {
                    $expected[$symbol instanceof ReferenceNode ? $symbol->name : $symbol->value] = true;
                }
                foreach ($ends as $end) {
                    $enqueue($end, [$id, $dot + 1, $origin]);
                }
            }
        }
        $matched = isset($completed[$input->length()]['@start'][0]);
        return new MatchResult($matched, $root, $input, $matched ? $input->length() : $furthest, $matched ? [] : array_keys($expected));
    }

    private function add(string $name, array $symbols): int
    {
        $id = count($this->rules);
        $this->rules[] = [$name, $symbols];
        $this->byName[$name][] = $id;
        return $id;
    }

    private function symbol(Node $node): Node
    {
        if ($node instanceof ReferenceNode || $node instanceof LiteralNode) {
            return $node;
        }
        $name = '@node-' . spl_object_id($node);
        if (!isset($this->byName[$name])) {
            $this->expand($name, $node);
        }
        return new ReferenceNode($name);
    }

    private function expand(string $name, Node $node): void
    {
        if ($node instanceof AlternativeNode) {
            foreach ($node->alternatives as $index => $alternative) {
                $id = $this->add($name, [$this->symbol($alternative)]);
                if ($this->identities !== null) {
                    $this->alternatives[$id] = $this->identities->alternativeId($node, $index);
                }
            }
        } elseif ($node instanceof SequenceNode) {
            $this->add($name, array_map($this->symbol(...), $node->elements));
        } elseif ($node instanceof GroupNode) {
            $this->expand($name, $node->expression);
        } elseif ($node instanceof OptionalNode || $node instanceof RepetitionNode) {
            $this->add($name, []);
            $symbols = [$this->symbol($node->expression)];
            if ($node instanceof RepetitionNode) {
                $symbols[] = new ReferenceNode($name);
            }
            $this->add($name, $symbols);
        } else {
            $this->add($name, [$node]);
        }
    }
}
