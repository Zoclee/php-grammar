# Phase 7H validation receipts

PHP 8.6 remains draft. No certification is granted. This output directory uses
the existing certification contract's revision-output boundary; no freshness
exclusion or gate requirement was changed.

The [focused regression](../../upstream-maintainer-resolution/regression.json)
contains 18 fresh lint observations and zero mismatches. Original fixtures and
source pins are unchanged. The two new observations check the maintainer's
standalone short-circuit example; no fixture program was executed.

The current v2 certification generator refuses before execution because its
closed preservation transition requires the predecessor bytes of
`grammar/8.6/changes.md`, `grammar/8.6/php.md` and `tools/8.6/change-map.json`.
Those three files contain the reviewed informative clarification and its
traceability hashes. This is a current contract-freshness failure, not evidence
that EBNF changed. The allowlist was not extended. Original v2/v4 contracts,
historical reports and failed conditions remain visible.

The original nine substantive gates and seven blockers remain open. A fresh
29/0/9 assessment is not claimed. The earlier Phase 7F counts remain historical.
Current command exits and their exact logs are retained alongside this appendix;
generation and independent verification are separate operations.

The [integrity check](integrity.json) verifies 4,634 predecessor files with
exactly the three reviewed changes, preserves the original traceability map,
and authenticates all six comments, both issue states, closure actors/events,
all original work-item/gate requirements and 66 local links. It passes with
zero errors. The [initial checkpoint](integrity-checkpoint.json) exposed an
overstrict timestamp comparison and the then-unwritten appendix link. GitHub's
issue `closed_at` and closure-event `created_at` differ by one second for each
issue: events are October 27 at 23:00:30 and 23:05:41 UTC respectively. Both
original values are preserved; the corrected check verifies each independently
against the response index rather than demanding equality between distinct
API fields. No upstream evidence was altered.

The fresh compatibility full-regression command ran **6,292 tests / 58,113
assertions with one failure**: `Php86CompilerContextualTest::testRecordedOracleEvidenceIsFreshAndStageSpecific`
still requires the historical `correctness-remediation-probes.json` digest of
`grammar/8.6/php.md`. The informative edit makes that historical freshness claim
fail. The original report and test were preserved, and the failure was not
suppressed. Its assertion count is lower because the failing test stops at the
first stale input. This is not a passing full-suite result.

## Final command results

| Check | Exit | Result |
|---|---:|---|
| [source](source.log) | 0 | pass |
| [provenance](provenance.log) | 0 | pass |
| [historical](historical.log) | 0 | pass |
| [traceability](traceability.log) | 0 | pass |
| [blocker-evidence](blocker-evidence.log) | 0 | pass |
| [manifest](manifest.log) | 0 | pass |
| [differential-retained](differential-retained.log) | 1 | refused; see retained diagnostics |
| [current-generate](current-generate.log) | 1 | no certification granted |
| [current-check](current-check.log) | 1 | no certification granted |
| [current-claims](current-claims.log) | 1 | no certification granted |

[Current compatibility](../../../8.5/compatibility/phase7h/evidence.json) is referenced
by its repository path in [the receipt](compatibility.json): 14 of 15 commands
passed, with unchanged inputs and no missing environment prerequisites. Independent
verification refuses the failed full-regression command; its success-result parser
also reports a missing actual PHPUnit result because the log ends in failure.
The actual failing test totals above come from that retained log, not that parser.

The retained ordinary-differential report is stale after the three reviewed
documentary/traceability edits. It is not a freshly passing 353-case matrix.
Current v4 check and claims refuse stale current dependency membership and stale
differential inputs. Current v2 generation refuses its fixed preservation hashes
before running gates. Thus there is **no fresh current 38-obligation aggregate**;
the nine original substantive blocked obligations and all seven mixed blockers
remain, alongside these current evidence-freshness failures.

A reviewed successor transition and fresh dependent contextual/differential
receipts are remaining integration work before a current green regression or
certification assessment can be claimed. Historical evidence must remain intact;
this phase does not extend allowlists, replace historical expectations, weaken
checks or treat informational updates as an implicit gate waiver.

[Final summary](summary.json), [preservation/integrity](integrity.json),
[certification receipts](certification.json), and [artifact digests](evidence-files.json)
record the results. JSON parsing and Git whitespace review pass. All old files
except the three reviewed informative/traceability edits retain their baseline
bytes; both EBNFs, PHP 8.5 artifacts, source locks, fixtures and original
certification records are preserved.
