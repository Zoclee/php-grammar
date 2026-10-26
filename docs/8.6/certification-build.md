# PHP 8.6 exact-pin executable preparation

October 24, 2026. Exact-pin executable verification remains **unestablished**.
The normative commit is `f23b4c222cebe33be93ee8ecaaa14390c5507a08`.
The existing RC4 Windows NTS executable is an observation oracle, not an attested
build of that commit. Its hash and selected build/runtime metadata are recorded
by `tools/8.6/certification.php --prepare` under `certification/`.

## Available environment and feasibility

Local immutable source archives and both distribution executables exist under
ignored `.audit/`. PHP 8.5.10 can run the repository suite with its mbstring DLL.
The default host PHP is 8.3.6 and is not changed. Environment discovery found
MinGW GCC and CMake, but no configured `cl`, `nmake`, Docker or Podman executable.
WSL distribution enumeration was denied in the restricted context; the owner
context returned no installed distribution names. A compiler executable alone
does not supply the supported Windows PHP SDK, MSVC toolchain and dependencies.
No viable configured isolated build environment was established, so no system
package installation, host PHP replacement or speculative build was performed.

## Reproducible isolated build preparation

Use a fresh disposable Windows SDK workspace or a provisioned Linux container;
do not compile over the audited source cache. Obtain php-src by the full immutable
commit from the official repository and verify `HEAD`, a clean working tree,
submodules (if any) and all source-lock hashes. Record the complete tree identity,
archive hash and acquisition URL. A lock covering selected syntax sources does
not attest the rest of the build tree.

For Windows, provision the upstream-supported Visual Studio C/C++ toolchain,
Windows SDK and PHP SDK binary tools/dependency bundle appropriate to RC4 x64.
Record exact versions and dependency archive digests, enter its SDK build shell,
run the pinned tree's `buildconf.bat`, and inspect `configure --help` before
selecting a CLI NTS build with required tokenizer, XML/DOM, mbstring, Phar and
test dependencies. Build with `nmake` inside the isolated workspace. The existing
NTS distribution's MSVC 2022 label is not proof of the required complete SDK.

For Linux, a reviewed immutable container image must supply a C compiler, make,
autoconf, pkg-config, bison and re2c versions required by the pinned build files,
plus development dependencies for the selected extensions (including libxml and
oniguruma where enabled). Use the pin's `buildconf`, then a recorded `configure`
invocation with an isolated prefix, CLI/tokenizer and the PHPUnit dependencies.
Do not invent exact tool versions without checking the pin's configure/build
requirements. Capture generated parser/scanner and configuration/header hashes.
Pin any ext-ast source separately before using it for AST/binding evidence.

The implementation plan is intentionally conditional on a provisioned toolchain;
it is not a claim that either build has been executed or is reproducible yet.
The existing PHP process runner can execute argument arrays and retain build
logs when those prerequisites are available; no second workflow runtime is added.

## Required attestation and differential profile

Retain source commit/tree, source acquisition digests, dirty-tree verification,
toolchain/dependency identities, exact build commands, generated source/header
hashes, configuration and extension hashes, complete stdout/stderr/exit codes,
and final executable SHA-256. Verify the executable used by differential tests
matches that build output; keep an independently reviewed attestation linking
source, build and binary. A hand-entered `sourceCommit` or verified-version boolean
is not accepted as proof by Phase 7A tooling.

Run `-n` with explicit `zend.multibyte=0`, short_open_tag profiles, error_reporting,
opcache.enable_cli=0 and opcache.jit=0. Record thread safety, architecture, integer
width, extensions and ini/configuration. Lint, native tokenization, declaration
linking and controlled execution have different validation stages. If opcache,
JIT, ZTS, encoding filters or other profiles are later claimed, they need separate
evidence. An exact-pin build still cannot close PHP86-013/014/015 or establish
final-release compatibility by itself.

After independently reviewing the build attestation, rerun differential and full
release obligations in the separately authorized certification phase. Retain the
old distributions and their results as historical observations. Do not silently
change the source pin, default executable, manifest status or blocker dispositions.
