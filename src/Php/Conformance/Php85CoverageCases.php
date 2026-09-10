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
        $cases = [
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

        // Each operator is an independent positive witness, including the
        // separate compile-time expression hierarchy.
        foreach (['and', 'xor', 'or', '??', '||', '&&', '|', '^', '&', '==', '!=', '===', '!==', '<=>', '<>', '<', '<=', '>', '>=', '.', '<<', '>>', '+', '-', '*', '/', '%', '**'] as $operator) {
            foreach (['expression', 'constant-expression'] as $rule) {
                $cases[] = new RuleLevelCase($rule, '8 ' . $operator . ' 2');
            }
        }
        foreach (['=', '+=', '-=', '*=', '/=', '.=', '%=', '&=', '|=', '^=', '<<=', '>>=', '**=', '??='] as $operator) {
            $cases[] = new RuleLevelCase('expression', '$value ' . $operator . ' 2');
        }
        foreach (['int', 'integer', 'float', 'double', 'string', 'binary', 'array', 'object', 'bool', 'boolean'] as $cast) {
            foreach (['expression', 'constant-expression'] as $rule) {
                $cases[] = new RuleLevelCase($rule, '(' . $cast . ') 1');
            }
        }
        foreach (['array', 'callable', 'iterable', 'bool', 'int', 'float', 'string', 'object', 'mixed', 'never', 'void', 'null', 'false', 'true', 'self', 'parent', 'static', 'Example', '?Example', 'A&B', '(A&B)|C'] as $type) {
            $cases[] = new RuleLevelCase('type', $type);
            // void/never/static have no valid parameter/property use.
            if (!in_array($type, ['void', 'never', 'static'], true)) {
                $cases[] = new RuleLevelCase('type-without-static', $type);
            }
        }
        foreach (['+', '-', '!', '~'] as $operator) {
            $cases[] = new RuleLevelCase('constant-expression', $operator . '1');
        }
        $matrix = [
            'expression' => [
                '--$x', '$x--', '@$x', '~$x', '-$x', '!$x', '++$x', '$x++',
                '$x = &$y', 'list($x, , list($y)) = $items', 'list("x" => list($y)) = $items',
                'array(&$x, "x" => &$y, ...$items,)',
                '$x?->name', '$x?->method()', '$x->$name', '$x->{"name"}',
                'A::{"method"}()', '$x::{"VALUE"}', 'new $class', 'new ($class)()',
                'new $classes[0]()', 'new $object->className()', 'new $object?->className()',
                'new A::$class()', 'new $object::$class()',
                'isset($x, $y,)', 'empty($x)', 'eval("return 1;")', 'exit', 'die()',
                'print $x', '+print $x', 'clone +$x', 'fn &($x) => $x',
                'static function &() use ($x, &$y,) { return $y; }',
                'include_once "a.php"', 'require "a.php"', 'require_once "a.php"',
                '\\A\\f()', 'namespace\\f()', 'A\\f()', '${$name}', 'clone clone $x',
            ],
            'constant-expression' => [
                '1 ? 2 : 3', '1 ?: 2 ?: 3', 'array("x" => 1, ...[2],)', '1.5',
                '__LINE__', '__FILE__', '__DIR__', '__CLASS__', '__TRAIT__', '__METHOD__',
                '__FUNCTION__', '__PROPERTY__', '__NAMESPACE__',
                'readonly(...)', 'exit(...)', 'die(...)', 'A::{"method"}(...)',
                'A::{"VALUE"}', '__FILE__[0]', 'new A()->name',
                'new Box()->child?->{"name"}[0]',
                'new Box()->{"child"}->other?->{"name"}',
            ],
            'namespace-use-declaration' => [
                'use A;', 'use \\A\\B as C;', 'use function A\\f, A\\g as h;',
                'use const A\\X;', 'use A\\{B, C\\D as E, function f, const X,};',
                'use function A\\{f, B\\g as h,};', 'use const A\\{X, B\\Y,};',
            ],
            'statement' => [
                'do { continue; } while ($x);',
                'foreach ($items as $value) {}', 'foreach ($items as $key => &$value) {}',
                'foreach ($items as list($x, $y)) {}', 'foreach ($items as [$x, $y]): endforeach;',
                'for (;;): endfor;', 'while ($x): break; endwhile;',
                'if ($x): elseif ($y): else: endif;',
                'switch ($x): ; case 1; break; default; break; endswitch;',
                'declare(ticks=1): enddeclare;',
            ],
            'class-member' => [
                'protected int $x = 1;', 'private static $x;', 'public readonly int $x;',
                'public final int $x;', 'public public(set) int $x;', 'public protected(set) int $x;',
                'protected final static function f() {}', 'private function f() {}',
                'protected const int X = 1;', 'private const X = 1;', 'final public const X = 1;',
                'var $x = 1;',
            ],
            'trait-use-declaration' => [
                'use T;', 'use T {}', 'use T, U { T::f insteadof U; }',
                'use T { f as alias; f as match; T::f as protected; f as final alias; }',
            ],
            'interface-declaration' => ['interface I extends A, B { const X = 1; public string $name { get; set; } }'],
            'enum-declaration' => ['enum E { use T; const X = 1; public function f() {} case A; }'],
            'namespace-definition' => ['namespace A {}', 'namespace {}'],
            'compound-statement' => [
                '{ function f() {} class C {} interface I {} trait T {} enum E {} }',
            ],
        ];
        foreach ($matrix as $rule => $sources) {
            foreach ($sources as $source) {
                $cases[] = new RuleLevelCase($rule, $source);
            }
        }
        return $cases;
    }
}
