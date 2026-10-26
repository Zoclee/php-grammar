# PHP 8.6 local source and contextual evidence remediation

October 26, 2026 — Phase 7E. **PHP 8.6 remains draft and uncertified.**
Seven source-hunk obligations have narrow equivalence proofs; nine additional
contextual predicates have exact-pin stage evidence. The full D-L01, D-L02 and
D-L03 completion criteria remain partially satisfied. No whole blocker is closed.
In particular, remaining local correspondence work is not mislabeled as an
upstream dependency or postponed until final release.

## A. Work items and baseline

The [execution baseline](local-source-context-remediation/baseline.json) retains
commit `4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting working-tree
state, original file hashes, all eleven Phase 7D work items and the prior
assessment. The [successor work ledger](local-source-context-remediation/work-items.json)
preserves every original scope, action, completion criterion, blocker definition
and blocked-gate requirement. Its `phase7e` fields record execution separately.

| Item | Executed work | Remaining requirement |
|---|---|---|
| D-L01 | Traced the four prioritized mixed hunks, G03 reference evaluation and G07 reentrant linking; discharged setter return synthesis | PFA dispatch/signatures, enum metadata/identity and reentrant member graphs remain partially reviewed |
| D-L02 | Preserved all 578 identities; authenticated both sides of all 222 pending/unresolved diffs; partitioned changed-line triage into 23 mechanism groups; discharged seven narrow hunks | 198 pending and 17 examined-unresolved identities, bounded-group and outside-ledger obligations remain blocking |
| D-L03 | Preserved all 73 descriptions; added nine predicate matrices across five descriptions; retained 109 prior observation pointers | Other predicate combinations, source integration and independently accepted conformance scope remain open |

The other eight Phase 7D items are not started here. The table describes partial
execution, not a redefinition of their completion criteria.

[Baseline checks](local-source-context-remediation/baseline-checks.json) freshly
verify the source lock, exact-source build provenance, historical integrity and
prior current compatibility. Global certification freshness at entry differs
from compatibility freshness: Phase 7D documentation had enlarged the global
dependency set, while the narrower compatibility contract still passed.

The immutable source pins remain PHP 8.5
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and PHP 8.6 RC4
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. Execution uses the authenticated
binary SHA-256 `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`
and core DLL `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f`.
Build custody is the existing trusted local controller/host contract, not a claim
of independent bitwise reproduction.

## B. PHP86-013 source-risk execution

The [versioned source review](local-source-context-remediation/source-review.json)
retains all original rows, full diff identities, both pinned source coordinates,
previous dispositions and explicit new proofs. The original Phase 7C.6 ledger
is unchanged. [Source slices](local-source-context-remediation/source-slices.json)
retain full reviewed text, whole-file hashes, range hashes, commits and pinned
PHPT contents. The new checker compares these with the authenticated build's
tracked-file inventory and the historical source hashes, then compares each
queued diff side with its actual source range.

| Original hunk | Narrow proof |
|---|---|
| `Zend/zend_compile.c:814:851` | Two FIXME comment lines removed; no executable tokens changed |
| `Zend/zend_compile.c:1262:1295` | Read-only const qualifiers and redundant void-pointer cast; unchanged redeclaration predicates and fatal arguments |
| `Zend/zend_compile.c:1344:1377` | Local `ce` moved into its sole using branch; unchanged lookup, condition, diagnostic and return |
| `Zend/zend_compile.c:2649:2704` | Unreachable return removed after a function declared non-returning at both pins |
| `Zend/zend_compile.c:2933:3009` | TODO comment removed; no executable tokens changed |
| `Zend/zend_compile.c:8767:9470` | Both synthesized setter AST forms compile to the same built-in `void` type |
| `Zend/zend_compile.c:11577:12362` | Read-only context qualifier and redundant C cast; unchanged AST traversal and predicates |

For setter synthesis, the old unqualified string `void` is found in
`builtin_types` and produces `ZEND_TYPE_INIT_CODE(IS_VOID, 0, 0)` before the
class-name/namespace-resolution branch. The new `ZEND_AST_TYPE` with `IS_VOID`
produces the same type. Neither synthesized form supplies nullable or compound
flags. Function compilation passes child 3 through `zend_compile_params`, and
the return checker applies the same void restriction. A namespaced class with
an imported property type reports `void` for both implicit and explicit setters.
That observation supports the source proof; it does not prove all hook variance.

Pending direct review decreases **204 → 198**; examined-unresolved decreases
**18 → 17**. Their combined count decreases **222 → 215**. The 28 preexisting
bounded-group references retain their limitations. These are obligation counts,
not language-change counts or a completeness percentage.

The [queue assessment](local-source-context-remediation/queue-assessment.json)
keeps every queued identity in one mechanism group and identifies the remaining
local correspondence question. Changed-line inspection is explicitly weaker
than a full transitive proof. No other queued hunk receives a new exclusion.

Four important boundaries remain:

- **PFA/reference dispatch:** ordinary argument compilation selects read versus
  function-argument mode using globals and reference metadata; constant AST
  evaluation rejects a bound non-placeholder for a required-reference parameter.
  Existing four default-evaluation controls and `constexpr_016.phpt` corroborate
  that distinction. They do not prove every dynamic signature, named binding,
  optional-reference path or optimized partial call.
- **Enums:** compilation assigns a case ID and a four-child constant-enum AST;
  evaluation defers during compilation and later allocates the enum object.
  Initializer-presence predicates are now isolated. Internal arginfo conversion,
  case/ordinary-constant table identity and deferred type/duplicate handling remain
  separate obligations. No allocation-layout change is declared harmless by name.
- **Reentrant linking:** parent interfaces, constants, methods and properties
  precede `do_implement_interface`. Existing guarded lookup, covariance and
  Override controls remain finite observations. Cyclic callbacks, partially
  installed members and failure recovery remain open. The source comment's
  constructor-prototype test is retained from its actual `tests/inheritance/`
  location; a comment path alone is not test authentication.
- **Optimizer/compiler boundary:** `array_map` specialization rejects strict
  types, wrong arity, non-FCC callbacks and nonliteral bound operands, then
  resolves names and synthesizes a loop through `zend_partial_apply`. Existing
  strict/non-strict controls are reused. Rollback, name/reference dispatch and
  specialized/fallback traversal remain unproved. All previously recorded
  outside-ledger optimizer, VM, linking, test and component obligations survive.

## C. C6-CONTEXT predicate and stage evidence

The [predicate manifest](local-source-context-remediation/predicates.json) maps
each selected predicate to immutable source coordinates, diagnostic mechanism,
grammar productions, normative clause, supporting PHPT where established,
fixtures, expected stages and enforcement limitations. The
[context successor](local-source-context-remediation/context-inventory.json)
preserves all 73 descriptions and predecessor observations verbatim.

| Description | Additional individually isolated predicates |
|---|---|
| C6-C018 | Only the final parameter may be variadic |
| C6-C029 | Setter arity, by-reference parameter, variadic parameter, default value and property/parameter typedness |
| C6-C021 | Setter synthesized void return forbids a returned value |
| C6-C035 | Backed enum case requires an initializer |
| C6-C036 | Unbacked enum case forbids an initializer |

The final [observation receipt](local-source-context-remediation/observations.json)
has **55 passing observations**: 54 lint checks and one controlled reflection
execution. Each predicate has a repair, live invalid declaration, retained and
discarded constant-initializer closure, ordinary false-branch closure and dead
statement. Repairs and discarded initializer branches pass; the other four
invalid forms reach the compiler diagnostic. Reflection reports
`implicit:void` and `explicit:void`.

Lint includes parsing and compilation; its error text is not an isolated parser
verdict. The source predicate establishes contextual-compilation attribution.
Constant folding controls whether the enclosing declaration is visited. The
reflection result is controlled runtime evidence. None is labeled native grammar
contextual enforcement, and no opcode-equivalence claim is made.

The first harness used ordinary expression ternaries for its purported discarded
cases. All nine still reached their contextual diagnostics. The
[initial checkpoint](local-source-context-remediation/initial-checkpoint/predicates.json)
preserves those fixtures and the [failed receipt](local-source-context-remediation/initial-observations.json).
The final manifest distinguishes ordinary expressions from constant initializers.
An intermediate successful receipt is retained separately; overlapping runs are
not summed as independent predicate coverage. Final execution followed checker
and source-proof finalization.

[Retained evidence pointers](local-source-context-remediation/retained-evidence.json)
identify 109 observations individually: 63 exact-profile RC4 contextual rows,
11 source-risk controls and 35 earlier predicate/stage controls. They support
readonly defaults, constant-object mutation, Override, enum restrictions,
nullsafe references, PFA/reference defaults, discarded declarations and traversal.
Readonly defaults follow PHP86-003's relaxation. Override declaration linking is
not attribute syntax; constant-object permissions do not settle PHP86-014.
Earlier C019/C028/C034/C069 predicate evidence is reused rather than rerun.

There is no isolated upstream PHPT association for the new setter-arity witness.
The typedness PHPT is a positive variance example; the setter PHPT is a positive
hook example. They are not misrepresented as exact negative tests. Local
predicate/diagnostic controls provide the negative evidence, with those upstream
association limits explicit.

The bounded claim remains external-compiler-assisted contextual evidence.
Neither documenting that boundary nor passing these controls constitutes an
independently accepted release limitation. C6-CONTEXT and CA86-001 remain blocking.

## D. Language correctness

No new normative correction is established. The setter AST substitution has a
narrow equivalence proof; the observed traversal difference matches the existing
ordinary-versus-initializer distinction. PHP86-014 writable-target intent,
PHP86-015 optimized traversal intent and CA86-001 discarded-hook claim
adjudication remain unresolved. No Gate B grammar change was made or inferred.

## E. Current certification impact

The [new compatibility revision](../8.5/compatibility/phase7e/evidence.json),
[v2 assessment](certification-contract-remediation/runs/phase7e-base/evidence.json)
and [v3 assessment](certification-contract-remediation/runs/phase7e/evidence.json)
use the existing authorized generators and independent checkers. Their exact
commands, exit codes and fresh gate totals are retained in the
[execution summary](certification-contract-remediation/phase7e-validation/summary.json)
and [certification checks](certification-contract-remediation/phase7e-validation/checks.json).
These links identify this phase's results; historical totals are not relabeled.

The existing 339-case differential report is independently checked and reused;
research controls are outside the ordinary corpus. The new checker is additive
supporting evidence and changes no gate decision. Nine original blocked gates
and seven underlying blockers retain their requirements, including discovery,
context scope, writable targets, folding order, discarded hooks and coverage.
C6-PROVENANCE still requires complete successful release evidence and eventual
final-source correspondence despite verified RC4 build identity. Claims must
continue to refuse while those requirements remain unmet.

## F. Validation

The [validation receipts](certification-contract-remediation/phase7e-validation/validation.json)
record the successor checker, predecessor source/context checks, retained exact
context and differential verification, fresh exact-source PFA regressions, source
lock, historical integrity, traceability, manifest, full PHPUnit and whitespace.
The [summary](certification-contract-remediation/phase7e-validation/summary.json)
reports actual test/assertion totals and failures, alongside compatibility's
version-boundary, structural/parity, lexer and historical regression checks.
Overlapping suites are not added together.

The standalone full suite passes **6,271 tests / 58,586 assertions**, zero
failures. Exact-source PFA validation passes **225 checks / 114 paired-version
observations**. All twelve commands in that validation receipt pass; the final
successor check additionally verifies the finalized dependency inventory.

The additive [checker](../../tools/8.6/local-source-context.php) verifies all queued
raw diff hashes/ranges, exact source slices, predecessor membership, permitted
status transitions, contextual correspondence, fixture hashes, runtime custody,
commands, diagnostics and evidence inputs. Its negative tests reject missing
identities, unsupported closure, changed completion criteria, unrelated failure
diagnostics, substituted commands and altered runtime identity. This integrity
checker cannot turn semantic review into a mathematical proof of all PHP behavior.
No full upstream PHPT-suite execution is claimed.

## G. Preservation

The [preservation receipt](certification-contract-remediation/phase7e-validation/preservation.json)
verifies all **4,029 baseline files byte-identical**. PHP 8.5 grammar, specification,
historical fixtures and evidence; PHP 8.6 EBNF and normative Markdown; pins,
manifest status, existing APIs/CLIs and all existing gate implementations remain
unchanged. Additions comprise research fixtures, evidence successors, a separate
checker and negative tests. Existing historical receipts are never overwritten.
The [artifact audit](certification-contract-remediation/phase7e-validation/artifacts.json)
records JSON, applicable PHP syntax, links, whitespace and retained-file digests.

## H. Next-workstream readiness

The scanner, grammar-alternative and AST/binding workstream may use these stable
source and predicate references, but none of its obligations is discharged here.
It was not begun. Independent certification and candidate promotion remain
unready. The three local work items still contain explicit local evidence gaps;
upstream-dependent intent questions and final-release correspondence are retained
separately. PHP 8.6 remains `draft`.
