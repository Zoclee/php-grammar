# PHP 8.6 Phase 5B targeted coverage expansion

October 24, 2026. PHP 8.6 remains **draft**. This phase expands finite conformance
evidence; it does not claim language completeness, implement a contextual
validator, change EBNF, or resolve PHP86-013/014/015. Phase 5C is not begun.

## 1. Baseline and preservation boundary

Repository commit: `11496fba8ae706b05bc500d99b8c0326a41054fc`. The working tree
already contained the eleven untracked Phase 5A assessment files. They are
part of this phase's preservation baseline, not new Phase 5B output.
A pre-edit snapshot records 1,787 project files.

Before modifying project inputs, the coverage CLI reproduced **209/361
productions (57.89%) and 223/800 alternatives (27.88%)**, with attempted counts
252 and 496. The existing integration checker passed **431 checks and 126
observations**, including source/distribution locks and all **13 boundaries**.
The baseline full suite passed **5,673 tests, 53,083 assertions**. Registration
was and remains 8.6 `draft`; the stable/default maintainer version remains 8.5.

Source pins are unchanged: PHP 8.5 branch `PHP-8.5` at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`; PHP 8.6 branch `PHP-8.6`, RC4 at
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. The existing primary
[source lock](../../tools/8.6/source-lock.json) is unchanged. The supplementary
[coverage lock](../../tools/8.6/coverage-source-lock.json) hashes the additional
parent-hook and unknown-hook PHPT evidence, without replacing either source pin.
Upstream PHPTs were read as source; the upstream suite was not executed.

PHP 8.6 EBNF SHA-256 remains
`12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4`;
the specification remains
`0a75ca80dff07af7165b010ad6adfea14218bc1f5b3747f822da2e7aba5b7ede`.
Both PHP 8.5 artifacts, fixtures, source locks, and certification evidence remain
unchanged. Historical Phase 5A ledgers are not regenerated.

## 2. Selection and measurement methodology

Selection follows CA86-005–009 and the common-language priorities in
[Phase 5A](completeness-assessment.md). Each batch identifies actual pinned
parser/compiler/scanner families, expected stage and paired-version outcomes
before recording fixtures. Candidate sources were linted or declaration-linked
in isolated processes; mismatches were investigated before adoption.

The [case map](../../tests/fixtures/php/8.6/coverage-cases.json) records 124
sources, hashes, selected productions, exact newly exercised alternative IDs,
source references, structural expectations, interpreter expectations, findings,
and diagnostic constraints. All are whole-source cases. The coverage runner
uses the existing `GrammarCoverageAnalyzer` with **zero additional rule-level
cases**, unchanged definitions and unchanged denominators of 361 and 800.
Only accepted `valid/*.php` inputs contribute positive chart coverage.
Contextual, malformed and known-discrepancy fixtures cannot inflate that metric.

“Exercised alternative” means a completed chart item on an accepted input,
the same historical method used in Phase 5A. It is not a proof that the item
belongs to a unique successful derivation. Tests assert the selected alternative
identities individually; merely hitting a broader production does not satisfy
those assertions. Lexer primitive successes are recorded separately. Excluded
scanner bodies are not directly matched to raise percentages.

Pinned parser correspondence for expression/statement helpers uses the actual
`expr`, `statement`, `constant`, `name`, `simple_variable`, modifier and internal
function productions. Exact name equality with layered EBNF helpers is not
assumed. Each selected source reference is checked against a locked file and
its named anchor. This is finite source correspondence, not a replacement for
the remaining whole-compiler discovery work.

## 3. Reviewable batches and resulting coverage

[The batch ledger](coverage-expansion-batches.json) records ordered union deltas.
Later batches can strengthen conformance without adding new chart identities.
The differential runner validates every batch; the focused PHPUnit provider
labels cases by batch. No duplicate source was found after newline normalization
and trimming across the 8.6 file corpus. Related variants are still related
evidence, not independent language features.

| Batch | Positive files | Structural negatives | Contextual negatives | Other files | New production / alternative identities |
|---|---:|---:|---:|---:|---:|
| Common statements, expressions and declarations | 47 | 22 | 0 | 0 | 70 / 194 |
| PFA and callable contexts | 11 | 0 | 5 | 0 | 0 / 1 |
| PHP86-003–007 combinations | 8 | 1 | 10 | 0 | 0 / 0 |
| PHP86-010 diagnostic boundaries | 2 | 0 | 0 | 0 | 0 / 0 |
| Lexical syntax corpus | 9 | 5 | 0 | 0 | 1 / 7 |
| Hook discrepancy and controls | 0 | 0 | 0 | 4 | 0 / 0 |
| Total | 77 | 28 | 15 | 4 | 71 / 202 |

The ordinary 8.6 inventory becomes **159 valid, 41 structural-invalid,
55 contextual-invalid and one runtime** file, plus four separately classified
known-discrepancy/control files. The 31 inline scanner entries and two diagnostic
boundary records are separate representations, not extra ordinary fixture files.

### Statements and expressions

New positives cover alternative if/while/for/foreach, do, switch forms, try/catch/
finally, catch without a variable, declare forms, global/static locals, labels/
goto, dangling else, match, arrows, closure captures, anonymous classes, list and
array destructuring, variable variables, includes, isset/unset, void casts,
arithmetic/bitwise/logical/relational/assignment operators, casts, yields, array
pairs and dereferencing. Small negatives isolate missing values, separators,
delimiters, terminators and illegal combinations. They remain structural
negatives only where both grammar and oracle reject at the relevant stage.

### Declarations, types and imports

Trait precedence/aliases, grouped and mixed imports, qualified/relative names,
interface hooks, abstract/final hooks, asymmetric visibility, DNF/intersection
types, enum members, attributes, magic constants and halt payloads gain witnesses.
Malformed trait precedence targets, empty group imports, invalid type grouping,
missing hook bodies and missing enum values supply nearby invalid forms.
This expands ordinary grammar evidence without mechanically copying 8.5's corpus.

## 4. PHP86-001 strengthened evidence

The existing five modified productions remain unchanged and covered. New
method/static/dynamic PFA cases combine positional, named and variadic
placeholders; method/static/dynamic and clone cases exercise callable trailing
commas. Class and enum constants add initializer contexts. Nullsafe and
constructor callable forms remain contextual rejections in RC4; mixing unpacking
and a named placeholder also rejects. The older interpreter rejects the new
syntax separately, without conflating its parser failure with RC4's compiler check.

Parent-set-hook evidence is now explicit:

- `coverage-parent-hook-pfa.php` and `coverage-parent-hook-fcc.php` are
  structurally accepted by 8.6 but reject with
  `Cannot create Closure for parent property hook call`.
- `coverage-parent-hook-repair.php` uses an ordinary parent set call and passes
  both versions.
- `coverage-parent-hook-discarded.php` places the PFA in a discarded enclosing
  closure: RC4 accepts; 8.5 still rejects its unsupported PFA syntax.

The pinned `Zend/tests/partial_application/hook.phpt`,
`property_hooks/parent_set.phpt`, and `zend_compile_parent_property_hook_call`
provide the source mechanism and controls. The helper rejects a callable marker
before compiling call arguments. The initial candidate's untyped setter parameter
triggered an earlier property-type check; using `set(int $value)` isolates the
intended callable prohibition. The rejection expectation was not weakened.
Parent-get-hook variants, every initializer combination, runtime signatures and
optimized traversal remain outside this finite expansion.

## 5. PHP86-003–007 contextual combinations

| Finding | New evidence | Stage and limitations |
|---|---|---|
| PHP86-003 | Compatible readonly inherited defaults; readonly-class static/untyped negatives; inherited type mismatch | Lint and declaration-link separated. Default acceptance is not permission to reassign. |
| PHP86-004 | Reference binding and compound writes through constant-object property dimensions; dimension-only constant-array reference rejection; constant reassignment rejection | No assignment-expression-based write target is introduced. Direct class-constant assignment is a structural rejection, unlike valid property paths and contextual reference guards. PHP86-014 remains separate. |
| PHP86-005 | Transitive interface constant Override, enum case Override, missing enum target | Declaration linking, not structural rejection. Missing targets fail in both versions for their respective compiler rules. |
| PHP86-006 | Backed enum with nullable debug-info return; required-parameter and forbidden clone method negatives | Declaration linking is observed independently of lint. Nullable return deprecation remains accepted. |
| PHP86-007 | Chained nullsafe reference return/yield, value return control, dead statement and discarded closure | Actual visited compilation context matters. No blanket nullsafe call or iteration prohibition. |

The source map names compiler/inheritance/enum/API functions and relevant
locked PHPTs. The repository matcher still returns structural acceptance for
contextual negatives. External oracle evidence does not become native enforcement.
No fixture executes arbitrary runtime programs: only reviewed declaration-link
fixtures execute; ordinary expression and lexical candidates are lint-only.

## 6. Lexical and scanner evidence

[The scanner matrix](../../tests/fixtures/php/8.6/coverage-lexical.json) adds
**31 source/profile entries** with explicit tokenization and source-validity
expectations. It covers tags and disabled short tags, name/high-byte boundaries,
enum/property keywords, composite yield-from, integer bases/separators/overflow,
floating literals, quoting/escapes, interpolation, backticks, heredoc/nowdoc,
comments, attributes, PFA punctuation, CRLF/trivia, halt payload and malformed
string/comment/heredoc/Unicode cases. Both version identities are tested.

Accepted token streams must preserve input bytes and contain the required token
categories. Nonaggregate entries compare native tokenizer lexeme boundaries;
aggregate string/backtick/heredoc/nowdoc entries retain their explicit abstraction
instead of claiming native per-token equivalence. One documented normalization
splits native `T_YIELD_FROM` into the canonical `yield`, `from` terminals. It
does not normalize arbitrary comments or whitespace. No lexer code changed.

Raw tokenization and full source validity are distinguished: an out-of-range
Unicode escape can be retained in a raw string token, but the matcher/string
validation and interpreter reject the source. Unterminated strings/comments and
bad heredoc indentation fail tokenization. An initially malformed test string
with two backslashes was repaired to a single escaped apostrophe; it was not
reclassified as accepted. Numeric malformed fixtures are not automatically
called scanner rejections merely because they concern numeral spelling.

There is still no claim of all 190 rules, all 11 states, or all recursive state
products independently covered. Reentrant exception handling, source encoding,
ZTS, full nesting/escape products and binding remain explicit gaps. The 8.5
scanner ledger and certification evidence are unchanged.

## 7. Version and diagnostic boundaries

**17 acceptance-boundary records** supplement the original 13, for **30 total**:
15 structural, eight lint and seven declaration-link. Each new record contains
the finding, fixture, first/last supported version, stage, immutable reference,
paired expected outcomes, observation report and provenance limitation.

The additions cover method/static/dynamic PFA, their callable trailing commas,
clone trailing comma, class/enum constant PFA, readonly inheritance, constant-object
reference/compound dimension paths, interface/enum Override, enum debug-info and
chained nullsafe reference return/yield. The generic structural runner continues
to reject nonstructural evaluation requests. The integration checker adds the
new reviewed case map to its stage-fixture allowlist and preserves unique
association, source checks and oracle verification.

[Two diagnostic records](../../tests/fixtures/php/8.6/diagnostic-boundaries.json)
cover accepted namespaced `LeT` function/method declarations and a return in a
closure nested inside finally. The first warns only in RC4; the latter is a
warning-free scope control in both versions. They remain accepted in both and
are not represented as syntax removals. Existing 67 deprecation witnesses remain
unchanged and are rechecked separately.

## 8. Inherited discarded-hook discrepancy

The affected production is `hook-name = "get" | "set"`, used by `property-hook`.
The pinned parser's `property_hook` takes `T_STRING`; the compiler's
`zend_get_property_hook_kind_from_name` and `zend_compile_property_hooks` reject
unknown names when compiling the declaration. The upstream `unknown_hook.phpt`
demonstrates the live rejection. The parent-hook source reviewed above is a
different constraint and is not substituted for unknown-name evidence.

Four permanent fixtures distinguish live, retained closure, discarded closure,
and repaired `get` forms. Both distribution interpreters reject live/retained
unknown hooks and accept discarded and repaired sources. Both grammars reject
all unknown-hook forms, including discarded. Thus the discrepancy is a known,
intentional specification restriction relative to Zend's folding-sensitive
acceptance, inherited from 8.5; no new RC4 interpreter defect is demonstrated.
Neither successful lint nor the broad parser identifier authorizes arbitrary
live hook names.

No correction is implemented. A separate proposal could move unknown-name
validation from unconditional EBNF restriction to visited-declaration context,
but it would require an explicit policy decision, independently scoped version
correction, live/retained/discarded and lexical/parser-boundary regression tests,
and honest contextual-enforcement documentation. Changing 8.5 remains outside
this upgrade. Gate B mechanism evidence exists; authorization to reverse the
intentional policy does not. The four fixtures remain outside ordinary positive
coverage and cannot be silently reclassified to increase percentages.

## 9. Final coverage and remaining gaps

| Metric | Phase 5A / reproduced baseline | Phase 5B | Change |
|---|---:|---:|---:|
| Positive production chart coverage | 209/361, 57.89% | 280/361, 77.56% | +71 productions, +19.67 percentage points |
| Positive alternative chart coverage | 223/800, 27.88% | 425/800, 53.13% | +202 alternatives, +25.25 percentage points |
| Acceptance-boundary records | 13 | 30 | +17 |

[The complete coverage ledger](coverage-expansion.json) retains every positive
witness, newly exercised identity and remaining classification. Among 81
unexercised productions, 52 are primitive-bypassed, 11 trivia-removed and **18
meaningful gaps** remain. Among 375 unexercised alternatives, 174 are primitive-
bypassed, nine trivia-removed, **191 meaningful gaps** remain and one enum
scanner-context identity remains independently unresolved.

The remaining productions comprise 14 closed-yield/prefix helpers,
`reserved-non-modifiers`, and unmatched declare/for/foreach statement forms.
These need source-valid whole-expression/dangling-else witnesses, not artificial
direct helper matching. Remaining alternatives include keyword positions,
prefix/yield/precedence combinations, type/member variants and contextual
corners. Passing a production does not establish every alternative or boundary.

PFA and contextual additions often add no raw identities because the grammar
intentionally shares their structure. Their value is the independently observed
context/stage distinction. No coverage target was used as a stopping rule;
the selected common syntax, implemented-finding, scanner and discrepancy batches
are complete within their documented bounds.

## 10. Traceability and validation

`grammar/8.6/changes.md` receives only material evidence updates: the newly
covered parent-set-hook scope, the new case map and preserved limitations.
All ten headings remain. The change map updates the reviewed document hash and
adds new finding fixture hashes; the five EBNF differences are unchanged.
No grammar, specification, lexer, public API or release gate is modified.

Reproduction from the repository root uses PHP tooling. Let `P` be
`rtk proxy .audit/php85/php.exe -n`; `U` adds
`-d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit
--do-not-cache-result --no-progress`.

```text
P tools/8.6/coverage-expansion.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/coverage-expansion-validation.json --coverage=docs/8.6/coverage-expansion.json
P tools/8.6/integration-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/coverage-expansion-integration.json
P tools/8.6/change-traceability.php --report=docs/8.6/coverage-expansion-traceability.json
P tools/validate-manifest.php
P bin/grammar-coverage.php 8.6
U
```

The expansion runner requires explicit interpreters and source trees; missing
inputs fail. The existing PHP 8.5.10/8.6.0RC4 distributions retain their locked
hashes and remain **not proven exact-pin builds**. Profile: `-n`, byte-oriented
source, no opcache/JIT, explicit short-tag settings, all diagnostics enabled.
The normal suite runs on 8.5.10; paired cases use both distributions.

The focused expansion PHPUnit selection passed **157 tests, 2,075 assertions**.
The paired expansion runner passed **2,310 checks, 310 observations**, zero
errors. These observations are 248 case/version records and 62 scanner/profile/
version records; linking records also retain their preceding lint result.
They are not 310 independent language features.

| Validation | Result |
|---|---|
| Full repository PHPUnit suite | PASS: 5,935 tests, 55,643 assertions, zero failures |
| Focused expansion PHPUnit | PASS: 157 tests, 2,075 assertions |
| Version-boundary PHPUnit | PASS: nine tests, 93 assertions |
| Paired expansion runner | PASS: 2,310 checks, 310 observations |
| Draft integration and 30 boundaries | PASS: 498 checks, 160 observations |
| Existing PFA regressions | PASS: 225 checks, 114 observations |
| Existing contextual regressions | PASS: 278 checks, 126 observations |
| Existing deprecation checks | PASS: 712 checks, 268 observations |
| Manifest validation | PASS |
| Change traceability | PASS: five modified productions, 13 documentary sections, no additions or removals |

The first final-suite run exposed an order-dependent assertion in the boundary
stage-count map. Sorting its keys preserves the exact stage/count expectations;
no check was removed. The focused boundary and full-suite reruns pass. The full
suite includes EBNF structure/reference integrity, Markdown parity, lexer and
conformance tests, public API/CLI regressions and historical PHP 8.5 regressions.

The [review ledger](coverage-expansion-review.json) retains actual command logs,
fixture/input hash checks and local-link results. The
[preservation ledger](coverage-expansion-preservation.json) checks all 1,787
baseline files, including 1,437 historical PHP 8.5 files and all eleven Phase 5A
artifacts. Only the five authorized existing files changed; EBNF, specification,
lexer and historical evidence remain unchanged.

## 11. Outstanding issues and Phase 5C readiness

| Priority / item | Remaining work and Gate B boundary |
|---|---|
| High — PHP86-013 / CA86-002 | Continue material compiler/AST/linking source reconciliation and relevant PHPT/RFC associations. Finite new cases do not close discovery. |
| High — PHP86-014 / CA86-003 | Assignment-result writable targets remain blocked pending intended-language and source-corresponding execution evidence. No such production was expanded. |
| High — PHP86-015 / CA86-004 | Optimized-call traversal remains unresolved. No universal folding or builtin-specific EBNF rule is introduced. |
| High — CA86-006 contextual integration | Isolate remaining RC4 predicates, declaration/linking contexts and live/discarded behavior. Structural acceptance is not compiler validation. |
| Medium — CA86-005/007/008 | Add parent-get-hook and wider initializer/ordering boundaries, remaining closed-yield and dangling-else witnesses, keyword/type/member alternatives and explicit negative/repair isolation. |
| Medium — CA86-009 | Extend scanner state products and rule-to-witness mapping; retain byte/category/aggregate distinctions. |
| Critical for unrestricted equivalence — CA86-001 | Preserve the documented hook policy divergence; any correction requires a separate proposal and explicit authorization. |
| High — CA86-010 | Exact-build provenance, later-RC/final review and independent release orchestration remain prerequisites for stronger certification. |

**Phase 5C: ready for targeted compiler/contextual reconciliation with bounded
scope.** The new fixtures and stage-specific observations provide a stronger
starting point. They do not authorize speculative corrections, close any of
PHP86-013/014/015, or establish candidate/stable readiness. PHP 8.6 remains draft;
Phase 5C has not started.
