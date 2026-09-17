# PHP 8.5 specification polish audit

## Scope and invariants

This is a documentation/specification quality change to
[`grammar/8.5/php.md`](../../grammar/8.5/php.md). The baseline is commit
`522478da381fdeffae3f8bd776621cb298ed5420`, with a clean working tree before
the documentation work. The canonical EBNF, source pin, fixtures, scanner,
matcher, contextual implementation and expected language behavior are frozen.

The canonical EBNF SHA-256 is
`7bede7421acf06152892210cc2a53c17628e88402057edf0a50a76cc11fb0a0c`.
Its 360 named production bodies, order, comments and bytes must remain unchanged.
The existing parsed-AST invariant is
`3febd80b8d88ecdfc5ec90ec12a4cce5db6d49270ee12cdc87386fbc84cf5f86`.
No grammar correction, fixture change or expectation change is part of this work.

The normative source remains `php-src` `PHP-8.5` at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. PHP 8.5.10 remains the executable
comparison, not an executable built from that exact commit. The byte profile,
`zend.multibyte=0`, short-tag profiles and all bounded-conformance caveats remain.

## Review method and block classification

Reviewed the complete baseline specification: the introduction, all source and
lexical rules, all tables, expression/helper explanations, every contextual row,
the nine-step constant-expression algorithm, deprecated-form outcomes, limitations,
generated index and canonical grammar block. The generated portions are retained
verbatim and verified mechanically rather than rewritten. The following ledger
classifies each major original block. Classification identifies its primary
role; disposition identifies the editorial action.

| Original block | Classification | Disposition | Destination and reason |
|---|---|---|---|
| Opening three-part contract | NORMATIVE | REWRITE | Status/scope and explicit three-layer conformance model; distinguish source validity from structural recognition. |
| Phase 4 negative-boundary introduction | HISTORICAL | MOVE | Final conformance history; preserve 250 structural and 22 contextual negatives, valid repairs, ledger/report links and the bounded claim. |
| Phase 5 scanner/remediation introduction | HISTORICAL | MOVE | Final history; lexical rules remain in the source model and the historical correction remains traceable. |
| Phase 6 introduction | HISTORICAL | MOVE | History retains the record. Current parser-action timing, surviving `never` checks, builtin overlap, modifier API scope and recovery/runtime exclusion move to their normative or conformance sections. |
| Source pin, source URLs, executable caveat | CONFORMANCE | KEEP | Status/scope before language details; retain exact hash and PHP 8.5.10 distinction. |
| Byte-stream and multibyte profile | NORMATIVE | KEEP | Source and lexical model. |
| Opening/closing tag table | NORMATIVE | KEEP | Focused opening/closing-tag subsection; existing two-column lookup remains useful. |
| PHP/HTML stream, terminators, EOF, halt payload | NORMATIVE | MOVE | Dedicated source-transition subsection; preserve the single token stream and outermost halt constraint. |
| Whitespace, comments, attribute exception | NORMATIVE | REWRITE | Separate whitespace/comments from state-sensitive lookahead; scope the attribute assertion to normal scripting immediately. |
| Composite yield-from, enum and ampersand categories | NORMATIVE | MOVE | Comment-sensitive lookahead stays near source rules; composite token/category explanation moves to names and tokens. |
| External primitive table | NORMATIVE | KEEP | Source model; every primitive remains one byte with its state/delimiter restriction. |
| Escapes and binary string prefixes | NORMATIVE | MOVE | Literals and strings, string-escape subsection. |
| Simple/braced interpolation and deprecated forms | NORMATIVE | MOVE | Literals and strings; no generic regex substitutes for state-sensitive scanning. |
| Heredoc/nowdoc labels, indentation and exact EOF | NORMATIVE | REWRITE | Dedicated subsection with shorter paragraphs; all lexical conditions retained. |
| Standalone-fragment trailing-newline test boundary | CONFORMANCE | MOVE | Known abstractions; distinguish the harness boundary from complete-file acceptance. |
| Scanner-state table | NORMATIVE | REWRITE | Top-level scanner section with current state, event, token/effect, next state/stack and special-rule columns. |
| Nested state/source transitions and offset tokens | NORMATIVE | KEEP | Scanner section; offsets get their own subsection, including `08`, numeric overflow and minus restrictions. |
| Identifier categories and atomic qualified names | NORMATIVE | MOVE | Names and tokens; lexical `variable` versus syntactic `variable-expression` explicitly defined in terminology. |
| Numeric tokens and overflow abstraction | NORMATIVE | MOVE | Numeric-literal subsection; abstraction also discoverable under conformance limits. |
| Precedence table and ternary binding restrictions | NORMATIVE | KEEP | Expressions and precedence; retain low-to-high order and parser/compiler distinctions. |
| Dereferencing categories | NORMATIVE | MOVE | Dedicated variables/calls/dereferencing section; retain the absence of universal postfix syntax. |
| Ordinary arguments, callable conversion and clone | NORMATIVE | MOVE | Arguments and arrays; keep constructor conversion structurally represented. |
| Prefix-context purpose and assignment/instanceof examples | EXPLANATORY | KEEP | Expressions; preserve every binding example and the established external anchor. |
| Closed-yield separator ownership | EXPLANATORY | KEEP | Expressions; retain nearest eligible yield, parentheses and tree-uniqueness explanation. |
| Matched/unmatched and closed-inner-list machinery | EXPLANATORY | MOVE | Statements and control flow, including the alternative-colon boundary, empty semicolon and expression-statement rules. |
| Mandatory contextual-layer introduction | NORMATIVE | REWRITE | Explain compiler contexts and link to pre-fold parser-action boundaries. |
| 22 contextual lookup rows | NORMATIVE | REWRITE | Fifteen domain subsections below; independent requirements become list items instead of dense cells. |
| Duplicate set-reference-parameter prohibition | REDUNDANT | REWRITE | Retain one explicit non-reference requirement in the supplied set-parameter rule; preserve the separate accepted reference-return marker. |
| Compiler source-area list | EVIDENCE | MOVE | Place function names beside the constraints they support. |
| Shared declaration bodies, enum backing and class-name lists | NORMATIVE | KEEP | Parser/compiler declaration boundary, with smaller subsections. |
| Hook structure, promotion, set-reference behavior | NORMATIVE | REWRITE | Separate structural possibilities from compiled checks; preserve the observed deprecation and prohibition of reference parameters. |
| Earlier unconditional “only get” correction | HISTORICAL | MOVE | Conformance history; current set-reference rule stays beside hook validation. |
| Attribute argument validation | NORMATIVE | KEEP | Boundary subsection; preserve marker/order validation before expression folding and whole-closure discarding distinction. |
| Parser-action modifier checks | NORMATIVE | KEEP | Explicit parser-action validation heading, distinct from later compiler validation. |
| 114-family/six-discarding-operator matrix paragraph | EVIDENCE | MOVE | Final evidence section, identified as historical bounded coverage; retain accepted set-reference exception and parser-action controls. |
| Constant-expression counterexamples | EXPLANATORY | KEEP | Constant-expression section before the algorithm; retain live/discarded contrasts. |
| Nine-step validation algorithm | NORMATIVE | REWRITE | Dedicated numbered normative algorithm; preserve order, whitelist, traversal exceptions and deferred checks. |
| Former constant-subset/isset assumption correction | HISTORICAL | MOVE | Final history; state the current `isset_variable: expr` rule directly in normative prose. |
| Deprecated and removed cast/interpolation/backtick rules | NORMATIVE | REWRITE | Status table plus phase/traversal subsections; no deprecated form is reclassified as invalid. |
| Ordinary-versus-constant `(unset)` outcome table | EVIDENCE | KEEP | Beside its normative traversal explanation; each result depends on expression context, so moving it to remote history would harm understanding. |
| For-loop `(void)` and final-condition rules | NORMATIVE | MOVE | Statements; this is accepted ordinary syntax, not a deprecated cast. |
| Historical discrepancy-remediation bullets | HISTORICAL | MOVE | Final history preserves old matrix counts, former blockers, corrected witnesses and methodology links. |
| Still-applicable conformance-limit clauses | CONFORMANCE | REWRITE | Consolidated known-abstractions section; no upgrade from bounded testing to exhaustive proof. |
| Generator ownership and canonical parity prose | EXPLANATORY | REWRITE | Syntactic productions before index/EBNF; move simplification/readability narrative to evidence links. |
| Semantic production index | EXPLANATORY | KEEP | Generated, complete and immediately before EBNF. |
| Canonical EBNF block | NORMATIVE | KEEP | Byte-preserved generated content; no production edits. |

## Findings and architecture decisions

1. **Audit narrative before rules:** three phase paragraphs preceded the source
   profile. Move them after the complete grammar, under conformance history.
2. **Repeated caveats:** retain the early bounded claim, collect detailed current
   limits in one section, and identify historical caveats as historical evidence.
3. **Implementation history before language behavior:** state `isset`'s expression
   input and the set-reference exception directly; explain former mistakes only
   in history.
4. **Unclear normative status:** define mandatory source validity across three
   layers, normative terms, and the explanatory status of examples.
5. **Blurred responsibilities:** distinguish structural acceptance, parser actions,
   compiler validation and folding/discard behavior explicitly. A function in
   `zend_compile.c` may execute in a parser action.
6. **Long compound rules:** split lexical topics, heredoc indentation paragraphs,
   parameters, argument ordering, hooks, write targets and control flow.
7. **Dense tables:** the contextual table was a poor fit for long independent
   requirements; use domain headings and lists. The scanner table lacked an
   explicit next-state/effect distinction; add those columns.
8. **Distant citations:** put compiler function references alongside their domains
   and replace brittle line-only references with parser/function names.
9. **Examples:** existing source-transition, exact-EOF, interpolation, binding,
   yield, callable-name folding and `isset` examples already expose the subtle
   distinctions. Retain them instead of adding redundant source samples.
10. **Acceptance versus implementation:** isolate the structural matcher's limits,
    fragment-test boundaries, modifier API scope and numeric-token abstraction
    from rules about PHP source itself.

The original document had eight level-two headings, with scanner, names,
declaration boundaries and constant validation nested under broad sections.
The new document has 24 level-two headings:

| Order | New section | Main content origin |
|---|---|---|
| 1 | Status and scope | Opening contract, source pin and executable comparison. |
| 2 | Conformance model | Explicit layers, normative terms, reading guide and glossary. |
| 3 | Source and lexical model | Tags, comments, whitespace, source transitions and primitives. |
| 4 | Scanner state transitions | State table, recursion, interpolation offsets. |
| 5 | Names and tokens | Identifiers, names and composite token categories. |
| 6 | Literals and strings | Numeric spellings, escapes, interpolation, heredoc/nowdoc. |
| 7 | Types | Type-family navigation, without-static purpose and overlap caveat. |
| 8 | Expressions and precedence | Existing precedence, prefix and closed-yield rules/examples. |
| 9 | Variables, calls and dereferencing | Existing parser-category distinctions. |
| 10 | Arguments and arrays | Existing arguments/clone rules and contextual lookup. |
| 11 | Statements and control flow | Matched/unmatched, closed-inner-list and for-loop rules. |
| 12 | Functions, closures and parameters | Structural/capture boundary and surviving-declaration checks. |
| 13 | Classes, interfaces, traits and enums | Shared structural bodies and contextual domain navigation. |
| 14 | Namespaces and imports | Atomic names/group separator and placement-validation boundary. |
| 15 | Attributes | Scanner recognition, argument structure and declaration constraints. |
| 16 | Constant expressions | Counterexamples and nine-step normative algorithm. |
| 17 | Contextual syntax constraints | Fifteen searchable domains replacing 22 dense rows. |
| 18 | Parser/compiler boundary rules | Four phases plus declaration/hook/attribute detail. |
| 19 | Deprecated but accepted syntax | Statuses and context-dependent removed-cast outcomes. |
| 20 | Known abstractions and conformance limits | Current scanner/binding/contextual/executable limits. |
| 21 | Syntactic productions | Ownership and generation explanation. |
| 22 | Production index | Unchanged generated semantic index. |
| 23 | Canonical EBNF | Unchanged generated full grammar. |
| 24 | Conformance evidence | Report map and explicitly historical audit narratives. |

The two extra sections relative to the suggested 22-part outline retain the
generated index's established heading and provide a short ownership explanation.
Constant expressions precede the contextual index so its folding links refer to
an already explained algorithm. Domain summaries complement the EBNF; they do
not transcribe every production. The old externally referenced
`unambiguous-prefix-and-statement-structure` anchor remains valid.

## Contextual-domain preservation

| New domain | Original rows retained |
|---|---|
| Constant-expression contexts | Constant expressions; allow-dynamic parameter/global/attribute/constructor contexts; non-dynamic property/class/enum contexts; constant calls; runtime static locals. |
| Type constraints | Stand-alone, nullable, union/intersection, return-only, scope and property/promoted/class-constant restrictions. |
| Parameter and closure constraints | Unique/variadic/name/capture rules, structural nonempty use, constructor promotion and parameter defaults. |
| Argument constraints | Positional/named/unpacked ordering, duplicate names, attribute exclusions and clone/exit checks. |
| Declarations and modifiers | Conflicts, reserved/redeclared/nested/anonymous declarations, method context, interface traits and attributed global versus class constants. |
| Properties | Readonly/type/static/default, abstract hooks, visibility/finality and interface properties. |
| Hooks | Kind/count, modifiers, parameters, reference-return distinction, body/abstract/final rules. |
| Class constants | Shared type, compatibility, modifiers and final/private rules. |
| Enums | Backing type, value presence, uniqueness/type deferral, forbidden members and trait checks. |
| Callable conversion | Surviving constructor and nullsafe restrictions, ordinary conversions and separate clone structure. |
| isset and unset | Expression-shaped isset list, compiler variable categories, reads and empty offsets; unset write-target rules follow in the next domain. |
| Writable variables and destructuring | Read/write targets, calls, nullsafe writes, foreach keys, holes and mixed destructuring styles. |
| Trait aliases | Single modifier/name, compiler static/abstract rejection and earlier readonly rejection. |
| Control-flow constraints | Enclosing construct/level, goto, yield, returns, defaults, try handlers and pipe/arrow restriction. |
| Namespace and declare constraints | Literal values/placement, strict_types, styles and imports. |

## Normative wording and terminology review

Requirements now use explicit **must**, **must not**, **may** and factual **is**
where this clarifies the obligation. For example, parameter uniqueness and
argument-order rules are independent requirements; the state-specific `08`
offset rule explicitly constrains the scanner. Possibility language remains
where genuinely conditional, such as deferred checks and folding outcomes.
No uncertainty is converted to certainty.

| Term(s) | Decision |
|---|---|
| source, byte stream | Source means bytes under the declared encoding/preprocessing profile. |
| token, terminal | Distinguish scanner categories from EBNF symbols; identical spelling does not erase ampersand or keyword categories. |
| primitive | External one-byte lexical rule, never a wildcard token. |
| identifier, name | Preserve position-specific T_STRING/semi-reserved categories and atomic qualified names. |
| expression | Structural category; a constant or write context adds validation. |
| variable, variable-expression | Lexical T_VARIABLE versus Zend's syntactic variable; qualify the old call/unset sentence to remove ambiguity. |
| callable, dereferenceable | Syntactic categories, not assertions of runtime success or writability. |
| structural, contextual | EBNF derivation versus mandatory external constraints, including early parser actions. |
| parser action, compiler validation | Timing determines whether discarding can avoid rejection; source-file location does not. |
| folding | Specified Zend traversal, not universal recursive evaluation. |
| visited AST | Reached during folding; errors are not retroactively suppressed by a later discard. |
| discarded branch | Must still lex and parse and pass early actions. |
| surviving AST | Subject to whitelist, allow_dynamic and actual subsequent compiler contexts. |

The nine-step sequence is retained: lexical validity/actions; folding;
short-circuit/ternary/coalescing traversal; surviving whitelist; allow_dynamic;
new restrictions; callable conversion; closures; declaration/type/argument checks.
The literal-name CALL/STATIC_CALL traversal exception, constructor folding,
ordinary-versus-constant unset behavior and deferred-check caveats remain intact.

## Tables, examples and source references

- The opening-tag and lexical-primitive lookup tables retain their rules.
- Scanner transitions are split by event into 18 rows with five explicit columns.
  Nested state ownership, short-tag re-entry, property hash comments, interpolation
  lookahead, offset restrictions and heredoc label/indentation checks remain.
- The precedence table retains every level and associativity description.
  Literal pipe characters in Markdown table code spans are escaped so GFM does
  not split their cells. This also repairs the deprecated-outcome table rendering.
- The contextual table becomes domain lists; the duplicate set-reference-parameter
  sentence is folded into the one complete parameter requirement.
- A deprecated/removed status table makes accepted aliases, `${...}`, backticks,
  scanner-rejected `(real)` and structurally represented `(unset)` easy to compare.
- A glossary and final evidence lookup table are added.
- No PHP source example is added or removed. Existing examples move with their
  rules, including close-tag/EOF, interpolation, precedence, nested yield,
  constant branch discarding, folded callable names and isset target cases.

All three immutable source URLs remain near the beginning. Local source names
are placed by their rules: scanner states and composite yield recognition;
parser `type_expr_without_static`, `argument_list`, `clone_argument_list`,
`class_statement_list`, `class_statement`, `enum_backing_type`,
`property_hook_list`, `property_hook`, `property_hook_body` and `attribute_decl`;
compiler type, parameter, attribute, property, hook, constant, enum, foreach,
conditional, pipe and declare functions. The destructuring citation now names
`zend_verify_list_assign_target`, whose guard contains the mixed-style diagnostic.
Constant validation retains `zend_const_expr_to_zval`, `zend_eval_const_expr`,
the `zend_try_ct_eval_*` family and `zend_compile_const_expr_fcc` instead of
requiring readers to navigate by old line numbers. These are citation/navigation
improvements, not newly inferred language rules.

## Historical material and conformance claims

The Phase 4/5/6 opening narrative is available under the final conformance-history
heading. Removed discrepancy prose is preserved as historical statements or
links: the two discarded-closure witnesses, 114-family matrix, six discarding
operators, 11,294-case historical binding matrix, assignment/instanceof fixes,
190 scanner-rule audit and 482-case product matrix. The older counts are explicitly
stage-specific, not presented as current denominators. Phase 7 completeness,
diagnostic disposition and final evidence records are linked alongside the
simplification/readability reports.

Current caveats remain easy to find: no exact Zend token-stream assertion, no
complete scanner/parser source-equivalence proof, no arbitrary-depth binding
proof, no complete repository-owned contextual validator, no promotion of
function-family witnesses into predicate proof, and no exact-pin executable or
runtime-success claim. Grammar authority is explicitly repository structural
authority, not official PHP standardization. No conformance claim is strengthened.

## Generation and regression protection

`tools/8.5/sync-documentation.php` previously replaced everything from the EBNF
start marker to EOF. It now locates the end marker, regenerates only that block,
and preserves the following hand-written appendix. A missing end marker fails
without writing the file. Index generation and canonical EBNF formatting are
unchanged.

Three documentation tests cover normative section/layer/boundary navigation,
the source pin and evidence placement; local-anchor resolution; and synchronizer
drift detection, regeneration, appendix preservation, repeat-run identity and
missing-end-marker rejection in an isolated temporary repository. Tests use
structural anchors, not exact paragraph wording. Existing tests continue checking
the generated index, EBNF parity, 21-section partition, 40 generated rules and
all production fingerprints. No PHP fixture or expected parser outcome changes.

## Validation results

Completed all 24 commands defined by `tools/grammar-release.py` using PHP
8.5.10, ext-ast 1.1.3/schema 120, `.audit/phase5.ini`, and
`COMPOSER_PROCESS_TIMEOUT=0`. Derived provenance reports were regenerated in
dependency order before their freshness checks; PHPUnit ran after those updates.
No grammar, fixture or test expectation was changed to satisfy a gate. Durable
logs are in `.audit/polish-validation/`. The working-tree audit used the clean
commit baseline captured before editing.

| Invariant / check | Result |
|---|---|
| Canonical EBNF | Byte-identical; SHA-256 `7bede7421acf06152892210cc2a53c17628e88402057edf0a50a76cc11fb0a0c`. |
| Production names and parsed ASTs | All 360 unchanged; existing frozen fingerprint and per-production regressions pass. |
| Semantic metadata | All 21 sections valid, partitioning the same 360 names in the same order. |
| Generated expressions | All 40 fresh in their two owned regions. |
| Generated index and Markdown EBNF | Both blocks unchanged; exact parity and synchronizer freshness pass. |
| PHP fixtures | All 1,371 byte-identical: 653 valid, 362 invalid, 356 contextual-invalid. |
| Fixture-directory metadata | All 1,376 files, including fixtures and metadata, byte-identical. |
| Ordinary differential | Zero mismatches and zero known discrepancies; both short-tag profiles pass. |
| Structural/contextual classification | Unchanged; contextual-invalid fixtures still match structurally and fail compilation. |
| Explicit AST | 63 positive / 10 negative cases, zero failures. |
| Operator/statement matrix | 15,342 cases, zero unexpected mismatches; acceptance, derivations, operand spans and binding unchanged. |
| Declaration folding | 1,840 cases, zero failures. |
| Phase 6 matrices | 860 cases: 70 direct-folding, 584 modifier, 34 ambiguity, 156 recursive, 16 malformed; zero failures. |
| Scanner/parser product | 482 cases, zero failures; both short-tag lexer differential profiles pass. |
| Grammar coverage | 297/360 productions and 606/790 alternatives exercised; 297 attempted productions and 607 attempted alternatives, unchanged classifications. |
| Lexical evidence | 190 rules, 25 families, 11 states; 2,700 direct, 101 primitive and 144 syntax cases, unchanged. |
| PHPUnit | **5,188 tests / 49,228 assertions**, all passing; three new documentation tests. |
| Documentation review | Local file and heading links resolve; original tag, primitive, precedence and cast-outcome table values preserved; table columns consistent. |
| Release gates | **24/24 PASS**. |

### Individual release gates

| Gate | Result |
|---|---|
| `composer-validate` | PASS |
| `expression-generation` | PASS |
| `grammar-coverage` | PASS |
| `lexer-coverage` | PASS |
| `ordinary-differential` | PASS |
| `explicit-ast` | PASS |
| `systematic-structure` | PASS |
| `declaration-folding` | PASS |
| `phase6-matrices` | PASS |
| `phase6-matrix-freshness` | PASS |
| `scanner-product` | PASS |
| `lexer-short-enabled` | PASS |
| `lexer-short-disabled` | PASS |
| `positive-report-freshness` | PASS |
| `negative-report-freshness` | PASS |
| `compiler-source-hashes` | PASS |
| `parser-reconciliation` | PASS |
| `phase6-evidence-freshness` | PASS |
| `interpolation-binding` | PASS |
| `diagnostic-predicates` | PASS |
| `source-correspondence` | PASS |
| `final-certification` | PASS |
| `phpunit` | PASS |
| `diff-whitespace` | PASS |

### Behavioral-report comparison

Compared all 18 pre-edit `docs/8.5/*.json` reports with their final regenerated
versions. Non-digest fields are identical, including array order; no production
inventory, case, operand, derivation or classification list was reordered for
comparison. Only input-digest maps may differ. Changed digest entries are checked
against the actual documentation/tooling/test inputs and dependent report bytes;
the release freshness gates verify those values. No grammar digest changes.

Seventeen reports are byte-identical. Only `final-evidence.json` changes, for
exactly three inputs: `grammar/8.5/php.md`, the new
`tests/Php/Conformance/Php85SpecificationStructureTest.php`, and
`tools/8.5/sync-documentation.php`. A stricter comparison removes only top-level
provenance maps, requires every remaining JSON value and array position to be
identical, and verifies each changed input digest against the current file bytes.

| Report | Before/after result |
|---|---|
| `compiler-boundaries.json` | Byte-identical |
| `diagnostic-dispositions.json` | Byte-identical |
| `diagnostic-witnesses.json` | Byte-identical |
| `final-evidence.json` | Behavior-identical; input digests only |
| `interpolation-binding.json` | Byte-identical |
| `negative-boundaries.json` | Byte-identical |
| `phase3-coverage.json` | Byte-identical |
| `phase5-lexical-evidence.json` | Byte-identical |
| `phase6-boundary-folding.json` | Byte-identical |
| `phase6-evidence.json` | Byte-identical |
| `phase6-matrices.json` | Byte-identical |
| `phase6-reconciliation.json` | Byte-identical |
| `phase6-upstream-tests.json` | Byte-identical |
| `remediation-folding-evidence.json` | Byte-identical |
| `scanner-product.json` | Byte-identical |
| `source-correspondence.json` | Byte-identical |
| `source-inventory.json` | Byte-identical |
| `systematic-structure.json` | Byte-identical |

The report comparison and fixture manifest are retained locally as
`.audit/polish-report-comparison.json` and `.audit/polish-baseline/`.
Byte identity of the canonical grammar establishes that this documentation
change alters no grammar semantics. The independently passing release gates
retain the existing bounded scanner/parser/compiler evidence; they do not
establish new formal or exhaustive PHP conformance.
