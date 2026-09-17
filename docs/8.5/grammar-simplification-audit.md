# PHP 8.5 grammar simplification audit

## Scope and result

This audit reviews all **364** original productions in source order. The result
has **360** productions: **317 KEEP, 3 SIMPLIFY, 4 INLINE, 40 GENERATE**.
Only interface/enum member wrappers and one optional repetition change their
EBNF representation. All 40 generated expression productions retain their exact
original text. No lexical primitive, contextual validator, precedence boundary,
operator set, statement binding rule, or fixture expectation changes.

The four removed production names are a tooling compatibility change: consumers
that selected `interface-member-list` or `enum-member-list` directly should use
`class-member-list`; those selecting `interface-member` or `enum-member` should
use `class-member`. Derivations retain member ordering and operand spans after
erasing these redundant names. Literal equality of trees containing the removed
alias nodes is neither possible nor claimed. Optional/repetition implementation
nodes likewise change; ordered named parts and derivation multiplicities do not.

The audit intentionally stops at four removals rather than seeking a numerical
target. The remaining aliases identify lexical, parser, or compiler concepts.

## Authority and verification

The source pin is `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.
The three local source files were checked against
[`source-lock.json`](../../tools/8.5/source-lock.json).

- [Parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y):
  lines 57–82 define operator precedence; `expr` starts at 1248;
  class/trait/interface/enum declarations at 604–655 and `class_statement_list`,
  `attributed_class_statement`, `class_statement` at 973–1006 share member syntax;
  `encaps_list`/`encaps_var` at 1664–1695 retain ordered string parts.
- [Scanner](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l):
  double-quote scanning, constant-string fast paths, and interpolation states at
  2570–2673 and 2843–2909 remain untouched. EBNF repetition does not redefine
  scanner segmentation or convert a lexical primitive into a parser wildcard.
- [Compiler](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c):
  `zend_begin_method_decl` (8247), `zend_compile_prop_decl` (8835), and
  `zend_compile_enum_case` (9460) retain declaration-kind legality. Existing
  parser-action modifier validation and surviving-AST restrictions stay external.

The existing [parser/compiler boundary report](parser-compiler-remediation.md),
[structure matrix](systematic-structure.json), and [scanner-product report](scanner-product.json)
remain behavioral authorities. Executable comparisons use the available PHP
8.5.10 oracle, with the existing [source correspondence limits](source-correspondence.json).
This maintenance change does not close certification limitations C1–C4.

## Method

Read every production and examine aliases, identical bodies, optional/repetition
forms, lists and separators, prefix recursion, matched/unmatched propagation,
constant wrappers, modifier targets, and scanner boundaries. The regression
test walks the complete parsed EBNF AST for optional one-or-more forms, including
grouped and sequence operands; the only candidate is double-quoted contents.
Lists with separators, optional trailing commas, nullable slots, or a nonempty
requirement are not candidates.

[`simplification-baseline.json`](../../tests/fixtures/php/8.5/simplification-baseline.json)
freezes all 364 original expression-node fingerprints and 28 nullable names.
[`Php85SimplificationTest`](../../tests/Php/Conformance/Php85SimplificationTest.php)
reverses only the approved three body edits and four removals and compares every
production against those fingerprints. This checks all preserved hierarchies,
not just selected operator examples. Nullability changes only through removal
of the two nullable alias lists: all 26 surviving nullable productions match.
Integrity validation checks undefined and unreachable productions independently.

## Applied SIMPLIFY and INLINE decisions

### Shared member bodies (M)

Reason: the interface and enum wrappers add no alternatives, restrictions, or
semantic attachment point beyond the containing declaration kind. The parser
explicitly shares `class_statement_list` and `class_statement`. Compiler legality
still attaches to the enclosing interface/enum and the actual member declaration.

Nullability/ambiguity: both old lists and `class-member-list` match zero or more
nonnullable `class-member` instances. Each removed member alias has one derivation
per class member; substituting it creates no new choice. Mapping old list nodes
to the shared list and erasing member aliases preserves ordered member spans.

Test evidence: 56 new shared-body acceptance/rejection cases across classes,
interfaces, traits and enums, five before/after unique-derivation comparisons,
the existing structural/contextual corpus and declaration-folding comparisons.
The negative-boundary anchor and parser correspondence now use the shared names.
Historical reports retain their original names as historical evidence.

#### `interface-declaration` — SIMPLIFY

Current (before this change):

```ebnf
interface-declaration =
    "interface" , identifier , [ interface-extends-clause ] ,
    "{" , interface-member-list , "}" ;
```

Proposed/final:

```ebnf
interface-declaration =
    "interface" , identifier , [ interface-extends-clause ] ,
    "{" , class-member-list , "}" ;
```

Disposition: applied. Reason, direct PHP source support, ambiguity/nullability analysis, and tests: M above.

#### `interface-member-list` — INLINE

Current (before this change):

```ebnf
interface-member-list =
    { interface-member } ;
```

Proposed/final: remove the production; use `class-member-list` through the shared declaration body.

Disposition: applied. Reason, direct PHP source support, ambiguity/nullability analysis, and tests: M above.

#### `interface-member` — INLINE

Current (before this change):

```ebnf
interface-member =
    class-member ;
```

Proposed/final: remove the production; use `class-member` through the shared declaration body.

Disposition: applied. Reason, direct PHP source support, ambiguity/nullability analysis, and tests: M above.

#### `enum-declaration` — SIMPLIFY

Current (before this change):

```ebnf
enum-declaration =
    "enum" , identifier , [ enum-backing-type ] , [ implements-clause ] ,
    "{" , enum-member-list , "}" ;
```

Proposed/final:

```ebnf
enum-declaration =
    "enum" , identifier , [ enum-backing-type ] , [ implements-clause ] ,
    "{" , class-member-list , "}" ;
```

Disposition: applied. Reason, direct PHP source support, ambiguity/nullability analysis, and tests: M above.

#### `enum-member-list` — INLINE

Current (before this change):

```ebnf
enum-member-list =
    { enum-member } ;
```

Proposed/final: remove the production; use `class-member-list` through the shared declaration body.

Disposition: applied. Reason, direct PHP source support, ambiguity/nullability analysis, and tests: M above.

#### `enum-member` — INLINE

Current (before this change):

```ebnf
enum-member =
    class-member ;
```

Proposed/final: remove the production; use `class-member` through the shared declaration body.

Disposition: applied. Reason, direct PHP source support, ambiguity/nullability analysis, and tests: M above.

### Optional one-or-more contents (S)

#### `double-quoted-string` — SIMPLIFY

Current (before this change):

```ebnf
double-quoted-string =
    [ "b" | "B" ] , "\"" , [ encapsulated-string-part , { encapsulated-string-part } ] , "\"" ;
```

Proposed/final:

```ebnf
double-quoted-string =
    [ "b" | "B" ] , "\"" , { encapsulated-string-part } , "\"" ;
```

Reason: `[ X , { X } ]` and `{ X }` describe the same ordered sequence of
parts. `X = encapsulated-string-part` is nonnullable: string text requires at
least one character/escape, and every variable alternative consumes syntax.
There is one empty sequence and a bijection for each positive number of parts;
any pre-existing ambiguity within a part or its segmentation is retained, not
fixed or duplicated by this rewrite. String delimiters keep the whole production
nonnullable. Token boundaries and prefix `b`/`B` remain identical.

PHP evidence supports empty/nonempty strings and ordered encapsulation; the
rewrite itself follows EBNF algebra rather than a new interpretation of
`encaps_list`. The scanner's aggregate-token abstraction is unchanged.

Tests: five before/after nonnullable-part derivation/span projections, four
malformed projected strings, ten actual source integration cases, all existing
scanner/lexical and bounded interpolation binding tests. The projection traverses
the EBNF contents directly, avoiding false confidence from primitive bypass.
Final disposition: applied; no other equivalent optional one-or-more form found.

## Generated maintenance decisions (G)

[`generate-expressions.php`](../../tools/8.5/generate-expressions.php) defines 17
precedence levels as metadata: name, child, operator set, associativity, and
whether the closed-yield logical pair is emitted. Each level emits its complete
expression and matching incomplete prefix context. Three logical levels also
emit their closed-yield counterparts, for **40** owned productions.

Reason: one metadata row now controls the corresponding operator set and child
references in both families. Left-fold repetition, non-associative optional
tails, and right-recursive coalescing/power have separate templates. Pending
right-associative chains deliberately repeat the child operand, while left
contexts start with the complete same-level expression. These templates copy
the audited structure; they do not infer a conventional precedence grammar.

PHP source support: parser precedence declarations (57–82) and `expr`, together
with the existing prefix, yield-key and Zend AST conformance evidence, support
the original forms. PHP does not contain these EBNF helper names. Exact output
identity, rather than a new source-to-EBNF translation, justifies generation.

Ambiguity/nullability: all generated bodies are byte-for-byte identical, so
production identity, alternatives, recursion, grouping, and nullable sets are
unchanged. Tests compare every emitted AST to the frozen original fingerprint,
verify byte determinism for LF/CRLF, reject missing owned rules, and detect/repair
drift. Existing operator, yield-key, dangling-else, duplicate-derivation and
operand-span matrices remain the behavioral checks.

Run (with this repository's RTK prefix):

```text
rtk php tools/8.5/generate-expressions.php
rtk php tools/8.5/generate-expressions.php --check
rtk php tools/8.5/sync-documentation.php
```

The generator replaces only named owned rules in place, preserving surrounding
bytes and production order. Missing or duplicate owned productions fail rather
than silently adding a second definition. PHPUnit and the release gate check
freshness. Canonical EBNF remains complete, explicit, ordinary and independently
consumable; no generator, metadata, include, or other version is needed at runtime.

Every following GENERATE entry has the same final disposition: applied, with
the current and proposed production **identical**. The reason, PHP support,
ambiguity/nullability considerations and test evidence are G above.

### `logical-or-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
logical-or-expression =
    logical-xor-expression , { "or" , logical-xor-expression } ;
```

### `logical-or-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
logical-or-prefix-context =
    logical-xor-prefix-context | logical-or-expression , "or" , [ logical-xor-prefix-context ] ;
```

### `closed-yield-logical-or-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
closed-yield-logical-or-expression =
    closed-yield-logical-xor-expression , { "or" , closed-yield-logical-xor-expression } ;
```

### `closed-yield-logical-or-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
closed-yield-logical-or-prefix-context =
    closed-yield-logical-xor-prefix-context | closed-yield-logical-or-expression , "or" , [ closed-yield-logical-xor-prefix-context ] ;
```

### `logical-xor-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
logical-xor-expression =
    logical-and-expression , { "xor" , logical-and-expression } ;
```

### `logical-xor-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
logical-xor-prefix-context =
    logical-and-prefix-context | logical-xor-expression , "xor" , [ logical-and-prefix-context ] ;
```

### `closed-yield-logical-xor-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
closed-yield-logical-xor-expression =
    closed-yield-logical-and-expression , { "xor" , closed-yield-logical-and-expression } ;
```

### `closed-yield-logical-xor-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
closed-yield-logical-xor-prefix-context =
    closed-yield-logical-and-prefix-context | closed-yield-logical-xor-expression , "xor" , [ closed-yield-logical-and-prefix-context ] ;
```

### `logical-and-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
logical-and-expression =
    print-expression , { "and" , print-expression } ;
```

### `logical-and-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
logical-and-prefix-context =
    print-prefix-context | logical-and-expression , "and" , [ print-prefix-context ] ;
```

### `closed-yield-logical-and-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
closed-yield-logical-and-expression =
    closed-yield-print-expression , { "and" , closed-yield-print-expression } ;
```

### `closed-yield-logical-and-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
closed-yield-logical-and-prefix-context =
    closed-yield-print-prefix-context | closed-yield-logical-and-expression , "and" , [ closed-yield-print-prefix-context ] ;
```

### `coalesce-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
coalesce-expression =
    boolean-or-expression , [ "??" , coalesce-expression ] ;
```

### `coalesce-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
coalesce-prefix-context =
    boolean-or-prefix-context | boolean-or-expression , "??" , { boolean-or-expression , "??" } , [ boolean-or-prefix-context ] ;
```

### `boolean-or-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
boolean-or-expression =
    boolean-and-expression , { "||" , boolean-and-expression } ;
```

### `boolean-or-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
boolean-or-prefix-context =
    boolean-and-prefix-context | boolean-or-expression , "||" , [ boolean-and-prefix-context ] ;
```

### `boolean-and-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
boolean-and-expression =
    bitwise-or-expression , { "&&" , bitwise-or-expression } ;
```

### `boolean-and-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
boolean-and-prefix-context =
    bitwise-or-prefix-context | boolean-and-expression , "&&" , [ bitwise-or-prefix-context ] ;
```

### `bitwise-or-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
bitwise-or-expression =
    bitwise-xor-expression , { "|" , bitwise-xor-expression } ;
```

### `bitwise-or-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
bitwise-or-prefix-context =
    bitwise-xor-prefix-context | bitwise-or-expression , "|" , [ bitwise-xor-prefix-context ] ;
```

### `bitwise-xor-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
bitwise-xor-expression =
    bitwise-and-expression , { "^" , bitwise-and-expression } ;
```

### `bitwise-xor-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
bitwise-xor-prefix-context =
    bitwise-and-prefix-context | bitwise-xor-expression , "^" , [ bitwise-and-prefix-context ] ;
```

### `bitwise-and-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
bitwise-and-expression =
    equality-expression , { "&" , equality-expression } ;
```

### `bitwise-and-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
bitwise-and-prefix-context =
    equality-prefix-context | bitwise-and-expression , "&" , [ equality-prefix-context ] ;
```

### `equality-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
equality-expression =
    relational-expression , [ ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , relational-expression ] ;
```

### `equality-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
equality-prefix-context =
    relational-prefix-context | relational-expression , ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , [ relational-prefix-context ] ;
```

### `relational-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
relational-expression =
    pipe-expression , [ ( "<" | "<=" | ">" | ">=" ) , pipe-expression ] ;
```

### `relational-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
relational-prefix-context =
    pipe-prefix-context | pipe-expression , ( "<" | "<=" | ">" | ">=" ) , [ pipe-prefix-context ] ;
```

### `pipe-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
pipe-expression =
    concatenation-expression , { "|>" , concatenation-expression } ;
```

### `pipe-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
pipe-prefix-context =
    concatenation-prefix-context | pipe-expression , "|>" , [ concatenation-prefix-context ] ;
```

### `concatenation-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
concatenation-expression =
    shift-expression , { "." , shift-expression } ;
```

### `concatenation-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
concatenation-prefix-context =
    shift-prefix-context | concatenation-expression , "." , [ shift-prefix-context ] ;
```

### `shift-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
shift-expression =
    additive-expression , { ( "<<" | ">>" ) , additive-expression } ;
```

### `shift-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
shift-prefix-context =
    additive-prefix-context | shift-expression , ( "<<" | ">>" ) , [ additive-prefix-context ] ;
```

### `additive-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
additive-expression =
    multiplicative-expression , { ( "+" | "-" ) , multiplicative-expression } ;
```

### `additive-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
additive-prefix-context =
    multiplicative-prefix-context | additive-expression , ( "+" | "-" ) , [ multiplicative-prefix-context ] ;
```

### `multiplicative-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
multiplicative-expression =
    boolean-not-expression , { ( "*" | "/" | "%" ) , boolean-not-expression } ;
```

### `multiplicative-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
multiplicative-prefix-context =
    boolean-not-prefix-context | multiplicative-expression , ( "*" | "/" | "%" ) , [ boolean-not-prefix-context ] ;
```

### `power-expression` — GENERATE

Current and proposed/final (unchanged):

```ebnf
power-expression =
    clone-expression , [ "**" , power-expression ] ;
```

### `power-prefix-context` — GENERATE

Current and proposed/final (unchanged):

```ebnf
power-prefix-context =
    clone-prefix-context | clone-expression , "**" , { clone-expression , "**" } , [ clone-prefix-context ] ;
```

## Complete production-by-production disposition

Each original production appears exactly once below. KEEP means the original
body is retained, with no safe mechanical simplification identified; rationale
names the boundary or structure being preserved. M/S/G link to the detailed
decisions above, including before/after forms and supporting evidence.

| # | Production | Decision | Rationale / evidence family |
|---:|---|---|---|
| 1 | `source-file` | KEEP | Public root and source transition boundary. |
| 2 | `inline-html` | KEEP | Source-mode/primitive boundary; retain parser-facing name. |
| 3 | `top-statement-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 4 | `top-statement` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 5 | `attributed-top-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 6 | `whitespace` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 7 | `comment` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 8 | `line-comment` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 9 | `block-comment` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 10 | `doc-comment` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 11 | `identifier` | KEEP | Ordinary identifier token category; not qualified-name segments. |
| 12 | `identifier-start` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 13 | `identifier-part` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 14 | `identifier-start-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 15 | `variable` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 16 | `variable-variable` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 17 | `variable-like` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 18 | `label` | KEEP | Label/goto identifier attachment point. |
| 19 | `semi-reserved-identifier` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 20 | `name-identifier` | KEEP | Qualified-name segment primitive has distinct keyword handling. |
| 21 | `namespace-declaration-name` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 22 | `qualified-name` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 23 | `fully-qualified-name` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 24 | `namespace-relative-name` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 25 | `name` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 26 | `class-name` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 27 | `integer-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 28 | `decimal-integer-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 29 | `binary-integer-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 30 | `octal-integer-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 31 | `explicit-octal-integer-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 32 | `hexadecimal-integer-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 33 | `floating-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 34 | `exponent-part` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 35 | `decimal-digits` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 36 | `numeric-separator` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 37 | `string-literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 38 | `single-quoted-string` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 39 | `double-quoted-string` | SIMPLIFY | S: equivalent contents repetition. |
| 40 | `heredoc-string` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 41 | `nowdoc-string` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 42 | `encapsulated-string-part` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 43 | `encapsulated-variable` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 44 | `encapsulated-offset` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 45 | `numeric-string` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 46 | `literal` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 47 | `magic-constant` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 48 | `binary-digit` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 49 | `octal-digit` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 50 | `decimal-digit` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 51 | `decimal-digit-nonzero` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 52 | `hexadecimal-digit` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 53 | `exponent-marker` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 54 | `ascii-letter` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 55 | `type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 56 | `optional-type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 57 | `type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 58 | `simple-type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 59 | `simple-type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 60 | `nullable-type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 61 | `nullable-type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 62 | `union-type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 63 | `union-type-element` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 64 | `union-type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 65 | `union-type-without-static-element` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 66 | `intersection-type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 67 | `intersection-type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 68 | `parenthesized-intersection-type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 69 | `parenthesized-intersection-type-without-static` | KEEP | Parser-level exclusion of static avoids modifier conflicts. |
| 70 | `return-type` | KEEP | Type/DNF structure, punctuation and return-context boundary. |
| 71 | `constant-expression` | KEEP | Ordered contextual constant validation attachment point. |
| 72 | `expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 73 | `logical-or-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 74 | `logical-xor-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 75 | `logical-and-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 76 | `assignment-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 77 | `assignment-operator` | KEEP | Named operator set or token category; preserve precedence/lexical references. |
| 78 | `conditional-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 79 | `coalesce-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 80 | `boolean-or-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 81 | `boolean-and-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 82 | `bitwise-or-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 83 | `bitwise-xor-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 84 | `bitwise-and-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 85 | `equality-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 86 | `relational-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 87 | `pipe-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 88 | `concatenation-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 89 | `shift-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 90 | `additive-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 91 | `multiplicative-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 92 | `power-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 93 | `instanceof-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 94 | `unary-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 95 | `cast-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 96 | `void-cast-statement` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 97 | `postfix-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 98 | `fully-dereferenceable-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 99 | `callable-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 100 | `primary-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 101 | `variable-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 102 | `member-name` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 103 | `object-operator` | KEEP | Named operator set or token category; preserve precedence/lexical references. |
| 104 | `nullsafe-object-operator` | KEEP | Named operator set or token category; preserve precedence/lexical references. |
| 105 | `constant` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 106 | `class-constant` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 107 | `class-name-reference` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 108 | `function-call` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 109 | `argument-list` | KEEP | Ordinary call versus first-class callable syntax. |
| 110 | `clone-argument-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 111 | `argument` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 112 | `argument-no-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 113 | `expression-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 114 | `array-creation-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 115 | `array-pair-list` | KEEP | Nullable slots and commas encode destructuring holes; not X-star. |
| 116 | `array-pair` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 117 | `list-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 118 | `long-list-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 119 | `include-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 120 | `match-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 121 | `match-arm-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 122 | `match-arm` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 123 | `match-arm-condition-list` | KEEP | Match-condition list role, despite expression-list equivalence. |
| 124 | `closure-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 125 | `arrow-function` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 126 | `lexical-variable-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 127 | `lexical-variable` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 128 | `backtick-string-part-list` | KEEP | Backtick scanner state despite shared contents. |
| 129 | `statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 130 | `compound-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 131 | `inner-statement-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 132 | `inner-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 133 | `inner-declaration` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 134 | `expression-statement` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 135 | `echo-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 136 | `statement-terminator` | KEEP | Source-mode close-tag adaptation attachment point. |
| 137 | `global-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 138 | `global-variable-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 139 | `global-variable` | KEEP | Global variable target category. |
| 140 | `static-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 141 | `static-variable-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 142 | `static-variable` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 143 | `unset-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 144 | `unset-variable-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 145 | `unset-variable` | KEEP | Unset surviving-target validation attachment point. |
| 146 | `return-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 147 | `break-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 148 | `continue-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 149 | `goto-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 150 | `label-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 151 | `empty-statement` | KEEP | Distinct statement AST role despite the semicolon spelling. |
| 152 | `if-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 153 | `alt-elseif-list` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 154 | `alt-else-clause` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 155 | `while-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 156 | `do-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 157 | `for-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 158 | `for-expression-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 159 | `nonempty-for-expression-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 160 | `for-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 161 | `for-condition-expression-list` | KEEP | Last condition excludes void; preceding nonempty list matters. |
| 162 | `foreach-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 163 | `foreach-target` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 164 | `foreach-key` | KEEP | Key-target contextual validation attachment point. |
| 165 | `foreach-value` | KEEP | Value/reference contextual validation attachment point. |
| 166 | `foreach-variable` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 167 | `switch-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 168 | `switch-case-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 169 | `switch-case` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 170 | `declare-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 171 | `declare-directive-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 172 | `declare-directive` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 173 | `try-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 174 | `catch-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 175 | `catch-clause` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 176 | `catch-type-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 177 | `finally-clause` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 178 | `function-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 179 | `parameter-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 180 | `parameter` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 181 | `parameter-modifiers` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 182 | `parameter-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 183 | `class-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 184 | `anonymous-class` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 185 | `class-modifiers` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 186 | `class-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 187 | `extends-clause` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 188 | `implements-clause` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 189 | `name-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 190 | `class-member-list` | KEEP | Shared nullable parser member list. |
| 191 | `class-member` | KEEP | Shared parser alternatives; declaration-kind legality stays contextual. |
| 192 | `property-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 193 | `property-modifier-list` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 194 | `property-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 195 | `property-element` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 196 | `hooked-property` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 197 | `property-hook-block` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 198 | `property-hook-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 199 | `property-hook` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 200 | `property-hook-body` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 201 | `property-hook-modifiers` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 202 | `property-hook-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 203 | `property-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 204 | `property-visibility-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 205 | `set-visibility-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 206 | `method-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 207 | `method-modifiers` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 208 | `method-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 209 | `method-body` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 210 | `class-constant-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 211 | `class-constant-modifiers` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 212 | `class-constant-modifier` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 213 | `class-constant-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 214 | `class-constant-element` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 215 | `constant-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 216 | `constant-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 217 | `constant-element` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 218 | `interface-declaration` | SIMPLIFY | M: directly select shared member list. |
| 219 | `interface-extends-clause` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 220 | `interface-member-list` | INLINE | M: remove redundant shared-member wrapper. |
| 221 | `interface-member` | INLINE | M: remove redundant shared-member wrapper. |
| 222 | `trait-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 223 | `trait-use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 224 | `trait-adaptation-block` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 225 | `trait-adaptation` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 226 | `trait-precedence` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 227 | `trait-alias` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 228 | `trait-method-reference` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 229 | `enum-declaration` | SIMPLIFY | M: directly select shared member list. |
| 230 | `enum-backing-type` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 231 | `enum-member-list` | INLINE | M: remove redundant shared-member wrapper. |
| 232 | `enum-member` | INLINE | M: remove redundant shared-member wrapper. |
| 233 | `enum-case` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 234 | `namespace-definition` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 235 | `namespace-use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 236 | `use-type` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 237 | `use-declaration-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 238 | `use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 239 | `legacy-namespace-name` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 240 | `group-use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 241 | `mixed-group-use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 242 | `unprefixed-use-declaration-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 243 | `unprefixed-use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 244 | `inline-use-declaration-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 245 | `inline-use-declaration` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 246 | `attribute-groups` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 247 | `attribute-group` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 248 | `attribute-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 249 | `attribute` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 250 | `halt-compiler-statement` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 251 | `inline-html-text` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 252 | `line-comment-text` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 253 | `block-comment-text` | KEEP | Comment scanner state and closing-delimiter category. |
| 254 | `doc-comment-text` | KEEP | Doc-comment scanner category; preserve primitive/trivia evidence. |
| 255 | `single-quoted-string-content` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 256 | `string-text` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 257 | `heredoc-label` | KEEP | Scanner label-equality/closing-lookahead attachment point. |
| 258 | `heredoc-body` | KEEP | Heredoc state/indentation boundary despite shared contents. |
| 259 | `nowdoc-body` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 260 | `halt-compiler-data` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 261 | `source-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 262 | `inline-html-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 263 | `line-comment-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 264 | `block-comment-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 265 | `single-quoted-string-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 266 | `string-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 267 | `nowdoc-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 268 | `escape-sequence` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 269 | `whitespace-character` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 270 | `non-ascii-byte` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 271 | `reserved-non-modifiers` | KEEP | Target-specific parser-action modifier category and list cardinality. |
| 272 | `boolean-not-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 273 | `clone-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 274 | `dereferenceable-scalar` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 275 | `new-dereferenceable` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 276 | `array-object-dereferenceable` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 277 | `callable-variable` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 278 | `static-member` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 279 | `new-variable` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 280 | `property-name` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 281 | `backtick-string` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 282 | `newline` | KEEP | Lexical/name/literal category, scanner state or token-boundary evidence. |
| 283 | `throw-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 284 | `arrow-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 285 | `print-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 286 | `yield-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 287 | `yield-from-expression` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 288 | `parameter-default` | KEEP | Parameter-specific constant validation attachment point. |
| 289 | `property-default` | KEEP | Property-specific constant validation attachment point. |
| 290 | `class-constant-initializer` | KEEP | Class constant validation attachment point. |
| 291 | `global-constant-initializer` | KEEP | Global constant validation attachment point. |
| 292 | `enum-case-initializer` | KEEP | Enum case validation attachment point. |
| 293 | `trait-alias-modifier` | KEEP | Trait alias target/folding boundary despite shared method syntax. |
| 294 | `ordinary-argument-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 295 | `first-class-callable-arguments` | KEEP | Declaration/member/import/attribute syntax role and contextual attachment point. |
| 296 | `constructor-argument-list` | KEEP | Constructor callable/argument contextual boundary. |
| 297 | `isset-variable-list` | KEEP | Named list preserves cardinality, separators, optional tails and coverage anchors. |
| 298 | `isset-variable` | KEEP | Expression parsing followed by surviving-target validation. |
| 299 | `matched-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 300 | `simple-statement` | KEEP | Statement/control-flow shape, delimiters and scope/binding boundary. |
| 301 | `unmatched-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 302 | `matched-if-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 303 | `unmatched-if-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 304 | `alternative-if-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 305 | `closed-inner-statement-list` | KEEP | Required closed final statement propagates dangling-else binding. |
| 306 | `matched-while-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 307 | `unmatched-while-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 308 | `matched-for-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 309 | `unmatched-for-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 310 | `matched-foreach-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 311 | `unmatched-foreach-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 312 | `matched-declare-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 313 | `unmatched-declare-statement` | KEEP | Nearest-else/elseif binding and closed/open propagation. |
| 314 | `arrow-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 315 | `include-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 316 | `logical-or-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 317 | `logical-xor-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 318 | `logical-and-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 319 | `print-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 320 | `yield-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 321 | `yield-from-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 322 | `assignment-prefix` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 323 | `assignment-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 324 | `conditional-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 325 | `coalesce-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 326 | `boolean-or-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 327 | `boolean-and-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 328 | `bitwise-or-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 329 | `bitwise-xor-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 330 | `bitwise-and-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 331 | `equality-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 332 | `relational-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 333 | `pipe-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 334 | `concatenation-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 335 | `shift-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 336 | `additive-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 337 | `multiplicative-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 338 | `boolean-not-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 339 | `instanceof-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 340 | `unary-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 341 | `power-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 342 | `clone-prefix-context` | KEEP | Incomplete-prefix recursion/operand structure; no precedence-only rewrite. |
| 343 | `arrow-function-header` | KEEP | Expression/call/dereference operand shape and contextual target boundary. |
| 344 | `include-operator` | KEEP | Named operator set or token category; preserve precedence/lexical references. |
| 345 | `unary-operator` | KEEP | Named operator set or token category; preserve precedence/lexical references. |
| 346 | `cast-operator` | KEEP | Named operator set or token category; preserve precedence/lexical references. |
| 347 | `yield-key-expression` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 348 | `yield-key-prefix-context` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 349 | `closed-yield-throw-expression` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 350 | `closed-yield-arrow-expression` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 351 | `closed-yield-arrow-prefix-context` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 352 | `closed-yield-include-expression` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 353 | `closed-yield-include-prefix-context` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 354 | `closed-yield-logical-or-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 355 | `closed-yield-logical-or-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 356 | `closed-yield-logical-xor-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 357 | `closed-yield-logical-xor-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 358 | `closed-yield-logical-and-expression` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 359 | `closed-yield-logical-and-prefix-context` | GENERATE | G: exact expression/prefix template; preserve all derivations. |
| 360 | `closed-yield-print-expression` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 361 | `closed-yield-print-prefix-context` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 362 | `closed-yield-yield-expression` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 363 | `closed-yield-yield-prefix-context` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |
| 364 | `yield-key` | KEEP | Closed-yield/key recursion determines which yield owns =>; special form stays explicit. |

## Considered and rejected

- Collapsing `type-without-static` into `type`: changes parser conflicts and
  structural acceptance; static exclusion is not just a compiler restriction.
- Collapsing matched/unmatched or `closed-inner-statement-list`: loses dangling
  else/elseif propagation through loops and declare. Keep all original bodies.
- Inlining constant defaults/initializers, isset/foreach/global/unset targets,
  labels, and heredoc labels: discards useful contextual or lexical boundaries.
- Inlining `constructor-argument-list`, `match-arm-condition-list`, and
  `trait-alias-modifier`: equal syntax does not imply equal compiler context.
- Unifying identifier/name primitives or doc/block comment text: scanner
  categories and primitive-bypass evidence distinguish apparently equal bodies.
- Combining heredoc/backtick contents: scanner state and delimiter requirements
  remain separately named even when repeated elements coincide.
- Converting separated lists, trailing-comma lists, `array-pair-list`, or
  nonempty repetitions to zero-or-more: changes empty slots or cardinality.
- Replacing prefix contexts with conventional precedence: breaks pending
  assignments and instanceof/power interactions. Generation copies existing
  audited forms only; special assignment, instanceof and unary cases stay explicit.
- Generating the rest of the closed-yield family now: the remaining rules
  cross yield-key boundaries, include a bare-yield endpoint, and specialize
  arrow/throw/include/print recursion. A second template framework would add
  complexity without the clear repetition benefit of the six logical rules.
  Keep all nine nonbinary closed-yield productions and `yield-key` explicit.
- Unifying modifier targets: changes parser-action validation ownership.

## Validation results

Executed on 2026-09-17 using PHP **8.5.10**, ext-ast **1.1.3**, and
PHPUnit **11.5.56**, with `PHPRC=.audit/phase5.ini` and `zend.multibyte=0`.
Both short-open-tag profiles were exercised. All **23 baseline release gates**
passed before the edits; all **24 final release gates** passed after them,
including the new expression-generation freshness gate.

| Evidence | Before | After / result |
|---|---:|---:|
| Canonical productions | 364 | 360 |
| Nullable productions | 28 | 26; only the two removed member-list aliases disappear |
| PHPUnit tests | 5,108 | 5,181 passed |
| PHPUnit assertions | 48,147 | 49,056 passed |
| New focused simplification tests | — | 73 tests, 913 assertions |
| Valid whole-source fixtures | 653 | 653 accepted |
| Structural-negative fixtures | 362 | 362 rejected |
| Contextual-negative fixtures | 356 | 356 structurally accepted and rejected by PHP compilation |
| Ordinary differential mismatches | 0 | 0 across 1,371 fixtures |
| Explicit AST witnesses | 63 positive + 10 negative | Same; zero failures |
| Operator/statement matrix | 15,342 | Same; zero acceptance/derivation/span/fold/binding failures |
| Declaration-folding comparisons | 1,840 | Same; zero failures |
| Phase 6 matrices | 860 | Same; 70 direct-folding, 584 modifier, 34 ambiguity, 156 recursive, 16 malformed cases |
| Scanner/parser product | 482 | Same; zero failures |
| Direct scanner cases | 2,700 | Same; zero failures |
| Lexical primitive cases | 101 | Same; zero failures |
| Scanner syntax cases | 144 | Same; zero failures |
| Bounded interpolation binding | 44 positive, 6 malformed, 54 operand comparisons | Same; zero failures |
| Diagnostic witnesses | 20 sites, 40 comparisons | Same; zero failures |
| Positive production coverage | 301 / 364 | 297 / 360; exactly four removed names |
| Positive alternative coverage | 606 / 790 | 606 / 790 |
| Unclassified meaningful coverage gaps | 0 | 0 |
| Markdown EBNF parity | Exact | Exact |

The seven behavioral JSON reports (`systematic-structure`, `phase6-matrices`,
`phase6-boundary-folding`, `scanner-product`, `phase5-lexical-evidence`,
`interpolation-binding`, `diagnostic-witnesses`) were compared recursively to
their pre-edit versions. They are **identical after excluding updated input
digests for the canonical grammar and derived coverage/source inventory**;
other hashes and all case records remain identical. Ordinary
differential command output is byte-for-byte identical. The existing deliberate
type/name helper ambiguity remains present; no unexpected duplicate derivation
was introduced. Yield-key and matched/unmatched regressions pass within PHPUnit
and the operator/statement matrix.

Derived coverage and source correspondence remove the four obsolete names.
The enum-semicolon negative witness now anchors `class-member-list`, reducing
distinct negative anchors from 123 to 122 while retaining all 272 boundary pairs.
That shared anchor also indexes the same existing witness in the modifier
evidence family. No fixture expectation or structural/contextual classification
was changed to make validation pass.

Final execution used every command from `tools/grammar-release.py`, with report
regeneration in dependency order and PHPUnit last so its evidence-hash checks
see the refreshed reports. Initial freshness checks found stale lexical-ledger
digests, first for the grammar and then for the subsequently regenerated source
inventory and coverage report. `bin/lexer-coverage.php --write`, Phase 6 evidence,
and certification were regenerated in dependency order; all three checks and
the entire PHPUnit command were rerun successfully. Composer's default
300-second process timeout interrupted one rerun; the successful run used
`COMPOSER_PROCESS_TIMEOUT=0`. No lexical case had changed.
All gates passed on their final run. Local execution logs are in
`.audit/simplification-baseline/` and `.audit/simplification-validation/`, including
the original failure, successful retry, final exit-code inventory and behavioral
comparison result. The ordinary reproducible release entry point remains
`composer release:check` with the prerequisites in the repository README.

| Final release gate | Result |
|---|---|
| `composer-validate` | PASS |
| `expression-generation` | PASS |
| `grammar-coverage` | PASS |
| `lexer-coverage` | PASS |
| `ordinary-differential` | PASS |
| `explicit-ast` | PASS |
| `systematic-structure` | PASS |
| `declaration-folding` | PASS |
| `phase6-matrices` | PASS |
| `phase6-matrix-freshness` | PASS |
| `scanner-product` | PASS |
| `lexer-short-enabled` | PASS |
| `lexer-short-disabled` | PASS |
| `positive-report-freshness` | PASS |
| `negative-report-freshness` | PASS |
| `compiler-source-hashes` | PASS |
| `parser-reconciliation` | PASS |
| `phase6-evidence-freshness` | PASS |
| `interpolation-binding` | PASS |
| `diagnostic-predicates` | PASS |
| `source-correspondence` | PASS |
| `final-certification` | PASS |
| `phpunit` | PASS |
| `diff-whitespace` | PASS |

Conformance behavior is unchanged within the existing bounded evidence, and
the restricted rewrites have the equivalence arguments above. This does not
upgrade the repository's claim to exhaustive PHP equivalence or complete
contextual validation. Scanner/source, structural EBNF, and contextual/compiler
constraints remain separate, with the same C1–C4 limits.
