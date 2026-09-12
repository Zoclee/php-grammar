# PHP 8.5 conformance

The target is valid PHP 8.5 source, with three required layers:

1. The lexical/source contract converts bytes and scanner states to tokens.
2. The standalone EBNF recognizes syntactic token sequences.
3. Contextual constraints exclude constructs rejected during Zend compilation.

The normative contract and full EBNF appear in `grammar/8.5/php.md`.
Structural acceptance alone is not a conformance verdict. The repository
implements the first two layers with a PHP lexer, token adapter, and Earley
chart recognizer. PHP 8.5 also has a rule-level early modifier validator and
an independent test-only derivation forest for precedence comparison. A complete
whole-source contextual validator remains unimplemented. The current evidence
and final blocker dispositions are in [the completeness report](8.5/completeness.md).
Release claims and regression gates follow [the certification policy](conformance-policy.md).

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
implementation; it is not counted as rejection by EBNF. Empty hook blocks are
parser-valid and fail only if their declarations survive compilation. Early
modifier checks must run before folding, while later declaration checks must
respect discarded branches.

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

See [Phase 6](8.5/phase6-conformance-closure.md) for current blockers. Source
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
[8.5/phase3-coverage.md](8.5/phase3-coverage.md).

## Systematic negative boundaries (Grammar Completeness Phase 4)

The [negative ledger](8.5/negative-boundaries.md) pairs 250 structural-negative
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
The [parser/compiler boundary remediation](8.5/parser-compiler-remediation.md)
removes the earlier structural restrictions responsible for discarded nested
declaration discrepancies. Parser-action constraints still precede folding.

The current ordinary corpus is 653 valid, 362 structural-negative, and 356
contextual-negative fixtures (1,371 total), with no separately recorded
acceptance discrepancies. The discarded-unset defect is fixed and has direct
ordinary/constant folding regressions. Both source-tag profiles run through PHP 8.5.10 using the existing
runner. The binary is a cross-check of expectations derived from pinned source;
neither lint acceptance nor fixture agreement proves complete conformance.

Constant expressions still use `constant-expression = expression`. Assignment,
yield, include/require, dynamic offsets/callables, captures, pipe and clone-with
have live contextual-negative and discarded-branch positive witnesses. An
unconditional EBNF ban would reject accepted PHP. `isset()` and constructor FCC
restrictions retain their earlier live/dead witnesses. Repeated hooks and type,
promotion, enum consistency and write-context rules remain separately documented.

Contributor instructions, source evidence, gap dispositions and verification
results are in [the Phase 4 report](8.5/phase4-negative-coverage.md). Regenerate
the negative ledger with `php tools/php85-negative-report.php`; `--check` detects
changes to ledger metadata, grammar, fixture content or corpus membership.
PHPUnit checks fixture/ledger parity, production references, classifications,
distinct repairs, contextual-review completeness and report freshness.

## Scanner evidence (Grammar Completeness Phase 5)

`composer lexer:coverage` executes independent source/token matrices, numeric
and string primitives, and whole-source lexical integration cases. It checks
the generated [lexical ledger](8.5/phase5-lexical-evidence.json) for freshness;
use `composer lexer:coverage -- --write` after reviewed changes. The ledger maps
each of the 190 source rules and every remaining primitive/trivia grammar
identity to implementation, states, evidence, and disposition. It measures
reviewed families, not a synthetic traversal percentage.

The source API accepts raw bytes under `zend.multibyte=0`, including invalid
UTF-8. Offsets, lengths, and columns count bytes; CRLF counts as one newline.
The lexer does not perform encoding conversion. Complete strings are aggregate
tokens; `StringSyntax` and EBNF validate interpolated fragments. Whole-file EOF
is exact. The non-root fragment API adds a trailing newline boundary so a
standalone heredoc label has a following byte. This boundary is not payload.

`enum` and `from` identifier tokens cannot match their keyword terminals;
identifier primitives still see their original spellings. Zend's atomic
yield-from is split into two keywords plus retained trivia, including comments
whose tag-like text is inside that atomic token. Trivia remains removed before
syntactic matching. Numeric overflow, malformed-input recovery, and composite
string tokenization have explicit dispositions in the report.

The optional scanner oracle runs separately from correctness:

```text
php-8.5 -d short_open_tag=1 tools/php85-lexer-differential.php
php-8.5 -d short_open_tag=0 tools/php85-lexer-differential.php
```

It compares independent expected tokens with normalized Zend tokens, checks
lexical rejection/integration cases in parser mode, and compares token streams
for the ordinary valid corpus. Token values and internal string-token counts
are intentionally abstracted. `token_get_all()` is used only by this optional
tool, never by the lexer, primitives, coverage correctness, or normal PHPUnit
recognition. See [Phase 5](8.5/phase5-lexer-audit.md) for the normalization
rules and historical findings. Phase 6 supersedes its folding discrepancy and
adds bounded recursive combinations and explicit recovery/source-validity limits.

## Contextual closure (Grammar Completeness Phase 6)

The [reconciliation](8.5/phase6-reconciliation.json) maps 177 Zend productions
and 623 alternatives/actions to canonical EBNF anchors and evidence. The
[consolidated index](8.5/phase6-evidence.json) records restriction ownership,
historical dispositions and finite blockers. These are evidence links, not
branch-equivalence proofs.

`Php85ModifierValidator::validate($target, $modifiers)` checks already identified
modifier lists before folding. Its five stable error categories distinguish
target legality, duplicates, visibility, set visibility and abstract/final
conflicts. A null return certifies that rule only. It does not parse source and
does not change `PhpGrammarMatcher`'s structural acceptance contract.

`composer conformance:phase6` runs modifier, direct-folding, ambiguity, recursive
and malformed-input matrices with PHP 8.5. `--check` verifies a fresh matrix
report without rewriting it. The structure oracle separately compares EBNF
operand/body spans with normalized Zend ASTs. Raw helper ambiguity is retained
when it produces the same interpretation, as for builtin type literals/names.

The former never-parameter contextual-only coverage alternative has a valid
discarded-closure witness. The scanner-only enum alias alternative remains
correctly classified. No grammar coverage percentage is a completion target.
