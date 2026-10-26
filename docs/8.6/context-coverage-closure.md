# PHP 8.6 contextual and coverage evidence closure

October 26, 2026 — Phase 7C.9. **Bounded evidence is strengthened; PHP 8.6
remains draft and uncertified.** C6-CONTEXT and C6-COVERAGE remain blocking.
Seven compiler predicates across six inherited descriptions now have focused
source and stage witnesses. Ordinary coverage increases by one production and
42 alternatives. No normative grammar, specification, API, source pin, policy,
manifest status or existing release obligation changes.

## 1. Baseline and authenticated profile

The [baseline](context-coverage-closure/baseline.json) records repository commit
`4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting working tree and
original file hashes. [Baseline checks](context-coverage-closure/baseline-checks.json)
verified the preceding current certification record, source lock, historical
integrity and separate current compatibility before this phase published inputs.
The historical current assessment was **29 passed / 0 failed / 9 blocked** across
38 obligations. It is not presented as this phase's fresh result.

| Identity | Value |
|---|---|
| PHP 8.6 RC4 source | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| PHP 8.5 source | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| Exact executable SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| Exact core DLL SHA-256 | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |

The new [evidence tool](../../tools/8.6/context-coverage.php) authenticates the
existing source/build custody record before interpreter execution. It checks
additional source ranges against both the clean source and authenticated build
tree, records input hashes, and checks executable/core identity and source ranges
again afterward. Its read-only `--check` verifies retained observations and
recomputes native matching and coverage; it does not repeat interpreter probes.
The separate receipt binds the report under the existing local custody model,
not a cryptographic signature or independent reproducible-build claim.

## 2. Finite work inventory

The [context inventory](context-coverage-closure/context-inventory.json) preserves
all **73 original descriptions**, including their original classification,
sources, fixtures, specification clauses and outstanding requirements. Selected
predicates are P1 work; other descriptions retain P2 follow-up obligations.
Descriptions are not counts of independently isolated compiler predicates.

The [coverage successor](context-coverage-closure/coverage-delta.json) preserves
all **64 production and 318 alternative** starting obligations with their original
family and reason, current witness status, stage, source correspondence and
remaining scope. The [scanner inventory](context-coverage-closure/scanner-inventory.json)
retains all **190 headers and 11 states**. The
[binding inventory](context-coverage-closure/binding-inventory.json) retains the
earlier binding obligations. No missing identity is removed from a denominator.

Compiler validity remains external to native structural matching. Local witnesses
can strengthen those obligations without implementing a compiler. Outstanding
material contextual, branch, scanner and binding obligations remain mandatory for
candidate and stable certification under the existing policy.

## 3. Contextual predicates and stage evidence

The [fixture manifest](../../tests/fixtures/php/8.6/context-coverage-cases.json)
binds every fixture, source range, function and normative clause. The
[observations](context-coverage-closure/observations-verified.json) retain commands,
diagnostics, exit codes and native results. Seven predicates receive five controls
each: a valid repair, a live negative, a retained-closure negative, an invalid
declaration in a discarded closure, and an unreachable `if (false)` negative.

| Description | Predicate | RC4 `Zend/zend_compile.c` function/range |
|---|---|---|
| C6-C018 | Variadic default value | `zend_compile_params`, 8473–8485 |
| C6-C019 | Duplicate parameter name | `zend_compile_params`, 8458–8468 |
| C6-C028 | Empty hook list | `zend_compile_property_hooks`, 9358–9371 |
| C6-C028 | Duplicate hook | `zend_compile_property_hooks`, 9487–9498 |
| C6-C029 | Getter parameter parentheses | `zend_compile_property_hooks`, 9431–9441 |
| C6-C034 | Invalid enum backing type | `zend_compile_enum_backing_type`, 9924–9941 |
| C6-C069 | Try without catch/finally | `zend_compile_try`, 7389–7405 |

All **35 controls** match their expected outcomes: 14 lint acceptances and 21
diagnostic rejections. Native structural matching accepts these structurally
representable forms. The rejected predicates are compiler checks, not newly
implemented native enforcement. Lint includes parsing and compilation; source
predicates and paired retained/discarded controls establish the narrower stage
interpretation. No runtime execution or general declaration-linking conclusion is
drawn from these lints.

Enclosing closure elimination avoids these selected checks; an unreachable
statement does not. The nine pinned PHPT files in the manifest support source
correspondence and diagnostics; they were inspected and authenticated, not run as
an upstream PHPT suite. Composite descriptions remain open: variadic ordering,
setter parameter combinations and other transitive declaration checks are not
discharged by these particular witnesses.

## 4. Existing stage-dependent evidence

The [retained stage index](context-coverage-closure/retained-stage-evidence.json)
preserves individual IDs, stages, versions, fixture hashes and observation hashes
for the existing 126-observation exact-profile matrix. Its verification is in
[regression receipts](context-coverage-closure/regressions.json). These are freshly
verified retained observations, not 126 newly executed probes.

Readonly defaults, Override, nullsafe references, constant expressions, PFA and
discarded declarations retain their finding-specific boundaries. Historical
readonly-default labels do not undo PHP86-003's relaxation. Separate exact-source
PFA and PHP86-010 deprecation regressions were executed as required validation:
**225 checks / 114 observations** and **712 checks / 268 observations**,
respectively, with zero failures. These paired-version totals are distinct from
the 80 new case records and are not added to the retained contextual matrix.
CA86-001 remains unresolved: discarded unknown-hook acceptance does not establish
intended validity or authorize changing the inherited get/set-only structure.

## 5. Ordinary production and alternative coverage

| Completed ordinary chart identities | Before | After | Gain |
|---|---:|---:|---:|
| Productions | 297/361 | 298/361 | 1 |
| Alternatives | 482/800 | 524/800 | 42 |

The new production is `reserved-non-modifiers`, witnessed through trait aliases.
The 21 positive ordinary fixtures cover aliases, types and references, members and
promotions, attributes, namespaces, control forms, yield, prefix/pipe/concatenation
forms, equality and the property magic constant. Five structural negatives test
missing aliases, malformed type boundaries, missing yield-from operands, hook
terminators and alternative-syntax separators. Each is also checked by the exact
interpreter. Selected alternative IDs are checked individually; merely matching a
broader production is insufficient.

The measurement uses accepted ordinary `valid/*.php` files, completed chart items
and no extra rule-level cases. Dedicated context/scanner/binding fixtures are
excluded from this metric. Remaining identities are **63 productions and 276
alternatives**. Primitive-bypassed lexical bodies and removed trivia require
appropriate scanner evidence; fabricated ordinary hits would misstate coverage.
Completed chart items do not establish unique parse trees or full Zend branch
coverage. Historical coverage reports and their fixed audit baselines are unchanged.

The [preflight](context-coverage-closure/preflight.json) retains one corrected
draft expectation: `A& $x` is a valid typed reference parameter. It is now an
explicit positive control; `A&|B $x` supplies the malformed boundary. No old
expectation or normative grammar was changed. The
[initial observation record](context-coverage-closure/observations-initial.json)
is preserved; the successor adds reviewed parser-range associations. Its first
independent check rejected a stale test-file hash after an in-flight test edit.
That [checkpoint](context-coverage-closure/observations.json) and failed check are
retained. The verified revision captures the stabilized inputs; no failed
freshness check is relabeled as passing.

## 6. Scanner correspondence

Fourteen scenarios address attribute/comment/close-tag handling, property keywords,
PFA trivia, comment-separated yield-from, numeric boundaries, interpolation and
offsets, legacy variable names, backticks, heredoc return, literal nowdoc, disabled
short tags, indentation failure and Unicode boundaries. They associate with **21
source headers and 10 named states**. These are source-associated behavioral
observations: **zero instrumented header or state hits are claimed**. All 11 state
identities remain in the inventory, including the unselected state.

Five scenarios compare token boundaries directly, with explicit trivia removal,
boundary whitespace trimming and splitting Zend's composite `T_YIELD_FROM` into
the native yield/from representation. Aggregate native string tokens prevent
token-for-token comparisons for the string cases. Byte preservation is checked for
accepted native lexer scenarios; exact tokenization and lint are retained separately.

Unicode overflow is accepted by raw native lexing and rejected during exact-source
lint. This does not become a lexer validity claim. Unicode escape helper evidence
is associated with its source function range, not an invented scanner-header hit.
General error recovery and exhaustive state/rule correspondence remain open.

## 7. Operand and binding correspondence

Five expressions have a unique native derivation and equal nonempty pre-optimizer
opcode sequences between original and native-operand-parenthesized forms:
`-$a ** $b`, `!$a instanceof C`, `yield $a + $b => $c * $d`,
`yield from $a = $b`, and `f(?, $a + $b)`.

The checker binds both fixture byte strings to the expression and derived operand
spans before invoking the exact interpreter. Compilation uses OPcache dump mode
with optimization and JIT disabled; the expressions are not executed. The native
derivation helper uses the existing PHP 8.5 token adapter, while both compilation
observations use authenticated PHP 8.6. This is finite operand/opcode
correspondence, **not AST equivalence**. Interpolation ASTs, declaration-member
relationships, reference-result categories, broader PFA dispatch and PHP86-014
writable-target validity remain open.

## 8. Blocker reassessment and certification

The [successor dispositions](context-coverage-closure/dispositions.json) retain
every original blocker definition verbatim. C6-CONTEXT has seven strengthened
predicate obligations; C6-COVERAGE has 43 new chart witnesses and bounded scanner
and operand evidence. Neither whole blocker is closed. PHP86-013, PHP86-014,
PHP86-015, CA86-001 and C6-PROVENANCE remain blocking. No new independently
established normative defect or Gate B correction is asserted.

The [fresh versioned certification record](certification-contract-remediation/runs/phase7c9/evidence.json)
and [outcome counts](certification-contract-remediation/phase7c9-validation/outcomes.json)
are authoritative for this phase's passed/failed/blocked totals. The
[execution/check/claims receipts](certification-contract-remediation/phase7c9-validation/checks.json)
distinguish generation, read-only validation and claim refusal. Original 36-gate
results, current 36-obligation results and additive 38-obligation results remain
separate. Passing evidence checks grant no release claim and do not waive blocked
gates or historical release failures.

## 9. Validation and preservation

[Dedicated validation](context-coverage-closure/validation.json) covers the new
evidence checker, focused contextual/scanner/binding and version-boundary tests,
manifest, traceability, historical integrity and whitespace. The focused suite
passes **1,391 tests / 12,855 assertions**; the four dedicated new tests contribute
24 assertions within that selection. The new negative
tests reject missing observations, wrong profiles, altered outcomes, absent
selected identities, empty opcode comparisons, changed runtime custody, changed
denominators and fixture/derivation substitution. The
[final observation verification](context-coverage-closure/verified-check.json)
supersedes only the preliminary stale-input failure.

The new [current compatibility revision](../8.5/compatibility/phase7c9/evidence.json)
retains the full PHPUnit run (**6,251 tests / 58,477 assertions**, zero failures)
and all 15 compatibility commands. Command outcomes and certification counts are
recorded in the linked final receipts. Compatibility is an additive current contract, not a rewrite of
PHP 8.5 historical certification. Overlapping test selections are not summed.

The [preservation audit](certification-contract-remediation/phase7c9-validation/preservation.json)
checks all **3,545 baseline files** byte-for-byte. The
[artifact validation](certification-contract-remediation/phase7c9-validation/artifacts.json)
checks JSON, local links, new tooling syntax, whitespace and retained original
inventory rows. The [artifact ledger](certification-contract-remediation/phase7c9-validation/evidence-files.json)
binds the phase outputs. Existing normative, historical, policy, API, gate and
source-lock artifacts are preserved; all additions belong to this bounded phase.

## 10. Next-phase readiness

Further bounded source-risk work and predicate/scanner/binding evidence are ready
to proceed in a separately scoped phase. Upstream intended-validity clarification
is still necessary for PHP86-014/015 and the disputed discarded-hook boundary.
Independent prerelease certification and candidate promotion remain **not ready**.
Final-release correspondence is not established by RC4 evidence. No subsequent
source-risk remediation, contract redesign, source upgrade or promotion was begun.
