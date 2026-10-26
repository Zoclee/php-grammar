# PHP 8.6 exact-pin context validation integration

October 25, 2026 — Phase 7C.5. The authenticated exact-source interpreter now
has an explicitly selected contextual evidence profile. Both profiles reproduce
the original 126 observations. **PHP 8.6 remains draft and uncertified.** No
grammar, contextual expectation, source pin, blocker disposition, or release
gate has changed.

## 1. Original limitation and baseline

The unchanged [legacy checker](../../tools/8.6/context-check.php) reads
`executables.8.6` from
[context-source-lock.json](../../tools/8.6/context-source-lock.json), requiring
SHA-256 `c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a`.
Its `Recorded distribution hash 8.6` assertion intentionally rejects the new
build, even when all contextual outcomes agree. It does not support profiles,
and requests version output before its identity assertion. It remains a
historical distribution checker, not the entry point for untrusted binaries.

The [baseline](exact-pin-context-integration/baseline.json) records repository
HEAD `4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting dirty working
tree, and 2,716 pre-edit file hashes. Earlier
phases' uncommitted work is retained. [Baseline command receipts](exact-pin-context-integration/baseline-checks.json)
record passing provenance and legacy context checks. The serialized check of
the previous current-compatibility revision completed after implementation
inputs changed and correctly rejected its stale dependency scope; it is not
presented as a pre-edit compatibility failure. The original revision is preserved.

## 2. Historical profile

`historical-rc4` retains the old executable hash and identifies the reviewed
immutable source commit without claiming that the distribution was built from
it. Its provenance consists of the original source lock and
[historical observations](context-validation.json), whose raw hashes are anchored
in the new adapter. The old command, expectations, and report bytes are unchanged.
The new adapter additionally authenticates identities before execution and
rejects missing/stale historical evidence or changed fixtures.

## 3. Exact-source profile and provenance

`exact-pin-rc4` requires:

| Identity | Required value |
|---|---|
| PHP source commit | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| Executable SHA-256 | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| Core DLL SHA-256, verified by build record | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |
| Build evidence contract | `php86-exact-source-v1` |
| Context evidence contract | `php86-context-profiles-v1` |

The adapter anchors the existing build record and custody receipt, then calls
the unchanged [provenance verifier](../../tools/lib/ExactPinProvenance.php).
That verifies the actual executable path and bytes, core/runtime dependencies,
Git source identity, all 28,445 source files, the 235-file certification lock,
generated files, toolchain and configuration/build logs. The selected source
directory must be the authenticated build tree. The PHP 8.5 comparison binary
and both contextual source locks are also checked before any probe executes.
Neither a filename nor a self-reported version establishes trust.

This inherits the [build report's](exact-pin-build.md) trusted local
controller/host boundary. Checksums detect changed records; they are not
signatures, external attestations, hermetic runtime evidence, or proof of
bit-for-bit reproducibility. Coordinated replacement of reviewed tooling and
all custody artifacts remains outside that boundary.

## 4. Tooling and evidence contract

New files:

- [ContextEvidence.php](../../tools/lib/ContextEvidence.php): explicit profiles,
  preflight authentication, observation generation, independent membership and
  outcome checks, historical comparison, and read-only verification.
- [context-profile.php](../../tools/8.6/context-profile.php): PHP entry point;
  requires a profile and report path, refuses overwrite, and emits a separate
  run receipt.
- [context-evidence.schema.json](../../schema/context-evidence.schema.json):
  separate contextual-observation format; PHP validation additionally enforces
  the precise identities, commands, expected results and dependencies.
- [ContextEvidenceTest.php](../../tests/ContextEvidenceTest.php): portable
  profile, provenance, fixture, observation and tampering regressions.

The only preexisting file modified is
[certification.php](../../tools/8.6/certification.php), adding opt-in
`--context-evidence` inspection. Default legacy behavior and all existing gate
inventories are retained. No historical checker, source lock or report is edited.

Profiles describe the source pin, executable digest, provenance, stages,
fixture set, flags and evidence contract. There is no default profile or implicit
PHP 8.6 binary selection. Probes use `-n`, `error_reporting=-1`,
`zend.multibyte=0`, `short_open_tag=1`, and explicitly disabled CLI OPcache/JIT.
They inherit the host environment; arbitrary environment values are not persisted.
Lint, declaration linking and controlled runtime remain distinct stages.

Reports retain each input path/hash, finding, expected outcome, full command
array, exit status and separate stdout/stderr. Comparison normalizes only the
repository path and surrounding whitespace as the historical checker did.
Raw streams remain available. Dependency membership is reconstructed by the
verifier, not accepted from a report's offered hash map. It also reauthenticates
the live build and source. `--check` executes no contextual probes and writes
no evidence. Generation verifies provenance and input stability again afterward.

## 5. Contextual results and comparison

The [historical-profile run](exact-pin-context-integration/historical.json) and
[exact-source run](exact-pin-context-integration/exact.json) each contain
**63 cases × two versions = 126 observations**, with **zero errors and zero
historical stage differences**. The exact profile contains 63 PHP 8.6 exact-build
observations plus 63 PHP 8.5 controls, not 126 distinct PHP 8.6 cases.
Both lint and the applicable later stage agree, including diagnostics and output.
Existing expectations were not changed to obtain agreement.
The [direct profile comparison](exact-pin-context-integration/comparison.json)
also finds zero raw stdout/stderr or exit-status differences. Each profile
executes 126 lint invocations plus 50 later-stage invocations. Observation stages
are 76 lint, 44 declaration-link and six controlled-runtime rows.

The unchanged legacy command also passes **278 checks / 126 observations** with
its required distribution. These observations cover PHP86-003 through PHP86-007;
they do not resolve unrelated writable-target or optimized-call validity, or
prove complete contextual enforcement.

## 6. Negative validation

[Negative execution checks](exact-pin-context-integration/negative-validation.json)
reject a valid distribution selected under the exact profile, the valid exact
build selected under the historical profile, omitted profile selection, a
modified copy of the actual executable, missing/unreviewed provenance selection,
wrong source correspondence, a missing observation even with a matching run
receipt, tampered run metadata and a valid report selected under the wrong profile.
No original binary or evidence file was modified.

The dedicated unit selection additionally rejects mismatched source commits,
missing build records, tampered configuration/digests/custody receipts,
missing/stale historical observations, changed fixtures, duplicate observations,
altered commands/outcomes and missing stage receipts. Synthetic unit receipts
test rejection logic; only the actual authenticated runs supply positive evidence.

## 7. Certification integration

The runner now recognizes the separate contextual evidence through:

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/certification.php --context-evidence --context-profile exact-pin-rc4 --context-report docs/8.6/exact-pin-context-integration/exact.json
```

This verifies a prerequisite artifact and always reports
`certificationGranted: false`. It cannot be combined with claims/run modes or a
hybrid contract. Historical distribution observations, exact-source observations,
current compatibility, and certification claims retain separate identities.

The executable-selection limitation for this contextual matrix is resolved.
Exact-source provenance and these contextual observations can now be consumed
by future certification-evidence work. **No release gate was converted to a
pass.** The legacy 36-gate ledger and additive 38-obligation hybrid inventory
remain unchanged; all mandatory checks still apply. In particular, matching
outcomes do not close C6-CONTEXT, C6-COVERAGE or the remaining prerelease/final-release
portion of C6-PROVENANCE. The historical 25/1/10 counts are not a fresh gate run.

## 8. Validation and current compatibility

Detailed commands, exits and logs are in
[validation.json](exact-pin-context-integration/validation.json):

| Validation | Result |
|---|---|
| Full PHPUnit, including EBNF integrity, reference validity and Markdown parity | 6,191 tests / 58,273 assertions; zero failures |
| Profile/provenance negative selection | 10 tests / 52 assertions; pass |
| Certification/historical/compatibility infrastructure selection | 22 tests / 168 assertions; pass |
| Version-boundary regression selection | Nine tests / 110 assertions; pass |
| Existing paired integration/boundary checker | 526 checks / 172 observations; zero failures |
| Source lock, manifest, blocker contract, exact provenance and both profile checks | Pass |
| Historical integrity | All 1,545 original inputs authenticated |
| Legacy PHP 8.5/8.6 aggregate checks and PHP 8.6 claims | Refuse as expected; no stale aggregate is treated as current certification |

Overlapping selections are not summed. The fresh
[current-compatibility revision](../8.5/compatibility/phase7c5/evidence.json)
passed all **15 commands**, including its own full suite, against **3,921
dependency identities**, unchanged during generation. Its independent check
passed. [Final verification](exact-pin-context-integration/final-validation.json)
records hybrid claims refusal with historical integrity and current compatibility
verified separately, plus overwrite refusal and claims-mode isolation. No old
compatibility revision was overwritten.

[Artifact checks](exact-pin-context-integration/artifact-validation.json) cover
JSON syntax, PHP lint, local documentation links, whitespace and expected command
exits. The [artifact ledger](exact-pin-context-integration/evidence-files.json)
retains hashes of the implementation, report and phase evidence. These are
additive evidence records, not updates to a historical certification ledger.

## 9. Historical preservation

The [final preservation check](exact-pin-context-integration/preservation.json)
confirms **2,715 of 2,716 baseline files byte-identical**. The sole changed file
is the authorized opt-in runner integration; all other phase files are additions.
PHP 8.5 and PHP 8.6 normative artifacts, all existing
fixtures and contextual expectations, manifest/status, source pins, old observation
records, build custody evidence, and both gate inventories retain their bytes.
The [read-only audit](exact-pin-context-integration/read-only-preservation.json)
also confirms all **406 monitored evidence files** unchanged during validation.
No historical report is regenerated or silently redirected to a new executable.

## 10. Remaining blockers and readiness

PHP86-013, PHP86-014, PHP86-015, CA86-001, C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE
remain blocking with unchanged dispositions. Source/compiler discovery, intended
writable-target validity, optimized-call validation order, discarded-hook policy,
coverage and final-release evidence remain outstanding.

Bounded compiler/contextual investigations and further certification-evidence
integration can use this verified profile. Independent prerelease certification
and candidate promotion are not ready. PHP 8.6 remains draft; PHP 8.5 remains
the stable/default recommendation. No next phase was started.

## 11. Reproduction

Use a new output filename in an existing directory; reports are never overwritten:

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/context-profile.php --generate --profile exact-pin-rc4 --php86 C:/Data/GitHub/php-grammar/.audit/php86-exact/source/x64/Release/php.exe --sources86 C:/Data/GitHub/php-grammar/.audit/php86-exact/source --report docs/8.6/exact-pin-context-integration/new-exact.json
rtk proxy .audit/php85/php.exe -n tools/8.6/context-profile.php --check --profile exact-pin-rc4 --report docs/8.6/exact-pin-context-integration/new-exact.json
```

For `historical-rc4`, explicitly select `.audit/php86/php.exe` and the reviewed
source directory. The PHP 8.5 binary/source defaults remain visible in the tool;
both can be supplied explicitly. Independent checks require the retained local
build/source/toolchain dependencies, not just the JSON report.
