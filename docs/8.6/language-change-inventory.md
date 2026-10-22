# PHP 8.6 language-change inventory

Stable IDs refer to the [selected pins](source-discovery.md), not moving master.
In every record, an upstream path is relative to this immutable
[RC4 source tree](https://github.com/php/php-src/tree/f23b4c222cebe33be93ee8ecaaa14390c5507a08).
Function/production anchors identify the relevant implementation within it.
The [source hashes](source-evidence.json), [PHPT hashes](upstream-test-differences.json)
and [paired observations](syntax-probes.json) supplement these references.
Unless explicitly stated, lexical impact is **none identified**, and local test
obligations below are future work, not permanent fixtures created in this phase.

## PHP86-001 — Partial function application and callable argument lists

| Field | Finding |
|---|---|
| Classification / confidence | Addition and modification; verified source and paired lint for representative forms |
| PHP 8.5 | `f(?)`, `f(name: ?)`, and `f(...,)` rejected; `f(...)` callable conversion accepted |
| PHP 8.6 | Placeholder arguments accepted; callable conversion permits trailing comma; constant PFAs supported |
| Productions | `argument-list`, `ordinary-argument-list`, `first-class-callable-arguments`, `argument`, `argument-no-expression`, `constructor-argument-list`, `clone-argument-list`; constant-expression attachment aliases need review |
| Lexical impact | Existing `?` and `...`; no new tokens or scanner states |
| Contextual impact | Variadic placeholder once and last; no unpacking mixed with PFA; named/positional ordering; constructor, nullsafe conversion and attribute restrictions; constant arguments undergo constant-expression validation |
| Source evidence | `Zend/zend_language_parser.y`: `argument_no_expr`, `argument_list`; `zend_ast.c`: `zend_ast_create_arg_list*`, `zend_ast_arg_list_add`; `zend_compile.c`: `zend_compile_args_ex`, `zend_eval_const_expr`; AST `ZEND_AST_PLACEHOLDER_ARG` |
| Implementation | Syntax `5472cac806a60f1c30cd473b3dabea3b205d5d1b`; implementation `b5fba859eaa72b14c7c8bc08e4a6d5ce9774e7fd`; constant support `2d359a40b748308403100caec4891c09b9262aa7` |
| RFC | [PFA v2](https://wiki.php.net/rfc/partial_function_application_v2), implemented |
| Upstream tests | `Zend/tests/partial_application/`: `rfc_examples_overview.phpt`, `rfc_examples_const_expr.phpt`, `named_placeholders.phpt`, `clone.phpt`, `compile_errors_001.phpt` through `compile_errors_006.phpt`, `constexpr_*`, attribute and pipe optimization cases |
| Local obligations | Positive positional/named/mixed bound arguments and clone; structural negatives for malformed markers; contextual negatives for ordering/unpacking/constructors/nullsafe/attributes; constant and discarded-expression pairs; version boundaries including `f(...,)` |
| Action | Small EBNF list edits plus Arguments and arrays, Callable conversion, Attributes, Constant expressions and validation-order prose; contextual adapter review |

`false && f(..., ?);` is accepted by RC4 lint: unconditional EBNF rejection
would incorrectly enforce a compiler check before folding. Constant-expression
traversal now enters callable argument subtrees: a discarded unset cast inside
a PFA argument can still be rejected. Keep parser acceptance separate from
which validation runs in ordinary versus initializer compilation.

## PHP86-002 — Placeholder optionality follow-up

| Field | Finding |
|---|---|
| Classification / confidence | No additional grammar impact; source verified |
| 8.5 → 8.6 | No PFA → each `?` creates a required parameter; `...` preserves applicable optional parameters |
| Productions / context | Same argument productions as PHP86-001; callable signature/runtime behavior |
| Source / tests | `Zend/zend_partial.c`; `Zend/tests/partial_application/optional_placeholder.phpt` |
| RFC | [Optional placeholder follow-up](https://wiki.php.net/rfc/partial_function_application_optional_placeholder), accepted and reflected in source; the page's placeholder implementation URL is not proof |
| Local obligation / action | Avoid documenting `?` as optional; runtime signature tests are outside structural grammar; no separate EBNF edit |

## PHP86-003 — Readonly property defaults

| Field | Finding |
|---|---|
| Classification / confidence | Contextual relaxation; verified |
| 8.5 → 8.6 | Ordinary typed readonly property defaults rejected → accepted, including readonly classes |
| Productions | `property-declaration` and existing initializer syntax already represent the form; no EBNF addition |
| Context / source | `Zend/zend_compile.c` property compilation removes default prohibition; type and static prohibitions remain; commit `a2640aebc914b3c6fc997c379b1f91a2c4db8c55` |
| RFC | [Readonly property defaults](https://wiki.php.net/rfc/readonly_property_defaults), implemented, PR 22588 |
| Upstream tests | `Zend/tests/readonly_props/readonly_with_default.phpt` and inheritance, asymmetric visibility, trait mismatch, interface/hook variants; readonly-class defaults |
| Local obligation | Positive explicit/implicit readonly defaults; negative static/untyped/hook restrictions; live versus discarded declaration and version-boundary pairs |
| Action | Remove only the default prohibition from Properties contextual prose; preserve all other restrictions |

## PHP86-004 — Writes through constant objects

| Field | Finding |
|---|---|
| Classification / confidence | Contextual relaxation; verified |
| 8.5 → 8.6 | `OBJ->p=1`, `ARR[0]->p=1`, `unset(OBJ->p)`, reference to `OBJ->p` rejected by lint → accepted; direct `ARR[0]=1` still rejected |
| Productions | Existing variable/property/dimension expressions and assignment/unset syntax; no new terminal |
| Context / source | `Zend/zend_compile.c`: `zend_can_write_to_variable` tracks `via_prop`, `zend_mark_const_object_fetch`, delayed constant-object fetch; commit `b16cab7da8e5748b173542da42fc8b602ae59dea` |
| RFC | [Constant object property writes](https://wiki.php.net/rfc/const_object_property_write), implemented, PR 20903 |
| Upstream tests | `Zend/tests/gh10497.phpt`, `gh10497_class_const.phpt`, `gh10497_dim_arr.phpt`, `gh10497_dim_obj.phpt`, `gh10497_func_arg.phpt`, `gh10497_non_object.phpt`, `gh10497_write_targets.phpt`, `gh10497_write_targets_guardrail.phpt` |
| Local obligation | Write/reference/unset/inc and nested-dimension positives; direct constant and constant-array mutation negatives; distinguish compile acceptance from runtime object/type validity |
| Action | Update Writable variables and destructuring contextual rules; do not broaden arbitrary constant writes |

## PHP86-005 — Override on constants and enum cases

| Field | Finding |
|---|---|
| Classification / confidence | Contextual modification; verified source and selected lint |
| 8.5 → 8.6 | Override attribute target disallowed → class constants and enum cases may satisfy inherited nonprivate constant/interface requirements |
| Productions | Existing attribute groups, class constants and enum cases; EBNF unchanged |
| Context / source | `Zend/zend_attributes.stub.php` and generated arginfo target flags; compiler override flags; inheritance resolves matching nonprivate parent/interface constants; commit `4629b3e1fa1abd5e720af5c3885756a975db1812` |
| RFC | [Override constants](https://wiki.php.net/rfc/override_constants), implemented, PR 20478 |
| Upstream tests | `Zend/tests/attributes/override/constants/` and delayed constant/enum validation variants |
| Local obligation | Parent/interface positives, enum cases, missing/private target negatives, delayed linking; lint alone cannot discharge all cases |
| Action | Update Attributes and Class constants contextual prose and future context validation |

## PHP86-006 — Enum debug information

| Field | Finding |
|---|---|
| Classification / confidence | Contextual relaxation; source verified, not established by lint |
| 8.5 → 8.6 | Enum `__debugInfo` prohibited → permitted; other forbidden magic methods remain forbidden |
| Productions | Enum and method declarations unchanged |
| Context / source | `Zend/zend_enum.c`: `zend_verify_enum_magic_methods` removes the `__debugInfo` prohibition; actual macros still prohibit `__get` despite a misleading comment |
| RFC | [Debuggable enums](https://wiki.php.net/rfc/debugable-enums), implemented, PR 21425 |
| Upstream tests | Removed `Zend/tests/enum/__debugInfo.phpt`; new `Zend/tests/enum/debugInfo/` tests for magic method, backing value, missing magic method, parameters, return and visibility |
| Local obligation | Declaration/linking positive, forbidden magic-method negative, parameter/return constraints; document validation stage |
| Action | Update Enums contextual prose only |

Both oracle versions linted enum `__debugInfo` and `__get` successfully. Deferred
linking explains why those observations do not establish language validity.

## PHP86-007 — Nullsafe method calls in reference returns and yields

| Field | Finding |
|---|---|
| Classification / confidence | New contextual restriction; source and lint verified |
| 8.5 → 8.6 | `function &f(){return $x?->f();}` and corresponding by-reference yield pass lint → fatal `Cannot take reference of a nullsafe chain` |
| Productions | `return-statement`, `yield-expression`, function reference marker and existing nullsafe calls; EBNF unchanged |
| Context / source | `zend_compile_return`, `zend_compile_yield` use `zend_is_variable_or_call` and nullsafe reference assertion; commit `6173a9a10989fa887240590a34c2b0eec50f003e` (GH 20628), no dedicated RFC identified |
| Upstream tests | `Zend/tests/gh20628_001.phpt`; existing `Zend/tests/nullsafe_operator/` reference regressions provide surrounding coverage |
| Local obligation | Return/yield contextual negatives; ordinary calls and value returns positive; discarded enclosing closure and version-boundary pairs |
| Action | Update reference/control-flow contextual prose; do not reject a discarded declaration structurally |

## PHP86-008 — Qualified static type correction

| Field | Finding |
|---|---|
| Classification / confidence | Implementation correction and historical oracle defect; no proven valid-language removal |
| 8.5 → 8.6 | Fully qualified static already rejected; namespace-relative static has diagnostic differences and an observed 8.5.10 class-scope crash → clean rejection |
| Productions | Existing type/name forms; no new syntax |
| Context / source | `zend_compile_single_typename`, builtin static entry; commit `8d38af63ce9fd8346be95bf30600e4624b5a8b8f`, OSS-Fuzz 532353396; no RFC identified |
| Upstream tests | `Zend/tests/type_declarations/relative_types/static/fully_qualified_static.phpt`, `namespace_relative_static.phpt` |
| Local obligation | Preserve qualified-type negatives and discarded closure probes; reconcile exact-pin behavior before asserting a version-boundary acceptance difference |
| Action | Record historical defect separately; no 8.5 repair or speculative EBNF removal |

## PHP86-009 — Parameter doccomments

| Field | Finding |
|---|---|
| Classification / confidence | No grammar impact; source verified |
| 8.5 → 8.6 | Comments remain accepted trivia; parameter metadata retention changes |
| Productions / lexical impact | Parameter lists unchanged; no new comment token or scanner state |
| Source / RFC | Parser `backup_doc_comment` placement and parameter arg-info metadata; commit `d0852d54babbc668e27a3b95577830f9e6e8b237`; [Parameter doccomments](https://wiki.php.net/rfc/parameter-doccomments), implemented, PR 21279 |
| Upstream tests / obligation | Parameter/reflection doccomment tests; exact selected PHPT mapping remains part of PHP86-013; preserve ordinary parameter/default/comment acceptance |
| Action | No canonical production change; optional informative documentation only |

## PHP86-010 — Deprecated forms remain accepted

| Field | Finding |
|---|---|
| Classification / confidence | Documentation and contextual diagnostics; implemented warning sites verified |
| 8.5 → 8.6 | Forms remain accepted with new deprecations: `readonly` function name; `let`/`is` declaration and alias names; `_` constants/aliases; `namespace` class constants; returns from finally; constructor/destructor value returns and generators; nullable `__debugInfo` return |
| Productions | Identifiers/names, functions, constants, imports, return/yield/type forms remain; do not reserve `let` or `is` |
| Source | `zend_compile.c`: `zend_assert_valid_class_name`, `zend_begin_func_decl`, `zend_compile_use`, `zend_compile_return`, generator checks; `zend_constants.c` constant declarations; `zend_API.c` magic method and class constant checks |
| Commits | let/is `938a3110bf3a2f978628f1d1ba466308ac012e11`; underscore `0beb2a99d3beb586a1ee5a2c768e43a9218be2a1`; finally `e721b3fdc35dc74121ed27ed43c2f9568f321bee`; readonly `a9ba369abe43a4947e4015171f0111c4a816a630`; namespace `c383b8cae02338f32d2fa460f003223003df1acb`; constructors `18d783719d58420fa7f333175830fcf40c916bea` |
| RFC | [8.6 deprecations](https://wiki.php.net/rfc/deprecations_php_8_6) has stale pending-implementation metadata; [constructor returns](https://wiki.php.net/rfc/deprecate-return-value-from-construct) and actual source determine implemented warnings |
| Upstream tests | Deprecated-identifier and namespace-class-constant tests in the PHPT difference index; additional per-warning mapping required under PHP86-013 |
| Local obligation | Acceptance tests with warnings enabled; ensure warnings never become grammar negatives; preserve case/context variants |
| Action | Update Deprecated but accepted syntax prose; no token removals |

## PHP86-011 — Closure and call optimizations

| Field | Finding |
|---|---|
| Classification / confidence | No identified grammar change; source verified for caching, broader interactions provisional |
| 8.5 → 8.6 | Stateless closure caching changes allocation; no implemented automatic static inference |
| Productions / context | Closure, arrow, pipe, call and constructor syntax unchanged except PHP86-001/007; retain constant-closure static/capture checks |
| Source / RFC | Cache commit `f47cd216e9537301471d6af770998fe5fe5c784c`; [Closure optimizations](https://wiki.php.net/rfc/closure-optimizations) partially implemented; August 11 errata excludes static inference |
| Upstream tests | Closure/call/pipe optimizations; `Zend/tests/gh20628_002.phpt` demonstrates pipe reference behavior; optimizer test reconciliation remains PHP86-013 |
| Local obligation / action | Preserve closure constant-expression negatives, reference and pipe compatibility; no speculative grammar change |

## PHP86-012 — Excluded proposals and library changes

| Field | Finding |
|---|---|
| Classification / confidence | Excluded from implemented syntax; proposal metadata captured, individual library integrations not all certified |
| 8.5 → 8.6 | No parser implementation identified for the candidate syntax listed below; library APIs are expressible using existing calls/classes |
| Productions / lexical/contextual impact | No authorized changes; a draft PR is not implementation evidence |
| Source / RFC / tests | Complete captured page list, status, targets and implementation links in [rfc-review.json](rfc-review.json); full parser/token diff is negative syntax evidence; no conformance fixtures for unimplemented syntax |
| Local obligation / action | Recheck ambiguous statuses and selected-pin integrations before closure; retain excluded disposition; no implementation |

Candidates include array shorthand, function autoloading, left-to-right
assignment, native markup, strict namespaces, literal scalar types (target 8.7),
primary constructors, serializable closures, friends, scope functions, readonly
variables, trailing Boolean operators, pipe-to-return, PFA implicit this, typed
aliases, pattern matching, namespace visibility, context managers, nullable casts,
void-as-null, data classes, immutable collections, extension methods, deprecated
interfaces, switch fallthrough, function composition and static constructors.
The captured case-sensitive-PHP page is withdrawn. Draft/discussion statuses
and targets are recorded individually; placeholders in page metadata remain
unresolved rather than being treated as votes. Older PFA proposals must not be
confused with implemented v2. No assertion is made that every historical rejected
RFC was individually reread. Recent rejected/withdrawn index entries still need
an explicit final cross-check.

## PHP86-013 — Remaining exhaustive-audit work

| Field | Finding |
|---|---|
| Classification / confidence | Unresolved research completeness, not a proposed language feature |
| 8.5 / 8.6 behavior | No blanket equivalence conclusion; 1,124 changed Zend PHPTs indexed, not individually semantically reconciled |
| Productions / context | Reference/list/pipe interactions; constant folding and delayed declarations; possible compiler/optimizer interactions outside identified findings |
| Source evidence | Full-tree hashes, core-file inventory, raw local diffs, compiler commit history and changed test index; absence of durable per-hunk disposition prevents exhaustive certification |
| RFC evidence | Per-library selected-pin verification and recent rejected/withdrawn index reconciliation incomplete; captured metadata is not full implementation verification |
| Upstream/local obligation | Triage all changed test families including relevant ext/opcache tests; attach each acceptance difference to source and minimal paired probe; add precise per-finding PHPT anchors where currently only a family is recorded |
| Action / disposition | Finish research before Phase 3 readiness; do not invent productions, silently waive coverage, or repair historical 8.5 defects |
