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
