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

1. use the previous version as a starting point where appropriate;
2. identify all syntax changes introduced by the new PHP version;
3. verify those changes against authoritative sources;
4. update the canonical `.ebnf` grammar;
5. update the corresponding `.md` documentation;
6. add or update conformance fixtures;
7. add the version package to `php-grammar.json`;
8. add version-boundary fixtures for syntax introduced or removed in the new version;
9. document meaningful differences from the previous PHP version.

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
