# PHP 8.6 certification blocker triage and evidence closure

October 25, 2026 — Phase 7C.6. **Bounded triage is complete; PHP 8.6 remains
draft and uncertified.** Twenty-two source-hunk review obligations are discharged,
and already satisfied provenance/context prerequisites are formally verified.
None of the seven original mixed certification blockers is fully resolved.
No normative rule, fixture expectation, manifest status, source pin, policy or
existing release gate was changed.

## 1. Current baseline and evidence contracts

The [baseline](certification-blocker-closure/baseline.json) records repository
commit `4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting dirty working
tree, source pins, original file hashes and historical gate rows. Those old rows
are reference evidence, not newly executed results.

| Identity | Verified value |
|---|---|
| PHP 8.5 source | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| PHP 8.6 RC4 source | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| Exact-source executable SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| Exact-source core DLL SHA-256 | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |
| Legacy RC4 distribution SHA-256 | `c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a` |

[Baseline checks](certification-blocker-closure/baseline-checks.json) pass source
lock, exact-source provenance, historical integrity, current compatibility and
the exact contextual profile. Both 36- and 38-obligation inventories were read
from the existing runner. A separate [pre-edit compatibility check](certification-blocker-closure/preedit-compatibility.json)
also passes before new tooling/tests were added.

These are separate contracts: historical byte integrity authenticates 1,545 old
inputs; current compatibility verifies bounded current regressions; the context
profile authenticates 126 retained stage observations; provenance binds source,
configuration, build outputs and custody. None grants certification or replaces
the original historical aggregate check.

## 2. Complete blocker inventory

The [current dispositions](certification-blocker-closure/dispositions.json)
preserve each original definition verbatim and add current requirements, gate
dependencies, evidence, local/upstream/final-release dependencies and release
impact. The [resolution plan](certification-blocker-closure/remediation.json)
supplies concrete next actions. Original Phase 6 and Phase 7B records are unchanged.

Categories: **A** evidence available, verify it; **B** local evidence incomplete;
**C** upstream language validity unresolved; **D** certification scope limitation;
**E** final-release dependent. Mixed blockers have multiple categories because
their original requirements cannot be honestly reduced to one mechanism.

| Blocker | Mechanisms | Current disposition and remaining dependency |
|---|---|---|
| PHP86-013 | B, C | Blocking. 204 pending and 18 examined-unresolved hunks, bounded-group uncertainties, outside-ledger paths and test associations remain. |
| PHP86-014 | C | Blocking. Compiler/VM mechanism established; intended writable-target validity and coherent implementation treatment remain undecided. |
| PHP86-015 | C, B | Blocking. Traversal mechanism established; intended acceptance boundary and complete dispatch correspondence remain open. |
| CA86-001 | D | Blocking. Known discarded-hook divergence needs independent claim adjudication or separately authorized correction. |
| C6-CONTEXT | A, B, D | Blocking. Exact-profile matrix verified; individual predicate/stage evidence and enforcement-scope adjudication remain. |
| C6-COVERAGE | B, D | Blocking. Meaningful production/alternative, scanner and binding obligations remain; no raw percentage target is imposed. |
| C6-PROVENANCE | A, B, E | Partially satisfied, still blocking. Exact RC4 identity is verified; complete successful prerelease gate evidence, runner integration and later/final-release correspondence remain. |

Additional infrastructure dependencies are not omitted:

- **H85-FRESHNESS:** the original `historical-certification-check` remains
  mandatory and fails. Historical integrity plus bounded current compatibility
  are additive prerequisites, not substitutes for its release obligations.
- **H86-PRESERVATION:** the fresh `historical-preservation` gate rejects three
  policy/API documents changed in Phase 7C.2 against its fixed Phase 7A baseline.
  The [failure assessment](certification-blocker-closure/gate-failure-assessment.json)
  proves their current hashes already appear in the Phase 7C.2 verified revision
  and this phase's baseline. This is not a missing snapshot input or a new
  grammar/evidence modification. An obligation-preserving versioned baseline
  transition is needed; old expected hashes were not updated.
- **H86-AUDIT-FRESHNESS:** `parser-scanner-correspondence` and
  `rfc-implementation-traceability` invoke the same source-audit command. With
  the correct clean-source profile, it still fails its pre-audit preservation
  and historical ledger-freshness assertions. Source locks and the 176-production,
  623-alternative and 190-scanner-header counts remain corroborated. A versioned
  current audit contract must retain the original baseline and authorized change
  history; neither gate was disabled or its old ledger regenerated.
- **T7B-RUNNER:** the old orchestrator still uses fixed child paths,
  distribution-only generated provenance metadata, statically blocked closure
  gates and preparation-only claims. Its existing inspection modes recognize
  exact provenance/context evidence, but do not change gate decisions.
  The legacy authorization message is a runner/schema limitation, not a request
  to reauthorize this assessment.

All material unresolved requirements block candidate promotion and stable
certification. Draft work may continue within each stated evidence boundary.

## 3. Evidence newly verified and legitimate partial closure

The existing build verifier again authenticates the 235-file certification lock,
28,445 tracked source files, exact executable/core DLL, generated files,
toolchain, commands, logs and custody receipt. The existing context verifier
again validates all 126 historical/exact-profile stage observations against
live authenticated identities. These are fresh **verifications of retained
observations**, not 126 newly executed exact-source probes in this phase.

New execution includes 24 focused paired lint observations from twelve existing
fixtures, exact-source PFA/deprecation regressions, the existing gate sequence,
and a separate current-compatibility revision. The source-risk review preserves
full hunk IDs, original diff hashes, pinned source ranges, macro/helper evidence
and current dispositions. Its [read-only checker](../../tools/8.6/blocker-closure-check.php)
rejects lost identities, unsupported status changes and mismatched evidence.

Legitimately satisfied requirements are:

1. Exact RC4 source/build/generated-file/configuration/binary identity.
2. Authenticated execution support for the existing contextual matrix.
3. Historical byte-integrity and bounded current-compatibility prerequisites,
   each under its separate contract.
4. Twenty-two individually identified source-hunk review obligations below.

**No whole original blocker is marked resolved.** In particular, verifying a
build does not satisfy a mixed requirement that also demands complete successful
release evidence and final-source correspondence.

## 4. PHP86-013 targeted source-risk progress

The [source review](certification-blocker-closure/source-review.json) retains
all **578 original identities** and classifies 26 selected pending hunks in
14 semantic groups. Each retained diff digest matches the historical raw-hunk
ledger; both sides match the authenticated source ranges. Supplemental type,
compiler and error-declaration headers were fetched at both immutable commits
and compared byte-for-byte with the local trees.

| Reviewed scope | Hunks | Disposition |
|---|---:|---|
| Intersection redundancy loop braces | 2 | Unchanged macros, predicates, counter/break targets; bounded no-language-change exclusion |
| Unreachable releases after `zend_error_noreturn` | 1 | Both headers declare non-returning behavior; fatal predicates unchanged |
| `Z_CONSTANT` predicate expansion | 2 | Old macro expands to the same `IS_CONSTANT_AST` comparison |
| Unused virtual-property helper argument and callers | 3 | Removed parameter unused; callers have no removed side effect; body changes only C const qualifiers |
| Promoted-parameter local rename | 1 | Same active op-array, constructor and abstract checks |
| Magic-method helper/name substitutions | 2 | Unused argument removed; replacement constants have identical lowercase/literal values |
| Opening license comments | 7 | Exact comment-only ranges; no executable/preprocessor change |
| Readonly default, constant Override, nullable debugInfo diagnostic, PFA/unpack guard | 4 | Already implemented PHP86-003/005/010/001 findings, mapped to existing source and regression evidence |
| Mixed PFA/reference/callable construction | 2 | Examined, unresolved; local guards do not discharge every reference/dispatch path |
| Synthesized setter return-type AST | 1 | Examined, unresolved; needs scoped transitive type/name-resolution proof |
| Enum allocation/arginfo/case identity | 1 | Examined, unresolved; registration and constant-evaluation associations remain |

[Semantic proofs](certification-blocker-closure/semantic-proofs.json) retain the
macro identities and full helper bodies used for the narrow exclusions.
[Finding associations](certification-blocker-closure/finding-associations.json)
identify the existing readonly, Override, nullable-debugInfo and unpack case IDs
and their authenticated observation reports individually.
The [focused probes](certification-blocker-closure/focused-probes.json) use existing
live/retained/discarded type and promotion fixtures plus hook controls: **12 cases,
24 paired lint observations, zero failures**. They are supporting finite
observations, not a proof of all transitive compiler behavior.

Pending direct review decreases **230 → 204**. Examined-unresolved increases
**14 → 18**, because four genuinely mixed hunks are now explicitly reviewed.
The combined pending/unresolved count decreases **244 → 222**: 18 bounded
no-language-change exclusions and four existing-finding associations. All
14 original examined-unresolved entries remain unresolved. The 28 preexisting
bounded-group references retain their uncertainties, as do outside-ledger
optimizer, VM, reentrant linking and test/component paths. These numbers are
review obligations, not counts of language changes or a completeness percentage.

## 5. PHP86-014 writable-target disposition

No new authoritative intended-validity decision was found in the bounded
refresh. [PR 20628](https://github.com/php/php-src/pull/20628) metadata and all
retained issue/review comment bodies match the prior review. Existing exact-pin
evidence explains TMP/CONST containers reaching VAR/CV-only write handlers;
successful reference-derived controls do not establish a general permission.
The 26-control investigation was not rerun.

Upstream clarification is still needed for the exact ordinary-assignment,
reference-assignment and list-derived dimension/property targets: should the
compiler reject each source form, or emit supported writable operands? A coherent
fix and source-corresponding operation/result-kind/live/discarded evidence must
follow that decision. Runtime invalid-opcode failures are not parser rejection.
No new EBNF restriction is justified by these anomalies.

## 6. PHP86-015 optimized-call disposition

[PR 19658](https://github.com/php/php-src/pull/19658) and its retained discussions
are likewise unchanged. No new intended boundary was established. The existing
31-control exact-source matrix remains the evidence for optimized constant
evaluation versus ordinary compilation; it was not broadly repeated.

The unresolved question is whether early validation of otherwise discarded ASTs
through optimized `printf`/`sprintf` paths is intended source validity or an
optimization artifact, and which direct/imported/qualified/namespace/dynamic/
named/unpack/PFA and initializer contexts define that boundary. An unconditional
argument-traversal rule would exceed the evidence. Existing Phase 5D informative
wording remains accurate; no normative correction is established.

## 7. Upstream refresh scope and limitations

The [retained API refresh](certification-blocker-closure/upstream-refresh.json)
records URLs, retrieval times, raw response hashes, comparisons and pagination
bounds. PR 20628 has 17 issue comments and 15 review comments; PR 19658 has eight
and 15. Relevant metadata/comment records are unchanged. Two narrowly dated
issue searches returned zero writable/opcode hits and one printf/constant hit.

That hit, [issue 24683](https://github.com/php/php-src/issues/24683), describes
stale runtime values in tracing JIT on PHP 8.5.12. It does not decide compile-time
discarded-cast validity or the unoptimized writable-target forms. It is retained
and classified rather than treated as corrective evidence. Search absence is
not exhaustive proof that no other upstream discussion exists. No maintainer
was contacted and no moving source revision replaced the normative pin.

## 8. Contextual obligations and scope

The [context/coverage triage](certification-blocker-closure/coverage-context-triage.json)
retains all 73 inherited restriction descriptions. They are not 73 independently
isolated RC4 predicates. The remaining work distinguishes parser actions,
compile-time constraints, deferred declaration linking and controlled runtime,
with individual source/test/stage correspondence where only family references
exist. Historical readonly-default labels must reflect PHP86-003's relaxation.

Missing predicate evidence and missing validator functionality are separate
obligations. The authenticated 126-observation profile resolves executable
selection for that matrix, not full contextual enforcement. Native whole-source
validation remains incomplete.

Existing policy permits a **named, bounded conformance-validated claim** and a
grammar claim with documented external scanner/context constraints. It does not
permit a complete-validator claim, automatic acceptance of an unexplained
mismatch, or silent waiver of mandatory gates. CA86-001 therefore remains open:
the intentional get/set-only structural restriction versus discarded unknown-hook
acceptance needs independent certification-scope adjudication. A documented
limitation is not automatically an accepted release limitation. Policy was not
changed to obtain a passing claim.

## 9. Coverage and binding obligations

All **64 unexercised production and 318 alternative identities** are retained.
Their 382 rows span 311 lexical/scanner/token-category entries, seven other
structure/name entries, 11 statement/control entries, 33 declaration/type/member/
attribute entries and 20 expression/prefix/argument entries. These are triage
families, not additional exclusions or newly measured scanner hits.

For ordinary alternatives, the next action is a focused witness or an independently
justified identity-specific disposition. For lexical bodies bypassed by token
primitives, use scanner primitive/state/error evidence instead of inventing chart
coverage. Scanner obligations retain 190 headers and 11 states; 51 source/profile
entries overlap 34 ranges, with 156 ranges retaining family-only evidence in that
matrix. Range overlap is not an instrumented hit.

Individual parser-branch and AST/operand correspondence remains necessary,
including closed-yield/prefix/dangling-else and interpolation binding. A missing
witness alone proves no grammar defect. Policy requires no arbitrary 100% fixture
target, but meaningful unexplained coverage or binding gaps still block release.
No new normative defect was established by this assessment.

## 10. Provenance reassessment

The exact RC4 build and contextual-profile prerequisites are **verified**, not
still “missing.” Their original historical statements remain historical. The
current mixed C6-PROVENANCE blocker retains successful complete prerelease gate
evidence, versioned runner/freshness integration and later/final-source review.

The build is source-authenticated under the reviewed local controller/host custody
model. It is not an independently demonstrated bit-for-bit reproducible build.
Neither an independent second build nor bitwise reproduction is required by the
current policy or exact-pin gate; this review does not invent either as a blocker.
The full upstream PHPT suite was not executed, and no full-suite claim is made.

Final-release correspondence is genuinely release-dependent. RC4 source identity
cannot establish compatibility with a future final source revision. By contrast,
predicate mapping, source-risk reduction and existing-runner evidence can proceed
now. Final-release work is not used as a reason to postpone those local obligations.

## 11. Fresh certification gate execution

The [snapshot driver](../../tools/8.6/certification-triage-run.php) copies current
project inputs and installed dependencies byte-for-byte into a new isolated
directory, then invokes the **unchanged existing runner**. The legacy matrix uses
its required hash-locked distribution; exact-source provenance/context checks
and exact-source regressions are recorded separately. Supplying the exact binary
to the legacy distribution-only checker would be the wrong profile.

`--prepare` executes gate commands. `--check` validates its new ledger;
`--claims` and `--run` retain refusal semantics. The latter is a readiness
inspection, not a hidden second gate execution. No historical output is overwritten.

<!-- gates:start -->
Fresh **36-gate** execution: **26 commands executed**, **22 passed / 4 failed / 10 blocked**. Ten blocked obligations were evaluated without closure-test execution.

[Execution receipt](certification-blocker-closure/run-verified/execution.json), [exported ledger](certification-blocker-closure/run-verified/artifacts/docs/8.6/final-evidence.json) and [complete gate assessment](certification-blocker-closure/gate-assessment.json) retain every identifier, command, outcome and dependency. Fresh snapshot `--check` passes; `--claims` and `--run` refuse. Historical 25/1/10 totals are not reused.

| Failed gate | Actual remaining dependency |
|---|---|
| `parser-scanner-correspondence` | H86-AUDIT-FRESHNESS; [log](certification-blocker-closure/run-verified/artifacts/docs/8.6/certification/logs/parser-scanner-correspondence.log) |
| `rfc-implementation-traceability` | H86-AUDIT-FRESHNESS; [log](certification-blocker-closure/run-verified/artifacts/docs/8.6/certification/logs/rfc-implementation-traceability.log) |
| `historical-preservation` | H86-PRESERVATION; [log](certification-blocker-closure/run-verified/artifacts/docs/8.6/certification/logs/historical-preservation.log) |
| `historical-certification-check` | H85-FRESHNESS; [log](certification-blocker-closure/run-verified/artifacts/docs/8.6/certification/logs/historical-certification-check.log) |

The corrected run uses the authenticated clean-source tree. The first run is preserved under `certification-blocker-closure/run/`; its build-tree scanner-header absence error is not a final source mismatch. The two source-audit gates still fail their old preservation/freshness assertions on the correct profile. Their shared failure is not two independent language defects.

The two opt-in prerequisites separately pass. Additive **38-obligation accounting** is **24 passed / 4 failed / 10 blocked**, comprising 26 legacy command executions and two independent prerequisite verifications. This is not a claim that `--run` executes 38 tests or that the two new prerequisites replace a failed gate.
<!-- gates:end -->

The original 36 obligations and the hybrid contract's two additional prerequisites
must not be conflated. Historical byte integrity and current compatibility remain
independent checks, not replacements for either failed legacy gate. Exact-profile
inspection is a separate prerequisite verification, not an extra counted release
gate or permission to relabel the statically blocked exact-pin gate.

## 12. Validation and preservation

[Supporting receipts](certification-blocker-closure/supporting-checks.json)
record exact-source PFA (**225 checks / 114 observations**), deprecation
(**712 checks / 268 observations**), contextual-profile verification, source-review
integrity, traceability, manifest and whitespace checks. All pass. The dedicated
certification/provenance/context/triage negative selection passes **36 tests /
235 assertions**. Overlapping test selections are not summed.

<!-- validation:start -->
The corrected snapshot and final current-compatibility revision each pass **6,195 tests / 58,288 assertions**, zero PHPUnit failures. [Current compatibility](../8.5/compatibility/phase7c6-verified/evidence.json) passes all **15 commands** against **3,925 dependencies**, unchanged during generation; independent verification passes. The earlier `phase7c6` revision is preserved as a checkpoint predating the helper default correction.

[Final checks](certification-blocker-closure/final-validation.json) retain independent current/historical verification and hybrid claims/run refusal. The [preservation audit](certification-blocker-closure/preservation.json) verifies **all 2,798 baseline files byte-identical**. The snapshot checker preserves **2,977 monitored run paths**. [Artifact validation](certification-blocker-closure/artifact-validation.json) covers JSON, PHP syntax, links, whitespace and retained digests; the [artifact ledger](certification-blocker-closure/evidence-files.json) records final phase file hashes.

Four release-gate failures and ten blocked obligations remain explicitly recorded; passing PHPUnit and current compatibility do not suppress them.
<!-- validation:end -->

The only implementation additions are the bounded review checker/library,
negative tests and snapshot orchestration helper. No existing tool or gate was
edited. The current JSON dispositions, source review, remediation and coverage
triage are additive successors that preserve original definitions and observations.

## 13. Actionable next-phase readiness

| Work | Readiness and exact dependency |
|---|---|
| Targeted source/context remediation | Ready for bounded evidence work: mixed reference/PFA dispatch, synthesized setter types, enum constant/registration paths and reentrant interface graphs; retain each original identity. |
| PHP86-014/015 normative remediation | Not ready for speculative grammar edits; obtain exact-form authoritative intent and coherent source correspondence first. |
| Hook/context bounded certification claim | Ready for a concrete independent scope decision; current limitations are not automatically accepted. |
| Coverage closure | Ready for prioritized witnesses/mappings and justified scanner/binding dispositions; no arbitrary percentage target. |
| Provenance/runner evidence integration | Verified artifacts are available; an obligation-preserving versioned contract must address static gates, both historical failures and full release requirements. |
| Independent prerelease certification | Not ready: mandatory language, discovery, contextual, coverage and infrastructure obligations remain. |
| Candidate promotion | Not ready; PHP 8.6 stays draft. |
| Final PHP 8.6 reconciliation | Procedure is ready: authenticate the final source, review changes incrementally from RC4, verify source/executable correspondence and run all required release gates. No final-release evidence is established by this reviewed record. |

No subsequent remediation, certification-contract redesign, source-pin upgrade
or promotion phase was begun.
