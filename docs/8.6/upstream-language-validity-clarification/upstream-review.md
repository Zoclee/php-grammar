# Bounded upstream review — October 26, 2026

This is a read-only refresh. No issue, comment or message was published.
[Retrieval receipts](upstream-refresh.json) retain eleven GET requests, timestamps,
raw response hashes, headers, query strings and comparisons with Phase 7C.6.
The six PR endpoints and five searches returned successfully. No response had
a next-page link; search results report `incomplete_results: false`. Each request
was bounded to 100 records. This is not an exhaustive search of all PHP forums,
branches, issues, review threads or release changes.

## Existing implementation discussions

The material metadata projection and all retained issue/review comment records
for [PR 20628](https://github.com/php/php-src/pull/20628) and
[PR 19658](https://github.com/php/php-src/pull/19658) are unchanged since the
October 25 audit. Raw metadata digests may differ because the comparison excludes
ephemeral metadata; raw responses remain available for inspection. PR 20628 has
17 issue comments and 15 review comments; PR 19658 has eight and 15.

PR 20628 explains the VAR/TMP overhaul. Its
[initial behavior-intent comment](https://github.com/php/php-src/pull/20628#issuecomment-3830980939),
[assertion repair](https://github.com/php/php-src/pull/20628#issuecomment-3831035977)
and [later bugfix assessment](https://github.com/php/php-src/pull/20628#issuecomment-3836866228)
concern other cases, including a newly constructed object passed to a by-reference
parameter. They do not decide assignment-result dimension/property writes.
Review discussion covers unwrapping, internal calls and yield-from elimination;
no exact-form intended-validity answer was identified.

PR 19658 discusses printf lowering and format handling, together with OPcache
pass-one constant collection after removal of call instructions. The
[constant-collection discussion](https://github.com/php/php-src/pull/19658#issuecomment-3244627245)
does not specify validity of a discarded `(unset)` cast. No new answer to that
question was identified in the refreshed comments or review threads.

## Search results and relevance

| Search | Results | Assessment |
|---|---:|---|
| `"hook" "discarded"` | 3 | FPM early bootstrap, JIT overridden properties and OCI CI; no exact unknown-name validity question identified |
| `"unknown hook"` | 0 | No exact result in this query; not proof of absence |
| `"Invalid opcode" "assignment"` | 2 | PR 20903 and old temporary-cleaning PR 1393; neither supplies an answer for the selected assignment-result examples |
| Updated since October 25: writable/opcode | 9 | Related implementation work, chiefly runtime cache, interruption, JIT, memory and extension behavior; no exact-form decision identified |
| Updated since October 25: printf/sprintf | 12 | Runtime/JIT/allocation and unrelated subsystem reports; no discarded-cast validity decision identified |

The complete result lists and bodies are retained in `upstream/`, rather than
silently dropping related hits. Especially relevant bodies were reviewed:

- [PR 20903](https://github.com/php/php-src/pull/20903) addresses **constant-object
  property writes**, links an RFC and implementation commit, and explains VAR
  materialization for write operands. Its exclusion of constant array writes
  is not an exclusion of all assignment-derived writes. It is useful mechanism
  evidence for a different feature, not an intended-validity answer here.
- [PR 24566](https://github.com/php/php-src/pull/24566) addresses a primed
  `ASSIGN_OBJ` cache bypassing set visibility after readonly/error or magic-set
  paths. It concerns repeated runtime property writes, not TMP/CONST containers
  emitted for the selected assignment-result examples.
- [PR 24487](https://github.com/php/php-src/pull/24487) concerns temporary cleanup
  when VM interrupts throw before a consuming opcode executes.
- [PR 24699](https://github.com/php/php-src/pull/24699) preserves indirect type
  information at JIT side-trace entry for changing by-value/by-reference calls.
  It is not the unoptimized compiler write-target boundary.
- [Issue 24683](https://github.com/php/php-src/issues/24683), also retained in
  Phase 7C.6, has a newly retrieved updated record about stale runtime constants
  under tracing JIT. Its full body does not decide compile-time discarded-cast
  acceptance. This refresh does not claim to have read all of that issue's
  comments or to have audited its implementation.

Search-body review is narrower than a full review of every linked issue's
discussion and patch. No moving revision was substituted for the source pin.
These findings support keeping all three questions open; they do not prove
intentional behavior or exhaustively establish the absence of an upstream fix.

## Publication recommendation

The drafts are ready for user review. Proposed destinations are focused php-src
issues, with PHP86-014 referencing PR 20628 and PHP86-015 referencing PR 19658;
CA86-001 is a property-hook/compiler question. The known PR threads are related
implementation discussions, not identified duplicates of these exact questions.
Repeat the duplicate check immediately before any separately authorized
publication. If an exact issue is then found, contribute the minimized evidence
there instead. Publication and maintainer contact are outside Phase 7G.
