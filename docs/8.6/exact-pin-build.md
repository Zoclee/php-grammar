# PHP 8.6 exact-pin interpreter build

October 25, 2026 — Phase 7C.3. **An isolated exact-source CLI build succeeded.**
PHP 8.6 remains **draft and uncertified**. No grammar, fixture expectation,
source pin, mandatory gate, or finding disposition changed.

## 1. Objective and outcome

Build an interpreter from `f23b4c222cebe33be93ee8ecaaa14390c5507a08`, verify its
source/build/output chain independently, and compare reviewed observations with
the previously used RC4 distribution and PHP 8.5. The result is a verified local
exact-source build, **not a demonstrated bit-for-bit reproducible build**.

The executable is local and ignored:
`.audit/php86-exact/source/x64/Release/php.exe`. Its required PHP core is the
adjacent `php8.dll`. Neither binary is a repository artifact or the default PHP.

## 2. Source authentication

[Authentication](exact-pin-build/authentication.json) records all **28,445**
tracked source files and **325** SDK files. Every source file was compared with
its Git blob identity before configuration and again after compilation.

| Identity | Value |
|---|---|
| Official origin | `https://github.com/php/php-src.git` |
| Commit | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| Tree | `926e6c6294bf279376a5037be5d0cf5770607608` |
| Tag | `php-8.6.0RC4` |
| Tag object | `2363aac87f03893686ee28db710ed2ca11cf701e` |
| SDK origin | `https://github.com/php/php-sdk-binary-tools.git` |
| SDK commit | `87567dececcf806c83e67fceb89a3bd206e7f1b1` |
| SDK tree | `3ed4507d50f7780bad7b6469495081befcdcfe5f` |

[Tag authentication](exact-pin-build/tag-authentication.json) confirms the
official remote's tag object and peeled commit. Git object checks passed. The
tag contains a signature, but its signing key was not independently verified.
Authentication relies on the official HTTPS remote, pinned Git identities, and
the existing source locks; it is not a claim of verified signer identity.

The existing certification lock's **235 files** agree with the checkout and
the independently reviewed source records. Git's `ident` attribute initially
expanded `$Id$` in seven files. Those files were restored directly from their
committed blobs; [before/after hashes](exact-pin-build/checkout-normalization.json)
record the normalization. No upstream source was patched. Ordinary Git diff
alone would not have detected this byte difference.

## 3. Selected build environment

[Environment inventory](exact-pin-build/environment.json): Windows 11 AMD64,
Visual Studio 2022 Community 17.12.3, MSVC toolset 14.42.34433, compiler
19.42.34435, and Windows SDK 10.0.22621.0. Docker/Podman were unavailable and
WSL had no installed distributions. Native compilation used the installed
compiler and a workspace-local official PHP SDK checkout.

No system-wide dependency, global Git setting, default PHP, or global PATH
was changed. The SDK initializes only its child process environment.

## 4. Toolchain and dependencies

The SDK reports `2.8.5-dev`; its immutable commit above is the acquisition
identity. [The initialized environment](exact-pin-build/environment-quoted.stdout.log)
reports Bison 3.3.2 and re2c 1.1.1. Compiler identity is also retained on
[stderr](exact-pin-build/environment-quoted.stderr.log). Compiler, linker,
librarian, nmake, and the complete SDK file inventory are hashed in the build
record. PHP's bundled dependencies are pinned by the authenticated source tree.
No external PHP library dependency bundle was required.

The build includes CLI/tokenizer and the core/bundled extensions selected by
the upstream configuration, including static OPcache. Differential commands
explicitly disable CLI OPcache and JIT. Windows/UCRT/VCRUNTIME remain host
dependencies, not a hermetically pinned runtime. Imported DLL names are retained
for [php.exe](exact-pin-build/runtime-dependencies-0.log) and
[php8.dll](exact-pin-build/runtime-dependencies-1.log).

## 5. Build procedure

The source and SDK occupy separate directories under `.audit/php86-exact`.
The official `phpsdk-vs17-x64.bat -t` launcher initializes the installed compiler.
The reviewed task runs `buildconf.bat`, followed by:

```text
configure.bat --disable-all --enable-cli --enable-tokenizer --disable-zts --disable-cgi --disable-phpdbg
nmake
```

[Configuration](exact-pin-build/configure-native.json) and
[compilation](exact-pin-build/compile-native.json) both exited zero. Compilation
ran from 10:45:05 to 10:47:15 UTC. The first launcher attempts failed with
Windows command-line syntax errors before compilation; their receipts and
logs remain preserved. Correct explicit `cmd /d /s /c` quoting resolved this.

The PHP-only [build controller](../../tools/8.6/exact-pin-build.php) generates
temporary batch launchers solely as Windows infrastructure, records their
exact contents, captures separate stdout/stderr, and refuses to overwrite an
evidence revision or an existing executable. Generated batch files and build
directories remain ignored. Compiler/generator warnings are retained; the
build was not warning-free. No warning was suppressed by patching upstream.

## 6. Outputs and retained logs

| Output | SHA-256 |
|---|---|
| `php.exe` | `445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6` |
| `php8.dll` | `1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f` |

The [build record](exact-pin-build/build.json) binds configuration, commands,
times, toolchain, generated parser/scanner/header files, executable and core
DLL hashes. Full logs: [configure stdout](exact-pin-build/configure-native.stdout.log),
[configure stderr](exact-pin-build/configure-native.stderr.log),
[compile stdout](exact-pin-build/compile-native.stdout.log), and
[compile stderr](exact-pin-build/compile-native.stderr.log).

## 7. Independent provenance verification

The [sealer](../../tools/8.6/exact-pin-seal.php) checked every tracked source
blob after compilation, source-lock agreement, successful command/log records,
generated files, toolchain hashes, and fresh output timestamps. It issued a
separate [custody receipt](exact-pin-build/build-receipt.json).

The read-only [verifier](../../tools/lib/ExactPinProvenance.php) checks the
receipt, fixed reviewed task/configuration, live Git identities, source files,
source lock, toolchain, generated files, binary/core DLL, and both log streams.
Both the [standalone check](exact-pin-build/final-provenance.log) and the
[certification runner's opt-in inspection](exact-pin-build/final-runner-provenance.log)
passed. Passing a prior distribution instead is
[rejected](exact-pin-build/final-distribution-refusal.log).

This is local custody evidence under a trusted controller and host. Hashes
detect alteration; they do not authenticate a malicious host or coordinated
forgery of records and receipts. No external build attestation, signing-key
validation, clean-machine reproduction, or second independent build is claimed.

## 8. Basic interpreter checks

The executable reports PHP 8.6.0RC4, NTS, Visual C++ 2022 x64. Retained checks
cover version/configuration fields without persisting arbitrary environment
variables, both short-tag tokenization profiles, ordinary function/class
declarations, a PFA runtime result of `5`, valid and invalid lint inputs, and
reviewed contextual constructs. Every comparison preserves its command, exit,
stdout, stderr, fixture identity, and validation stage.

## 9. Differential results

[Final comparison](exact-pin-build/differential-final.json): **422 cases,
1,621 checks, zero failures, zero stage-result differences** between the
exact-source interpreter and the earlier RC4 distribution. Each case is
observed with PHP 8.5, the prior RC4 distribution, and the new build.

The matrix includes PHP86-001, PHP86-003 through PHP86-007, PHP86-010,
inherited discarded hooks, **36 version-boundary fixtures**, and **81** focused
compiler probes: 26 writable-target, 31 traversal, 20 predicate, and four
linking cases. These explicitly retain PHP86-014/015 observations. Runtime
execution is limited to reviewed fixtures and bounded basic examples; upstream
PHPT suites were not executed indiscriminately.

Structural-only boundary expectations are not treated as compiler-acceptance
expectations. Lint, declaration linking, controlled runtime, warnings, and
invalid-opcode observations remain distinct. The earlier
[comparison](exact-pin-build/differential.json) is preserved as a checkpoint;
the final revision adds controller hashes and explicit runtime-output assertions.

## 10. Existing versus exact-pin observations

The new compiler generation is Visual C++ 2022; the previous RC4 distribution
reports Visual C++ 2026. Their selected stage results nevertheless agree.
Observed acceptance, rejection, diagnostics, runtime output, and known invalid
opcode outcomes do not establish intended language semantics. No expectation,
grammar rule, or finding disposition was changed to force agreement.

## 11. Validation and preservation

The full suite passed **6,173 tests / 58,207 assertions**. Provenance unit
coverage passed **four tests / 18 assertions**, with a further
[nine rejected provenance scenarios](exact-pin-build/provenance-negatives.json).
These cover a wrong commit, matching version string alone, altered/substituted
executable, missing log, mismatched lock, unreviewed configuration/task, missing
toolchain, and missing source correspondence. Positive provenance was verified
against the actual build, not a fabricated executable fixture.

[Final command receipts](exact-pin-build/final-validation.json) retain source,
manifest, historical-integrity, provenance, claims-refusal, and whitespace
checks. Historical authentication still verifies all **1,545** original inputs;
it does not retroactively attest historical execution. The new
[current-compatibility revision](../8.5/compatibility/phase7c3/evidence.json)
passed all **15 commands** against **3,908 dependency identities**, with inputs
unchanged during generation. Its independent check passed; the hybrid claims
check separately reports historical integrity and current compatibility true
while refusing certification. [Artifact checks](exact-pin-build/artifact-validation.json)
also passed PHP syntax, JSON integrity, local links, and whitespace validation.
This revision preserves the earlier revisions. Older current-compatibility snapshots become
stale when tooling changes; their bytes and historical meaning remain intact.

[Preservation](exact-pin-build/preservation.json): **2,456 of 2,458** baseline
files are unchanged. The only changes are additive opt-in provenance dispatch
in the existing PHP 8.6 runner and helper. Both versions' normative grammars,
specifications, fixtures, source pins, manifest/status, all historical PHP 8.5
certification evidence, and all preexisting observation files remain unchanged.
Legacy and successor gate inventories and default claims behavior are preserved.

## 12. Certification implications and readiness

The *missing exact-source executable* prerequisite can now be reconsidered using
this evidence. **C6-PROVENANCE is not automatically closed**, and no mandatory
gate was silently changed from blocked to passed. Existing claims still refuse
certification. PHP86-013, PHP86-014, PHP86-015, CA86-001, C6-CONTEXT, and
C6-COVERAGE remain open with their prior classifications.

| Follow-up | Readiness |
|---|---|
| PHP86-013/014/015 investigation | Exact-source observations are available; semantic/source adjudication remains required. |
| Additional contextual evidence | The verified executable can be selected explicitly for reviewed probes. |
| Independent prerelease certification | Not ready: mandatory discovery, contextual, coverage and discrepancy obligations remain. |
| Candidate promotion | Not ready; status remains draft. |

No remediation, certification execution, or promotion phase was started.

## 13. Reproduction and verification instructions

For an independent rebuild, use a fresh isolated workspace and the exact source
and SDK commits above, not either moving branch. Obtain the official RC4 tag
with Git checkout conversion disabled (`core.autocrlf=false`). Normalize its
seven `ident` files to raw committed blobs as recorded above, and verify all
source bytes and the 235-file source lock before building. Use the recorded
compiler/SDK versions and the official SDK task launcher with the exact
configuration and `nmake` procedure in section 5. Preserve new command logs,
generated-file hashes, and output hashes in a new evidence revision. A new
build requires a new custody record; do not reuse this build's receipt or claim
binary reproducibility from matching version text. No second rebuild was run.

For this retained build, use explicit paths (prefix commands with `rtk proxy`
under the repository's agent workflow):

```text
.audit/php85/php.exe -n tools/8.6/exact-pin-check.php
.audit/php85/php.exe -n tools/8.6/certification.php --provenance --php86 .audit/php86-exact/source/x64/Release/php.exe
.audit/php85/php.exe -n tools/8.6/exact-pin-differential.php --report docs/8.6/exact-pin-build/new-comparison.json
.audit/php85/php.exe -n tools/evidence/compatibility.php --check --report docs/8.5/compatibility/phase7c3/evidence.json
.audit/php85/php.exe -n tools/8.6/certification.php --claims
```

The final command must refuse certification. Evidence checks require the local
source/toolchain/runtime artifacts; their absence is an explicit failure, not
permission to trust the committed metadata alone. The build controller and
sealer are intentionally scoped to this recorded Windows workspace layout;
they do not provision system dependencies or claim a portable hermetic build.
