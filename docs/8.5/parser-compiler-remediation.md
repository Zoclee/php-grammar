# PHP 8.5 parser/compiler boundary remediation

Current status: the [subsequent remediation audit](remediation-audit.md) resolves the discarded-unset, for-condition, assignment-prefix and instanceof-power defects, expands the boundary matrix to 114 families, and supplies systematic AST and scanner-product comparisons. Counts and unresolved findings below describe the historical stage unless explicitly updated.

Phase 5 update: the declaration and AST decisions below remain in force.
The [scanner audit](phase5-lexer-audit.md) now adds direct evidence for all
190 scanner rules and records one new folding discrepancy, `false && (unset) 1;`.
The historical statement below about zero recorded discrepancies describes this
report's original validation, not the current corpus. Current counts and the
corrected scanner-sensitive coverage classification are in the Phase 5 report.

The confirmed declaration-boundary mismatches are corrected. The ordinary
fixture corpus now agrees with PHP 8.5.10, including the two formerly isolated
discarded-closure witnesses. A newly discovered alternative-if binding error
is also corrected. **Full PHP 8.5 conformance remains unproven.**

This report supersedes the outstanding-gap descriptions and current counts in
the historical remaining-audit and Phase 4 reports. It preserves the three
layers: lexical/source contract, canonical syntactic EBNF, and contextual
constraints. Contextual checks are ordered: parser actions run before folding;
body compilation runs only if the enclosing declaration survives.

## Sources and review scope

The source pin remains `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`:

- [Parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y)
- [Scanner](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l)
- [Compiler](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c)

All three SHA-256 hashes were verified against `tools/php85-source-lock.json`.
The [source inventory](source-inventory.json) contains 177 parser
productions, 190 scanner rules, and 143 selected compiler functions.
The new [fatal diagnostic inventory](compiler-boundaries.json) records
244 direct fatal diagnostic sites in 87 functions, including 14 sites in
parser-reachable helpers. Constant-folding failures and implementation resource
limits are classified separately. It includes locations and diagnostics, not
just a list of compiler function names.

The source-driven review covered all recorded diagnostic sites by category:
names/scopes, write contexts, calls/arguments, control flow, types, parameters,
captures, methods, hooks, properties, constants, enums, traits, declarations,
namespaces/imports, constant folding and constant-expression validation.
Defensive downstream checks do not imply parser acceptance: for example,
readonly methods already fail target conversion in a parser action, and the
scanner rejects the removed real-cast form in parser mode. Correction from
Phase 5: the scanner does still supply `T_UNSET_CAST`; the compiler rejects
surviving unset casts, and short-circuit folding can discard one. Direct diagnostic-site
enumeration is not a proof that all indirect, deferred or environment-dependent
validation paths have been covered by fixtures.

## Structural corrections and contextual rules

| Productions | Parser evidence | Contextual validation |
|---|---|---|
| `class-member`, `interface-member`, `enum-member` | `class_statement_list`, `class_statement`, `attributed_class_statement`, lines 973-1006 | `zend_compile_prop_decl`, `zend_compile_enum_case`, `zend_compile_use_trait`, `zend_begin_method_decl`: declaration-kind restrictions on properties, cases, trait use, method visibility and bodies. |
| `enum-backing-type` | `enum_backing_type: ':' type_expr`, 655-657 | `zend_compile_enum_backing_type`: backing type must be int/string when compiled. |
| `trait-alias-modifier` | `trait_alias`, 1036-1052, method-target modifier conversion | `zend_check_trait_alias_modifiers`: static/abstract aliases fail compilation. Readonly/set-visibility aliases still fail parser actions. |
| `property-hook-list`, `property-hook` | `property_hook_list`, `property_hook`, `optional_parameter_list`, 1136-1177 | `zend_compile_property_hooks`: nonempty valid hook kinds, uniqueness, parameter shape, visibility, bodies and finality. |
| `try-statement` | `statement`, `catch_list`, `finally_statement`, 540-566 | `zend_compile_try`: at least one catch/finally when compiled. |
| `attribute` | `attribute_decl` uses normal `argument_list`, 367-371 | `zend_compile_attributes`: unpacking, callable conversion, ordering, duplicate names and surviving constant expressions. |
| `attributed-top-declaration` | `attributed_top_statement` uses `const_list`, 398-406 | `zend_compile_const_decl`, 9718-9725: one constant per attributed global declaration. |
| `name-list`, `catch-type-list` | `class_name_list`, `catch_name_list` consume `class_name`, including static | Class-name target/scope checks occur during compilation. |
| `alternative-if-statement`, `alt-elseif-list`, `closed-inner-statement-list`, `inner-declaration` | `if_stmt`, `alt_if_stmt`, T_NOELSE/T_ELSEIF/T_ELSE precedence, 761-789 | Structural nearest-if binding must hold before an alternative-syntax colon. |

Ordinary and hooked property syntax, method bodies, class constant lists,
attributes and enum cases now share the same class-member structure for all
class-like declaration kinds. A property in an enum or case in a non-enum is
structurally accepted but rejected if compilation reaches it.

Hook modifiers remain final-only because the broader Bison member-modifier
nonterminal is immediately filtered by a parser action. General hook names,
empty lists, optional/general parameter lists and both reference-return markers
are retained structurally. Hook bodies keep the three parser forms: semicolon,
compound body and expression body. Parameter hooks themselves trigger promotion;
explicit promotion modifiers are not required in a concrete constructor.

Five obsolete specialized helpers were removed: `interface-property-declaration`,
`interface-property-modifiers`, `constant-argument-list`, `constant-argument`,
and `attribute-or-constructor-argument`. Consumers referencing those production
names must migrate to shared property/argument productions. This is a structural
API correction, not merely an editorial change. The canonical file remains
complete and standalone, with 363 productions.

## Newly confirmed boundary cases

- `static` is parser-valid in implements/interface-extends/trait-use/insteadof
  class-name lists and catch type lists; the EBNF previously omitted it.
- Attributed global constant lists are parser-valid. They cannot be placed in
  a closure because global const declarations are not `inner_statement` forms;
  a discarded-closure counterpart would itself be a parser error.
- `&set {}` is accepted by the pinned parser/compiler and PHP 8.5.10, including
  execution of an isolated declaration. It emits a void-reference-return
  deprecation. The previous unconditional ban was incorrect. A set **parameter**
  still cannot be passed by reference.
- Parser-action modifier failures cannot be discarded. Duplicate modifiers,
  abstract/final conflicts and anonymous-class modifiers require early checks
  even where the EBNF's repetition structurally accepts them.
- `if ($a): if ($b) foo(); elseif ($c): bar(); endif;` is rejected by Zend.
  The elseif shifts toward the inner unmatched if, where a colon cannot begin
  its body. The previous EBNF incorrectly accepted it as an outer alternative
  elseif. Before `else:`/`elseif:`, a nonempty alternative body now ends in a
  matched statement or declaration. `endif` does not impose this restriction.

## Fixtures and independent checks

The [boundary matrix](../../tests/fixtures/php/8.5/parser-compiler-boundaries.json)
contains 85 families with separate live, retained-static-closure and discarded
ternary fixtures: 84 compiler-invalid families and the accepted `&set` exception.
Each family records its parser/compiler evidence. There are 255 matrix PHP
fixtures, 24 early parser-action controls, 20 alternative-if negative/repair
fixtures, and four further constant/promotion/closed-list fixtures: **303 new
PHP fixture files**. Fourteen existing structural negatives were reclassified
as contextual negatives, and two known discrepancies moved into ordinary valid
fixtures. The obsolete test asserting that those discrepancies must fail was
replaced with a matrix-consistency test.

The folding runner checks all 85 families across six discarding and seven
retaining forms: ternary, symbolic/word AND/OR, coalesce, and non-discarding xor.
It checks both structural acceptance and PHP lint. It does not assume that
AND/OR visit children in the same order as ternary/coalesce; the normative
folding traversal in `php.md` is preserved.

The [structure matrix](../../tests/fixtures/php/8.5/parser-structure.json) supplies
59 expressions/statements and explicit parenthesized/braced equivalents.
`ext-ast` compares the actual Zend ASTs; only line metadata, declaration IDs and
the explicit-parentheses marker on conditional nodes are normalized away.
Operator flags and child order are preserved. The independent EBNF derivation
forest checks uniqueness and operand spans for the same sources. Ten negative
alternative-if cases require both Zend parser failure and no EBNF derivation.
The forest now splits Zend's composite yield-from token to match the documented
EBNF token adapter.

The stateful scanner architecture is unchanged. The complete differential corpus
and scanner tests continue exercising shebang/tags and both short-tag profiles,
nested interpolation/scripting/comments, varname/property/offset states,
heredoc/nowdoc, yield-from, ampersand lookahead, numeric offsets and keyword/name
classification. Numeric overflow categories and composite/string token
abstractions remain documented; scanner equivalence is not inferred from counts.

## Validation on 2026-09-12

| Check | Result |
|---|---|
| PHPUnit, PHP 8.5.10 | 1,774 tests, 7,788 assertions; pass |
| Formal EBNF integrity | All 363 productions pass syntax, references, reachability and allowed-nullability checks |
| Generated EBNF/Markdown parity | Pass |
| Ordinary PHP 8.5.10 differential corpus | 599 valid, 347 structural-negative, 276 contextual-negative; 0 mismatches |
| Recorded acceptance discrepancies | 0; former witnesses now pass ordinarily |
| Folding matrix | 1,105 comparisons; 0 failures |
| Zend AST grouping | 59 positive, 10 negative; 0 failures |
| Positive coverage | 300/363 productions, 601/785 alternatives; no unclassified meaningful gaps |
| Pinned source hashes and diagnostic inventory | Pass |

The CLI oracle is PHP 8.5.10, not a build of the exact source pin. AST observations
use ext-ast 1.1.3, schema 120, with the PHP 8.5 NTS x64 Windows build from
`https://downloads.php.net/~windows/pecl/releases/ast/1.1.3/php_ast-1.1.3-8.5-nts-vs17-x64.zip`
(archive SHA-256 `781fdf612e1890b82c06bcb587e34d8876946ff7bc72b30cdc88bc7411a783e2`).
The extension observes Zend's parser; it does not supply a third-party grammar.

Reproduction, with PHP 8.5 and required PHPUnit extensions available:

```text
rtk proxy php vendor/phpunit/phpunit/phpunit --no-progress
rtk proxy php bin/php85-conformance.php /path/to/php-8.5
rtk proxy php tools/php85-boundary-folding.php /path/to/php-8.5
rtk proxy php -d extension=ast tools/php85-ast-conformance.php
rtk proxy php tools/php85-coverage-report.php --check
rtk proxy php tools/php85-negative-report.php --check
rtk proxy python tools/php85-compiler-boundaries.py .audit --check
rtk proxy python tools/php85-source-inventory.py .audit
rtk git diff --check
```

## Remaining discrepancies and conformance assessment

No unexplained PHP 8.5 acceptance/rejection differential remains in the checked
corpus or new folding/AST matrices. The known discarded-declaration examples
and the newly discovered alternative-if error are resolved.

Exhaustive prefix-context and matched/unmatched equivalence remains an explicit
conformance blocker: the targeted regression tests pass, but they are not an
all-combinations proof. Likewise, inventorying every parser production/scanner
rule and direct compiler diagnostic does not prove every structural production
equivalent or provide an isolated witness for every compiler error path.
Indirect/deferred name, type, inheritance and constant evaluation checks remain
normative contextual constraints rather than a complete repository validator.
Full PHP 8.5 conformance must not be claimed on this evidence.

Files changed comprise the canonical EBNF/Markdown, README/conformance status,
historical-report supersession notes, generated source/coverage/negative ledgers,
this report and the new compiler inventory, boundary/structure fixtures and
tests, the test-only derivation forest, and the three new audit runners plus
the negative-report generator's empty-discrepancy handling.
