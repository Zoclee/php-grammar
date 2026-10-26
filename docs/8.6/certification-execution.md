# PHP 8.6 certification execution and blocker assessment

October 24, 2026. **Phase 7B assessment; PHP 8.6 remains draft and uncertified.**
This report distinguishes executed checks from evaluated blocking obligations.
Passing finite matrices does not establish grammar completeness, candidate
readiness, exact-executable equivalence, or compatibility with a final release.
No finding is waived and no normative artifact, public API, source pin, profile,
manifest status, historical report, or existing gate implementation is changed.

## 1. Repository and execution baseline

Repository HEAD is `4625873a6bac908b5cff4c677dba4ac10dd050e0`. The tree is **not
clean**: the previous Phase 7A additions are untracked. The
[baseline](certification-execution-baseline.json) records the complete Git status
and 2,227 original file hashes, including those additions. The original Phase 7A
`--check` passed before this assessment added any files. Its recorded results
were 25 passed, one failed and ten blocked gates; 6,157 tests and 58,061 assertions.

| Baseline | Immutable identity |
|---|---|
| PHP 8.5 source | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| PHP 8.6 RC4 source | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| PHP 8.5 EBNF | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| PHP 8.5 specification | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |
| PHP 8.6 EBNF | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| PHP 8.6 specification | `45688b7d36ff16d823360f9072f43aa8e52c4dbd9189c890a6fdeef592bca521` |

The [assessment driver](../../tools/8.6/certification-assessment.php) copies the
recorded baseline and installed Composer dependencies into the isolated
`.audit/phase7b-certification` directory. It verifies copied baseline bytes and
records dependency hashes. Oracle binaries and source caches are selected by
explicit absolute paths to the existing local distributions and pinned trees.
The host's default PHP and configuration are not changed. This copy includes the
actual untracked Phase 7A implementation; a checkout of HEAD alone would omit it.

The existing runner has fixed child-output locations. Executing it directly in
the working tree would overwrite Phase 7A reports. The isolated copy allows
unchanged execution and preserves every original report. Fresh reports and logs
are exported byte-for-byte under `certification-execution/run/`; their internal
paths refer to the isolated execution root, not to the older working-tree reports.
The exported generator ledger still identifies its implementation as phase 7A.
That label is retained rather than edited into a claim of certification. This
separate phase 7B assessment records what actually ran.

## 2. Actual gate execution and accounting

[Execution metadata](certification-execution/execution.json) and
[exact commands](certification-execution/commands.json) record the working
directory, argument arrays, exit codes, logs and hashes. The unchanged runner's
`--prepare` executes **26 gate commands**. The other **ten obligations are
evaluated as blocked without executing a closure validator**. They are not ten
executed failures, and existing evidence files are not counted as executions.
Some gates deliberately rerun the same integration, audit or traceability
command for different obligations; the 36 labels do not represent 36 independent
test suites. Test totals from overlapping commands must not be added together.

[The gate assessment](certification-execution/gate-assessment.json) retains all
36 identifiers, exact actual results, commands or explicit non-execution,
purposes, required evidence, hashes, failure categories, remediation dependencies
and rerun requirements. Every failed or blocked gate maps individually into
[the remediation plan](certification-remediation.json).

<!-- gates:start -->
| Gate | Purpose | Actual result | Execution evidence |
|---|---|---|---|
| `manifest-schema` | Validate schema, metadata paths, RC4 pin and draft/stable status. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/manifest-schema.log) |
| `manifest-regressions` | Exercise valid and invalid manifest contracts. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/manifest-regressions.log) |
| `standalone-structure-parity` | Load only 8.6; check EBNF structure/references, parity, version selection and CLI. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/standalone-structure-parity.log) |
| `positive-negative-fixtures` | Run registered positive and structural-negative fixture providers. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/positive-negative-fixtures.log) |
| `ordinary-differential` | Rerun every reviewed ordinary 8.6 positive, negative, contextual and discrepancy fixture through structure and RC4 lint. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/ordinary-differential.log) |
| `lexer-profiles` | Compare version-specific token boundaries and both short-tag profiles with both distributions. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/lexer-profiles.log) |
| `pfa-differential` | Validate PFA positives, structural/contextual negatives and paired lint with pinned hashes. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/pfa-differential.log) |
| `contextual-differential` | Check compiler, declaration-link and controlled-runtime expectations at their declared stage. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/contextual-differential.log) |
| `diagnostic-differential` | Separate warning presence/stage from rejection in both distributions. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/diagnostic-differential.log) |
| `version-boundaries` | Discover structural/lint/link boundaries and verify paired outcomes and finding identity. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/version-boundaries.log) |
| `coverage-measurement` | Measure actual 8.6 production/alternative witnesses and compare source-backed lexical cases. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/coverage-measurement.log) |
| `parser-scanner-correspondence` | Recompute the 176-production/623-alternative and 190-header inventories, source locks and mappings. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/parser-scanner-correspondence.log) |
| `compiler-contextual-reconciliation` | Verify bounded semantic groups, inventories, source references and preserved uncertainties. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/compiler-contextual-reconciliation.log) |
| `rfc-implementation-traceability` | Recompute all RFC candidate dispositions at the selected source pin; no RFC-only permission. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/rfc-implementation-traceability.log) |
| `production-change-traceability` | Reject unexplained added/removed/modified productions, lexical/prose drift and stale witnesses. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/production-change-traceability.log) |
| `changes-document-completeness` | Verify all ten sections, finding IDs, source pins and regression references. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/changes-document-completeness.log) |
| `historical-preservation` | Compare every pre-7A project file, including all 8.5 evidence/fixtures/locks and both 8.6 specifications. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/historical-preservation.log) |
| `public-api-cli` | Run consumer workflow, package contract and navigation CLI regressions. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/public-api-cli.log) |
| `source-lock-integrity` | Verify independent RC4 lock completeness and every required source SHA-256. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/source-lock-integrity.log) |
| `evidence-freshness` | Verify historical audit inputs; final --check also compares complete current input/output sets. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/evidence-freshness.log) |
| `blocker-dispositions` | Require all seven complete, unchanged Phase 6 blocking dispositions. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/blocker-dispositions.log) |
| `documentation-json` | Validate new JSON, local documentation links, status wording and whitespace. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/documentation-json.log) |
| `full-regression` | Run the entire repository PHPUnit suite without cached results. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/full-regression.log) |
| `infrastructure-negatives` | Exercise isolated missing/stale/invalid inputs, failed gates and false certification claims. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/infrastructure-negatives.log) |
| `historical-certification-check` | Run unmodified 8.5 certification --check; record pre-existing freshness failures, never rewrite history. | **failed** | [command log](certification-execution/run/docs/8.6/certification/logs/historical-certification-check.log) |
| `composer-validation` | Validate Composer metadata strictly. | **passed** | [command log](certification-execution/run/docs/8.6/certification/logs/composer-validation.log) |
| `production-coverage-closure` | Disposition every uncovered production with independent evidence; unresolved meaningful gaps block. | **blocked** | Not executed; blocked obligation |
| `alternative-coverage-closure` | Disposition every uncovered alternative, including context-only from; percentages do not waive gaps. | **blocked** | Not executed; blocked obligation |
| `scanner-state-closure` | Supply independent RC4 scanner state/helper/error products; header equality is insufficient. | **blocked** | Not executed; blocked obligation |
| `ast-binding` | Supply independent RC4 operand/AST and interpolation binding evidence, including retained ambiguities. | **blocked** | Not executed; blocked obligation |
| `folding-order` | Resolve optimized-call traversal and independently reconcile live/retained/discarded validation order. | **blocked** | Not executed; blocked obligation |
| `discovery-closure` | Resolve outstanding syntax-relevant compiler/AST/linking/optimizer discovery records. | **blocked** | Not executed; blocked obligation |
| `writable-target-validity` | Establish intended writable-target validity and compiler/VM consistency. | **blocked** | Not executed; blocked obligation |
| `contextual-enforcement-scope` | Adjudicate incomplete contextual enforcement and predicate-level evidence for the bounded claim. | **blocked** | Not executed; blocked obligation |
| `discarded-hook-disposition` | Independently adjudicate the inherited get/set-only discrepancy; do not inherit an 8.5 conclusion. | **blocked** | Not executed; blocked obligation |
| `exact-pin-executable` | Require reviewed source/build/generated-file/configuration/binary provenance; a version string is insufficient. | **blocked** | Not executed; blocked obligation |
<!-- gates:end -->

`--check`, `--claims` and `--run` are separately invoked after generation.
`--check` validates freshness and report consistency; it is not a certificate.
`--claims` evaluates eligibility and refuses it. `--run` also refuses before any
full gate execution: its current implementation is a preflight, not an alternate
full orchestrator. The Phase 7B request authorizes this assessment, but the code
still unconditionally emits its Phase 7A authorization message. That message is
an implementation limitation, not a claim that the user failed to authorize the
present work. Mandatory substantive blockers independently prevent success.

## 3. PHP 8.5 historical freshness investigation

The historical final-evidence file was last committed at
`7d8b6e5fa92f82e7e8c122a9a989a22727f61c3e`. The unmodified certification checker
reports `Stale certification artifact: docs/8.5/final-evidence.json`.
The [digest comparison](certification-execution/historical-freshness.json)
reconstructs the input-set algorithm in
`tools/8.5/certification.php:197-220` without invoking its generation mode:

| Comparison at the Phase 7B baseline | Count |
|---|---:|
| Recorded historical inputs | 1,545 |
| Inputs required by the same generator on the baseline tree | 2,030 |
| Existing paths with changed SHA-256 | 12 |
| Additional paths included by directory enumeration | 485 |
| Missing previously recorded paths | 0 |
| Protected PHP 8.5 files with changes | 0 of 1,437 |

The twelve changed paths are:

| Path | Relevance to the historical contract |
|---|---|
| `README.md` | Consumer/default/draft claim documentation |
| `bin/php-grammar` | Shared navigation CLI and metadata exposure |
| `docs/versioning.md` | Governance, readiness and evidence requirements |
| `php-grammar.json` | Shared discovery and version metadata |
| `src/Php/Conformance/VersionBoundaryCase.php` | Boundary-stage metadata |
| `src/Php/Conformance/VersionBoundaryRunner.php` | Structural versus contextual boundary handling |
| `src/Php/Lexing/PhpVersion.php` | Shared version configuration |
| `tests/Php/Conformance/VersionBoundaryTest.php` | Shared boundary regressions |
| `tests/Php/Lexing/Php85ScannerAuditTest.php` | Reviewed shared-lexer freshness attestation |
| `tests/Repository/ConsumerCliTest.php` | CLI regression contract |
| `tests/Repository/ConsumerWorkflowTest.php` | Consumer regression contract |
| `tests/Repository/RepositoryManifestTest.php` | Manifest/discovery regression contract |

The machine-readable comparison retains **both full hashes for each changed
path**, and the path/hash for each of the 485 additions. The additions comprise
three 8.6 grammar-package files, nine test classes, 446 fixture/metadata files,
25 files under tools/8.6 and two shared tooling files. These are causes of input
set inequality, not 485 newly discovered PHP 8.5 defects.

The [conformance policy](../conformance-policy.md#regeneration-and-freshness)
explicitly hashes grammar, source, tests, CLI and report/policy inputs. Therefore
the mismatch is a **legitimate current-tree freshness failure**. Governance edits
legitimately invalidate a claim that the old certificate describes this exact
current tree, even when they do not change EBNF acceptance. They do not erase
the historical execution results or prove that the immutable 8.5 grammar became
incorrect. This is not merely a governance-document mismatch: shared executable
code, manifests, regression tests and the enumerated file set also changed.

The checker follows its documented whole-repository contract; no faulty hash
comparison was found. The architectural limitation is using that contract as
immutable, independently maintained per-version evidence: adding a new version
necessarily invalidates the older aggregate. The 8.6 eligibility checker then
treats that failed historical check as mandatory. This is an explicit
cross-version lifecycle dependency, not an independent 8.6 syntax finding.

Three additional **read-only** legacy checks were run in the snapshot: positive
coverage report freshness, Phase 6 evidence freshness and seven-file source
correspondence. Their [commands/results](certification-execution/supporting-checks.json)
are separate from the 36-gate denominator. They do not replace the complete
27-gate historical release suite or prove that refreshing only the final digest
would make every historical gate pass. No historical evidence generation command
was run and no 8.5 report was refreshed.

**Proposed resolution, not implemented:** separately authorize an 8.5 evidence
review. Preserve the original ledger and its commit, review the twelve shared
changes and current dependency graph, run all required legacy gates, and choose
either an expressly authorized current-tree refresh or a versioned historical
record plus a separately hashed shared-integration attestation. The latter must
still detect changed, missing and added shared dependencies and verify both
versions' behavior. Do not simply remove docs, exclude new versions by pattern,
copy new hashes into the old file, or relabel its failure as passed. The existing
shared-lexer attestation demonstrates a bounded one-file review; it does not
authorize an aggregate waiver for the remaining inputs.

The historical bounded grammar claim is not automatically retracted, expanded
or renewed by this assessment. Any refreshed current-tree claim needs independent
review of its bounds and original C1-C4 limitations. No historical acceptance of
a limitation transfers automatically to PHP 8.6.

## 4. Independent assessment of the ten blocked obligations

All seven [Phase 6 blocker dispositions](source-audit-dispositions.json) retain
their original `blocksCertification: true` values. The following is assessment,
not closure. Each row has its own dependency/verification record in the plan.

| Blocked gate | Finding and concrete missing work | RC4 work versus external dependency |
|---|---|---|
| `production-coverage-closure` | C6-COVERAGE: 64 unexercised productions, including reserved-non-modifiers; independently justify 52 primitive and 11 trivia abstractions. | Focused source/witness work can proceed now; no coverage-percentage waiver. |
| `alternative-coverage-closure` | C6-COVERAGE: 318 unexercised alternatives, including 134 meaningful gaps and unresolved context-only `from`. | Isolate exact identities at RC4; contextual or binding discrepancies may need other findings resolved. |
| `scanner-state-closure` | C6-COVERAGE: 190 headers/11 states do not establish rule/state/helper/error products; 156 ranges lack isolated RC4 witnesses in the later matrix. | Source and distribution probes can proceed; exact-build claims need provenance. |
| `ast-binding` | C6-COVERAGE: family mapping/acceptance does not prove operand/AST or interpolation binding. | Build independent RC4 derivation/AST comparisons; compatible oracle/adapter support is a prerequisite. |
| `folding-order` | PHP86-015: optimized-call and array/constant visited-AST order lacks a general intended-compatibility boundary. | RC4 dispatch analysis and controls can proceed; upstream intent clarification is required for closure. |
| `discovery-closure` | PHP86-013: 230 pending and 14 examined-unresolved hunk records, plus transitive/outside-ledger paths and test associations. | Substantial RC4 review can proceed; uncertain semantic groups remain separately blocked. |
| `writable-target-validity` | PHP86-014: intended assignment/reference/list-derived writable targets and compiler/VM inconsistencies remain unresolved. | Reproductions/source analysis now; authoritative intended-validity evidence and potentially a later reviewed fix/pin are needed. |
| `contextual-enforcement-scope` | C6-CONTEXT: 73 restriction descriptions are not isolated predicates or complete native enforcement. | Predicate/stage isolation now; explicit bounded-claim adjudication depends on linked discrepancies. |
| `discarded-hook-disposition` | CA86-001: get/set-only EBNF rejects an unknown hook in a discarded declaration that Zend can accept. | Evidence supports a concrete policy proposal now; retaining a bounded divergence or correcting 8.6 needs separate authorization and verification. |
| `exact-pin-executable` | C6-PROVENANCE: no independently attested build, missing SDK/generator prerequisites, and no accepting attestation verifier. | Provisioning proposal now; safe build and verifier work remain prerequisites. |

The coverage residue remains 174 primitive-bypassed, nine trivia-removed, 134
meaningful and one unresolved alternative. The parser audit accounts for 176
productions and 623 alternatives; 59 RFC candidates are classified. These are
inventory/review bounds, not proofs that every predicate or branch is equivalent.
The existing recorded PR discussions do not settle PHP86-014/015; no new upstream
intent decision is asserted in this assessment.

No final release is needed merely to investigate RC4 source or add independent
witnesses. A later corrected source pin might be needed for a compiler/VM fix;
that would require its own authorized incremental source review. A final release
label alone would not settle the discrepancies. Final-release reconciliation
remains a distinct future requirement and was not begun here.

## 5. Exact-pin executable provenance and environment

[Fresh environment probes](certification-execution/environment.json) refine the
Phase 7A PATH-only assessment. `vswhere` finds Visual Studio 2022 Community
17.12.3 with C++ tools, and the installed x64 compiler is MSVC 14.42.34433; its
binary hash is recorded. `cl` and `nmake` are not configured on PATH. Bison,
re2c, phpsdk_buildtree and phpsdk_deps are not available through the checked PATH;
the recorded common PHP SDK/dependency directories are absent. Docker and Podman
are unavailable, and owner-context WSL enumeration explicitly reports no
installed distribution. An installed compiler alone is not a provisioned PHP SDK.

The RC4 source's Windows build configuration requires Bison 3.0.0 and re2c 1.0.3
or later (`win32/build/confutils.js`, lines 54 and 57). The complete supported
SDK/dependency bundle, generator identities and generated files remain to be
provisioned and recorded. No suitable complete isolated build environment was
established, so no build or system installation was attempted. This finding does
not claim that Visual Studio is absent or that an RC4 build is impossible.

Fresh interpreter metadata is exported in
[the interpreter record](certification-execution/run/docs/8.6/certification/interpreters.json).
The existing PHP 8.5.10 distribution reports Visual C++ 2022; the PHP 8.6.0RC4
distribution reports Visual C++ 2026, x64 NTS, API20260924/VS18, built October 21,
2026. These are different compiler labels. The older build-preparation text's
MSVC 2022 reference must not be read as the RC4 distribution's compiler identity.
Neither version string nor binary SHA-256 proves correspondence to its normative
source commit. The source lock's 235 RC4 source/test hashes attest selected bytes,
not the complete build tree, generated files, dependencies or executable ancestry.

The precise next step is a separately provisioned disposable build with a clean
immutable source tree, pinned compiler/SDK/dependencies, generated-source/header
and configuration hashes, exact build commands/logs, and independently reviewed
linkage to the executable actually used by differential tests. Keep `-n`,
zend.multibyte=0, explicit short tags, opcache.enable_cli=0 and opcache.jit=0;
separate lint, tokenization, declaration linking and controlled execution.
No host PHP replacement or machine-wide executable selection change is proposed.

## 6. Conformance execution and limits

The [fresh runner ledger](certification-execution/run/docs/8.6/final-evidence.json)
contains actual fixture totals, command outcomes, complete input/output hashes,
coverage measurements and interpreter records. Its full-suite and negative-test
logs are retained without rewriting the Phase 7A originals. The ordinary
differential rechecks every reviewed source fixture; the focused PFA, contextual,
diagnostic, lexer/profile and version-boundary checks retain their distinct
validation stages. The isolated runtime sources are the existing reviewed probes,
not arbitrary fixture execution. Successful lint does not prove declaration-link
acceptance or runtime validity.

<!-- validation:start -->
Fresh full regression: **6,157 tests / 58,061 assertions**, exit 0.

[Full regression log](certification-execution/run/docs/8.6/certification/logs/full-regression.log) and the gate logs above record the actual executions. Overlapping gate test counts are not summed. The dedicated certification negative-test gate passes with **10 tests / 40 assertions**, retaining rejection of manipulated or stale evidence.

The three additional PHP 8.5 coverage, Phase 6 evidence, and source-correspondence read-only checks pass; see [supporting checks](certification-execution/supporting-checks.json). These do not replace the failed aggregate freshness gate or imply a complete historical recertification.

The isolated prior freshness check, generation and fresh check exit zero. Claim evaluation and run preflight exit one as expected. Before/after hashes preserve all 2,225 monitored run paths.
<!-- validation:end -->

All measured checks are bounded to the selected sources, distributions, matrices
and byte-source profile. Upstream PHPT-suite execution, exhaustive scanner
equivalence, arbitrary binding equivalence, a complete contextual validator and
final-release compatibility are not claimed. A passing full suite is not a
disposition for any mandatory blocker.

## 7. Certification infrastructure limitations

The [T7B-RUNNER proposal](certification-remediation.json) identifies concrete
limitations in the existing Phase 7A implementation:

1. `--run` always refuses and executes no full gate sequence; the available gate
   execution path is `--prepare`.
2. `claimErrors()` always appends a Phase 7A authorization error. Static closure
   gates must remain blocked by construction, even if future evidence changes.
3. `provenanceErrors()` has no success path for an independently valid future
   build attestation. A boolean/commit string must still never be accepted alone.
4. Fixed child-output paths and the global input-set fingerprint couple report
   generations and later documentation/tool additions. `--report` relocates only
   the final ledger. New assessment files legitimately make the old working-tree
   report stale for the expanded tree; they do not change its historical bytes.
5. The old 8.5 aggregate freshness check is mandatory in the 8.6 claim path,
   coupling historical repository-wide evidence to independent version eligibility.

These are not language defects and do not justify bypassing a gate. A future
authorized infrastructure change should provide run-specific output roots,
versioned evidence/claim policy, independently validated closure and attestation
records, and explicit historical/shared-integration contracts. It must preserve
all negative checks and previous runner behavior where contractually required.
The new assessment driver is an isolation and reporting wrapper; it does not
patch the runner, alter the 36 obligations, or turn blocked gates into passes.

## 8. Prioritized remediation and independent verification

The [machine-readable plan](certification-remediation.json) records each failed
or blocked gate's stable finding ID, root cause, required evidence, Gate B
requirements, dependencies, whether work can proceed now, upstream/final-release
needs, and closure verification. The recommended order is:

1. Scope and authorize H85-FRESHNESS and T7B-RUNNER remediation independently.
   Preserve historical ledgers, review dependency boundaries, and add legitimate
   future closure/build verification without weakening failure defaults.
2. Provision the isolated RC4 SDK/build and independent attestation in parallel
   with source research. This enables exact-executable evidence but closes no
   language discrepancy by itself.
3. Prioritize PHP86-013 discovery and minimized PHP86-014/015 source/dispatch
   investigations. Seek authoritative intent evidence for the two unresolved
   language boundaries before any normative correction.
4. Address the four C6-COVERAGE gates individually with exact identity/witness,
   scanner-state and binding evidence. Retain every justified residue and avoid
   arbitrary numerical targets or artificial chart coverage.
5. Isolate remaining contextual predicates and obtain an explicit CA86-001 and
   bounded contextual-scope decision. Native whole-source enforcement would be
   a separate, much stronger claim than a grammar with external constraints.
6. After separately authorized remediation, rerun affected commands and the
   complete independent gates with fresh hashes. Reassess claim eligibility
   without automatic promotion. Only a later authorized phase may begin final
   PHP 8.6 source reconciliation and its release-specific evidence.

Gate B applies to every proposed normative, lexical or contextual correction:
immutable source proof, exact affected constructs/layers, smallest justified
change, parity, positive/negative and stage/boundary regressions, and preservation
of historical versions. This assessment authorizes none of those corrections.

## 9. Reproduction, read-only validation and preservation

The driver uses PHP for all substantive orchestration and analysis. Reproduce an
execution with a new snapshot name; it refuses to overwrite an existing snapshot:

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-assessment.php --execute --snapshot .audit/phase7b-recheck
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-assessment.php --analyze
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-assessment.php --seal
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-assessment.php --check
```

`--seal` records digests only; it is not a certification or independent proof.
`--check` separately verifies baseline preservation, gate accounting, exported
run inputs/artifacts and recorded outcomes. The runner's own `--check` was also
executed against its isolated generated ledger. Recorded before/after hashes
verify that `--check`, `--claims` and `--run` do not modify evidence. All 2,227
baseline files, including every Phase 7A report and all 1,437 protected PHP 8.5
files, must remain byte-identical in the workspace. The assessment adds new
reports and a wrapper; it does not refresh original ledgers to make them pass.

## 10. Separate readiness decisions

| Milestone | Readiness | Reason |
|---|---|---|
| Historical freshness remediation proposal | **READY** | Exact changed/added paths and hashes, contract conflict, and independent verification requirements are established. Implementation/refresh still needs separate authorization. |
| PHP 8.6 blocker resolution | **READY WITH BOUNDED LIMITATIONS** | Current RC4 permits focused discovery, witnesses and concrete proposals; upstream intent, SDK/build and claim decisions remain dependencies. |
| Bounded evidence collection infrastructure | **READY WITH BOUNDED LIMITATIONS** | Available commands execute in isolation; final certification/attestation paths need the proposed separate changes. |
| Independent prerelease grammar certification | **NOT READY** | Historical gate failure, seven blocking findings, missing closure evidence and provenance remain. |
| Candidate promotion | **NOT READY** | Belief-of-completeness requirements are unmet; passing tests do not supply them. |
| Final PHP 8.6 certification | **NOT READY** | No final-release reconciliation or source/executable release verification was performed, in addition to the existing blockers. |

Source correspondence has been reviewed within its explicit family/inventory
bounds. Individual tests may support a precisely named bounded conformance
statement, but the available evidence does not justify a package-level prerelease
certification decision. PHP 8.5 remains the recommended stable grammar; PHP 8.6
remains draft. Phase 7B stops at assessment and reporting. No next phase is begun.
