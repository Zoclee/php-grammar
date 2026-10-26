# Hybrid historical and current evidence implementation

October 25, 2026. Phase 7C.2 implements infrastructure and bounded current
compatibility evidence. PHP 8.5 remains the recommended stable grammar; PHP 8.6
remains draft and uncertified. The [Phase 7C.1 review](historical-evidence-contract-review.md)
and every previous evidence file remain historical records.

## Architecture and coexistence

The implementation uses the proposed `tools/evidence/historical-check.php`,
`compatibility.php`, `compatibility-policy.json`, and
`schema/certification-compatibility.schema.json` paths. Internal PHP classes
`HistoricalEvidence` and `CompatibilityEvidence` separate integrity, dependency
discovery, generation and independent verification. `documentation-inputs.json`
records explicit transitive document inputs. The new format identifies itself as
`php-grammar/current-compatibility/1`, evidence kind `current-compatibility` and
contract `current-compatibility-v1`. It cannot be parsed as a legacy schema-1
certification or manifest schema 1.0.

The old PHP 8.5 generator, policy, release runner, ledger, source locks and
claim documents were not changed. The old repository-wide freshness command
continues to fail for legitimate input changes. Historical integrity and current
compatibility are separately verified facts; neither renews the old decision.

## Historical authentication and the eight inputs

The trust anchor is the retained ledger at Git commit
`7d8b6e5fa92f82e7e8c122a9a989a22727f61c3e`, raw Git-blob SHA-256
`04bb3abfa0635036759ceb4698525f2ed015d77f6937be460a7e8e134bea2257`.
The preserved checkout ledger remains SHA-256
`cce919d8b848a9892c33d62b09d5b4d87d6f6a50bf551746cefce634fd47ac9c`.
The first certification revision is separately identified as
`db06a494d6e958e065371ee27cc301227314fd59`. The verifier checks the immutable
Git object, expected schema/pin/input count, preserved ledger and each raw input
against the anchored expected digest. It does not trust hashes supplied by an
unverified current report.

The Phase 7C.1 isolated checkout supplies 1,537 matching inputs. The preserved
Phase 7B snapshot supplies the eight exceptions below. All eight were already
present in that snapshot before this implementation; no bytes were synthesized,
normalized or copied over historical files. Their raw contents match the
immutable ledger's SHA-256, which authenticates content independently of the
snapshot's filename. This resolves exact **content identity**; it does not
establish archive custody, original execution success, or build provenance.

| Historical path | Expected SHA-256 | Result |
|---|---|---|
| `src/Ebnf/Validation/GrammarValidator.php` | `bdf4560238826406bce9077b239ed4ade0b54faf3802820de95cce83f3453cf6` | Exact preserved bytes verified |
| `tests/fixtures/php/8.5/parser-structure.json` | `c19f354fb06ce0b8c37ed289b79414cb0da52ed70a918f0a4f2b1d4d3ae9c98a` | Exact preserved bytes verified |
| `tests/fixtures/php/8.5/valid/boundary-alt-closed-declaration.php` | `771b1bea1d51c5aea9ac58af634924a64af9f22e0cc2bc2feba5faeac05808d9` | Exact preserved bytes verified |
| `tests/fixtures/php/8.5/valid/phase3-destructure-keyed-list.php` | `590cb3c8bb848ae92eee92f1dbac83308ae53f7db0d0dfe8db2bb674996e5afb` | Exact preserved bytes verified |
| `tests/fixtures/php/8.5/valid/remaining-dangling-loop-bodies.php` | `03303182eeef6f8d73e8fdab97d01b8fe840fc5fdde1a3b82dd552623ab60240` | Exact preserved bytes verified |
| `tests/fixtures/php/8.5/valid/remaining-scanner-nested-nowdoc.php` | `47ac12c85425f3c288e75ac2ed254a0ad8dfd8829a6f284123dfa5b3a143cb37` | Exact preserved bytes verified |
| `tools/8.5/data/certification-policy.json` | `23f4a1281b8f6353c3ab6cc7604c807ca856db72a7c5a642f73f751e37f1d476` | Exact preserved bytes verified |
| `tools/8.5/data/phase6-policy.json` | `1c2c925420de49a5d3282a19343d3cab1de8353b6ed3f621e312b79003138d59` | Exact preserved bytes verified |

[Historical authentication](hybrid-evidence/historical-integrity.json) records
all 1,545 outcomes, both source roots, the old claim/classifications and source
lock reference. Missing or changed snapshot/supplement bytes produce incomplete
integrity. Only the eight reviewed paths may use the supplement. A caller cannot
provide a replacement expected hash, silently substitute current files for other
historical inputs, or use normalized text equality. No old tests were rerun.

## Current scope and independently generated observations

The verifier reconstructs required dependencies from the checked-in policy,
not the report's offered input map. All `src`, `tests`, `grammar`, `bin`, `tools`,
`schema` and installed `vendor` files are included, with per-file reasons and
hashes. This deliberately retains all shared test discovery and registered
grammar interactions. It includes manifest/configuration/Composer lock, current
policy and API documents, old 8.5 evidence, explicit consumed 8.6 documents,
literal document references from executable PHP and manifest metadata links.
The 36 cross-version boundary fixtures and shared-lexer attestation are included.

New unreferenced PHP 8.6 research prose is outside this bounded claim. Changes to
known executable inputs or newly added shared code/tests are detected. Dynamic
document dependencies require explicit policy updates; this is a reviewed input
contract, not a claimed complete static dependency analyzer. Unknown future
runtime surfaces must be reviewed before extending the claim. Output revisions
are excluded from input recursion but checked independently; symbolic-link
dependencies, unsafe paths and case-colliding input identities are rejected.

Generation selects existing PHP 8.5.10 and PHP 8.6 RC4 distributions, verifies
their reviewed hashes before executing them, and records binary/extension hashes,
version output, flags and lack of exact-pin provenance. It records repository
HEAD, dirty-tree status, UTC time, source/cache identities, dependency inventory,
actual argument arrays, command receipts, logs, test totals and paired boundary
observations. Input hashes before and after execution must agree. New evidence
uses `docs/8.5/compatibility/<revision>/`; an existing revision is never overwritten.
The host's PHP configuration and global Git settings are unchanged.

The current claim is deliberately bounded to 15 independently executed commands:
manifest validation, the full current suite, API/CLI, boundary and infrastructure
selections, expression freshness, ordinary 8.5 differential, both 8.5 scanner
profiles, positive/negative and Phase 6 evidence checks, source correspondence,
the complete RC4 lock check, and paired stage-specific boundary execution.
No historical test totals are copied into those current observations.

## Read-only verification and tamper model

`--check` reconstructs mandatory input membership, rehashes dependencies, checks
schema/claim identity, source/interpreter/cache observations, command identity,
exit codes, separate command and whole-report receipts, log digests and measured
PHPUnit totals. The whole-report receipt also detects valid-looking timestamp,
repository metadata and serialization tampering. It checks
the paired boundary artifact independently. Missing prerequisites and unsupported
claims fail closed. Historical integrity remains a separate check; current
compatibility can be observed even when a historical snapshot is unavailable.

The trust boundary includes reviewed tooling/policy and retained run artifacts.
Checksums are not signatures: coordinated replacement of all results, receipts
and their digests is not authenticated merely by internal consistency. For
release decisions retain independently reviewed/archived run artifacts. Neither
verifier repairs, writes, normalizes, generates fixtures nor changes status.

## PHP 8.6 integration and legacy-obligation mapping

`tools/8.6/certification.php` defaults to `--contract legacy`, preserving existing
inventory/check/claims/run/prepare behavior. Unknown contracts fail. Explicit
`--contract historical-plus-current-v1` uses the separate
[v2 inventory](certification-gates-v2.json): all original 36 obligations plus
`historical-artifact-integrity85` and `current-compatibility85`, both required.
Hybrid preparation refuses to overwrite old reports; generate the companion
separately. Hybrid check/claims/run verify both new prerequisites and the original
ledger checks; claims/run retain all original blocking findings.

**No substitution was made.** The Phase 7C.1 proposal allowed an explicitly
authorized successor mapping; this implementation takes the conservative
additive path because a bounded current regression record is not a complete
renewal of all legacy release evidence. The old `historical-certification-check`
retains its identifier, meaning and failed outcome. The opt-in contract therefore
has 38 obligations, not a relabeled 36-gate success. This is an explicit limitation
of release eligibility, not a hidden fallback or a claim that the old failure
was fixed. The following accounts for all 27 legacy obligations:

| Legacy obligations | Current evidence or retained requirement |
|---|---|
| Composer validation | Retained legacy release prerequisite; dependency identities recorded, no fresh Composer validation implied by compatibility. |
| Manifest schema; manifest regressions; consumer contract | Fresh manifest command, full-suite manifest regressions and API/CLI selection. |
| Expression generation; PHPUnit | Fresh check and full suite. |
| Grammar coverage; lexer coverage | Existing classified historical artifacts authenticated; related current regression tests execute. Standalone release coverage commands remain required for renewal. |
| Ordinary differential | Fresh PHP 8.5 corpus comparisons. |
| Explicit AST; systematic structure; declaration folding; Phase 6 matrices; scanner product | Historical artifacts authenticated; current regression cases execute. The complete standalone release matrices were not regenerated or substituted. |
| Phase 6 matrix freshness | Retained legacy prerequisite; Phase 6 aggregate freshness is separately rerun. |
| Both lexical oracle profiles | Both freshly executed. |
| Positive and negative report freshness | Both freshly checked. |
| Compiler source hashes; parser reconciliation | Normative source bytes independently checked; historical mapping artifacts authenticated. Full legacy reconciliation remains required for renewal. |
| Phase 6 evidence freshness | Freshly checked. |
| Interpolation binding; diagnostic predicates | Historical evidence authenticated; current full-suite coverage retained. Standalone release witnesses remain required for renewal. |
| Source correspondence | Fresh immutable-cache comparison check. |
| Final certification | Unchanged legacy checker rerun and fails legitimately; neither new claim replaces it. |
| Diff whitespace | Executed in final implementation validation. |

This preserves release strength while enabling useful independent current
compatibility evidence now. It does not close any language discrepancy or
authorize promotion. Replacing a legacy prerequisite remains a separate decision
requiring the missing complete current release evidence.

## Tests, validation and preservation

`HistoricalEvidenceContractTest` tests raw-byte mismatches, all eight individual
missing/tampered supplements, forbidden fallback and unsafe paths, and tampered
ledger rejection. `CompatibilityEvidenceTest` mutates grammar/specification,
fixtures, parser/lexer, source locks, boundary fixtures, the shared attestation,
mandatory membership, schemas, claims, command outcomes, measured counts and
logs. It verifies unrelated research exclusion, newly referenced document
inclusion, input-set additions/removals, required boundary output, non-writing
verification, and hybrid refusal with PHP86-013/014/015 unresolved. Existing
certification tests remain unchanged.

Final execution totals and preservation results are recorded below after the
independent current run. No certification is granted by this report.

<!-- validation:start -->
The [final current evidence](../8.5/compatibility/phase7c2-verified/evidence.json) records **15 passed commands**, **6,169 tests / 58,189 assertions**, and **3899 dependency identities**. No prerequisites are missing, and before/after input hashes agree.

The dedicated negative selection passed **22 tests / 168 assertions**. Paired boundaries passed **526 checks / 172 observations / zero failures**. Both scanner profiles, the ordinary 8.5 differential corpus, source/correspondence checks, manifest and API/CLI checks passed. Counts from overlapping selections are not summed.

[Independent verification](hybrid-evidence/verification.json) records current `--check` exit 0, hybrid claims/run exit 1, both legacy checks exit 1, and Git whitespace exit 0. All **34 monitored run/ledger files** remained unchanged during verification. Hybrid output separately reports historical integrity and current compatibility true while refusing certification for the retained legacy requirements and mandatory blockers.

The earlier `phase7c2` revision passed its commands but correctly recorded changed inputs while verifier strengthening was underway. The intermediate `phase7c2-final` revision initially verified, then became stale after whole-report receipt protection was added. Both checkpoints remain preserved; neither supplies the current claim. Only `phase7c2-verified` supplies the final independently verified observations.

[Final preservation and checks](hybrid-evidence/final-validation.json) cover 2336 pre-implementation files: **2332 unchanged**, with only the three authorized policy/API documents and the opt-in 8.6 runner changed. All 1440 protected grammar, specification, PHP 8.5 fixture/tool/evidence files remain byte-identical. Previous Phase 7A/B/C.1 reports, source locks, manifest and statuses remain unchanged. PHP lint, JSON integrity, local links and whitespace checks pass.
<!-- validation:end -->

## Remaining obligations and readiness

PHP86-013 discovery, PHP86-014 writable targets, PHP86-015 folding/optimized-call
order, CA86-001 discarded hooks, C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE remain
blocking. Neither source pin nor package status changed. The original historical
freshness failure remains visible. Exact-source executables remain unattested.

| Follow-up | Readiness |
|---|---|
| Historical byte authentication | READY for content-integrity use of the available authenticated bytes; portable archive retention and custody attestation remain separate limitations. |
| Independent exact-pin PHP 8.6 build preparation | READY WITH BOUNDED LIMITATIONS: provisioning and SDK/generator requirements from Phase 7B remain. |
| PHP 8.6 blocker remediation | READY WITH BOUNDED LIMITATIONS: focused source/evidence work may proceed under Gate B; no blocker waived. |
| Prerelease certification execution | NOT READY: original release prerequisites, source/executable provenance and mandatory language/coverage blockers remain. |

No subsequent build, grammar remediation, release reconciliation or promotion
phase has begun.
