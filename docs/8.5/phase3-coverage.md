# PHP 8.5 Grammar Completeness — Phase 3 of 7

Current status: the [subsequent remediation audit](remediation-audit.md) resolves the discarded-unset, for-condition, assignment-prefix and instanceof-power defects, expands the boundary matrix to 114 families, and supplies systematic AST and scanner-product comparisons. Counts and unresolved findings below describe the historical stage unless explicitly updated.

Phase 5 update: [the scanner audit](phase5-lexer-audit.md) supersedes
scanner-evidence gaps and current counts in this historical report. It maps
all 190 scanner rules, preserves 52 primitive-bypassed and 11 removed-trivia
productions, and removes one false reserved-enum alternative hit (601 to 600).
The original contextual-only alternative remains; one scanner-context-only
alternative is now explicit. A newly recorded discarded-unset folding witness
is assigned to Phase 6. Historical phase-specific results below are retained.

Phase 3 establishes systematic positive evidence for the major PHP 8.5 syntax
areas. Every remaining uncovered identity has an explicit classification.
This completes the positive-coverage phase, not the full conformance audit.

The tables below record the historical Phase 3 baseline. The JSON ledger has
been regenerated for the [remaining-audit corrections](remaining-audit.md):
303/366 productions and 609/794 alternatives now have positive recognition
evidence. No uncovered identity is classified as a meaningful coverage gap;
this does not eliminate the two recorded conformance discrepancies.

Grammar Completeness [Phase 4](phase4-negative-coverage.md) subsequently
adds 272 paired positive repairs and systematic negative evidence. Positive
totals remain **303/366 productions (82.8%) and 609/794 alternatives (76.7%)**;
attempted totals remain 303/366 and 611/794. The current corpus has 497 valid
fixtures (492 default profile plus five disabled-short-tag fixtures).
The generated JSON is current; tables labeled historical below remain Phase 3
records. Phase 4 fixtures are excluded from reconstruction of the historical
Phase 3 backlog. Rejection evidence is in its own
[ledger](negative-boundaries.md), not added to successful coverage.

## Historical results

| Metric | Post-audit baseline | Phase 3 |
|---|---:|---:|
| Completed productions | 236/331 (71.3%) | 265/331 (80.1%) |
| Completed alternatives | 277/769 (36.0%) | 570/769 (74.1%) |
| Attempted productions | 255/331 | 265/331 |
| Attempted alternatives | 563/769 | 572/769 |
| Default-profile valid files | 104 | 169 |
| Disabled-short-tag valid files | 4 | 4 |
| All valid differential files | 108 | 173 |
| Structural-negative files | 76 | 76 |
| Contextual-negative files | 25 | 25 |
| Differential total | 209 | 274 |
| Unexpected mismatches | 0 | 0 |
| Coverage rule cases | 10 | 246 |
| PHPUnit tests / assertions | 387 / 1,094 | 726 / 1,780 |

Added **65 positive fixtures**, **236 coverage rule cases**, and **35 direct
primitive cases**. The latter validate token primitives and intentionally do
not inflate EBNF traversal counts. No fixtures were removed or reclassified.

The generated [coverage ledger](phase3-coverage.json) contains the original
95-production/492-alternative backlog, its disposition, current remaining
identities, classification counts, the first accepted witness for every
exercised identity, primitive successes, and the full indexed rule matrix.
It derives identities and bypass paths from the grammar and adapter registry.

## Method and interpretation

Start with `composer grammar:coverage`, select meaningful alternatives, inspect
the PHP 8.5 source family, then add focused integration files or rule fragments.
The operator matrix independently exercises each binary, assignment, and cast
choice. Repeated rule names have unique PHPUnit dataset keys.

Coverage records completed Earley items during an accepted positive input. It
does not reconstruct a unique successful derivation; local/ambiguous item
completion can contribute. A production hit alone is insufficient evidence for
its alternatives, optional combinations, or contextual legality. The matrices
below review these beyond the raw percentages. Rejected positive inputs now
abort coverage analysis instead of silently contributing partial completions.

Whole-file fixtures run through the repository lexer, adapter, and independent
chart recognizer and through official PHP 8.5 lint. Rule fragments run through
`matchesRule()` and are traceable to the pinned grammar/compiler families.
They are not individually submitted to PHP lint without enclosing context.
In particular, `void`/`never` type fragments denote valid return types;
constant property chains use constructible objects in
`phase3-constant-constructed-chains.php`; fragments still do not independently
prove successful symbol resolution or folding.

## Language-area review

Fixture references in this table omit `.php` and refer to
`tests/fixtures/php/8.5/valid/`. Existing audit witnesses are retained where
they already establish the feature; the ledger records exact rule indices.

| Area | Positive evidence / expansion | Primary source family |
|---|---|---|
| Source transitions | `phase3-{if,while,for,foreach,switch,declare}-html`; existing tags, echo tags, inline HTML, comments, doc comments, disabled-tag tests | parser statement/alternate statements; scanner INITIAL/close tags |
| Names | `phase3-names-relative`, namespaces-braced; existing keyword-qualified/high-byte/object-lookup cases; direct primitive tests | parser name/identifier; scanner LABEL/name states |
| Imports | `phase3-imports-*`: simple/aliased, function/const, mixed and homogeneous groups, nested prefixes, trailing commas | use declarations/group use |
| Types | every simple return type, legal non-static simple forms, nullable, intersection, union and DNF rule matrix; `phase3-types-return-contexts`; existing DNF and static-return fixtures | type/union/intersection productions; compiler typename/params |
| Operators | each textual logical, boolean, bitwise, equality (including `<>`), relational, shift, additive, multiplicative and power operator; concatenation/coalesce; every assignment and accepted cast alias; unary/prefix/postfix and reference assignment | parser precedence/expr; scanner cast tokens |
| Low-precedence expressions | print and prefix print, include/require variants, arrow and closure variants, clone and nested clone; existing throw, pipe, coalesce and ternary regressions | expr/precedence |
| Variables/dereferencing | `phase3-new-variable*`, dynamic properties/members, nullsafe calls/access; rule `${...}`, qualified calls, nested clone; existing recursive globals, static unset, literal/call chains and new parentheses | variable/new_variable/callable/fully_dereferenceable |
| Arrays/arguments | `phase3-arrays-reference`, keyed/nested list destructuring; existing short destructuring, named keyword arguments, unpacking, callable markers and trailing commas | array_pair/argument_list; compiler list assignment |
| Statements | do/while, foreach key/reference/list/short forms, all alternate loops, alternate if/elseif/else, switch separators, declare; catch unions without variable, multiple catches/finally; existing echo/unset/global/static/goto/return/throw/break/continue | statement/foreach_variable/catch/switch cases |
| Functions | `phase3-parameters-variants`, closures captures/reference/attributes, static reference arrow, generators, nested declarations | parameter/lexical_var/function/closure |
| Classes | readonly/final inheritance and readonly anonymous class; methods with protected/private/final/static/abstract forms; existing named/anonymous attributes, inheritance/interfaces | class declaration/modifiers/members |
| Members | property defaults, visibility, static/readonly/final, all set visibility forms, promoted visibility/readonly, constant visibility/final/types | property/parameter/class-constant productions |
| Interfaces/traits/enums | interface inheritance/constants; enum trait/constants/methods and existing backed/unbacked cases; trait precedence, empty adaptations, all alias action forms and reserved alias keywords | interface/enum/class_statement/trait_alias |
| Constant expressions | full separate operator/cast matrix, both ternaries, long/keyed/unpacked arrays, floating/magic literals, callable forms and computed members, enum/new property reads and offsets | compiler zend_is_allowed_in_const_expr and const-expression compilation |
| Lexical forms | existing numeric bases/separators/floats, binary strings, interpolation/offsets, heredoc/nowdoc, backticks, escapes, halt data; 35 new direct primitive assertions sets | scanner literal/string/comment states; StringSyntax adapter |

The keyword-alias fixture is a single focused matrix for the reserved-name
alternative of trait aliasing, rather than an unrelated multi-feature program.
It is checked by PHP 8.5 lint, not merely generated and assumed correct.

## Dedicated hook matrix

| Form | Witness |
|---|---|
| Get expression / set expression | `phase3-hooks-asymmetric`, `phase3-hooks-promoted` |
| Get block / set block with backing storage/default | `phase3-hooks-blocks` |
| Reference-returning get | `phase3-hooks-reference-get` |
| Explicit typed set parameter / trailing comma / hook attributes | `phase3-hooks-attributes`, existing `audit-hooks-set-parameter` |
| Interface get-only / set-only / both | `phase3-hooks-interface-{get,set,both}` |
| Abstract hooks | existing `audit-hooks-abstract` |
| Final hooks | existing `audit-hooks-final` |
| `var` hooked declaration | existing `audit-hooks-var` |
| Asymmetric property visibility | `phase3-hooks-asymmetric` |
| Constructor-promoted hooks | `phase3-hooks-promoted` |
| `__PROPERTY__` | `phase3-hooks-attributes` |

Visibility belongs to the property; hook-level visibility is not valid syntax.
Existing contextual negatives continue checking duplicate hooks, invalid
static/readonly combinations, reference setters, concrete body requirements,
interface bodies and final/private combinations. No exhaustive combination
matrix or contextual validator was introduced.

## PHP 8.5 feature matrix

| Addition represented in the grammar | Positive evidence |
|---|---|
| Pipe `\|>` | `audit-php85-pipe`, expression fixture |
| Clone-with / clone as callable | `audit-php85-clone-{named,unpacked,trailing-comma,callable}` |
| `(void)` statement and for-list placements | `012-void-cast-clone`, `audit-statements-for-void-prefix` |
| `__PROPERTY__` | `phase3-hooks-attributes` |
| Attributes on global constants | `audit-attributes-global-constant` |
| Final promoted properties | `audit-promotion-final` |
| Asymmetric static property visibility | `audit-properties-static-asymmetric` |
| Casts in constant expressions | all ten accepted spellings in rule matrix; `audit-constants-casts` |
| Static noncapturing closures in constant expressions | `audit-constants-static-closure` |
| First-class callables in constant expressions | existing `audit-constants-*callable`, `audit-constants-first-class`; new builtin/readonly/dynamic-member fixtures |

The PHP 8.5.0 `NEWS` entry was reviewed alongside the pinned parser/compiler.
New built-in attributes such as `NoDiscard` use existing attribute syntax;
new API names do not create additional grammar productions.

## Remaining identities

| Classification | Productions | Alternatives | Why direct traversal is absent |
|---|---:|---:|---|
| Primitive-bypassed | 55 | 188 | Atomic token primitives replace EBNF bodies or cut every source path to lexical helpers, including names, numbers, strings/interpolation, HTML and halt payload internals. |
| Trivia removed | 11 | 9 | Whitespace/comments/doc comments are lexed then removed before structural matching. |
| Contextual-only | 0 | 2 | `never` and `void` in `simple-type-without-static` cannot appear in valid parameter/property positions. Their return-type alternatives have positive witnesses. |
| Meaningful valid syntax still needing evidence | 0 | 0 | No current unclassified structural gaps. New identities default to this visible category. |
| Structural/helper indirect or impossible path other than lexical bypass | 0 | 0 | No extra exclusions needed. |
| Suspected unresolved grammar/lexer/matcher defect | 0 | 0 | No new discrepancy requiring a syntax change was established. |

The JSON lists every identity, not just these aggregates. Primitive categories
are not claims that all scanner states or byte combinations are exhaustively
tested. `Php85LexicalPrimitiveTest` directly tests 35 token forms; `LexerTest`
tests trivia, source transitions, token metadata, names, numeric/string classes
and casts. Audit lexical fixtures additionally pass the differential runner.
Heredoc/nowdoc can be consumed by broader `string-literal` primitives, so an
unused narrower primitive does not indicate an absent source feature.

## Discrepancies and contextual review

No EBNF, lexer, adapter acceptance, or chart recognition changes were required.
Two coverage-infrastructure issues were corrected: repeated rule dataset names
could collide; rejected positive inputs could silently contribute partial
coverage. Regression checks protect unique datasets, rejected positive rules,
shared syntactic paths in bypass classification, and reordered contextual type
alternatives. Raw coverage denominators were preserved.

One draft positive fixture mixed `[]` and `list()`. The official compiler
rejects that combination (`zend_compile.c` lines 3250–3255). The final positive
uses nested `list()` consistently and still exercises the intended keyed-list
alternative. This is a documented contextual boundary, not a grammar defect.

Type legality, promotion placement, hook combinations, enum consistency,
write targets, argument ordering and constant-expression context remain subject
to the documented compiler checks. All 25 contextual-negative regressions
remain in the differential run. No new negative-boundary campaign was started.
Major audit corrections have retained positive evidence and additional trait,
hook, operator, source-transition and dereference witnesses in the ledger.

## Reproduction and limits

```text
composer validate --strict
composer test
composer grammar:coverage
php bin/php85-conformance.php .audit/php85/php.exe
php tools/php85-coverage-report.php
php tools/php85-coverage-report.php --check
```

Use an official PHP 8.5 binary as described in the audit; the suite's host PHP
8.4.22 is not the language oracle. PHP 8.5.10 lint uses both short-tag profiles
without executing fixture programs. The chart recognizer remains independent
of Zend and is used by all applicable new fixtures. No recognizer was weakened
to obtain agreement.

Phases 4–7 retain negative boundaries, AST binding equivalence, exhaustive
constant folding, contextual validation, scanner-state equivalence and other
remaining audit work. Runtime behavior, type checking and name resolution are
outside this positive coverage phase. Full PHP 8.5 conformance is not claimed.

Sources: the [pinned parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y),
[scanner](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l),
and [compiler](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c),
with function/rule locations in `source-inventory.json`.

Final verification on 2026-09-10: `composer validate --strict`, `composer test`
(726 tests, 1,780 assertions), `composer grammar:coverage`, PHP 8.5.10
differential lint (274 files, zero unexpected mismatches), generated report
`--check`, and `git diff --check` all passed. EBNF integrity and Markdown
production parity are included in the test suite.

## File inventory

71 files added, 11 changed, none removed.

Added implementation, tests, and reports:

- [docs/8.5/phase3-coverage.json](phase3-coverage.json)
- [docs/8.5/phase3-coverage.md](phase3-coverage.md)
- [src/Php/Conformance/Php85CoverageClassification.php](../../src/Php/Conformance/Php85CoverageClassification.php)
- [tests/Php/Conformance/Php85CoverageClassificationTest.php](../../tests/Php/Conformance/Php85CoverageClassificationTest.php)
- [tests/Php/Conformance/Php85LexicalPrimitiveTest.php](../../tests/Php/Conformance/Php85LexicalPrimitiveTest.php)
- [tools/php85-coverage-report.php](../../tools/php85-coverage-report.php)

Added 65 fixtures under `tests/fixtures/php/8.5/valid/`:

```text
phase3-anonymous-readonly.php
phase3-arrays-reference.php
phase3-arrow-static-reference.php
phase3-assign-reference.php
phase3-catch-union-without-variable.php
phase3-class-readonly.php
phase3-closures-attributed-reference.php
phase3-closures-captures.php
phase3-constant-array-long.php
phase3-constant-callable-builtins.php
phase3-constant-callable-readonly.php
phase3-constant-constructed-chains.php
phase3-constant-dynamic-member.php
phase3-constant-magic.php
phase3-constant-new-property.php
phase3-constant-property-chain.php
phase3-constant-ternaries.php
phase3-constants-visibility.php
phase3-declare-html.php
phase3-dereference-dynamic-property.php
phase3-dereference-nullsafe.php
phase3-destructure-keyed-list.php
phase3-destructure-nested-list.php
phase3-enum-members.php
phase3-for-html.php
phase3-foreach-html.php
phase3-foreach-list.php
phase3-foreach-reference.php
phase3-foreach-short.php
phase3-generators-forms.php
phase3-hooks-asymmetric.php
phase3-hooks-attributes.php
phase3-hooks-blocks.php
phase3-hooks-interface-both.php
phase3-hooks-interface-get.php
phase3-hooks-interface-set.php
phase3-hooks-promoted.php
phase3-hooks-reference-get.php
phase3-if-html.php
phase3-imports-constant-group.php
phase3-imports-function-group.php
phase3-imports-mixed-group.php
phase3-imports-simple-kinds.php
phase3-include-forms.php
phase3-interface-inheritance.php
phase3-language-intrinsics.php
phase3-loop-do.php
phase3-methods-modifiers.php
phase3-names-relative.php
phase3-namespaces-braced.php
phase3-nested-declarations.php
phase3-new-variable-offset.php
phase3-new-variable-property.php
phase3-new-variable-static.php
phase3-new-variable.php
phase3-parameters-variants.php
phase3-promotion-visibility.php
phase3-properties-modifiers.php
phase3-switch-html.php
phase3-trait-alias-forms.php
phase3-trait-empty-adaptations.php
phase3-trait-keyword-aliases.php
phase3-trait-precedence.php
phase3-types-return-contexts.php
phase3-while-html.php
```

Changed files:

- [README.md](../../README.md)
- [bin/grammar-coverage.php](../../bin/grammar-coverage.php)
- [docs/conformance.md](../conformance.md)
- [docs/8.5/audit-remediation.md](audit-remediation.md)
- [docs/sources.md](../sources.md)
- [grammar/8.5/php.md](../../grammar/8.5/php.md)
- [src/Php/Conformance/GrammarCoverageAnalyzer.php](../../src/Php/Conformance/GrammarCoverageAnalyzer.php)
- [src/Php/Conformance/Php85CoverageCases.php](../../src/Php/Conformance/Php85CoverageCases.php)
- [src/Php/Conformance/PhpGrammarMatcher.php](../../src/Php/Conformance/PhpGrammarMatcher.php)
- [tests/Php/Conformance/GrammarCoverageAnalyzerTest.php](../../tests/Php/Conformance/GrammarCoverageAnalyzerTest.php)
- [tests/Php/Conformance/RuleLevelConformanceTest.php](../../tests/Php/Conformance/RuleLevelConformanceTest.php)
