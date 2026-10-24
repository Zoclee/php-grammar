# PHP 8.6 Phase 5E final coverage expansion

October 24, 2026. **Targeted coverage expanded; PHP 8.6 remains draft.** No EBNF,
normative specification, lexer, manifest or public API change is made. This is
the final dedicated coverage-expansion phase, not a completeness or certification
decision. PHP86-013/014/015 remain open. Phase 6 has not begun.

## Baseline and methodology

Repository commit: `11496fba8ae706b05bc500d99b8c0326a41054fc`. The
[baseline ledger](final-coverage-baseline.json) records starting Git status and
SHA-256 hashes of 2,045 existing files, including uncommitted Phase 5A–5D work.
The baseline suite reproduced **6,019 tests, 56,847 assertions, zero failures**.
The [initial measurement](final-coverage-initial.json) reproduced Phase 5B's
280/361 productions and 425/800 alternatives. The
[initial integration check](final-coverage-initial-integration.json) reverified
all 30 existing boundaries before additions.

Source pins remain PHP 8.5 `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and
PHP 8.6 RC4 `f23b4c222cebe33be93ee8ecaaa14390c5507a08`. Existing source locks
are unchanged. The new [supplementary lock](../../tools/8.6/final-coverage-source-lock.json)
records the current Phase 5D specification hash and reuses reviewed source hashes
at those same pins. It does not supersede the historical Phase 5B lock.

| Preserved PHP 8.6 artifact | SHA-256 |
|---|---|
| EBNF | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| Normative specification | `45688b7d36ff16d823360f9072f43aa8e52c4dbd9189c890a6fdeef592bca521` |

The method remains `GrammarCoverageAnalyzer` with zero extra rule-level cases.
Only accepted ordinary `valid/*.php` sources contribute positive chart coverage.
Completed chart items are witnesses, not proof of a unique successful derivation
or AST binding. Contextual negatives, malformed sources and inline scanner
entries do not inflate positive coverage. Denominators remain 361 and 800.

Selection began with all uncovered identities in the baseline. Closed-yield
helpers and unmatched `for`, `foreach` and `declare` forms were the remaining
syntactic production gaps. Declaration/type, prefix and statement alternatives
were selected alongside nearby negative forms. Existing Phase 5B fixtures are
identified by the preserved [124-case map](../../tests/fixtures/php/8.6/coverage-cases.json);
none were rewritten. The new [58-case map](../../tests/fixtures/php/8.6/final-coverage-cases.json)
records exact target productions, newly exercised alternative IDs, source
anchors, hashes, stage and paired expectations.

## Coverage results and bounded batches

| Measure | Initial | Final | Change |
|---|---:|---:|---:|
| Productions | 280/361 (77.56%) | 297/361 (82.27%) | +17 |
| Alternatives | 425/800 (53.13%) | 482/800 (60.25%) | +57 |
| Acceptance-boundary records | 30 | 36 | +6 |
| Dedicated scanner source/profile entries from Phase 5B onward | 31 | 51 | +20 |

Scanner entries are finite evidence counts, not an upstream rule/state coverage
percentage. Neither phase establishes a complete exercised-rule numerator for
the 190-rule, 11-state scanner inventory. A percentage would be unsupported.
The [coverage ledger](final-coverage.json) lists every remaining production and
alternative, classifications, positive witnesses and lexical primitive witnesses.
The review ledger records ordered batch contributions without counting the same
identity twice.

| Batch | Positive files | Structural negatives | Contextual negatives | New productions / alternatives |
|---|---:|---:|---:|---:|
| A: expressions | 13 | 4 | 3 | 14 / 26 |
| B: statements and declarations | 19 | 5 | 0 | 3 / 30 |
| C: scanner profiles | 0 | 0 | 0 | 0 / 0; 20 separate inline entries |
| D: contextual and version combinations | 8 | 0 | 6 | 0 / 1 |
| Total ordinary fixtures | 40 | 9 | 9 | 17 / 57 |

**Batch A — expressions.** Keyed yield with print, include, arrow and throw
operands; nested yield; yield-from assignment; reference generators; unary/power,
increment/decrement, cast aliases, callable and clone combinations; constant and
nullsafe restrictions; PFA inside a generator. The `yield throw $error ?? throw
new Exception => 2` witness exercises closed-yield prefix propagation that simple
yield statements do not reach. It is lint-only evidence; no execution or unique
binding claim is inferred. Missing yield operands and malformed keyed yield
are structural negatives. Yield in a constant, reference-generator delegation
and nullsafe increment remain structurally accepted contextual negatives.

**Batch B — statements and declarations.** Unmatched loop/declaration bodies,
elseif propagation, semicolon switch labels, match commas, try/finally, referenced
foreach, void casts in for lists, nested keyed lists, `var` hooks, reference get
hooks, promoted hooks, trait visibility adaptations, return/parameter types,
attributed closures, nested declarations and namespace imports. Nearby negatives
omit separators, result expressions, hook terminators, type operands or enum
case names. Existing alternatives/control-flow fixtures remain in the corpus.

**Batch C — scanner evidence.** Twenty additional inline entries cover tag/comment
transitions, attribute versus hash-comment boundaries, offset and nested string
interpolation, legacy variable-name and backtick states, heredoc/nowdoc return
to scripting, label suffixes/EOF and indentation boundaries, Unicode escape
limits, PFA trivia, yield-from whitespace, numeric token boundaries and both
short-tag profiles. Their source slices and immutable links are recorded in the
[scanner matrix](../../tests/fixtures/php/8.6/final-coverage-lexical.json).

The new runner verifies each scanner slice hash, input hash, repository token
acceptance, byte preservation and paired interpreter lint. Eligible entries also
compare native token lexeme boundaries. Aggregated string/heredoc/backtick tokens
are deliberately not claimed equivalent to Zend's finer token decomposition.
The existing documented `T_YIELD_FROM` split is the only normalization. Invalid
Unicode can survive raw tokenization while source validation rejects it. No lexer
implementation is changed, and source review plus examples do not instrument
Zend scanner-state execution.

**Batch D — contextual and version boundaries.** All verified finding families
receive representative additional evidence:

| Finding | New combination / boundary | Validation |
|---|---|---|
| PHP86-001 | PFA in generators; parent-get-hook callable rejection and ordinary-call repair | Structural boundary for generator PFA; lint distinguishes parent-hook rejection |
| PHP86-003 | Private readonly default; untyped readonly default rejection | Lint; default permission does not relax required typing |
| PHP86-004 | Global constant-object unset; constant-array unset rejection | Lint only; no assignment-derived target rule inferred |
| PHP86-005 | Trait constant Override with implementing-class target and missing-target control | Declaration-link; missing target passes RC4 lint before linking rejects |
| PHP86-006 | Untyped enum debug-info method; static-method rejection | Declaration-link; method-signature legality stays separate |
| PHP86-007 | Nullsafe call result returned by reference from a method; value-yield control | Lint; no blanket nullsafe expression restriction |
| PHP86-010 | Reference-returning namespaced `LeT`; return from finally | Accepted in both versions, with RC4 deprecation assertions; no acceptance-removal boundary |

The [six new boundary records](final-coverage-boundaries.json) identify finding,
source, first/last version, paired expected behavior, stage, immutable source,
observation identity and distribution provenance. Totals are 16 structural,
11 lint and nine declaration-link boundaries. The integration checker retains
unique fixture association for executed cases and includes the new case map.
The tests' exact boundary counts increase; their stage/rejection checks remain.
The external interpreter supplies contextual enforcement, not the native matcher.
Only declaration-only reviewed linking fixtures execute; ordinary expression,
scanner and generator programs are linted without execution.

**Batch E — reconciliation.** The review checks preserved baseline hashes,
unchanged denominators, new case hashes, ordered contribution unions, duplicate
sources, historical records, JSON and documentation links. It retains every
uncovered identity for subsequent source correspondence.

## Evidence decisions and remaining gaps

Candidate investigation identified expected, source-explained boundaries rather
than a newly established grammar defect. Mixed keyed/unkeyed list assignment was
repaired to a consistently keyed witness; class-constant `new` was replaced by
a global constant to isolate object mutation. `break` leaving finally is forbidden
in both versions and was not relabeled accepted/deprecated; the adopted return
witness tests the actual deprecation. Nullsafe property reference rejection
already exists in 8.5; the new version boundary uses a nullsafe call result.
The malformed multi-operator yield-key candidate remains a structural negative.
No negative expectation was lowered to hide a mismatch.

All 64 remaining unexercised productions are lexical/scanner or adapter-bypassed
forms. Existing classification conservatively retains `reserved-non-modifiers`
as a meaningful gap, rather than automatically excluding that token-category
production. This does not establish that all PHP syntax is represented. The remaining
318 alternative identities include lexical bodies, reserved-token categories and
less common syntactic/prefix alternatives. The complete identity list and existing
classification reasons remain in the coverage ledger; no item is removed from a
denominator or newly declared irrelevant. Positive lexer primitives supply a
different kind of evidence from EBNF-body chart coverage.

| Remaining classification | Productions | Alternatives |
|---|---:|---:|
| Primitive-bypassed | 52 | 174 |
| Trivia-removed | 11 | 9 |
| Meaningful gap | 1 | 134 |
| Unresolved | 0 | 1 |

These are the unchanged methodology's categories, not new exemptions. The
[remaining-family ledger](final-coverage-remaining.json) also groups exact
identities by syntax area for Phase 6.

Remaining work includes comprehensive pinned parser/action correspondence,
scanner rules and recursive state products, encoding/ZTS/opcache profiles,
binding/operand spans, full contextual enforcement, optimizer interactions,
parent-hook runtime combinations and exact-build/final-release reconciliation.
Higher coverage is not a substitute for those obligations. The inherited
discarded-hook policy remains unchanged and explicitly documented.

PHP86-013 remains open for discovery and compiler interactions; PHP86-014 remains
open for intended assignment-derived writable-target validity; PHP86-015 remains
open for optimized-call traversal compatibility. No new categorical rule or
Gate B grammar correction is proposed by this phase.

## Validation and preservation

Fresh [oracle validation](final-coverage-validation.json),
[integration](final-coverage-integration.json), [PFA](final-coverage-pfa.json),
[contextual](final-coverage-context.json) and
[deprecation](final-coverage-deprecation.json) reports are Phase 5E evidence.
Historical Phase 5A–5D and PHP 8.5 reports are retained byte-for-byte.
Final suite and preservation results are recorded in
[the review ledger](final-coverage-review.json).

| Check | Result |
|---|---|
| Full repository suite, including EBNF/reference/parity, API/CLI and PHP 8.5 regressions | PASS: 6,147 tests, 58,021 assertions, zero failures |
| New whole-source and scanner matrix | PASS: 1,131 checks, 156 paired-version observations, zero errors |
| Focused coverage, PFA, contextual, boundary and traceability tests | PASS: 440 tests, 4,016 assertions |
| Integration and all 36 boundaries | PASS: 526 checks, 172 observations |
| Existing PFA matrix | PASS: 225 checks, 114 observations |
| Existing PHP86-003–007 matrix | PASS: 278 checks, 126 observations |
| Existing deprecation matrix | PASS: 712 checks, 268 observations |
| Existing Phase 5C discrepancy matrix | PASS: 1,708 checks, 501 hash/absence checks, 162 observations; 162 lint and 37 execution invocations |
| Production-difference traceability | PASS: five modified, zero added/removed productions relative to 8.5; 14 documentary sections |
| Manifest | PASS: schema 1.0 and package paths |
| Preservation | PASS: 2,039 of 2,045 initial files unchanged; exactly six existing files changed; all 1,437 PHP 8.5 path files unchanged |
| Documentation and whitespace | PASS: 45 local link targets, 24 JSON inputs, Markdown trailing whitespace and Git diff check |

The six changed existing files are changes.md, its traceability map, the boundary
index, the integration checker's additional reviewed-case association, and the
two whole-index test counts. The original PFA and contextual boundary tests keep
their original inventories; new records live in `version-boundaries/final-8.6/`,
following Phase 5B's separate batch directory convention. All original 30
boundary records are byte-identical. New test/evidence files are additive.

The temporary PHP preservation/reconciliation driver's source is embedded in
the review ledger. It is an audit aid, not a new release gate. Source bytes are
compared after newline normalization and trimming for duplicate detection;
scanner duplicates are compared by exact source hash plus short-tag profile.
No duplicate new whole-source fixture or scanner source/profile was found.

Reproduction commands (from the repository root):

```powershell
rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress
rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress --filter 'Php86FinalCoverageTest|VersionBoundaryTest|Php86CoverageExpansionTest|Php86TraceabilityTest|Php86ContextualChangesTest|Php86PartialApplicationTest'
rtk proxy .audit/php85/php.exe -n tools/8.6/final-coverage-expansion.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/final-coverage-validation.json --coverage=docs/8.6/final-coverage.json
rtk proxy .audit/php85/php.exe -n tools/8.6/integration-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/final-coverage-integration.json
rtk proxy .audit/php85/php.exe -n tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=docs/8.6/final-coverage-pfa.json
rtk proxy .audit/php85/php.exe -n tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/final-coverage-context.json
rtk proxy .audit/php85/php.exe -n tools/8.6/deprecation-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/final-coverage-deprecation.json
rtk proxy .audit/php85/php.exe -n tools/8.6/compiler-contextual-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --cache=.audit --report=docs/8.6/final-coverage-probes.json
rtk proxy .audit/php85/php.exe -n tools/8.6/change-traceability.php --report=docs/8.6/final-coverage-traceability.json
rtk proxy .audit/php85/php.exe -n tools/validate-manifest.php
rtk git diff --check
rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll .audit/phase5e-review.php --final
```

PHPUnit uses PHP 8.5.10 with `-n` and the audit mbstring extension. Paired probes
use the hash-verified PHP 8.5.10 and PHP 8.6.0RC4 distributions, `-n`, byte-oriented
source and explicit short-tag settings. They are not proven exact-pin builds;
the upstream PHPT suite and full release/certification workflow were not run.
All existing release gates remain in place.

Both PHP 8.6 EBNF and normative specification retain their Phase 5D hashes. The
five existing PHP86-001 production differences and all 14 reviewed documentary
sections remain. Only changes.md's evidence/remaining-work prose and its reviewed
hash change. PHP 8.5 remains the recommended stable/default selection; numeric
latest-version discovery still includes draft 8.6.

## Phase 6 readiness

**READY WITH EXPLICIT LIMITATIONS** to begin comprehensive PHP 8.6 source
correspondence and completeness auditing. The stronger coverage supplies inputs
for that audit, not its conclusions. Candidate promotion, complete conformance
and stable certification remain **NOT READY**. Phase 6 has not begun.
