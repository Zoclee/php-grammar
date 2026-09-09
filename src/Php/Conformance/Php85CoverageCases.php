<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

final readonly class Php85CoverageCases
{
    /**
     * @return list<RuleLevelCase>
     */
    public static function ruleLevelCases(): array
    {
        return [
            new RuleLevelCase('function-declaration', 'function f(int|string $value): ?string { return "x"; }'),
            new RuleLevelCase('class-declaration', 'final class User extends Person implements Named { public string $name; }'),
            new RuleLevelCase('interface-declaration', 'interface Named { public function name(): string; }'),
            new RuleLevelCase('trait-declaration', 'trait T { public function f(): int { return 1; } }'),
            new RuleLevelCase('enum-declaration', 'enum Status: string { case Active = "active"; }'),
            new RuleLevelCase('attribute-groups', '#[Example("value")]'),
            new RuleLevelCase('match-expression', 'match ($x) { 1 => "one", default => "other", }'),
            new RuleLevelCase('closure-expression', 'static function (&$x): int { return 1; }'),
            new RuleLevelCase('arrow-function', 'fn ($x): int => $x'),
            new RuleLevelCase('array-creation-expression', '["a" => 1, ...$items]'),
        ];
    }
}
