# PHP 8.5 conformance

The target is valid PHP 8.5 source, with three required layers:

1. The lexical/source contract converts bytes and scanner states to tokens.
2. The standalone EBNF recognizes syntactic token sequences.
3. Contextual constraints exclude constructs rejected during Zend compilation.

The normative contract and full EBNF appear in `grammar/8.5/php.md`.
Structural acceptance alone is not a conformance verdict. The repository
implements the first two layers with a PHP lexer, token adapter, and Earley
chart recognizer. It does **not** yet implement a complete contextual validator
or expose parse trees for precedence comparison.

## Reproducible checks

```text
composer test
php bin/php85-conformance.php /path/to/php-8.5
python tools/fetch-php85-sources.py .audit
python tools/php85-source-inventory.py .audit
```

`PHP85_BINARY` can supply the binary when the lint command has no argument.
The command requires PHP 8.5.x and uses `-n`, `short_open_tag=1`, and
`zend.multibyte=0`. The `short-tags-disabled` subcorpus additionally uses
`short_open_tag=0` in both engines. It calls `php -l` without executing fixtures.
Deprecations and warnings do not constitute rejection; a nonzero lint exit
status does. EBNF results are obtained independently of PHP's parser/tokenizer.

The shared corpus is under `tests/fixtures/php/8.5/`:

| Directory | EBNF expectation | PHP lint expectation |
|---|---|---|
| `valid` | Accept | Accept |
| `invalid` | Reject | Reject |
| `contextual-invalid` | Accept structurally | Reject during compilation |

The third category measures the boundary of the missing contextual
implementation; it is not counted as rejection by EBNF. Contextual constraints
that are economical to encode, such as nonempty hook blocks, already have
structural restrictions and regressions in `invalid`.

Lint does not resolve every autoloaded symbol, deferred constant value,
attribute class, or inheritance relationship. Its successful result does not
prove runtime validity or all environment-dependent contextual constraints.

## Token contract

Opening tags are skipped, `<?=` emits `echo`, and `?>` emits `;`. Inline HTML
remains a statement token. One construct can span many PHP regions. Recognized
PHP source cannot fall back to HTML to avoid a syntax error. `<?php` needs space,
tab, newline, or EOF, not merely a non-identifier boundary. Short tags depend on
configuration.

Names containing backslashes are atomic tokens: trivia removal cannot turn
`Foo \ Bar` into `Foo\Bar`. Variables, ordinary identifiers, semi-reserved
identifiers, numeric subclasses, and strings use distinct primitive matchers.
Casts normalize case and horizontal whitespace. Literal token matching never
joins multiple tokens to synthesize one token.

The chart recognizer supports nullable and indirectly left-recursive EBNF,
needed for Zend's dereferencing categories. The original generic recursive
matcher remains available for byte-level and small-rule tests.

## Integrity, parity, and coverage

Repository tests check EBNF parsing, duplicate definitions, references against
the declared primitive registry, root reachability, and allowed empty rules.
The parity test compares the full Markdown grammar block with EBNF.
Intentional empty lists are not empty lexical tokens: whitespace, identifiers,
numeric tokens, and text tokens must consume input.

`composer grammar:coverage` reports production and alternative traversal.
Successful structural paths do not prove contextual validity or unique AST
grouping. Lexical primitives are exercised through lexer and differential
fixtures. Coverage has no minimum threshold.

See `docs/php85-audit-remediation.md` for remaining discrepancies. Source
inventory coverage is not an exhaustive production-by-production equivalence
proof.

## Systematic positive coverage (Grammar Completeness Phase 3)

The work queue is the actual `composer grammar:coverage` output. A production
hit establishes that some form completed; alternatives measure distinct choices
inside that production, including nested groups. Optional presence/absence and
repetition cardinality are reviewed through focused matrices; these percentages
do not measure all combinations.

The chart collector records completed items anywhere during an accepted input,
including ambiguous/local completions, rather than reconstructing a unique
successful derivation. Attempted totals additionally include structural-negative
inputs. Contextual-negative files remain separate. Positive fixtures and rule
cases must be accepted before their coverage can be reported; rejected positives
abort generation instead of contributing partial coverage.

The default-profile corpus and `Php85CoverageCases` drive these raw totals.
The five short-tags-disabled positive files additionally participate in PHPUnit
and differential checks, but are not added to the historical coverage denominator.
Direct primitive unit cases are likewise reported as lexer/adapter evidence,
not added as artificial EBNF production hits.

To add positive coverage:

1. Select an uncovered production/alternative or a missing optional-form variant.
2. Check the pinned PHP 8.5 parser, scanner, or compiler family in the source inventory.
3. Add a small descriptive `valid/*.php` file when integration matters, or a
   `Php85CoverageCases` fragment when a complete file would obscure the form.
4. Keep whole-file examples legal under documented contextual constraints.
   A fragment such as `type: void` is valid in a return position; it does not
   permit a void parameter. Constant fragments involving unresolved symbols
   establish structural shape, not constant folding or name resolution.
5. Run PHPUnit, the PHP 8.5 differential runner, and coverage; regenerate
   `php tools/php85-coverage-report.php` and verify with `--check`.

The generated report includes every remaining identity, classification, syntax
area, reason, first positive witnesses for exercised items, actual primitive
successes, all rule fragments, and the original Phase 3 backlog. A graph walk
from `source-file` stops at the actual adapter primitive registry; descendants
also reachable through a syntactic path are **not** silently classified as
bypassed. Removed trivia has its own category. New gaps remain visible and make
the report command fail until investigated. No percentage threshold is used.

Current coverage and the language-area/feature matrices are in
[php85-phase3-coverage.md](php85-phase3-coverage.md).

## Systematic negative boundaries (Grammar Completeness Phase 4)

The [negative ledger](php85-negative-boundaries.md) pairs 250 structural-negative
and 22 contextual-negative sources with 272 nearby valid repairs. It anchors
124 productions across every major syntax area and all 14 requested boundary
categories. These are reviewed examples, not exhaustive alternative or mutation
coverage. Successful-production and attempted-coverage percentages do not
measure rejection coverage.

Every structural-negative fixture must fail repository recognition and PHP lint.
Every contextual-negative fixture must pass repository recognition and fail
PHP lint. Every positive repair must pass both. The ledger reviews all 85
contextual-negative fixtures separately, including the 63 inherited fixtures;
none count as EBNF rejection evidence. This classification describes repository
behavior, not whether Zend diagnoses an error during parsing or compilation.
The [parser/compiler boundary remediation](php85-parser-compiler-remediation.md)
removes the earlier structural restrictions responsible for discarded nested
declaration discrepancies. Parser-action constraints still precede folding.

The current ordinary corpus is 599 valid, 347 structural-negative, and 276
contextual-negative fixtures (1,222 total), with no separately recorded
acceptance discrepancy. Both source-tag profiles run through PHP 8.5.10 using the existing
runner. The binary is a cross-check of expectations derived from pinned source;
neither lint acceptance nor fixture agreement proves complete conformance.

Constant expressions still use `constant-expression = expression`. Assignment,
yield, include/require, dynamic offsets/callables, captures, pipe and clone-with
have live contextual-negative and discarded-branch positive witnesses. An
unconditional EBNF ban would reject accepted PHP. `isset()` and constructor FCC
restrictions retain their earlier live/dead witnesses. Repeated hooks and type,
promotion, enum consistency and write-context rules remain separately documented.

Contributor instructions, source evidence, gap dispositions and verification
results are in [the Phase 4 report](php85-phase4-negative-coverage.md). Regenerate
the negative ledger with `php tools/php85-negative-report.php`; `--check` detects
changes to ledger metadata, grammar, fixture content or corpus membership.
PHPUnit checks fixture/ledger parity, production references, classifications,
distinct repairs, contextual-review completeness and report freshness.
