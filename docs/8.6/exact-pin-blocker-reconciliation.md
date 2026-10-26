# PHP 8.6 exact-pin blocker reconciliation

October 25, 2026 — Phase 7C.4. **Targeted reconciliation is complete; PHP 8.6
remains draft and uncertified.** Exact-source evidence confirms the known
compiler/VM defect and traversal behavior, but does not settle their general
intended-validity boundaries. No normative artifact, source pin, historical
observation, or release-gate definition changed.

## 1. Authenticated baseline

[Identity](exact-pin-blocker-reconciliation/identity.json) and
[baseline checks](exact-pin-blocker-reconciliation/baseline-checks.json) record:

| Artifact | Identity |
|---|---|
| Repository HEAD | `4625873a6bac908b5cff4c677dba4ac10dd050e0` plus preserved uncommitted prior work |
| Official PHP source | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| `php.exe` SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| `php8.dll` SHA-256 | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |
| Build | Windows x64 NTS, MSVC 2022; `--disable-all --enable-cli --enable-tokenizer --disable-zts --disable-cgi --disable-phpdbg` |

The existing Phase 7C.3 verifier passed against the live source, executable,
DLL, generated files, toolchain, configuration, logs, and separate receipt.
The 235-file certification lock passed; the full 28,445-file source inventory
remains bound to the build. All lock/build-record digests are retained in the
identity record. The executable was neither rebuilt nor replaced. PHP 8.5
remains the recorded distribution comparison, not an asserted exact-source build.

## 2. Original blocker matrix and current assessment

The [current supplemental dispositions](exact-pin-blocker-reconciliation/dispositions.json)
retain every obligation from the Phase 7B remediation plan, including original
claim, category, required/available evidence, remaining uncertainty, certification
impact, and resolution criteria. They supersede only the stated evidence gaps;
the original ledgers remain unchanged.

| Blocker | Category and current claim | Still required |
|---|---|---|
| PHP86-014 | Language-validity uncertainty plus confirmed upstream compiler/VM defect | Authoritative intended rule for exact assignment-derived targets and coherent implementation treatment |
| PHP86-015 | Intended compatibility uncertainty; source-verified traversal differences | General intended boundary, complete dispatch correspondence, contextual enforcement |
| PHP86-013 | Missing compiler/AST/linking discovery evidence | Remaining hunk identities, transitive paths, and upstream-test associations |
| CA86-001 | Inherited specification restriction relative to discarded-declaration acceptance | Independent claim/policy adjudication or separately authorized correction |
| C6-CONTEXT | Missing predicate/stage evidence and enforcement | Individual contextual predicates and authorized bounded scope |
| C6-COVERAGE | Missing production/alternative, scanner and binding evidence | Witnesses/dispositions for each remaining identity and state/binding obligation |
| C6-PROVENANCE | Exact-source build prerequisite satisfied; release evidence incomplete | Runner integration, complete fresh prerelease evidence, later RC/final correspondence |
| H85-FRESHNESS / T7B-RUNNER | Certification infrastructure limitations | Preserve historical integrity/current compatibility separation without silently substituting mandatory gates |

All seven existing release-blocking finding IDs remain blocking. A confirmed
mechanism or a satisfied provenance prerequisite is not a release waiver.

## 3. PHP86-014: writable-target behavior

**Disposition: retained as a bounded certification blocker, with explicitly
separated confirmed-mechanism and unresolved-intent subfindings.**

The [construct ledger](exact-pin-blocker-reconciliation/constructs.json) classifies
all 26 writable controls: three confirmed valid controls, ten parser-accepted
but runtime-defective constructs, seven with unresolved intended validity,
one confirmed invalid structural form, and five contextual restrictions.
The labels do not infer language intent from lint or runtime exit codes.

| Construct family | Exact-pin evidence | Classification |
|---|---|---|
| Ordinary assignment dimension/property writes; nested bases | `ASSIGN` produces `T`; write consumes `T`; execution reaches invalid opcode | Parser-accepted but runtime-defective; intended permission unresolved |
| Nonreference list result, variable RHS | `QM_ASSIGN` produces `T`; `ASSIGN_DIM T...` fails | Same defect mechanism |
| Nonreference list result, literal RHS | `ASSIGN_DIM array(...) ...` consumes CONST and fails | CONST-container mismatch; not a TMP result |
| Compound assignment, increment, unset, reference fetch, `??=` | Operation-specific TMP consumers and recorded invalid-opcode tuples | Same defect family, separately observed |
| Reference assignment and reference-bearing list results | `V` consumers; reviewed alias mutations succeed | Observed success, intended new permission unresolved |
| Assignment reads, ordinary call-result write, constant-object property write | Expected output; source/test controls | Confirmed valid controls; PHP86-004 remains distinct |
| Literal array write, direct `new` property write, constant-array-only and nullsafe writes | Compiler rejects | Contextually restricted |
| Nested parenthesized reference-source assignment control | Structural mismatch and Zend lint rejection | Confirmed invalid structural form |
| Discarded closure / dead statement | Discarded body emits no bad write; dead statement can retain it without execution | Success does not establish intended writable validity |

The important new evidence is a **direct pre-optimizer dump**. For
`assign-write`, it includes `T1 = ASSIGN CV0($a) array(...)` followed by
`ASSIGN_DIM T1 int(0)`. Reference assignment instead supplies `V3`. The
pinned [VM handler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2680)
accepts VAR/CV containers, not TMP/CONST. The same operand restrictions are
recorded for object writes, compound writes, writable/read-write dimension
fetches, and unset. Compiler dispatch explicitly admits assignment ASTs at
[zend_compile_var_inner](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12774).

This corroborates the defect independently of the runtime diagnostic. It does
not decide whether the correct repair rejects the source or supports the write.
`gh20628_003.phpt` and `gh20628_005.phpt` establish adjacent assignment/read
behavior, not a blanket assignment-write permission.

Refreshed [PR 20628](https://github.com/php/php-src/pull/20628) discussions address
the TMP/VAR overhaul and related reference-passing issues. They do not decide
these exact writable targets. [PR 20903](https://github.com/php/php-src/pull/20903)
supports constant-object writes, a different PHP86-004 scope. The search hit
[PR 23546](https://github.com/php/php-src/pull/23546) concerns stale optimizer
operand uses, not the intended validity of the tested unoptimized target forms.
No upstream code was patched or newer revision substituted.

## 4. PHP86-015: optimized-call validation order

**Disposition: mechanism corroborated; intended compatibility remains bounded
and blocking. No EBNF action or additional normative wording change is justified.**

All 31 traversal controls reproduce the previous paired outcomes. Direct,
qualified, imported, namespace-fallback, dynamic, named/unpacked and PFA calls
remain separate. Logical AND/OR, ternary, coalescing, live casts, discarded
closures, constant initializers, and adjacent `sprintf`/`strlen`/`array_map`
controls are included.

The source explains both directions of the difference:

- `zend_compile_func_printf` attempts constant evaluation of a sole format
  argument before checking optimization eligibility. Its fallback through
  `sprintf` can also evaluate the format.
- `zend_eval_const_expr` visits both logical operands but can select only a
  surviving ternary arm or non-null coalescing operand.
- Ordinary compilation has different short-circuit, ternary and coalescing
  traversal. Named/dynamic/namespace dispatch and PFA eligibility remain relevant.

Direct printf ternary/coalescing controls emit an `ECHO` with the surviving
string. The rejected logical controls produce no opcode dump. An empty dump
after rejection is not a separate parser verdict. The observed difference
already exists with CLI OPcache disabled; it is not attributable solely to a
later OPcache optimization pass.

[PR 19658](https://github.com/php/php-src/pull/19658) documents optimization and
constant-collection effects, not a universal intended discarded-cast rule.
The bounded refreshed searches did not identify such an authoritative decision;
absence from search results is not proof that none exists.

The current `php.md` already contains the Phase 5D scope clarification at
lines 1161–1173. Its PHP 8.5 statement/initializer table is explicitly historical,
not a universal call-argument algorithm. Earlier C5C-D01/D02 proposals were
already addressed informatively; this phase does not reopen or reapply them.
Future contextual work must model visited AST and dispatch, never invent
function-specific EBNF rules for an optimization.

## 5. PHP86-013: bounded source-group progress

[Group reconciliation](exact-pin-blocker-reconciliation/discovery-groups.json)
retains exact historical hunk IDs, source locations, grammar/contextual rules,
probe and upstream-test references, dispositions, risks and certification effects.

| Groups | Current progress and limit |
|---|---|
| G01 — writable dispatch | Direct operand dumps added; intended writable validity remains open |
| G02 — optimized calls | Exact-source traversal and emitted/discarded instruction evidence added; general intended boundary open |
| G03 — constant callable evaluation | Four new controls establish bounded mandatory-reference default evaluation; broader named/reference signatures and timing remain open |
| G07 — interface/linking order | Existing four autoload controls plus one guarded reentrant class lookup; cycles, other member graphs and failure recovery remain open |
| G04/G05/G06/G08/G10 | Five existing exact-scope exclusions retained, not reopened or generalized |
| G09 | Existing PHP86-005 contextual association corroborated, not a new syntax change |
| G11 | Existing hook-policy classification corroborated; release adjudication remains separate |

The four new PFA controls correspond to pinned
[constexpr_016.phpt](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/partial_application/constexpr_016.phpt)
and `zend_ast_evaluate_arg_list`. A placeholder preserves later reference
passing; binding a constant to a required-reference parameter throws when the
default is evaluated. Named and variadic controls agree. The caught errors
occur after `declared;` output, distinguishing default evaluation from lint.

The new reentrant control queries an in-progress implementation class during
variance-triggered autoload: both versions report `hidden;linked`. It uses only
fixed inline declarations and a depth guard. Its upstream constructor-prototype
test reference is explicitly **adjacent**, not a claimed exact upstream
reentrant witness. The path was corrected to the actual `inheritance/`
subdirectory; earlier probe/evidence checkpoints remain preserved, and the
verified revision uses the corrected metadata.

No new raw hunk is discharged. The Phase 6 effective **230 pending-direct-review
and 14 examined-unresolved** identities remain; G01/G02/G03/G07 remain bounded
unresolved groups. This is not a restarted exhaustive audit or a claim that all
other transitive, optimizer, PHPT or outside-ledger paths are harmless.

## 6. Inherited discarded-hook discrepancy

Five paired controls reconfirm that live/retained unknown hooks reject, while
discarded ordinary and constant-closure declarations can survive Zend lint.
Both repository grammars reject the unknown name; the get repair is accepted.
Pre-optimizer evidence corroborates discarded declarations without claiming
that their source was structurally invalid to Zend.

The pinned parser accepts identifier-shaped hook names and the compiler checks
get/set only on visited declarations. The EBNF intentionally restricts names
earlier. This is a documented normative restriction relative to folding-sensitive
compiler acceptance and a contextual-enforcement/claim boundary. It is not a
new PHP 8.6 feature or a newly established historical correctness defect under
the existing bounded claim. **CA86-001 remains blocking for independent claim
adjudication.** PHP 8.5 is untouched.

## 7. Provenance and gate reassessment

The existing `certification.php --provenance --php86 <exact executable>` passed.
The exact-source evidence obligation is satisfied within the Phase 7C.3 trusted
local-host/controller boundary. It requires no second independent compilation;
bit-for-bit reproducibility remains unclaimed.

However, the legacy runner still requires `exact-pin-executable` to be blocked
and its historical preparation schema forbids `exactPinExecutableVerified=true`.
The new inspector is not a replacement release orchestrator. Changing historical
reports or the gate definition to manufacture a pass would violate this phase.
The [gate reassessment](exact-pin-blocker-reconciliation/gate-reassessment.json)
therefore separates **passed prerequisite inspection** from **blocked legacy
gate integration**. C6-PROVENANCE now explicitly separates completed exact-source
evidence from remaining prerelease/final-release evidence.

The preserved 36-gate run remains **25 passed / one failed / ten blocked**;
these are historical run counts, not 25 freshly certified gates. The failure
is the mandatory historical-certification freshness gate. The successor retains
38 additive obligations. Fresh historical integrity and current compatibility
are separately verified; they do not replace the original failed gate.
No complete certification run or package promotion was begun.

## 8. Evidence and validation results

The [verified probe report](exact-pin-blocker-reconciliation/probes-verified.json)
contains **86 cases / 172 paired observations / 645 checks**, with **172 lint,
43 controlled execution, and 86 opcode-diagnostic invocations**, zero errors.
**57** successful RC4 inputs produced instruction dumps. Lint combines Zend
parsing, contextual compilation and emission; repository structural matching
is separate. No independent pure Zend-parser verdict is invented.

Opcode diagnostics use the authenticated binary with CLI OPcache enabled only
for non-executing `-l`, optimization level zero, JIT disabled, and pre-optimizer
dumping enabled. Runtime/lint baselines keep CLI OPcache disabled. The two
profiles agree on acceptance. This is not a JIT, optimized-runtime, debug-build,
AST-binding-completeness or scanner-coverage claim.

[Validation receipts](exact-pin-blocker-reconciliation/validation.json) and
[additional checks](exact-pin-blocker-reconciliation/additional-validation.json)
retain actual commands, exits and logs:

| Check | Result |
|---|---|
| Full PHPUnit, including EBNF/reference integrity, parity, API/CLI and historical regressions | **6,185 tests / 58,239 assertions**, zero failures |
| PFA | 225 checks / 114 observations, pass with exact RC4 |
| PHP86-003–007 contextual matrix | 126 observations agree; legacy exact-binary invocation has one distribution-identity assertion failure; required-distribution rerun passes 278 checks |
| Provenance-aware exact comparison, including contextual cases and 36 boundaries | 422 cases / 1,621 checks, zero errors or RC4 distribution differences |
| PHP86-010 diagnostics | 712 checks / 268 observations, pass with exact RC4 |
| Existing distribution integration/boundaries | 526 checks / 172 observations, pass |
| Change traceability | Five preexisting modified productions, 14 documentary sections, no new unexplained changes |
| Source lock, manifest, blocker contract and provenance inspection | Pass |
| Legacy checks and certification claims | Refuse as expected; stale historical aggregate requirements remain visible |

The contextual identity failure is retained, not hidden or converted to a
language failure. That old checker requires the original distribution hash;
the independently provenance-aware runner supplies the exact-source comparison.
Similarly, older diagnostic tools do not themselves attest a build: the parent
receipt binds their explicit executable to the successful provenance check.

Current compatibility is preserved in the separate
[phase7c4-verified revision](../8.5/compatibility/phase7c4-verified/evidence.json).
All **15 commands passed**, with **3,917 dependency identities** unchanged
during generation. Its independent check passed. The
[final validation record](exact-pin-blocker-reconciliation/final-validation.json)
also verifies probe dependencies, source slices and upstream-test hashes, PHP
syntax, JSON, local links, whitespace, and baseline preservation. Hybrid claims
report historical integrity and current compatibility true while still refusing
certification.
The earlier `phase7c4` checkpoint predates the source-citation correction and
does not supply the final current claim. No older revision was overwritten.

## 9. Authoritative evidence and search limits

[Source review](exact-pin-blocker-reconciliation/source-review.json) binds 20
reviewed slices and 11 upstream tests to the authenticated tree. Tests were
read for correspondence; arbitrary PHPT execution was not performed.
[Dated upstream snapshots](exact-pin-blocker-reconciliation/upstream.json)
retain official PR metadata/comments and bounded issue-search responses, URLs,
timestamps, hashes and result counts. PR 20628 has 17 issue and 15 review
comments; PR 19658 has eight and 15. First-page limits are explicit; one broad
query has 31 results with only 30 retrieved. No exhaustive search or absence
proof is claimed. No maintainer was contacted.

## 10. Proposed Gate B actions and remaining obligations

No newly justified normative correction is established. PHP86-014 still needs
authoritative intent/coherent treatment before any target restriction changes.
PHP86-015 needs upstream clarification and further contextual evidence; its
existing informative wording is accurate. A hook-equivalence correction is only
a conditional policy proposal: identifier hook names plus visited-declaration
get/set validation, live/discarded controls, parity, and separate historical
version governance. No such edit is authorized or implemented here.

The existing 64 unexercised productions and 318 alternatives, scanner-state and
helper products, AST binding, contextual predicate scope and final-release
correspondence remain obligations. Five new research inputs do not become
ordinary positive coverage witnesses. Neither passing tests nor opcode dumps
waive any of these requirements.

## 11. Historical preservation

[Preservation](exact-pin-blocker-reconciliation/preservation.json) verifies all
**2,576 baseline files unchanged**, including both versions' normative artifacts,
PHP 8.5 fixtures/evidence, source locks, prior phase records, package metadata,
changes.md and both legacy/successor gate inventories. This phase adds PHP-only
research tooling, focused tests/fixtures and supplemental evidence. Historical
statements about then-missing provenance remain historical; the dated current
assessment supplies the stronger evidence without rewriting them.

## 12. Next-phase readiness

| Follow-up | Readiness |
|---|---|
| Gate B writable/traversal normative remediation | Not ready for speculative edits; intended-validity authority remains missing |
| Hook-policy correction | Concrete conditional proposal available; requires separate claim/policy decision |
| Remaining compiler/contextual evidence closure | Ready for bounded investigation using exact-source execution and opcode diagnostics; not complete |
| Independent prerelease certification | Not ready: discovery, language intent, contextual/coverage obligations and runner/freshness integration remain |
| Candidate promotion | Not ready; PHP 8.6 remains draft and PHP 8.5 remains the stable/default recommendation |

Reproduce the focused matrix with a new output filename:

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/exact-pin-reconcile.php --additional-cases tests/fixtures/php/8.6/exact-pin-reconciliation-cases.json --report docs/8.6/exact-pin-blocker-reconciliation/new-probes.json
```

The runner verifies provenance before execution and refuses to overwrite a
report. It retains exact input, expected/observed stages, commands, exit codes,
stdout/stderr and binary identity. No subsequent remediation, certification or
promotion phase was started.
