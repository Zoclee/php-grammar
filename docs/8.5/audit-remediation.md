# PHP 8.5 audit and remediation

Current Phase 6 status: [conformance closure](phase6-conformance-closure.md) and
[the evidence/disposition index](phase6-evidence.json) supersede current-status
claims below. The discarded enum/trait/unset defects are fixed; the never-type
coverage classification is corrected. Earlier phase counts and findings are
historical. Four concrete full-conformance blockers remain in the Phase 6 report.

Current status: the [subsequent remediation audit](remediation-audit.md) resolves the discarded-unset, for-condition, assignment-prefix and instanceof-power defects, expands the boundary matrix to 114 families, and supplies systematic AST and scanner-product comparisons. Counts and unresolved findings below describe the historical stage unless explicitly updated.

The [remaining-audit corrections](remaining-audit.md) supersede this
historical report's precedence, dangling-else, folding and scanner status.
The validation counts below are retained as a historical baseline.

Grammar Completeness [Phase 4](phase4-negative-coverage.md) supplies the
current negative-boundary results and [complete contextual review](negative-boundaries.md).
There are 917 ordinary fixtures and two separately retained discarded-closure
discrepancies. No new grammar, scanner, adapter, matcher or chart-recognizer
correction was required by the Phase 4 matrix; full conformance remains unproven.

This audit supersedes the earlier disposition checklist. That checklist called
several unresolved areas fixed. The current result is a substantial structural
remediation with explicit remaining discrepancies; it is **not** a certification
of PHP 8.5 conformance.

## Evidence and method

Primary source: `php-src` branch `PHP-8.5`, revision
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`, retrieved 2026-09-10.
The parser, scanner, and compiler are pinned together. Supporting manual/RFC
references in `docs/sources.md` do not override these implementation sources.

The source inventory records every parser production, scanner rule entry,
compiler function in the selected compile-related families, and EBNF production.
It provides locations and file hashes in `docs/8.5/source-inventory.json`.
There are 177 parser productions and 190 scanner rule entries. Regenerate with:

```text
python tools/fetch-php85-sources.py .audit
python tools/php85-source-inventory.py .audit
```

This inventory prevents entire grammar areas from disappearing from view. It is
not a proof of equivalence or a substitute for a fresh manual comparison of all
alternatives. That exhaustive final reconciliation is still outstanding in the
areas identified below.

Executable oracle: official Windows PHP 8.5.10 NTS x64 release ZIP,
`php-8.5.10-nts-Win32-vs17-x64.zip`, SHA-256
`22ec430195984d233eb9e62c637a945bbcda06efca2f392d9d96d62c6acd34f8`.
The checksum was verified before use. This is a released patch build, not a
build of the pinned branch commit. Downloads live in ignored `.audit/` and are
not distributed with the grammar.

## Issues and regressions

`P` means `Zend/zend_language_parser.y`, `S` means
`Zend/zend_language_scanner.l`, and `C` means `Zend/zend_compile.c` at the pinned
revision. Fixture names below omit the `audit-` prefix and `.php` suffix; they
are under `tests/fixtures/php/8.5/{valid,invalid,contextual-invalid}`.

| Issue | Productions / implementation | Source reference | Change / regression |
|---|---|---|---|
| Independent PHP regions reject templates | source-file, statement, token adapter | P start/statement; S INITIAL and close-tag rule | One token stream; `tags-interleaved`, `tags-return-close` |
| Long tag boundary too broad | Lexer consumeOpenTag | S INITIAL `<?php` rules, lines 2309–2334 | Require whitespace/EOF; `tags-empty-php`, `tags-case`; disabled-tag lexer tests |
| Echo EOF wrongly optional | source stream, echo-statement | S `<?=` emits T_ECHO; P statement | No fabricated EOF semicolon; `tags-echo-eof`, `tags-echo-empty` |
| Closing tags only terminate selected statements | token adapter | S close-tag emits `;`, line 2524 | Normalize all close tags; `tags-return-close`, `tags-comment-close` |
| Byte and whitespace categories too broad | non-ascii-byte, whitespace-character | S LABEL/WHITESPACE, lines 1377–1378 | Explicit primitive registry and four whitespace bytes; `names-high-byte`, `lexical-form-feed`, `lexical-vertical-tab` |
| Comments swallow delimiters / attributes | comment primitives, Lexer | S line/block comment states | First terminator and `#[` distinction; `comments-first-terminator`, `comments-unclosed`, `comments-attribute` |
| Qualified-name trivia and reserved names | name, identifier, semi-reserved-identifier | P name/identifier/reserved_non_modifiers; S name tokens | Atomic names and distinct identifier contexts; `names-trivia-qualified`, `names-reserved-class`, `names-keyword-method`, `names-halt-method` |
| Contextual enum and readonly function names | function-declaration, Lexer | P function_name; S enum lookahead | Preserve enum as a name where appropriate; `names-enum-class`, `names-readonly-function` |
| Magic constants accepted as declaration names | PhpVersion keyword table | S magic-constant rules; P T_STRING contexts | Separate tokens; `names-magic-declaration`, `names-magic-constant` |
| Octal separator and invalid numeric spellings | integer-literal and subclasses | S LNUM/HNUM/BNUM/ONUM and octal validation | Remove unrestricted integer primitive; `numbers-octal-separator`, `numbers-prefixes`, `numbers-invalid-octal`, separator negatives |
| Numeric floats / prefixes | floating-literal, Lexer | S DNUM/EXPONENT_DNUM | Base/separator fixtures; `numbers-floats`, `numbers-float-dot-underscore` |
| Binary-prefixed strings and heredocs | string productions, Lexer | S `b?` string/header rules | Both prefix cases; `strings-binary`, `strings-binary-heredoc` |
| Duplicated dollar in complex interpolation | encapsulated-variable | P encaps_var/T_CURLY_OPEN | `{` plus existing variable; `strings-interpolation`, `strings-interpolation-expression` |
| Quoted offsets and nested interpolation quotes | StringSyntax, Lexer, adapter | P encaps_var_offset; S interpolation states | Validate extracted fragments independently; `strings-nested-quotes`, `strings-interpolation-quoted-offset`, `strings-interpolation-unclosed` |
| Unicode escape errors ignored | StringSyntax | S zend_scan_escape_string | Reject malformed/out-of-range escapes; `strings-unicode-empty`, `strings-unicode-range` |
| Heredoc closing label/indentation/newline | heredoc/nowdoc productions, Lexer | S ST_HEREDOC/ST_NOWDOC/header rules | Equality, indentation, quoted/empty headers, closing punctuation; `strings-heredoc-*`, `strings-nowdoc-indented` |
| Precedence hierarchy incorrect | expression layers | P precedence declarations lines 64–96; C zend_compile_conditional | Reorder high operators and introduce low levels; `precedence-unary-power`, `precedence-not-instanceof`, `precedence-coalesce`, chaining negatives. AST grouping remains open. |
| Universal postfix grammar | variable-expression and dereferenceable categories | P variable/callable_variable/fully_dereferenceable/new_variable | Preserve Zend categories; `dereference-call-chain`, `dereference-new-*`, `dereference-curly-offset` |
| Matcher discards indirect left recursion | ChartMatcher | P mutually recursive dereference categories | Earley recognition; ChartMatcherTest nullable-cycle and indirect-recursion cases |
| Recursive globals and static unset | global-variable, unset-variable | P global_var/unset_variable | Use proper recursive categories; `dereference-global-recursive`, `dereference-unset-static`, contextual `dereference-unset-call` |
| Callable marker mixed with arguments | argument-list | P argument_list | Complete special form retained; `arguments-first-class`, `arguments-mixed-callable`, `arguments-reference` |
| Clone-with special list too narrow | clone-argument-list | P clone_argument_list/non_empty_clone_argument_list | Named, unpacked, trailing-comma and callable forms; `php85-clone-*` |
| Static local initializer restricted to constants | static-variable | P static_var `= expr` | Runtime expressions; `statements-static-runtime` |
| Void for-condition nuance | for-condition-expression-list | P for_cond_exprs/non_empty_for_exprs | Earlier elements may discard; last must be expression; `statements-for-void-prefix`, `statements-for-final-void` |
| Try without handler, switch leading semicolon | try-statement, switch-case-list | P statement/switch_case_list; C zend_compile_try | Require catch/finally, retain leading `;`; `statements-try-*`, `statements-switch-semicolon` |
| Constant syntax too broad/narrow | constant-expression hierarchy and context wrappers | C zend_is_allowed_in_const_expr / zend_compile_const_expr | Static noncapturing closures, callable conversion, casts, new, offsets/property reads; reject runtime calls/captures/interpolation; `constants-*`. Folding edge cases remain open. |
| Attributes reuse unrestricted arguments | attribute, constant-argument-list | C zend_compile_attributes | No unpacking/callable list; `attributes-unpacking`, `constants-attribute-new` |
| Multiple attributed global constants | attributed-top-declaration | C zend_compile_const_decl | Single global constant; class multiple constants remain valid; `attributes-multiple-global`, `attributes-class-multiple` |
| Hook list/name/modifier/forms | property-hook and property-declaration | P property_hook/hooked_property; C zend_compile_property_hooks | Nonempty get/set, final-only hook modifier, separate hooked declaration; `hooks-*` |
| Abstract / var hooked properties | property-modifier, property-declaration | P property_modifiers; C zend_compile_prop_decl | Retain valid forms; `hooks-abstract`, `hooks-var` |
| Empty trait aliases and wrong precedence reference | trait-alias, trait-precedence | P trait_alias/absolute_trait_method_reference | Nonempty alias action and qualified precedence reference; `members-trait-empty-as` |
| Bare enum/class member semicolons | enum-member, class-member | P class_statement | Exclude separators; `members-enum-semicolon`, `members-class-semicolon` |
| Type context/promotion/enum consistency | type and context constraints | C zend_compile_params/typename/enum_case | Document mandatory contextual checks; `promotion-*`, `types-*`, `enums-*` |
| Cast token normalization and aliases | cast-expression, token adapter | S cast rules lines 1636–1713 | Horizontal whitespace/case and deprecated aliases; `casts-whitespace-case`, `casts-real`, `casts-unset` |
| Halt payload lexed as PHP | halt-compiler-data, Lexer | P top_statement; zend_stop_lexing | Stop tokenization after terminator; `halt-data` |
| Markdown drift | generated EBNF block | Repository EBNF authority policy | Full production parity test and synchronization command |

## Contextual constraints

The PHP 8.5 Markdown specification contains the normative matrix: constant
validation modes, type legality, modifiers, hook combinations, constructor
promotion, enum values, argument ordering, writable variables, declaration
placement, and control flow. These are part of source validity even when not
encoded in pure EBNF. Twenty-five focused fixtures currently expose the
structural/contextual boundary; the lint runner prints their count separately.

The earlier positive unit example `class A { public int $x { get; } }` was
invalid concrete-class source. It now uses a hook body; the contextual-negative
fixture `hooks-concrete-no-body` records the actual compiler behavior.

## Current dispositions of historical gaps

1. The precedence/dangling-else ambiguity defects are fixed. Phase 6 provides
   15,342 systematic structure cases; this is finite binding evidence.
2. Constant folding now has declaration and direct-expression matrices. Exact
   diagnostic predicates and delegated AST evaluation remain blocker C2.
3. Recursive scanner stacks are implemented and bounded combinations pass.
   Embedded binding fingerprints inside aggregate strings remain blocker C3.
4. Early modifier-list validation is implemented. Whole-source contextual
   traversal and additional rules remain blocker C1.
5. All 177 parser productions/623 alternatives and 190 scanner rules have
   persistent mappings. Those mappings are not mathematical equivalence proofs;
   the exact source-pin executable correspondence remains blocker C4.

## Verification record

The following counts are the historical post-audit baseline. Grammar
Completeness Phase 3 subsequently adds positive evidence for imports, trait
precedence/aliases (including every reserved alias alternative), alternate
source transitions, dereference categories, operator/type matrices, and hooks.
See [the Phase 3 report](phase3-coverage.md) for current counts and the
generated per-element evidence ledger. No canonical grammar correction was
required in Phase 3. The original regression corpus remains in all checks.

The positive review also confirmed the contextual ban on mixing `[]` and
`list()` within one destructuring tree (`zend_compile.c`, lines 3250–3255 at
the pin). The new keyed and nested `list()` fixtures consistently use `list()`;
this constraint is not forced into EBNF or expanded into a negative campaign.

The differential command uses the same fixture files for independent EBNF
recognition and official PHP lint, including separate enabled/disabled short-tag
profiles. The final run on PHP 8.5.10 recorded 108 positive, 76
structural-negative, and 25 contextual-negative fixtures with zero unexpected
mismatches. The repository suite passed 387 tests and 1,094 assertions on PHP
8.4.22. Integrity checks passed for 331 productions: no undefined references
outside the explicit primitive registry, duplicates, unreachable productions,
or unintended nullable productions. Markdown production parity and diff
whitespace checks passed. Coverage reporting completed: 236/331 productions
(71.3%) and 277/769 alternatives (36.0%) exercised. Generated chart symbols are
excluded from coverage totals. Subsequent changes must rerun these checks.

Final assessment: substantially improved, source-traceable structural grammar;
**full PHP 8.5 source conformance is not established**.
