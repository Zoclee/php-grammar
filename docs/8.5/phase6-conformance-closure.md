# PHP 8.5 Grammar Completeness — Phase 6 of 7

Phase 6 reconciles 177 pinned Zend parser productions and their 623 alternatives,
adds early modifier validation, and expands binding, folding, ambiguity and
recursive-source evidence. No unexplained differential mismatch remains in the
audited matrices. Four concrete conformance blockers remain; this report does
not certify full PHP 8.5 conformance or choose Phase 7 release wording.

The canonical EBNF is unchanged. One coverage classification was wrong: `never`
in a parameter type can occur in valid source if an enclosing closure is
discarded. A live/retained/discarded compiler regression now exercises this
case. Coverage changes from 301/364 productions and 605/790 alternatives to
301/364 (82.7%) and 606/790 (76.7%); neither denominator changes. Attempted
coverage remains 301 productions and 607 alternatives.

## Processing layers and source authority

```text
source bytes → scanner/tokenization → parser and early actions → AST/binding
             → folding/discarded branches → surviving contextual checks
             → source validity within the selected PHP configuration
```

Early parser actions matter: duplicate modifiers or abstract/final conflicts
can reject a declaration before its enclosing expression is discarded. Later
checks do not run on declarations removed by constant folding. Runtime symbol
lookup, class loading, actual type compatibility and runtime constant values
remain outside this project's source-only contract. Lint success is not a
promise that executing a file succeeds.

The authority is PHP-8.5 revision
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`, with parser, scanner and compiler
SHA-256 hashes in [the source lock](../../tools/php85-source-lock.json).
Executable evidence uses PHP 8.5.10 and ext-ast 1.1.3, schema 120. The binary
does not establish identity with the exact source pin (blocker C4).

## Persistent reconciliation and evidence

[phase6-reconciliation.json](phase6-reconciliation.json) records every parser
production, each RHS alternative, source line, action calls, canonical anchors,
abstractions, positive production witnesses, negative boundaries and contextual
families. The generator rejects missing/unknown anchors and changed pinned
source hashes. Exact-name mappings and explicit renamed/inlined mappings are
used; no fuzzy name matching or silent omission is allowed.

The ledger is a production/action reconciliation, not a claim that every RHS
has an isolated positive and negative fixture. Evidence granularity is recorded
explicitly: canonical production witnesses and compiler function-family links
do not prove branch equivalence. All 244 direct fatal sites in 87 compiler
functions retain line numbers, diagnostic text, ownership and evidence links.
Thirteen of the 14 early fatal sites are modifier checks. The remaining early
encoding-declaration literal-AST check (`compile:6922`) is explicitly marked
`unimplemented-contextual` under C1/C2, rather than falsely called EBNF-enforced.

Intentional abstractions include:

- Bison precedence/associativity becomes explicit expression levels, pending
  assignment/prefix contexts and closed yield-key contexts.
- Dangling-else precedence becomes matched/unmatched statements and closed
  alternative-if lists. List recursion becomes EBNF repetition/options.
- Two ampersand lookahead tokens share the `&` terminal; atomic names and strings
  use documented scanner/adapter contracts.
- Shared class-member syntax is retained for classes, interfaces, traits and
  enums; surviving declaration-kind legality is contextual.
- The three `backup_*` productions are internal metadata/state, not source
  terminals. Their effects on later checks, such as generator flags, are not
  discarded from the contextual inventory.
- Nested `__halt_compiler()` is an error-only reduction and is intentionally
  excluded from valid inner statements. Allocation, location metadata and
  diagnostic formatting are not canonical grammar productions.

[phase6-evidence.json](phase6-evidence.json) consolidates ten language areas:
modifiers, types, parameters/functions, properties/hooks, enums, traits, writes,
calls/attributes, constants/folding and control/scopes. Each lists restrictions,
implementation status, lexical/positive/negative/binding/contextual/folding
evidence, sources and limitations. Its historical index gives explicit current
dispositions to the original audit issue table, subsequent defects, scanner
families and remaining proof limits. Historic validation counts remain historic.

## Contextual validation architecture

[`Php85ModifierValidator`](../../src/Php/Conformance/Php85ModifierValidator.php)
is a PHP 8.5 rule-level API. It takes an already identified modifier list and
declaration target, not raw PHP source. It checks eight targets: class,
anonymous class, method, property, promoted parameter, class constant, hook and
trait alias. Stable categories are `php85.modifier.target`, `.duplicate`,
`.visibility`, `.set-visibility` and `.abstract-final`.

Run these checks before folding. A null result means only that the supplied
modifier list passed this rule; it does not validate an entire declaration or
file. The generic EBNF matcher remains independent. The repository does not yet
expose a whole-source contextual AST traversal (C1). No production code invokes
PHP's tokenizer, parser, lint or an external compiler to implement the rule.

The input list uses the `member_modifier`/`class_modifier` token spellings;
the separate `var` property alternative is represented as `public` by its
parser action before calling this API.

The 584-case modifier matrix compares single and ordered-pair modifiers against
PHP in discarded closures, isolating parser actions from later declaration
checks. Class targets use their three grammar modifiers; other targets use ten
member spellings. Trait aliases use single modifiers because their grammar
does not admit lists. Static/abstract aliases pass early method-target conversion
and are rejected only during surviving trait compilation. The rule preserves
that distinction and checks neither runtime behavior nor type compatibility.

The inventory covers all requested write operations, type positions,
declaration/modifier combinations, hook forms, enums and trait adaptations.
The 115-family boundary corpus provides live, retained and discarded cases,
including the new `parameter-never` family. Remaining source-validator and
individual-diagnostic evidence gaps are listed as C1/C2, not hidden behind a
zero-mismatch count.

## Binding and ambiguity

[systematic-structure.json](systematic-structure.json) now contains 15,342 cases,
4,048 more than the prior 11,294. Nine call/dereference/closure/arrow/clone/new
operands are crossed with all 44 binary operators in both positions. Five
switch/try/catch/finally wrappers extend the statement matrix from 14 to 19
wrappers, including nested switches and handlers with dangling/alternative else.
The new operand crosses include 44 expressions already present in the prefix
matrix; deduplication preserves each source once. There are 748 new expression
sources and 3,300 new statement sources.

The independent Earley derivation forest checks rejection or unique derivation.
For accepted cases it forces completed operand spans, pending-prefix completion
spans and implicit left folds using parentheses, and statement spans using
braces. Zend ASTs must remain equal. Operator flags and child order are retained;
line metadata, declaration IDs and the conditional parentheses marker are
normalized as before. This measures binding, not merely acceptance.

The separate 34-case ambiguity matrix adds types/DNF, qualified names,
attributes, argument lists, array/destructuring forms, members/hooks, trait
adaptations and alternate control flow. Three failed qualified-name probes
exposed a missing name-token split in the test-only forest adapter; it is fixed.
Two `int` property examples each have two raw derivations: an explicit builtin
literal or the equivalent `name` helper. Both preserve identical type spans and
one normalized structure. These are recorded helper ambiguities, not silently
counted as unique raw derivations. No canonical rewrite is warranted.

Binding within aggregate interpolated-string tokens remains C3. Arbitrary
nesting equivalence is not inferred from a finite derivation matrix.

## Folding, recursive combinations and recovery

The declaration folding matrix expands from 114 × 13 = 1,482 to 115 × 16 =
1,840 comparisons. Added templates test both selected/unselected middle ternary
arms and a nested null-coalesce/ternary context. The persistent
[folding report](phase6-boundary-folding.json) records templates and input hashes.

An additional 70 direct cases cross five restrictions with seven expression
contexts in ordinary and constant-initializer positions: unset casts, `isset`
expressions, nullsafe writes, `$GLOBALS` writes and constructor callable
conversion. Outcomes are declared independently of the running oracle.

For example, ordinary `false && (unset) 1;` is accepted; a constant initializer
containing the same logical expression rejects. Ordinary ternary/coalesce
compiles both children, while constant evaluation can skip unselected children.
Constant logical evaluation visits children first, so visiting an unset cast
fails before logical folding. Invalid nodes rejected only in the subsequent
constant-expression walk have different results. Existing direct real-cast and
38-case remediation evidence remains in force.

The new recursive matrix crosses five wrappers (interpolation, closures,
anonymous classes, dynamic-property braces and heredocs) in all 25 ordered pairs,
repeated one, two or three times: 75 expressions, at most six generated wrappers.
Three further attribute/constant-expression, alternate PHP/HTML and comment/tag
controls give 78 sources, tested under both short-tag configurations: 156
comparisons. Existing 482 scanner-product comparisons remain separate. This is
a precise bounded sample, not exhaustive arbitrary nesting.

Eight malformed sources under both profiles produce 16 rejection comparisons:
nested halt, missing expression/hook body, malformed interpolation, unclosed
comment/heredoc, broken attribute and malformed catch. Specialized Zend errors
do not imply valid source. No recovery AST, diagnostic wording, parser recovery
state or partial token-stream equivalence is promised.

## Upstream regression provenance

[phase6-upstream-tests.json](phase6-upstream-tests.json) records pinned paths,
URLs, source hashes and local adaptations for six PHPTs: removed unset casts,
duplicate set visibility, abstract/final hooks, nonstatic constant closures,
constant closure captures and empty-dimension coalescing. Twelve small local
negative/repair fixtures remove execution and output assertions. The separate
never-parameter family adds three files. All 15 join ordinary conformance tests;
all eight new contextual negatives have reviewed dispositions.

## Coverage and remaining classifications

The new never-parameter family is a compiler/folding regression, not a fabricated
raw coverage target. It proves the old rationale “no valid source witness” false.
The contextual-only alternative classification is removed, including its stale
fallback for void. Live declarations remain invalid and tested.

`reserved-non-modifiers/alternative:25` remains scanner-context-only. Its
non-primitive route is an unmodified trait alias before `;`, where `enum` is an
identifier token, not `T_ENUM`; the scanner requires a following label-start
byte for the keyword. Phase 5 enum/alias evidence accounts for it.

All remaining productions are 52 primitive-bypassed plus 11 removed-trivia.
Remaining alternatives are 174 primitive-bypassed, nine removed-trivia and one
scanner-context-only. There is no unclassified meaningful grammar coverage gap.
Scanner evidence stays at 190 rules, 25 families, 11 states, 2,700 direct cases,
101 primitive cases and 144 syntax cases. Dependent evidence hashes are refreshed.

## Concrete full-conformance blockers

The machine-readable blockers include impact, sources, evidence and closure work.
The current inventory lists 73 restriction descriptions across ten areas and
79 historical dispositions. C2 names 230 direct diagnostic sites requiring
predicate-level evidence or a justified scope disposition; function-family
evidence is already indexed for them.

| ID | Unproven behavior and consequence | Work required |
|---|---|---|
| C1 | Whole-source contextual validation beyond modifier lists: types/parameters/returns, properties/hooks/promotion, enums/traits, writes/calls, constants/attributes and control/declaration scopes. Structural matching alone can falsely certify invalid source. | Add a source/derivation context representation and ordered folding-aware checks; expose checked versus unsupported rules. |
| C2 | Individual compiler diagnostic guards and delegated AST/inheritance/enum/attribute checks lack isolated source-only dispositions. Potential false accepts/rejects remain beyond the matrix. | Close the exact diagnostic-site list in the evidence ledger; trace `zend_ast.c`, `zend_inheritance.c`, `zend_enum.c`, `zend_attributes.c`, and distinguish runtime/name/type compatibility. |
| C3 | Binding fingerprints inside aggregate interpolated strings/heredocs are not compared. Acceptance agrees at the tested depths but structure is not proven there. | Expose an audit-only embedded derivation view and compare encapsulation fingerprints for the existing recursive matrix. |
| C4 | PHP 8.5.10 executable identity differs from the normative source pin. | Build the pin and rerun, or reconcile all relevant release-to-pin source changes. |

Exact malformed recovery, unbounded mathematical equivalence and runtime
execution are explicit scope limits, not additional unspecified blockers.

## Reproduction and verification

Use PHP 8.5 with ext-ast for the structural oracle and the usual PHPUnit
extensions. Prefix commands with `rtk` in this repository.

```text
composer validate --strict
composer test
composer grammar:coverage
composer lexer:coverage
composer conformance:phase6
php tools/php85-phase6.php --check
php bin/php85-conformance.php /path/to/php85
php tools/php85-boundary-folding.php /path/to/php85
php -d extension=ast tools/php85-ast-conformance.php
php -d extension=ast tools/php85-systematic-structure.php
php tools/php85-scanner-product.php
php -d short_open_tag=1 tools/php85-lexer-differential.php
php -d short_open_tag=0 tools/php85-lexer-differential.php
php tools/php85-coverage-report.php --check
php tools/php85-negative-report.php --check
python tools/php85-compiler-boundaries.py /path/to/pinned-sources --check
python tools/php85-reconcile.py /path/to/pinned-sources --check
python tools/php85-phase6-evidence.py --check
git diff --check
```

Generate reports in dependency order: positive/negative coverage, source
inventory, lexical ledger, matrices/reconciliation, consolidated evidence.
The reconciliation and evidence generators are offline once pinned sources are
available. Matrix hash checks in PHPUnit do not require downloaded php-src.

Final validation on 2026-09-12:

| Check | Final result |
|---|---|
| `composer validate --strict` | Pass |
| `composer test` | Pass: 5,105 tests, 48,143 assertions |
| `composer grammar:coverage` | Pass: 301/364 productions, 606/790 alternatives; attempted 301/607 |
| `composer lexer:coverage` | Pass: 190 rules; 2,700 direct, 101 primitive, 144 syntax cases |
| `composer conformance:phase6` | Pass: 860 cases, no failures |
| Phase 6 `--check` | Pass; current matrix input hashes and outcomes match |
| Ordinary differential | Pass: 1,371 paired comparisons, 653 valid / 362 structural-negative / 356 contextual-negative; zero known exceptions or mismatches |
| Explicit Zend AST witnesses | Pass: 63 positive and 10 negative |
| Systematic derivation/AST | Pass: 15,342 cases; 14,287 unique accepted derivations and AST comparisons, 1,055 parser rejections |
| Additional ambiguity matrix | Pass: 34 cases; 32 unique raw derivations, two documented two-derivation helpers, one normalized structure each |
| Declaration folding | Pass: 115 families × 16 templates = 1,840 |
| Direct folding | Pass: 70 ordinary/constant-context comparisons (part of the 860 Phase 6 cases) |
| Existing scanner product | Pass: 482 comparisons |
| New recursive/recovery matrices | Pass: 156 recursive plus 16 malformed comparisons (part of the 860) |
| Lexical oracle, short tags enabled | Pass: 2,342 token comparisons, 84 rejection checks, 144 syntax checks, 648 corpus token streams |
| Lexical oracle, short tags disabled | Pass: 273 token comparisons, five corpus token streams |
| Source hashes/compiler diagnostic inventory | Pass: three pinned hashes, 244 sites, 87 functions, 14 early sites |
| Parser reconciliation/evidence freshness | Pass: 177 productions, 623 alternatives; ten areas, 79 historical dispositions, four blockers |
| Positive/negative generated report freshness | Pass |
| EBNF syntax/references/reachability/nullability, Markdown parity, repository consistency | Pass in the full PHPUnit suite |
| Final Phase 6 evidence-focused PHPUnit recheck | Pass: 191 tests, 1,053 assertions |
| `git diff --check` | Pass |

The lexical oracle preserves its one intentional tokenizer-only `halt-malformed`
exception: tokenizer recovery can stop after three significant tokens, while
parser validity requires the actual directive. It is not an unexplained source
acceptance mismatch. Four expected escape-overflow diagnostics are reported by
the enabled-profile lexical run. The initial PHPUnit run exposed stale coverage
hashes; regenerated dependent reports resolve that failure in the final run.

New case accounting by requested area:

| Area | Added or reused evidence |
|---|---|
| Write contexts | 28 direct nullsafe/`$GLOBALS` folding cases, 14 `isset` cases; existing assignment/reference/destructuring/foreach/unset families retained |
| Type contexts | New never-parameter live/retained/discarded family, exercised under 16 folding templates; six type-shape ambiguity cases; prior void/nullable/union/intersection/DNF evidence retained |
| Declarations/modifiers | 584 early-rule comparisons: 12 class, 12 anonymous-class, 110 each property/method/parameter/class-constant/hook, ten trait-alias |
| Properties/hooks | 110 hook-modifier comparisons, member/hook ambiguity cases and two PHPT-derived abstract/final-hook fixtures; 19 existing hook boundary families retained |
| Enums | Six existing enum-specific and relevant shared-member families rerun under 16 templates; no new enum grammar or validator rule |
| Traits | Ten early alias comparisons, three adaptation ambiguity cases, four existing trait-specific families rerun under 16 templates |
| Upstream provenance | Six PHPTs reduced to 12 fixtures, plus three never-parameter fixtures |
| Structural binding | 4,048 additional unique generated sources; no canonical binding defect found |

No canonical grammar, production matcher or production lexer defect was found
in this phase. The test-forest qualified-name adapter and the coverage
classification/freshness defects were fixed. The contextual modifier validator
is new; no claim is made that its rule-level API closes C1. No files are removed.

## File inventory

Added (28):

- docs/8.5/phase6-boundary-folding.json
- docs/8.5/phase6-conformance-closure.md
- docs/8.5/phase6-evidence.json
- docs/8.5/phase6-matrices.json
- docs/8.5/phase6-reconciliation.json
- docs/8.5/phase6-upstream-tests.json
- src/Php/Conformance/Php85ModifierValidator.php
- tests/Php/Conformance/Php85ModifierValidatorTest.php
- tests/Php/Conformance/Php85Phase6Test.php
- tests/Support/Php85Phase6Cases.php
- tests/fixtures/php/8.5/contextual-invalid/boundary-parameter-never-live.php
- tests/fixtures/php/8.5/contextual-invalid/boundary-parameter-never-retained.php
- tests/fixtures/php/8.5/contextual-invalid/phase6-abstract-final-hook.php
- tests/fixtures/php/8.5/contextual-invalid/phase6-closure-capture.php
- tests/fixtures/php/8.5/contextual-invalid/phase6-empty-dimension.php
- tests/fixtures/php/8.5/contextual-invalid/phase6-nonstatic-closure.php
- tests/fixtures/php/8.5/contextual-invalid/phase6-set-visibility.php
- tests/fixtures/php/8.5/contextual-invalid/phase6-unset-cast.php
- tests/fixtures/php/8.5/valid/boundary-parameter-never-discarded.php
- tests/fixtures/php/8.5/valid/phase6-abstract-final-hook.php
- tests/fixtures/php/8.5/valid/phase6-closure-capture.php
- tests/fixtures/php/8.5/valid/phase6-empty-dimension.php
- tests/fixtures/php/8.5/valid/phase6-nonstatic-closure.php
- tests/fixtures/php/8.5/valid/phase6-set-visibility.php
- tests/fixtures/php/8.5/valid/phase6-unset-cast.php
- tools/php85-phase6-evidence.py
- tools/php85-phase6.php
- tools/php85-reconcile.py

Changed (27):

- README.md
- composer.json
- docs/8.5/audit-remediation.md
- docs/8.5/negative-boundaries.json
- docs/8.5/negative-boundaries.md
- docs/8.5/parser-compiler-remediation.md
- docs/8.5/phase3-coverage.json
- docs/8.5/phase3-coverage.md
- docs/8.5/phase4-negative-coverage.md
- docs/8.5/phase5-lexer-audit.md
- docs/8.5/phase5-lexical-evidence.json
- docs/8.5/remaining-audit.md
- docs/8.5/remediation-audit.md
- docs/8.5/source-inventory.json
- docs/8.5/systematic-structure.json
- docs/conformance.md
- docs/sources.md
- grammar/8.5/php.md
- src/Php/Conformance/Php85CoverageClassification.php
- tests/Php/Conformance/Php85CoverageClassificationTest.php
- tests/Php/Conformance/Php85NegativeBoundaryLedgerTest.php
- tests/Php/Lexing/Php85ScannerAuditTest.php
- tests/Support/DerivationForest.php
- tests/fixtures/php/8.5/parser-compiler-boundaries.json
- tools/php85-boundary-folding.php
- tools/php85-coverage-report.php
- tools/php85-systematic-structure.php

Removed: none. Ignored local PHP/source downloads and one-off audit scripts are not repository deliverables.
