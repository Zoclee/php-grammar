# PHP 8.5 Grammar Completeness — Phase 4 of 7

Current Phase 6 status: [conformance closure](phase6-conformance-closure.md) and
[the evidence/disposition index](phase6-evidence.json) supersede current-status
claims below. The discarded enum/trait/unset defects are fixed; the never-type
coverage classification is corrected. Earlier phase counts and findings are
historical. Four concrete full-conformance blockers remain in the Phase 6 report.

Current status: the [subsequent remediation audit](remediation-audit.md) resolves the discarded-unset, for-condition, assignment-prefix and instanceof-power defects, expands the boundary matrix to 114 families, and supplies systematic AST and scanner-product comparisons. Counts and unresolved findings below describe the historical stage unless explicitly updated.

Phase 5 update: [the scanner audit](phase5-lexer-audit.md) supersedes
scanner-evidence gaps and current counts in this historical report. It maps
all 190 scanner rules, preserves 52 primitive-bypassed and 11 removed-trivia
productions, and removes one false reserved-enum alternative hit (601 to 600).
The original contextual-only alternative remains; one scanner-context-only
alternative is now explicit. The discarded-unset folding witness
was fixed by the remediation audit and is permanently tested in Phase 6. Historical phase-specific results below are retained.

Historical audit snapshot. The [parser/compiler boundary remediation](parser-compiler-remediation.md) supersedes its outstanding-gap descriptions and counts.

Phase 4 establishes systematic nearby-invalid evidence for every major PHP 8.5
syntax area. It adds **250 structural-negative fixtures, 22 contextual-negative
fixtures, and 272 corresponding positive repairs**. All 14 boundary categories
are represented. The two discarded-declaration discrepancies were unresolved at Phase 4 and
were subsequently fixed by the parser/compiler remediation; this is not full PHP 8.5 conformance.

## Results

| Metric | Before Phase 4 | After Phase 4 |
|---|---:|---:|
| Valid fixtures, both short-tag profiles | 225 | 497 |
| Structural-negative fixtures | 85 | 335 |
| Contextual-negative fixtures | 63 | 85 |
| Ordinary differential fixtures | 373 | 917 |
| Separately reported known discrepancies | 2 | 2 |
| Unexpected differential mismatches | 0 | 0 |
| Completed productions | 303/366 (82.8%) | 303/366 (82.8%) |
| Completed alternatives | 609/794 (76.7%) | 609/794 (76.7%) |
| Attempted productions | 303/366 | 303/366 |
| Attempted alternatives | 611/794 | 611/794 |
| Coverage rule cases | 246 | 246 |
| PHPUnit tests / assertions | 863 / 2,163 | 1,411 / 5,503 |

The default profile contains 492 valid, 332 structural-negative, and 85
contextual-negative fixtures. The disabled-short-tag profile retains five
valid and three structural-negative fixtures. All 544 new files participate
automatically in the existing PHPUnit and PHP 8.5 differential corpus.
No old fixtures were removed or reclassified.

## Method and scope

The starting points were the 366-production canonical EBNF, the Phase 3
positive witnesses and the subsequent audit's corrected expression, statement,
argument, declaration and scanner categories. For each reviewed boundary, a
small source form changes one principal syntactic component: remove an
operand, move a modifier, repeat a separator, replace a delimiter, or combine
incompatible alternatives. A paired valid file demonstrates the nearby form
that should still be accepted. The two files intentionally share a descriptive
boundary basename in their respective classification directories.

The machine-readable [ledger](negative-boundaries.json) records each
negative/repair pair, production anchor, syntax area, boundary category,
classification and pinned source evidence. Its generated
[readable report](negative-boundaries.md) contains every fixture path,
category totals, the complete contextual review and the known gap dispositions.
The 272 pairs anchor 124 distinct productions. Anchors locate the syntax under
review; they do not prove that every alternative or combination has been
exhaustively tested, or identify the exact chart item that caused rejection.

Structural-negative means rejection by the repository's lexical/source,
adapter and EBNF recognition pipeline, as well as by PHP lint. A lexical
failure is included in this category even if it occurs before chart recognition.
Contextual-negative means structural acceptance followed by PHP compilation
rejection. PHP compilation remains the differential contextual check; the
repository does not implement a complete contextual validator.

Successful-production coverage and attempted coverage remain separate from
this rejection ledger. The repaired inputs exercise already-covered syntax,
so unchanged positive percentages are expected. No rejection percentage or
minimum percentage threshold is introduced. Coverage generation keeps Phase 4
repairs out of historical Phase 3 backlog reconstruction and preserves the
earlier backlog witnesses while allowing current first-positive witnesses to
reflect the expanded corpus.

## Boundary matrix

The ledger uses these 14 categories: missing required element; unexpected extra
element; invalid token/order; invalid delimiter; illegal empty form; invalid
alternative combination; duplicate syntactic component; malformed nesting;
invalid associativity/chaining; illegal trailing token; illegal prefix/postfix
form; invalid declaration structure; invalid source-mode transition; lexical
boundary violation.

| Area | New structural negatives | New contextual negatives | Principal boundaries |
|---|---:|---:|---|
| Expressions and precedence | 32 | 0 | Equality/spaceship/relational chains, ternary punctuation, coalesce and assignment, power, prefix/postfix forms, pipe, clone-with, textual operators, instanceof, casts, yield-from, throw and include/require |
| Dereferenceability | 12 | 0 | Scalar calls/access, nullsafe/static/offset combinations, new with omitted parentheses, anonymous classes, empty members, removed curly offsets |
| Arguments and FCC | 12 | 0 | Mixed FCC markers, forbidden FCC trailing comma, repeated separators, malformed named/unpack arguments, call-time references |
| Variables and assignments | 14 | 0 | Variable variables, empty/unclosed braced variables, literal/parenthesized assignment targets, references, keyed/short/nested list syntax, member names |
| Types | 15 | 0 | Nullable union/intersection boundaries, missing/repeated separators, DNF grouping, static parameter/property positions and missing return types |
| Properties and hooks | 17 | 0 | Outer semicolons, ordinary/hooked declaration mixing, hook delimiters/bodies, modifier order, set-parameter punctuation, asymmetric visibility, promotion, interface var forms |
| Class/interface/trait declarations | 16 | 0 | Names/bodies, bare semicolons, inheritance/implements ordering, member separators and typed class constants |
| Trait adaptations and enums | 20 | 0 | Empty use/aliases, insteadof targets, adaptation delimiters/outer semicolon, attributes, modifier order, enum cases, live backing-type and alias restrictions |
| Functions, closures and arrows | 16 | 0 | Parameters, variadic/reference placement, lexical-use lists, arrow bodies/attributes and return-type ordering |
| Statements and control flow | 40 | 0 | All requested statement families, alternate terminators, switch/match cases, catch/finally order, labels and list separators |
| Imports and namespaces | 16 | 0 | Names, aliases, group delimiters/emptiness, function/const use, commas and qualified-name trivia |
| Constant declarations/expressions | 7 | 11 | Missing initializers, attributed multiple constants, default/attribute boundaries; live versus discarded assignment/yield/include/require, dynamic operations, captures/FCC, pipe and clone-with |
| Source modes | 12 | 0 | Long/echo/open/close tag boundaries, recognized PHP rejection, alternate-control HTML transitions and halt-compiler arguments |
| Lexical boundaries | 21 | 0 | Numeric separators/prefixes, identifiers/keywords, strings/interpolation, heredoc/nowdoc, comments/attributes, cast whitespace and an actual NUL byte |
| Additional contextual boundaries | 0 | 11 | Argument ordering/unpack placement, full ternary chains, variadic placement/defaults, duplicate hooks, empty/mixed-key destructuring and nullsafe writes |
| **Total** | **250** | **22** | **272 paired positive repairs** |

The source-mode negatives use the default enabled-short-tag profile. For
example, `<?phpX` starts a short-tag region containing `phpX`; it is not a valid
long tag. The existing disabled-short-tag profile correctly treats unrecognized
opening sequences as HTML. No rule treats all tag-like text as invalid, and
recognized malformed PHP cannot escape validation by falling back to HTML.

## Contextual review and constant folding

All 63 inherited contextual negatives were inspected and retained; all 22 new
ones are separately classified. The ledger requires a review entry for every
current contextual-negative file. The review covers type legality, constructor
promotion, hook combinations/implementations, enum case consistency, variable
write/unset/destructuring contexts, `isset()` targets, constructor/nullsafe FCC,
argument ordering, ternary chains, parameter restrictions, modifiers and
constant-expression modes.

Several requested invalid forms cannot honestly be classified as EBNF
rejections. In the pinned parser, `argument` accepts expressions, named values
and unpacking independently; `zend_compile_args` checks ordering later.
`zend_compile_conditional` rejects surviving full/mixed unparenthesized ternary
chains. Repeated get hooks parse and require a contextual duplicate check.
Existing regression fixtures continue to distinguish these from malformed
separators, missing bodies and missing ternary operands.

The canonical `constant-expression = expression` is deliberately preserved.
`zend_const_expr_to_zval` first calls `zend_eval_const_expr`, then validates the
surviving AST through `zend_compile_const_expr`. Every new contextual constant
case has a positive companion that puts the same operation in a discarded
constant-conditional arm. This includes assignment, yield, yield-from,
include/require, dynamic offsets, increment, capturing static closures, dynamic
FCC, pipe and clone-with. An unconditional structural ban would reject those
valid positive witnesses. Valid PHP 8.5 static noncapturing closures, FCC and
other constant additions retain their earlier positive fixtures as well.

Existing `isset()` expression/call/empty-offset negatives and constructor FCC
negatives remain contextual, together with the audit's discarded-arm positives.
No broader contextual compiler or constant-folding implementation was added.
These distinctions are source-confirmed, not concessions made solely to match
the installed executable.

## Final disposition of the two known gaps

The parser, scanner and compiler hashes match the locked source pin
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. The known fixtures still produce
**repository rejection / PHP 8.5.10 acceptance**, separately from the ordinary
corpus. Pinned evidence and locations are linked in [sources.md](../sources.md).

| Gap | Source and owning layer | Phase 4 disposition |
|---|---|---|
| Invalid enum backing types in discarded closures | Parser `enum_backing_type` accepts `type_expr`; `zend_compile_enum_backing_type` enforces int/string when the declaration is compiled. | Retain the known discarded-enum fixture. Add live object/union backing negatives with int repairs. Current EBNF rejects too early when PHP discards the enclosing closure. Correct live/dead handling requires broader contextual declaration validation. |
| Trait alias modifiers in discarded closures | Parser `trait_alias` accepts a method-target modifier; `zend_compile_trait_alias` calls `zend_check_trait_alias_modifiers`, which rejects static/abstract. Readonly fails earlier parser modifier conversion. | Retain the known discarded-static-alias fixture. Add live static/abstract/readonly negatives with public repairs. The compiler-owned static/abstract distinction after folding remains unresolved; readonly is not the same discarded-branch exception. |

The structural-negative label for the live fixtures describes the current
repository rejection behavior. It does not move the upstream restriction from
contextual compilation into the PHP parser. No new EBNF restriction was added
to hide either gap, and their count remains two.

## Findings and changes by layer

No new EBNF, lexer, token adapter, generic matcher, chart recognizer or contextual
validator defect was established by this matrix. No canonical production or
accepted-language rule changed. The existing derivation-forest checks still
verify targeted precedence, matched/unmatched statements, nested yields and
arrays, and verify that nonassociative chains have no derivation. No new
ambiguity was discovered; this is not an all-input parse-tree uniqueness proof.

Two test/reporting issues were fixed:

- The repository lexer smoke test assumed every valid file contained an ordinary
  opening tag. Echo-only repairs demonstrated that assumption was wrong. It now
  verifies that token lexemes reconstruct the entire source byte for byte,
  covering echo tags and inline HTML without special filename exceptions.
- Phase 4 repairs could be mistaken for pre-Phase-3 fixtures or replace historical
  Phase 3 witnesses in the positive report. The generator separates current
  witnesses from historical backlog witnesses and excludes `phase4-` fixtures
  from the historical baseline. A regression checks that the backlog does not
  cite Phase 4 repairs.

The exploratory differential run also corrected four fixture-design mistakes:
`const int = 1` is a legal constant named `int`, so the missing typed-constant
name case now uses `const int|string = 1`; a scalar static-class repair was
replaced by `C::foo`; the set-hook repair received an explicit compatible
`int` parameter; and a global parameter repair uses `A` instead of out-of-class
`self`. These were fixture errors, not reasons to change grammar acceptance.
The final differential run has no unexpected mismatch.

## Contributor workflow

1. Choose an accepted syntax form and a nearby invalid boundary. Check the
   canonical production, positive evidence and pinned PHP source family.
2. Add a small `invalid/phase4-<area>-<boundary>.php` or
   `contextual-invalid/phase4-<area>-<boundary>.php` file and the matching
   `valid/phase4-<area>-<boundary>.php` repair. Keep both complete source files.
3. Add their paths, production anchor, boundary category, classification and
   evidence to `docs/8.5/negative-boundaries.json`. Add a contextual review
   entry whenever the negative requires contextual rejection. Document any
   folded/dead-branch exception instead of silently tightening the EBNF.
4. Run the ordinary PHPUnit/differential suites. Both sides of each pair must
   satisfy their separate expectations; lint never executes fixture programs.
5. Regenerate both coverage reports and check freshness. The negative report
   hashes the grammar, ledger and complete corpus bytes, including the NUL
   fixture; corpus additions/removals or fixture edits invalidate the report.
6. For discrepancies, inspect the pinned source and identify whether the fixture,
   EBNF, lexer, adapter, recognizer or contextual boundary is responsible. Record
   the decision, update the correct layer and retain a regression.

The ledger tests enforce classification/path agreement, unique pairs, existing
production anchors, distinct repaired contents, all category identities, full
Phase 4 fixture membership and complete contextual/known-discrepancy reviews.
They do not infer PHP validity from metadata; the ordinary conformance and
differential tests establish that evidence independently.

## Verification

Commands run from the repository root (the local shell uses the `rtk` prefix):

```text
composer validate --strict
composer test -- --no-progress
composer grammar:coverage
php bin/php85-conformance.php .audit/php85/php.exe
php tools/php85-coverage-report.php
php tools/php85-coverage-report.php --check
php tools/php85-negative-report.php
php tools/php85-negative-report.php --check
python tools/php85-source-inventory.py .audit
git diff --check
```

The source inventory remains 177 parser productions, 190 scanner entries,
143 selected compiler functions and 366 canonical EBNF productions. Source
hash validation and regeneration pass without changing that inventory.
EBNF syntax/reference/duplicate/reachability/nullable checks, complete Markdown
production parity and the existing targeted derivation/ambiguity checks are
included in `composer test`.

Final verification on 2026-09-11:

- `composer validate --strict`: pass; `composer.json` is valid.
- `composer test -- --no-progress`: pass, **1,411 tests / 5,503 assertions**
  on PHP 8.4.22. This includes grammar integrity, Markdown parity, targeted
  parse-tree uniqueness/ambiguity checks and negative-ledger integrity/freshness.
- `composer grammar:coverage`: pass, **303/366 productions and 609/794
  alternatives**; attempted totals remain 303/366 and 611/794.
- PHP 8.5.10 differential runner: pass, **497 valid / 335 structural-negative /
  85 contextual-negative**, **zero unexpected mismatches**, **two separately
  reported known discrepancies**. All 917 ordinary fixtures and both known
  fixtures were checked.
- Positive and negative generated report `--check` commands: pass.
- Pinned source hashes and source-inventory regeneration: pass; inventory unchanged.
- Historical Phase 3 backlog compared with its pre-Phase-4 JSON: unchanged.
- `git diff --check`, whitespace/conflict-marker inspection of new and changed
  files, local Markdown link checks and file-inventory reconciliation: pass.

All Phase 4 completion criteria have evidence: every major syntax area has
nearby-invalid witnesses, recent audit corrections have regressions, structural
and contextual cases are separately enforced, the differential corpus has no
unexpected discrepancy, and both preexisting gaps have explicit final dispositions.
The scope limitations below remain in force.

## File inventory

Added fixtures: **544 files**, all enumerated with clickable negative and
positive paths in [the generated ledger](negative-boundaries.md):

- `tests/fixtures/php/8.5/invalid/phase4-*.php`: 250 files.
- `tests/fixtures/php/8.5/contextual-invalid/phase4-*.php`: 22 files.
- `tests/fixtures/php/8.5/valid/phase4-*.php`: 272 files.

Added reporting and tests:

- [docs/8.5/negative-boundaries.json](negative-boundaries.json)
- [docs/8.5/negative-boundaries.md](negative-boundaries.md)
- [docs/8.5/phase4-negative-coverage.md](phase4-negative-coverage.md)
- [tools/php85-negative-report.php](../../tools/php85-negative-report.php)
- [tests/Php/Conformance/Php85NegativeBoundaryLedgerTest.php](../../tests/Php/Conformance/Php85NegativeBoundaryLedgerTest.php)

Changed files:

- [README.md](../../README.md)
- [docs/conformance.md](../conformance.md)
- [docs/8.5/phase3-coverage.md](phase3-coverage.md)
- [docs/8.5/phase3-coverage.json](phase3-coverage.json)
- [docs/8.5/remaining-audit.md](remaining-audit.md)
- [docs/8.5/audit-remediation.md](audit-remediation.md)
- [docs/sources.md](../sources.md)
- [grammar/8.5/php.md](../../grammar/8.5/php.md), informative evidence notes only.
- [tests/Php/Lexing/RepositoryLexerSmokeTest.php](../../tests/Php/Lexing/RepositoryLexerSmokeTest.php)
- [tools/php85-coverage-report.php](../../tools/php85-coverage-report.php)

Total: **549 added, 10 changed, none removed**. The canonical EBNF and grammar
conventions required no changes. Research inputs/scripts in ignored `.audit/`
are not repository artifacts or prerequisites for using the grammar or ledger.

## Limitations retained for Phases 5–7

The campaign establishes major-area rejection evidence, not exhaustive
scanner-state/byte equivalence, AST equivalence, all-input ambiguity absence,
constant-folding equivalence or complete contextual validation. Name resolution,
type checking and runtime behavior were not introduced into this phase. The
installed PHP 8.5.10 binary is a differential cross-check rather than the
canonical source of truth. The two known discrepancies remain visible and must
be reconciled in their correct layer before any broader conformance claim.
