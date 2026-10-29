# PHP 8.6 grammar changes

## 1. Overview and comparison baseline

**Registered draft; provisional and incomplete working document.** Phase 3B
implements PHP86-001 partial function application (PFA) and callable
trailing commas. Phase 3C adds the contextual specification changes PHP86-003
through PHP86-007 below, with no further EBNF change or new contextual validator.
Phase 3E documents PHP86-010 accepted-but-deprecated forms and their diagnostic
stages, also without EBNF changes.
No complete PHP 8.6 compatibility or certification is claimed.
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

No syntax removal is implemented in PHP86-001 or PHP86-010. Deprecation does not
remove an accepted production. This is not a conclusion about
all PHP 8.6 changes; other findings remain outside this phase.

## 5. Lexical and tokenization changes

No new token, keyword, or scanner state is required. Existing `?` and
`T_ELLIPSIS` spellings distinguish placeholders from `??` and from unpacking
`...expression`. Scanner source is pinned in the
[PFA source lock](../../tools/8.6/pfa-source-lock.json).
The original Phase 3B tests used the PHP 8.5 lexer through an in-memory test
manifest, with comments between tokens and a `??` negative. Phase 4B uses the
registered 8.6 configuration and verifies both tag profiles and token boundaries.
Shared scanner logic retains the reviewed token rules; this is bounded lexical
evidence, not independent whole-scanner certification.

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
and constant traversal; discarded repairs are in the valid corpus. Phase 5B
adds parent-set-hook PFA/FCC rejection, an ordinary-call repair, and a
discarded-closure control. Parent-get-hook variants and broader optimization/
constant-evaluation interactions remain obligations beyond that bounded matrix.

PHP86-002 is explanatory: `?` creates a required parameter, whereas `...`
preserves applicable optionality (`zend_partial.c`, `optional_placeholder.phpt`).
It introduces no additional production. Runtime signature certification is deferred.

### Phase 3C contextual specification changes

Each finding independently meets Gate B for the stated specification and evidence
scope. All changes below are contextual, with **zero EBNF or lexical edits**.
The structural matcher does not enforce them. The
[context case manifest](../../tests/fixtures/php/8.6/context-cases.json) maps every
fixture to its finding, implementation/test evidence, validation stage, and paired
expectations. Prefixes below refer to `context-<prefix>*.php` in the 8.6 valid,
contextual-invalid, and runtime fixture directories. Structural acceptance remains true in
both grammars, including all contextual-negative cases.

| Finding | PHP 8.5 versus RC4; normative scope | Existing productions and local evidence |
|---|---|---|
| PHP86-003 | Ordinary readonly defaults prohibited -> eligible typed instance defaults permitted, including readonly classes. Type/constant-expression, nonstatic, unhooked, inheritance, and initialization constraints remain. Promotion defaults still initialize parameters. | `property-declaration`; Properties and Promotion prose. `readonly-*` fixtures cover defaults, implicit readonly, types, inheritance, promotion, discarded closure, and controlled reassignment. |
| PHP86-004 | Constant-derived property write paths rejected -> property mutation through global/class constant objects and intervening dimensions permitted. Dimension-only constant array writes remain invalid. No assignment-based writable-target relaxation. | `variable-expression`, `assignment-expression`, `unset-statement`, `foreach-statement`; Writable variables and destructuring prose. `object-*` fixtures cover references, unset, increment, compound writes, dimensions, destructuring, nonobject failure, and mutation execution. Existing foreach lint acceptance is a control, not a new acceptance boundary. |
| PHP86-005 | Override target excludes constants -> qualifying class/interface constants and enum cases supported. A nonprivate inherited constant must match; finality/type/visibility constraints remain. | `attribute`, `class-constant-declaration`, `enum-case`; Class constants prose. `override-*` declaration-link cases cover parents/interfaces/enums, protected/private/missing/final/type cases, and delayed parent declaration. |
| PHP86-006 | Enum `__debugInfo` forbidden at linking -> permitted with ordinary magic-method checks. No arguments, nonstatic, return type compatible with `?array` when declared; nonpublic visibility warns. Other enum magic prohibitions remain, especially `__get`. | `enum-declaration`, `method-declaration`; Enums prose. `enum-*` cases separate lint from linking and test backed/unbacked, signatures, visibility, and forbidden-method controls. |
| PHP86-007 | Nullsafe method-call reference returns/yields pass tested 8.5 lint -> rejected when compiled in RC4. Existing nullsafe property/binding restrictions remain. No blanket value-return, yield, or foreach restriction. | `return-statement`, `yield-expression`, `function-declaration`; Control-flow constraints prose. `nullsafe-*` cases distinguish by-reference/value contexts, ordinary calls, property binding, discarded closure, unreachable/dead statements, and foreach controls. |

Immutable implementation evidence at
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`:

- PHP86-003: [compiler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c), `zend_compile_prop_decl`; [inheritance](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_inheritance.c) property checks. Upstream `readonly_props/readonly_with_default.phpt` and `readonly_with_default_inheritance.phpt`.
- PHP86-004: compiler `zend_can_write_to_variable`, `zend_mark_const_object_fetch` and constant/property fetch compilation. Upstream `gh10497_write_targets.phpt`, `gh10497_write_targets_guardrail.phpt`, `gh10497_dim_arr.phpt`, `gh10497_func_arg.phpt`, `gh10497_non_object.phpt`.
- PHP86-005: compiler `zend_compile_class_const_decl`, `zend_compile_enum_case`; inheritance constant matching and unresolved Override checks; [attribute target definition](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_attributes.stub.php) and generated arginfo. Upstream `attributes/override/constants/basic.phpt`, `failure.phpt`, `visibility_01.phpt`, `visibility_03.phpt`.
- PHP86-006: [enum checks](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_enum.c), `zend_verify_enum_magic_methods`; [magic-method checks](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_API.c), `zend_check_magic_method_implementation`. Upstream `enum/debugInfo/` magic method, backed value, parameter, return, and visibility PHPTs. Nullable-return deprecation is diagnostic, not a new structural rejection.
- PHP86-007: compiler `zend_compile_return`, `zend_compile_yield`, `zend_is_variable_or_call`, and `zend_assert_not_short_circuited`; upstream `gh20628_001.phpt` plus recorded ordinary/discarded research probes.

The [context source lock](../../tools/8.6/context-source-lock.json) preserves both
source pins, hashes, pre-edit artifact hashes, and interpreter distribution hashes.
The [Phase 3C report](../../docs/8.6/context-implementation.md) records exact
commands and limitations. Seven [contextual boundary records](../../tests/fixtures/version-boundaries/contextual-8.6)
declare their stage explicitly; they must not be interpreted as structural
acceptance boundaries by the generic structural runner. The
[paired results](../../docs/8.6/context-validation.json) record lint separately
from declaration linking and the three controlled-runtime witnesses.

### Phase 3E diagnostic-only specification changes

**PHP86-010:** all deprecated forms below remain accepted subject to existing
contextual restrictions. There are **zero EBNF, token, or lexical edits**.
Warnings are separate from source rejection. The existing `readonly` import
alias rejection is a negative control, not a new deprecation or removal.

| Construct / unchanged productions | PHP 8.5 versus RC4 | Source and local case group |
|---|---|---|
| Names, `identifier`, `type`, class-like declarations | `let`/`is` declaration and type-name diagnostics added; qualified `_` type/alias checks broadened. `_` class declarations already warned, including namespaced declarations. Qualified suffix matching differs from unqualified matching; runtime class aliases lowercase first. | Compiler `zend_assert_valid_class_name`, `zend_compile_typename_ex`, `zend_compile_class_decl`; API `zend_register_class_alias_ex`; `names` cases. |
| `function-declaration`, `method-declaration` | Functions named `readonly`, `let`, `is` now warn during compilation; methods and `_` functions retain warning-free controls. | Compiler `zend_begin_func_decl`; `names` cases. |
| Global constants, `constant-declaration` | `_`, `let`, `is` names newly warn at runtime registration; lint alone does not produce this diagnostic. | Constants `zend_register_constant`; `names` cases. |
| `namespace-use-declaration`, `use-declaration` | Class/constant aliases `_`, `let`, `is` and function aliases `let`, `is` now warn during compilation. `_` function aliases remain warning-free. `readonly` function aliases are rejected by both parsers. | Compiler `zend_compile_use`; `imports` cases. |
| `class-constant-declaration`, `enum-case` | Case-insensitive `namespace` names now warn during compilation; same-named property/method and `define` controls do not. | API `zend_declare_typed_class_constant`; `namespace` cases. |
| `return-statement` in finally | Bare/value returns newly warn during compilation, including a nested try inside finally; nested closure context is independent. | Compiler `zend_compile_return`; `finally` cases. |
| `method-declaration`, `return-statement`, `yield-expression` | Constructor/destructor value returns and generator declarations newly warn during compilation; bare returns do not. | Compiler `zend_compile_return`, `zend_compile_func_decl_ex`; `returns` cases. |
| `method-declaration`, `type` for `__debugInfo` | Nullable return declarations newly warn during compilation. Null received by the debug handler already warned at runtime in PHP 8.5; an ordinary direct call is distinct. | API `zend_check_magic_method_implementation`; object handlers `zend_std_get_debug_info`; `debug` cases. |

Immutable implementation sources at the RC4 pin:
[compiler](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c),
[constants](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_constants.c),
[API](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_API.c),
[object handlers](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_object_handlers.c).
Upstream evidence includes
[deprecated identifiers](https://github.com/php/php-src/tree/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/deprecated_identifiers),
`functions/readonly_as_method_name_is_ok.phpt`,
`deprecate_namespace_class_constant.phpt`,
`try/finally_return_deprecation.phpt`,
`magic_methods/constructor_destructor_return.phpt`, `return_types/042.phpt`,
and `debug_info/debug_info.phpt` under that tree's `Zend/tests` directory.
Both-side source/test hashes and 67 isolated sources with explicit paired
lint/execution expectations are in the
[deprecation case manifest](../../tests/fixtures/php/8.6/deprecation-cases.json).
The [Phase 3E report](../../docs/8.6/deprecation-implementation.md) and
[paired results](../../docs/8.6/deprecation-validation.json) record validation
and limits. `__sleep`/`__wakeup` deprecation was reverted and is not introduced
here. No warning-only version-boundary acceptance record is added.

## 7. Compatibility and unchanged syntax

PHP 8.5 grammar, Markdown, fixtures, evidence, and tooling remain unchanged.
The focused matrix checks both grammars, including existing calls, unpacking,
`(...)`, unary clone, and comma clone controls. Six
[version-boundary records](../../tests/fixtures/version-boundaries/8.6) cover
basic, named, mixed, clone, constant, and callable-trailing-comma introductions.
Full PHP 8.6 historical-syntax conformance remains deferred. Phase 4B registers
the draft; PHP 8.5 remains the recommended stable version. The numeric
`latestVersion()` API includes drafts and therefore returns 8.6.

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

Phase 5D adds informative scope clarifications in the specification's Writable
variables and destructuring and Ordinary compilation versus constant folding
sections. PHP86-014 includes assignment-derived property, compound, increment,
unset and reference-binding contexts; lint success and execution failure or
success do not settle intended validity. PHP86-015's statement/initializer
examples do not define universal call-argument traversal. Builtin specialization,
array evaluation and PFA contexts retain their documented boundaries. These are
explanatory limitations, not new syntax or normative permissions/prohibitions.
See [correctness remediation](../../docs/8.6/correctness-remediation.md) and the
historical [Phase 5C evidence](../../docs/8.6/compiler-contextual-reconciliation.md).
The inherited get/set-only hook restriction and its explicit discarded-declaration
divergence remain unchanged. PHP86-013/014/015 remain unresolved; the five existing
PHP86-001 production changes, fixtures and source pins are preserved.

Phase 5E adds source-backed yield/prefix, statement/declaration, scanner,
contextual and version-boundary evidence without changing EBNF or normative
specification clauses. The [final coverage expansion report](../../docs/8.6/final-coverage-expansion.md)
records exact alternative witnesses, parent-get-hook callable rejection and
ordinary-call control, 20 additional scanner entries, and six added acceptance
boundaries. Increased coverage is not a new language change or certification;
PHP86-013/014/015 remain open and PHP 8.6 remains draft.

Phase 5B expands finite conformance evidence without changing EBNF productions,
lexer behavior, or normative specification clauses. The
[coverage expansion report](../../docs/8.6/coverage-expansion.md) records common
syntax positive/negative pairs, additional PHP86-001/003–007 combinations,
scanner witnesses, and stage-specific boundaries. The
[case map](../../tests/fixtures/php/8.6/coverage-cases.json) identifies exact
source families, fixture hashes, selected chart alternatives, paired expectations,
and validation stages. Parent-set-hook PFA and trailing-comma conversion remain
compiler rejections; their discarded-closure control is accepted by RC4.
Diagnostic boundaries remain accepted in both versions and are recorded
separately from acceptance/removal metadata. The inherited discarded unknown-hook
restriction remains unchanged and explicitly tested as a known discrepancy.
PHP86-013/014/015 remain open; this evidence is not certification.

Phase 4B integrates explicit version selection, shared scanner logic with an
independent 8.6 configuration, stage-aware boundary evidence, and PHP change
traceability. See [draft integration](../../docs/8.6/draft-integration.md) and
[the reviewed change map](../../tools/8.6/change-map.json). These checks establish
functional draft integration, not discovery closure or release certification.
The Phase 3 test-local configuration descriptions below record their original
runs; the current focused tests use the registered package and its 8.6 lexer.

The [case manifest](../../tests/fixtures/php/8.6/pfa-cases.json) maps 57 focused
fixtures to parser/compiler or PHPT evidence, separate structural expectations
for both versions, lint expectations, and RC4 diagnostics where applicable.
[Focused PHPUnit tests](../../tests/Php/Conformance/Php86PartialApplicationTest.php)
exercise the production changes, both grammar versions, list disjointness, clone
ambiguity, tokens, parity, and boundary metadata. Phase 3B used a test-local
manifest; Phase 4B exercises the registered package through the public API.

Run `php tools/8.6/pfa-check.php --php85=<executable> --php86=<executable>
--sources=<RC4-tree> --report=<output.json>` for traceability, source hashes, and
paired lint. Omitted source/interpreter options are explicitly recorded as not
run. This focused checker does not implement the full future release contract.
Actual command results and limitations are recorded in the
[Phase 3B report](../../docs/8.6/pfa-implementation.md) and
[lint results](../../docs/8.6/pfa-validation.json).

For PHP86-010, run `php tools/8.6/deprecation-check.php --php85=<executable>
--php86=<executable> --sources85=<8.5-tree> --sources86=<RC4-tree>
--report=<output.json>`. This checks source hashes, acceptance, warning presence,
and diagnostic stage in isolated processes. The focused PHPUnit test verifies
both structural grammars and recorded evidence; neither changes version discovery.

## 10. Known limitations and outstanding uncertainties

The [Phase 3D discovery review](../../docs/8.6/discovery-final-review.md) records
the discovery dispositions and prioritized backlog. That review added no canonical
or normative change. Phase 3E now implements PHP86-010 warning-only documentation;
PHP86-013/014/015 remain open. Parameter metadata and the newly reconciled
property-cache optimization require no additional production. Phase 3B/C rules
and their historical validation evidence remain unchanged.

PHP86-013 (discovery gaps), PHP86-014 (assignment-based writable targets), and
PHP86-015 (compiler traversal and skipped validation) remain open. Phase 7H
narrows the unresolved explanation for PHP86-015 and CA86-001: maintainer
responses in [#24794](https://github.com/php/php-src/issues/24794#issuecomment-6455774723)
and [#24795](https://github.com/php/php-src/issues/24795#issuecomment-6455687623)
confirm dead-expression elimination can skip contextual compile errors and is
not specific to `printf()`. Both issues are closed as not planned, not fixed.
Earlier paired observations remain valid evidence at their recorded profiles;
the replies do not establish all dispatch/initializer boundaries. The inherited
get/set-only divergence still needs a separate Gate B correction or an accepted,
enforceable scope disposition. Both repository blockers remain; PHP86-014 is
unresolved. These are informative clarifications, with no EBNF, fixture, source
pin or release-gate change. See the
[Phase 7H resolution](../../docs/8.6/upstream-maintainer-resolution.md).
PHP86-003 through PHP86-007 have the bounded Phase 3C specification and evidence
scope above; their contextual validator integration remains unimplemented.
PHP86-010 is documented with the bounded diagnostic evidence above;
PHP86-008/009/011 retain correction, metadata, and optimization dispositions;
PHP86-012 proposals/API-only scopes remain excluded. PHP86-002 is explanatory
only. Phase 3C does not close broader compiler/discovery uncertainty. Its scope
does not alter the earlier PHP86-001 structural evidence or certify all contextual
combinations, delayed inheritance graphs, or optimizer profiles.

Exact-pin executable provenance, runtime callable/signature behavior, all
optimizer profiles and interactions, remaining parent-hook runtime combinations,
complete initializer-context combinations, complete contextual enforcement, and
final-release reconciliation remain outstanding. Phase 4B supplies explicit
lexer configuration, public manifest integration, bounded change traceability,
and stage-aware boundary evidence. It resolves the prior directory/manifest and
empty-boundary test failures while retaining their intended invariants.
Inherited certification evidence is not refreshed.
The draft is available for further independently verified Gate B work, not for
release certification. No completeness or release-certification phase is started.
