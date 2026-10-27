# PHP 8.6 upstream language-validity clarification

## October 27 editorial finalization (Phase 7G.1)

The three linked drafts now use the publication-ready six-section issue format.
The [final publication review](upstream-language-validity-clarification/publication-review.md)
records the editorial changes, refreshed duplicate search, example verification,
preservation checks and per-issue recommendations. Nothing has been published.

The sections below retain the October 26 research record. Their twelve-block
count, reproduction-profile wording, validation receipt and artifact ledger
describe that earlier draft revision, not the revised public bodies. The current
drafts retain eight fenced PHP examples and eight supporting table cases;
omitted controls and full profiles remain in `reproducers.json`. The new review
retains separate receipts rather than rewriting historical validation or hashes.
All blockers, response/action mappings and certification dispositions remain unchanged.

## October 26 research record

October 26, 2026 — Phase 7G. **Three final drafts are ready for user review and
remain unpublished.** This phase prepares questions; it establishes no new
language rule, blocker closure, certification claim or release status.

## A. Three clarification requests

| Draft | Precise question | Proposed destination |
|---|---|---|
| [PHP86-014](upstream-language-validity-clarification/PHP86-014-draft.md) | Should assignment/reference/destructuring-result dimension and property writes, compound writes, unset and reference binding reject during compilation or receive supported writable operands? | Focused php-src issue referencing PR 20628, unless an exact existing issue is identified before publication |
| [PHP86-015](upstream-language-validity-clarification/PHP86-015-draft.md) | Is optimized-call traversal intended to alter compile-time validity of discarded expressions, and what exact dispatch/initializer boundaries apply? | Focused php-src issue referencing PR 19658, subject to the same duplicate check |
| [CA86-001](upstream-language-validity-clarification/CA86-001-draft.md) | Must unknown hook names reject even inside eliminated closures and discarded constant-initializer arms? | Focused php-src property-hook/compiler issue, or an exact existing thread if found |

These are additive successors to the three Phase 7D drafts, which remain
byte-identical. Their expected language outcomes are deliberately questions,
not guessed requirements. Runtime opcode failures, compilation success and
parser permissiveness are kept distinct.

## B. Reproducer verification

The [selected evidence](upstream-language-validity-clarification/reproducers.json)
retains 20 cases and 40 paired lint observations, original JSON pointers,
fixture bytes/digests, exact command arrays, diagnostics and controlled runtime
results where already recorded. Twelve source blocks in the drafts match their
retained fixture bytes. The writable operation table supplies the remaining
small witnesses without reproducing the full 26/31/five-case investigations.

The command profile is `php -n -d zend.multibyte=0 -d short_open_tag=1
-d error_reporting=-1 -d memory_limit=64M -d max_execution_time=5
-d opcache.enable_cli=0 -d opcache.jit=0 -l <file>`. Drafts give executable paths
and explain omitting `-l` only for the controlled writable runtime witnesses.
No new probe matrix was executed: retained authenticated observations suffice.

PHP 8.5.10 is a distribution comparator, not an attested build of the PHP 8.5
source pin. PHP 8.6.0RC4 NTS VC2022 x64 is authenticated to
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`; executable SHA-256
`445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`,
core DLL `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f`.
The PHP 8.5 source pin remains `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.

Fresh execution of `tools/8.6/exact-pin-check.php` passes under
`php86-exact-source-v1`: exact-source verified, certification not granted,
zero errors. The [receipt](upstream-language-validity-clarification/provenance.json)
and [log](upstream-language-validity-clarification/provenance.log) retain that
result. This authenticates the existing build under its controller/custody
contract; it is not a new build or a bitwise-reproducibility claim.

## C. Upstream review

The [bounded refresh](upstream-language-validity-clarification/upstream-review.md)
retains eleven successful read-only requests and full raw response hashes.
Both relevant PRs' material metadata and all retained issue/review comments are
unchanged since October 25. No new authoritative decision on these exact forms
was identified. Related constant-object operand work, runtime property-cache
checks, VM interrupt cleanup and JIT reports are classified separately.

No exact duplicate was identified in the bounded searches. That conclusion
does not cover every PHP discussion or prove the absence of a fix. A duplicate
check immediately before separately authorized publication remains appropriate.

## D. Potential resolutions

The [response/action map](upstream-language-validity-clarification/response-actions.json)
retains D-U01, D-U02 and D-U03 and distinguishes rejection, support, mixed
boundaries and unanswered intent. It identifies potential upstream corrections,
contextual wording, fixture changes and narrowly justified EBNF changes.

For PHP86-014, supported writes need coherent operand emission; rejected forms
need authoritative compile-time boundaries. For PHP86-015, uniform validity may
require traversal repair, while intentional dispatch differences need precise
contextual specification. For CA86-001, uniform rejection can preserve get/set
grammar while requiring earlier compiler checks; intentional discarded acceptance
requires an independently adjudicated structural/contextual boundary. None
justifies accepting unknown hooks in live declarations.

Every normative correction requires separate Gate B authorization and reviewed
source-corresponding regressions. An explanatory maintainer answer alone does
not discharge a mixed source, implementation, scope and release obligation.

## E. Remaining blockers

All seven blockers remain: **PHP86-013, PHP86-014, PHP86-015, CA86-001,
C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE**. All nine blocked current obligations
remain. The prior Phase 7F assessment is **29 passed / 0 failed / 9 blocked**
under its 38-obligation contract; those are retained results, not newly run
Phase 7G gates. PHP 8.6 stays draft and uncertified; PHP 8.5 remains recommended
stable. No candidate promotion or remediation phase begins.

## F. Preservation and validation

The [baseline](upstream-language-validity-clarification/baseline.json) records
HEAD `4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting dirty tree,
4,580 project file hashes and the then-matching Phase 7F inventory.
[Focused validation](upstream-language-validity-clarification/validation.json)
checks every baseline file, all 93 retained probe inputs, executable/DLL/build
identity, selected observations, code-block equality, twenty source slices in
both clean and build trees, upstream raw response/header digests, JSON,
documentation links and whitespace. Source slices preserve original line
terminators; fixture bytes are not normalized.

Focused validation passes with zero errors: all 4,580 baseline files are
byte-identical, 40 selected observations match, and all twenty source slices
match in both trees. JSON and 26 local documentation links pass. The read-only
Git whitespace check also passes in the repository owner context; the sandbox
context could not read Git metadata. Final bundle hashes are retained in the
[artifact ledger](upstream-language-validity-clarification/evidence-files.json).

The [freshness comparison](upstream-language-validity-clarification/freshness.json)
distinguishes the unchanged bounded compatibility dependency inventory from the
expanded global certification inventory. New research documents make the old
global assessment historical for this expanded tree. No old digest, gate result
or contract was rewritten to conceal that fact.

Only additive research documentation/evidence is introduced. No syntax,
expectation, implementation, test discovery or release configuration changes.
Under the [conformance policy](../conformance-policy.md), the full
release suite applies to release claims and syntax-affecting work; no such claim
is made here. It was not rerun for these research documents. The Phase 7F
6,292-test / 58,829-assertion result and 15-command compatibility result remain
historical evidence, not Phase 7G executions. Focused checks and fresh provenance
verification are the actual validation for this phase.

## G. Publication readiness

All three drafts are ready for user review. They need no further local probe
execution to express their questions. The intended language outcomes require
upstream clarification and remain unresolved. Nothing was posted, no maintainer
was contacted, and no external publication or subsequent implementation is
authorized by this completed preparation phase.
