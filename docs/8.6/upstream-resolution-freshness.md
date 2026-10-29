# PHP 8.6 upstream-resolution evidence freshness

October 28, 2026 — Phase 7H.1. PHP 8.6 remains **draft**. This phase repairs
current evidence dependency selection after the authorized Phase 7H informative
clarifications. Historical evidence and original failure outcomes remain intact.
No normative grammar, language expectation, source pin, public API or mandatory
release obligation changes.

## 1. Reproduced failure and dependency chain

The failed command was `full-regression` in
[current-compatibility-v1 revision phase7h](../8.5/compatibility/phase7h/evidence.json):
PHP 8.5.10 with `-n`, explicit `php_mbstring.dll`, and
`vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress`.
The [original log](../8.5/compatibility/phase7h/full-regression.log) records
6,292 tests, 58,113 assertions and one failure. The
[fresh isolated reproduction](upstream-resolution-freshness/reproduction.log)
records one test, ten assertions and the same failure before any correction.

`Php86CompilerContextualTest::testRecordedOracleEvidenceIsFreshAndStageSpecific`,
original line 50, compared each `inputHashes + fixtureHashes` entry in
`docs/8.6/correctness-remediation-probes.json` with current bytes. That Phase 5D
report retains the Phase 5C generator identity and 162 paired observations. Its
SHA-256 is `c9618823346494c4c8eada5ff8718356d3599daa6aa2666cf398eb47e2ab58bc`.
The first stale dependency was `grammar/8.6/php.md`:

| Input | Historical expected SHA-256 | Authorized Phase 7H SHA-256 |
|---|---|---|
| `grammar/8.6/php.md` | `45688b7d36ff16d823360f9072f43aa8e52c4dbd9189c890a6fdeef592bca521` | `1aa67b423e391c55f92f5b65ffba81bdd9d0dd8ab4c49201e66fab9eff1d100e` |
| `grammar/8.6/changes.md` | `27754b1eb2f9806753e94243e1c25bba6c1039aed0c43943d13f5c0655643d9d` | `627be3da6c3dfdb5d431fea82f486fe5e42794b1c16cf0cc276bd1bb79261611` |
| `tools/8.6/change-map.json` | `1396186ea1a2d022a22fdaf36090b9d523db77ab16590c453c9a01e4002b9903` | `b609105cf760075d9dea32d97d0689c228840cc9c37b3401bf9b63510652c38f` |

The v2 preservation contract also correctly rejected these three changed files.
The Phase 7F v4 certification assessment rejected current dependency membership
and its old 353-case differential input hashes. Thus the failures share the
Phase 7H change as their origin, but are separate dependency contracts, not one
assertion that can simply be ignored. The unchanged EBNF alone cannot establish
that the prose and traceability changes were authorized or harmless.

## 2. Authenticated successor

The [baseline](upstream-resolution-freshness/baseline.json) retains the dirty
Phase 7H working tree and every predecessor project-file digest. The original
documents, traceability map and test are additionally retained as raw
[predecessor bytes](upstream-resolution-freshness/predecessor/grammar/8.6/php.md).
No historical report is overwritten or relabeled as current.

`php86-upstream-resolution-freshness-v1` authenticates the original contextual
report, both phase baselines, the sealed Phase 7H artifact index and its complete
bundle, including raw upstream responses and original work/blocker requirements.
The approved before/after pairs are constants in
[UpstreamResolutionFreshness](../../tools/lib/UpstreamResolutionFreshness.php),
not values learned from the changed files or supplied by a caller. The
[transition](upstream-resolution-freshness/transition.json) preserves every
original identity and hash.

The unchanged contextual generator was rerun into
[compiler-contextual.json](upstream-resolution-freshness/compiler-contextual.json):
1,708 checks, 501 hash checks, 162 lint observations and 37 explicitly reviewed
execution probes, with zero errors. Independent verification requires whole-record
equality with the authenticated predecessor after substituting only the reviewed
`php.md` dependency hash, then verifies every current input and fixture digest.
All observations, stages, diagnostics, expectations, interpreter profiles and
source identities remain equal. These are distribution-profile observations;
they are not relabeled as exact-source execution.

The mandatory compatibility document inventory adds the successor, predecessor
copies and every file consumed from the sealed Phase 7H bundle. No prior input
is removed. Revision-output paths in the validation driver are constructed from
the selected revision, avoiding a circular dependency on its own future output;
the actual test/evidence inputs remain explicit mandatory dependencies.

The original test now selects this successor and first verifies its authenticated
transition. All previous freshness and stage-specific assertions remain. The
test's own before/after bytes are a fourth explicitly reviewed preservation
transition; this does not authorize arbitrary test edits. New negative tests
reject missing or altered predecessors, modified prose/map/EBNF/upstream evidence,
missing dependencies or observations, changed fixtures/source/oracle identities,
fabricated claims, unrecognized contracts and broadened projection allowances.

## 3. Preservation and certification composition

The current runner retains its existing v2 execution and v4 differential
composition. New revisions explicitly select
`--preservation-contract php86-preservation-v3`. Omitting that option retains
the original v2 preservation meaning and rejection of the Phase 7H bytes.
The v3 record embeds the entire v2 predecessor transition, preserves every
original baseline hash and permits only the four exact reviewed transitions
above. Every other original dependency remains mandatory.

The corresponding `php86-current-source-audit-v3` uses an isolated projection
and independently verifies all current and projected bytes and full membership.
The original three policy-document substitutions remain. The additional
specification/change-document bytes are hash-only inputs to this source audit;
the modified test is read only by its preservation scan. The change map is a
computational input: verification proves its entire content unchanged except
the two reviewed documentary section hashes/reasons and `changesSha256`.
In particular, all source-relevant findings, productions, fixtures, obligations
and implementation mappings remain identical. The unchanged audit recomputes
the complete original ledger, including all unresolved source obligations.
The working tree is never projected or rewritten.

Only historical-preservation and the two shared source-audit rows receive `@v3`
dependency identities. The 36 original obligations, two independent historical/
current prerequisites, seven original mixed blockers and their requirements are
retained. The ordinary-differential row continues to use the existing v4
composition with a newly generated, independently verified 353-case report.
No new coverage waiver, freshness exclusion, gate omission or promotion rule is
introduced. All 27 historical release-command outcomes remain required by the
existing historical-release-dependency contract.

## 4. Maintainer dispositions and preservation

The authenticated [Phase 7H dispositions](upstream-maintainer-resolution/dispositions.json)
and [work ledger](upstream-maintainer-resolution/work-items.json) remain byte-identical.
CA86-001 has an explained skipped-compilation cause but retains the discarded-hook
structural mismatch and its original correction/scope requirement. PHP86-015
records the general discarded-expression explanation, not a printf-specific rule,
and retains dispatch/initializer correspondence requirements. PHP86-014 remains
unresolved. No upstream closure is converted into repository blocker closure.

All seven blockers remain: PHP86-013, PHP86-014, PHP86-015, CA86-001,
C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE. PHP 8.5 and PHP 8.6 EBNF and
specifications, historical evidence, source pins, manifest status, public APIs,
fixture expectations and original release gates remain unchanged from the
Phase 7H.1 baseline. PHP 8.6 stays draft; no subsequent phase begins.

## 5. Commands and actual validation

The [validation appendix](certification-contract-remediation/phase7h1-validation/results.md)
is the final results record: exact command arrays, logs, exits, full-suite counts,
all 15 compatibility outcomes, independent verification, preservation and
historical-integrity results, negative tests, JSON/link/whitespace checks,
current 38-obligation assessment and the required claims refusal. It explicitly
states whether compatibility and the full PHPUnit suite pass. Generated reports
and independent checks are recorded separately; historical outcomes are not
counted as fresh executions.

Reproduction entry points are:

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/upstream-freshness.php --check
rtk proxy .audit/php85/php.exe -n tools/evidence/compatibility.php --generate --revision phase7h1-audited
rtk proxy .audit/php85/php.exe -n tools/evidence/compatibility.php --check --report docs/8.5/compatibility/phase7h1-audited/evidence.json
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-current.php --contract php86-certification-v2 --generate --revision phase7h1-audited-base --preservation-contract php86-preservation-v3 --profile exact-pin-rc4 --legacyProfile historical-rc4 --compatibility-report docs/8.5/compatibility/phase7h1-audited/evidence.json
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-scanner-differential.php --contract php86-certification-v4 --generate --revision phase7h1-audited --assessment docs/8.6/certification-contract-remediation/runs/phase7h1-audited-base/evidence.json --differential-report docs/8.6/upstream-resolution-freshness/differential-v2.json
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-scanner-differential.php --contract php86-certification-v4 --check --report docs/8.6/certification-contract-remediation/runs/phase7h1-audited/evidence.json
rtk proxy .audit/php85/php.exe -n tools/8.6/certification-scanner-differential.php --contract php86-certification-v4 --claims --report docs/8.6/certification-contract-remediation/runs/phase7h1-audited/evidence.json
```

Generation requires unused revision names. Claims must refuse while the nine
original substantive obligations and seven blockers remain unresolved, even
when current compatibility and evidence verification pass.

## 6. Isolated-snapshot dependency correction

The preserved `phase7h1-verified` compatibility revision passed all 15 commands
and 6,310 tests / 58,864 assertions. Its base certification run exposed a
separate snapshot inventory defect: the general revision-output exclusion
omitted the sealed Phase 7H artifact index and bundle consumed by the successor
tests. The isolated release PHPUnit run therefore reported 18 errors and one
failure, despite the repository suite passing. This was a real missing mandatory
dependency, not an authorized failure or a coverage waiver.

`CertificationCurrent::inventory()` now explicitly authenticates and includes
the fixed authority anchors and every sealed bundle member. The regression test
requires their exact hashes in the snapshot inventory. The prior run is preserved;
`phase7h1-audited` is the successor validation revision. All current stage
receipts must be present before publishing the final validation results.

The snapshot inventory correction also changes an authenticated input of the
353-case differential report. Its successor is `differential-v2.json`; the
original report and receipt are preserved. Intermediate validation revisions
remain checkpoints, and only the final revision is used for current claims.

## 7. Preserved historical command refusals

Independent checking of the composed `phase7h1-final` assessment passed. Its
actual 38-obligation result is 27 passed, two failed and nine blocked; claims
refuse for eleven unmet obligations and all seven blockers. The initial validation
script incorrectly assumed 29 passed and only nine unmet obligations. This was
a reporting expectation defect, not evidence that either historical gate passed.

The original coverage-measurement command reports `Unchanged 8.6 specification`;
its original specification hash is intentionally preserved. The original
evidence-freshness command rejects the old hashes of php.md, changes.md and
change-map.json recorded in section 1. These command outcomes are retained in
the legacy export and current assessment, with no replacement or closure. All
current successor preservation/freshness checks and independent verifications
pass. Their failures cannot be converted to passes merely because the document
transition is authenticated. Any later disposition would need its own reviewed
contract and evidence; it is outside this phase.

The corrected validation reporter explicitly requires the actual 27/2/9 result
and preserves all eleven unmet obligations. The `phase7h1-audited` revision
records validation after this reporting correction. No gate or blocker is waived.
