# PHP 8.5 remaining-audit corrections

Historical audit snapshot. The [parser/compiler boundary remediation](php85-parser-compiler-remediation.md) supersedes its outstanding-gap descriptions and counts.

The audited expression/statement ambiguities, interface `var` properties,
argument categories, constant-expression folding and scanner boundaries are
corrected. **Full PHP 8.5 conformance is not established:** two confirmed
discarded-declaration discrepancies remain.

Grammar Completeness [Phase 4](php85-phase4-negative-coverage.md) now adds 272
nearby-invalid/repair pairs and reviews all contextual negatives. Its current
validation counts supersede the historical counts below: 917 ordinary
differential fixtures, zero unexpected mismatches, and the same two separately
reported discrepancies. The EBNF and its positive coverage totals are unchanged.

The two gaps were reverified against the same source hashes and PHP 8.5.10.
`enum_backing_type` accepts `type_expr`; `zend_compile_enum_backing_type` later
limits it to int/string. Trait aliases accept method-target modifiers in the
parser; `zend_check_trait_alias_modifiers`, called during alias compilation,
rejects static/abstract. Readonly fails earlier modifier conversion. Phase 4
adds live enum object/union and static/abstract/readonly alias regressions while
retaining the discarded-closure discrepancy fixtures. The complete live/dead
distinction belongs to contextual declaration validation; it is not resolved
by adding an unconditional EBNF restriction.

## Evidence

The source pin remains `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.
SHA-256 checks passed for `zend_language_parser.y`, `zend_language_scanner.l`
and `zend_compile.c` against `tools/php85-source-lock.json`. The executable
oracle is PHP 8.5.10, not a build of the exact pin. The regenerated inventory
contains 177 parser productions, 190 scanner rule entries, 143 selected compiler
functions and 366 canonical EBNF productions. Every version remains standalone.

## Productions and contextual decisions

| Request | Result and pinned evidence |
|---|---|
| Precedence | Removed `prefix-expression` / `prefix-unary-expression` escape paths. Layered expressions and nonempty `*-prefix-context` productions retain mixed-prefix syntax without duplicate high-precedence paths. `!` is below instanceof, unary/casts/silence below power, power/coalesce recurse right, and clone retains its top level. Source: precedence declarations and `expr`. |
| Nested yield | `yield-key-expression`, `yield-key`, and `closed-yield-*` assign `=>` to the nearest eligible yield, including nested keyed/unkeyed yields and low-prefix keys. Source: three yield alternatives and T_YIELD/T_DOUBLE_ARROW precedence. |
| Dangling else | Matched/unmatched if and loop/declare bodies encode nearest-if binding for both else and elseif. Braces, alternative terminators and do-while close propagation. Source: `if_stmt_without_else`, `if_stmt`, T_NOELSE/T_ELSEIF/T_ELSE. |
| Interface var | `interface-property-declaration` accepts `var`; contextual rules require public/var hooked properties without defaults or hook implementations. Source: `class_statement`, `property_modifiers`, `zend_compile_prop_decl`. |
| Callable conversion | Separate ordinary, callable-conversion and constructor argument productions. Constructor/nullsafe-chain conversion is forbidden when compilation reaches it. Normal function/object/static and clone conversion remain valid. Sources: argument/constructor/clone parser lists, `zend_compile_new`, `zend_compile_call_common`. |
| isset | Dedicated list/element productions mirror Zend. The pinned `isset_variable` is **expr**, not variable. `zend_compile_isset_or_empty` requires VAR/DIM/PROP/NULLSAFE_PROP/STATIC_PROP: direct calls/arithmetic fail; call-result offsets/properties can qualify. |
| Empty statements | Expression statements require an expression. A standalone semicolon, including the token supplied by a close tag, derives only as `empty-statement`. |
| Throw statements | Removed `throw-statement`; `throw $e;` derives through expression-statement and throw-expression, matching `statement: expr ';'`. |
| Trait aliases | Dedicated public/protected/private/final modifier. Static/abstract fail `zend_compile_trait_alias`; readonly fails parser modifier-target conversion. Only one modifier is permitted. See the discarded-closure limitation below. |
| Constants | `constant-expression = expression` replaces the incomplete source subset. Markdown specifies folding, surviving-AST validation, static-name checks, closure-body compilation and allow_dynamic propagation in order. Source: compiler 11318–11649 and 12079–12383. |
| Ternary folding | Syntax encodes left association; runtime full/mixed-chain restrictions are contextual. Constant folding can discard a chain before `zend_compile_conditional` checks it. |
| Array pairs | Reconciliation exposed leading omitted destructuring elements and duplicate trailing-comma/short-list derivations. Optional elements now mirror `possible_array_pair`; explicit nested list syntax uses `long-list-expression`. Array-value omissions remain contextual errors. |

An unconditional structural ban on `isset(1 + 2)` or `new Foo(...)` conflicts
with PHP's accepted `const X = true ? 1 : ...;` forms. These restrictions are
therefore contextual, with discarded-arm positive and live-arm negative
fixtures. This is a source-confirmed departure from the request's structural
assumption. Seven original constant-operation negatives and the ternary-chain
negative moved to `contextual-invalid`; they still fail PHP lint.

The standalone Markdown keeps the three layers: lexical/source rules,
canonical EBNF, and mandatory contextual syntax. It documents surviving static
noncapturing closures/FCC, casts, new/object-cast modes, folded names, dead
subexpressions, property/class/enum initializers, defaults, globals and attributes.

## Scanner and parser review

The state contract now covers INITIAL/SHEBANG, scripting, string/backtick,
heredoc/nowdoc, varname, property lookup, numeric offset and end-heredoc states.
`Lexer::interpolationEnd` recursively scans scripting tokens; `StringSyntax`
no longer guesses nested boundaries by skipping quotes. Regressions cover
nested interpolation/comments/heredocs, labels inside nested strings, outer
indentation excluding scripting lines, property `#[` comments, keyword varnames,
tags inside interpolation closures, both short-tag profiles, shebang comments,
numeric offset spellings, qualified names and case-insensitive yield-from.

Production-family comparison covered expressions/statements, declarations,
parameters/types, argument/clone lists, dereference categories, array pairs,
interpolation and imports. All production names/rule entries are inventoried;
this is not an exhaustive equivalence proof. Scanner token abstractions and
deferred contextual/name/type/value validation remain explicit boundaries.

## Tests and validation

`Php85ParseStructureTest` uses a separate test-only Earley derivation forest.
It asserts one tree and expected operand spans for the requested operators,
mixed prefixes, nested yields, dangling else, empty/throw statements and arrays.
Nonassociative chains have zero trees. A deliberately ambiguous toy grammar
verifies that the helper detects two derivations. Its lexical primitives are
limited to the targeted snippets; it is not a general AST/source API.

The ordinary fixture corpus and known discrepancies are independently checked
against PHP 8.5.10. EBNF integrity, Markdown parity, PHPUnit and coverage are
rerun. Generated coverage records recognition evidence, not AST equivalence.
The regenerated ledger covers 303/366 productions and 609/794 alternatives;
the remainder is explicitly classified as lexical/trivia/contextual-only,
with no unclassified meaningful coverage gap.

Final verification on 2026-09-11:

- PHPUnit: **863 tests, 2,163 assertions**, passing on PHP 8.4.22.
- PHP 8.5.10 differential lint: **225 valid, 85 structural-invalid,
  63 contextual-invalid**, zero unexpected mismatches; **two known
  discrepancies** printed separately. Both short-tag profiles are included.
- Added **101 fixture files**, including the two known discrepancies;
  reclassified eight existing negatives without changing their rejection by PHP.
- All **366 productions** pass integrity validation, including duplicate,
  undefined-reference, reachability and nullable-production checks.
- Generated EBNF/Markdown parity, coverage generation, source-pin hashes,
  coverage grammar hash and `git diff --check` pass.
- Comparing the test forest against the original HEAD grammar detects multiple
  derivations for `!$x instanceof Foo`, `print $x = 1`, `throw $a ?? $b`,
  dangling else, empty statements and throw statements. The corrected grammar
  produces exactly one for each. Counts are capped at three; a cap is not
  reported as an exact ambiguity count.

Reproduction commands:

```text
rtk proxy php vendor/phpunit/phpunit/phpunit --no-progress
rtk proxy php bin/php85-conformance.php .audit/php85/php.exe
rtk proxy php tools/php85-coverage-report.php
rtk proxy python tools/php85-source-inventory.py .audit
rtk git diff --check
```

## Remaining discrepancies

PHP accepts these after discarding the closure, but the EBNF rejects the
declarations before folding:

```php
const X = true ? 1 : static function() { enum E: object {} };
const X = true ? 1 : static function() {
    class C { use T { foo as static bar; } }
};
```

They live in `tests/fixtures/php/8.5/known-discrepancies/valid/`. The lint runner
prints/counts them separately; zero unexpected mismatches does not hide them.
Constant folding versus restricted nested declarations remains incompletely
reconciled, so that discrepancy note is retained. Mandatory contextual
validation is still documentation plus differential tests, not a complete
repository-owned compiler.

Final assessment: source-traceable corrections and unique targeted derivations;
**full/canonical PHP 8.5 conformance is not established**.
