# Conformance And Grammar Coverage

This document describes repository-owned grammar conformance and coverage
reporting.

## Conformance Pipeline

PHP source conformance uses this pipeline:

```text
PHP source
  -> repository PHP lexer
  -> TokenStream
  -> PHP grammar input adapter
  -> generic EBNF matcher
  -> grammar/<version>/php.ebnf
```

The installed PHP interpreter is not used as an oracle. Tests do not call
`php -l`, `token_get_all()`, or subprocesses for grammar correctness.

The project conformance target is valid PHP language syntax for the selected
major/minor version. It is not "whatever the installed PHP binary accepts," and
it is also not every intermediate form that the upstream Zend parser can reduce
before later compile-time contextual validation.

## Token Contract

Syntactic EBNF literals match token lexemes. For example, `"function"`, `"|"`,
`"=>"`, and `";"` match tokens with those exact source lexemes.

Lexical productions such as `identifier`, `variable`, `integer-literal`,
`floating-literal`, `string-literal`, `heredoc-string`, and `inline-html-text`
are matched by PHP-specific token category primitives. Whitespace, comments,
and doc comments are trivia and are removed before syntactic grammar matching.

The PHP source-mode boundary is lexical, not an ambiguous EBNF preference.
Inline HTML is emitted only outside PHP mode. `<?php`, `<?=`, and
configuration-enabled `<?` enter PHP mode; `?>` leaves PHP mode and can also
serve as a statement terminator where PHP permits it. The echo opening tag
matches an expression list, so forms such as `<?= $a, $b ?>` are tested without
using the local PHP interpreter.

Identifier primitives distinguish ordinary identifiers from contextual name
positions. Hard language constructs such as `unset` and `list` do not become
ordinary callable names merely because a grammar position asks for a
`name-identifier`. Numeric literal primitives validate the exact token lexeme
for decimal, binary, octal, explicit octal, hexadecimal, and floating literal
subclasses, including separator placement.

Some PHP validity checks are contextual and remain outside ordinary EBNF:
heredoc/nowdoc opening and closing label equality, flexible heredoc indentation,
duplicate modifiers, impossible type combinations, invalid attribute targets,
and callable validity for the pipe operator. These are syntax-adjacent
compile-time checks, not runtime semantics, and should be enforced by a future
contextual validation layer when the repository grows one.

## Fixture Layout

Whole-source conformance fixtures live under:

```text
tests/fixtures/php/<version>/valid/
tests/fixtures/php/<version>/invalid/
```

Valid fixtures must be accepted by the canonical grammar for that version.
Invalid fixtures must be rejected by it. Fixtures should target syntax, not
semantic errors such as missing classes, impossible type combinations, or
runtime behavior.

## Grammar Coverage

Grammar coverage is not PHP code coverage. It measures which canonical EBNF
productions and branch constructs are exercised by conformance inputs.

The coverage command is:

```bash
composer grammar:coverage
```

The report currently includes:

- production coverage;
- alternative coverage;
- attempted production count;
- attempted alternative count;
- uncovered production identities;
- uncovered alternative identities.

Stable coverage identities are derived from the parsed grammar. Productions use
their production name. Alternatives, optionals, and repetitions use deterministic
per-production counters such as:

```text
function-declaration
function-declaration/alternative:1
function-declaration/optional:2
function-declaration/repetition:1
```

## Valid And Invalid Fixture Coverage

Coverage distinguishes attempted traversal from successful coverage.

Valid whole-file fixtures and selected rule-level samples contribute to both
attempted and successfully matched coverage. Invalid fixtures contribute only to
attempted coverage. This preserves useful information about grammar paths that
were explored before rejection without allowing invalid source to make valid
branches appear fully covered.

Coverage percentages are informational in the current completeness phase. No
minimum threshold is enforced yet.
