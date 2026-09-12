<?php

declare(strict_types=1);

namespace PhpGrammar\Tests\Support;

/** Finite dimensions and explicit expected outcomes, independent of the oracle. */
final class Php85Phase6Cases
{
    public static function folding(): iterable
    {
        // zend_compile_short_circuiting skips ordinary RHS compilation;
        // ordinary ternary/coalesce still compile both children. In constant
        // expressions zend_eval_const_expr visits logical children before
        // folding, but selects ternary/coalesce children first. Unset casts
        // themselves fail evaluation; the other nodes fail the later walk.
        $expressions = ['unset' => '(unset) 1', 'isset' => 'isset(1)',
            'nullsafe-write' => '($a?->b = 1)', 'globals-write' => '($GLOBALS = [])',
            'callable-conversion' => 'new C(...)'];
        $contexts = ['live' => '%s', 'and' => 'false && %s', 'or' => 'true || %s',
            'false-middle' => 'false ? %s : 1', 'true-last' => 'true ? 1 : %s',
            'null-coalesce' => 'null ?? %s', 'value-coalesce' => '1 ?? %s'];
        foreach ($expressions as $id => $expression) foreach ([false, true] as $constant) {
            foreach ($contexts as $context => $template) {
                $expected = $constant
                    ? in_array($context, ['false-middle', 'true-last', 'value-coalesce'], true)
                        || ($id !== 'unset' && in_array($context, ['and', 'or'], true))
                    : in_array($context, ['and', 'or'], true);
                yield ["$id/" . ($constant ? 'constant/' : 'ordinary/') . $context,
                    '<?php ' . ($constant ? 'const X = ' : '') . sprintf($template, $expression) . ';', $expected];
            }
        }
    }

    public static function modifiers(): iterable
    {
        $members = explode(' ', 'public protected private public(set) protected(set) private(set) static abstract final readonly');
        $templates = [
            'class' => '%s class C {}',
            'anonymous-class' => '$x = new %s class {};',
            'property' => 'class C { %s int $x; }',
            'method' => 'abstract class C { %s function f() {} }',
            'parameter' => 'class C { function __construct(%s int $x) {} }',
            'class-constant' => 'class C { %s const X = 1; }',
            'hook' => 'class C { public int $x { %s get => 1; } }',
            'trait-alias' => 'class C { use T { f as %s alias; } }',
        ];
        foreach ($templates as $target => $template) {
            $tokens = in_array($target, ['class', 'anonymous-class'], true) ? ['abstract', 'final', 'readonly'] : $members;
            foreach ($tokens as $a) {
                yield [$target, [$a], sprintf($template, $a)];
                if ($target === 'trait-alias') continue; // grammar admits one alias modifier
                foreach ($tokens as $b) yield [$target, [$a, $b], sprintf($template, "$a $b")];
            }
        }
    }

    public static function ambiguity(): iterable
    {
        $groups = [
            'type' => ['?A', 'A|B', 'A&B', '(A&B)|C', 'A|(B&C)', 'A|B|C'],
            'name' => ['A', 'A\\B', '\\A\\B', 'namespace\\A'],
            'attribute-groups' => ['#[A]', '#[A(1), B]', '#[A] #[B(1+2)]'],
            'argument-list' => ['()', '($a,)', '(a: $a, ...$b)', '(...)'],
            'array-pair-list' => ['$a', '$a,', ',,$a', '1 => $a, 2 => &$b'],
            'expression' => ['[$a, $b] = $c', 'list($a,, $b) = $c', 'clone($a)', 'clone($a,)', 'clone($a, $b)'],
            'class-member' => ['public int $x;', 'function f() {}', 'public int $x { get => 1; set {} }'],
            'trait-adaptation' => ['T::f insteadof U;', 'f as protected g;', 'f as final;'],
            'statement' => ['if ($a): while ($b): ; endwhile; endif;', 'switch ($a): case 1: switch ($b) {} endswitch;'],
        ];
        foreach ($groups as $entry => $sources) foreach ($sources as $source) yield [$entry, $source];
    }

    /** Two different wrappers at each ordered pair, repeated to depths 1..3.
     * All wrappers preserve a single expression. These test bytes through lint.
     */
    public static function recursive(): iterable
    {
        $wrappers = [
            'interpolation' => '"{$a[%s]}"',
            'closure' => '(static function() { return %s; })()',
            'anonymous-class' => '(new class { function f() { return %s; } })',
            'braces' => '$a->{%s}',
            'heredoc' => "<<<DOC%d\n{\$a[%s]}\nDOC%d\n",
        ];
        foreach ($wrappers as $left => $outer) foreach ($wrappers as $right => $inner) {
            foreach ([1, 2, 3] as $depth) {
                $expr = '1';
                for ($i = 0; $i < $depth; $i++) {
                    foreach ([$inner, $outer] as $j => $wrapper) {
                        $label = $i * 2 + $j;
                        $expr = str_starts_with($wrapper, '<<<') ? sprintf($wrapper, $label, $expr, $label) : sprintf($wrapper, $expr);
                    }
                }
                yield ["$left/$right/$depth", '<?php ' . $expr . ';', true];
            }
        }
        yield ['attribute-constant', '<?php #[A([1, [2 + 3]])] class C {}', true];
        yield ['alternative-html', '<?php if ($a): while ($b): ?>x<?php if ($c): ?>y<?php endif; endwhile; endif;', true];
        yield ['comment-string-tag', '<?php echo "?>/*"; /* ?> */ // ?>html<?php ;', true];
    }

    public static function malformed(): iterable
    {
        yield ['halt-inner', '<?php function f() { __halt_compiler(); }'];
        yield ['missing-expression', '<?php $a = ;'];
        yield ['missing-hook-body', '<?php class C { public int $x { get } }'];
        yield ['unterminated-interpolation', '<?php echo "{$a[0]";'];
        yield ['unclosed-comment', '<?php /*'];
        yield ['unclosed-heredoc', "<?php echo <<<END\nx\n"];
        yield ['attribute-recovery', '<?php #[A(] function f() {}'];
        yield ['try-recovery', '<?php try {} catch () {}'];
    }
}
