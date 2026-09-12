# Sources

The [Phase 5 scanner audit](8.5/phase5-lexer-audit.md) and generated
[lexical evidence ledger](8.5/phase5-lexical-evidence.json) map every one of
the 190 pinned scanner rules to states, implementation, positive/boundary
evidence, and disposition. The same source pin and SHA-256 lock remain in use.
Supporting review includes `zend_scan_escape_string`, indentation/newline and
nesting helpers, parser identifier feedback, and the halt directive.

New source-backed corrections concern enum prefix lookahead (scanner 1568–1574),
atomic yield-from and NUL-sensitive comment macros (1388–1392, 1426–1429),
removed real-cast parser-mode rejection (1664–1669), and heredoc/nowdoc labels
requiring a following byte (2729, 3000, 3124). Zend still emits `T_UNSET_CAST`
(1708–1709); surviving casts are rejected by the compiler (10472, 12366).
The recorded `false && (unset) 1;` acceptance witness belongs to Phase 6 folding
closure. Optional PHP 8.5.10 tokenizer/lint comparisons cross-check these
findings without replacing the source definition or repository lexer.

## PHP 8.5 implementation pin

The current remediation uses branch `PHP-8.5` at commit
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e` for
`Zend/zend_language_parser.y`, `Zend/zend_language_scanner.l`, and
`Zend/zend_compile.c`. File hashes and rule/function locations are recorded in
`8.5/source-inventory.json`. See `8.5/audit-remediation.md` for changes,
regressions, verification results, and unresolved discrepancies. Earlier
release-tag/RFC references below remain supporting historical evidence.

This document defines the source policy for the `php-grammar` repository.

Grammar Completeness Phase 3 uses the same implementation pin and verified
PHP 8.5.10 binary. The [positive coverage report](8.5/phase3-coverage.md)
maps syntax areas and 8.5 additions to parser/compiler families and fixtures.
The official distribution's `NEWS` entry for PHP 8.5.0 was also reviewed for
casts, closures and first-class callables in constant expressions, pipe,
clone-with, final promotion, and `(void)`. Library/API additions introduce no
extra EBNF alternatives merely because their names are new.

Grammar Completeness Phase 4 reverified the SHA-256 hashes of all three pinned
source files against `tools/php85-source-lock.json`. Its
[negative-boundary ledger](8.5/negative-boundaries.md) records parser/scanner
families or compiler functions for each pair and each contextual review.
PHP 8.5.10 lint independently checks the fixtures without executing them.

Particularly relevant pinned evidence:

- [Parser precedence and expression syntax](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y#L64): equality/relational nonassociativity; argument, variable, type, hook, adaptation and statement productions elsewhere in the same file.
- [Argument ordering](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c#L3717): `zend_compile_args` checks positional/named/unpack order after parsing.
- [Ternary chains](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c#L10505): `zend_compile_conditional` checks full/mixed unparenthesized chains.
- [Constant folding order](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c#L11634): `zend_const_expr_to_zval` calls `zend_eval_const_expr` before `zend_compile_const_expr`.
- [Enum backing validation](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c#L9221): `enum_backing_type` parses `type_expr`; int/string legality is checked when the declaration is compiled.
- [Trait alias validation](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c#L8993): static/abstract are rejected by `zend_check_trait_alias_modifiers`, called from `zend_compile_trait_alias`; readonly is rejected by parser modifier conversion.
- [Long-tag boundaries](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l#L2309): `<?phpX` falls back to a short PHP tag only when enabled; disabled short tags leave the sequence in HTML.

The two discarded-declaration gaps remain source-confirmed contextual ordering
discrepancies. Their live rejection fixtures do not prove that EBNF rejection
before folding is correct in a discarded closure.

The goal is to ensure that every grammar rule is based on authoritative evidence and that material grammar changes remain traceable to the PHP language sources from which they were derived.

## 1. Purpose

The grammar in this repository describes PHP language syntax.

It must therefore be compiled from reliable, primary sources wherever possible.

This document defines:

- which sources are preferred;
- how source conflicts are handled;
- how grammar changes should be traced to evidence;
- how implementation behavior should be distinguished from intended language syntax;
- how upstream material may be referenced without unnecessarily copying it.

## 2. Source Priority

When determining PHP syntax, prefer sources in approximately this order:

1. PHP parser grammar and relevant source code for the target PHP version;
2. accepted PHP RFCs that define syntax changes;
3. official PHP language documentation;
4. official PHP migration guides and release documentation;
5. official PHP parser, language, and conformance tests;
6. PHP issue tracker discussions and implementation commits where needed to resolve ambiguity;
7. secondary sources only as supporting evidence.

Primary sources should take precedence over tutorials, blog posts, generated references, or third-party parser grammars.

## 3. PHP Source Grammar

The PHP parser grammar is one of the strongest sources for determining syntax accepted by a specific PHP release.

When consulting PHP source:

- use the source corresponding to the PHP version being documented;
- identify the relevant parser or lexer rules;
- distinguish parser implementation details from abstract language grammar;
- do not mechanically copy parser-generator syntax into canonical EBNF;
- translate implementation-specific constructs into the repository's canonical EBNF notation.

Implementation grammar may contain:

- parser-generator directives;
- precedence declarations;
- semantic actions;
- internal token names;
- conflict-resolution techniques;
- implementation-specific helper productions.

These should not be copied into canonical EBNF unless they represent actual language syntax.

## 4. PHP RFCs

Accepted PHP RFCs are important sources for syntax introduced or changed in a PHP version.

When using an RFC:

- confirm that it was accepted and implemented;
- confirm the PHP version in which it became effective;
- verify the final implementation where practical;
- do not assume that early proposal syntax exactly matches the final shipped syntax.

RFC examples are informative evidence, but the shipped parser behavior remains important when determining the exact grammar.

Rejected, withdrawn, superseded, or draft RFCs must not be treated as defining PHP syntax.

## 5. Official PHP Documentation

Official PHP documentation is an important human-readable source for language syntax.

Use it to:

- understand intended syntax;
- identify language constructs;
- verify examples;
- clarify terminology;
- cross-check parser-derived grammar.

Documentation may lag behind implementation or simplify syntax for readability.

Where documentation and the shipped parser disagree, investigate rather than automatically choosing either source.

## 6. Migration Guides and Release Documentation

Official migration guides and release notes are useful for identifying syntax changes between PHP versions.

They are particularly valuable when adding a new grammar version.

Use them to identify:

- new syntax;
- removed syntax;
- deprecated syntax where parsing behavior changes;
- keyword changes;
- operator changes;
- declaration changes;
- type syntax changes.

Migration guides are discovery aids and should normally be verified against the parser, RFC, or tests before changing canonical grammar.

## 7. PHP Tests

Official PHP tests can provide strong evidence for accepted and rejected syntax.

Tests are especially useful for:

- edge cases;
- parser ambiguities;
- contextual keywords;
- invalid syntax;
- precedence behavior;
- syntax introduced by a particular feature;
- historical behavior.

Where possible, use tests corresponding to the PHP version being documented.

A passing parser test may demonstrate accepted syntax, but test intent and version context should still be considered.

## 8. Issues and Commits

PHP issue tracker discussions and implementation commits may be used when authoritative sources are ambiguous or contradictory.

They can help determine:

- whether parser behavior is intentional;
- whether a syntax difference is a bug;
- when a syntax correction was introduced;
- whether documentation is outdated;
- whether a patch release changed parsing behavior.

These sources should generally support, rather than replace, primary language evidence.

## 9. Secondary Sources

Secondary sources include:

- tutorials;
- books;
- blog posts;
- Stack Overflow answers;
- third-party parser implementations;
- syntax highlighters;
- IDE grammars;
- language-server grammars.

These may be useful for discovery or comparison but should not normally be used as the sole authority for canonical grammar.

If a secondary source reveals a possible grammar issue, verify it against primary PHP sources.

## 10. Third-Party Grammars

Do not treat another PHP grammar implementation as authoritative merely because it is widely used.

Third-party grammars may:

- intentionally support only a subset of PHP;
- accept non-standard syntax;
- lag behind PHP releases;
- contain parser-specific transformations;
- include implementation bugs;
- be licensed under terms incompatible with this repository.

Use such grammars for comparison only unless their provenance and licensing have been reviewed.

## 11. Source Conflicts

When sources disagree, do not guess.

Investigate the disagreement.

A typical resolution process is:

1. confirm the target PHP version;
2. inspect the PHP parser grammar for that version;
3. inspect the relevant RFC, if one exists;
4. inspect official documentation;
5. inspect relevant PHP tests;
6. inspect implementation history or issue discussions if necessary;
7. determine whether the difference concerns syntax, semantics, documentation, or an implementation bug.

Document material decisions where the resolution is not obvious.

## 12. Intended Syntax vs Parser Bugs

The grammar should normally describe the intended PHP language syntax for the target major.minor release.

A parser bug in one patch release does not automatically justify creating a separate grammar version.

When behavior differs between patch releases, determine whether the difference is:

- an intentional language syntax change;
- a parser bug;
- a parser bug fix;
- a documentation correction;
- an implementation detail.

Follow the policy in `docs/versioning.md` when deciding whether patch-specific handling is required.

## 13. Syntax vs Semantics

Not every PHP compile-time error is a grammar error.

A construct may be syntactically valid but rejected later during semantic analysis.

When researching a rule, distinguish between:

- lexical rejection;
- parser rejection;
- compile-time semantic rejection;
- runtime failure.

Canonical EBNF should describe syntax, not unrelated semantic restrictions.

For PHP conformance in this repository, canonical grammar targets valid PHP
language syntax for the selected major/minor release. A form accepted by
`zend_language_parser.y` before later contextual validation is not automatically
valid source syntax. When the upstream parser and valid-language boundary differ,
record whether the repository fixed the EBNF, fixed lexer/tokenization, fixed
the conformance adapter, or documented an explicit contextual constraint.

## 14. Recording Sources

Material grammar changes should be traceable.

Where practical, record relevant source references in:

- the corresponding human-readable grammar documentation;
- commit messages;
- pull request descriptions;
- changelog entries;
- source annotations or notes when a rule is unusually subtle.

A source record should identify enough information to find the evidence again.

Useful details include:

- PHP version;
- RFC title;
- PHP source file;
- relevant production or token;
- test name or path;
- issue or commit identifier;
- documentation section.

## 15. Per-Version Source Notes

Version-specific grammar documentation may include a source section summarizing the main evidence used for that PHP release.

For example:

```markdown
## Sources

- PHP 8.5 parser grammar
- Accepted RFC: Pipe Operator v3
- PHP 8.5 migration guide
- Relevant upstream parser tests
```

Do not turn source sections into exhaustive bibliographies unless doing so improves traceability.

## 16. Source URLs

Prefer stable upstream URLs when recording references.

Where possible, link to:

- versioned PHP source;
- accepted RFC pages;
- official manual pages;
- version-specific migration documentation;
- permanent commit references.

Avoid relying only on links that point to a moving development branch when documenting historical grammar.

## 17. Copying Upstream Material

The purpose of this repository is to provide an independently maintained canonical EBNF representation of PHP syntax.

Do not copy large blocks of upstream prose or parser grammar unnecessarily.

Instead:

1. study the authoritative source;
2. identify the syntax being defined;
3. express that syntax using this repository's EBNF conventions;
4. record the upstream source for traceability.

Short identifiers, keywords, production concepts, and language syntax necessarily overlap with PHP itself.

Substantial verbatim copying should be avoided unless its licensing implications have been reviewed.

## 18. Licensing and Provenance

Source provenance and repository licensing are separate concerns.

Referencing an upstream source does not mean that its text should be copied into this repository.

Before importing substantial material from another project:

- inspect its license;
- determine whether reuse is compatible with this repository;
- preserve required notices where applicable;
- prefer independent re-expression where practical.

The repository should maintain a clear distinction between describing PHP syntax and redistributing PHP documentation or implementation source.

## 19. Unsupported or Unclear Syntax

If a grammar question cannot be resolved confidently:

- do not invent a production;
- leave the existing grammar unchanged where safer;
- record the unresolved question;
- identify the conflicting or incomplete sources;
- add a test or research note where useful.

Correctness is more important than apparent completeness.

## 20. Source Review for New PHP Versions

When adding a new PHP major.minor grammar version, review at least:

1. the parser grammar for that PHP release;
2. accepted RFCs targeting that release;
3. the official migration guide;
4. relevant lexer changes;
5. relevant parser tests;
6. syntax-affecting release notes.

Compare the new grammar against the previous PHP version and account for every intentional syntax difference.

When a syntax difference is version-specific, add a version-boundary fixture
where practical. Boundary metadata should identify the feature, first supported
version, optional last supported version, fixture source, and authoritative
reference such as a PHP RFC, parser grammar, scanner source, migration guide, or
official test.

## 21. Source Review for Grammar Corrections

When correcting an existing grammar:

1. identify the exact incorrect production;
2. identify the PHP version affected;
3. gather authoritative evidence;
4. determine whether the issue affects syntax or semantics;
5. update the EBNF;
6. update the Markdown documentation;
7. add regression fixtures where practical;
8. record the source of the correction.

## 22. Evidence Standard

The stronger or more disruptive a grammar change is, the stronger the supporting evidence should be.

For example:

- correcting punctuation in an obvious production may require little investigation;
- adding a new syntactic form should be supported by primary sources;
- changing precedence or associativity should be verified carefully;
- introducing patch-specific grammar behavior requires strong evidence;
- removing previously accepted syntax requires especially careful validation.

## 23. Canonical Principle

The source policy of this repository is:

> Canonical grammar must be derived from authoritative PHP language evidence, expressed independently using this repository's EBNF conventions, and remain traceable to the sources used to establish correctness.

## Phase 6 parser/compiler closure evidence

The source pin remains `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.
[The reconciliation ledger](8.5/phase6-reconciliation.json) records all 177
parser productions, 623 alternatives/actions, canonical anchors and all 244
direct compiler fatal sites. `tools/php85-reconcile.py` verifies the three
pinned hashes before generating or checking the ledger.

[Six reviewed PHPT regressions](8.5/phase6-upstream-tests.json) include pinned
paths, URLs, SHA-256 hashes and minimized local negative/repair fixtures.
They cover removed unset casts, duplicate asymmetric visibility, abstract/final
hooks, nonstatic/capturing constant closures and empty coalesce dimensions.
The test executable is still PHP 8.5.10 rather than a build of the exact pin;
[Phase 6](8.5/phase6-conformance-closure.md) records this as blocker C4.
