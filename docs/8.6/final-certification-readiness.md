# PHP 8.6 final certification readiness and completion roadmap

October 26, 2026 — Phase 7D. **Retain `draft`. Independent prerelease
certification and candidate promotion are not ready.** The selected Phase 7C.10
current assessment records **29 passed / 0 failed / 9 blocked** across 38
obligations. All seven mixed blockers remain open. Zero failing observations in
the named ordinary corpus does not establish complete language correspondence.
This phase assesses evidence and prepares follow-up work; it changes no grammar,
fixture, policy, gate, source pin, blocker disposition or package status.

## 1. Baseline and evidence boundary

The [baseline](final-certification-readiness/baseline.json) records HEAD,
preexisting working-tree status and per-file SHA-256 digests. HEAD is
`4625873a6bac908b5cff4c677dba4ac10dd050e0`; the substantial existing uncommitted
work is part of the reviewed baseline, not a clean-HEAD assertion.

| Identity | Value |
|---|---|
| PHP 8.6 RC4 source | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| PHP 8.5 source | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| Exact RC4 executable SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| Exact RC4 core DLL SHA-256 | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |
| Legacy RC4 distribution SHA-256 | `c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a` |

The [build contract](exact-pin-build/build.json) binds source, generated files,
configuration, tools, logs and binary custody under the reviewed local host and
controller model. It is not an independently reproduced bit-for-bit build.
The PHP 8.5 comparator remains its authenticated distribution profile. The legacy
distribution and exact-source RC4 profiles are not interchangeable.

The policy basis is [conformance policy](../conformance-policy.md),
[versioning and prerelease review](../versioning.md), and
[source evidence requirements](../sources.md), applied with repository AGENTS.md.
The standalone [EBNF](../../grammar/8.6/php.ebnf),
[specification](../../grammar/8.6/php.md) and
[changes document](../../grammar/8.6/changes.md) remain authoritative at their
existing scope. Historical prose about previously missing build or runner
integration must be read with its dated successors; it is not a current missing
prerequisite finding.

## 2. Current and historical gate accounting

The selected [v3 assessment](certification-contract-remediation/runs/phase7c10/evidence.json)
composes the independently verified
[v2 execution](certification-contract-remediation/runs/phase7c10-base/evidence.json)
with the reviewed [339-case inventory](differential-inventory-reconciliation/inventory.json).
Only `ordinary-differential` receives the Phase 7C.10 differential successor.
Phase 7C.7 preservation/audit/provenance transitions remain explicit and retain
their original requirements and historical outcomes.

| Assessment | Passed | Failed | Blocked |
|---|---:|---:|---:|
| Selected current v3, 36 obligations | 27 | 0 | 9 |
| Selected current v3 plus two independent prerequisites | 29 | 0 | 9 |
| Retained v2 predecessor, 38 obligations | 28 | 1 | 9 |
| Retained original PHP 8.6 run, 36 gates | 21 | 5 | 10 |
| Separate retained original PHP 8.5 release run, 27 gates | 25 | 2 | 0 |

These are execution/assessment results from Phase 7C.10, independently checked
in this phase before publishing its documents. They are not 38 newly executed
gate commands. Historical integrity authenticates old bytes; bounded current
compatibility checks current regressions; neither renews historical release
certification. The original failures remain visible in their original contracts.

The exact-pin executable gate already passes its narrow successor requirement.
No new gate update is proposed: each of the nine blocked rows still has material
unmet requirements. Existing `--claims` refuses with nine unmet gates and seven
blockers. Passing PHPUnit cannot override that refusal.

**Publication freshness boundary.** The unchanged global certification inventory
includes ordinary documentation. Adding this report and its evidence creates a
documentation-only membership delta after baseline verification. The retained
29/0/9 assessment therefore describes the verified prepublication tree, not a
fresh execution covering these added documents. The
[publication audit](final-certification-readiness/publication-validation.json)
records the exact additions and final read-only freshness outcome. No exclusions
were changed and no old report was regenerated. D-C01 requires a new execution
revision after follow-up inputs stabilize; until then the published tree must
not be advertised as having a live-fresh global certification receipt.

## 3. Nine-gate/seven-blocker dependency matrix

The [remaining-work ledger](final-certification-readiness/remaining-work.json)
copies each original gate row and all seven original blocker rows, including
their original definitions. It adds evidence, remaining requirements, local and
upstream dependencies, timing and candidate/stable impact. The quoted gate
requirements below are unchanged.

| Blocked gate | Original requirement | Direct blocker | Work items |
|---|---|---|---|
| `production-coverage-closure` | Disposition every uncovered production with independent evidence; unresolved meaningful gaps block. | C6-COVERAGE | D-S01 |
| `alternative-coverage-closure` | Disposition every uncovered alternative, including context-only from; percentages do not waive gaps. | C6-COVERAGE | D-S01 |
| `scanner-state-closure` | Supply independent RC4 scanner state/helper/error products; header equality is insufficient. | C6-COVERAGE | D-S02 |
| `ast-binding` | Supply independent RC4 operand/AST and interpolation binding evidence, including retained ambiguities. | C6-COVERAGE | D-S03 |
| `folding-order` | Resolve optimized-call traversal and independently reconcile live/retained/discarded validation order. | PHP86-015 | D-U02 |
| `discovery-closure` | Resolve outstanding syntax-relevant compiler/AST/linking/optimizer discovery records. | PHP86-013 | D-L01, D-L02 |
| `writable-target-validity` | Establish intended writable-target validity and compiler/VM consistency. | PHP86-014 | D-U01 |
| `contextual-enforcement-scope` | Adjudicate incomplete contextual enforcement and predicate-level evidence for the bounded claim. | C6-CONTEXT | D-L03, D-U03 |
| `discarded-hook-disposition` | Independently adjudicate the inherited get/set-only discrepancy; do not inherit an 8.5 conclusion. | CA86-001 | D-U03 |

**C6-PROVENANCE depends on successful complete release evidence across all nine
rows**, and separately on final-source correspondence. It is not a tenth blocked
gate, nor grounds to relabel the passed `exact-pin-executable` gate as blocked.
PHP86-014/015 also constrain discovery and contextual scope; CA86-001 constrains
contextual scope; writable result categories intersect binding evidence.

All nine can be assessed now, but none has evidence sufficient for a pass. Local
source/predicate/witness work is available for all relevant areas. Intended
validity for PHP86-014/015 and the hook question is still upstream-dependent;
dependent discovery/context/binding subobligations cannot erase those questions.
No RC4 assessment intrinsically requires final-release source, though a coherent
implementation fix may require a separately reviewed later pin. Each remaining
material requirement blocks both candidate and stable status. Final compatibility
requires revisiting every gate against the actual final source.

## 4. Local source and contextual evidence

The [Phase 7C.6 review](certification-blocker-closure/source-review.json) retains
all 578 raw-hunk identities: 204 pending and 18 examined-unresolved remain, plus
28 bounded-group references with their uncertainties and the recorded
outside-ledger paths. Twenty-two narrow review obligations were discharged;
they are not 22 proven language changes or a completeness percentage.

D-L01 prioritizes mixed PFA/reference/callable dispatch, synthesized setter
return-type AST, enum allocation/arginfo/case identity and the existing reentrant
interface graphs. D-L02 then exhausts the already enumerated residual queue,
including named optimizer/VM/linking and upstream-test/component associations.
Both tasks preserve exact hunk IDs and diff hashes. This is a bounded queue and
its named transitive dependencies, not authorization to restart an unrestricted
audit of the entire PHP source tree. Review may find a genuine correction; that
would require separate Gate B work, not a speculative edit in this assessment.

The [context inventory](context-coverage-closure/context-inventory.json) preserves
73 descriptions, not 73 isolated predicates. The existing scope adjudication
classifies eight as separate modifier-list API checks, three as direct structural,
two as partial structural, one as known structural divergence, two as partial
lexical and 57 as external contextual. These categories describe enforcement
limits, not accepted release waivers.

Seven predicates across six descriptions have 35 focused controls. Composite
descriptions remain open. D-L03 starts with variadic/default/order and duplicate
parameter distinctions, hook combinations, enum backing type and try/finally
boundaries, then covers each remaining description's individual predicates and
stages. Early parser actions, visited compilation, deferred declaration linking
and controlled runtime must remain distinct. The 126-observation exact context
profile authenticates a named finite matrix; it does not enforce every contextual
restriction in the native matcher. Readonly-default labels must reflect
PHP86-003's relaxation.

Local evidence closure is feasible without implementing a complete PHP compiler.
It still requires an independently justified bounded claim and complete evidence
for its material obligations. External enforcement must be disclosed and cannot
turn structural acceptance into a whole-source-validity verdict.

## 5. Upstream-dependent language questions

Three **unpublished** maintainer-facing drafts retain exact fixture bytes, hashes,
paired observations, source locations and the relevant discussion context:

- [PHP86-014 draft](final-certification-readiness/PHP86-014-draft.md): should
  ordinary/reference/list-derived writes reject at compilation or emit supported
  writable operands? Ordinary assignment produces TMP, literal-list bases can
  produce CONST, while handlers require VAR/CV. Successful reference-derived
  controls do not establish a general permission. PR 20628 does not resolve the
  exact intended boundary. A coherent implementation and operation/result-kind
  matrix must follow the intended-validity decision.
- [PHP86-015 draft](final-certification-readiness/PHP86-015-draft.md): is early
  traversal through optimized `printf`/`sprintf` intended source validity or an
  optimization artifact? Logical, ternary and coalescing behavior and
  direct/imported/qualified/namespace/dynamic/named/unpack/PFA dispatch remain
  separate. PR 19658 establishes mechanism, not a universal validity rule.
- [CA86-001 draft](final-certification-readiness/CA86-001-draft.md): should an
  unknown hook in an eliminated closure be accepted? The parser admits `T_STRING`;
  visited compilation checks get/set. Both versions accept the retained discarded
  witnesses and reject live/retained unknown hooks, while both repository grammars
  reject unknown names. The constant witness is a ternary initializer, not proof
  of all constant-folding behavior. No specific authoritative issue deciding this
  intent is retained.

For CA86-001, an independently reviewed, enforceable claim exclusion is also a
policy-permitted scope route. It needs evidence and explicit acceptance; it is
not already granted. Authoritative intent is necessary before asserting a general
language-validity rule, not an invented prerequisite for every possible bounded
scope decision.

The [draft evidence index](final-certification-readiness/upstream-draft-evidence.json)
preserves exact observations and JSON pointers into the authenticated 26-control
writable, 31-control traversal and five paired hook investigations. No experiment
was rerun for these drafts. Lint is not a pure parser verdict; invalid opcode is
not parser rejection; absent opcode output after rejection is not independent
evidence. The last bounded upstream refresh is Phase 7C.6, not a new Phase 7D
internet search. No maintainer was contacted and nothing was published externally.

## 6. Coverage, scanner and binding requirements

Ordinary completed chart identities are **298/361 productions and 524/800
alternatives**. The [successor coverage ledger](context-coverage-closure/coverage-delta.json)
retains the original 64/318 obligations and records the 1/42 improvements, leaving
63/276. D-S01 requires each remaining identity to have an appropriate witness or
independently justified disposition. Lexical bodies bypassed by token primitives
and removed trivia need scanner evidence, not invented chart hits. Covered
family mappings also do not establish individual Zend branch equivalence.

The [scanner inventory](context-coverage-closure/scanner-inventory.json) retains
190 headers and 11 states. Fourteen scenarios associate with 21 headers and ten
state names; **zero instrumented header/state hits are claimed**. D-S02 addresses
the remaining named state, recursive return/error/EOF products, primitive helpers,
and explicit encoding/configuration bounds. Aggregate native string tokens limit
token-by-token comparisons. Header equality and overlapping source ranges do not
prove execution or complete scanner correspondence.

The [binding inventory](context-coverage-closure/binding-inventory.json) has five
finite native-operand/forced-parentheses/preoptimizer-opcode witnesses. These are
not AST equivalence. D-S03 requires individual retained-ambiguity, closed-yield,
prefix, dangling-else, interpolation, member and reference/PFA correspondence,
using actual AST/action evidence where opcode equality cannot distinguish it.
An uncovered identity does not prove a grammar defect; an unexplained meaningful
gap still blocks release. No arbitrary percentage target is introduced.

## 7. Bounded statements and prohibited claims

| Proposed statement | Assessment and required qualification |
|---|---|
| Standalone canonical EBNF integrity, references and Markdown parity | Supported within the validated repository checks; canonical does not mean PHP-endorsed or complete. |
| Source-backed structural correspondence | Supported only for named reviewed productions/findings and explicit scanner/context abstractions. Full branch correspondence is missing; 176 productions/623 alternatives are an inventory, not a proof. |
| Differential conformance | Supported for the named 339-case inventory and exact RC4 profile, with stage-specific expectations and zero mismatches. Deferred linking and contextual negatives must not be described as native rejection. |
| Lexical/scanner correspondence | Qualified finite state/token/source observations are supported. Complete state/helper/error correspondence remains missing. |
| Documented externally enforced contextual constraints | Supported with the individual enforcement/stage limits and exact matrix bounds. Not a complete native validator or automatic accepted scope limitation. |
| Limited prerelease certification now | Prohibited while mandatory material gates/blockers remain. A bounded evidence statement is not a release waiver. |
| Grammar-complete / complete source-language validity | Not established; discovery, intended validity, contextual, coverage and binding evidence remain incomplete. |
| Complete interpreter/compiler or exhaustive equivalence | Unsupported and prohibited; no mathematical/exhaustive proof exists. |
| Final-release compatibility, candidate or stable readiness | Not supported by RC4 evidence; no promotion is justified. |

Under [existing policy](../conformance-policy.md), a named bounded
“conformance-validated” matrix statement can be made with these qualifications.
It does not authorize independent prerelease certification with the remaining
blockers. PHP 8.5's accepted limitations cannot be imported as PHP 8.6 waivers.
No policy change is proposed to obtain certification.

## 8. RC4 work versus final-release dependencies

| Obligation | Can proceed against RC4 now | What requires later/final source |
|---|---|---|
| Source correctness | D-L01/D-L02 source review and D-U01–03 intent questions | Review any authoritative fix at its actual immutable revision; no moving-pin substitution. |
| Evidence completeness | Predicate/stage, ordinary identity, scanner and binding work | Incrementally revisit all affected mappings and observations after source changes. |
| Certification infrastructure | Build/context/inventory integration already exists; new stable-input execution receipts can be generated | Configure and independently verify the actual final interpreter/profile. |
| Release correspondence | Preserve RC4 identity and complete its remaining prerequisites | Authenticate final source; reconcile parser/scanner/compiler/AST/VM/optimizer/linking/tests/generated files from last reviewed pin. |
| Final validation | Prepare explicit procedures and dependency inventory | Run full required release gates and differential matrices against final source/executable; decide promotion only from resulting evidence. |

The exact RC4 build is verified, not still missing. Runner/inventory integration
has advanced through v3. Remaining C6-PROVENANCE obligations are complete
successful release evidence, current dependency freshness and later/final-source
correspondence. Independent second compilation and bitwise reproducibility are
optional confidence improvements, not newly invented blockers. A full upstream
PHPT campaign is not claimed or made an automatic prerequisite; relevant pinned
test correspondence remains required. Final-source uncertainty is not a reason
to postpone locally resolvable RC4 work.

## 9. Finite completion roadmap

The machine-readable ledger gives **exact repository input paths, original
requirements, commands, dependencies, deliverables and objective completion
criteria** for each item. All eleven are mandatory within their stated claim
scope; optional quality improvements are separately labeled. Completing a tranche
does not automatically close a mixed blocker.

| Workstream | Priority and stable item | Required deliverable and completion boundary |
|---|---|---|
| 1. Local source and contextual evidence | D-L01, first | Selected high-risk hunk/helper/PHPT proof ledger; no unsupported whole-group closure. |
| 1. Local source and contextual evidence | D-L02, after first tranche | All enumerated residual hunk/group/outside-ledger obligations individually resolved or explicitly blocking; zero lost identities or unexplained material paths. |
| 1. Local source and contextual evidence | D-L03, parallel | Predicate/stage/enforcement ledger for all 73 descriptions and independently justified scope; no material missing predicate correspondence. |
| 2. Upstream language-validity clarification | D-U01, early | Attributed writable-target intent and coherent operation/result-kind treatment. |
| 2. Upstream language-validity clarification | D-U02, early | Attributed optimized-call intent and dispatch/traversal correspondence. |
| 2. Upstream language-validity clarification | D-U03, early | Attributed hook intent for a general rule, or independently justified enforceable scope exclusion; explicit PHP 8.6 adjudication either way. |
| 3. Scanner, coverage and binding | D-S01, parallel | Per-identity witness/disposition for 63 productions and 276 alternatives; preserve prior identities and corpus expectations. |
| 3. Scanner, coverage and binding | D-S02, parallel | Independent state/helper/error products for the recorded scanner obligations, with explicit profile bounds. |
| 3. Scanner, coverage and binding | D-S03, parallel | Individual binding/ambiguity/interpolation correspondence beyond the five operand witnesses. |
| 4. Certification execution and final source | D-C01, after stabilized evidence | Verified compatibility and new v2 execution/v3 successor receipts; independently verified closure decisions and all required prerelease obligations satisfied. Regenerate compatibility only if its bounded inputs changed. |
| 4. Certification execution and final source | D-C02, after final release | Authenticated final source/build, incremental reconciliation and full successful final-profile validation. |

D-L03 and D-S03 can start independent local portions while their disputed
boundaries await D-U03 and D-U01. D-L02 depends on D-U01/02 only for affected
semantic closure. D-S01 delegates lexical identities to D-S02 without counting
them twice. External questions can be prepared now; sending these drafts is
outside this phase. An answer is not assumed to arrive or to justify a particular
grammar rule.

Existing read-only commands in the ledger validate retained input contracts;
they do **not** automatically validate newly proposed closure proofs. The follow-up
implementation must supply successor-aware verification and negative checks for
new evidence, preserve the old rows and use separate report paths. Current
`context-coverage.php --check --report <successor>` can validate supported
observation successors; expanded predicate/identity semantics require explicit
reviewed checker support. Final-profile commands cannot truthfully be specified
until the final pin/build exists. These are explicit implementation dependencies,
not claims that unavailable tooling already passes.

## 10. Validation and preservation

<!-- validation:start -->
Fresh read-only verification passes source-lock integrity, exact-source build and
context provenance, historical byte integrity, current compatibility, all 38
current obligation records, the 339-case differential inventory/report, retained
context/coverage classifications, blocker integrity, change traceability and
manifest validation. [Command receipts](final-certification-readiness/validation.json)
retain actual commands, exits and log hashes. The global current check passes
before publication; claims refuses with **16 errors: nine gates and seven
blockers**, as required. No release grant is inferred.

The full PHPUnit suite freshly passes **6267 tests / 58569 assertions**,
zero failures. It includes EBNF/reference integrity, Markdown parity, version
boundaries and historical regression checks. The 339 differential cases and 126
context stage observations are independently verified retained evidence in this
phase, not newly executed interpreter probes. No new full release run, upstream
PHPT suite, build or upstream search is claimed.

All **4002 baseline files** remain byte-identical before publication.
JSON syntax, local documentation links, exact original gate/blocker rows, retained
reproducer observations, evidence hashes and whitespace are checked in the final
[publication audit](final-certification-readiness/publication-validation.json).
That audit records the expected post-publication global freshness refusal caused
by added assessment documents; the substantive 29/0/9 result remains the verified
prepublication assessment.
<!-- validation:end -->

The [preservation audit](final-certification-readiness/publication-validation.json)
compares the pre-phase files byte-for-byte. New files consist only of this report,
the remaining-work ledger, three unpublished drafts and supporting assessment
receipts. Historical PHP 8.5 artifacts, both PHP 8.6 normative files, changes.md,
source pins, existing evidence, tools, tests, policies, manifest and gate contracts
remain unchanged. PHP 8.6 remains `draft`.

## 11. Decision

Proceed next with the finite local evidence work and separately authorized
upstream clarification. Prepare complete certification execution only after
material closure evidence and stable inputs exist. Retain draft status meanwhile.
No gate or blocker is proposed as newly satisfied, no normative correction is
established by this assessment, and no next phase or promotion has begun.
