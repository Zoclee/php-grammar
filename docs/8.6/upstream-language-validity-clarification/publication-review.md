# Final upstream issue publication review

October 27, 2026 — Phase 7G.1. Editorial finalization only; no issue was posted
and no maintainer was contacted. The intended language outcomes remain unresolved.

## Drafts and recommendation

| Draft | Final title | Recommendation |
|---|---|---|
| [PHP86-014](PHP86-014-draft.md) | PHP 8.6: Assignment-result writes compile but fail with invalid opcode | READY TO PUBLISH |
| [PHP86-015](PHP86-015-draft.md) | PHP 8.6: Optimized printf changes compile-time acceptance of discarded expressions | READY TO PUBLISH |
| [CA86-001](CA86-001-draft.md) | Unknown property hooks are accepted inside discarded declarations | READY TO PUBLISH |

Each issue has Summary, Minimal reproducer, Actual behaviour, Expected behaviour /
clarification requested, Affected versions and Relevant implementation sections.
Internal phase/gate terminology, fixture paths, timestamps, executable hashes,
response-map references and certification procedures were removed from public
text. The malformed writable-operation table was repaired. The traversal
question no longer asks maintainers to specify every dispatch combination.

Eight fenced PHP blocks and eight writable table cases remain byte-identical
to retained evidence. The assignment read/literal controls and live/constant-arm
hook controls omitted from the public bodies remain in [reproducers.json](reproducers.json).
The [response/action map](response-actions.json), full provenance, probe matrices
and prior upstream review remain unchanged. No verified observation was deleted.

## Final read-only duplicate review

[Retrieval receipts](publication-review/retrievals.json) retain ten successful
GitHub API GET requests, exact query URLs, raw response/header hashes and scope.
The PR metadata, issue comments and review comments for
[PR 20628](https://github.com/php/php-src/pull/20628) and
[PR 19658](https://github.com/php/php-src/pull/19658) are materially unchanged
from the [October 26 review](upstream-review.md). Their discussions concern
VAR/TMP handling and printf lowering, respectively; neither establishes intended
validity for these exact examples.

Each query was scoped to `repo:php/php-src`, with at most 100 results. All four
reported `incomplete_results: false` and no next page; the six PR endpoints also
had no next-page link.

| Search terms | Results | Review finding |
|---|---:|---|
| `"Invalid opcode" "assignment"` | 2 | [PR 20903](https://github.com/php/php-src/pull/20903) concerns constant-object writable operands; [PR 1393](https://github.com/php/php-src/pull/1393) concerns temporary cleanup. Neither identifies the assignment-result question as an existing issue. |
| `"printf" "unset"` | 32 | Returned titles and relevant body passages concern runtime, memory, extension behavior and benchmarks; no exact discarded-cast traversal issue identified. Full returned bodies are retained for review. |
| `"unknown hook"` | 0 | No match in this query. |
| `"hook" "discarded"` | 3 | [PR 6772](https://github.com/php/php-src/pull/6772) concerns FPM bootstrap, [PR 17395](https://github.com/php/php-src/pull/17395) JIT for overridden properties, and [PR 8348](https://github.com/php/php-src/pull/8348) OCI CI. None addresses discarded unknown hook names. |

No exact duplicate was identified within this bounded review. It is not an
exhaustive search of issue comments, forums, branches or all possible search
terms, and does not prove absence of a duplicate or fix. If an exact issue is
identified before submission, contribute the minimized evidence there instead.
PRs 20628 and 19658 remain implementation references, not identified duplicates.

## Technical verification

The [evidence-integrity receipt](publication-review/integrity.json) checks the
4,580-file historical baseline, all 93 retained probe inputs, interpreter/DLL
and build-record hashes, all 40 selected observations and twenty source slices
in both clean and build source trees. All checks pass. This is a new verification
of retained evidence, not a new certification assessment.

The [example verification](publication-review/examples.json) maps each of the
sixteen published examples/table cases to its authenticated observation and
records 42 successful comparisons using the simplified public command profile:
32 paired `php -n -l` runs and ten RC4 `php -n` writable-case runtime runs.
Exit statuses, rejecting diagnostics and successful writable outputs match the
retained observations. No traversal or hook runtime behavior is inferred from lint.

Pinned source URLs were checked against the authenticated local source files
and line locations. The compiler, parser and VM anchors identify the stated
implementation; source-file digests and line numbers are retained in the example
receipt. No source pin changed. PHP 8.5 remains a distribution comparator, not an
exact-source attestation; no bit-for-bit PHP 8.6 build reproducibility is claimed.

An additional invocation of the full `exact-pin-check.php` produced no result
before interruption in either the sandbox or owner context. No fresh full
provenance attestation is claimed. Authentication rests on the preserved prior
receipt and the successful current executable/DLL, build-record, source-slice
and observation-integrity checks described above.

Markdown review checked the six-section order, balanced fences, table column
counts, literal PHP blocks, diagnostic formatting and independent readability.
Local documentation links and trailing whitespace were checked. The public
questions preserve the distinction between parsing, compilation, opcode emission
and execution, and do not infer intended validity from optimization or elimination.

## Preservation and scope

The [preservation receipt](publication-review/preservation.json) compares the
start-of-review file hashes with the final tree and records current artifact
hashes. Only the three drafts and their research overview changed among existing
project files; new files contain this review and its evidence. The original
`evidence-files.json` and `validation.json` remain historical receipts for the
October 26 revision, explicitly identified as such in the overview.

Grammar productions, normative specifications, fixtures, source pins, public
APIs, release gates, package status, historical artifacts and all seven blockers
remain unchanged. No full release suite was rerun for this documentation-only
change. Publication readiness means readiness to ask the questions, not resolution
of the language-validity findings or authorization to publish.
