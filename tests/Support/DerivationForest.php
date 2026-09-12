<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Support;

use PhpGrammar\Ebnf\{AlternativeNode, Grammar, GroupNode, LiteralNode, Node, OptionalNode, ReferenceNode, RepetitionNode, SequenceNode};

/** Test-only Earley forest: retain all derivations, unlike the recognition matcher. */
final class DerivationForest
{
    private array $rules = [];
    private array $byName = [];
    private array $completed = [];
    private array $tokens;
    private array $memo = [];
    private array $primitives = ['variable' => T_VARIABLE, 'identifier' => T_STRING,
        'decimal-integer-literal' => T_LNUMBER];

    public function __construct(Grammar $grammar, string $source)
    {
        $this->tokens = self::tokens($source);
        foreach ($grammar->productions() as $production) {
            $this->expand($production->name, $production->expression);
        }
    }

    /** Normalize Zend's composite yield-from token to the EBNF adapter's two terminals. */
    public static function tokens(string $source): array
    {
        $tokens = [];
        foreach (token_get_all('<?php ' . $source) as $token) {
            if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
            if (is_array($token) && $token[0] === T_YIELD_FROM) {
                $tokens[] = [T_YIELD, 'yield'];
                $tokens[] = [T_STRING, 'from'];
            } else {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    /** @return list<array> At most three trees; two already disprove uniqueness. */
    public function trees(string $root): array
    {
        $start = $this->add('@start', [new ReferenceNode($root)]);
        $charts = $seen = $waiting = [];
        $enqueue = static function (int $position, array $item) use (&$charts, &$seen): void {
            $key = implode(':', $item);
            if (!isset($seen[$position][$key])) {
                $seen[$position][$key] = true;
                $charts[$position][] = $item;
            }
        };
        $enqueue(0, [$start, 0, 0]);
        for ($position = 0; $position <= count($this->tokens); $position++) {
            for ($cursor = 0; $cursor < count($charts[$position] ?? []); $cursor++) {
                [$id, $dot, $origin] = $charts[$position][$cursor];
                [$name, $symbols] = $this->rules[$id];
                if ($dot === count($symbols)) {
                    $this->completed[$name][$origin][$position][$id] = true;
                    foreach ($waiting[$origin][$name] ?? [] as [$pid, $pdot, $porigin]) {
                        $enqueue($position, [$pid, $pdot + 1, $porigin]);
                    }
                    continue;
                }
                $symbol = $symbols[$dot];
                if ($symbol instanceof ReferenceNode && !isset($this->primitives[$symbol->name])) {
                    $waiting[$position][$symbol->name][] = [$id, $dot, $origin];
                    foreach ($this->byName[$symbol->name] ?? [] as $child) $enqueue($position, [$child, 0, $position]);
                    if (isset($this->completed[$symbol->name][$position][$position])) {
                        $enqueue($position, [$id, $dot + 1, $origin]);
                    }
                } elseif ($this->terminal($symbol, $position)) {
                    $enqueue($position + 1, [$id, $dot + 1, $origin]);
                }
            }
        }
        return $this->derive($root, 0, count($this->tokens));
    }

    private function terminal(Node $symbol, int $position): bool
    {
        $token = $this->tokens[$position] ?? null;
        if ($token === null) return false;
        if ($symbol instanceof ReferenceNode) return is_array($token) && $token[0] === ($this->primitives[$symbol->name] ?? null);
        return $symbol instanceof LiteralNode && $symbol->value === (is_array($token) ? $token[1] : $token);
    }

    private function derive(string $name, int $start, int $end): array
    {
        $key = "$name:$start:$end";
        if (isset($this->memo[$key])) return $this->memo[$key];
        $trees = [];
        foreach (array_keys($this->completed[$name][$start][$end] ?? []) as $id) {
            foreach ($this->sequence($this->rules[$id][1], 0, $start, $end) as $children) {
                $trees[] = ['name' => $name, 'start' => $start, 'end' => $end, 'children' => $children];
                if (count($trees) === 3) break 2;
            }
        }
        return $this->memo[$key] = $trees;
    }

    private function sequence(array $symbols, int $dot, int $start, int $end): array
    {
        if ($dot === count($symbols)) return $start === $end ? [[]] : [];
        $symbol = $symbols[$dot];
        $results = [];
        if ($symbol instanceof ReferenceNode && !isset($this->primitives[$symbol->name])) {
            foreach (array_keys($this->completed[$symbol->name][$start] ?? []) as $mid) {
                if ($mid > $end) continue;
                foreach ($this->sequence($symbols, $dot + 1, $mid, $end) as $tail) {
                    foreach ($this->derive($symbol->name, $start, $mid) as $tree) {
                        $results[] = [$tree, ...$tail];
                        if (count($results) === 3) return $results;
                    }
                }
            }
        } elseif ($start < $end && $this->terminal($symbol, $start)) {
            return $this->sequence($symbols, $dot + 1, $start + 1, $end);
        }
        return $results;
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
        if ($node instanceof ReferenceNode || $node instanceof LiteralNode) return $node;
        $name = '@node-' . spl_object_id($node);
        if (!isset($this->byName[$name])) $this->expand($name, $node);
        return new ReferenceNode($name);
    }

    private function expand(string $name, Node $node): void
    {
        if ($node instanceof AlternativeNode) {
            foreach ($node->alternatives as $alternative) $this->add($name, [$this->symbol($alternative)]);
        } elseif ($node instanceof SequenceNode) {
            $this->add($name, array_map($this->symbol(...), $node->elements));
        } elseif ($node instanceof GroupNode) {
            $this->expand($name, $node->expression);
        } elseif ($node instanceof OptionalNode || $node instanceof RepetitionNode) {
            $this->add($name, []);
            $symbols = [$this->symbol($node->expression)];
            if ($node instanceof RepetitionNode) $symbols[] = new ReferenceNode($name);
            $this->add($name, $symbols);
        } else {
            $this->add($name, [$node]);
        }
    }
}
