<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Matching;

use PhpGrammar\Ebnf\AlternativeNode;
use PhpGrammar\Ebnf\Grammar;
use PhpGrammar\Ebnf\GroupNode;
use PhpGrammar\Ebnf\LiteralNode;
use PhpGrammar\Ebnf\Node;
use PhpGrammar\Ebnf\OptionalNode;
use PhpGrammar\Ebnf\ReferenceNode;
use PhpGrammar\Ebnf\RepetitionNode;
use PhpGrammar\Ebnf\SequenceNode;

final readonly class Matcher
{
    /**
     * @param array<string, callable(Input, int): list<int>> $primitiveMatchers
     */
    public function __construct(
        private string $rootRule = 'source-file',
        private array $primitiveMatchers = [],
    ) {
    }

    public static function withDefaultPrimitives(string $rootRule = 'source-file'): self
    {
        return new self($rootRule, [
            'code-unit' => static function (Input $input, int $offset): array {
                return $offset < $input->length() ? [$offset + 1] : [];
            },
        ]);
    }

    public function matches(Grammar $grammar, string|Input $input): MatchResult
    {
        return $this->matchesRule($grammar, $this->rootRule, $input);
    }

    public function matchesRule(Grammar $grammar, string $rule, string|Input $input): MatchResult
    {
        $input = is_string($input) ? new StringInput($input) : $input;
        $context = new MatchContext($input);
        $endOffsets = $this->matchReference($grammar, $rule, 0, $context);
        $matched = in_array($input->length(), $endOffsets, true);

        if (!$matched && $endOffsets !== []) {
            $context->recordFailure(max($endOffsets), 'end of input');
        }

        return new MatchResult(
            matched: $matched,
            rule: $rule,
            input: $input,
            furthestOffset: $matched ? $input->length() : $context->furthestOffset,
            expected: $matched ? [] : $context->expected(),
        );
    }

    /**
     * @return list<int>
     */
    private function matchNode(Grammar $grammar, Node $node, int $offset, MatchContext $context): array
    {
        if ($node instanceof ReferenceNode) {
            return $this->matchReference($grammar, $node->name, $offset, $context);
        }

        if ($node instanceof LiteralNode) {
            if ($this->literalMatches($context->input, $offset, $node->value)) {
                return [$this->literalEndOffset($context->input, $offset, $node->value)];
            }

            $context->recordFailure(
                $this->literalFailureOffset($context->input, $offset, $node->value),
                '"' . addcslashes($node->value, "\\\"\n\r\t\f\v") . '"',
            );
            return [];
        }

        if ($node instanceof SequenceNode) {
            return $this->matchSequence($grammar, $node, $offset, $context);
        }

        if ($node instanceof AlternativeNode) {
            return $this->matchAlternative($grammar, $node, $offset, $context);
        }

        if ($node instanceof GroupNode) {
            return $this->matchNode($grammar, $node->expression, $offset, $context);
        }

        if ($node instanceof OptionalNode) {
            return $this->uniqueOffsets(array_merge(
                [$offset],
                $this->matchNode($grammar, $node->expression, $offset, $context),
            ));
        }

        if ($node instanceof RepetitionNode) {
            return $this->matchRepetition($grammar, $node, $offset, $context);
        }

        return [];
    }

    /**
     * @return list<int>
     */
    private function matchReference(Grammar $grammar, string $rule, int $offset, MatchContext $context): array
    {
        $key = $rule . '@' . $offset;
        if (array_key_exists($key, $context->memo)) {
            return $context->memo[$key];
        }

        if (isset($context->active[$key])) {
            $context->recordFailure($offset, $rule);
            return [];
        }

        $production = $grammar->productionMap()[$rule] ?? null;
        if ($production === null) {
            if (array_key_exists($rule, $this->primitiveMatchers)) {
                $offsets = ($this->primitiveMatchers[$rule])($context->input, $offset);
                if ($offsets === []) {
                    $context->recordFailure($offset, $rule);
                }

                return $this->uniqueOffsets($offsets);
            }

            $context->recordFailure($offset, $rule);
            return [];
        }

        $context->active[$key] = true;
        $offsets = $this->matchNode($grammar, $production->expression, $offset, $context);
        unset($context->active[$key]);

        $context->memo[$key] = $this->uniqueOffsets($offsets);
        return $context->memo[$key];
    }

    /**
     * @return list<int>
     */
    private function matchSequence(Grammar $grammar, SequenceNode $node, int $offset, MatchContext $context): array
    {
        $offsets = [$offset];
        foreach ($node->elements as $element) {
            $nextOffsets = [];
            foreach ($offsets as $currentOffset) {
                array_push($nextOffsets, ...$this->matchNode($grammar, $element, $currentOffset, $context));
            }

            $offsets = $this->uniqueOffsets($nextOffsets);
            if ($offsets === []) {
                return [];
            }
        }

        return $offsets;
    }

    /**
     * @return list<int>
     */
    private function matchAlternative(Grammar $grammar, AlternativeNode $node, int $offset, MatchContext $context): array
    {
        $offsets = [];
        foreach ($node->alternatives as $alternative) {
            array_push($offsets, ...$this->matchNode($grammar, $alternative, $offset, $context));
        }

        return $this->uniqueOffsets($offsets);
    }

    /**
     * @return list<int>
     */
    private function matchRepetition(Grammar $grammar, RepetitionNode $node, int $offset, MatchContext $context): array
    {
        $results = [$offset];
        $queue = [$offset];
        $visited = [$offset => true];

        while ($queue !== []) {
            $currentOffset = array_shift($queue);
            foreach ($this->matchNode($grammar, $node->expression, $currentOffset, $context) as $nextOffset) {
                if ($nextOffset === $currentOffset) {
                    continue;
                }

                if (!isset($visited[$nextOffset])) {
                    $visited[$nextOffset] = true;
                    $results[] = $nextOffset;
                    $queue[] = $nextOffset;
                }
            }
        }

        return $this->uniqueOffsets($results);
    }

    /**
     * @param list<int> $offsets
     * @return list<int>
     */
    private function uniqueOffsets(array $offsets): array
    {
        $offsets = array_values(array_unique($offsets));
        rsort($offsets, SORT_NUMERIC);

        return $offsets;
    }

    private function literalMatches(Input $input, int $offset, string $literal): bool
    {
        if ($literal === '') {
            return true;
        }

        if ($offset >= $input->length()) {
            return false;
        }

        $value = $input->valueAt($offset);
        if (is_string($value) && $value === $literal) {
            return true;
        }

        if ($offset + strlen($literal) > $input->length()) {
            return false;
        }

        for ($index = 0; $index < strlen($literal); $index++) {
            if ($input->valueAt($offset + $index) !== $literal[$index]) {
                return false;
            }
        }

        return true;
    }

    private function literalEndOffset(Input $input, int $offset, string $literal): int
    {
        if ($offset < $input->length() && $input->valueAt($offset) === $literal) {
            return $offset + 1;
        }

        return $offset + strlen($literal);
    }

    private function literalFailureOffset(Input $input, int $offset, string $literal): int
    {
        if ($offset < $input->length() && $input->valueAt($offset) === $literal) {
            return $offset;
        }

        $limit = min(strlen($literal), $input->length() - $offset);
        for ($index = 0; $index < $limit; $index++) {
            if ($input->valueAt($offset + $index) !== $literal[$index]) {
                return $offset + $index;
            }
        }

        return $offset + $limit;
    }
}
