# PHP 8.6 certification evidence and runner contract remediation

October 25, 2026 — Phase 7C.7. PHP 8.6 remains **draft and uncertified**.
This phase adds an explicit current assessment contract. It does not change
normative grammar, fixture expectations, source pins, consumer APIs, policy,
legacy commands, or historical evidence. All seven mixed blockers remain open.

## 1. Baseline and original failures

The [baseline](certification-contract-remediation/baseline.json) records HEAD,
the preexisting dirty working tree and all original project-file hashes.
[Pre-edit checks](certification-contract-remediation/baseline-checks.json) passed
exact-source provenance, source locks, historical integrity, current compatibility
and exact contextual evidence before implementation. They also reproduced the
historical freshness and preservation failures and the shared audit failure.
The two audit gate identifiers use one underlying command.

The source remains `f23b4c222cebe33be93ee8ecaaa14390c5507a08`; the exact executable
remains `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`.
The historical distribution retains its independent profile and hash.

## 2. Versioned contracts and historical dependency

The separate [current runner](../../tools/8.6/certification-current.php) requires
`--contract php86-certification-v2`. The [schema](../../schema/certification-current.schema.json)
is separate from legacy evidence schema 1, the existing hybrid contract and
manifest schema 1.0. The original runner and its default refusal semantics
remain byte-identical.

`historical-release-dependency@v2` is a current dependency decision, not renewal
of PHP 8.5 certification. It requires independently authenticated historical
bytes, a fresh independently verified current-compatibility revision, complete
current input membership, and all 27 original PHP 8.5 release-command outcomes.
Twenty-five must succeed. The original final aggregate must refuse **solely**
with `Stale certification artifact: docs/8.5/final-evidence.json`.
The lexer gate must execute all 2,700 direct cases, 101 primitives and 144 syntax
cases and fail solely its historical ledger freshness comparison. Its distinct
`lexer-coverage-shared-selector@v2` successor independently recomputes the whole
ledger and authenticates the existing [Phase 4B review](shared-lexer-regression.json).
Only the exact reviewed `src/Php/Lexing/PhpVersion.php` before/after hash may differ;
every other input, mapping, family and disposition must remain identical. The
review's raw hash and both complete ledger hashes are fixed trust anchors.
Any additional failure, missing command, altered source, unexplained diagnostic,
or incomplete compatibility evidence blocks this successor dependency.

This restricted transition replaces the historical repository-wide byte-equality
condition and the precisely reviewed shared-selector ledger hash only in the
explicitly selected successor dependencies. The unchanged
aggregate still reconstructs its complete substantive evidence before reaching
that refusal. Its failed result remains visible under its original identity;
it is never relabeled a successful legacy certification. Complete current
dependencies, all other release obligations, separate historical authentication
and bounded current regressions supply the new dependency evidence together.
The 15-command compatibility pass alone is insufficient. PHP 8.5's original
bounded claim and C1–C4 limitations remain historical and are not inherited as
PHP 8.6 acceptance decisions.

The first 27-command run exposed the lexer ledger condition that the bounded
compatibility matrix did not cover. Its [investigation](certification-contract-remediation/lexer-freshness-investigation.json)
proves the sole difference is the already reviewed selector hash. The initial
run remains preserved; no historical ledger was regenerated to obtain a pass.
The new [dependency verifier](../../tools/lib/HistoricalReleaseDependency.php)
requires the retained original failure and new independent correspondence proof
together. The original release commands still report 25 passed and two failed.

## 3. Restricted preservation transition

[Preservation86](../../tools/lib/Preservation86.php) anchors both original
baselines, the original source ledger, Phase 7C.2 compatibility record and
Phase 7C.6 blocker dispositions. Its closed transition permits exactly the
recorded before/after hashes of `docs/api.md`, `docs/conformance-policy.md` and
`docs/versioning.md`. It verifies the successor hashes against the anchored
Phase 7C.2 dependency records and retains every original expected hash.
Every other predecessor file must remain byte-identical. No caller can extend
the allowlist or supply replacement expected hashes.

Generation writes a distinct transition record. Independent verification
reconstructs that record from the reviewed authority and compares every current
successor byte. Historical compatibility records serve as authenticated past
change evidence, not a claim that those older compatibility runs are current.

## 4. Current source audit

`php86-current-source-audit-v2` preserves the entire original ledger, source
locks, parser branches, scanner rows, RFC dispositions and unresolved scope.
The unchanged audit runs in an isolated projection containing all current
computational dependencies. Only the three preservation-only policy documents
use their authenticated predecessor bytes there. Those documents are not semantic
inputs to the audit: their sole read in that command is baseline hash comparison.
Their actual current bytes are independently enforced by the transition verifier.

The projection is never the working tree or a rewritten historical record.
The independent check reconstructs its complete dependency membership, verifies
every byte, then reruns the unchanged audit's `--check`. It compares the complete
computed ledger, not just counts. This retains 176 parser productions,
623 alternatives and 190 scanner headers, with every original mapping limitation.
Missing parser/scanner/RFC correspondence or altered locks cannot pass merely
because headline totals agree. No completeness claim follows from audit freshness.

## 5. Execution, profiles and independent verification

`--generate` requires a new revision and explicit `exact-pin-rc4` and
`historical-rc4` selections. It authenticates interpreter identities before
execution, creates separate legacy, audit and PHP 8.5 release snapshots, and
retains commands, logs, outputs and snapshot identities. The unchanged 36-gate
runner uses its hash-locked distribution. Exact-source provenance and context
are independently verified under their own profile; they do not silently
relabel distribution observations as exact-source observations.

`--check` verifies without generating evidence or executing contextual probes.
It authenticates live source/build custody, context, compatibility, original
blockers, transitions, current input membership and execution artifacts; it
recomputes source correspondence in the authenticated projection. It detects
stale or incomplete evidence and records before/after artifact integrity.
The exact-context verifier includes the complete live build/provenance check.
A successful proof is shared with the provenance gate only when the selected,
context-recorded and build-recorded executable paths are identical. If context
verification fails, provenance is checked separately. This is one shared
verification, not two newly executed test matrices. Selected binaries, adjacent
core/runtime DLLs and release extensions are hashed before and after execution
and verification, alongside the retained release ini profile.
Temporary process capture files are outside the retained evidence tree.
Generation success is not independent verification.

The current dependency set includes all source, tests, grammar, tools, CLI,
schema, vendor and ordinary documentation, plus root configuration and anchored
predecessors. Revision output directories, old compatibility output revisions
and this explanatory report are excluded as outputs. The selected compatibility
revision and every current run artifact are separately bound and independently
checked. Policy, API, source and executable dependencies are never excluded.
Trust remains the existing reviewed local controller/host custody model, not
signatures or protection against coordinated replacement of tooling and all
trusted receipts. No second build or bitwise reproducibility requirement is added.

## 6. Gate decisions and accounting

All 36 original identifiers and obligation texts remain, with their original
execution rows embedded. Each current row names its contract, inputs, source,
profile, command, expected/actual evidence, freshness, execution state, result
and blocking dependencies. Preservation and both audit gates have explicit
`@v2` successors. Exact provenance can satisfy its narrow gate after independent
verification while C6-PROVENANCE retains complete release and final-source work.

Closure gates read independently verified current blocker dispositions and
remaining requirements. An absent or unsupported closure record fails; it cannot
turn a blocked gate into a pass. Unresolved language validity, blocked
prerequisites, non-execution, retained verification and fresh execution remain
distinct. Claims evaluate substantive missing requirements without the obsolete
Phase 7A authorization message. They do not promote a manifest or grant certification.

The two hybrid prerequisites remain separate from the original 36. Shared audit,
integration and traceability commands are identified as shared obligations;
neither their labels nor verification of retained context observations are
presented as additional independent test suites. The additional 27-command
dependency assessment is reported separately from the 38-obligation denominator.

<!-- results:start -->
| Assessment | Actual outcome |
|---|---|
| Fresh original 36-gate run | **22 passed / 4 failed / 10 blocked** |
| Versioned current 36-obligation assessment | **27 passed / 0 failed / 9 blocked** |
| Current 38-obligation assessment, including both hybrid prerequisites | **29 passed / 0 failed / 9 blocked** |
| Separate original PHP 8.5 release run | **25 passed / 2 failed**, all 27 commands executed |

The original 36-gate run executed **26 command invocations representing 23 distinct command arrays**. Shared commands are not independent test suites. The two additional hybrid prerequisites are separately verified obligations, not extra legacy gate executions.

[Current evidence](certification-contract-remediation/runs/phase7c7-verified/evidence.json), [original ledger](certification-contract-remediation/runs/phase7c7-verified/legacy/docs/8.6/final-evidence.json), [gate assessment](certification-contract-remediation/gate-assessment.json), [restricted transition](certification-contract-remediation/runs/phase7c7-verified/transition.json), [current audit](certification-contract-remediation/runs/phase7c7-verified/audit.json) and [lexer transition](certification-contract-remediation/runs/phase7c7-verified/lexer-transition.json) retain the complete identities and decisions. Independent current `--check` passes; `--claims` refuses for substantive unmet obligations, without a Phase 7A authorization error.

The legacy historical aggregate, legacy preservation and two legacy audit gates still fail under their original contracts. Their explicitly versioned successors pass their independent checks. Exact-pin provenance also passes its narrow successor gate. Nine substantive closure obligations remain blocked; all seven original mixed blockers remain active. The initial `phase7c7` run and compatibility revision remain preserved checkpoints predating the lexer-transition integration.
<!-- results:end -->

## 7. Negative validation and preservation

[Isolated tests](../../tests/CertificationCurrentTest.php) exercise unauthorized
baseline bytes, incorrect transition authority, missing predecessors, tampered
successor hashes, changed shared policy, stale audit dependencies, lost parser
and scanner correspondence, incorrect RFC evidence, modified locks, wrong
executables and profiles, tampered compatibility bytes, missing gates, incorrect
dependencies, fabricated execution, blocker removal, false claims, historical
evidence substitution and non-writing failure paths. Additional tests reject
an incomplete 27-command dependency and unexplained aggregate failures.
Real historical evidence is never modified for negative scenarios.

<!-- validation:start -->
The final legacy snapshot and current-compatibility full suites pass **6,221 tests / 58,338 assertions**, with zero PHPUnit failures. The dedicated certification/provenance/context/transition selection passes **62 tests / 285 assertions**; overlapping selections are not summed.

[Current compatibility](../8.5/compatibility/phase7c7-verified/evidence.json) passes all **15 commands** against **3,934 dependencies**, unchanged during generation, and passes independent verification. [Final receipts](certification-contract-remediation/final-validation.json) retain current check/claims, historical integrity, legacy refusals, source locks, manifest, negative tests and whitespace commands with actual exits.

[Preservation](certification-contract-remediation/final-preservation.json) verifies **all 3,093 pre-phase files byte-identical**. All existing tools, policies, fixtures, normative artifacts, source locks, manifest status and earlier evidence are preserved. [Artifact validation](certification-contract-remediation/final-artifact-validation.json) covers PHP syntax, JSON, local links and whitespace; the [artifact ledger](certification-contract-remediation/final-evidence-files.json) binds final implementation and phase evidence. The runner also binds **46 executable/core/runtime/extension files** and verifies artifact stability during read-only checks.
<!-- validation:end -->

## 8. Remaining blockers and readiness

PHP86-013 retains source/compiler discovery gaps; PHP86-014 and PHP86-015 retain
unresolved intended-validity boundaries. CA86-001 needs independent hook-claim
adjudication. C6-CONTEXT needs predicate/stage evidence and scope adjudication;
C6-COVERAGE needs meaningful production/alternative, scanner and binding closure.
C6-PROVENANCE retains successful complete release evidence and later/final-source
correspondence even when exact RC4 identity is verified. No whole blocker closes.

Targeted evidence work is ready within the existing bounds; speculative language
changes are not. Contextual and coverage closure can proceed with focused
witnesses and mappings. Independent prerelease certification and candidate
promotion remain blocked by substantive obligations. Final reconciliation still
requires an authenticated final revision, incremental review and release gates;
none was begun here. PHP 8.5 remains stable/default and PHP 8.6 remains draft.

## 9. Reproduction

First generate and independently verify a new current-compatibility revision.
Then select a new assessment revision:

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-current.php --contract php86-certification-v2 --generate --revision NEW --profile exact-pin-rc4 --legacyProfile historical-rc4 --compatibility-report docs/8.5/compatibility/NEW/evidence.json
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-current.php --contract php86-certification-v2 --check --report docs/8.6/certification-contract-remediation/runs/NEW/evidence.json
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-current.php --contract php86-certification-v2 --claims --report docs/8.6/certification-contract-remediation/runs/NEW/evidence.json
```

The final command must refuse while mandatory blockers remain. Local source,
build, historical and execution snapshots are verification dependencies; their
absence is a failure, not permission to trust JSON alone.
