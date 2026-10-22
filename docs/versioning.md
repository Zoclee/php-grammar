# Versioning

This document defines how the `php-grammar` repository versions PHP grammar specifications and how those versions relate to PHP releases and repository releases.

## 1. Grammar Versions

Grammar versions correspond to PHP **major.minor** language releases.

Examples:

```text
8.5
8.6
9.0
9.1
```

Each supported PHP language version has its own directory:

```text
grammar/<major.minor>/
```

Supported versions are declared in the repository manifest:

```text
php-grammar.json
```

The manifest is the source of truth for version discovery, grammar loading,
conformance fixture discovery, lexer configuration, and version-boundary tests.
Adding a grammar directory without adding a manifest entry is incomplete.
Adding a manifest version without its required package files is invalid.

For example, future repositories may contain packages such as:

```text
grammar/<major.minor>/
```

Each directory contains the canonical grammar and its human-readable representation:

```text
grammar/8.5/php.ebnf
grammar/8.5/php.md
```

## 2. Patch Releases

PHP patch releases do not normally receive separate grammar directories.

For example, these should not normally exist:

```text
grammar/8.5.1/
grammar/8.5.2/
grammar/8.5.3/
```

Patch releases generally contain bug fixes and implementation corrections rather than intentional changes to PHP language syntax.

A patch-specific grammar should only be introduced when the accepted syntax of PHP materially differs between patch releases and that difference cannot be represented accurately by the existing major.minor grammar.

Before introducing patch-specific handling:

1. verify the behavior against authoritative PHP sources;
2. determine whether the difference is an actual language syntax change or only a parser, documentation, or implementation bug;
3. document the evidence and rationale;
4. prefer keeping a single major.minor grammar whenever possible.

Patch-specific grammar versions should be exceptional.

## 3. New Major or Minor PHP Releases

Create a new grammar directory for each PHP release that introduces a new major or minor language version.

For example:

```text
grammar/<new-major.minor>/
```

When adding a new version:

1. record the repository baseline commit and begin with exact, byte-for-byte
   copies of the immediately preceding supported version's `php.ebnf` and `php.md`;
2. identify all syntax changes introduced by the new PHP version;
3. verify those changes against authoritative sources;
4. update the canonical `.ebnf` grammar;
5. update the corresponding `.md` documentation;
6. add or update conformance fixtures;
7. add the version package to `php-grammar.json`;
8. add version-boundary fixtures for syntax introduced or removed in the new version;
9. document every material difference in `changes.md` using section 15.

Preserve unchanged productions and documentation. Apply only incremental changes
justified by the pinned PHP implementation; avoid unrelated restructuring,
renaming, formatting, and simplification. Do not regenerate or rewrite a whole
grammar to introduce incremental syntax. Synchronizing existing generated
documentation sections after reviewed edits remains appropriate.

Every new version after PHP 8.5 must provide three files:

```text
grammar/<major.minor>/php.ebnf
grammar/<major.minor>/php.md
grammar/<major.minor>/changes.md
```

This includes the future PHP 8.6 package; it does not register or create it now.
PHP 8.5 is exempt from retroactive `changes.md` creation. Both grammar and
specification remain complete and standalone; neither may depend on the preceding
version or on `changes.md` to reconstruct its accepted syntax.

For an upgrade, preserve the preceding version as an immutable historical
baseline. Introducing PHP 8.6 must leave PHP 8.5's `php.ebnf`, `php.md`, fixtures,
and conformance evidence unchanged. Compare them with the recorded repository
baseline before completion. Document unrelated historical defects separately;
section 4 permits evidenced corrections in a separate change, not silent repairs
within an upgrade. Keep existing version-boundary tests passing and preserve all
release gates.

Do not assume that only headline language features affect the grammar.

## 4. Historical Grammar Corrections

Published grammar files may be corrected when they are demonstrably inaccurate.

For example, if:

```text
grammar/8.5/php.ebnf
```

incorrectly rejects syntax that PHP 8.5 accepts, the PHP 8.5 grammar should be corrected.

Such a correction does not create a new PHP grammar version.

The directory remains:

```text
grammar/8.5/
```

A correction should:

1. be verified against the relevant PHP version;
2. include authoritative evidence where practical;
3. update both `.ebnf` and `.md` representations;
4. include regression coverage where practical;
5. be described in the repository changelog or release notes when material.

Historical grammar must describe the language version it represents, not the behavior of newer PHP versions.

## 5. PHP Version vs Repository Version

PHP grammar versions and repository release versions are independent.

PHP grammar versions identify the language being described:

```text
<major.minor>
```

Repository versions identify releases of the `php-grammar` project itself:

```text
v0.1.0
v0.2.0
v1.0.0
```

A repository release may contain:

- corrections to existing grammar versions;
- new PHP grammar versions;
- documentation improvements;
- test additions;
- tooling changes;
- formatting or structural improvements.

A repository release number must not be interpreted as a PHP version.

## 6. Repository Release Versioning

The repository should use Semantic Versioning for project releases where practical:

```text
MAJOR.MINOR.PATCH
```

Examples:

```text
v0.1.0
v0.2.0
v1.0.0
v1.1.0
v1.1.1
```

General guidance:

- **MAJOR**: breaking changes to published grammar interfaces, conventions, file layout, or tooling contracts.
- **MINOR**: new PHP grammar versions, substantial new capabilities, or backward-compatible additions.
- **PATCH**: corrections, documentation fixes, test improvements, and backward-compatible grammar fixes.

While the project remains pre-1.0, breaking changes may occur more frequently, but they should still be documented clearly.

The [public API contract](api.md) makes these rules concrete: production
rename/removal, incompatible manifest schemas, and public signature changes are
breaking changes and require migration guidance. Additive public APIs, optional
manifest capabilities, and new PHP grammar versions are minor changes.
Documentation/evidence corrections, non-breaking descriptive metadata updates,
and internal performance improvements are patch changes. Schema identifiers are
independent of package releases and PHP language versions; retain a published
schema when introducing an incompatible successor.

## 7. Grammar Compatibility

A grammar version represents the syntax of one PHP major.minor language version.

Compatibility between PHP versions must not be assumed.

For example, one version package:

```text
grammar/8.5/php.ebnf
```

may accept syntax that:

```text
grammar/<another-major.minor>/php.ebnf
```

must reject.

Likewise, syntax removed or restricted by a later PHP version must not be retroactively removed from older grammar versions.

Each grammar must remain historically accurate.

## 8. Syntax Introduced Mid-Cycle

If PHP appears to accept new syntax in a patch release, investigate before changing the versioning model.

Possible causes include:

- a parser bug fix;
- previously intended but incorrectly rejected syntax;
- documentation correction;
- implementation behavior that was already part of the intended language;
- an exceptional syntax change in a patch release.

The default position is that the major.minor grammar represents the intended language for that release line.

Only create patch-specific distinctions when they are necessary for correctness and supported by strong evidence.

## 9. Version Status

Status labels must use the evidence definitions in
[conformance-policy.md](conformance-policy.md). A stable grammar package does
not imply a complete compiler validator or exhaustive equivalence.

The repository may classify grammar versions by status where useful.

Suggested statuses are:

- **draft** — incomplete or under active construction;
- **candidate** — believed complete but still undergoing validation;
- **stable** — considered complete and suitable for general consumption;
- **historical** — retained for reference but no longer actively developed.

Status should not change the grammar's version identifier.

For example, PHP 8.5 remains:

```text
8.5
```

whether its repository status is draft, stable, or historical.

## 10. Latest Grammar

The repository should avoid using an unversioned grammar as the only canonical representation.

Consumers should reference an explicit PHP version:

```text
grammar/8.5/php.ebnf
```

If a convenience alias such as `latest` is introduced, it must resolve to a specific PHP major.minor grammar and must not replace versioned paths.

Machine consumers should prefer explicit versions for reproducibility.

## 11. Git Tags

Git tags identify repository releases, not PHP releases.

Examples:

```text
v0.1.0
v0.2.0
v1.0.0
```

Do not use PHP version numbers alone as repository release tags unless the project later adopts an explicitly documented alternative release model.

## 12. Version Comparison

When documenting differences between PHP grammar versions, describe only syntax-level changes.

Useful categories include:

- added productions;
- removed productions;
- changed alternatives;
- new keywords;
- changed contextual keyword behavior;
- new operators;
- changed expression precedence or associativity;
- new declaration forms;
- changed lexical rules.

Runtime, semantic, or behavioral changes should not be presented as grammar differences unless they affect accepted source syntax.

## 13. Canonical Rule

The core versioning rule of this repository is:

> A grammar version identifies a PHP major.minor language version, while repository releases identify revisions of this grammar project.

This distinction must remain clear in directory names, documentation, changelogs, tooling, and releases.

## 14. Evidence required for a mature new version

Keep version-specific tooling and source locks under `tools/<major.minor>/`,
such as `tools/8.5/`. Shared release orchestration remains directly under `tools/`.

New versions inherit the testing methodology, never a dependency on another
grammar. Each version must independently supply a complete EBNF, matching
Markdown specification, immutable upstream source mapping, lexer configuration,
positive fixtures, structural and contextual negatives, scanner evidence,
classified grammar coverage ledger, version-boundary fixtures and a conformance
report. Use the final evidence schema's nine categories and individual blocker
dispositions; adapt generators to that version's source and actual audit totals.
Do not copy PHP 8.5's counts or certification conclusion.

Register the package in `php-grammar.json`; preserve standalone loading and run
repository consistency and version-boundary tests. The current single-version
manifest remains valid: Phase 7 adds evidence alongside it without changing the
manifest schema or inventing PHP 8.6/8.7 packages. Before another version is
called mature, implement its release runner and certify its independent source
pin, coverage classifications, boundary behavior and limitations under the
[regression policy](conformance-policy.md).

## 15. Required changes.md format

Use the following headings, in order, in each new version's `changes.md`:

```markdown
# PHP <version> grammar changes
## 1. Overview and comparison baseline
## 2. Added syntax
## 3. Modified syntax
## 4. Removed syntax
## 5. Lexical and tokenization changes
## 6. Contextual and compiler-enforced restrictions
## 7. Compatibility and unchanged syntax
## 8. Authoritative source references
## 9. Conformance and regression evidence
## 10. Known limitations and outstanding uncertainties
```

Retain all sections. State when a category has no verified changes, and
distinguish that finding from an area still awaiting review. The overview must
identify the immediately preceding supported version, the repository baseline
commit, both PHP source revisions, and the new grammar's development/release
status and audited source profile.

Give each material change a stable identifier and record:

- affected production names or language constructs;
- previous behavior and new behavior;
- affected layers: `php.ebnf`, `php.md`, lexer/tokenization, or contextual
  validation, including whether contextual enforcement is implemented;
- authoritative source locations at the immutable PHP commit, with supporting
  upstream tests and implemented RFC references where applicable;
- corresponding local conformance/regression fixture paths, version-boundary
  evidence, and validation results (or an explicit outstanding test obligation).

Entries may cross-reference a shared source or evidence section without
duplicating records. Cover contextual changes even when EBNF is unchanged.
Classify cosmetic or representation-only differences separately from PHP language
changes and justify any necessary production rename or structural rewrite.
Compatibility claims must name the regression evidence, not assume inheritance.
Record excluded proposals and changes without syntax implications with their
dispositions. Unresolved discrepancies must identify evidence, affected syntax,
risk, release impact, and remaining work under the conformance policy.

`changes.md` is explanatory, non-canonical documentation. Put the complete new
version's rules in its own `php.ebnf` and `php.md`.

## 16. Prerelease and final-release review

Use manifest schema 1.0's existing `status`, `phpSource.branch`,
`phpSource.commit`, `sourceProfile`, and `metadata` fields. No new schema or
status enumeration is needed. A future package may link `changes.md` through
`metadata.changes`; the conventional path is still required by this policy.
Registration occurs when the implementation supplies the required package,
fixtures, and lexer configuration, not during governance or source discovery.

For an unfinished version use `draft`; use `candidate` only when believed
complete but awaiting validation. Record the PHP development or prerelease
identifier explicitly in the specification and change document. Do not equate
a grammar's candidate status with an official PHP release candidate.

Record the actual upstream development/release branch and full immutable commit
used for verification. Do not assume a `PHP-8.6` branch exists or use a moving
branch tip as reproducible evidence. Keep source locks and hashes under
`tools/<version>/` and evidence under `docs/<version>/`, following existing
conventions. Record the interpreter version/build, configuration, and its
correspondence to the pin; disclose any difference from an exact-pin executable.

For each subsequent PHP release candidate, compare its immutable source revision
with the last reviewed pin, including scanner, parser, relevant compiler code,
and tests. Record the review even if no syntax changed. Incorporate verified
changes incrementally into the new version only, update its change/source/test
evidence and locks, and rerun applicable gates. Preserve prior review results
and historical version evidence; do not rewrite the grammar wholesale.

Before claiming compatibility with a final PHP release, record its exact release
identifier and immutable source revision, reconcile differences from the last
candidate, verify source/executable correspondence, and run the version's full
release gates and differential tests against that release. All material changes
must be accounted for, with remaining discrepancies explicitly disposed under
[conformance-policy.md](conformance-policy.md). Promote to `stable` only when
the evidence supports that policy's bounded claim. Neither prerelease results
nor stable status establish exhaustive equivalence or exact-executable
certification.

## 17. Change traceability validation contract

The following is a requirement for the implementation phase; comparison tooling
is not implemented in PHP 8.6 Phase 1. Extend the existing PHP EBNF parser,
validation, and reporting infrastructure through `php tools/<name>.php` entry
points rather than introducing another framework or runtime.

The comparison must take the recorded preceding-version baseline, the new
standalone package, and its `changes.md` records. Compare parsed productions by
name and structure to inventory additions, removals, and modifications, including
alternatives, optionality, repetition, terminals, and non-terminal/token
references. Review textual differences as well so documentation, lexical
contracts, and contextual restrictions outside EBNF are not missed. Treat
cosmetic differences separately; structural equivalence alone cannot prove
language equivalence or completeness of prose review.

Map every material difference to a change identifier, pinned source evidence,
and regression evidence. Report unmapped differences, stale/nonexistent
production or fixture references, missing required sections, and unresolved
obligations explicitly. Renames must account for both old and new productions.
Human review must reconcile non-EBNF changes and verify that entries describe
actual implementation behavior. Unexplained material differences or missing
required evidence fail completion; documented limitations remain subject to
the existing release/blocker policy, not automatic acceptance.

Before declaring implementation complete, retain command results and evidence
for all of the following:

1. EBNF structural validity, production references, and standalone loading.
2. EBNF/Markdown production parity and consistency of normative prose.
3. Correct manifest registration, package paths, and version discovery.
4. Version-specific scanner/token contracts and parser compatibility.
5. Focused positive, structural-negative, and contextual-negative fixtures.
6. Differential checks with the appropriate PHP interpreter and declared profile.
7. Version-boundary tests for introduced, removed, or restricted syntax.
8. Previous-version regression tests and unchanged baseline artifacts/evidence.
9. Complete `changes.md` sections and traceability reconciliation.
10. Explicit unresolved discrepancy dispositions and bounded completion claims.

Use `tests/fixtures/php/<version>/` and `tests/fixtures/version-boundaries/`.
Preserve the current release runner, evidence categories, logs, freshness checks,
and blocker dispositions described in [conformance-policy.md](conformance-policy.md).
Future version runners must supply their own evidence without disabling or
weakening existing gates. Coverage must be classified; do not invent percentage
targets or copy PHP 8.5's totals or certification conclusions.
