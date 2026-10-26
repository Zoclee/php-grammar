# PHP 8.6 conformance scope and contextual validation adjudication

October 26, 2026 — Phase 7C.8. **PHP 8.6 remains draft and uncertified.**
CA86-001 and C6-CONTEXT remain blocking. This independent review establishes
their narrower evidence and enforcement boundaries; it neither changes the
language nor accepts their remaining requirements as release exclusions.

## 1. Baseline and authority

The [baseline](conformance-scope-adjudication/baseline.json) records HEAD
`4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting working tree and
hashes of all **3,412** starting project artifacts.
The [scope record](conformance-scope-adjudication/scope.json) separately binds
the grammar/specifications, policy, matcher, source inventories, historical
claim and retained exact-source observations. Earlier records remain unchanged.

The authoritative contracts are [AGENTS.md](../../AGENTS.md),
[conformance policy](../conformance-policy.md), [versioning](../versioning.md),
[grammar conventions](../grammar-conventions.md), [source policy](../sources.md)
and the [consumer API contract](../api.md). Canonical does not mean complete;
conformance-validated must name its audited matrices and configuration. A
structural match is not a whole-source validity verdict. Documented external
constraints can support a bounded grammar claim only with independently
justified scope and the required release evidence.

| Identity | Preserved value |
|---|---|
| PHP 8.5 pin | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| PHP 8.6 RC4 pin | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| Exact-source executable SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| PHP 8.6 EBNF SHA-256 | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| PHP 8.6 specification SHA-256 | `45688b7d36ff16d823360f9072f43aa8e52c4dbd9189c890a6fdeef592bca521` |

The prior versioned assessment is **27 passed / 0 failed / 9 blocked** for
36 obligations, or **29 / 0 / 9** including the two hybrid prerequisites.
These are retained Phase 7C.7 results, not this phase's executions. Its original
legacy run remains 22 / 4 / 10. The baseline verification attempted after
writing this phase's baseline correctly rejects the old current report as
`Stale or incomplete current dependency set`: new research documents belong
to that contract. That failure is preserved, not called a pre-edit pass.

The new assessment uses the unchanged `php86-certification-v2` contract and a
distinct `phase7c8` revision. Its [execution record](certification-contract-remediation/runs/phase7c8/evidence.json)
and [independent checks](certification-contract-remediation/phase7c8-validation/checks.json)
are the authoritative fresh outcomes. No older ledger is regenerated.

## 2. Conformance layers

The [machine-readable matrix](conformance-scope-adjudication/scope.json) supplies
authority, capability, evidence, limitations and permitted/prohibited claims
for each of these eight layers.

| Layer | Implemented capability | Boundary on the claim |
|---|---|---|
| Structural EBNF | EBNF parsing/integrity, chart recognition, complete token consumption and Markdown production parity | No contextual verdict or native source AST; unknown discarded hooks remain a known false rejection relative to Zend. |
| Lexer/scanner | Version-selected byte lexer, scanner states, token primitives and interpolation fragment validation | Aggregated tokens are not Zend token identity; state/helper coverage and multibyte/ZTS scope remain bounded. |
| Contextual compiler rules | Normative prose, interpreter harness, separate supplied-list modifier validator | Matcher does not call the modifier validator or implement the full compiler predicates. |
| Declaration/inheritance linking | Controlled interpreter observations for named linking fixtures | No native class/name/member resolver; lint alone is insufficient. |
| Constant evaluation | Documented traversal and finite interpreter/folding evidence | No native ordered evaluator; optimized dispatch and transitive paths remain open. |
| Runtime | Reviewed, controlled interpreter fixtures | No general runtime correctness claim; runtime errors are not structural rejections. |
| Differential acceptance | Configuration-specific comparison against explicit lexical/structural/stage expectations | Finite named corpus only; contextual-negative structural acceptance is expected boundary evidence. |
| Complete source validation | Not implemented | No complete-validator, exhaustive-equivalence or unqualified fully-conformant claim. |

`PhpGrammarMatcher::matchesRule()` tokenizes, validates interpolation fragments,
adapts tokens and invokes `ChartMatcher`. It does not run declaration linking,
compiler predicate traversal or constant evaluation. `Php85ModifierValidator`
validates an already identified target and modifier list. A successful list
check neither identifies that list in source nor validates its declaration.
Its historical PHP 8.5 scope must not silently become a complete RC4 validator.

## 3. CA86-001: evidence and disposition

**Disposition D: unresolved intended-validity boundary, still blocking.**
The implementation mismatch itself is established; it is not an unknown test
outcome. The [hook record](conformance-scope-adjudication/hook-adjudication.json)
retains ten individual observation pointers, fixture hashes, outcomes, opcode
summaries and exact source slices. No new hook probes were needed or executed
for this adjudication.

Canonical `property-hook` uses `hook-name = "get" | "set"` at
`grammar/8.6/php.ebnf:788–795`. These case-insensitive terminals deliberately
exclude every other name, including in discarded declarations. The normative
[property-hook clause](../../grammar/8.6/php.md#property-hooks-and-promotion)
explicitly documents that compatibility difference. Both versions retain it.

At the RC4 pin, `Zend/zend_language_parser.y:1160–1169` accepts `T_STRING` in
`property_hook`. `zend_get_property_hook_kind_from_name` at
`Zend/zend_compile.c:9348` recognizes get/set; `zend_compile_property_hooks`
raises the unknown-name error at lines 9409–9414 when visiting the declaration.
The compiler's ordinary short-circuit path and constant-expression selection
can discard the enclosing closure before its class/property body is compiled.
The retained constant witness is a ternary initializer, not a claim that every
constant logical operator discards its right operand before validation.

| Existing fixture ID | Grammar, both versions | PHP 8.5.10 lint | Exact RC4 lint |
|---|---|---|---|
| `hook-live` | Reject | Reject, unknown hook | Reject, unknown hook |
| `hook-retained` | Reject | Reject, unknown hook | Reject, unknown hook |
| `hook-discarded` | Reject | Accept | Accept |
| `hook-constant-discarded` | Reject | Accept | Accept |
| `hook-get-repair` | Accept | Accept | Accept |

The exact-source pre-optimizer dump for the ordinary discarded case contains
only `RETURN int(1)`. The constant case declares `F` as `1` and returns; neither
emits the unknown-hook declaration. This corroborates source ordering before
OPcache optimization. `php -l` combines parsing and compilation; these are not
independent measured parser and compiler verdicts. PHP 8.5 remains the identified
distribution oracle, not an asserted exact-source build.

The alternatives are independently assessed:

- **A, verified grammar defect:** the EBNF demonstrably differs from pinned Zend
  acceptance. That alone does not settle the intended validity needed to reverse
  an explicit specification restriction in this phase. Any equivalence correction
  requires a separate Gate B proposal and historical-correction decision.
- **B, contextual limitation alone:** insufficient. The EBNF itself rejects the
  discarded source; it is not simply missing a later rejection check. Calling it
  purely external enforcement would hide a structural mismatch. A finite claim
  that excludes it must name and justify that exclusion explicitly.
- **C, compiler defect:** not established. Live unknown-hook rejection and a
  get/set manual rule do not establish that discarded declarations must reject.
  No authoritative intended-validity decision for those exact forms is present
  in the reviewed evidence.
- **D:** preserves both the proven mechanism and the unresolved intended
  boundary. Neither a normative correction nor a release exclusion follows.

The original requirement was an independent claim/policy disposition or a
separately authorized correction. The assessment component is now recorded, but
the assessment does **not approve** the mismatch for release. CA86-001 remains
blocking for independent PHP 8.6 certification and parser-equivalence claims.

## 4. All 73 contextual descriptions

The [individual inventory](conformance-scope-adjudication/restrictions.json)
preserves every original `C6-C001` through `C6-C073` record verbatim and adds
constructs, current normative clause, pinned function-family locations, refined
stage, supporting fixtures, native capability, interpreter evidence and remaining
obligation. The [readable 73-row table](conformance-scope-adjudication/restriction-table.md)
provides the individual classifications and next requirements.

| Primary capability classification | Descriptions | Exact identities |
|---|---:|---|
| Separate modifier-list checking, without whole-source integration | 8 | C001–C007, C040 |
| Direct structural representation | 3 | C013 DNF grouping, C042 qualified precedence reference, C065 dangling-else/alternative binding |
| Partial structural enforcement with external residue | 2 | C009 static by position, C072 halt scope |
| Structural restriction with known Zend divergence | 1 | C027 hook names |
| Partial lexical enforcement with external residue | 2 | C059 real/unset casts, C073 reserved/special names and nested declarations |
| External contextual requirement; surrounding structure remains native | 57 | All other retained identities |

These are primary classifications of descriptions, not disjoint compiler phases,
predicate counts or a completeness percentage. For example, the lexer supplies
tokens to structural rules and modifier descriptions combine early and late
checks. Direct structural representation does not discharge the broader
individual parser-branch or AST-binding evidence obligation.

C024 preserves its historical readonly-default wording only as history: the
current PHP86-003 rule permits defaults and retains type/static/hook and
inheritance checks. C029 distinguishes a set hook's reference return marker
from a forbidden reference parameter. C034 retains full parsed enum backing
type structure; int/string-only validity is a compiler check. C069 deliberately
allows a bare try structurally because a surviving compiler check rejects it.
C059 separates early scanner rejection of real casts from visited-AST rejection
of unset casts. None justifies an artificial unconditional EBNF restriction.

All rows retain honest source precision. Existing RC4 family entry points and
hashes are **not individual predicate proofs**. Historical fixtures are labeled
historical support; filename associations do not certify RC4 outcomes. Exact
changed-rule observations are linked separately and explicitly remain partial
for composite descriptions. Empty direct RC4 lists identify evidence still
missing, rather than implying that a historical test was rerun at the exact pin.

## 5. C6-CONTEXT and native versus external enforcement

**C6-CONTEXT remains blocking.** Its original requirement combines individual
RC4 predicate/upstream-test/stage evidence with independent enforcement-scope
adjudication. It does not require every restriction to become EBNF. Policy
allows external constraints but requires honest implementation status and a
justified bounded claim. That permission does not automatically discharge
missing predicate correspondence.

The exact contextual profile supplies 63 cases × two versions: **126 retained
stage observations** (76 lint, 44 declaration-link, six controlled-runtime).
Live authentication verifies their source/executable/configuration correspondence;
it is not 126 newly executed probes. It settles the matrix's executable-selection
prerequisite, not all 73 descriptions. Some descriptions contain several
predicates; changed-rule interactions add obligations beyond a literal count.

The independently complete narrow work is the capability/scope classification
and identification of the authenticated finite evidence. Outstanding work is
individual positive/negative and retained/discarded isolation where family
references remain; parser-action versus late compiler/linking/evaluation
classification; transitive changed-rule correspondence; and independently
justified exclusions for resolved name/value/member semantics. No complete
native contextual validator has been demonstrated or claimed.

## 6. Bounded claims under existing policy

The [claim records](conformance-scope-adjudication/scope.json) specify exact
scope, evidence, stages, exclusions, discrepancies, blockers and publication
conditions. This review permits the following **draft evidence statements**:

1. The unchanged canonical PHP 8.6 EBNF passes structural integrity/reference
   and Markdown parity checks. This says nothing about complete PHP validity.
2. Source inventory/mapping exists for 176 parser productions, 623 alternatives
   and 190 scanner headers, with the recorded family-level limitations. This is
   not a complete source-correspondence or grammar-completeness claim.
3. The **exact-context-63-stage-expectations** matrix has verified expected
   outcomes under its retained configuration, subject to successful live profile
   verification. Its bounds are the preexisting 63 identities in
   `context-cases.json`, both named oracles and their explicit stage expectations.
   No passing subset is selected after observing results. Contextual-negative
   structural matches remain expected boundary evidence under existing policy.

The third statement can be described as bounded conformance evidence for that
named matrix, with external enforcement disclosed. It must not be shortened to
“PHP 8.6 is conformance-validated” without the bounds. It excludes the separate
unknown-hook corpus, unlisted predicates, other configurations and general
linking/runtime correctness. CA86-001 remains prominently disclosed and blocking;
its absence from this preexisting matrix is not an accepted release exclusion.

A grammar-complete claim with external constraints is **not currently
certifiable**. It still needs meaningful coverage/correspondence closure,
predicate dispositions and a concrete decision about the hook mismatch, plus
every mandatory versioned release requirement. None of the permitted statements
supports candidate or stable status. No complete-source or exhaustive-equivalence
claim is permitted.

## 7. Blocker and certification impact

The original definitions and current Phase 7C.6 dispositions are preserved.
The additive [scope assessments](conformance-scope-adjudication/scope.json)
record the original requirements and remaining scope; no gate status is edited.
No whole blocker is resolved. The new narrow adjudication work is complete,
while the exact provenance/context prerequisites were already satisfied in
earlier phases and are not counted as new whole-blocker closures.

All seven blockers remain: PHP86-013 discovery; PHP86-014 writable-target intent;
PHP86-015 traversal intent; CA86-001 discarded hooks; C6-CONTEXT predicate and
enforcement scope; C6-COVERAGE scanner/production/binding evidence; and
C6-PROVENANCE complete release and final-source correspondence. The unchanged
current runner must continue refusing certification while those requirements
remain. No historical C1 accepted limitation is imported as an RC4 waiver.

## 8. PHP 8.5 compatibility and separate governance issue

The historical [PHP 8.5 claim](../8.5/completeness.md) remains grammar-complete
with external contextual constraints and bounded PHP 8.5.10 conformance evidence,
with its recorded C1–C4 limitations and known-discrepancy record. Its grammar,
specification, fixtures and historical evidence are unchanged. The original
release contract is neither renewed nor reinterpreted.

**G7C8-H85-SCOPE** records a separate wording/governance concern: historical
grammar-completeness wording must be read alongside the deliberate discarded-hook
divergence. An unqualified parser-equivalence reading would contradict that
evidence. Existing explicit qualifications support preserving the historical
claim; they do not authorize carrying its accepted limitations into independent
PHP 8.6 certification. Any clarification or grammar correction belongs in a
separately reviewed historical change. No historical document is silently fixed.

## 9. Required corrections or evidence

No normative correction is established by this review. CA86-001 needs an
authoritative intended-validity decision for the exact discarded/live forms or
a separately reviewed, enforceable claim exclusion. An identifier-shaped hook
proposal would change accepted structure and require Gate B, stage controls,
parity, and a separate PHP 8.5 correction decision.

For C6-CONTEXT, use the individual remaining-obligation column. Prioritize
isolated type/member/parameter checks, early modifier actions, deferred
Override/enum/linking paths, writable/reference targets and constant traversal.
Retain live, retained and discarded witnesses and distinguish diagnostics from
acceptance. Do not infer a native validator from normative prose or require a
full compiler implementation merely to document an external rule.

## 10. Validation and evidence freshness

[Executed commands and log hashes](conformance-scope-adjudication/validation.json)
record **6,221 tests / 58,338 assertions**, zero PHPUnit failures. The focused
hook/context/boundary/API/manifest and certification-negative selection passes
**1,357 tests / 10,140 assertions**. The selections overlap and are not summed.
The full suite includes EBNF integrity/reference and Markdown parity checks.
Manifest and Git whitespace checks pass.

[Baseline checks](conformance-scope-adjudication/baseline-checks.json) pass
source-lock, historical integrity, current compatibility and exact-context
verification and retain commands and actual exits. Exact-context verification invokes the
live exact-source build/provenance verifier. Existing authenticated hook and
context observations are reused; no broad hook experiment or upstream PHPT
suite is claimed. Full repository tests may exercise their existing regression
paths without creating a new exact-source probe corpus.

This phase adds only documentation/evidence. The selected Phase 7C.7 current
compatibility revision remains independently checkable because its bounded
dependency inventory excludes unreferenced research prose; it is not renewed
by this review. The broader current certification contract includes these new
documents, so it receives a new `phase7c8` execution revision. Final checks and
artifact receipts are stored under the current runner's existing output root,
outside its computational input inventory. That follows the unchanged contract;
no new freshness exclusion is introduced.

See the [fresh current evidence](certification-contract-remediation/runs/phase7c8/evidence.json),
[independent verification and claim refusal](certification-contract-remediation/phase7c8-validation/checks.json),
and [artifact validation](certification-contract-remediation/phase7c8-validation/artifacts.json)
for actual final results. Retained legacy failures and substantive blocked gates
must not be hidden by PHPUnit success. Those receipts distinguish successful
evidence generation/checking from certification refusal.

## 11. Preservation

The [final preservation receipt](certification-contract-remediation/phase7c8-validation/preservation.json)
compares every baseline path byte-for-byte. PHP 8.5 and PHP 8.6 normative
artifacts, historical evidence, source locks, fixture expectations, policies,
existing API/CLI contracts and release gates remain unchanged. PHP 8.6 remains
`draft`; PHP 8.5 remains stable. All work in this phase is additive adjudication
and evidence, with no matcher, lexer, parser or validator implementation change.

## 12. Next-phase readiness

| Work | Readiness |
|---|---|
| Contextual evidence remediation | Ready for the individual bounded obligations; no automatic release exclusions. |
| PHP86-013 source-risk closure | Ready for targeted remaining hunks/transitive associations; this review discharges none. |
| PHP86-014/015 clarification | Ready for exact-form upstream intent questions; speculative normative changes remain unjustified. |
| Independent prerelease certification | Not ready: all seven mixed blockers and substantive closure obligations remain. |
| Candidate promotion | Not ready; retain draft status. |

Phase 7C.8 stops at adjudication and its validation. No subsequent implementation,
policy redesign, source upgrade, promotion or certification phase is begun.
