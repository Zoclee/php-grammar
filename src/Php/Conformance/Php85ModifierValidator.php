<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Conformance;

/**
 * PHP 8.5 parser-action checks for an already identified modifier list.
 *
 * This rule-level API does not parse source or certify an entire declaration.
 * Run it before folding, even for declarations in discarded expressions.
 * Source: pinned zend_compile.c, zend_modifier_list_to_flags and
 * zend_add_{class,anonymous_class,member}_modifier, lines 881-1049.
 */
final class Php85ModifierValidator
{
    private const ALLOWED = [
        'class' => ['abstract', 'final', 'readonly'],
        'anonymous-class' => ['readonly'],
        'method' => ['public', 'protected', 'private', 'abstract', 'final', 'static'],
        'property' => ['public', 'protected', 'private', 'public(set)', 'protected(set)', 'private(set)', 'abstract', 'final', 'static', 'readonly'],
        'parameter' => ['public', 'protected', 'private', 'public(set)', 'protected(set)', 'private(set)', 'final', 'readonly'],
        'class-constant' => ['public', 'protected', 'private', 'final'],
        'hook' => ['final'],
        // The parser converts aliases with the METHOD target. The compiler
        // subsequently rejects abstract/static aliases if they survive folding.
        'trait-alias' => ['public', 'protected', 'private', 'abstract', 'final', 'static'],
    ];

    /** @param list<string> $modifiers Canonical token spellings, excluding trivia.
     *  @return ?string Stable category of the first failure; null means this rule passed.
     */
    public function validate(string $target, array $modifiers): ?string
    {
        if (!isset(self::ALLOWED[$target])) {
            throw new \InvalidArgumentException('Unknown PHP 8.5 modifier target: ' . $target);
        }
        $seen = [];
        $visibility = $setVisibility = false;
        foreach ($modifiers as $modifier) {
            $modifier = strtolower($modifier);
            if (!in_array($modifier, self::ALLOWED[$target], true)) {
                return 'php85.modifier.target';
            }
            $isVisibility = in_array($modifier, ['public', 'protected', 'private'], true);
            $isSetVisibility = str_ends_with($modifier, '(set)');
            if ($isVisibility && $visibility) return 'php85.modifier.visibility';
            if ($isSetVisibility && $setVisibility) return 'php85.modifier.set-visibility';
            if (in_array($target, ['class', 'method', 'property', 'trait-alias'], true)
                && (($modifier === 'final' && isset($seen['abstract']))
                    || ($modifier === 'abstract' && isset($seen['final'])))) {
                return 'php85.modifier.abstract-final';
            }
            if (isset($seen[$modifier])) return 'php85.modifier.duplicate';
            $seen[$modifier] = true;
            $visibility = $visibility || $isVisibility;
            $setVisibility = $setVisibility || $isSetVisibility;
        }
        return null;
    }
}
