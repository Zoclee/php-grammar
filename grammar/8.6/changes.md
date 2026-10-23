# PHP 8.6 grammar changes

## 1. Overview and comparison baseline

**Provisional, incomplete working document and unregistered draft.** Phase 3B
implements only PHP86-001 partial function application (PFA) and callable
trailing commas. No complete PHP 8.6 compatibility or certification is claimed.
The preceding supported version is PHP 8.5; the repository copying baseline is
`d4cd2c5446a9de7fa783533ef8d9e8f8f0fad3ee`.
The pre-edit EBNF SHA-256 is
`2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb`;
the pre-edit Markdown SHA-256 is
`bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f`.
The same hashes still identify the protected PHP 8.5 artifacts.

Upstream comparison: branch `PHP-8.5` at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`, versus branch `PHP-8.6`, tag
`php-8.6.0RC4`, at `f23b4c222cebe33be93ee8ecaaa14390c5507a08`.
The audited profile here is byte-oriented ordinary PHP source and lint of the
focused PFA corpus, with `-n` and no opcache/JIT. Executables are PHP 8.5.10
and PHP 8.6.0RC4 Windows distributions, not proven exact-pin builds.

## 2. Added syntax

**PHP86-001/PFA.** Positional `?`, named `name: ?`, and a bare `...` mixed with
bound arguments or other placeholders construct partial callables. Functions,
dynamic calls, object methods, static methods, and clone use existing call
entry points. The resulting callable may be invoked with existing call syntax.
PHP 8.5 rejected these new lists. PFA arguments do not become general expressions:
`name: ...`, `? + 1`, and a standalone `?` remain structural errors.

`argument-no-expression` adds `?` and the named `?` alternative; `argument`
adds bare `...`. The named helper deliberately excludes bare `...` so the
single-variadic list can remain disjoint without introducing another production.
Local coverage includes `pfa-basic`, `pfa-multiple`, `pfa-mixed`, `pfa-named`,
`pfa-method`, `pfa-static`, `pfa-dynamic`, `pfa-nested`, and `pfa-invoke` under
[valid fixtures](../../tests/fixtures/php/8.6/valid), with malformed counterparts
under [invalid fixtures](../../tests/fixtures/php/8.6/invalid).

## 3. Modified syntax

**PHP86-001/lists.** Exactly five productions change; none is added, removed,
renamed, or reordered:

| Production | Change and evidence | Local witnesses |
|---|---|---|
| `ordinary-argument-list` | Other lists permit placeholders and mixed bare `...`; exclude the sole `...` case. Parser `argument_list`, `non_empty_argument_list`, `argument_no_expr`; AST list construction. | `pfa-basic`, `pfa-mixed`, `pfa-variadic-first`; disjointness rule test |
| `first-class-callable-arguments` | `(...,)` joins `(...)`. Parser `possible_comma`; compiler single-variadic special case. | `pfa-fcc-trailing`, `pfa-fcc-control`; disjointness rule test |
| `clone-argument-list` | Placeholder lists and `(...,)`; initial unnamed expression still requires a comma. Parser `clone_argument_list`, `non_empty_clone_argument_list`; upstream `clone.phpt`. | `pfa-clone`, `pfa-clone-bound`, `pfa-clone-array`, `pfa-clone-named`, `pfa-clone-trailing`, unary/comma controls |
| `argument` | Adds the bare variadic placeholder, separately from unpacking. Parser `argument_no_expr`; compiler `zend_compile_args_ex`. | `pfa-mixed`, `pfa-unpack-control`, `pfa-unpack-first`, `pfa-unpack-last` |
| `argument-no-expression` | Adds required positional/named placeholders. Parser `argument_no_expr`; upstream `named_placeholders.phpt`. | `pfa-basic`, `pfa-named-required`, `pfa-named-ellipsis`, `pfa-placeholder-expression` |

`argument-list`, `constructor-argument-list`, call/method productions, attribute
and constant-expression attachment rules change transitively, without textual
edits. Constructors and attributes accept parser structure but retain compiler
restrictions. No precedence or associativity change is made. The generated EBNF
block is synchronized; production-index names and unrelated productions remain
unchanged. Header/status edits identify this draft and limit inherited audit claims.

## 4. Removed syntax

No syntax removal is implemented in PHP86-001. This is not a conclusion about
all PHP 8.6 changes; other findings remain outside this phase.

## 5. Lexical and tokenization changes

No new token, keyword, or scanner state is required. Existing `?` and
`T_ELLIPSIS` spellings distinguish placeholders from `??` and from unpacking
`...expression`. Scanner source is pinned in the
[PFA source lock](../../tools/8.6/pfa-source-lock.json).
The focused tests use the existing PHP 8.5 repository lexer through an in-memory
test manifest, with comments between tokens and a `??` negative. This is bounded
token compatibility, not a registered or independently certified 8.6 lexer.

## 6. Contextual and compiler-enforced restrictions

**PHP86-001/context.** The specification documents checks enforced by the pinned
compiler; no PHP 8.6 contextual validator is implemented in this phase.
Structural matching intentionally accepts the contextual-negative fixtures.

- `zend_compile_args_ex` permits a final bare `...` after named arguments;
  positional values/`?` must not follow named arguments. A variadic placeholder
  occurs at most once and last. Unpacking cannot mix with PFA in either order.
- `zend_compile_call_common` rejects constructor and nullsafe-chain callable
  construction. `zend_compile_parent_property_hook_call` rejects parent hook
  callable construction. `zend_compile_attributes` rejects a placeholder list
  belonging to an attribute; a PFA nested as an argument value instead follows
  constant-expression rules.
- `zend_compile_const_expr_fcc` permits function/static-method PFAs with suitable
  literal names; bound arguments must satisfy their initializer's constant
  constraints. `zend_compile_const_expr_args` enforces argument restrictions.
  RC4 `zend_eval_const_expr` traverses callable argument subtrees, but does not
  recursively fold callable names. Ordinary invocation and object-method PFAs
  are not permitted as surviving constant expressions.
- These are visited-AST checks. Live, ordinary-discarded, and discarded-enclosing
  closure witnesses distinguish them from parser rejection. Constant evaluation
  visits both logical operands, including an unset cast inside a bound argument.
  No blanket optimized-call traversal rule is introduced (PHP86-015 remains open).

Evidence: `compile_errors_001.phpt` through `compile_errors_006.phpt`,
`constexpr_001.phpt` through `constexpr_008.phpt`, and the named compiler functions.
Local [contextual negatives](../../tests/fixtures/php/8.6/contextual-invalid)
cover ordering, unpacking, constructors, nullsafe, attributes, names, variables,
and constant traversal; discarded repairs are in the valid corpus. Parent-hook
specific fixtures and broader optimization/constant-evaluation interaction
coverage remain explicit obligations beyond the tested matrix.

PHP86-002 is explanatory: `?` creates a required parameter, whereas `...`
preserves applicable optionality (`zend_partial.c`, `optional_placeholder.phpt`).
It introduces no additional production. Runtime signature certification is deferred.

## 7. Compatibility and unchanged syntax

PHP 8.5 grammar, Markdown, fixtures, evidence, and tooling remain unchanged.
The focused matrix checks both grammars, including existing calls, unpacking,
`(...)`, unary clone, and comma clone controls. Six
[version-boundary records](../../tests/fixtures/version-boundaries/8.6) cover
basic, named, mixed, clone, constant, and callable-trailing-comma introductions.
Full PHP 8.6 historical-syntax conformance and public discovery integration are
deferred; the manifest and default version remain unchanged.

## 8. Authoritative source references

All new implementation references use the immutable RC4 tree:

- [Parser](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_language_parser.y): `argument_list`, `argument_no_expr`, `argument`, clone lists, `ctor_arguments`, call and attribute entry points.
- [Compiler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c): functions in section 6, `zend_is_allowed_in_const_expr`, and ordinary short-circuit compilation.
- [AST](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_ast.c): `zend_ast_create_arg_list*`, `zend_ast_arg_list_add`, and placeholder-to-callable conversion markers.
- [Upstream tests](https://github.com/php/php-src/tree/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/partial_application): tests named above, `rfc_examples_overview.phpt`, `rfc_examples_const_expr.phpt`, `named_placeholders.phpt`, `clone.phpt`, and `optional_placeholder.phpt`.
- [Implemented PFA v2 RFC](https://wiki.php.net/rfc/partial_function_application_v2), supporting the implementation evidence rather than substituting for it.

Source hashes, reviewed test hashes, production mappings, and protected baseline
hashes are in the [PFA source lock](../../tools/8.6/pfa-source-lock.json).
Earlier [inventory](../../docs/8.6/language-change-inventory.md),
[probes](../../docs/8.6/syntax-probes.json), and
[implementation plan](../../docs/8.6/implementation-plan.md) remain research
evidence, not retroactive conformance results.

## 9. Conformance and regression evidence

The [case manifest](../../tests/fixtures/php/8.6/pfa-cases.json) maps 57 focused
fixtures to parser/compiler or PHPT evidence, separate structural expectations
for both versions, lint expectations, and RC4 diagnostics where applicable.
[Focused PHPUnit tests](../../tests/Php/Conformance/Php86PartialApplicationTest.php)
exercise the production changes, both grammar versions, list disjointness, clone
ambiguity, tokens, parity, and boundary metadata. The test-local manifest uses
the existing public configuration API and never alters public version discovery.

Run `php tools/8.6/pfa-check.php --php85=<executable> --php86=<executable>
--sources=<RC4-tree> --report=<output.json>` for traceability, source hashes, and
paired lint. Omitted source/interpreter options are explicitly recorded as not
run. This focused checker does not implement the full future release contract.
Actual command results and limitations are recorded in the
[Phase 3B report](../../docs/8.6/pfa-implementation.md) and
[lint results](../../docs/8.6/pfa-validation.json).

## 10. Known limitations and outstanding uncertainties

PHP86-013 (discovery gaps), PHP86-014 (assignment-based writable targets), and
PHP86-015 (optimized-call traversal) remain open with unchanged dispositions.
PHP86-003 through PHP86-007 and PHP86-010 are not implemented here;
PHP86-008/009/011 retain correction, metadata, and optimization dispositions;
PHP86-012 proposals/API-only scopes remain excluded. PHP86-002 is explanatory
only. This phase does not close any research finding outside PHP86-001's tested
structural scope.

Exact-pin executable provenance, runtime callable/signature behavior, all
optimizer profiles and interactions, parent-hook-specific regression fixtures,
complete initializer-context combinations, independent lexer/contextual adapter,
public manifest integration, full change-traceability contract, and final-release
reconciliation remain outstanding. The existing directory/manifest consistency
test rejects an unregistered grammar directory. The existing empty-boundary
repository assumption also fails once the new required boundary records exist;
neither test is weakened. Inherited certification evidence is not refreshed.
The draft is available for further independently verified Gate B work, not for
release certification. No subsequent phase is started.
