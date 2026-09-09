# php-grammar

Versioned EBNF grammars and human-readable specifications for PHP language syntax.

php-grammar provides a complete, source-backed grammar for each supported PHP major/minor release, designed for direct use by tools and accompanied by matching Markdown documentation.

## Purpose

PHP syntax is often described through a mix of parser implementation files, manual pages, RFCs, migration guides, examples, and third-party grammars. This project brings those pieces together into a maintained grammar reference with two goals:

- provide canonical `.ebnf` grammar files for specific PHP language versions;
- provide corresponding `.md` documentation that explains the same syntax in human-readable form.

The EBNF is the authoritative artifact. Markdown documentation may add explanation, examples, and source notes, but it must not describe syntax that is absent from the canonical EBNF.

## Uses

This repository can support projects that need a clear PHP syntax reference, including:

- parser and lexer research;
- syntax highlighters and editor tooling;
- static analysis tooling;
- code formatters and refactoring tools;
- documentation generators;
- language compatibility checks;
- grammar comparisons across PHP versions;
- educational material about PHP language syntax.

The grammar describes syntax, not runtime behavior. It does not attempt to define PHP semantics, type checking, standard library behavior, framework conventions, or coding style rules.

## Repository Layout

The intended project layout is:

```text
grammar/
  8.5/
    php.ebnf
    php.md
  8.6/
    php.ebnf
    php.md
  9.0/
    php.ebnf
    php.md

docs/
  grammar-conventions.md
  sources.md
  versioning.md

src/
  Ebnf/
    Lexer.php
    Parser.php
    Validation/

tests/
  fixtures/
    ebnf/
    <version>/
      valid/
      invalid/
```

## Versioning Model

Grammar versions correspond to PHP major/minor language releases:

```text
grammar/8.5/php.ebnf
grammar/8.6/php.ebnf
grammar/9.0/php.ebnf
```

Patch releases such as `8.5.1` or `8.5.2` do not normally receive separate grammar directories. A patch-specific grammar should only be introduced when PHP syntax materially differs between patch releases and that difference is verified against authoritative sources.

Repository releases are independent of PHP language versions. A project release such as `v0.2.0` may contain new grammar versions, corrections to existing grammars, documentation updates, tooling changes, or tests.

See [docs/versioning.md](docs/versioning.md) for the full policy.

## Grammar Conventions

Canonical grammar files use a small ISO-style EBNF subset. The notation is designed to be readable, deterministic, and implementation-neutral.

Example:

```ebnf
argument-list =
    "(" ,
    [ argument , { "," , argument } ] ,
    ")" ;
```

The grammar avoids parser-generator-specific syntax, semantic actions, and implementation-only token names unless they are required to accurately describe PHP source text.

See [docs/grammar-conventions.md](docs/grammar-conventions.md) for the full notation and style guide.

## Sources and Evidence

Grammar changes should be traceable to authoritative PHP language sources. Preferred sources include:

- PHP parser and lexer source for the relevant PHP version;
- accepted PHP RFCs;
- official PHP documentation;
- official PHP migration and release documentation;
- PHP parser and language tests.

When sources disagree, the discrepancy should be investigated and documented instead of guessed around.

See [docs/sources.md](docs/sources.md) for the full source policy.

## Contributing

Contributions should preserve the core project rule: describe PHP syntax accurately, do not redesign it.

For grammar changes:

1. identify the affected PHP version;
2. verify the syntax against authoritative sources;
3. update the canonical `php.ebnf`;
4. update the corresponding `php.md`;
5. add or update focused valid and invalid fixtures where practical;
6. run available validation and tests;
7. document material source evidence.

Each versioned grammar must remain complete and standalone. Do not implement a grammar version as an overlay, diff, include, or extension of another PHP version.

## Testing

Phase 1 tests validate this repository's canonical EBNF files directly. They do
not use `php -l`, the installed PHP parser, or the local PHP version to decide
whether PHP source syntax is valid.

Install dependencies and run the PHPUnit suite:

```bash
composer install
composer test
```

The Phase 1 suite parses every `grammar/<version>/php.ebnf` file with the
project's EBNF parser and validates grammar integrity, including malformed EBNF,
duplicate productions, undefined references, unreachable productions, the
required root production, production naming, and unintended empty productions.

The PHP files under `tests/fixtures/<version>/` remain representative source
examples for later conformance phases. They are not used as an oracle for Phase
1 grammar correctness.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE).
