# AGENTS.md

## Project Purpose

This repository defines canonical grammar specifications for PHP language syntax.

The primary artifacts are:

- `.ebnf` files: canonical machine-readable grammar definitions.
- `.md` files: human-readable grammar documentation corresponding to the EBNF.

The repository maintains grammar by PHP **major.minor** language version, for example `8.4`, `8.5`, and `9.0`.

Patch releases such as `8.4.1` or `8.4.2` do not normally receive separate grammar versions. A patch-specific grammar should only be introduced if the PHP language syntax itself materially differs.

## Core Principles

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

Prefer a single canonical file per PHP version:

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

If modular source files are introduced later, the repository should still provide an assembled canonical `php.ebnf` artifact for each supported PHP version.

## Human-Readable Markdown

Each:

```text
grammar/<version>/php.md
```

must describe the same grammar as:

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

1. PHP parser/source grammar used by the relevant PHP release;
2. accepted PHP RFCs defining syntax changes;
3. official PHP language documentation;
4. official PHP migration and release documentation;
5. PHP tests demonstrating parser behavior.

When sources disagree, investigate the actual parser behavior and document the discrepancy.

Do not silently copy grammar from an unrelated third-party grammar implementation.

## Adding a New PHP Version

When adding a new major/minor PHP version:

1. copy the previous version as a starting point when appropriate;
2. identify all accepted syntax changes for the new PHP version;
3. verify changes against primary sources;
4. update the canonical EBNF;
5. update the corresponding Markdown;
6. add or update valid and invalid fixtures;
7. verify existing syntax that should remain compatible;
8. document important differences from the previous PHP version.

Do not assume that a new PHP version differs only by the published headline features.

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
