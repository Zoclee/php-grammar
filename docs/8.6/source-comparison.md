# PHP 8.5 to PHP 8.6 RC4 source comparison

Both immutable pins and hashes are in [source discovery](source-discovery.md).
All new-source references below resolve against
[`f23b4c222cebe33be93ee8ecaaa14390c5507a08`](https://github.com/php/php-src/tree/f23b4c222cebe33be93ee8ecaaa14390c5507a08).
Source function and production names are location anchors; line numbers are
not required to reconstruct the comparison.

## Parser and scanner

The full `Zend/zend_language_parser.y` comparison identifies placeholder
arguments (`?`, bare `...`, and named `identifier : ?`) and replacement of the
dedicated first-class-callable argument alternative by AST construction through
the general list. A trailing comma after bare `...` becomes accepted.
`argument_no_expr` and the argument-list constructors are the material parser
changes (PHP86-001). The shift in `backup_doc_comment` around parameter defaults
retains parameter documentation rather than changing accepted tokens
(PHP86-009). AST representation adjustments are not new source productions.

No added/removed token declaration or precedence/associativity declaration was
identified in this diff. Existing expression, statement, namespace, declaration,
type, attribute and modifier productions outside these changes retain their
syntax. This establishes parser-diff compatibility, not compiler equivalence.

The full scanner diff adds no lexical pattern, keyword reservation or scanner
state. In particular `let` and `is` remain identifier tokens, and `?` / `...`
reuse existing terminals. Changes concern extracted compilation helpers, TLS,
unsigned line counters and heredoc exception-state handling. Scanner interfaces
and AST headers were compared; `zend_language_scanner_defs.h` is byte-identical.
Strings, interpolation, numeric forms, comments and tags have no identified
regular-expression changes. Error paths and state restoration still require
regression coverage; this review does not prove executable scanner equivalence.

## Compiler and related implementation

| Component / anchors | Material disposition |
|---|---|
| `zend_compile.c`: `zend_compile_args_ex`, callable conversion, `zend_eval_const_expr` | PFA validation, constant PFA arguments, and changed traversal; PHP86-001 |
| `zend_ast.c`, `zend_ast.h`: argument-list constructors, `ZEND_AST_PLACEHOLDER_ARG` | PFA AST wrapping; export/evaluation support is not independent syntax |
| `zend_partial.c` | Required `?` parameters and optionality preserved by `...`; PHP86-002 |
| `zend_compile.c`: property declaration checks | Readonly defaults permitted; PHP86-003 |
| `zend_can_write_to_variable`, `zend_mark_const_object_fetch` | Writes through constant objects; PHP86-004 |
| `zend_attributes.stub.php`, `zend_attributes_arginfo.h`, `zend_inheritance.c` | Override constants and enum cases; PHP86-005 |
| `zend_enum.c`: `zend_verify_enum_magic_methods` | Enum `__debugInfo` permitted; PHP86-006 |
| `zend_compile_return`, `zend_compile_yield`, `zend_is_variable_or_call` | New nullsafe reference rejection; PHP86-007 |
| `zend_compile_single_typename` | Qualified-static correction; PHP86-008 |
| `zend_assert_valid_class_name`, `zend_begin_func_decl`, `zend_compile_use`, `zend_compile_return`, `zend_constants.c`, `zend_API.c` | Deprecations retain acceptance; PHP86-010 |
| Closure caching and call/pipe/new/array_map compilation | Optimization and reference-flow changes; PHP86-011/013 |

Compiler comparison included 150 hunks retained by a temporary review filter
after whitespace, const qualification and basic type-spelling normalization.
That filter is an aid, not proof that excluded changes are immaterial. The
raw diff was retained locally. AST trait naming and removed unused hook AST
forms, copyright changes, enum case IDs and caching infrastructure do not
independently justify canonical grammar changes.

The VAR/TMP overhaul is especially important: commit
`6173a9a10989fa887240590a34c2b0eec50f003e` contains a source-validity change
alongside runtime reference behavior. `Zend/tests/gh20628_001.phpt` demonstrates
nullsafe return rejection; `gh20628_002.phpt` demonstrates pipe return-reference
behavior; `gh20628_003.phpt` covers assignment separation and `gh20628_004.phpt`
covers method calls. The latter three are not evidence of new token syntax.
Further list/reference/pipe combinations remain a bounded-audit obligation.

## Upstream tests

[The test-difference index](upstream-test-differences.json) identifies every
differing `Zend/tests/**/*.phpt` with title and old/new SHA-256:

| Difference | Count |
|---|---:|
| Added | 405 |
| Removed | 25 |
| Existing `--FILE--` changed | 612 |
| Existing `--FILE--` unchanged | 82 |

The 82 cases may still change expected diagnostics, INI, setup or cleanup.
The extraction does not compare `--FILEEOF--` or external scripts. No count
represents a passing test result. This index is not an exhaustive review of
`ext/`, `sapi/`, generated files, or optimizer tests.

Focused review covers PFA compile-error and constant-expression tests, readonly
defaults, override/deferred checks, enum debugging, constant-object writes,
nullsafe references, qualified-static regressions and deprecation evidence.
Inventory records identify the relevant official paths. The 54 paired lint
probes minimize acceptance questions independently of upstream runtime output.
All 1,124 indexed test differences still require a durable per-family review
disposition before an exhaustive test-audit claim. The index deliberately labels
individual records as discovery rather than semantic certification.

## RFC and release reconciliation

NEWS/UPGRADING Core sections were used as discovery aids. PFA v2, readonly
defaults, constant-object writes, override constants and enum debugging are
verified in selected-pin implementation, not inferred from announcements.
Parameter doccomments change metadata. Closure optimizations are only partially
implemented: the RFC's August 11 errata says static inference was not merged.
Do not relax the static requirement for constant closures on that basis.
The deprecations RFC's stale pending-implementation status does not override
actual compiler warnings in RC4.

The remaining implemented-index entries concern pack/unpack endian formats,
stream errors/filter limits, Locale display keywords, polling, session defaults,
mbregex, SortDirection, trimming form feed, grapheme reversal, clamp, reflection,
mysqli quoting, TLS resumption, error arguments, Duration and SNMP. These describe
APIs/runtime behavior rather than new canonical productions. Their individual
implementation links and targets are retained in `rfc-review.json`; complete
selected-pin integration verification remains outstanding. Minimum supported
versions and URI follow-up were also collected as supporting candidates.

## Compatibility and historical limitations

Reproduce existing 8.5 lexical-state, precedence, ambiguous-prefix, call/clone,
constant-folding, declaration, hook and version-boundary families for 8.6 later.
Do not inherit results or coverage percentages. Compare unchanged cases before
adding version-specific expectations; retain both positive and negative cases.

The 8.5 C1 whole-source contextual-validation gap remains applicable. C2's 201
direct and 77 delegated unresolved predicates are historical audit counts,
not 8.6 counts; rebuild that evidence. C3 aggregate binding/recursion/escaping/
dedent limitations require new version-specific validation. C4 exact-pin binary
provenance is not resolved merely by downloading an RC-tagged executable.
Discarded hook-name/declaration restrictions and unset-cast folding remain
historical boundary issues; do not repair 8.5 during this upgrade.

Unresolved areas and their required follow-up are PHP86-013. No absence of a
headline RFC is treated as evidence that contextual acceptance is unchanged.
