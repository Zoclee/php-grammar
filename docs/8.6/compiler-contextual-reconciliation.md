# PHP 8.6 Phase 5C compiler and contextual reconciliation

October 24, 2026. **Targeted investigation complete; PHP86-013/014/015 remain
open. PHP 8.6 remains draft.** No EBNF, normative specification, lexer, parser,
manifest, public API or release-gate change is made. No correction, promotion,
certification or next phase is started.

## 1. Baseline and scope

Repository commit: `11496fba8ae706b05bc500d99b8c0326a41054fc`. The existing
uncommitted Phase 5A/5B work is part of the protected starting state.
The [baseline](compiler-contextual-baseline.json) records hashes of **1,939
pre-existing project files** and the preceding verified results: 280/361
productions (77.56%), 425/800 alternatives (53.13%), 30 boundaries, and 5,935
tests / 55,643 assertions with zero failures. Coverage definitions and ordinary
fixture inventories are unchanged; the new research subdirectory does not
contribute positive coverage.

The immutable sources remain:

| Version | Branch/profile | Commit |
|---|---|---|
| PHP 8.5 | PHP-8.5 comparison pin | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| PHP 8.6 | PHP-8.6 RC4 | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |

The [source lock](../../tools/8.6/reconciliation-source-lock.json) supplements
the unchanged primary lock. The runner verifies the original 168 paired source
records and five archive/executable hashes, primary focused hashes, additional
source/test files, cached upstream responses and 24 RC4 source slices. Hashes
identify reviewed content; they do not prove binary/source equivalence.
All **501 file-hash/absence checks** pass. Source-slice checks are additional.

Paired testing uses the existing PHP **8.5.10** and **8.6.0RC4** Windows CLI
distributions. Exact version/build output, executable hashes, NTS/debug flags
and effective settings are preserved in the
[probe report](compiler-contextual-probes.json). Neither executable is proven
to be an exact-pin build. Tests use `-n`, byte-oriented source, enabled short
tags, all diagnostics, a 64 MB memory limit and a five-second execution limit.
Effective CLI opcache is verified disabled. No JIT/opcache matrix or exact-pin
build is claimed.

This phase answers unresolved boundary questions from
[Phase 3D](discovery-final-review.md), [Phase 5A](completeness-assessment.md) and
[Phase 5B](coverage-expansion.md). Existing controls are retained to isolate new
operations, dispatch conditions and declaration contexts, not counted as newly
discovered features. It does not reopen the 5,989-PHPT inventory, unrelated API
behavior, complete optimizer discovery or release certification.

## 2. PHP86-014: writable-target disposition

**Overall: UNRESOLVED — BOUNDED.** The compiler/VM operand mismatch is an
**UPSTREAM IMPLEMENTATION DEFECT** supported by the pinned implementation.
The intended accepted language and correct repair remain unresolved. This
distinction preserves the finding rather than closing it on successful lint.

The parser admits dereferenceable expressions and assignment ASTs. Both
repository grammars structurally accept the ordinary/list/reference dimension
and property forms below. RC4's `zend_compile_var_inner` dispatches ASSIGN and
ASSIGN_REF in write contexts; ordinary assignment emits a TMP result.
Reference assignment retains VAR outside read/isset contexts. List assignment
propagates its evaluated RHS result: the nonreference variable RHS is copied to
TMP, a literal can remain CONST, and a reference-bearing list can retain VAR.

`zend_delayed_compile_dim` and `zend_delayed_compile_prop` pass that result into
write operands. The pinned VM declares VAR/CV containers for `ZEND_ASSIGN_DIM`,
`ZEND_ASSIGN_DIM_OP`, `ZEND_FETCH_DIM_W`, `ZEND_FETCH_DIM_RW` and `ZEND_UNSET_DIM`;
`ZEND_ASSIGN_OBJ` also allows implicit-this/unused, but no TMP container.
Operand type 1 is CONST, 2 TMP, 4 VAR and 8 CV. This explains the tested invalid
opcode tuples without treating a runtime handler failure as a syntax error.

Primary anchors: [assignment emission](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L3543),
[reference result](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L3643),
[variable dispatch](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12774),
[dimension VM handler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2680),
and [property VM handler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2489).

| Reviewed form | PHP 8.5 lint | RC4 lint / execution | Disposition |
|---|---|---|---|
| `($a=[1])[0]` read | Pass | Pass / expected value | Confirmed read control; GH20628 regression covers assignment reads. |
| `($a=[1])[0]=2` and nested ordinary assignment base | Reject | Pass / `23/2/1` | Compiler/VM defect; intended write validity unresolved. |
| `([$a]=$b)[0]=2`, variable RHS | Reject | Pass / `23/2/1` | Same TMP mismatch. |
| `([$a]=[1])[0]=2`, literal RHS | Reject | Pass / `23/1/1` | CONST container mismatch; not every list result is TMP. |
| `($a=new stdClass)->p=2` | Reject | Pass / `24/2/1` | Extends the affected area to assignment-derived property writes. |
| Ordinary assignment base with `+=`, `++`, `unset`, reference binding | Reject | Pass / `27/2/1`, `87/2/1`, `75/2/1`, `84/2/1` respectively | Operation-specific VM evidence; no blanket structural rejection. |
| Ordinary assignment base with `??=` | Reject | Pass / `23/2/1` | Memoized write path still exposes the mismatch. |
| Reference assignment base: dimension/property write, unset, `??=` | Reject | Pass / successful alias mutations | Observed success, not proof of intended new validity. |
| Reference-bearing list assignment base | Reject | Pass / shared element updated | VAR result explains difference from nonreference lists. |
| Array literal write and direct `new` temporary property write | Reject | Reject | Confirmed contextual negatives in both profiles. |
| Ordinary call-result dimension write | Pass | Pass / success | Existing valid control; does not authorize arbitrary temporaries. |
| Constant-derived object property write | Reject | Pass / expected mutation | Independently intended PHP86-004 rule, not PHP86-014 closure. |
| Constant-array-only and nullsafe write paths | Reject | Reject | Existing guards retained. |
| Write inside discarded closure | Pass | Pass / success | Enclosing AST is not compiled. |
| Same write inside `if(false)` | Reject | Pass / success | RC4 emits but does not execute the bad path; dead-statement success is not validity proof. |

The nested reference-RHS candidate `($a=&($b=&$c))[0]=2` is rejected by both
parsers and both repository grammars: a parenthesized assignment alone does not
satisfy the required reference-source variable form. It remains an explicit
parse-boundary control, not a VM defect or a repaired positive example.

The cached [GH20628 PR](https://github.com/php/php-src/pull/20628), merge
`6173a9a10989fa887240590a34c2b0eec50f003e`, and discussion describe TMP/VAR
overhaul and dereferencing. `gh20628_003.phpt` covers nested assignment operand
separation; `gh20628_005.phpt` covers assignment-result reads. Neither establishes
intent for these writes. Discussion of passing `new` to a reference parameter
concerns a different runtime restriction. The dated cached responses were
reviewed and hash-verified; no fresh exhaustive upstream search is claimed.

Affected productions remain `assignment-expression`, `variable-expression`,
`array-object-dereferenceable` and their property/dimension/unset contexts.
The current EBNF already represents the tested structures. The specification's
PHP86-004 carve-out at lines 896–909 correctly keeps assignment-derived targets
unresolved, although future research navigation should name property and
read-modify-write operations as well as dimensions.

**No required EBNF change is established.** Do not add an unconditional
assignment-target prohibition or relaxation. Resolution needs an authoritative
intended-validity decision, a coherent compiler/VM repair, exact-pin execution
and operation/result-kind positive, negative, live/discarded and boundary tests.
Until then this area blocks unrestricted writable-target correctness and
candidate/stable certification.

## 3. PHP86-015: optimized-call validation order

**Overall: UNRESOLVED — BOUNDED.** The traversal mechanism is established;
the intended compatibility boundary is not. No independently confirmed upstream
defect disposition is inferred merely from dispatch-dependent acceptance.

RC4 [zend_compile_func_printf](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5264)
evaluates a sole argument before checking whether it is a constant string.
Other arities can reach the existing `zend_compile_func_sprintf`, which evaluates
the first argument before checking eligibility. Thus an attempted optimization
can affect acceptance even when it ultimately falls back.

`zend_eval_const_expr` visits both AND/OR children, but selects only the known
surviving ternary arm or non-null coalesce operand. Ordinary compilation skips
known short-circuit RHS nodes while compiling both ternary arms and the
coalesce default. Unset casts reject whenever either traversal visits them.

| Lint-only family | PHP 8.5 | RC4 | Source explanation |
|---|---|---|---|
| Direct `printf(false && (unset)$x)` / `printf(true || (unset)$x)` | Pass | Reject | New constant-evaluation attempt visits the RHS. |
| Direct printf with discarded ternary arm / non-null coalesce default | Reject | Pass | Constant evaluation discards the cast before ordinary compilation. |
| Live cast or null-left coalesce | Reject | Reject | The cast is visited. |
| Dynamic printf | Ordinary traversal | Ordinary traversal | No static builtin specialization. |
| Fully qualified/imported printf | Ordinary traversal | Specialized traversal | Known global builtin is selected. |
| Namespace-fallback printf | Ordinary traversal | Ordinary traversal | Namespace dispatch is not assumed to resolve to the global builtin. |
| Named `format:` argument | Ordinary traversal | Ordinary traversal | Special-function gate rejects named arguments before printf evaluation. |
| `printf("%s", true || (unset)$x)` | Pass | Pass | Only the format takes constant evaluation; the second argument uses ordinary compilation. |
| `sprintf` format | Constant traversal | Constant traversal | Existing behavior; not all builtins share it. |
| `strlen` argument | Ordinary traversal | Ordinary traversal | Its specialization calls ordinary expression compilation. |
| Dead statement / discarded enclosing closure | Body visited / body skipped | Body visited / body skipped | Reachability and compile-time discarding are different. |
| Constant static closure body containing printf | Pass for OR control | Reject for OR control | Constant closure body still compiles as a function body. |
| Discarded enclosing constant closure | Pass | Pass | Body never compiled. |

Two unpack controls prevent a misleading conclusion: `printf(...[true ||
(unset)$x])` rejects in both versions because array construction itself invokes
constant evaluation. `printf(...$args, format: true || (unset)$x)` passes both.
The named/unpack specialization gate is not evidence that all nested argument
expressions use one traversal.

RC4 `printf(true || (unset)$x, ?)` passes as an ordinary PFA, whereas the same
bound argument in a constant PFA rejects. A known outer constant conditional
can discard that PFA before its arguments are visited. Ordinary PFA with an
untaken ternary cast rejects. PHP 8.5 rejects the placeholder syntax separately.
`array_map` controls retain the ordinary PFA result with and without strict
types; nonliteral bound arguments fail its optimization eligibility test.
The closure-callback control is also ineligible for its FCC-only path. These
controls do not claim that a specialized array_map path executed in every case.

The cached [printf PR 19658](https://github.com/php/php-src/pull/19658), commit
`26c96d38f440a5b6476a97527e1748f9bf187541`, explains optimization, not intended
discarded-cast validity. The relevant array_map PHPT was read as source, including
its opcache configuration; it was not run as an upstream suite or substituted
for an exact printf-cast regression.

**No EBNF modification is necessary for the observed difference.** The current
visited-AST/constant-folding clauses are accurate within their stated contexts.
The historical ordinary-expression-statement table at `php.md:1127` is explicitly
PHP 8.5.10 evidence, not a universal call-argument algorithm. Proposed
documentation clarification C5C-D01 should make this scope and builtin/array
entry into constant evaluation explicit. Any broader normative validation rule
still needs Gate B evidence and a separate implementation decision.

## 4. Inherited discarded hooks

**RESOLVED — DOCUMENTATION IMPACT** for classification and claim scope; no
correction is applied. `hook-name = "get" | "set"` deliberately restricts the
language before folding. Zend's parser accepts `T_STRING`; its compiler checks
the hook name only when visiting the property declaration.

PHP 8.5 and RC4 both reject live/retained unknown hooks and accept the discarded
ordinary and constant-closure witnesses. Both grammars reject the unknown hook
forms even when discarded; both accept the get repair. The exact inherited
policy is explicit in `grammar/8.5/php.md:880` and retained in
`grammar/8.6/php.md:1073`. The upstream `property_hooks/unknown_hook.phpt` supplies
the live rejection, not evidence for an unconditional parser predicate.

This is an intentional specification restriction relative to folding-sensitive
Zend acceptance, not an unreported RC4 grammar regression or demonstrated new
upstream defect. A future unrestricted compiler-acceptance claim would require
a separate correction proposal: identifier-shaped hook syntax, visited-declaration
get/set validation, lexical/parser controls, live/retained/discarded fixtures and
an honest contextual-enforcement statement. Historical PHP 8.5 correction must
be separately governed; it is not silently bundled into an 8.6 upgrade.

## 5. PHP86-013 source-group reconciliation

**UNRESOLVED — BLOCKING** for discovery completeness and candidate/stable claims.
The [new group ledger](compiler-contextual-groups.json) preserves full original
hunk records, source positions, hashes and earlier dispositions. The historical
compiler ledger itself is unchanged. Source slices, locked PHPTs and paired
case associations accompany the review; one group is not one language feature.

| Group | Exact review scope | Disposition |
|---|---|---|
| C5C-G01 | Assignment/list/reference result dispatch and write consumers | UNRESOLVED — BOUNDED; PHP86-014 defect mechanism confirmed, intent open. |
| C5C-G02 | printf/sprintf specialization and ordinary-call gates | UNRESOLVED — BOUNDED; PHP86-015 scope narrowed. |
| C5C-G03 | Constant callable argument traversal and PFA AST evaluation | UNRESOLVED — BOUNDED; finite compile controls pass, evaluation/reference interactions remain. |
| C5C-G04 | Constant-name/coalesce switch defaults and fatal tails | RESOLVED — NO GRAMMAR IMPACT; old macro expansion, reachable cases and predicates preserved. |
| C5C-G05 | Attribute validator cleanup following a noreturn error | RESOLVED — NO GRAMMAR IMPACT; removed statement is unreachable; delayed validation remains. |
| C5C-G06 | Closure capture diagnostics and hash iteration | RESOLVED — NO GRAMMAR IMPACT; duplicate predicate and binding loop unchanged. |
| C5C-G07 | Interface installation order, now with autoloaded-parent controls | UNRESOLVED — BOUNDED; ordinary autoload/covariance/Override evidence added, partially linked reentrancy still open. |
| C5C-G08 | Enum case-ID AST representation and backing-value child position | RESOLVED — NO GRAMMAR IMPACT; constructors and consumers agree; no source terminal changes. |
| C5C-G09 | Deferred enum Override flag and constant validation | RESOLVED — CONTEXTUAL VALIDATION IMPACT; mapped to already implemented PHP86-005, no new edit. |
| C5C-G10 | Cache-slot eligibility after static closure body compilation | RESOLVED — NO GRAMMAR IMPACT for this block; no automatic static inference or skipped body checks. |
| C5C-G11 | Unchanged hook-name restriction and historical evidence | RESOLVED — DOCUMENTATION IMPACT; retain explicit known divergence. |

The eleven groups cover **28 distinct historical raw hunks**. Fifteen receive
a resolved disposition for their reviewed scope; thirteen remain in bounded
groups. Of the historical Phase 2.2 **276 pending-direct-review** records,
**13 are discharged and 263 are not discharged by this phase**. This is a
comparison against that fixed historical queue, not a claim that Phase 3D
annotations did not exist or that 263 is the entire current discovery universe.
The ledger enumerates those IDs and all 550 unselected raw hunks without changing
their earlier dispositions. It also records five evidence-backed exclusion
groups, one implemented-context association and one policy classification.

The enum layout review checks the new case-ID slot across compiler construction,
AST kind child count, constant traversal, runtime evaluation and enum construction.
Interleaved ordinary constants do not change the observed case order. The
backing-type mismatch remains a deferred execution-time TypeError in both
versions; lint is insufficient. The nearby Override change is separately
classified as PHP86-005 rather than hidden inside a mechanical exclusion.

The four reviewed autoload programs use fixed inline declarations without
filesystem/network access. They cover a covariant implementation, incompatible
return, inherited-interface constant Override and missing Override target.
They do not recursively demand a partially linked class, cover cycles or certify
failure recovery. No blanket inheritance exclusion follows from them.

Remaining syntax-relevant priorities are actual partially linked/reentrant
graphs, constant/PFA evaluation and reference binding, writable-result intent,
optimized traversal interactions and unexplained changed compiler predicates
outside the selected hunks. Existing attributes/enum/property constraints retain
their implementation mappings. Unrelated runtime/API tests are not prerequisites
for this targeted phase, and no unreviewed source group becomes harmless by default.

## 6. Stage-aware evidence and specification assessment

The [case manifest](../../tests/fixtures/php/8.6/reconciliation-cases.json) contains
**81 sources / 162 version observations**. Each retains exact bytes/hash, family,
group/production mapping, structural and lint expectations, reviewed execution
permission, expected result/diagnostic and stage. The runner performs **162 lint
invocations and 37 controlled executions**. Expected failures are passing
regression evidence, not evidence that every source is valid.

Lexical byte preservation and repository structural matching are independently
observed. Zend `php -l` combines parsing, compiler validation and opcode emission;
it does not expose three independent stage verdicts. No AST/opcode dump was
captured. The emitted operand-kind explanation is pinned source analysis,
corroborated by execution diagnostics, not a separately measured opcode-generation
stage. Autoload linking is observed during reviewed execution. Runtime invalid
opcodes and enum value evaluation are never relabeled structural negatives.

| Proposal / area | Correctness assessment and required action |
|---|---|
| C5C-D01 — traversal scope | Documentation clarification: identify optimized-call/array entry into constant evaluation; preserve visited-AST rules and historical statement-only table. No universal new prohibition. |
| C5C-D02 — writable-target research scope | Documentation clarification: PHP86-014 includes property, read-modify-write, unset and reference-fetch consumers. Existing PHP86-004 carve-out remains correct. |
| C5C-D03 — hook equivalence | Historical policy divergence; structural/contextual correction only if a separately authorized task changes the acceptance contract. Not a new PHP 8.6 feature. |
| Writable-target grammar | No structural correction justified. Intention is unresolved; successful reference results are not an implemented language relaxation here. |
| Contextual validator | Stage-aware evidence added; no native whole-source compiler/linking validator implemented. Actual visited AST and runtime/declaration boundaries remain required. |
| Enum/capture/attribute/cache reviewed hunks | No additional normative omission or structural defect established beyond existing implemented findings. Exclusions are exact-scope only. |

No new material finding ID is needed: newly observed write-operation failures
share PHP86-014's mechanism; dispatch variations refine PHP86-015; hook policy
retains CA86-001. Stable C5C group/proposal IDs organize evidence rather than
inflate the language-change inventory.

## 7. Validation and preservation

The [research runner](../../tools/8.6/compiler-contextual-check.php) fails on
missing inputs or changed source/fixture bytes. The
[focused tests](../../tests/Php/Conformance/Php86CompilerContextualTest.php) retain
structural, lexical, deferred-linking, expected VM-failure and hook-discrepancy
boundaries. Existing validators write only new Phase 5C reports.

| Check | Actual result |
|---|---|
| New paired research runner | PASS: 1,708 checks; 501 file-hash/absence checks; 162 observations; 162 lint / 37 execution invocations; zero errors |
| Focused PHPUnit | PASS: 84 tests, 1,204 assertions, zero failures |
| Full repository PHPUnit | PASS: 6,019 tests, 56,847 assertions, zero failures |
| Existing PHP86-001 PFA | PASS: 225 checks, 114 observations, zero failures |
| Existing PHP86-003–007 contextual checks | PASS: 278 checks, 126 observations, zero failures |
| Existing PHP86-010 deprecation checks | PASS: 712 checks, 268 observations, zero failures |
| Integration and all 30 boundaries | PASS: 498 checks, 160 observations, zero failures |
| Production/alternative coverage CLI | PASS: unchanged 280/361 and 425/800 |
| Production traceability | PASS: five modified productions, zero additions/removals; 13 documentary sections |
| Manifest | PASS: schema 1.0 and package paths; 8.6 remains draft |
| Preservation and report integrity | PASS: all 1,939 baseline files unchanged, including 1,437 historical 8.5 files; local links and research JSON checked |

The full suite includes EBNF syntax/reference integrity, Markdown parity,
lexer/conformance and version-boundary tests, historical PHP 8.5 tests and public
API/CLI regressions. Selections overlap; counts are not additive. These passing
checks do not resolve the explicitly recorded language-intent and provenance
limitations. Git diff whitespace checks also pass.

Reproduce from the repository root with `P = rtk proxy .audit/php85/php.exe -n`:

```text
P tools/8.6/compiler-contextual-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --cache=.audit --report=docs/8.6/compiler-contextual-probes.json
P tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=docs/8.6/compiler-contextual-pfa.json
P tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/compiler-contextual-context.json
P tools/8.6/deprecation-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/compiler-contextual-deprecation.json
P tools/8.6/integration-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/compiler-contextual-integration.json
P tools/8.6/change-traceability.php --report=docs/8.6/compiler-contextual-traceability.json
P tools/validate-manifest.php
P -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress
P bin/grammar-coverage.php 8.6
P -d extension=.audit/php85/ext/php_mbstring.dll tools/8.6/compiler-contextual-review.php --logs=.audit --report=docs/8.6/compiler-contextual-review.json
```

Source archives, extracted trees, locked binaries and dated cached GitHub
responses must be supplied; a current moving API response cannot silently
replace a historical snapshot. The five cached responses support provenance and
bounded interpretation, not a current exhaustive upstream issue disposition.

The [review ledger](compiler-contextual-review.json) and
[baseline hashes](compiler-contextual-baseline.json) verify preservation of every
pre-existing project file, including all PHP 8.5 grammar/specification, fixtures,
locks and certification evidence; Phase 5A/5B reports; PHP 8.6 EBNF/specification;
manifest; API code; and release gates. The five PHP86-001 production differences
remain intact. Stable/default maintainer selection remains 8.5; numeric latest
version discovery still includes the 8.6 draft. No historical evidence is regenerated.

## 8. Dispositions, remediation and next-phase readiness

The [machine-readable disposition record](compiler-contextual-reconciliation.json)
separates confirmed mechanism, language intent, affected area, known/unknown
behavior, grammar risk, release impact and closure evidence.

| Next activity | Readiness | Bounds |
|---|---|---|
| Targeted correctness remediation | READY WITH BOUNDED LIMITATIONS | Research/documentation scope clarifications and independently verified stage-aware work may be proposed. PHP86-014/015 normative edits and hook-policy reversal remain blocked. |
| Independent PHP 8.6 certification preparation | READY WITH BOUNDED LIMITATIONS | Inventory/build-provenance and evidence-planning work can proceed; this is not readiness to certify or to waive unresolved source/scanner/binding obligations. |
| Candidate-status promotion | NOT READY | PHP86-013 discovery, intended writable/traversal boundaries and completeness gaps remain. |
| Stable release certification | NOT READY | Candidate blockers, exact-build/final-release reconciliation and independent release evidence remain. |

Prioritize intended writable-target repair and source-corresponding execution;
then dispatch-aware traversal policy and partially linked/reentrant declarations.
Prepare C5C-D01/D02 clarifications as separately reviewable changes. Consider
C5C-D03 only through explicit historical-correction governance. This report
identifies the work; it does not begin it.
