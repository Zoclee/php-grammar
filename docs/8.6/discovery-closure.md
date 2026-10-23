# PHP 8.6 Phase 2.1 discovery closure assessment

Phase 2.3 governance note, October 23, 2026: [Gate A](../versioning.md#31-readiness-gates)
now permits a protected, unregistered, unchanged working baseline before discovery
closure. The Phase 3A block below was correct under the earlier policy and is
retained as history; its copying prohibition is superseded only for Gate A.
Discovery is still incomplete, and PHP86-013/014/015 remain unresolved with the
implementation and certification impacts in the
[current readiness decision](implementation-plan.md#phase-23-readiness-decision--october-23-2026).
No Phase 3A work is performed by this governance clarification.

Historical Phase 2.1 assessment. The current
[Phase 2.2 risk assessment](discovery-risk-assessment.md) separates phase
blockers, bounds PHP86-014, records PHP86-015, and provides revised disposition
counts. It still does not claim discovery closure; PHP86-013 remains open.

Assessment date: October 23, 2026. **NOT READY. Phase 2.1 is incomplete and
PHP86-013 remains open.** The work below records additional evidence and a new
blocking discrepancy; it does not claim to have completed the requested audit.
No Phase 3 work has begun.

## Baselines and preservation

The source comparison retains PHP 8.5
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and PHP 8.6.0RC4
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. All 341 recorded SHA-256 checks
passed before comparison: five archives/executables and 168 pairs of selected
source files, including absent-side entries where applicable. The downloaded
PHP 8.5.10 and RC4 executables retain the provenance limitations in
[source discovery](source-discovery.md); neither is certified as an exact-pin
reproducible build.

The [official schedule](https://wiki.php.net/todo/php86), refreshed October 23,
announces RC4 on October 21 and schedules RC5 for November 5. No newer release
was identified there and no baseline was replaced. Snapshot hashes are in
`source-evidence.json`. Historical PHP 8.5 certification was not regenerated.

## Test reconciliation

The [machine-readable ledger](upstream-test-reconciliation.json) expands the
original Zend-only index to every changed PHPT path in the cached archive
comparison. Extracted test bytes were checked against the recorded hashes.
Every changed section has old/new hashes; FILEEOF, FILE_EXTERNAL, setup,
configuration, and expected-output changes are included.

| Measure | Records |
|---|---:|
| Changed PHPTs indexed across the source tree | 5,989 |
| Zend/tests subset (the original index) | 1,124 |
| ext/opcache/tests subset | 198 |
| Examined and resolved | 40 |
| Examined PHPT delta, unresolved external/generated input review | 87 |
| Merely indexed, no semantic disposition | 5,862 |

The 127 examined records include five individually inspected GH20628 tests and
members of 18 reviewed groups with byte-identical complete section deltas.
Group records preserve individual members, the changed section content, and
the classification rationale. Findings include diagnostic capitalization,
finally-return warnings, expected-output whitespace, and platform/extension
eligibility changes. A filename was never used as an irrelevance criterion.
Potential external/generated input dependencies deliberately prevent a resolved
status even when the changed PHPT sections have a clear classification.

`gh20628_004.inc` was separately inspected and hashed. The other indexed tests'
dependencies are not certified. The ledger is a broader discovery universe,
not a claim that every library test needs language-level investigation. A
defensible relevant/excluded disposition is still required. No upstream PHPT
suite was executed.

## Compiler and AST reconciliation

[The compiler ledger](compiler-change-reconciliation.json) inventories 578 raw
hunks in `zend_compile.c`, `zend_ast.c`, `zend_ast.h`, `zend_inheritance.c`,
`zend_enum.c`, `zend_attributes.c`, `zend_API.c`, and `zend_constants.c`.
Twenty-three hunks are explicitly examined and linked to six review groups;
555 remain indexed. Raw line ranges and hunk hashes retain both immutable
source locations. This coverage does not encompass all changed optimizer/core
files and does not certify the unreviewed hunks.

Focused review addresses list/reference result handling, assignment dispatch,
pipe result contexts, nullsafe reference returns/yields, constant-PFA traversal,
and explicit-static closure caching. The ledger lists all ten requested source
areas with their actual status. Optimized call paths, broader declaration
traversal, and complete inheritance/AST reconciliation remain unfinished.

Forty-one new paired probes were added to `syntax-probes.json`: 31 lint probes,
seven declaration-link probes, and three controlled runtime probes. Together
with the original 54 lint probes, this is 95 sources and 190 observations.
Only short, reviewed sources were executed; declaration probes define classes,
interfaces, and enums without invoking application methods. Runtime probes use
local arrays and JSON output, without external input or file/network effects.

The linking observations strengthen PHP86-003/005/006. Nullsafe probes strengthen
PHP86-007: unreachable statements still receive checks while a folded-away
enclosing closure can avoid them. Ordinary versus initializer PFA probes
strengthen PHP86-001. Constant closure controls preserve the explicit-static
requirement in PHP86-011.

## Newly discovered material discrepancy: PHP86-014

The RC4 compiler's new ASSIGN/ASSIGN_REF dispatch accepts some assignment-based
dimension-write paths that PHP 8.5 lint rejects. Controlled execution gives
different outcomes:

| RC4 source form | Lint | Execution |
|---|---|---|
| `($v = [1])[0] = 2;` | Pass | `Invalid opcode 23/2/1` fatal |
| `$w=[1]; ($v = &$w)[0] = 2;` | Pass | Both aliases contain `[2]` |
| `$w=[1]; ([$v] = $w)[0] = 2;` | Pass | `Invalid opcode 23/2/1` fatal |

This is not a blanket new-valid-syntax finding. The corresponding read control
passes both versions; literal-array-write and destructuring-target controls
fail both. Existing upstream GH20628 tests cover neighboring cases, not the
exact new write failure. [PHP86-014](language-change-inventory.md#php86-014--assignment-based-write-contexts-and-invalid-opcode-discrepancy)
records source anchors, probe IDs, production mapping, and required follow-up.
Resolve intended behavior and exact-pin behavior before changing writable-target
constraints. PHP86-011's provisional no-impact treatment cannot cover this area.

## RFC reconciliation

All 58 existing official RFC snapshots were fetched again and are byte-identical
to the prior snapshots. The official index was also refreshed. Each existing
record in [rfc-review.json](rfc-review.json) preserves its new status metadata,
snapshot hash, and available ancestry evidence.

For 30 accepted/implemented candidates, 28 primary candidate implementation
commits were verified as ancestors of RC4 using the official php-src GitHub API.
Actual PR `merged` state, candidate commit, base/merge-base identities, API
response hash, and comparison status are recorded separately. Explicit commits
were checked where a linked PR was not marked merged. This does not establish
complete integration of optional parts, later follow-ups, or absence of reverts.

The optional-placeholder page has no usable implementation commit link; its
existing pinned-source evidence remains relevant but its new ancestry check is
unresolved. The stream-errors candidate also remains unresolved. The remaining
per-library selected-pin checks and recent rejected/withdrawn/postponed index
review have not been completed. Excluded proposals remain excluded, including
the withdrawn case-sensitive-PHP proposal. No proposed syntax was added.

## Language-change inventory and implementation impact

The detailed [inventory](language-change-inventory.md) preserves PHP86-001
through PHP86-013 and adds PHP86-014.

| Finding | Material impact / existing grammar anchors |
|---|---|
| PHP86-001 | PFA placeholders, callable trailing comma, constant PFA validation: argument-list families, argument-no-expression, constructor/clone lists; contextual ordering and folding |
| PHP86-002 | Placeholder optionality explanation; no independent production addition |
| PHP86-003 | Readonly defaults: property-declaration contextual relaxation |
| PHP86-004 | Constant-object writes: variable/property/dimension and writable-target constraints |
| PHP86-005 | Override constants/cases: attributes, class constants, enum-case contextual/linking rules |
| PHP86-006 | Enum __debugInfo: enum/method contextual restriction relaxed |
| PHP86-007 | Nullsafe reference return/yield rejection: return-statement and yield-expression constraints |
| PHP86-008 | Qualified-static implementation correction; no proven valid-language removal |
| PHP86-009 | Parameter doccomment metadata; no production change |
| PHP86-010 | Deprecated but accepted identifiers, returns/yields, and types; documentation only |
| PHP86-011 | Closure/call optimizations; explicit-static rules retained; broader interactions still provisional |
| PHP86-012 | Excluded proposals and library integration review; no speculative grammar changes |
| PHP86-013 | Unresolved discovery completeness |
| PHP86-014 | Assignment/dereference writable-target discrepancy; no grammar edit authorized |

## Readiness decision

**NOT READY.** Both the newly observed PHP86-014 discrepancy and substantial
unfinished test/source/RFC review prevent closure. These are not justified
minor residual limitations. The [implementation plan](implementation-plan.md)
retains the research gate and distinguishes reference-assignment writes from
the ordinary/list invalid-opcode cases.

Complete the unresolved ledger records or exclude them with evidence; finish
compiler/AST/optimizer source reconciliation; verify each RFC's implemented
components and excluded statuses; resolve PHP86-014 with authoritative evidence
and appropriate executable provenance. Do not create an 8.6 grammar package
while those obligations remain.

## Artifacts and validation

Modified all four Phase 2 Markdown documents and all four existing JSON evidence
files. Added this assessment and the PHPT/compiler ledgers. Research drivers
are temporary PHP files under ignored `.audit/`; no tracked workflow or release
gate was changed.

| Check | Result |
|---|---|
| Recorded artifact and selected source hashes | PASS: 341 checks, zero mismatches |
| Temporary PHP research-integrity check | PASS: six JSON documents decoded; record uniqueness, totals, group membership, original 1,124 Zend hashes, ancestry invariants, 95 paired sources, and 31 local Markdown links checked |
| `tools/validate-manifest.php` with PHP 8.5.10 | PASS: existing schema 1.0 and package paths |
| PHPUnit `tests/Repository` | PASS: 21 tests, 885 assertions |
| PHPUnit `tests/Ebnf` | PASS: 73 tests, 153 assertions |
| PHPUnit documentation parity/specification structure filter | PASS: 4 tests, 65 assertions |
| SHA-256 preservation snapshot | PASS: all 1,565 pre-existing grammar, test, tool, source, historical-evidence, manifest, Composer, and policy files unchanged |
| `git diff --check` and Markdown whitespace check | PASS |

PHPUnit used `-n -d extension_dir=.audit/php85/ext -d extension=mbstring`.
The integrity check validates the research ledger structure and internal
invariants; there is no claim of a pre-existing JSON Schema for these new
research ledgers. Existing validation tooling, grammar/specification, fixtures,
manifest registration, and release gates are unchanged. No `grammar/8.6/`
directory exists. Git's restricted-runner ownership issue required read-only
checks outside that runner; no global Git configuration was edited.

These successful checks establish research-artifact and repository integrity,
not completion of the discovery audit or PHP 8.6 conformance.
