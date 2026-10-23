# PHP 8.6 Phase 2.2 risk assessment

Assessment date: October 23, 2026. **Phase 3: NOT READY. Phase 4: NOT READY
as a complete implementation. Final certification: NOT READY.** This review
reduces unsupported closure obligations, bounds PHP86-014, and identifies a
further contextual acceptance difference, PHP86-015. It does **not** complete
the requested discovery closure. PHP86-013 remains open. No Phase 3 work began.

The pins remain PHP 8.5 `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and
PHP 8.6 RC4 `f23b4c222cebe33be93ee8ecaaa14390c5507a08`. Earlier evidence and
IDs are retained. JSON `phase22` fields contain current risk dispositions;
earlier fields and counts remain historical Phase 2.1 evidence.
The [risk evidence](discovery-risk-assessment.json) records supporting hashes,
source anchors, exclusion reasoning, and probe commands.

## Risk rules and source coverage

**Critical:** parser/scanner rules, tokens, precedence, compiler rejection,
writable/reference contexts, declaration validity and constant-expression
evaluation. These need direct review and grammar/contextual mappings.
**Elevated:** AST transformations, optimization, linking, inheritance,
attributes, enums and relevant regression tests. Establish their effect on
acceptance before exclusion. **Low:** a demonstrated mechanical, diagnostic,
harness, runtime or API-only delta with no source-acceptance mechanism.

A directory, filename, expected-output-only change or matching phrase is not
an exclusion rule. Unassociated test records conservatively remain elevated
pending implementation association; that does not require individually reading
every library test. Likewise, a compiler file can contain low-risk mechanical
edits while an optimization introduces critical contextual changes.

| Source scope | Risk and disposition |
|---|---|
| Parser and scanner | Critical; prior full comparisons retained. PFA arguments/trailing comma and parameter metadata identified; no new token, precedence declaration or scanner pattern identified. Executable state/error regression obligations remain. |
| Eight-file compiler/AST ledger | 578 raw hunks: 164 critical, 159 elevated, 255 low. Critical/elevated defaults are conservative queues, not assertions that each changes the language. |
| Restricted mechanical group | 255 low-risk hunks excluded by exact changed-token comparison, allowing only C `const` qualification and `true`/`false` versus `1`/`0`. Strings, character literals and comments remain tokens; preprocessing edits are excluded from this rule. |
| Direct focused additions | PFA AST construction/evaluation/traversal, call/reference result contexts, `printf`, Override constants, enum debugInfo, and interface traversal order. |
| Other core/optimizer files | Still not fully associated with semantic change groups. The eight-file ledger is not the complete discovery universe; no low-risk default is applied outside it. |

Representative mechanical changes are `zend_get_unqualified_name`'s boolean
returns (`zend_compile.c`, old line 186/new 191) and const qualification of the
lookup result in `zend_have_seen_symbol` (445/456). Standard C boolean values
and read-only qualification do not alter the PHP check or its control flow.
This deliberately excludes broader integer-type changes, macro replacements,
allocation rewrites and reordered checks from automatic equivalence.

| Raw hunk disposition | Count |
|---|---:|
| Grouped mechanical exclusion | 255 |
| New focused review, mapped to findings | 16 |
| Examined, interactions unresolved | 16 |
| Prior focused review retained outside mechanical group | 15 |
| Pending direct review | 276 |

The seven Phase 2.2 groups comprise one mechanical group and six semantic
review groups. They are separate from the six historical Phase 2.1 groups and
are not counts of independent language changes. Eight previously examined
hunks also satisfy the narrower mechanical rule; no historical review was
deleted. The ledger retains every raw hunk ID, line range and original hash.

The interface-inheritance move is specifically **not excluded**:
`do_interface_implementation` now installs inherited interfaces before
constant/method/property checks (old lines 2171/2202, new 2164/2199).
Subtype/linking implications need regression reconciliation. PFA AST evaluation
also performs bound-argument/reference checks at evaluation time; parser or lint
success alone does not establish their eventual outcome.

## PHPT dispositions and dependency review

| Current disposition | Count |
|---|---:|
| Individually reviewed and resolved | 5 |
| Grouped, evidence-excluded | 82 |
| Examined but unresolved | 40 |
| Indexed only | 5,862 |
| Total changed PHPTs | 5,989 |

The five individual reviews are the existing GH20628 tests. The 82 grouped
exclusions come from nine existing exact-delta Windows eligibility groups;
they are **not** counted as individually reviewed tests. Of the original 87
dependency-flagged records, 82 now have a bounded exclusion and five remain
unresolved. The other 35 formerly resolved group members are counted as
examined-unresolved here because a byte-identical diagnostic/whitespace delta
alone does not meet the stronger independent-source exclusion rule. Historical
Phase 2.1 classifications remain visible.

The Windows group exclusion uses both sides of `util.inc`, the byte-identical
`util_utf8.inc`, and the test runner's separate SKIPIF process and eligibility
handling (`run-tests.php:2331–2414`). The changed sections move Windows,
slow-test, codepage and iconv checks; FILE, INI and expected sections do not
change within these exact groups. The shared helper changes remove pre-7.1
codepage compatibility and reorganize filesystem/basename operations. They do
not introduce a PHP source-acceptance rule.

Dependency/generation references were scanned in both versions of all 82
members. Reviewed representatives include `bug54028.phpt`,
`test_cp1251_to_utf8_0.phpt`, and both `bug75063_cp1251.phpt` and
`bug75063_utf8.phpt`. The latter two generate PHP filesystem loops; their
generated source is unchanged. The UTF-8 helper's generated include is a fixed
echo statement. This addresses generated input explicitly rather than ignoring
an `include` or `file_put_contents` match. No upstream tests or helpers were
executed. This excludes the documented deltas, not Windows runtime behavior or
all scanner encoding questions.

The remaining 40 examined records retain diagnostic, expected-whitespace,
Solaris eligibility and external-input questions. The 5,862 indexed records
still need source association; they have not been relabeled irrelevant.
PFA, constant evaluation, writable/reference paths, readonly/defaults,
Override/linking, hooks, enum validation and optimized call tests are priority
families because of their implementation associations in the inventory.
Their complete per-record reconciliation is unfinished.

The material language inventory now has **15 IDs**, including the discovery
gap itself; this is not a count of 15 new syntax features. This review adds one
material finding, PHP86-015, through focused probes rather than an upstream
test-suite pass. No PHPT count denotes conformance or semantic completeness.

The inventory separates one structural syntax finding (PHP86-001), five verified
contextual changes (PHP86-003–007), the new contextual traversal observation
(PHP86-015), and one unresolved writable-target discrepancy (PHP86-014).
Other IDs cover explanatory/runtime behavior, corrections, diagnostics,
metadata, optimization uncertainty, exclusions and discovery completeness.

## PHP86-014: source-level explanation, intended syntax still unresolved

The [official GH20628 PR](https://github.com/php/php-src/pull/20628) describes
operand handling and dereference optimization. The fetched metadata records
merged commit `6173a9a10989fa887240590a34c2b0eec50f003e`; it does not establish
an intended expansion of assignment-based writable targets. Search did not
identify an authoritative disposition of the exact new write cases; that is
not evidence that no such discussion exists.

The pinned compiler adds ASSIGN and ASSIGN_REF dispatch in
[`zend_compile_var_inner`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12763).
Ordinary assignment emits a TMP result; reference assignment dereferences its
result only in R/IS contexts, preserving VAR in write contexts. The pinned
[`ZEND_ASSIGN_DIM` handler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2680)
supports VAR/CV containers, not TMP. TMP is operand type 2, matching the
observed invalid-opcode tuple `23/2/1`. List assignment from a variable copies
its RHS through a TMP and exposes the same mismatch in the recorded case.

Thus the source explains why lint can finish opcode generation and execution
then enter an invalid-handler fatal. It also explains why the reference case
can execute successfully. This is a compiler/VM inconsistency, not a basis for
declaring ordinary/list forms syntactically invalid or reference forms intended
new-valid syntax. GH20628's dimension-read regression does not test these writes.

No exact-pin executable was built. The Windows RC4 version label and matching
source explanation strengthen the observation but do not remove its provenance
limitation. Keep PHP86-014 as a **bounded implementation-area blocker** for
assignment/dereference writable-target rules and final certification. By itself
it need not block an unchanged baseline once the broader discovery gate closes.
No writable-target edit is authorized.

## PHP86-015: optimized printf changes contextual traversal

The new `zend_compile_func_printf` evaluates the first argument before ordinary
compilation and falls back to the existing `sprintf` optimization. Constant
evaluation traverses both AND/OR children but can discard a known untaken
conditional/coalesce branch. This changes whether an unsupported unset cast is
visited. The ordinary compiler takes a different traversal path.

| Probe form | Tested 8.5 lint | Tested RC4 lint |
|---|---|---|
| `printf(false && (unset)$x);` | Pass | Reject |
| `printf(false && (unset)$x, 1);` | Pass | Reject |
| `printf(true ? "ok" : (unset)$x);` | Reject | Pass |
| `printf("ok" ?? (unset)$x);` | Reject | Pass |
| `$f="printf"; $f(false && (unset)$x);` | Pass | Pass |
| `sprintf(false && (unset)$x);` | Reject | Reject |

Fully qualified `printf` and an unreachable `if(false)` statement retain the
new rejection; a folded-away enclosing closure avoids it. A live unset cast
fails both versions. These are lint-only observations, not executed programs.
Five foreach/nullsafe controls also pass both versions, so PHP86-007's
return/yield restriction must not be generalized to foreach from a helper-name
match. Optimized array_map/pipe ordering probes preserve the checked PFA errors;
they do not certify every optimization path.

The 20 new paired probes bring the ledger to 115 sources / 230 observations:
105 lint, seven declaration-link and three controlled-runtime sources. Existing
probes are unchanged. PHP86-015 requires contextual validation-order treatment,
not new EBNF terminals. The intended compatibility boundary and full optimized
call coverage remain unresolved. Do not amend the PHP 8.5 grammar to compensate.

## RFC reconciliation

All 58 proposal identities and their prior snapshot/ancestry evidence remain.
Ten syntax/context candidates have explicit finding IDs and selected-pin
source anchors; 20 API/build/runtime proposal scopes are excluded from grammar
edits; 28 draft/discussion/withdrawn proposals remain excluded from speculative
implementation. These classifications are in [rfc-review.json](rfc-review.json).

API scope exclusions distinguish pack format strings from PHP operators,
runtime trim masks from scanner whitespace, Reflection query methods from new
modifiers, and stream input acquisition failures from the grammar of supplied
source. Full unrelated API certification is not a baseline-copy prerequisite.
The exclusion does not declare arbitrary accompanying compiler changes harmless.

Primary ancestry remains verified for 28 candidates, unresolved for the optional
placeholder follow-up and stream-errors candidate. Optional placeholder behavior
has separate selected-pin source/test evidence; the placeholder RFC link is
still not commit evidence. Closure automatic static inference remains excluded.
The deprecations page's pending status does not override implemented warnings.
Complete current-index discovery and reversion/component reconciliation are
still open; this report does not claim to have finished that portion of the
request.

## Phase-specific readiness and remaining work

The [inventory](language-change-inventory.md#phase-22-blocker-matrix) assigns
all IDs separately. Verified syntax/context changes PHP86-001–010 retain their
production mappings and future positive, negative, stage-specific and boundary
test obligations. PHP86-011 loses any blanket optimization no-impact reading;
PHP86-012 does not make unrelated API runtime certification a grammar blocker.

**Phase 3 — NOT READY.** Pins and preservation requirements are established,
and PHP86-014 is bounded. However, the new printf acceptance finding, the
interface-order question, and incomplete source/test association show that
material discovery uncertainty is not yet isolated. This decision is not based
on needing to read every library PHPT or certify every API. Finish the credible
compiler/optimizer/linking queues and their relevant test families; provide
source-backed grouped exclusions for the rest. The harmlessness of copying
alone does not discharge the discovery obligation.

**Phase 4 — NOT READY as a complete implementation.** PFA list edits and the
verified contextual findings have concrete evidence and can remain in the
plan. Assignment-based writable targets and unverified optimization/traversal
rules remain blocked independently of baseline creation. No speculative edit
or blanket contextual acceptance rule is justified. This is not a claim that
every verified finding lacks sufficient evidence.

**Final certification — NOT READY.** No 8.6 package or implementation evidence
exists, the source is RC4, exact-pin interpreter correspondence is limited,
and discovery/contextual discrepancies remain. All existing structural,
parity, differential, version-boundary, traceability and release gates still
apply. Neither the exclusions nor the successful integrity checks waive them.

## Validation and preservation

| Check | Result |
|---|---|
| Existing source/archive/executable hashes | PASS: 341 checks, zero failures |
| New source evidence and PR snapshot | PASS: 26 source hash/absence checks and PR response hash |
| Research JSON / ledger integrity | PASS: historical totals, original Zend hashes, nine exclusion groups, 164 excluded-PHPT side hashes, current risk totals, 115 paired sources, RFC ancestry invariants and grammar anchors |
| Local Markdown links / whitespace | PASS: 39 local links; no trailing whitespace |
| Manifest validation | PASS: existing schema 1.0 and package paths |
| PHPUnit Repository | PASS: 21 tests, 885 assertions |
| PHPUnit Ebnf | PASS: 73 tests, 153 assertions |
| Documentation parity/specification structure filter | PASS: four tests, 65 assertions |
| Protected-file preservation snapshot | PASS: all 1,565 pre-existing protected files unchanged |
| Git diff whitespace and scope | PASS: changes confined to `docs/8.6/` |

PHPUnit used the explicit PHP 8.5.10 executable with `-n` and the audit copy's
mbstring extension. The temporary PHP integrity driver checks ledger invariants;
it is not a new tracked release gate or a claim of a JSON Schema for research
artifacts. Git ownership checks required read-only execution outside the
restricted runner; no global Git configuration was changed.

Research changes are confined to `docs/8.6/`; temporary PHP drivers remain in
ignored `.audit/`. No upstream PHPT suite, new workflow, grammar package,
manifest registration, fixture change, release-gate change or historical
certification regeneration occurred. These checks establish research and
repository integrity, not completed discovery or PHP 8.6 conformance.
