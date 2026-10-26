# PHP 8.6 differential evidence inventory reconciliation

October 26, 2026 — Phase 7C.10. The expanded **339-case** ordinary corpus now has
an explicit inventory successor and authenticated exact-source observations with
**zero mismatches**. Historical inventories and runners remain unchanged.
PHP 8.6 remains **draft and uncertified**; no language or fixture expectation is
changed and none of the seven mixed certification blockers is closed.

## 1. Baseline and root cause

The [baseline](differential-inventory-reconciliation/baseline.json) records HEAD
`4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting working tree and
**3,819 original file hashes**. [Pre-edit verification](differential-inventory-reconciliation/baseline-checks.json)
passes the preceding current assessment, exact provenance/context prerequisites,
source locks, historical integrity and current compatibility. The preceding
38-obligation result was **28 passed / 1 failed / 9 blocked**.

The unchanged [legacy boundary validator](../../tools/8.6/certification-boundaries.php)
requires exact corpus membership against the retained
[Phase 6 inventory](source-audit-boundaries.json). That report has 313 cases;
Phase 7C.9 added 26 ordinary files. The
[reconciliation](differential-inventory-reconciliation/reconciliation.json)
confirms **339 current files, exactly 26 additions, zero removals and zero changed
original fixtures**. The original gate correctly refuses the membership
difference; it is not relabeled as having passed.

The predecessor SHA-256 is
`262e9549c65c36d8e75930bd6c14e9c8b7dbbdfe50f98a8c62e732f9a59e0f04`.
Its date, raw fixture hashes, metadata, diagnostics, outcomes and distribution
profile are preserved, including the explicit `exactPinExecutableVerified: false`.

## 2. Classification of the additions

| New cases | Count | Inclusion and validation boundary |
|---|---:|---|
| Accepted in lint and structure | 20 | Existing ordinary whole-source witnesses; no runtime validity claim |
| Accepted discarded declaration | 1 | `discarded-types`: parameter constraints are bypassed after enclosing declaration elimination |
| Structural-negative boundaries | 5 | Malformed alias, type, yield-from, hook terminator and alternative-syntax separator |

The positive witnesses cover trait aliases, types/references, members/promotions,
attributes, namespaces, control forms, equality, the property magic constant,
yield and prefix expressions. Every row retains its fixture hash, expected
lexical/structural/lint result, normalized diagnostic messages, source ranges,
validation stage and inclusion reason. `discarded-types` does not authorize
`never`, `void`, `self` or `parent` parameters in arbitrary live declarations.

The separate Phase 7C.9 inventories retain **35 contextual controls, 14 scanner
scenarios and five operand cases**. Their fixtures and forced binding counterparts
remain in the dedicated evidence directory. They are not silently added to the
ordinary corpus or excluded from their existing evidence contracts. The
[anchored Phase 7C.9 manifest](../../tests/fixtures/php/8.6/context-coverage-cases.json)
continues to bind those separate experiments.

## 3. Authorized versioned transition

The new [inventory](differential-inventory-reconciliation/inventory.json) identifies
contract `php86-ordinary-differential-v2`, revision `phase7c10-rc4`, its predecessor
and the Phase 7C.10 authority. Its 313 inherited rows embed each original row
verbatim. Its 26 added rows derive only from the hash-anchored Phase 7C.9 manifest
and authenticated observations; generation never learns expectations from a new
execution. Whole-record comparison rejects changes to the revision, predecessor,
classification, stage, source evidence or expected result.

The [PHP-only validator](../../tools/8.6/differential-inventory.php) separates
inventory creation, original integrity, current membership, execution and
verification. `--original-check` authenticates the original bytes and recorded
universe; it does not substitute for the unchanged legacy current-corpus gate.
`--inventory-check` requires the complete current universe and exact fixture
hashes. Extra, missing or modified files fail; no denominator or category is
relaxed to admit an unexplained change.

## 4. Source, executable and execution results

| Identity | Preserved value |
|---|---|
| PHP 8.6 RC4 source | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| Exact executable SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| Exact core DLL SHA-256 | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |
| Historical distribution SHA-256 | `c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a` |

The generator authenticates the complete existing build/provenance record and
source locks before running PHP. Additional pinned source ranges are verified
against both the clean source and authenticated build tree. It records driver,
executable and core hashes, input stability and post-execution source checks.
This retains the reviewed local controller custody model; it does not assert
independent bit-for-bit build reproduction.

[Fresh differential evidence](differential-inventory-reconciliation/differential.json)
contains **339 exact-source lint observations**, with native lexical and structural
results for the same 339 cases, and **zero mismatches**. These are different
measurements of one corpus, not 1,017 independent fixtures. Commands, raw outputs,
exit codes and expected classifications remain individually inspectable.

The inventory retains 208 accepted lint/structure cases, 11 accepted diagnostic
cases, one discarded-declaration positive, 52 structural/adapter rejections,
three lexical rejections, 60 compiler-contextual rejections and four deferred
declaration-link negatives. Lint combines parser and compiler checks; a lint
success is not proof that declaration linking or runtime execution succeeds.
The ordinary corpus is never arbitrarily executed at runtime.

The [fresh contextual profile](differential-inventory-reconciliation/context.json)
adds 126 paired-version stage observations under the explicit exact profile,
including controlled declaration-link cases, and passes independent verification.
The [PFA regression](differential-inventory-reconciliation/pfa.json) passes 225
checks / 114 paired observations. The existing version-boundary and compatibility
matrices retain their selected profiles; distribution observations are never
renamed as exact-source observations. No runtime anomaly is reclassified as a
parser rejection.

## 5. Freshness and independent verification

[Independent differential verification](differential-inventory-reconciliation/differential-check.json)
passes. It checks the separate receipt, deterministic inventory, complete live
membership, source/executable custody, current dependency hashes, retained command
arrays, outcomes and diagnostics, and recomputes native results. It does not rerun
lint or rewrite either inventory or observation report.

Diagnostic comparison removes transport paths, source line numbers and stack
frames while retaining primary diagnostic severity and message text. Messages
containing substantive clauses such as “in constant expression” are preserved.
Raw output is always retained. Diagnostic-only differences remain separate from
acceptance, compiler rejection and deferred linking.

## 6. Certification integration

The additive [successor runner](../../tools/8.6/certification-differential.php)
requires explicit `--contract php86-certification-v3`. It composes an independently
verified, freshly executed `php86-certification-v2` assessment with the independently
verified expanded differential report. The existing v2 runner and all legacy
commands are byte-identical.

Only `ordinary-differential` receives successor identity
`ordinary-differential@v2`. Its original obligation text, original failed result
and entire predecessor current row are preserved. The successor requires all
339 reviewed expectations under the exact-source profile and permits the
predecessor failure only when its log reports exactly 313 cases and solely the
known membership error. An additional legacy mismatch cannot be hidden by this
transition. Every other gate, blocker and hybrid prerequisite is inherited
unchanged. This remains **36 obligations plus two prerequisites**, not a new
39th release obligation.

The [fresh base assessment](certification-contract-remediation/runs/phase7c10-base/evidence.json),
[successor assessment](certification-contract-remediation/runs/phase7c10/evidence.json),
[execution/check/claims receipts](certification-contract-remediation/phase7c10-validation/checks.json)
and [final outcome counts](certification-contract-remediation/phase7c10-validation/outcomes.json)
distinguish original, v2 and successor results. Generation is not independent
verification. The claims operation must continue to refuse for mandatory
unresolved gates and blockers even when ordinary differential freshness passes.

## 7. Negative validation and compatibility

[Isolated negative tests](../../tests/DifferentialInventoryTest.php) pass
**16 tests / 92 assertions**. They reject missing additions, modified fixtures,
removal of original or added cases, unknown corpus additions, wrong predecessor
hashes, tampered revisions, classification/stage/source changes, unsupported
expectations, wrong profiles, lost observations, altered runtime custody,
stale inputs and tampered output receipts. Tests also verify that only the
authorized gate changes and that extra predecessor errors fail closed.
Invalid read-only checks preserve their isolated evidence and sentinel files.
Genuine evidence and fixture bytes are never mutated for these tests.

The [focused selection](differential-inventory-reconciliation/validation.json)
passes **812 tests / 7,369 assertions**, alongside both inventory checks, exact
context generation/verification, PFA, source locks, manifest, traceability and
whitespace. The [new current compatibility revision](../8.5/compatibility/phase7c10/evidence.json)
retains the full suite: **6,267 tests / 58,569 assertions**, zero failures.
Overlapping selections are not summed. Final command outcomes and separate
historical release limitations remain visible in the certification receipts.

## 8. Preservation and remaining blockers

The [preservation audit](certification-contract-remediation/phase7c10-validation/preservation.json)
checks all 3,819 baseline artifacts. PHP 8.5, both normative PHP 8.6 artifacts,
every existing fixture, source pin, policy, API, manifest status, inventory,
runner and earlier assessment remain unchanged. All implementation and evidence
are additive. [Artifact validation](certification-contract-remediation/phase7c10-validation/artifacts.json)
checks JSON, new PHP syntax, local documentation links, whitespace, original row
equality and final input stability; the
[artifact ledger](certification-contract-remediation/phase7c10-validation/evidence-files.json)
binds the final outputs.

PHP86-013, PHP86-014, PHP86-015, CA86-001, C6-CONTEXT, C6-COVERAGE and
C6-PROVENANCE retain their original blocking dispositions. Inventory freshness
does not establish discovery completeness, intended writable-target validity,
hook-scope acceptance, complete contextual enforcement, scanner/AST coverage or
final-release correspondence.

## 9. Reproduction and readiness

Run `tools/8.6/differential-inventory.php --original-check` and
`--inventory-check`; generate a new differential report with `--generate
--profile exact-pin-rc4 --report NEW`, then independently `--check` it. Preserve
all existing report paths. Generate and verify a new compatibility revision and
fresh v2 assessment using the existing current runner. Finally invoke
`tools/8.6/certification-differential.php --contract php86-certification-v3
--generate --revision NEW --assessment BASE --differential-report DIFFERENTIAL`;
verify and inspect claims using separate `--check --report REPORT` and
`--claims --report REPORT` operations. The retained receipts contain exact
commands and paths for this phase.

The reconciled inventory is ready for future blocker-specific evidence work.
Upstream language-validity clarification and independent completeness review
remain necessary. Prerelease certification and candidate promotion remain
**not ready**. No subsequent research, grammar correction, source upgrade,
certification grant or status-promotion phase was begun.
