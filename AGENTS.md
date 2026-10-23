# AGENTS.md

## Project Purpose

This repository defines canonical grammar specifications for PHP language syntax.

The primary artifacts are:

- `.ebnf` files: canonical machine-readable grammar definitions.
- `.md` files: human-readable grammar documentation corresponding to the EBNF.

The repository maintains grammar by PHP **major.minor** language version, for example `8.4`, `8.5`, and `9.0`.

Patch releases such as `8.4.1` or `8.4.2` do not normally receive separate grammar versions. A patch-specific grammar should only be introduced if the PHP language syntax itself materially differs.

## Core Principles

Repository tooling, tests, validation, report generation, and maintenance logic
must be implemented in PHP. Use `php tools/<name>.php` entry points and keep
substantive logic out of shell wrappers. Composer/Git and platform launchers are
infrastructure; do not introduce another language runtime for project workflows.

1. **EBNF is canonical.**
   - The `.ebnf` definition is the authoritative grammar.
   - Markdown documentation must not introduce syntax that is absent from the canonical EBNF.

2. **Describe PHP, do not redesign it.**
   - Grammar must reflect actual PHP language syntax.
   - Do not normalize, simplify, improve, or reinterpret PHP syntax merely to make the grammar cleaner.

3. **Prefer authoritative sources.**
   - Use official PHP documentation, PHP source/parser definitions, accepted PHP RFCs, release documentation, and other primary sources.
   - Avoid relying on third-party tutorials or informal descriptions when a primary source exists.

4. **Every grammar change must be traceable.**
   - Record the reason and source for material grammar changes.
   - When resolving ambiguity, document the evidence used.

5. **Do not guess.**
   - If the language behavior or syntax is unclear, investigate before changing the grammar.
   - Mark unresolved questions explicitly rather than inventing a rule.

6. **Maintain semantic parity.**
   - The `.ebnf` and `.md` files for a PHP version must describe the same accepted language syntax.

7. **Keep every version standalone.**
   - Each PHP grammar version must be complete, standalone, and independently consumable.
   - A version's canonical grammar must not depend on another version through inheritance, includes, overlays, patches, or diffs.

## Repository Structure

Expected structure:

```text
grammar/
  8.4/
    php.ebnf
    php.md
  8.5/
    php.ebnf
    php.md
  9.0/
    php.ebnf
    php.md

docs/
  grammar-conventions.md
  versioning.md
  sources.md

tests/
  fixtures/
    valid/
    invalid/

tools/
```

Do not introduce a substantially different structure without a clear maintenance or tooling benefit.

## Versioning Policy

Grammar directories correspond to PHP language versions using:

```text
major.minor
```

Examples:

```text
8.4
8.5
9.0
```

Do not create directories for normal patch releases:

```text
8.4.1
8.4.2
8.4.3
```

If a patch release appears to require a syntax-level grammar change:

1. verify the change against authoritative PHP sources;
2. determine whether it represents an actual language grammar difference or merely an implementation/documentation correction;
3. document the decision before introducing patch-specific handling.

Repository releases and Git tags are independent of PHP language versions.

## Standalone Version Grammars

Each:

```text
grammar/<version>/php.ebnf
```

must fully define the syntax for that PHP major.minor version.

Each:

```text
grammar/<version>/php.md
```

must fully document that version's grammar.

A consumer must be able to use a version grammar directly, for example:

```text
grammar/8.5/php.ebnf
```

without loading any other PHP grammar version.

Do not use version-to-version dependency constructs such as:

- `extends 8.4`
- `include ../8.4/php.ebnf`
- "same as PHP 8.4 except..."
- delta, overlay, or patch files required to reconstruct the grammar

Cross-version comparison is allowed for research, review, and change documentation, but the final canonical grammar and Markdown documentation for each version must remain self-contained.

Historical versions must remain independently accurate. Do not rewrite an older grammar merely to share structure with a newer version.

Version differences may be documented separately for convenience, but such documentation is non-canonical and must never be required to reconstruct a version's grammar.

## EBNF Requirements

Follow the EBNF dialect and conventions defined in `docs/grammar-conventions.md`.

Until that document says otherwise:

- use consistent production naming;
- keep terminals visually distinct from non-terminals;
- keep formatting deterministic;
- avoid parser-generator-specific extensions in canonical grammar;
- avoid implementation-specific semantic actions;
- represent syntax, not runtime semantics;
- keep lexical and syntactic concerns clearly separated;
- use comments only where they clarify non-obvious grammar decisions.

The canonical grammar should remain consumable by general-purpose tooling.

## Grammar Organization

Prefer a single complete canonical file per PHP version:

```text
grammar/<version>/php.ebnf
```

Organize the file internally into logical sections such as:

1. lexical grammar;
2. names and identifiers;
3. literals;
4. types;
5. expressions;
6. statements;
7. declarations;
8. classes, interfaces, traits, and enums;
9. attributes;
10. namespaces and imports;
11. other version-specific syntax.

Do not split the canonical grammar into many files unless there is a demonstrated tooling or maintenance need.

If modular source files are introduced later, the repository must still provide a complete assembled canonical `php.ebnf` artifact for each supported PHP version. The assembled artifact must not require another PHP version's grammar to be loaded.

## Human-Readable Markdown

Each:

```text
grammar/<version>/php.md
```

must fully document and describe the same grammar as:

```text
grammar/<version>/php.ebnf
```

The Markdown version may add:

- headings;
- explanations;
- examples;
- notes;
- source references;
- version-change annotations.

It must not alter the accepted syntax.

Where practical, generate or validate Markdown grammar productions against the canonical EBNF to prevent drift.

## Sources and Evidence

For grammar work, prefer sources in roughly this order:

1. official `php-src` lexer, parser, and relevant compiler implementation;
2. official release-specific source commits and tests;
3. accepted and implemented PHP RFCs;
4. official migration guides, language documentation, and release notes.

Verify every material version change against an immutable PHP source commit.
Record the branch, commit, source locations, and regression evidence following
[docs/sources.md](docs/sources.md). An RFC targeting a release is not evidence
that its syntax was implemented. Classify implemented syntax, contextual
restrictions, changes without grammar implications, unimplemented proposals,
and unresolved discrepancies separately.

When sources disagree, investigate the actual parser behavior and document the discrepancy.

Do not silently copy grammar from an unrelated third-party grammar implementation.

## Adding a New PHP Version

Provisional baseline creation (Gate A) is a separate preparatory step under
[docs/versioning.md](docs/versioning.md#31-readiness-gates). It permits only
unregistered, byte-for-byte copies with recorded baseline commit, verified
original hashes and copy equality, historical preservation, and an external
unverified-working-baseline label. Discovery may remain open; no grammar,
lexical, or contextual edit or support claim is permitted at this gate.
Such copies are not a completed version package. Gate B requires verified,
traceable findings, affected productions, and test/boundary obligations before
implementation edits. All completion and certification requirements below
remain in force. Phase 2.3 changes governance only; it does not begin Phase 3A.

When adding a new major/minor PHP version:

1. begin with byte-for-byte copies of the immediately preceding supported
   version's `php.ebnf` and `php.md`, recording the repository baseline commit;
2. identify all accepted syntax changes for the new PHP version;
3. verify changes against primary sources;
4. update the canonical EBNF so the committed result is a complete independent grammar;
5. update the corresponding Markdown so the committed result fully documents that version;
6. add or update valid and invalid fixtures;
7. verify existing syntax that should remain compatible;
8. document every material difference in `grammar/<version>/changes.md` using
   the required format and traceability contract in
   [docs/versioning.md](docs/versioning.md#15-required-changesmd-format).

Every version introduced after the existing PHP 8.5 baseline must contain
`php.ebnf`, `php.md`, and `changes.md`. PHP 8.5 does not require a retroactive
`changes.md`. The change document is explanatory and must never be required to
load or reconstruct either standalone specification artifact.

Preserve unchanged productions and documentation; apply only incremental,
implementation-backed changes. Do not rewrite or regenerate an entire grammar
for an incremental language change, or introduce unrelated restructuring,
renaming, formatting, or simplification. Existing generated sections may be
synchronized from reviewed canonical changes without unrelated churn.

The preceding version is an immutable baseline for the upgrade: keep PHP 8.5's
grammar, specification, conformance fixtures, and evidence unchanged when adding
PHP 8.6. Record unrelated historical defects separately and handle corrections
under the Grammar Corrections policy in a separate change. Preserve existing
version-boundary tests and release gates; do not disable or weaken them.

Use existing manifest `status`, `phpSource`, and `metadata` conventions for
development versions. Record the actual source branch and immutable commit;
review successive release candidates incrementally. Do not claim final-release
verification before satisfying the prerelease and final-release criteria in
[docs/versioning.md](docs/versioning.md#16-prerelease-and-final-release-review).

Before completing an implementation, demonstrate EBNF structural validity and
reference integrity, EBNF/Markdown parity, manifest registration and discovery,
version-specific lexer/parser compatibility, positive and negative fixtures,
differential validation with the appropriate PHP interpreter, version-boundary
conformance, historical regression protection, complete change traceability,
and explicit unresolved discrepancy dispositions. Preserve the evidence and
reporting requirements of [docs/conformance-policy.md](docs/conformance-policy.md).
No arbitrary coverage target or unsupported completeness claim substitutes for
that evidence.

The implementation phase must account for added, removed, and modified
productions, including alternatives, optionality, repetition, and token
references, as well as lexical and contextual changes without EBNF edits.
Extend existing PHP validation tooling according to the intended contract in
[docs/versioning.md](docs/versioning.md#17-change-traceability-validation-contract).
This contract is a future implementation requirement, not an existing automated
gate. PHP 8.6 preparation establishes policy only: do not create its package,
register it, change validation behavior, or implement comparison tooling during
Phase 1.

Do not assume that a new PHP version differs only by the published headline features.

Do not commit a new version as an extension, include, overlay, patch, or diff against an older version.

## Tests and Conformance

Grammar changes should include tests whenever practical.

Use fixtures to demonstrate:

```text
tests/fixtures/valid/
tests/fixtures/invalid/
```

Valid fixtures should cover accepted syntax.

Invalid fixtures should cover syntax that the grammar must reject.

Prefer small, focused fixtures that isolate a grammar rule.

For new syntax, include:

- a minimal valid example;
- representative variants;
- relevant boundary cases;
- at least one invalid example when meaningful.

Where tooling permits, compare grammar expectations with the parser behavior of the corresponding official PHP version.

## Change Discipline

When editing grammar:

- make the smallest change that correctly represents the language;
- avoid unrelated formatting churn;
- preserve stable production names unless renaming materially improves correctness;
- check references to renamed productions;
- update Markdown and tests in the same change;
- document compatibility-impacting grammar corrections.

A change to the grammar may affect downstream parsers and tools even when it looks editorial. Treat production renames and structural rewrites as potentially breaking changes.

## Grammar Corrections

A correction to an older PHP grammar version is allowed when the existing grammar is demonstrably wrong.

Do not change historical grammar merely to match a newer PHP version.

Do not change historical grammar merely to share structure or reduce duplication with newer versions.

For corrections:

1. confirm the syntax for the specific PHP version;
2. record the evidence;
3. update EBNF;
4. update Markdown;
5. add regression coverage.

## Style

Use clear, technical language.

Prefer precise terms such as:

- terminal;
- non-terminal;
- production;
- lexical token;
- expression;
- statement;
- declaration;
- optional;
- repetition;
- alternative.

Avoid vague wording such as "something like", "normally", or "etc." in normative grammar descriptions unless the text is explicitly informative rather than normative.

Use American English consistently unless the repository establishes another convention.

## Scope Boundaries

This repository defines PHP language syntax.

It is not primarily responsible for:

- runtime semantics;
- type-system semantics beyond syntax;
- engine implementation details that do not affect accepted syntax;
- coding style;
- formatting standards such as PSR-12;
- static-analysis rules;
- IDE behavior;
- framework-specific syntax;
- templating languages that embed PHP.

Only include such material when required to explain the grammar.

## Agent Workflow

Before making a grammar change:

1. identify the PHP version affected;
2. identify the exact syntax question;
3. inspect the existing EBNF and Markdown;
4. verify the behavior against authoritative sources;
5. determine the smallest correct grammar change.

After making the change:

1. validate EBNF syntax;
2. check all referenced productions;
3. verify EBNF/Markdown parity;
4. add or update fixtures;
5. run available tests and validation tools;
6. review the diff for accidental grammar changes;
7. summarize sources, decisions, and validation results.

## Uncertainty

If evidence is incomplete or contradictory:

- do not invent a grammar rule;
- document the unresolved point;
- cite the conflicting evidence;
- prefer leaving the current grammar unchanged until the issue can be resolved.

Correctness and traceability are more important than completing a grammar section quickly.
