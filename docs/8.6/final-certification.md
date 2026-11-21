# PHP 8.6.0 final certification decision

November 20, 2026 — Phase 8B–9.

**NOT SIGNED OFF — RELEASE BLOCKED**

The official GA source was authenticated, an isolated exact-source CLI was built,
and fresh GA differential and regression evidence was collected. PHP86-014 is
still reproducible in GA. The seven original blockers remain, and the existing
certification contracts have not been transitioned to GA. PHP 8.6 remains
**draft**, PHP 8.5 remains **stable**, and no release tag or publication is
authorized by this assessment.

This is an evidence-backed blocked-release decision, not successful independent
final certification. The new observation/build/bundle contracts are additive
records; they do not replace, weaken, or renew any mandatory release contract.

## 1. Baseline and authority

Repository HEAD: `afc4cfdda723ddb0abd453ab0eb69e187a75a599`.
The [baseline](ga-finalization/baseline.json) records 5,402 preexisting files,
the complete manifest, Git identity and local/remote tag command receipts.
The working tree already contained untracked Phase 8A work:
`docs/8.6/rc7-source-reconciliation.md`, its adjacent evidence directory, and
`tools/8.6/rc7-source-reconciliation.php`. Those inputs are preserved. The
baseline status receipt also shows this phase's newly created controller and
evidence directory; it is not a claim that they predated this task.

The governing requirements remain [conformance policy](../conformance-policy.md),
[versioning](../versioning.md), [sources](../sources.md),
[grammar conventions](../grammar-conventions.md), [API contract](../api.md),
and repository [AGENTS.md](../../AGENTS.md). In particular, a known in-scope
mismatch and incomplete material evidence cannot be waived by passing fixtures.
Documented external contextual enforcement does not by itself authorize a
bounded certification claim.

## 2. Official final source

| Identity | Authenticated value |
|---|---|
| Repository | `https://github.com/php/php-src.git` |
| Tag | `php-8.6.0` |
| Annotated tag object | `48a5ee8a311db69103a6fa25b330e5ff3847af3d` |
| Full commit | `8aaada3f9605d81a38b8240f93c7389dfbc77ef9` |
| Release date | November 19, 2026 |
| Source tar.gz SHA-256 | `62337dccde3d9fcc39797d4529f42637613036eaf3875353c206874e5813a3c1` |
| Official Windows NTS x64 ZIP SHA-256 | `d4783fffe1f4ab072600daff1622a7a2df936957a787a338c59e3dbd4d846d98` |

The [versioned GA source profile](ga-finalization/source-profile.json),
[official release record](ga-finalization/release-json.body),
[announcement](ga-finalization/release.body),
[migration page](ga-finalization/migration.body),
[tag API response](ga-finalization/ga-tag.body), and
[local tag object](ga-finalization/tag-object.log) retain the provenance.
HTTP response bodies have adjacent headers, retrieval times and hash receipts.
GitHub reports a valid signature. Local `git verify-tag` could not verify the
signer because the public key was unavailable; no independent GPG trust-chain
claim is made. Authentication uses the official HTTPS repository and published
archive checksums under the repository's established custody model.

All **280 selected GA files** agree byte-for-byte between Git and the official
source archive, including the retained source inventory and every changed GA
path. See [archive correspondence](ga-finalization/archive-correspondence.json).
The complete Git-tree comparison retains additions and removals; it does not
rely on the truncated GitHub compare endpoint or on a moving branch.

The GA observation profile is not yet the active manifest/certification profile.
RC4 and RC7 profiles and source locks remain unchanged. Promoting the new profile
requires an explicit GA successor of the existing source, executable,
differential, preservation and certification contracts, not replacing a pin
string in old evidence.

## 3. RC7-to-GA reconciliation

The [complete diff](ga-finalization/complete-diff.log),
[history](ga-finalization/history.log),
[tree inventory](ga-finalization/tree-inventory.json), and
[per-path review](ga-finalization/review.json) account for **53 changed paths**.

Parser, scanner, compiler, AST, VM definitions, inheritance, property hooks,
attributes, enum and constant implementation files are byte-identical to RC7.
No additional structural syntax, lexical rule, precedence, associativity, or
contextual-validity change was identified in this incremental comparison.

The changed Zend code handles fatal-error lifecycle, incomplete module shutdown,
and trampoline arguments in backtraces. Other implementation changes concern
DOM/SOAP ownership, Phar signature enforcement, session persistence/configuration,
HTTP/FTP protocol handling, URI recomposition, CGI/FastCGI request handling,
FPM output escaping and debugger formatting. They are runtime/security or
diagnostic implementation corrections, not PHP grammar additions. Release
metadata, security regressions and the corrected TLS RFC URL are classified
separately in the ledger. This bounded review does not discharge the older
PHP-8.5-to-8.6 discovery queue.

No EBNF production, normative clause, lexer implementation, fixture expectation,
or public API was changed in this phase. The existing five PHP86-001 production
modifications remain the grammar's only implemented structural delta:
`ordinary-argument-list`, `first-class-callable-arguments`,
`clone-argument-list`, `argument`, and `argument-no-expression`.

## 4. Authenticated interpreter and reproducibility

The local exact-source build is
`.audit/phase8b/source/x64/Release/php.exe`, with adjacent `php8.dll`.
It reports PHP 8.6.0, NTS, Visual C++ 2022 x64. This version string is supplementary
information; source/build/output custody is established separately.

| Output | SHA-256 |
|---|---|
| Local exact-source CLI | `880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b` |
| Local exact-source core DLL | `9b2a8bad3ff00c1aa57c83676cff79a830df63ed5af1fc3eb6d55de8b8ab57f9` |

[Source authentication](ga-finalization/build-source.json) verifies all **28,633**
tracked source files against immutable Git blobs. Extraction initially exposed
checkout conversion, quoted Unicode names and archive-omitted test data; raw
Git bytes were restored and the complete final source inventory verified before
configuration. The source archive extraction also needed a process-local 1 GiB
PHP memory limit. These preparation failures did not authorize source patches.

The SDK is the previously used official commit
`87567dececcf806c83e67fceb89a3bd206e7f1b1`. The toolchain is MSVC
14.42.34433, Windows SDK 10.0.22621.0, Bison 3.3.2 and re2c 1.1.1.
The existing Windows SDK launcher ran:

```text
buildconf.bat
configure.bat --disable-all --enable-cli --enable-tokenizer --disable-zts --disable-cgi --disable-phpdbg
nmake
```

[Environment](ga-finalization/build-environment.json),
[configuration](ga-finalization/build-configure.json),
[compilation](ga-finalization/build-compile.json), and the separate
[build receipt](ga-finalization/build-receipt.json) bind commands, task bytes,
tool hashes, generated parser/scanner files, outputs and complete logs.
Build warnings remain in the logs. Independent read-only verification rechecks
all source files, SDK inputs, build tasks, logs and output identities.

The trust boundary is the reviewed local controller and host. This is not a
hermetic or independently reproduced bit-for-bit build. Windows runtime
dependencies are external. No existing exact-RC4 receipt is reused, and the
host's default PHP installation was not replaced.

The final [independent build check](ga-finalization/final-build-verification.log)
passed with all 28,633 source files and 18 build/output/tool identities verified.

## 5. Fresh conformance and coverage

| Measurement or execution | Actual result |
|---|---|
| Canonical productions | 361; 298 exercised, 63 unexercised |
| Canonical alternatives | 800; 545 exercised, 255 unexercised |
| Recomputed Zend parser | 176 productions, 623 alternatives |
| Recomputed scanner | 190 rule headers, eleven states |
| Current ordinary differential corpus | 353 cases, zero mismatches |
| Stage-aware GA controls | 410 observations on each of the local build and official distribution |
| Focused coverage/scanner/binding replay | 37 cases: 17 coverage, 14 scanner, six binding; zero recorded differences |
| Writable-target stage controls | 36 observations: lint, opcode generation and execution remain distinct |
| Focused upstream PHPTs | 15 passed; zero failed, skipped or warned |
| Full repository regression | 6,311 tests, 58,955 assertions; passed on PHP 8.5.10 |

These selections overlap and are not summed into a fictitious unique test count.
The [final full-suite rerun](ga-finalization/final-phpunit.log) also passed
6,311 tests and 58,955 assertions; its command, exit zero and log digest are in
the [separate receipt](ga-finalization/final-phpunit.command.json).
The [fresh coverage output](ga-finalization/validation-coverage.log),
[source recount](ga-finalization/source-counts.json),
[ordinary differential](ga-finalization/ordinary.json),
[exact-build observations](ga-finalization/observations-exact.json),
[distribution observations](ga-finalization/observations-distribution.json),
[focused observations](ga-finalization/focused.json),
[writable controls](ga-finalization/writable.json), and
[upstream receipt](ga-finalization/upstream.json) preserve their actual bounds.

The 410-observation matrix retains **one known diagnostic-string mismatch**:
`reconciliation/hook-retained:lint`. Rejection remains exit 255; GA and RC7
include the anonymous-class filename/line suffix and embedded NUL in the error
where the historical expectation has only `class@anonymous`. No acceptance
outcome changed, and no historical expectation was rewritten. This is not the
separate discarded-hook structural divergence, which remains a release blocker.

Ordinary controls use `-n`, `zend.multibyte=0`, explicit short-tag settings and
disabled CLI OPcache/JIT. The focused binding and writable opcode probes enable
OPcache with optimization disabled solely to collect pre-optimization opcodes.
The six binding controls compare original and forced grouping against a
different grouping; they do not prove exhaustive AST equivalence. The scanner
observations do not establish complete state/helper/error coverage. Coverage
classifications and remaining identities are preserved; no arbitrary percentage
target or production removal is used.

## 6. Blocker dispositions

The [assessment](ga-finalization/gate-assessment.json) preserves every original
blocker definition and adds the current disposition without changing its flag.

| Blocker | Current evidence, remaining requirement and release consequence |
|---|---|
| PHP86-013 | The 53-path GA delta is reviewed. The retained Phase 7E queue still has 198 pending and 17 examined-unresolved hunks, plus grouped/outside-ledger and test-association obligations. These require local source review; they are not all upstream dependencies. Discovery and promotion remain blocked. |
| PHP86-014 | [PR #25046](https://github.com/php/php-src/pull/25046) is open and unmerged; its proposed commit is not a GA ancestor. GA's writable dispatch is identical to RC7. Ordinary assignment-derived writes pass lint and emit a temporary `ASSIGN_DIM` operand, then fail with `Invalid opcode 23/2/1`; reference-derived forms retain their recorded execution behavior. A coherent implementation and intended-validity disposition remain missing. No contextual rule or EBNF permission is invented. |
| PHP86-015 | The maintainer's skipped-compilation explanation and wontfix decision remain resolved. GA retains the known call/traversal boundaries. Complete ordered dispatch/visited-AST correspondence and bounded-claim adjudication remain outstanding; no universal optimizer rule is inserted into EBNF. |
| CA86-001 | The maintainer explanation remains resolved. The get/set-only grammar still rejects unknown hooks in declarations PHP discards. An independently justified correction or enforceable scope exclusion has not been adopted. The closed upstream issue does not close this certification gate. |
| C6-CONTEXT | The 73 inherited descriptions are not 73 isolated predicates. Interpreter-backed stage evidence is available, but complete material predicate correspondence and claim adjudication remain open. Native matching does not enforce all compiler/linking constraints. |
| C6-COVERAGE | All remaining identities and existing per-identity dispositions remain. Fresh measurements and focused scanner/binding controls strengthen evidence but do not close meaningful alternative, helper/state and binding obligations. |
| C6-PROVENANCE | GA source and local exact-source executable evidence now exist. A complete successful release run, adopted GA certification dependencies, historical integrity and fresh current-compatibility evidence are still required. This mixed blocker is not closed by the build alone. |

The [upstream maintainer resolution](upstream-maintainer-resolution.md) and
its retained comments are preserved. No upstream discussion was reopened and no
message was posted. PHP86-014 remains an implementation/validity discrepancy;
PHP86-015 and CA86-001 have resolved explanations but incomplete certification
dispositions. These distinctions remain explicit.

## 7. Mandatory gates and certification contract

All **36 original gates plus two hybrid prerequisites (38 obligations)** remain
in [gate-assessment.json](ga-finalization/gate-assessment.json), with exact
original requirement text, supporting observations and unsatisfied GA status.
That file is a blocked-release preflight, **not a replacement runner or a
38-gate pass ledger**. Nine original substantive closure gates remain blocked:

- production and alternative coverage closure;
- scanner-state closure and AST binding;
- folding order and discovery closure;
- writable-target validity;
- contextual enforcement scope and discarded-hook disposition.

The [actual command receipts](ga-finalization/validation.json) distinguish
passing manifest/traceability/coverage/PHPUnit commands from failed historical,
compatibility and certification checks. Existing `php86-certification-v4`
`--check` and `--claims` both return exit 1. Required old runtime/source caches
are unavailable, and current dependency inventories are stale. In particular,
the retained assessment requires `.audit/php86/php.exe`; the new GA executable
cannot be substituted for that RC4 dependency. Historical integrity also lacks
authenticated snapshot bytes. These failures are not evidence that this phase
changed those historical files.

The full independent **GA** release runner has therefore not completed and is
not reported as passing. The old contracts explicitly select RC4. Final-source
observations cannot silently become successor gate passes, and a passing
PHPUnit suite cannot authorize sign-off. The historical Phase 7H.1 27/2/9
assessment is not relabeled as a fresh GA outcome.

## 8. Traceability and historical preservation

`grammar/8.6/changes.md` retains all ten required sections and passes its current
traceability checks. It remains an explicitly incomplete **RC4-based** change
document, not a finalized GA document. The GA delta, source identities and
implementation dispositions are recorded here and in the additive per-path
ledger. Finalizing the normative package and its source references still needs
the complete source/discrepancy work and a verified source-profile transition.

The bundle verifier requires byte-for-byte preservation of all 5,402 baseline
files, including both versions' EBNF/Markdown, PHP 8.5 fixtures, locks,
certification/release evidence, existing Phase 7 contracts and the preexisting
Phase 8A work. This is current baseline preservation, not a substitute for
authentication of the historical certification snapshot. No historical evidence
was regenerated. Manifest status, discovery, documented default-version policy
and existing APIs remain unchanged.

## 9. Supported claims, limitations and release preparation

Supported: the named GA source authentication, local build custody, incremental
source comparison, fresh measured coverage and finite stage-aware observations.
Structural matching and external contextual validation remain distinct.

Unsupported: stable PHP 8.6 grammar certification, a complete PHP validator,
exhaustive parser/compiler equivalence, complete source-discovery closure,
complete scanner/AST coverage, or renewal of historical PHP 8.5 certification.

The configured project remote advertises **v1.0.2** as its latest tag, and the
[GitHub release API](ga-finalization/project-release.body) confirms it is the
latest published, non-prerelease release. The local tag list was empty. Composer
has no embedded package version; the public Packagist metadata endpoint returned
404, so no Packagist publication is claimed. **v1.1.0** is the
prospective minor release for a completed backward-compatible PHP 8.6 addition,
not PHP's own version and not a created tag. See the
[conditional release-preparation record](ga-finalization/release-preparation.md).
README, API claims, manifest, package release metadata and default selection
are not promoted while certification is blocked. No release archive or final
publication description is represented as frozen or approved.

No commit, stable tag, push, GitHub release or package publication was performed.
There are no approved publication commands for this blocked candidate.

## 10. Verification and remediation

Generation and checking are separate. The final artifact seal includes this
report, the controller and all new phase evidence; only the seal itself is
excluded from its own digest set. Read-only checks do not write report files.
This avoids circular self-hashing without excluding mandatory existing inputs.
The seal validates this blocked-release bundle, not global certification freshness.
Its file inventory and digests are retained in
[artifacts.json](ga-finalization/artifacts.json).

```text
rtk proxy .audit/php85/php.exe -n -d memory_limit=1G tools/8.6/ga-finalization.php --verify-build
rtk proxy .audit/php85/php.exe -n -d memory_limit=1G tools/8.6/ga-finalization.php --check
rtk proxy .audit/php85/php.exe -n tools/validate-manifest.php
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-scanner-differential.php --contract php86-certification-v4 --claims --report docs/8.6/certification-contract-remediation/runs/phase7h1-audited/evidence.json
```

The first three commands must pass for this retained bundle; the last must
refuse certification. Verification requires the ignored build/source/toolchain
dependencies. Their absence is an error, never permission to trust JSON alone.

Acquisition/build/observation modes in the PHP controller record this specific
run and refuse to overwrite evidence. Reproduction starts from the recorded
baseline and preserved Phase 8A inputs in an isolated workspace, restores the
official source/SDK dependencies, and uses a fresh evidence revision. The
retained receipts contain exact command arrays and paths. Do not rerun generating
modes over these records or reuse the local build receipt for another binary.

Required remediation remains concrete: finish the Phase 7E source-risk queue;
resolve PHP86-014's coherent implementation/intended-validity requirement;
complete the ordered traversal, hook, contextual and coverage dispositions;
restore authenticated historical dependencies; implement an explicitly reviewed
GA successor preserving all 38 obligations; then generate fresh compatibility
and GA certification evidence, independently verify it, finalize the package
and freeze its release inputs. No mandatory obligation is waived by this report.

**Final decision: NOT SIGNED OFF — RELEASE BLOCKED.**
