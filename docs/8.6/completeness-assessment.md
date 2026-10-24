# PHP 8.6 Phase 5A completeness assessment

October 24, 2026. **Assessment complete; PHP 8.6 remains an incomplete `draft`.**
Targeted Phase 5B evidence remediation may proceed under the finding-specific
conditions in section 11. Grammar-completeness, candidate promotion, and stable
certification are **NOT READY**. This assessment makes no grammar correction,
closes no discovery finding, and does not begin Phase 5B.

The current positive corpus exercises **209/361 productions (57.89%)** and
**223/800 alternatives (27.88%)**. All five modified PFA productions and their
15 alternatives have positive chart evidence. These percentages measure test
coverage, not language completeness, unique derivations, or contextual validity.
There is one independently reproduced inherited acceptance discrepancy and
substantial evidence gaps. No additional missing PHP 8.6 syntax is established
by this assessment.

## 1. Audit baseline and source profile

Repository commit: `11496fba8ae706b05bc500d99b8c0326a41054fc`.
Initial Git status was clean. Registration remains schema 1.0, PHP 8.5 `stable`
and PHP 8.6 `draft`. The manifest selects the independent 8.6 grammar,
specification, fixture directory, `source-file` root, and `8.6` lexer identity.
PHP 8.5 remains the recommended stable/default maintainer version;
`latestVersion()` still returns numeric latest 8.6, including drafts.

| Artifact | Audited SHA-256 |
|---|---|
| `grammar/8.5/php.ebnf` | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| `grammar/8.5/php.md` | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |
| `grammar/8.6/php.ebnf` | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| `grammar/8.6/php.md` | `0a75ca80dff07af7165b010ad6adfea14218bc1f5b3747f822da2e7aba5b7ede` |
| `grammar/8.6/changes.md` | `8b4ae4e05224c1f4ad9c88d937e7c94e898f005c6c64b676588817999f8c5f48` |
| `php-grammar.json` | `56f05d24b50bade238cc1d03d2c41b2036b8ded435fcbddea0db719d903d42fb` |

Immutable upstream pins are unchanged:

- PHP 8.5, branch `PHP-8.5`: `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.
- PHP 8.6, branch `PHP-8.6`, tag `php-8.6.0RC4`:
  `f23b4c222cebe33be93ee8ecaaa14390c5507a08`.

Interpreters are the existing Windows NTS x64 distributions: PHP 8.5.10,
built August 25, 2026, and PHP 8.6.0RC4, built October 21, 2026. Their SHA-256
values are respectively `32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9`
and `c545e69850c3d8af1cd2edef4d1682ad07232a231b7f49a06657538ccdf1db0a`.
Neither is proven to have been built from the exact source pin. Runs use `-n`,
byte-oriented source, `zend.multibyte=0`, and no enabled opcache/JIT. The
integration matrix explicitly tests both short-tag profiles. PHPUnit uses
8.5.10 with its mbstring extension; this is not a full-suite run under RC4.

Reviewed authority includes [versioning](../versioning.md),
[sources](../sources.md), [grammar conventions](../grammar-conventions.md),
[conformance policy](../conformance-policy.md), [API contract](../api.md),
[draft integration](draft-integration.md), the Phase 3B/C/D/E evidence,
the current standalone artifacts, and the historical
[8.5 completeness report](../8.5/completeness.md). Historical coverage and
certification conclusions are not transferred to 8.6.

## 2. EBNF production coverage

[The coverage ledger](completeness-coverage.json) inventories every production
and alternative with reachability, positive fixture identities, negative
attempts, change mapping, source-evidence status, and contextual limitations.
It uses the existing `GrammarCoverageAnalyzer`, `CoverageIdentityMap`, and
`CoverageCollector`, with **82 valid files, 13 structural-invalid files, and
zero additional rule-level cases**. Contextual/runtime files are inventoried
separately and do not inflate positive coverage.

The method counts completed chart items while recognizing an accepted input.
It does not prove that an item belongs to a unique complete derivation.
Failed inputs contribute only to attempted coverage: **252/361 productions**
and **496/800 alternatives**. Attempting a rule is not positive coverage or
proof that a negative isolates that rule.

| Corpus classification | Productions | Alternatives |
|---|---:|---:|
| Directly exercised by an independently selected rule in this coverage run | 0 | 0 |
| Indirectly exercised, with no uncovered alternative in that production | 123 | 223 |
| Partially exercised: production hit, but some alternatives unexercised | 86 | 0 |
| Uncovered, requiring witnesses or investigation | 89 | 393 |
| Excluded from chart-body coverage with graph-based justification | 63 | 183 |
| Unresolved scanner-context classification | 0 | 1 |
| Total | 361 | 800 |

The 123 indirectly exercised and 86 partially exercised productions together
form the 209 raw positive hits; they are not double-counted. Alternative rows
record individual branch hits, so their partial category is empty. All families
remain bounded even where all current alternatives are hit. Separate PHPUnit rule-selection tests exist
for ordinary, callable, and clone lists; the zero direct count above does not
claim those tests are absent.

All **361/361** productions are reachable from the configured roots
`source-file`, `whitespace`, and `comment`; **350/361** are reachable from
`source-file` alone. The other 11 are the intentionally separate trivia graph.
Parsing, references, permitted nullability, and structural integrity pass.
Structural validity of an alternative does not establish correct PHP semantics.

The 63 chart exclusions comprise 52 primitive-bypassed productions and 11
trivia productions; the 183 alternative exclusions comprise 174 and nine
respectively. These classifications are recomputed on the 8.6 graph using the
shared matcher primitive list. Their reason is architectural, not copied
8.5 conformance evidence. Scanner correctness still needs separate evidence.
`reserved-non-modifiers/alternative:25` remains unresolved for independent
8.6 classification; its inherited `enum` lookahead explanation is not a fresh
8.6 witness.

Representative uncovered syntactic families are alternative control flow,
loops, switch/match, break/continue/goto, try/finally, global/static statements,
trait use/adaptations, grouped imports, arrow/anonymous-class forms, closure
captures, variable variables, and several precedence/prefix/yield contexts.
The exact 89-production and 393-alternative queues are machine-readable;
absence of a fixture is not evidence that the grammar rejects the construct.

## 3. Alternative-level and PFA coverage

The denominator is **800**, compared with 792 in the current 8.5 grammar.
The eight additional identities arise within PHP86-001's five modified
productions; no production was added or removed. Counts include nested
`AlternativeNode` branches in canonical traversal order, not optional/repetition
states and not Zend parser alternatives.

| Modified production | Positive alternatives / total | Independent evidence and residue |
|---|---:|---|
| `ordinary-argument-list` | 4/4 | Positional/named placeholders, mixed bound values and ellipsis; direct disjointness tests against FCC lists. |
| `first-class-callable-arguments` | 0/0 | No alternative node; `(...)` and `(...,)` are independently tested. Optional comma is not an alternative denominator. |
| `clone-argument-list` | 3/3 | Placeholder, bound, named, array and trailing-comma witnesses; direct `($object)` rejection and `($object,)` acceptance. |
| `argument` | 3/3 | Expression, non-expression and bare ellipsis; unpacking and ordering contextual negatives remain separate. |
| `argument-no-expression` | 5/5 | Includes nested named-expression/placeholder branch identities; named ellipsis and placeholder-expression structural negatives. |

All five production bodies are exercised. The collector's optional/repetition
fields are not a demonstrated full path metric for this chart run; empty
optional event lists must not be interpreted as proof that commas were untested.
The existing `Php86PartialApplicationTest::testCallableAndOtherListsRemainDisjoint`
provides explicit selections and opposite-rule rejection checks.

Function, dynamic, object-method, static-method, nested and immediately invoked
PFAs have positive fixtures. Clone has its distinct syntax controls. Constructor,
anonymous-constructor, nullsafe and attribute-list cases are intentionally
structurally accepted but compiler-invalid. Parameter defaults, global constants,
static-method constants, named constants, and nested attribute values have
bounded positive evidence; variables, names, unpacking, order and constant
traversal have negatives. Discarded closure/ordinary-folding controls prevent
an unconditional structural interpretation of compiler restrictions.

Missing evidence includes parent-property-hook PFA rejection/repair and
discarded contexts, trailing-comma callable forms across every method/dynamic/
constructor/nullsafe/initializer context, and wider initializer/optimizer
combinations. The 15/15 result does not close these obligations. No current
per-alternative negative-isolation metric exists: the ledger preserves visits
to negatives but marks boundary attribution unresolved rather than counting
speculative chart branches as verified rejection coverage.

## 4. Lexer and scanner coverage

[The source inventory](completeness-source-inventory.json) re-extracts the
pinned scanner rule headers: **190/190 match in order** across the pins.
This is textual pattern/state-header correspondence, not equality of C actions,
error recovery, helper functions, parser feedback, or executable state products.
The Phase 3D review records AST compilation extraction, storage/type changes,
and heredoc exception-state changes; those limitations remain.

`PhpVersion::php86()` selects an independent version identity and the shared
keyword table. PFA reuses `?`, `...`, colon and commas; `??`, `?->`, and unpacking
remain distinct. `let` and `is` are not newly reserved. No new scanner state,
token spelling, or precedence declaration is established by the reviewed diff.

The independent integration evidence has **11 source/profile entries**, all
with paired lint; **seven** have native-tokenizer lexeme-boundary comparisons.
This is 7/11 entries with token comparison, not 7/190 scanner rules. There is
**no independent complete RC4 rule-to-witness ledger**, so a scanner coverage
percentage is not established. The inherited inventory has 25 lexical families
and 11 scanner states; its thousands of 8.5 cases remain 8.5 evidence.

| Family/state area | Current 8.6 evidence | Missing or bounded evidence |
|---|---|---|
| Keywords, contextual identifiers, property names | `names`, `property`; PHP86-010 paired diagnostics; focused keyword controls | Full reserved-word position matrix, enum and yield-from lookahead, qualified names and high bytes. |
| Operators/PFA | `pfa`, `operators`; focused placeholder/comment/unpacking token tests | All operator adjacency and scanner-feedback combinations. |
| Strings/interpolation | `interpolation`, integration strings, malformed-string test | Nested states, backticks, escape/Unicode boundaries, simple offsets and embedded binding. |
| Heredoc/nowdoc | One entry each, ordinary fixtures; Phase 3D malformed probes | Reentrant exception state, nested labels/interpolation, all indentation/newline/EOF boundaries. |
| Numeric literals | Incidental decimal literals in fixtures | Dedicated base/separator/overflow/exponent positive and malformed matrices. |
| Comments/whitespace | `comment`, PFA comment boundary, malformed-comment test | Doc/hash/property comments, NUL, FF/VT, yield-from composite comments. |
| PHP tags/HTML | Short on/off and echo-tag entries | Long-tag boundaries, repeated PHP/HTML transitions and nested interpolation transitions. |
| Shebang, halt payload, raw bytes | Inherited implementation and documentation | Independent 8.6 direct rule witnesses and boundary classification. |

The generic `bin/lexer-coverage.php` is explicitly hard-coded to the 8.5 audit.
It is not an 8.6 report even if passed an extra version argument. No historical
ledger was regenerated to manufacture independent RC4 coverage.

## 5. Positive and negative fixture coverage

| Top-level 8.6 corpus | Files | Interpretation |
|---|---:|---|
| `valid` | 82 | Structural acceptance; later-stage expectations remain case-specific. |
| `invalid` | 13 | Structural rejection. |
| `contextual-invalid` | 40 | Structural acceptance with external compiler/linking rejection. |
| `runtime` | 1 | Nonobject evaluation failure; not syntax rejection. |
| Total | 136 | File inventory, not number of distinct language rules. |

The three file manifests provide 57 PFA records (32 positive, eight structural
negative, 17 contextual negative), 63 contextual records, and 16 compatibility
records (11 positive, five structural negative). They account for the 136 files.
The separate 67 inline deprecation witnesses must not be added to file coverage.
The contextual manifest separates **38 lint, 22 declaration-link, and three
controlled-runtime** records. Lint observations accompanying a later-stage
record do not replace its required stage.

Fresh PFA/context/integration checks verify paired interpreter expectations,
source/fixture hashes and diagnostics. All 13 structural negatives reject;
contextual negatives continue to match structure. No stage misassignment was
found in these reviewed manifests. Enum `__debugInfo` is the decisive control:
8.5 lint can succeed while linking rejects. Scalar-as-object failure remains
in `runtime`, and nullable/nonpublic `__debugInfo` diagnostics are not structural
errors. Readonly reassignment is evaluated separately from default syntax.

The five compatibility structural negatives concern types, arguments,
delimiters, expressions and empty attributes. They do not cover the missing
control-flow, traits/imports, lexical and precedence families listed above.
Nearby invalid counterparts and live/retained/discarded combinations are
underrepresented outside the focused findings. Reusing relevant historical
witnesses after pin review is appropriate; wholesale duplication of the 8.5
corpus would not establish source correspondence or predicate isolation.

## 6. Contextual specification coverage

The source ledger retains all **73 inherited restriction descriptions**, each
with an explicit current normative-clause/production-family mapping, inherited
source/test evidence, and unresolved RC4 predicate-isolation status. Those 73
are an inventory, not 73 proven or implemented RC4 checks. Some descriptions
include structural or resolved-value obligations; they are not a denominator
of purely contextual compiler predicates.

All seven changed constraint/diagnostic groups below have normative prose and
focused external evidence, but **0/7 have new whole-source contextual enforcement
in the repository matcher**. This denominator is seven reviewed groups, not
every PHP restriction. Rule-level modifier-list validation does not supply
whole-source declaration scope or folding semantics.

Fixture names below are relative to `tests/fixtures/php/8.6/`; exact paths,
hashes, expectations and source references are in the linked case manifests.

| Finding / normative clause in `php.md` | Productions / pinned source | Positive, negative and enforcement gap |
|---|---|---|
| PHP86-001: Argument constraints, Callable conversion, Constant-expression validation order | Five changed list productions; `function-call`, `attribute`, constant wrappers. Parser `argument_no_expr`; compiler `zend_compile_args_ex`, `zend_compile_call_common`, `zend_compile_const_expr_fcc`, `zend_compile_const_expr_args`; AST callable markers. | `valid/pfa-basic.php`, `pfa-default.php`, `pfa-attribute-value.php`; `contextual-invalid/pfa-named-order.php`, `pfa-constructor.php`, `pfa-constant-traversal.php`, discarded repairs. Parent-hook and wider initializer coverage missing; no compiler enforcement. |
| PHP86-003: Properties | `property-declaration`; `zend_compile_prop_decl`, inheritance property checks. | `valid/context-readonly-default.php`, `context-readonly-class.php`; untyped/static/hook/new/type negatives and inheritance/reassignment cases. No source-context validator. |
| PHP86-004: Writable variables and destructuring | `variable-expression`, `assignment-expression`, `unset-statement`, `foreach-statement`; `zend_can_write_to_variable`, `zend_mark_const_object_fetch`. | `context-object-*` property/dimension/reference/destructuring positives and constant-array guards; nonobject runtime case. No general writable-target enforcement; PHP86-014 excluded. |
| PHP86-005: Class constants, Attributes | `attribute`, `class-constant-declaration`, `enum-case`; target flags, `zend_compile_class_const_decl`, inheritance constant matching. | `context-override-parent`, interface/enum/protected/delayed positives; missing/private/final/type negatives. All linking is external; reentrant graph coverage incomplete. |
| PHP86-006: Enums | `enum-declaration`, `method-declaration`; `zend_verify_enum_magic_methods`, `zend_check_magic_method_implementation`. | `context-enum-debug`, backed/untyped-return positives; get/parameter/return/static negatives and visibility-warning/lint-limit controls. No repository magic-method/link validator. |
| PHP86-007: Control-flow constraints | `return-statement`, `yield-expression`, `function-declaration`; `zend_compile_return`, `zend_compile_yield`, `zend_assert_not_short_circuited`. | Nullsafe reference return/yield negatives; value, ordinary reference, foreach and discarded positives; unreachable/dead negatives. No blanket nullsafe or folding rule is justified. |
| PHP86-010: Names and tokens, Functions, Namespaces and imports, Class constants, Control-flow constraints | Identifier/declaration/type/import/return/yield rules; compiler name/use/return checks, `zend_register_constant`, API magic-method checks and debug handler. | 67 inline witnesses in `deprecation-cases.json`, with alias/method/case/scope/discarding controls and a pre-existing invalid readonly import. Accepted diagnostics stay accepted; no warning engine in matcher. |

PHP86-002 explains required versus optional placeholder behavior; it adds no
production, and runtime signature certification is deferred. PHP86-008 is the
qualified-static historical correction/crash disposition, not newly accepted
syntax. PHP86-009 is parameter metadata capture, not a new grammar feature.
Their absence from the acceptance-boundary index is not automatically a defect;
qualified-static and metadata compatibility evidence still needs explicit
version-specific indexing before claiming a complete inventory.

The general inherited clauses for types, parameters/captures, hooks, trait
aliases, control scopes, namespace/declare rules and constant-expression
folding remain mandatory specification material. Per-predicate RC4 negative/
repair evidence and source correspondence are incomplete. PHP86-015 prevents
turning ordinary versus constant traversal examples into a universal call rule.
Documentation, upstream PHPT source review, and external lint are not local
contextual enforcement.

## 7. Version-boundary completeness

All **13/13 registered boundary records** are discovered and checked for both
versions: six structural, four lint, and three declaration-link records.
The six structural cases cover basic/named/mixed/clone/constant PFA and FCC
trailing comma, with first support 8.6. Readonly default, constant-object property,
Override parent/enum and enum debug cases also start at 8.6 at their required
stages. Nullsafe reference return/yield records have first and last supported
version 8.5, so RC4 rejection is explicit.

The generic runner rejects nonstructural records rather than representing a
structural match as a compiler verdict. Integration checks associate each
contextual boundary with its reviewed case and invoke the correct stage.
No diagnostic-only record misrepresents deprecated acceptance as removal.

The index is representative, not exhaustive. Missing dedicated metadata includes
method/static/dynamic PFA and clone FCC trailing-comma combinations, parent-hook
constraints, and broader initializer contexts. Many property/reference and
linking variants already have paired evidence in the focused manifests without
individual boundary JSON files: distinguish an indexing gap from absent tests.
PHP86-010 uses paired warning-stage evidence instead of acceptance/removal
metadata, which is appropriate. No lexer-specific acceptance change is verified
that would require a new first-supported-version record.

## 8. Upstream source correspondence

Fresh source-lock checks pass. The assessment parser inventory uses the existing
`ParserReconciliation::branches()` tokenizer on both pinned files, retaining
every RHS/action. RC4 has **176 productions and 623 alternatives/actions**;
8.5 has 177 and 623. The removed `backup_lex_pos` is an empty internal action,
not a missing PHP syntax production.

Nine parser bodies differ: `parameter`, `argument_list`,
`non_empty_argument_list`, `clone_argument_list`, `non_empty_clone_argument_list`,
`argument_no_expr`, `trait_method_reference`, `absolute_trait_method_reference`,
and `inline_function`. PHP86-001 explains the argument changes; PHP86-009 explains
parameter comment metadata; the Phase 3D review explains trait AST-kind changes
and the removed backup action. The other **167/176** current bodies have equal
normalized RHS/action records. Equality does not prove unchanged compiler
behavior, because called functions may change.

All current parser rows retain inherited canonical anchors as navigation aids,
explicitly labeled unverified for complete RC4 reconciliation. There is no new
claim of 176 independently certified semantic mappings. Scanner headers are
reconciled separately in section 4. Compiler/AST/optimizer traversal, linking,
reference handling, and diagnostic predicates remain the material gaps under
PHP86-013/014/015. No unrelated library-PHPT census is required by this assessment.

The RFC inventory retains the 58 original identities plus the Phase 3D
direction-aware property-resolution candidate. PFA, readonly defaults,
constant-object writes, Override, enum debug, parameter metadata and diagnostics
have selected-pin evidence. Proposed generics/static inference and excluded API
changes do not become productions. Component/reversion/follow-up reconciliation
is still incomplete; an RFC target or ancestry alone is not implementation proof.

**Verified inherited acceptance divergence:** the existing 8.5
`boundary-hook-name-discarded.php` witness was tested unchanged against both
versions. Both interpreters accept it at lint; both grammars reject it.
An unknown live hook rejects, and a normal `get` repair accepts in both.
[Six paired lint observations](completeness-probes.json) preserve exact sources,
hashes and results. RC4 parser `property_hook` at line 1160 accepts an identifier;
compiler `zend_get_property_hook_kind_from_name` at 9348 and hook validation at
9409–9412 reject unknown names when compiled. The specification intentionally
restricts `hook-name` to get/set, including discarded declarations. This is a
documented language-policy divergence, not an unreported implementation mistake
or proof of missing new 8.6 syntax. Exact executable correspondence remains limited.

## 9. Change-document traceability

[Fresh traceability validation](completeness-traceability.json) passes all ten
required headings in order, IDs, source pins, fixture hashes/paths, production
references, generated EBNF parity, reviewed non-EBNF input hashes, and
**13 reviewed documentary sections**. Structural comparison finds exactly
five modified productions, zero additions/removals, and no unexplained EBNF
differences. Before/after trees preserve optionality, repetition and token
references rather than checking production names alone.

Sections 1–3 record baseline, PFA additions and list changes; section 4 separates
no implemented removal from incomplete discovery; section 5 records lexical
reuse; section 6 maps contextual and diagnostic changes; sections 7–9 record
compatibility, immutable sources and fixtures/results; section 10 retains
limitations. Implemented material changes have previous/new behavior, affected
productions or clauses, local evidence, and boundary records or explicit
remaining obligations. PHP86-002/008/009 are explanatory/correction/metadata
dispositions rather than invented structural deltas.

This is traceability completeness for the reviewed implementation, not complete
language discovery. The document openly defers parent-hook, initializer,
optimizer, signature and provenance obligations. Hash agreement detects drift;
it does not prove every sentence semantically correct or close those obligations.

## 10. Unresolved findings and blockers

| Finding | Grammar/specification and test impact | Candidate/stable impact |
|---|---|---|
| PHP86-013 | Material compiler/AST/optimizer/PHPT/RFC association remains incomplete. Broader linking and source-state interactions lack dispositions. Unknown syntax differences remain possible, not established facts. | Blocks complete discovery and a belief-based candidate completeness claim; blocks stable certification. Independent bounded work can proceed. |
| PHP86-014 | Assignment-based dimension writes have a source-explained compiler/VM inconsistency; reference results differ from TMP ordinary/list results. Intended validity remains unresolved. Current clauses do not authorize an expansion. | Blocks writable-target edits and certification of that area; preserve distinct parse/lint/execution observations. |
| PHP86-015 | Direct optimized printf can visit/discard nodes differently from ordinary/dynamic/namespace calls. Existing research records both acceptance directions. No universal traversal or builtin-specific grammar rule follows. | Blocks complete validation-order claims and affected edits; prevents stable certification while unexplained. |
| Exact-pin/final-release provenance | Distribution labels and hashes identify tested binaries, not exact builds. RC4 is not a final-release review. | No exact-pin executable or final-release certification. |
| Independent evidence/release integration | Missing classified RC4 scanner/predicate/binding matrices; historical release evidence has shared-input freshness dependencies. | Full independent 8.6 release certification is not available. No gate waived. |

The hook discrepancy is separately tracked as CA86-001 below. None of these
findings is closed or rewritten by this assessment.

## 11. Prioritized remediation backlog

Priorities describe evidence and conformance risk, not automatic permission to
edit grammar. Every item retains its dependency and Gate B disposition.

| ID / priority / area | Evidence and required remediation | Expected tests | Dependencies and Gate B |
|---|---|---|---|
| CA86-001 — Critical for unrestricted acceptance-equivalence claims: discarded hook names | Reproduced inherited intentional divergence in `completeness-probes.json`; `hook-name` and Property hooks and promotion clause. Review and explicitly retain the bounded policy or separately propose a correction; do not silently change 8.5. | Live/retained/discarded unknown hooks, get/set repairs, source-stage checks on both pins. | Source mechanism verified; policy decision required before a correction. Gate B evidence sufficient for scoped investigation, not authorization to reverse intentional policy. |
| CA86-002 — High: discovery/source correspondence | PHP86-013 and nine changed parser bodies; 167 equal bodies do not settle compiler actions. Finish material source/test/RFC associations and independent RC4 mapping. | Relevant parser/compiler/AST and reentrant-linking regression families, explicit exclusions and immutable hashes. | Depends on PHP86-013 closure/bounding. Gate B not ready for unspecified edits; research ready. |
| CA86-003 — High: writable targets | PHP86-014 ordinary/list/reference dimension-write observations and compiler/VM operand evidence. Establish intended accepted language before changing rules. | Read/write/unset/inc/reference, ordinary/list/reference bases, live/discarded and execution-stage controls. | Exact source-corresponding evidence and authoritative disposition needed. Gate B blocked. |
| CA86-004 — High: optimized validation order | PHP86-015 direct/imported/qualified/namespace/dynamic printf and sprintf evidence. Isolate actual traversal and intended compatibility boundary. | AND/OR, ternary/coalesce, live/dead/discarded, one/two arguments and optimizer/fallback profiles. | Depends on discovery/optimizer review. Gate B blocked for generalized rules. |
| CA86-005 — High: parent-hook PFA and cross-context boundaries | `changes.md` explicitly defers fixtures despite `zend_compile_parent_property_hook_call` prohibition. Add isolated rejection and repaired ordinary-call evidence; index meaningful missing boundaries. | Parent hook PFA/FCC/trailing comma, live/discarded declaration; method/static/dynamic/constructor/nullsafe variants in both versions. | Existing prohibition has pinned source evidence; verify scope and interactions. Gate B ready for bounded evidence work; grammar edits require a demonstrated mismatch. |
| CA86-006 — High: contextual source correspondence | 73 inherited descriptions, seven focused changed groups, no whole-source enforcement. Map each predicate to RC4 source and clause; label out-of-scope resolved semantics and documentary omissions individually. | Negative/repair and live/retained/discarded tests at lint/link/runtime as applicable. | No token-pattern substitute for scope/folding. Gate B ready for independently verified clause/evidence work; no blanket validator implementation readiness. |
| CA86-007 — Medium: production/alternative corpus | 89 uncovered productions, 393 uncovered alternatives and one unresolved enum alternative. Add focused witnesses and classify legitimate exclusions. | Control-flow, traits/imports, arrows/captures, dereferencing, prefix/yield/precedence and relevant repairs; verify both versions where unchanged. | Select by ledger identity and pinned source. Gate B ready for evidence-only cases after per-case review; no coverage target. |
| CA86-008 — Medium: negative isolation | 13 structural negatives and no per-alternative rejected-boundary attribution. Build explicit negative/repair mapping without treating chart attempts as rejection causes. | Missing elements, invalid alternatives/order/delimiters, optional/repetition edges and discarded-context controls. | Depends on source-stage classification and CA86-007 families. Gate B ready for bounded fixtures, not unconditional compiler restrictions in EBNF. |
| CA86-009 — Medium: scanner matrix | 190 equal headers, 11 source/profile entries, no independent 190-rule witness ledger. Reconcile helpers/actions and annotate all 25 inherited families and 11 states for RC4. | Numeric, escape, byte, shebang/halt, enum/yield-from, comment, tag, nested interpolation/heredoc positive and malformed pairs. | Preserve 8.5 ledger; new independent evidence and both tag profiles. Gate B ready for tests; any lexer correction requires a verified mismatch. |
| CA86-010 — High: certification provenance/orchestration | Distribution builds unverified; full 8.6 release runner/evidence absent; Phase 4A shared-input freshness limitation retained. Establish independent source/build/release evidence. | Exact-build differential and binding matrices, all applicable release gates, incremental later-RC/final review. | Build provenance and CA86-002–009 dispositions; not ready for certification. No release gate changes in this phase. |
| CA86-011 — Low: evidence navigation | Representative boundary index omits paired cases already present; inherited 8.5 labels/line references can be mistaken for RC4 certification. Improve links and scope labels without changing old evidence. | Link/anchor checks, fixture hash association, metadata stage checks. | Depends on audited mappings. Gate B ready for documentary clarification only. |

## 12. Validation, preservation and next-phase readiness

All commands ran from the repository root. `P` means
`rtk proxy .audit/php85/php.exe -n`. Focused checkers used
`--php85=.audit/php85/php.exe --php86=.audit/php86/php.exe`;
PFA used `--sources=.audit/php-php-src-f23b4c2`; the others used
`--sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2`.
Reports were directed only to new `docs/8.6/completeness-*.json` artifacts.

| Actual command/selection | Result |
|---|---|
| `P tools/validate-manifest.php` | PASS, schema 1.0 and package paths. |
| `P bin/grammar-coverage.php 8.6` and assessment driver | PASS, 209/361 productions, 223/800 alternatives; all identities classified for this assessment, with unresolved evidence explicit. |
| `P tools/8.6/pfa-check.php` with source/interpreters | [PASS](completeness-pfa-validation.json), 225 checks, 114 paired observations, zero failures. |
| `P tools/8.6/context-check.php` with sources/interpreters | [PASS](completeness-context-validation.json), 278 checks, 126 paired stage observations, zero failures. |
| `P tools/8.6/deprecation-check.php` with sources/interpreters | [PASS](completeness-deprecation-validation.json), 712 checks, 268 observations, zero failures. |
| `P tools/8.6/integration-check.php` with sources/interpreters | [PASS](completeness-integration-validation.json), 431 checks, 126 observations, zero failures; lexical profiles and all 13 boundaries included. |
| `P tools/8.6/change-traceability.php` | PASS, five modifications, 13 documentary sections, ten required headings, no errors. |
| `P -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress` | **PASS: 5,673 tests, 53,083 assertions, zero failures**, 3:09 elapsed. Includes EBNF integrity/parity, lexer, positive/negative conformance, boundaries, PFA/context/deprecations, PHP 8.5 regression and public APIs. |
| Assessment hook probes | Three sources, six lint observations; one expected inherited divergence, live negative and valid repair confirmed. Not a new conformance pass claim. |

[Captured command outputs and assessment integrity checks](completeness-validation.json)
retain the actual results. Selections overlap; do not sum their counts. PHPUnit checks and interpreter
observations are different units. No upstream PHPT suite, exhaustive scanner
product, AST-binding certification, exact-pin build, or complete release runner
was executed. The 8.5-only lexer report and historical certification were not
regenerated. A passing full suite does not resolve the recorded completeness gaps.

[Preservation evidence](completeness-preservation.json) records all 1,776
pre-existing project input hashes and their final comparison, including the
8.5 grammar/specification, fixtures and evidence, 8.6 grammar/specification,
manifest, APIs, tests and release gates. No pre-existing project file changed;
only this assessment and supporting `completeness-*` evidence were added.
Git staging and configuration were untouched. Read-only Git inspection required
the repository owner's execution context because the restricted runner reported
an ownership mismatch.

Reproduction uses existing PHP tooling. Audit-only PHP driver source is embedded
in each relevant ledger's `reproduction.driverSource`; materialize it under
`.audit/` and run from the repository root as recorded. Coverage and source
inventories can therefore be rebuilt without relying on an untracked script
being retained. Source extraction requires the pinned source caches checked by
the focused validators. The preservation baseline is retained in the ledger
itself. These are assessment artifacts, not new public APIs or release gates.

**Phase 5B: READY WITH BOUNDED CONDITIONS** for the specifically scoped evidence,
fixture, mapping and documentation work above. New corrections require their
own verified mismatch, traceability, positive/negative/stage/boundary obligations,
and dependency review. PHP86-014/015 edits remain blocked; PHP86-013 remains open.
No broad remediation, contextual-validator implementation, candidate promotion,
or stable certification is authorized or started by this assessment.
