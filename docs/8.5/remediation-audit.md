# PHP 8.5 remaining-defect remediation

Current Phase 6 status: [conformance closure](phase6-conformance-closure.md) and
[the evidence/disposition index](phase6-evidence.json) supersede current-status
claims below. The discarded enum/trait/unset defects are fixed; the never-type
coverage classification is corrected. Earlier phase counts and findings are
historical. Four concrete full-conformance blockers remain in the Phase 6 report.

The confirmed for-condition and discarded-unset defects are fixed. The systematic
operator audit additionally found and fixed pending-assignment and
instanceof/exponentiation acceptance defects. No confirmed source-acceptance or
derivation mismatch remains in the audited matrices. Full PHP 8.5 conformance
is **not certified**: finite differential testing does not establish arbitrary
recursive scanner/parser equivalence or every contextual/deferred compiler path.

## Sources and architecture

The source authority remains PHP-8.5 revision
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. The three source hashes were checked
against `tools/php85-source-lock.json`. Executable evidence uses PHP 8.5.10 and
ext-ast 1.1.3, schema 120; that release binary is not a build of the exact pin.
The lexical/source contract, canonical EBNF and contextual validation remain
separate. The repository matcher intentionally accepts contextual-negative
fixtures. Nothing in this change makes it a complete contextual validator.

## Canonical changes

| Production | Correction and pinned source |
|---|---|
| `nonempty-for-expression-list` (new), `for-expression-list`, `for-condition-expression-list` | A comma prefix must contain an element. Parser `for_cond_exprs`, `for_exprs`, `non_empty_for_exprs`, lines 1206–1221. Empty lists remain permitted; final condition elements cannot use `(void)`. |
| `cast-operator` | Recognize parser-level `(unset)` with ordinary cast precedence. Parser line 1368 and scanner T_UNSET_CAST; compilation/folding rejection remains mandatory when reached. |
| `assignment-expression`, `assignment-prefix-context` | Allow a pending higher-precedence prefix before variable/list assignments, including compound/reference forms. Parser `variable '=' expr`, assignment operators and variable/expression reductions, lines 1251–1297. `$a + $b = $c` is `$a + ($b = $c)`, not an invalid computed assignment target. |
| `instanceof-expression`, `instanceof-prefix-context` | Preserve the forced reduction of the restricted class-reference right operand before subsequent power syntax. Parser `expr T_INSTANCEOF class_name_reference`, line 1346, and `class_name_reference`, lines 1470–1474. `$a instanceof $b ** $c` is `($a instanceof $b) ** $c`; pending powers also admit prefix operands. |

No matched/unmatched, clone, list or closed-yield production needed a change.
The tree audit adapter did need two fixes: the synthesized `from` half of a
composite token must not match an ordinary identifier, and close tags/HTML must
retain their parser statement effects. The former had caused two false duplicate
derivations for `yield from +$a` and `yield from -$a`; it was not an EBNF ambiguity.

## Contextual and folding reconciliation

`(unset)` is still a removed cast, not restored live PHP syntax. Its AST can be
discarded before validation. The [38-case evidence](remediation-folding-evidence.json)
contains eight for-loop cases, twenty unset cases across ordinary/global-constant
contexts and ten separate real-cast controls. Each case has a persistent fixture.

The new asymmetry is material: ordinary short-circuit compilation skips a
determined right operand, whereas ordinary ternary/coalesce compilation still
visits their alternatives. Constant-initializer evaluation visits both logical
children but skips unselected literal ternary arms and non-null coalesce defaults.
Visiting an unset cast in `zend_eval_const_expr` fails immediately, before its
operand. The standalone specification gives the complete result table.

Reconciliation reviewed `zend_const_expr_to_zval`, `zend_eval_const_expr`,
`zend_is_allowed_in_const_expr`, `zend_compile_const_expr`, ordinary short-circuit,
conditional/coalesce and cast compilation. Existing constant fixtures recheck
literal and nonliteral branches, warning-sensitive operations, visited empty
offsets/array errors, static/capturing closures, callable conversion, new/object
casts, class/name folding, property/nullsafe-property reads, arrays/unpacking and
named/ordinary argument traversal. CALL/STATIC_CALL do not acquire a generic
recursive folding rule. Closure bodies are compiled only after the closure
survives, using function compilation rather than the constant-expression whitelist.

The direct-fatal inventory still contains 244 sites in 87 functions, including
14 parser-action sites. The original 85-family boundary matrix is preserved and
extended by 29 families (87 fixtures), for 114 total. Additions cover unset,
destructuring, special-variable writes, duplicate statics/members/labels,
return/generator restrictions, NoDiscard, removed/custom functions, duplicate
defaults, goto/break and parent-hook context. Every new live/retained form rejects
and every discarded form accepts. All 13 folding templates pass for all 114
families: 1,482 comparisons. Existing parser-action controls still reject in
discarded closures. This does not imply every direct diagnostic has an isolated
witness or cover deferred checks in other translation units.

## Systematic structure and ambiguity audit

[The generator](../../tools/php85-systematic-structure.php) enumerates every
ordered pair of 44 binary spellings, 29 prefix forms against binary operators in
both positions, pending prefixes, prefix pairs, ternary/postfix interactions,
reference assignments, repeated chains and nested keyed yields. It retains the
requested precedence regressions and exercises clone/list alternatives.

The statement matrix crosses 14 wrappers at two nesting levels with five bodies,
then adds ordinary outer else and alternative else/elseif contexts: 3,920 cases.
Wrappers include if/elseif/else, all four propagating loop/declare forms, do/while,
braces and alternative syntax. Bodies include unmatched/matched ifs, declarations
and HTML. No statement production change was required.

For each of 11,294 cases, the test-only Earley forest requires zero derivations
for Zend parser rejection and exactly one for acceptance. For accepted inputs it
forces all complete EBNF operand spans, pending-prefix completion spans and
implicit left folds using parentheses, or statement-body spans using braces,
then compares the complete normalized Zend AST. It removes declaration IDs and
the explicit-parentheses conditional flag, which do not change grouping. It does
not merely compare lint success. Empty declare bodies are preserved rather than
changed into block-mode declarations. HTML witnesses use the equivalent echo AST.

The [machine-readable report](systematic-structure.json) records counts and input
hashes. The audited finite matrix has no remaining ambiguity or grouping blocker.
The old targeted-only prefix/matched-unmatched blocker is therefore resolved for
this matrix. Formal equivalence for arbitrary nesting depth remains unproven.

## Scanner/parser product

[The product generator](../../tools/php85-scanner-product.php) crosses 31 scanner
bodies with seven enclosing source contexts and two short-tag profiles, plus 48
heredoc/nowdoc EOF boundaries: [482 comparisons](scanner-product.json).
It covers HTML transitions, shebang, composite enum/yield lookahead, embedded close
tags, NUL, property lookup, varname/offset states, nested interpolation/heredoc,
ampersands and qualified names. All agree with PHP lint. A NUL-containing comment
prevents composite yield-from scanning; the probe uses `from[0]` to avoid confusing
that lexical distinction with the contextual empty-offset error in `from[]`.

The original scanner suite also passes: 190 rule dispositions, 2,700 direct
byte/token cases, 101 primitive cases and 144 syntax cases. Numeric overflow token
values, aggregate string tokens, trivia handling and malformed-input recovery
remain documented abstractions; the stateful scanner contract is retained.

## Verification

| Check | Result |
|---|---|
| Full PHPUnit suite | 4,859 tests, 46,969 assertions passed; subsequent structure regression additions pass separately below |
| Final targeted structure tests | 110 tests, 221 assertions passed |
| Final integrity/scanner/ledger/structure recheck | 3,073 tests, 41,069 assertions passed; report grammar/tool hashes match current files |
| Corpus + PHP lint | 646 valid, 362 structural-negative, 348 contextual-negative; zero mismatches and zero known-discrepancy fixtures |
| Boundary/folding matrix | 114 families × 13 templates = 1,482; zero failures |
| Explicit AST witnesses | 63 positive and 10 negative; zero failures |
| Systematic structure | 11,294 cases: 10,389 unique accepted derivations/AST comparisons and 905 rejected cases; zero failures |
| Scanner product | 482 cases; zero failures |
| Scanner coverage | 2,700 direct + 101 primitive + 144 syntax cases; zero failures |
| Positive grammar coverage | 301/364 productions and 605/790 alternatives; remaining identities classified as lexical/trivia/contextual/scanner-only, no meaningful syntactic gap |
| Integrity | EBNF parsing, undefined references, duplicate definitions and generated Markdown parity pass in PHPUnit; Composer strict validation and diff whitespace checks pass |

Reproduction (prefix commands with `rtk` in this repository):

```text
php vendor/phpunit/phpunit/phpunit --no-progress
php bin/php85-conformance.php /path/to/php85
php tools/php85-boundary-folding.php /path/to/php85
php85 -d extension=ast tools/php85-ast-conformance.php
php85 -d extension=ast tools/php85-systematic-structure.php
php85 tools/php85-scanner-product.php
php85 -d short_open_tag=1 tools/php85-lexer-differential.php
php85 -d short_open_tag=0 tools/php85-lexer-differential.php
php bin/lexer-coverage.php
php bin/grammar-coverage.php
python tools/php85-compiler-boundaries.py /path/to/pinned-sources --check
```

## Files and remaining limits

Changed artifacts include `grammar/8.5/php.ebnf` and its generated Markdown,
the lexer cast recognition, tree/scanner test support, focused tests, two new
differential tools, parser-structure/boundary matrices and evidence ledgers.
There are 128 new PHP fixtures and two reclassifications: the live unset fixture
moves to contextual-invalid and the formerly discrepant discarded unset fixture
moves to valid. Historical reports are marked as superseded for current status.
`php-grammar.json` requires no change: version, root and primitive contracts remain
the same.

There is no known unresolved acceptance defect in the tested corpus or matrices.
Remaining conformance assurance limits are unbounded recursive scanner/parser
combinations, complete contextual/deferred-path validation and the executable
oracle's patch-build difference from the source pin. These limits prevent a claim
of full PHP 8.5 conformance despite resolution of the known defects and the finite
prefix/statement audit blockers.
