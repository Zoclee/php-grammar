# PHP 8.5 Grammar

This document is the human-readable companion to the canonical PHP 8.5 grammar
in `php.ebnf`. The `.ebnf` file is authoritative; this Markdown describes the
same standalone language target and records the source evidence used to compile
it.

The grammar is complete as a direct PHP 8.5 source syntax specification. It does
not include, extend, or depend on another PHP version.

## Source Unit

A PHP source file is modeled as a sequence of inline HTML regions and PHP code
regions. PHP code begins with an opening tag, either `<?php`, `<?`, or the echo
opening tag `<?=`. A closing tag `?>` is optional at the end of a PHP code
region. The echo opening tag is represented as an expression-producing code
region equivalent to an echo expression in source syntax.

Top-level PHP code accepts statements, attributed declarations, namespace
declarations, namespace import declarations, global constants, and
`__halt_compiler();`.

## Lexical Grammar

The lexical grammar covers:

- PHP opening and closing tags;
- inline HTML;
- insignificant whitespace and comments;
- line comments, block comments, and documentation comments;
- identifiers, variables, labels, and variable variables;
- reserved and semi-reserved keyword behavior;
- qualified, fully qualified, and namespace-relative names;
- integer, floating-point, single-quoted, double-quoted, heredoc, and nowdoc
  literals;
- interpolated variables in double-quoted strings, heredocs, and backticks;
- magic constants, including `__PROPERTY__`;
- PHP 8.5 tokens `|>` and `(void)`.

Identifiers start with an ASCII letter, underscore, or non-ASCII byte and may
continue with digits. The grammar keeps `identifier`, `name-identifier`,
`qualified-name`, `fully-qualified-name`, and `namespace-relative-name` distinct
so contextual keyword behavior is visible instead of flattened into a single
name token.

The terminal spelling in the grammar is canonical. PHP keywords are treated
case-insensitively by the lexer, while names preserve source spelling.

## Namespaces And Imports

Namespace declarations are standalone top-level constructs:

```ebnf
namespace-definition =
      "namespace" , namespace-declaration-name , ";"
    | "namespace" , namespace-declaration-name , "{" , top-statement-list , "}"
    | "namespace" , "{" , top-statement-list , "}" ;
```

Imports support ordinary, function, constant, grouped, and mixed grouped use
forms. Group use prefixes are modeled independently from unprefixed imported
names to preserve PHP's distinction between leading-backslash names and
unprefixed group members.

## Types

The grammar covers PHP 8.5 type syntax, including:

- built-in simple types;
- class and interface names;
- `self`, `parent`, and `static` where accepted;
- nullable types;
- union types;
- intersection types;
- disjunctive-normal-form style unions containing parenthesized intersections.

Parameter types use `type-without-static` to preserve the parser's restriction
that `static` is not accepted as a parameter type. Return types and property
types use `type`.

The grammar describes syntax only. It does not encode semantic restrictions such
as duplicate union members, impossible type combinations, or class existence.

## Expressions

Expression precedence is represented by layered productions:

```text
assignment-expression
conditional-expression
coalesce-expression
boolean-or-expression
boolean-and-expression
bitwise-or-expression
bitwise-xor-expression
bitwise-and-expression
equality-expression
relational-expression
pipe-expression
concatenation-expression
shift-expression
additive-expression
multiplicative-expression
instanceof-expression
unary-expression
postfix-expression
primary-expression
```

This ordering places PHP 8.5 `|>` tighter than relational comparisons and looser
than concatenation, matching the PHP 8.5 parser precedence declarations.

The grammar covers assignments, compound assignments, reference assignments,
conditional and coalesce expressions, logical and bitwise operators, equality
and relational operators, the pipe operator, concatenation, shifts, arithmetic,
`instanceof`, unary operators, casts, postfix access, calls, object and nullsafe
object access, static access, array access, increments, `yield`, `yield from`,
`throw`, include/require forms, `print`, closures, arrow functions, `match`,
arrays, lists, constants, class constants, object creation, cloning, `isset`,
`empty`, `eval`, `exit`, and backticks.

### PHP 8.5 Pipe Operator

PHP 8.5 adds the binary pipe operator:

```php
$result = $value |> trim(...) |> strtolower(...);
```

The right-hand side is syntactically an expression. Semantic callable validation
is outside this grammar.

### Void Cast

PHP 8.5 adds `(void)` as syntax for intentionally discarding a value. The parser
accepts it in expression-statement and `for` expression-list positions, not as a
general expression:

```php
(void) compute();

for ((void) setup(); $ok; (void) tick()) {
}
```

Accordingly, `void-cast-statement` and `for-expression` model `(void)` without
adding it to `cast-expression`.

### Constant Expressions

The grammar allows the PHP 8.5 constant-expression surface to include closures,
first-class callables, and casts. Whether a particular expression is permitted
in a given constant-expression context may still involve compile-time semantic
validation beyond syntax.

## Statements

Statements include block statements, ordinary and alternative `if`, `while`,
`for`, `foreach`, `switch`, and `declare` forms, `try`/`catch`/`finally`,
expression statements, `(void)` discard statements, echo, global, static,
unset, return, throw, break, continue, goto, labels, and empty statements.

Switch cases accept either `:` or `;` after `case` and `default`. The semicolon
form remains syntactically accepted in PHP 8.5 even though it is deprecated.

## Functions And Closures

Function declarations use:

```ebnf
function-declaration =
    "function" , [ "&" ] , identifier , "(" , parameter-list , ")" ,
    return-type , compound-statement ;
```

Anonymous functions support optional `static`, by-reference returns, parameter
lists, lexical `use (...)` variables, return types, and compound bodies. Arrow
functions support optional `static`, by-reference returns, parameter lists,
return types, and a single expression body.

Parameters support attributes, visibility and promotion modifiers, final
promotion, readonly promotion, by-reference passing, variadics, default values,
and property hook blocks.

## Classes

Class declarations and anonymous classes cover modifiers, inheritance,
interfaces, properties, methods, constants, trait use, and attributes. Property
syntax includes:

- ordinary properties;
- typed properties;
- readonly and final properties;
- static properties;
- asymmetric visibility including `public(set)`, `protected(set)`, and
  `private(set)`;
- property hooks with full, abstract, and short expression bodies.

PHP 8.5 extends asymmetric visibility to static properties and permits final
constructor property promotion; both forms are represented in the modifier
productions.

## Interfaces

Interfaces may extend one or more interfaces and contain method declarations and
class constant declarations. Attributes may appear on interface declarations,
methods, and constants where the parser accepts attributed declarations.

## Traits

Traits use the same class member list as classes, including methods,
properties, constants, and nested trait-use declarations. Trait adaptations
cover precedence rules and aliases:

```php
use A, B {
    A::method insteadof B;
    B::method as private alias;
}
```

## Enums

Enum declarations cover unit enums, backed enums with `int` or `string` backing
types, implemented interfaces, cases with optional values, methods, constants,
trait use, attributes, and empty member separators.

## Attributes

Attributes are modeled as one or more groups:

```ebnf
attribute-group =
    "#[" , attribute-list , [ "," ] , "]" ;
```

Attribute arguments use the ordinary argument-list grammar. PHP 8.5 permits
attributes on compile-time non-class constants, so `constant-declaration` is an
attributed top-level declaration.

The built-in PHP 8.5 attributes `NoDiscard` and `DelayedTargetValidation` do
not require special grammar productions; they are names used through the normal
attribute grammar.

## PHP 8.5-Specific Syntax Accounted For

- `|>` pipe operator with its PHP 8.5 precedence position.
- `(void)` discard syntax as a statement and in `for` expression lists.
- Constant-expression expansion for closures, first-class callables, and casts.
- Attributes on compile-time non-class constants.
- `#[NoDiscard]` and `#[DelayedTargetValidation]` as ordinary attribute names.
- Static properties with asymmetric visibility.
- Final constructor property promotion.
- `__PROPERTY__` magic constant.
- `clone(...)` function-style syntax and clone first-class callable syntax.

## Source Evidence

Primary evidence used for this PHP 8.5 grammar:

- PHP 8.5.10 source parser grammar:
  <https://github.com/php/php-src/blob/php-8.5.10/Zend/zend_language_parser.y>
- PHP 8.5.10 scanner:
  <https://github.com/php/php-src/blob/php-8.5.10/Zend/zend_language_scanner.l>
- PHP 8.5.10 upgrading notes:
  <https://github.com/php/php-src/blob/php-8.5.10/UPGRADING>
- PHP 8.5 release announcement:
  <https://www.php.net/releases/8.5/en.php>
- Official PHP 8.5 migration guide:
  <https://www.php.net/manual/en/migration85.php>
- RFC: Pipe Operator v3:
  <https://wiki.php.net/rfc/pipe-operator-v3>
- RFC: Closures in constant expressions:
  <https://wiki.php.net/rfc/closures_in_const_expr>
- RFC: First-class callables in constant expressions:
  <https://wiki.php.net/rfc/fcc_in_const_expr>
- RFC: Marking return values as important:
  <https://wiki.php.net/rfc/marking_return_value_as_important>
- RFC: Static asymmetric visibility:
  <https://wiki.php.net/rfc/static-aviz>
- RFC: Attributes on constants:
  <https://wiki.php.net/rfc/attributes-on-constants>
- RFC: Final property promotion:
  <https://wiki.php.net/rfc/final_promotion>
- RFC: Override properties:
  <https://wiki.php.net/rfc/override_properties>
- RFC: DelayedTargetValidation attribute:
  <https://wiki.php.net/rfc/delayedtargetvalidation_attribute>
- RFC: Clone with v2:
  <https://wiki.php.net/rfc/clone_with_v2>

## Fixtures

Representative fixtures are under:

```text
tests/fixtures/8.5/valid/
tests/fixtures/8.5/invalid/
```

They cover lexical constructs, names and namespaces, literals, types,
expression precedence, statements, functions and closures, classes, interfaces,
traits, enums, attributes, declarations, and PHP 8.5-specific syntax.

Phase 1 repository tests validate the canonical `php.ebnf` file directly with
the project EBNF parser and integrity validator. These PHP source fixtures are
retained for later conformance phases and are not linted with the installed PHP
CLI as part of grammar validation.

## Known Limitations

The grammar is implementation-neutral EBNF and intentionally avoids copying
PHP's Bison grammar mechanics. A few details are therefore represented as
lexical categories rather than byte-level regular expressions, especially
source encodings, heredoc indentation checks, and uninterpreted string/comment
text.

The `code-unit` reference at the bottom of the lexical grammar is a primitive
source-text category for tooling, not an ordinary production expanded in
`php.ebnf`. It denotes one raw source byte. The generic EBNF matcher consumes
one byte for this primitive when matching `StringInput`, while the separate
Phase 3 PHP lexer classifies byte ranges into repository-owned tokens. Full PHP
source conformance and version-boundary fixture validation are deferred to later
phases.

The grammar describes syntax and not PHP's separate semantic validation phase.
Examples include duplicate modifiers, invalid attribute targets, impossible type
combinations, and callable validity for `|>`.
