# Historical certification evidence contract review

October 24, 2026. Phase 7C.1: investigation and implementation proposal only.

**Recommend an additive hybrid contract: immutable historical evidence plus
independently validated current compatibility evidence.** Keep schema 1 and all
existing command defaults unchanged. There is no backward-compatible way to make
the existing repository-wide freshness check pass on a different tree while
preserving its meaning. A successor gate contract must be explicitly versioned
and authorized. Nothing in this review grants certification or closes a gate.

## 1. Baselines, authority and published meaning

The review starts at repository HEAD
`4625873a6bac908b5cff4c677dba4ac10dd050e0`, with untracked Phase 7A and 7B work.
The [review baseline](historical-evidence-contract-review/baseline.json) records
the complete status and hashes of 2,311 existing files. This report and its new
supporting directory are additions, not replacements for previous evidence.

The applicable contracts are [AGENTS.md](../../AGENTS.md),
[versioning](../versioning.md), [conformance policy](../conformance-policy.md),
[source policy](../sources.md), and [public API compatibility](../api.md).
The [preparation report](certification-preparation.md),
[execution assessment](certification-execution.md), and
[remediation plan](certification-remediation.json) establish the unresolved
H85-FRESHNESS issue. The code index available in this session pointed to another
repository; this review uses this repository's files and Git objects directly.

The first final-evidence revision is
`db06a494d6e958e065371ee27cc301227314fd59`, containing 1,520 inputs. The retained
ledger was last revised at
`7d8b6e5fa92f82e7e8c122a9a989a22727f61c3e`, containing **1,545 inputs**.
These are different certification revisions. The
[eight-revision history](historical-evidence-contract-review/claim-history.json)
records intervening restructuring, grammar/API work and the PHP tooling migration.
All eight use evidence schema `1`; none embeds a repository commit field.
Git history supplies the candidate snapshot identity, which must be independently
checked against recorded bytes. A last-modifying commit alone is not proof of
complete reproducibility.

The retained artifact is `docs/8.5/final-evidence.json`, SHA-256
`cce919d8b848a9892c33d62b09d5b4d87d6f6a50bf551746cefce634fd47ac9c`.
Its published claim remains:

> Grammar-complete, with documented external contextual constraints; conformance
> evidence is bounded and uses PHP 8.5.10.

It records `grammar_complete: true`, `whole_source_validator_complete: false`
and `exhaustively_equivalent: false`. The
[completeness report](../8.5/completeness.md) retains C1 contextual enforcement,
C2 diagnostic predicates, C3 aggregate binding and C4 executable provenance
limitations. This review neither renews nor retroactively narrows that claim.
The historical report's earlier test/gate totals describe earlier runs; they must
not be substituted for the current 27-gate runner or Phase 7B totals.

The PHP 8.5 normative pin remains branch `PHP-8.5`, commit
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. The three
[locked source hashes](../../tools/8.5/source-lock.json) are:

| Source | SHA-256 |
|---|---|
| `zend_language_parser.y` | `f108f2c2ff4c98c68b6478e7fbdebc16d8afb8bbbc3ae6079e5b285b448dac0c` |
| `zend_language_scanner.l` | `8dfed84b0f6cabe73f004ea00c69614ea84542130a9b97e7699f801ef7ce7403` |
| `zend_compile.c` | `25190ee20f2660aa66577f5da71b43be63268e90fa7c0ecb2e3cf8f1e621b225` |

The seven-file pin/release comparison remains
`docs/8.5/source-correspondence.json`; distribution PHP 8.5.10 is not an attested
exact-pin executable. PHP 8.6 retains RC4 commit
`f23b4c222cebe33be93ee8ecaaa14390c5507a08` and its independent locks.

## 2. What schema 1 actually checks

`tools/8.5/certification.php:197-221` combines the explicit
`tools/8.5/data/certification-policy.json/input_paths` with **every file** under
`src`, `tests`, `grammar`, `bin` and `tools`, sorts/deduplicates the paths, and
hashes their raw checkout bytes with SHA-256. Directory enumeration includes
new files, not just paths recorded in the old ledger. It does not select only
PHP 8.5 files. Explicit dependencies include `.gitattributes`, Composer metadata,
the manifest, README, versioning/conformance policy, completeness wording and
the supporting Phase 3/5/6, diagnostic, binding and correspondence reports.
The exact evidence-document input list and hashes are retained in
[historical-baseline.json](historical-evidence-contract-review/historical-baseline.json).

`--check` reconstructs derived evidence and compares it to the existing report;
it is not merely a loop over old input hashes. `Support::emitText()` normalizes
report line endings for textual comparison, while `Support::hashes()` hashes
raw input bytes. Earlier matrix/source/coverage checks can fail before the final
comparison. Omitting a dependency from a replacement ledger would bypass part
of this contract unless a separate verifier derives mandatory membership.

Conformance policy lines 67-91 explicitly document broad final-input hashing,
review before regeneration, immutable source comparisons and byte preservation.
Versioning lines 117-123 separately require preserving the preceding grammar,
fixtures and evidence during an upgrade. Both are meaningful: preserve the old
record, and do not call it fresh for an expanded current tree.

The implementation matches the existing contract. It has no evidence of a
deliberately designed long-term dependency boundary between independent versions:
the all-directory loop predates PHP 8.6. Conversely, its documented scope cannot
now be treated as an accidental checksum and silently reduced. The defect is a
missing multi-version evidence distinction, not a broken SHA-256 comparison.

For the retained PHP runner, the established aggregate commands are
`php tools/8.5/certification.php --check` and `composer release:check`, or
`php tools/grammar-release.php --php <php85> --composer <composer>
--source-directory <sources> --source-cache <cache> --logs <new-directory>`.
The [historical runner copy](historical-evidence-contract-review/historical-release-runner.txt)
records the concrete 27-command mapping at the retained revision. Its required
checks include full PHPUnit, manifest/consumer compatibility, positive/negative
and differential evidence, coverage, scanner and both short-tag profiles,
AST/folding, compiler/parser source checks, diagnostics, binding, report
freshness, final certification, and whitespace. A future renewal must execute
the applicable complete obligations; refreshing final hashes is insufficient.

## 3. Exact classification of the freshness differences

The [per-path classification](historical-evidence-contract-review/input-classification.json)
accounts for **all 497 Phase 7B differences**, with old/new hashes for changed
files, hashes for additions, category and impact. No path is unclassified.

| Category | Changed | Added | Representative paths and relevance |
|---|---:|---:|---|
| PHP 8.5 grammar/specification | 0 | 0 | Direct normative dependencies remain unchanged. |
| PHP 8.5 fixtures | 0 | 0 | Existing acceptance and contextual witnesses remain unchanged. |
| PHP 8.5 certification tooling | 0 | 0 | Existing generator, policies and locks remain unchanged. |
| Shared implementation and regression tooling | 8 | 0 | `PhpVersion.php`, `VersionBoundaryCase.php`, `VersionBoundaryRunner.php`, five tests; potentially material to current PHP 8.5 behavior. |
| Shared policy/governance | 1 | 0 | `docs/versioning.md`; preservation, readiness and release requirements. |
| Shared consumer/discovery contract | 3 | 0 | `README.md`, `bin/php-grammar`, `php-grammar.json`; claims, metadata and version selection. |
| PHP 8.6 package artifacts | 0 | 3 | Standalone EBNF, specification and `changes.md`. |
| PHP 8.6 fixtures/metadata | 0 | 410 | `tests/fixtures/php/8.6/`; acceptance, stage, coverage and source witnesses. |
| Shared cross-version boundary fixtures | 0 | 36 | `tests/fixtures/version-boundaries/`; 16 structural, 11 lint and nine declaration-link records. These explicitly concern both versions. |
| PHP 8.6 test classes | 0 | 9 | `Certification86Test.php`, eight `Php86*Test.php` classes; some exercise shared APIs, preservation or paired versions. |
| PHP 8.6 tooling and locks | 0 | 25 | `tools/8.6/certification.php`, `integration-check.php`, source locks and audit tools. |
| PHP 8.6 support in shared tooling directory | 0 | 2 | `tools/lib/Certification86.php`, `ChangeTraceability.php`; 8.6 callers, with shared validation dependencies. |
| Unrelated repository additions | 0 | 0 | No reported path is dismissed as unrelated. |
| **Total** | **12** | **485** | 1,545 historical inputs versus 2,030 at the Phase 7B execution baseline. |

The 446 fixture/metadata additions reported in Phase 7B therefore divide into
410 version-local files and **36 shared boundary files**. Grouping all 446 as
irrelevant to PHP 8.5 would be incorrect. The two shared-directory classes are
8.6 certification/traceability implementations, not replacements for
`Support.php`; their placement alone neither proves nor disproves relevance.

The [reviewed twelve-file diff](historical-evidence-contract-review/changed-inputs.diff)
supports these individual dispositions:

| Changed file | Material question and required current evidence |
|---|---|
| `README.md` | Adds draft-selection and boundary-stage wording. Verify stable 8.5 recommendations and bounded claims remain accurate. |
| `bin/php-grammar` | Adds profile/pin/metadata output. Preserve prior fields, exit codes and explicit 8.5 navigation. |
| `docs/versioning.md` | Adds Gate A/B, standalone upgrade, preservation and final-release rules. Review policy meaning, not only grammar bytes. |
| `php-grammar.json` | Adds draft 8.6 registration; the 8.5 entry remains. Verify schema, paths, status and highest-version behavior. |
| `src/Php/Conformance/VersionBoundaryCase.php` | Adds stage/finding metadata and validation with structural defaults. Test old constructor/data behavior and invalid stages. |
| `src/Php/Conformance/VersionBoundaryRunner.php` | Refuses contextual records in structural evaluation. Verify old structural behavior and stage separation. |
| `src/Php/Lexing/PhpVersion.php` | Extracts common keywords and adds 8.6 configuration. Test 8.5 identity, token behavior and both short-tag profiles. |
| `tests/Php/Conformance/VersionBoundaryTest.php` | Retains isolated single-version/empty-directory cases and adds multi-version/stage cases. Check no original obligation was lost. |
| `tests/Php/Lexing/Php85ScannerAuditTest.php` | Replaces direct ledger equality with exact reviewed shared-lexer delta checks. Include `docs/8.6/shared-lexer-regression.json` as a transitive dependency despite its directory. |
| `tests/Repository/ConsumerCliTest.php` | Adds draft navigation assertions; keep existing 8.5 CLI regressions. |
| `tests/Repository/ConsumerWorkflowTest.php` | Adjusts available/latest versions and diagnostics while keeping stable 8.5 loading. Verify documented numeric-latest semantics. |
| `tests/Repository/RepositoryManifestTest.php` | Expects both versions. Retain legacy manifest parsing and 8.5-specific metadata assertions. |

These are plausible compatible integration changes, not a new certification
decision. Relevant tests passing supplies bounded support, not a reason to remove
their dependencies. The shared-lexer attestation is narrowly limited to one
reviewed input; it is not authority for an aggregate freshness waiver.

At the **current Phase 7C.1 tree**, the unchanged 8.5 algorithm finds 2,031 inputs,
12 changed and **486 added**. The additional path is Phase 7B's
`tools/8.6/certification-assessment.php`; see
[current-input-delta.json](historical-evidence-contract-review/current-input-delta.json).
This does not revise the historical 12/485 observation. The new review documents
are outside that old generator's enumerated directories and explicit list.
The 8.6 checker has a wider docs-inclusive input set and correctly reports its
working-tree preparation ledger stale after assessment additions.

## 4. Additional historical-byte reproducibility finding

A raw Git-blob comparison differs from checkout hashes for 602 of the 1,545
inputs. Most are expected CRLF transformations governed by `.gitattributes`.
A new isolated index and checkout of the retained commit reproduced **1,537**
recorded input hashes, leaving **eight mismatches**:

1. `src/Ebnf/Validation/GrammarValidator.php`
2. `tests/fixtures/php/8.5/parser-structure.json`
3. `tests/fixtures/php/8.5/valid/boundary-alt-closed-declaration.php`
4. `tests/fixtures/php/8.5/valid/phase3-destructure-keyed-list.php`
5. `tests/fixtures/php/8.5/valid/remaining-dangling-loop-bodies.php`
6. `tests/fixtures/php/8.5/valid/remaining-scanner-nested-nowdoc.php`
7. `tools/8.5/data/certification-policy.json`
8. `tools/8.5/data/phase6-policy.json`

[Checkout results](historical-evidence-contract-review/historical-checkout.json)
and the [byte comparison](historical-evidence-contract-review/historical-byte-review.json)
record exact expected/observed hashes, lengths and CRLF counts. All eight
preserved working-tree files match the old ledger. All eight checkout files
equal those copies after newline normalization, but **normalization is diagnostic
only**: scanner fixture byte hashes must still match exactly. The historical
ledger itself reproduces exactly after its declared checkout conversion.

This finding does not explain away the twelve current-tree changes, and it does
not prove a historical grammar defect. It establishes that the commit alone is
insufficient to reconstruct every certified byte in the available checkout.
Assign proposal dependency **H85-BYTES**: authenticate an archived checkout or a
separate eight-file byte supplement bound to the unchanged ledger and original
commit. Existing copies and recorded hashes supply candidates, not an invented
claim that a full historical release rerun has passed. If authentication cannot
be established, historical reproducibility remains blocked. Do not normalize
fixtures, edit old hashes, or claim that Git blobs alone satisfy the ledger.

## 5. Comparison of dependency models

| Dimension | A: repository-wide refresh | B: version-scoped freshness | C: immutable historical baseline | D: historical plus current compatibility |
|---|---|---|---|---|
| Correctness | Correct for a current whole-tree claim; unchanged inputs are not enough when the input set grows. | Correct only with complete shared/transitive dependencies and explicit exclusions. | Correct for past claims only; cannot establish current behavior. | Separates past identity from current behavior and retains both obligations. |
| Reproducibility | Rebuild entire evidence chain for each covered tree change. | Rebuild version and shared dependencies; requires independently derived membership. | Original tools, bytes, policies and environment; H85-BYTES must be resolved. | Historical reproduction plus independent current run, each with its own identity. |
| Backward compatibility | Existing CLI/schema behavior retained, but renewal must coexist with preserved historical artifacts. | Changing schema-1 meaning in place is incompatible. New contract required. | Additive historical verifier can coexist; cannot silently replace current check. | Additive companion and opt-in successor gate preserve all legacy defaults. |
| Integrity | Broad addition/removal/change detection; costly unrelated invalidations. | Omission and transitive-dependency risks unless membership is externally checked. | Anchored ledger, full snapshot and source locks; no current compatibility assurance alone. | Both records cross-linked; omissions, stale shared files and scope substitution rejected. |
| Schema | No input-scope change; alternate output/history identity still needed for renewal. | New scope/closure/claim fields and schema identity. | External historical envelope; old record untouched. | Distinct evidence kind/schema and dependency-policy identity; schema 1 remains readable. |
| Tamper detection | Hashes detect divergence from a trusted record, not replacement of record and inputs together. | Trust root plus mandatory membership and unknown-file handling required. | Trusted commit/archive/ledger digest; hash equality alone is not authentication. | Same trust root plus independently computed current set and reviewed policy. |
| Maintenance | Smallest conceptual change, highest recurring recertification cost. | Dependency audit and classifications on every relevant addition. | Low historical churn; separate current validation still needed. | More initial design, bounded renewal when relevant dependencies change. |
| Future versions | Every covered new version invalidates old current-tree freshness. | Independent versions possible; shared changes still invalidate affected records. | Historical claims survive unrelated additions; current claims remain separate. | Best fit for independently maintained versions with shared implementation. |

A is valid if maintainers require whole-tree recertification and separately
authorize preservation-compatible renewal. It does not meet the requested
avoidance of unnecessary repository-wide renewal. B is not safe as a directory
filter: the scanner attestation and 36 boundary fixtures demonstrate why.
C is necessary but insufficient as the sole new release prerequisite. **D is
preferred**, using B's explicit dependency accounting for the companion record
and C's immutable historical identity. It requires no grammar, lexer, API or
source-pin change.

## 6. Proposed semantics and minimum contract

These definitions are proposals for a successor policy, not edits to existing
claims or checker behavior:

| Term | Meaning |
|---|---|
| Historical certification | The bounded decision recorded for a specific evidence revision, source pin, byte snapshot and profile. It is not a current-tree certificate. |
| Historical evidence freshness | Exact reconstruction/validation against that historical input set and policy. Under legacy `--check`, the target remains the supplied current tree; callers must not change that interpretation silently. |
| Current repository compatibility | A new, bounded verification of the selected version using current shared code, tests, policy and discovery behavior. It does not assert language completeness beyond the historical claim. |
| Source-lock integrity | Complete expected lock identity plus actual source bytes matching every required hash. Matching a version string or self-edited lock is insufficient. |
| Shared tooling change | A change to any transitive generator, adapter, matcher, validator, bootstrap, test helper, configuration or dependency affecting the result. It invalidates the affected companion until reviewed and rerun. |
| Version regression evidence | Named current matrices with expected stages, commands, oracle identities and observed outcomes. Test selection and discovery are themselves dependencies. |
| Certification renewal | A new evidence revision and decision after all applicable obligations; never merely updating the old hash map. |
| Release dependency | An explicit required claim/evidence kind, contract version, scope and verdict. A historical pass cannot substitute for a current pass. |

A repository change does not erase a past run or prove its grammar wrong. It
makes the old evidence stale **for the current repository**. Newly discovered
in-scope defects may separately require correction/retraction under existing
policy. A broader/current certification claim requires a new decision.

The minimal hybrid must require:

1. A trusted historical reference: retained commit, exact ledger SHA-256,
   original schema/claim/profile, complete raw input set, source locks and
   authenticated checkout-byte supplements where necessary. Reject any mismatch.
2. A separate `current-compatibility` record with its own schema identifier,
   version, claim scope, baseline reference, repository identity, dirty-tree
   file hashes when applicable, dependency-policy hash, commands, profiles,
   executable/configuration identities, logs, outcomes and limitations.
3. A verifier-owned mandatory dependency policy, not a list trusted from the
   supplied report. Include the selected grammar/specification/fixtures/evidence,
   source locks, relevant boundary cases, shared `src`/bootstrap/helpers,
   validator and test discovery/configuration, Composer lock/autoload/runtime
   dependencies, manifest/CLI/schema, and applicable policy/claim documents.
   Explicitly include the shared-lexer attestation outside `docs/8.5/`.
4. Inventory additions/deletions as well as changed bytes. Initially treat all
   shared code and shared tests as relevant; narrow only through reviewed
   dependency evidence. Unknown paths in executable/discovery surfaces fail
   closed pending classification. Pure version-local exclusions need a recorded
   reason and no transitive/cross-version use. No unrestricted `8.6/` exclusion.
5. Separate byte identity, compatibility verdict and certification eligibility.
   Hash before and after execution; verify child outputs independently. Retain
   old stale results as old results. An unchanged scope fingerprint can survive
   an unrelated addition without asserting that all files at a new HEAD were
   tested; a claim about that exact new repository revision needs its own run.
6. Stable output roots per evidence revision. No write into old report paths,
   no output self-hashing cycle, no arbitrary output-directory exclusion that
   permits executable dependencies to escape hashing. Validate output membership.

SHA-256 establishes integrity relative to a trusted reference. It does not make
a ledger authentic when an attacker can replace both the data and its hash map.
Review-controlled policy and pinned Git/archive identities are mandatory;
cryptographic signing may be added later but is not a substitute for membership
and semantic checks.

## 7. Precise implementation proposal, not implemented

| File or proposed new path | Minimum separately authorized change |
|---|---|
| `docs/conformance-policy.md` | Define historical versus current compatibility, trusted references, fail-closed scope review and renewal. Preserve the schema-1/27-gate contract section and state successor applicability explicitly. |
| `docs/versioning.md` | Describe additive coexistence and claim/version migration; preserve standalone grammars, immutable previous evidence and Gate B. |
| `docs/api.md` | Document the new semi-public evidence kind and command contract; leave manifest schema 1.0, consumer APIs and old defaults unchanged. |
| New `tools/evidence/historical-check.php` | PHP read-only verifier for an authenticated historical snapshot and ledger. Resolve H85-BYTES first; no regeneration or normalization shortcut. |
| New `tools/evidence/compatibility.php` | Generate into a new output root and independently `--check` the companion. Reuse existing validators; derive mandatory inputs from reviewed policy. Never patch old results. |
| New `tools/evidence/compatibility-policy.json` | Versioned dependency classes, mandatory memberships, command/claim requirements and reviewed exclusion rules, including transitive external-directory evidence. |
| New `schema/certification-compatibility.schema.json` | A distinct evidence kind and schema identity; require historical reference, dependency policy, input/output digests, command outcomes, source/oracle/profile bounds and explicit compatibility-only claim. Unknown incompatible schema must fail. |
| New `docs/8.5/compatibility/<revision>/` | New companion and logs only after implementation and an independently checked run. Preserve every existing 8.5 artifact. |
| `tools/lib/Certification86.php`, `tools/8.6/certification.php` | Add an explicit successor contract selection with historical-integrity AND current-compatibility prerequisites. Keep legacy `--check`, `--claims`, `--run`, inventory and preparation interpretation intact. Do not reuse a schema-1 success flag for a new meaning. |
| `docs/8.6/certification-gates.json` | Keep the legacy inventory intact; add a separate versioned successor inventory, for example `certification-gates-v2.json`, with an explicit mapping of the old historical obligation to its two required successor checks. |
| `tests/Certification86Test.php` plus new `tests/HistoricalEvidenceContractTest.php` and `tests/CompatibilityEvidenceTest.php` | Preserve old negatives/default behavior; add isolated schema, membership, historical-byte, compatibility and claim-substitution tests below. |
| `tests/Php/Lexing/Php85ScannerAuditTest.php` and `docs/8.6/shared-lexer-regression.json` | No immediate edit. Include their existing attestation dependency and preserve its exact one-file scope. Generalization is optional later work, requiring separate review. |
| `tools/8.5/certification.php`, `tools/8.5/data/certification-policy.json`, `tools/grammar-release.php`, `docs/8.5/final-evidence.json` | **No immediate modification.** Retain legacy current-tree behavior and its nonzero freshness result. Historical schema remains available and interpretable. |

No source-lock contents or PHP pins need changing. The new verifier must anchor
lock digests and required membership to reviewed identities, then check actual
sources. No exact-build claim is introduced. The 8.6 provenance and other nine
blocked obligations remain independent.

For the first companion, map **all 27 legacy obligations** to historical
verification or newly executed current equivalents. Run all unaffected current
gates, full current PHPUnit, both-version boundary/differential checks, consumer
API/CLI and both 8.5 scanner profiles. Replace neither the old aggregate nor the
scanner ledger comparison with an unchecked success: demonstrate original
historical integrity and independently validate each current derived result,
including the existing reviewed lexer delta. Capture fresh reports in isolated
revision-specific output locations. The 2,994-test review selection and Phase 7B
results do not constitute this future companion run.

The successor prerequisite intentionally changes release dependency meaning.
It needs an explicit policy decision and migration notes; a silent drop-in
replacement is **not backward-compatible**. Old readers/commands continue to
see the original contract and stale result. New callers must select the new
contract and receive independently checked results, not a relabeled old gate.
Making the successor the default is optional later migration and requires a
tooling compatibility/release decision. Resolving the unrelated Phase 7A
claim-mode and exact-attestation limitations is also separate work.

## 8. Required negative and positive validation

All proposed tests operate on isolated copies and must check nonzero exit,
specific failure category and no writes during validation.

| Mutation or scenario | Required result |
|---|---|
| Change 8.5 EBNF or specification without evidence | Historical snapshot rejects if altered there; current companion rejects changed normative inputs and cannot renew the historical claim. |
| Modify/delete an 8.5 fixture or add an unrecorded fixture | Current membership/hash validation fails; old immutable snapshot remains separately checkable. |
| Modify shared matcher, lexer, validator, bootstrap, test helper or test selection | Companion fails even when the file is outside `8.5/`; rerun and review required. |
| Alter shared-lexer attestation or any of the 36 boundary fixtures | Companion fails; stage changes cannot be presented as structural validation. |
| Alter source bytes, pin, lock hash, or omit a required lock entry | Reject mismatch against the independently anchored source identity and membership. |
| Tamper with historical ledger, input or byte supplement | Historical integrity fails; changing a companion's reference hash must not redefine the trusted baseline. |
| Normalize the eight checkout mismatches | Reject unless raw hashes reproduce the authenticated original bytes; normalized text equality cannot pass. |
| Omit a mandatory input and recompute all supplied hashes | Reject against verifier-derived mandatory membership, not the supplied list. |
| Add an unclassified shared executable file or delete/rename a dependency | Reject or require explicit dependency classification; no silent ignore. |
| Supply unknown schema, incompatible contract or wrong evidence kind | Fail closed; never infer compatibility from the presence of a `passed` field. |
| Present historical certification as current, omit the companion, or substitute another version/profile | Reject claim/dependency mismatch. |
| Forge passing command totals, omit/fail a required gate, alter logs, or change input during generation | Reject independently checked evidence and before/after digests. |
| Try path traversal, duplicate/case-colliding paths, symlink escape or arbitrary output exclusions | Reject ambiguous/out-of-root dependencies and unverifiable output membership. |
| Add a demonstrably unrelated PHP 8.6-only document/fixture | Historical snapshot result remains unchanged; a properly scoped companion remains valid only if approved dependency discovery confirms no transitive/cross-version effect. Legacy current-tree check still follows its old broad rules. |
| Change manifest registration while adding a version | Historical identity remains unchanged; current compatibility becomes stale because discovery behavior changed. |
| Run unchanged legacy commands/read schema-1 evidence | Same parsing, output semantics and exit behavior as before migration; no forced new fields or suppressed old failure. |

Test the distinction between positive validation of historical identity and
negative validation of current freshness explicitly. A new compatibility pass
must never promote PHP 8.6, erase C1-C4, or claim exact-executable equivalence.

## 9. Observed validation and preservation

[validation.json](historical-evidence-contract-review/validation.json) records
actual argument arrays, working directories, log paths, hashes and exit codes.
Commands used the explicit existing `.audit/php85/php.exe` with `-n`; PHPUnit
also loaded its local mbstring extension. No host defaults changed.

| Executed read-only check | Observed result |
|---|---|
| `tools/8.5/certification.php --check --source-cache <existing cache>` | Exit 1: stale `docs/8.5/final-evidence.json`, as required by current inputs. |
| `tools/8.6/certification.php --check` in the current tree | Exit 1: input set/hash freshness mismatch after assessment additions. Existing gate outcomes are not regenerated. |
| `tools/8.6/certification-assessment.php --check` | Exit 0: preserved Phase 7B evidence, accounting and exported run hashes verify. |
| `tools/8.5/compiler-boundaries.php <existing source directory> --check` | Exit 0: three source hashes; 244 sites, 14 parser-action sites and 87 functions. |
| `tools/8.6/certification-gate.php --gate source` | Exit 0: complete RC4 certification lock and 235 source/test hashes verify. |
| `tools/validate-manifest.php` | Exit 0. |
| `tools/8.6/certification-gate.php --gate freshness` | Exit 0 for its bounded historical audit inputs. This is distinct from complete current-tree final-evidence freshness. |
| `tools/8.6/certification-gate.php --gate preservation` | Exit 0. |
| PHPUnit certification, manifest, consumer, boundary, package consistency and 8.5 scanner selection | **2,994 tests / 41,252 assertions passed**, including existing certification negative tests. |

No complete new 27- or 36-gate certification run, exact-pin build, historical
evidence generation, or new companion generation is claimed. Phase 7B's
25 passed / one failed / ten blocked remains its recorded execution result.
Read-only integrity, JSON, local-link, whitespace and final baseline comparisons
are recorded in [review-validation.json](historical-evidence-contract-review/review-validation.json).
All 2,311 pre-review files must remain byte-identical, including both grammars
and specifications, all previous evidence, schemas, locks, manifest/statuses,
release runners, gate inventories and published claim documents. The temporary
historical checkout used an isolated index; the primary index was not changed.

## 10. Decisions and next-phase readiness

**READY WITH BOUNDED LIMITATIONS for a separately authorized implementation
phase.** The proposal is concrete; it is not an approved successor policy.
Before a successor gate can pass, maintainers must explicitly settle:

1. Adoption of the hybrid historical/current distinction, its bounded claim
   wording and opt-in gate-contract version. Existing schema-1 behavior remains.
2. H85-BYTES authentication and archival treatment of the eight original input
   byte sequences. Historical reconstruction must not be assumed complete.
3. The mandatory shared/transitive dependency policy, unknown-file handling and
   per-path exclusion review. Boundary fixtures and the external-directory
   scanner attestation must be included.
4. The complete initial 27-obligation mapping, independent current validation
   requirements and trusted evidence-reference ownership.

Optional later work includes signed attestations, a generalized dependency
graph, reusable CI caches and eventual default-command migration. None is a
prerequisite for writing the minimal additive verifier, and none authorizes
weakening required inputs or claims.

H85-FRESHNESS remains unresolved under today's gate. PHP 8.6 remains draft;
prerelease certification, promotion and final certification remain NOT READY.
This phase ends with assessment and proposal. Implementation, historical
renewal and final-release reconciliation have not begun.
