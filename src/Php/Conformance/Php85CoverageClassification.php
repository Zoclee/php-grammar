<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

use PhpGrammar\Ebnf\{AlternativeNode, Grammar, GroupNode, LiteralNode, Node, OptionalNode, ReferenceNode, RepetitionNode, SequenceNode};
use PhpGrammar\Ebnf\Coverage\CoverageIdentityMap;

/** Classifies gaps without treating an arbitrary uncovered rule as lexical. */
final class Php85CoverageClassification
{
    private array $reachable = [];
    private array $trivia = [];
    private array $bypassed = [];
    private array $contextualOnly = [];

    /** @param list<string> $primitives */
    public function __construct(Grammar $grammar, array $primitives)
    {
        $map = $grammar->productionMap();
        $types = $map['simple-type-without-static']->expression ?? null;
        if ($types instanceof AlternativeNode) {
            $identities = CoverageIdentityMap::fromGrammar($grammar);
            foreach ($types->alternatives as $index => $type) {
                if ($type instanceof LiteralNode && in_array($type->value, ['void', 'never'], true)) {
                    $this->contextualOnly[$identities->alternativeId($types, $index)] = true;
                }
            }
        }
        $walk = function (string $name, array &$seen, bool $stop) use (&$walk, $map, $primitives): void {
            if (isset($seen[$name])) return;
            $seen[$name] = true;
            if (!isset($map[$name]) || ($stop && in_array($name, $primitives, true))) return;
            foreach (self::references($map[$name]->expression) as $reference) {
                $walk($reference, $seen, $stop);
            }
        };
        $walk('source-file', $this->reachable, true);
        foreach (['whitespace', 'comment'] as $name) $walk($name, $this->trivia, false);
        foreach ($primitives as $name) $walk($name, $this->bypassed, false);
        // Primitive bodies themselves are bypassed even when their tokens are reachable.
        foreach ($this->reachable as $name => $_) {
            if (!in_array($name, $primitives, true)) unset($this->bypassed[$name]);
        }
    }

    /** @return array{classification: string, area: string, reason: string, evidence: string} */
    public function classify(string $identity): array
    {
        $production = explode('/', $identity)[0];
        if (isset($this->trivia[$production])) {
            return ['classification' => 'trivia-removed', 'area' => 'source/lexical',
                'reason' => 'Lexer recognizes trivia; withoutTrivia() removes it before chart recognition.',
                'evidence' => 'tests/Php/Lexing/LexerTest.php::testTokenizesWhitespaceCommentsAndDocComments'];
        }
        if (isset($this->bypassed[$production])) {
            return ['classification' => 'primitive-bypassed', 'area' => 'source/lexical',
                'reason' => 'Token primitive replaces this body or cuts every source-file path to this lexical helper.',
                'evidence' => 'tests/Php/Lexing/LexerTest.php; tests/Php/Conformance/Php85AuditRemediationTest.php; audit-numbers-*, audit-strings-*, audit-names-*, audit-halt-data fixtures'];
        }
        if (isset($this->contextualOnly[$identity])) {
            return ['classification' => 'contextual-only', 'area' => 'types',
                'reason' => 'never/void are return-only; this helper is used for parameters and properties. Positive type/return coverage exists, but this placement has no valid source witness.',
                'evidence' => 'Php85CoverageCases type matrix; contextual-invalid/audit-types-parameter-void.php; docs/php85-audit-remediation.md'];
        }
        return ['classification' => 'meaningful-gap', 'area' => $production,
            'reason' => 'Needs positive evidence or explicit investigation; never automatically suppressed.', 'evidence' => ''];
    }

    /** @return list<string> */
    private static function references(Node $node): array
    {
        if ($node instanceof ReferenceNode) return [$node->name];
        $children = match (true) {
            $node instanceof AlternativeNode => $node->alternatives,
            $node instanceof SequenceNode => $node->elements,
            $node instanceof GroupNode, $node instanceof OptionalNode, $node instanceof RepetitionNode => [$node->expression],
            default => [],
        };
        $names = [];
        foreach ($children as $child) array_push($names, ...self::references($child));
        return $names;
    }
}
