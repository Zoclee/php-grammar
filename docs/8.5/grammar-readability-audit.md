# PHP 8.5 grammar readability audit

## Scope and invariant

This phase reorganizes the standalone PHP 8.5 grammar without simplifying it.
All **360 production names and all 360 parsed expression ASTs are unchanged**.
Only definition order, informative comments and whitespace change. `source-file`
remains first. No aliases, alternatives, grouping nodes or reference edges were
added or removed. No fixture or scanner/contextual implementation was changed.

The SHA-256 of the name-sorted map of per-production expression serialization
hashes is `3febd80b8d88ecdfc5ec90ec12a4cce5db6d49270ee12cdc87386fbc84cf5f86`
before and after. `Php85ReadabilityTest` freezes this pre-edit fingerprint;
the existing simplification regression also checks each original AST individually.
Identical named ASTs and root imply identical derivations, operand spans,
nullability and reachability independently of the bounded differential tests.
Production line numbers and definition iteration order intentionally change.

The pre-edit baseline is commit
`448473e6937f3598ea95eae29d00a5ee8c721687` with a clean working tree.

## Evidence and method

Read every production and the surrounding specification, generator, conventions,
source mapping and regression tests. Parsed the before/after grammars with the
repository EBNF parser, compared expression hashes and reference edges by name,
and compared sorted validator results. This is an organization change, not a
new interpretation of PHP syntax.

Primary source: [pinned Zend parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y).
Its `type_expr_without_static` comment explicitly identifies conflicts with the
static property modifier. `clone_argument_list` explains the comma distinction
from unary `clone($expr)`. `class_statement_list`, `new_variable`,
`fully_dereferenceable`, `callable_expr` and the precedence declarations explain
why the corresponding EBNF families must stay separate. The repository's
[source reconciliation](phase6-reconciliation.json),
[binding matrices](systematic-structure.json), and
[specification](../../grammar/8.5/php.md#unambiguous-prefix-and-statement-structure)
provide the existing translation evidence for prefix, yield and statement helpers.
These comments do not enlarge the existing bounded conformance claim.

## Organization decisions

| Area reviewed | Finding and recommendation | Disposition |
|---|---|---|
| Production ordering | A broadly conceptual front half was followed by historical appendices mixing unrelated syntax. Replace that tail with named conceptual sections; preserve the root. | SECTION, MOVE |
| Section boundaries | Only lexical comments supplied substantial landmarks. Introduce 21 numbered, searchable comments with stable semantic identifiers. | SECTION |
| Naming consistency | Existing names expose parser categories and attachment points. Uniform suffixes would not justify compatibility breakage. | KEEP |
| Separated families | Arguments, dereferencing, backticks, closures, operators and initializer aliases were separated from their primary families. Bring them together. | MOVE |
| Reference distance | Optimize within related families rather than global forward-reference elimination; recursion and the syntax/helper boundary require cross-section references. | MOVE, KEEP |
| Unexplained unusual rules | Add nearby reasons for without-static, clone arguments, callable conversion, shared members, prefix contexts, dangling else and closed yield. | COMMENT |
| Generated versus manual | Forty rules were distributed through three regions with only a file-header ownership hint. Mark two owned regions, pair expressions with prefixes, keep closed-yield rules in the yield section. | SECTION, MOVE, COMMENT, FORMAT |
| Lexical versus syntactic | State-sensitive string adapters are not arbitrary character matching. Separate trivia, names/literals and primitive adapters; retain explicit links to the scanner contract. | SECTION, COMMENT, KEEP |
| Parser helper families | Assignment, instanceof/power and prefix structures encode operand binding. Group manual helpers without flattening or altering any grouping. | MOVE, COMMENT, KEEP |
| Contextual attachment points | `constant-expression` was far from five identical-body aliases. Place all six together and explain distinct compiler contexts. | MOVE, COMMENT, KEEP |
| List/helper naming | `parameter-list` is nullable, `property-modifier-list` is nonempty, and `array-pair-list` models holes. A uniform suffix cannot express these differences. | KEEP |
| Modifier families | Property list and element rules were separated by hook syntax. Make list/element/visibility adjacent, retain each target-specific family near its declaration. | MOVE, COMMENT, KEEP |
| Statements | Main forms and dangling-else machinery have different reading purposes. Keep a statement section and a complete helper section, including alternative elseif/else and closed-inner-list boundaries. | SECTION, MOVE, COMMENT |
| Expressions | Generated precedence and manual prefix/assignment logic need visible ownership boundaries. Pair generated complete/pending forms; preserve special manual bridges and the closed-yield family. | SECTION, MOVE, COMMENT, KEEP |
| Comment quality | Existing lexical comments were useful but insufficient about scanner boundaries; there was little duplicated prose to remove. Replace them with concise boundary explanations and avoid copying the detailed Markdown discussion. | COMMENT |
| Formatting | Historical tail had repeated blank lines and a very long reserved-token line. Standardize spacing, wrap that catalog and generated prefix alternatives without changing tokens or groups. | FORMAT |
| Navigation | A single fenced grammar block is difficult to scan. Generate a semantic production index from section comments, with all 360 names covered once. | SECTION |

## Layout and family movements

The requested major order is refined with a separate dereferencing section
between expressions and arguments. Closures/arrows are with functions; backtick
rules are with strings. `void-cast-statement` is with statements. Global constant
declarations accompany function/parameter declarations, while class constants
remain with classes. Name-list is with names; namespace-use helpers stay together.
Attributes retain their compact adjacent four-rule family.

Types remain contiguous, including optional-type-without-static and return-type.
Modifier list/element pairs stay beside their targets rather than collecting
parameter modifiers far from parameters. Hooks retain their block/list/body and
modifier family together. Nothing merges the target-specific modifier languages.

Matched/unmatched helpers, their simple-statement base, alternative-if,
closed-inner-statement-list, alt-elseif-list and alt-else-clause form one section.
Yield-key and every closed-yield rule form another. Generated precedence has 34
rules; the six generated closed-yield rules remain a contiguous subregion inside
yield machinery. A single 40-rule generated region would split the latter family,
so two explicit regions are intentional.

## Formatting policy

Keep `name =` on its own line, four-space body indentation, and leading `|` on
continuation alternatives. Use one blank line between productions/comments and
section markers. Short lexical/operator sets and simple lists stay compact;
large distinct alternatives may span lines. Existing grouping parentheses,
alternative order, repetition/optional tails and list multiplicities are frozen.
Wrap the reserved catalog to practical line lengths and show each generated
prefix alternative on a separate line. Do not reflow every protected hand-written
rule solely to meet a line-width target.

## Naming review

No production rename is accepted or applied. The following are the strongest
style candidates considered; reference counts count distinct referring rules,
including self references where present. No external call-site search can prove
that a public rule is unused.

| Current | Candidate considered | Reason / Zend terminology | Affected referring rules | Compatibility decision |
|---|---|---|---|---|
| `property-modifier-list` | `property-modifiers` | Plural consistency; Zend uses property_modifiers, but this EBNF list is nonempty. | `property-declaration` | KEEP; direct rule-selection/API break outweighs wording benefit. |
| `argument-no-expression` | `non-expression-argument` | More natural English; existing name closely mirrors Zend argument_no_expr. | `argument`, `clone-argument-list` | KEEP; direct rule-selection/API break outweighs wording benefit. |
| `name-list` | `class-name-list` | Clarifies element type; Zend class_name_list provides precedent, but current uses already show the type. | `implements-clause`, `interface-extends-clause`, `trait-precedence`, `trait-use-declaration` | KEEP; direct rule-selection/API break outweighs wording benefit. |
| `postfix-expression` | `increment-or-primary-expression` | Not a universal postfix chain; this is an EBNF umbrella rather than an exact Zend production. | `clone-expression` | KEEP; direct rule-selection/API break outweighs wording benefit. |
| `constant-expression` | `initializer-expression` | Could avoid implying compile-time validation; would obscure established contextual attachment terminology. | `class-constant-initializer`, `declare-directive`, `enum-case-initializer`, `global-constant-initializer`, `parameter-default`, `property-default` | KEEP; direct rule-selection/API break outweighs wording benefit. |

The `-expression`, `-statement`, `-declaration`, `-element`, `-clause`, `-body`,
`-block`, `-reference`, `-name` and `-prefix-context` suffixes distinguish useful
concepts. `-list` versus plural forms is historically mixed but not incorrect.
The type and modifier names intentionally distinguish syntax from contextual
legality. Comments address confusing umbrella names without breaking callers.

## Reference-distance measurements

Distances below are absolute differences in definition ordinal (not lines),
averaged/maximized over references within each family, excluding self references.
This measures locality in both directions; it does not pretend recursive forward
references should disappear. Definition lines change as comments improve.

| Family | Before mean / max | After mean / max |
|---|---:|---:|
| Types | 5.0 / 15 | 5.0 / 15 |
| Variables, dereferencing and calls | 113.4 / 259 | 6.4 / 15 |
| Arguments and arrays | 39.9 / 182 | 1.7 / 4 |
| Classes, interfaces, traits and enums | 9.6 / 81 | 6.6 / 36 |
| Namespaces and imports | 2.6 / 9 | 2.6 / 9 |
| Attributes | 1.0 / 1 | 1.0 / 1 |
| Matched/unmatched statement machinery | 21.3 / 148 | 7.1 / 13 |
| Generated precedence/prefix families | 88.4 / 242 | 1.6 / 2 |
| Yield-key / closed-yield machinery | 5.8 / 17 | 5.8 / 17 |

The complete/generated expression hierarchy still crosses the helper boundary;
moving every reference closer would obscure ownership or split recursive families.
The generated production index and section identifiers provide predictable
navigation even for those deliberately retained long references.

## Maintenance and regression checks

`tools/8.5/grammar-sections.php` derives machine-readable JSON directly from the
canonical section comments. It is also used by `sync-documentation.php` to emit
the semantic index. No duplicate 360-name metadata file is maintained, and no
consumer requires either PHP tool to read the standalone grammar.

The expression generator still owns exactly 40 productions and preserves
surrounding bytes and rule order. Its prefix output is formatted across two
alternatives; `--check` and the existing missing-rule/drift/idempotence/LF/CRLF
tests continue enforcing freshness. The Markdown synchronization command now
also supports `--check`; PHPUnit checks the generated index and exact EBNF parity.

Four readability tests protect the pre-edit AST fingerprint, root and count;
the complete ordered section partition and fresh index; protected type,
argument, initializer, matched/unmatched and yield families; and exact generated
ownership across two contiguous regions. They use names and structure, never
line numbers or indentation snapshots. Existing integrity/nullability and
behavioral tests retain their original expectations.

## Complete production inventory

Every canonical production was reviewed. The following complete partition is
also the navigation order. `MOVE` means definitions may move within/between
sections; all body ASTs and public names have disposition `KEEP` throughout.

### 01. Source/root grammar (5)

`source-file`, `inline-html`, `top-statement-list`, `top-statement`, `attributed-top-declaration`.

### 02. Lexical and token-level grammar (5)

`whitespace`, `comment`, `line-comment`, `block-comment`, `doc-comment`.

### 03. Names and identifiers (14)

`identifier`, `identifier-start`, `identifier-part`, `identifier-start-character`, `label`, `semi-reserved-identifier`, `name-identifier`, `namespace-declaration-name`, `qualified-name`, `fully-qualified-name`, `namespace-relative-name`, `name`, `class-name`, `name-list`.

### 04. Literals and strings (23)

`integer-literal`, `decimal-integer-literal`, `binary-integer-literal`, `octal-integer-literal`, `explicit-octal-integer-literal`, `hexadecimal-integer-literal`, `floating-literal`, `exponent-part`, `decimal-digits`, `numeric-separator`, `string-literal`, `single-quoted-string`, `double-quoted-string`, `heredoc-string`, `nowdoc-string`, `encapsulated-string-part`, `encapsulated-variable`, `encapsulated-offset`, `numeric-string`, `literal`, `magic-constant`, `backtick-string`, `backtick-string-part-list`.

### 05. Types (16)

`type`, `optional-type-without-static`, `type-without-static`, `simple-type`, `simple-type-without-static`, `nullable-type`, `nullable-type-without-static`, `union-type`, `union-type-element`, `union-type-without-static`, `union-type-without-static-element`, `intersection-type`, `intersection-type-without-static`, `parenthesized-intersection-type`, `parenthesized-intersection-type-without-static`, `return-type`.

### 06. Expressions (21)

`expression`, `primary-expression`, `postfix-expression`, `assignment-expression`, `assignment-operator`, `conditional-expression`, `instanceof-expression`, `unary-expression`, `cast-expression`, `boolean-not-expression`, `clone-expression`, `throw-expression`, `arrow-expression`, `include-expression`, `print-expression`, `yield-expression`, `yield-from-expression`, `match-expression`, `match-arm-list`, `match-arm`, `match-arm-condition-list`.

### 07. Variables, dereferencing and calls (22)

`variable`, `variable-variable`, `variable-like`, `variable-expression`, `callable-variable`, `new-variable`, `static-member`, `fully-dereferenceable-expression`, `array-object-dereferenceable`, `callable-expression`, `new-dereferenceable`, `dereferenceable-scalar`, `property-name`, `member-name`, `object-operator`, `nullsafe-object-operator`, `constant`, `class-constant`, `class-name-reference`, `function-call`, `isset-variable-list`, `isset-variable`.

### 08. Arguments and arrays (13)

`argument-list`, `ordinary-argument-list`, `first-class-callable-arguments`, `constructor-argument-list`, `clone-argument-list`, `argument`, `argument-no-expression`, `expression-list`, `array-creation-expression`, `array-pair-list`, `array-pair`, `list-expression`, `long-list-expression`.

### 09. Statements (48)

`statement`, `compound-statement`, `inner-statement-list`, `inner-statement`, `inner-declaration`, `expression-statement`, `echo-statement`, `statement-terminator`, `global-statement`, `global-variable-list`, `global-variable`, `static-statement`, `static-variable-list`, `static-variable`, `unset-statement`, `unset-variable-list`, `unset-variable`, `return-statement`, `break-statement`, `continue-statement`, `goto-statement`, `label-statement`, `empty-statement`, `if-statement`, `while-statement`, `do-statement`, `for-statement`, `for-expression-list`, `nonempty-for-expression-list`, `for-expression`, `for-condition-expression-list`, `foreach-statement`, `foreach-target`, `foreach-key`, `foreach-value`, `foreach-variable`, `switch-statement`, `switch-case-list`, `switch-case`, `declare-statement`, `declare-directive-list`, `declare-directive`, `try-statement`, `catch-list`, `catch-clause`, `catch-type-list`, `finally-clause`, `void-cast-statement`.

### 10. Functions, closures and parameters (13)

`function-declaration`, `closure-expression`, `arrow-function`, `arrow-function-header`, `lexical-variable-list`, `lexical-variable`, `parameter-list`, `parameter`, `parameter-modifiers`, `parameter-modifier`, `constant-declaration`, `constant-list`, `constant-element`.

### 11. Classes, interfaces, traits and enums (44)

`class-declaration`, `anonymous-class`, `class-modifiers`, `class-modifier`, `extends-clause`, `implements-clause`, `class-member-list`, `class-member`, `property-declaration`, `property-modifier-list`, `property-modifier`, `property-visibility-modifier`, `set-visibility-modifier`, `property-list`, `property-element`, `hooked-property`, `property-hook-block`, `property-hook-list`, `property-hook`, `property-hook-body`, `property-hook-modifiers`, `property-hook-modifier`, `method-declaration`, `method-modifiers`, `method-modifier`, `method-body`, `class-constant-declaration`, `class-constant-modifiers`, `class-constant-modifier`, `class-constant-list`, `class-constant-element`, `interface-declaration`, `interface-extends-clause`, `trait-declaration`, `trait-use-declaration`, `trait-adaptation-block`, `trait-adaptation`, `trait-precedence`, `trait-alias`, `trait-method-reference`, `trait-alias-modifier`, `enum-declaration`, `enum-backing-type`, `enum-case`.

### 12. Namespaces and imports (12)

`namespace-definition`, `namespace-use-declaration`, `use-type`, `use-declaration-list`, `use-declaration`, `legacy-namespace-name`, `group-use-declaration`, `mixed-group-use-declaration`, `unprefixed-use-declaration-list`, `unprefixed-use-declaration`, `inline-use-declaration-list`, `inline-use-declaration`.

### 13. Attributes (4)

`attribute-groups`, `attribute-group`, `attribute-list`, `attribute`.

### 14. Source termination / __halt_compiler (2)

`halt-compiler-statement`, `halt-compiler-data`.

### 15. Lexical primitive adapters (27)

`binary-digit`, `octal-digit`, `decimal-digit`, `decimal-digit-nonzero`, `hexadecimal-digit`, `exponent-marker`, `ascii-letter`, `inline-html-text`, `line-comment-text`, `block-comment-text`, `doc-comment-text`, `single-quoted-string-content`, `string-text`, `heredoc-label`, `heredoc-body`, `nowdoc-body`, `source-character`, `inline-html-character`, `line-comment-character`, `block-comment-character`, `single-quoted-string-character`, `string-character`, `nowdoc-character`, `escape-sequence`, `whitespace-character`, `non-ascii-byte`, `newline`.

### 16. Reserved identifiers / token categories (1)

`reserved-non-modifiers`.

### 17. Parser-derived expression helper machinery (15)

`arrow-prefix-context`, `include-prefix-context`, `print-prefix-context`, `yield-prefix-context`, `yield-from-prefix-context`, `assignment-prefix`, `assignment-prefix-context`, `conditional-prefix-context`, `boolean-not-prefix-context`, `instanceof-prefix-context`, `unary-prefix-context`, `clone-prefix-context`, `include-operator`, `unary-operator`, `cast-operator`.

### 18. Constant-expression attachment aliases (6)

`constant-expression`, `parameter-default`, `property-default`, `class-constant-initializer`, `global-constant-initializer`, `enum-case-initializer`.

### 19. Matched/unmatched statement machinery (17)

`matched-statement`, `simple-statement`, `unmatched-statement`, `matched-if-statement`, `unmatched-if-statement`, `alternative-if-statement`, `closed-inner-statement-list`, `matched-while-statement`, `unmatched-while-statement`, `matched-for-statement`, `unmatched-for-statement`, `matched-foreach-statement`, `unmatched-foreach-statement`, `matched-declare-statement`, `unmatched-declare-statement`, `alt-elseif-list`, `alt-else-clause`.

### 20. Generated precedence/prefix families (34)

`logical-or-expression`, `logical-or-prefix-context`, `logical-xor-expression`, `logical-xor-prefix-context`, `logical-and-expression`, `logical-and-prefix-context`, `coalesce-expression`, `coalesce-prefix-context`, `boolean-or-expression`, `boolean-or-prefix-context`, `boolean-and-expression`, `boolean-and-prefix-context`, `bitwise-or-expression`, `bitwise-or-prefix-context`, `bitwise-xor-expression`, `bitwise-xor-prefix-context`, `bitwise-and-expression`, `bitwise-and-prefix-context`, `equality-expression`, `equality-prefix-context`, `relational-expression`, `relational-prefix-context`, `pipe-expression`, `pipe-prefix-context`, `concatenation-expression`, `concatenation-prefix-context`, `shift-expression`, `shift-prefix-context`, `additive-expression`, `additive-prefix-context`, `multiplicative-expression`, `multiplicative-prefix-context`, `power-expression`, `power-prefix-context`.

### 21. Yield-key / closed-yield machinery (18)

`yield-key-expression`, `yield-key-prefix-context`, `closed-yield-throw-expression`, `closed-yield-arrow-expression`, `closed-yield-arrow-prefix-context`, `closed-yield-include-expression`, `closed-yield-include-prefix-context`, `closed-yield-logical-or-expression`, `closed-yield-logical-or-prefix-context`, `closed-yield-logical-xor-expression`, `closed-yield-logical-xor-prefix-context`, `closed-yield-logical-and-expression`, `closed-yield-logical-and-prefix-context`, `closed-yield-print-expression`, `closed-yield-print-prefix-context`, `closed-yield-yield-expression`, `closed-yield-yield-prefix-context`, `yield-key`.

## Validation results

Completed the 24 commands from `tools/grammar-release.py` using PHP 8.5.10,
ext-ast 1.1.3/schema 120, `.audit/phase5.ini`, and `COMPOSER_PROCESS_TIMEOUT=0`.
Derived evidence was refreshed in dependency order; PHPUnit ran after freshness
inputs were updated. Logs are retained locally in `.audit/readability-validation/`.
The initial lexical-coverage check correctly rejected the stale ledger after the
grammar bytes changed; its rerun after regeneration passed. The optional pre-edit
full-suite rerun exceeded Composer's default 300-second timeout and was stopped;
the final suite completed with that process timeout disabled. Before/after
comparisons use the captured clean-commit grammar and evidence files.

- PHPUnit: **5,185 tests / 49,165 assertions**, all passing (four new tests / 109 assertions).
- Canonical productions: **360 before / 360 after**, no renames, no body AST changes.
- All **26 nullable productions** unchanged; all reference edges identical; no undefined or unreachable productions under the existing source/trivia roots.
- **1,371 PHP fixtures** byte-identical: 653 valid, 362 invalid, 356 contextual-invalid. All 1,376 fixture-directory files, including metadata, are unchanged.
- Ordinary differential: zero mismatches or known discrepancies; structural/contextual classifications unchanged.
- Explicit AST: 63 positive / 10 negative cases, zero failures.
- Operator/statement matrix: **15,342 cases, zero unexpected mismatches**; yield-key and matched/unmatched binding preserved.
- Declaration folding: 1,840 cases, zero failures.
- Phase 6: 860 cases (70 direct-folding, 584 modifier, 34 ambiguity, 156 recursive, 16 malformed), zero failures.
- Scanner/parser product: 482 cases, zero failures; both short-tag differential profiles pass.
- Grammar coverage unchanged: 297/360 productions and 606/790 alternatives exercised; 297 attempted productions and 607 attempted alternatives. Remaining classifications unchanged; no meaningful gap.
- Lexical evidence unchanged: 190 scanner rules, 25 families, 11 states; 2,700 direct, 101 primitive and 144 syntax cases.
- Interpolation and diagnostic binding evidence unchanged, as verified against the pre-edit JSON reports.
- All 40 generated expressions fresh; generator drift detection retained; Markdown EBNF parity and semantic index freshness pass.

### Release gates

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

### Before/after evidence comparison

Compared every pre-edit `docs/8.5/*.json` report. Only input digests and the order
of production-derived inventories may differ. The inventory normalization is
limited to `remaining`, `baseline_backlog`, `grammar_bypasses`, `contextual_only`,
`scanner_context_only` and `ebnf_productions`; case, operand and derivation arrays
are never reordered for comparison. Digest changes correspond to the grammar,
its synchronized documentation, changed maintenance/test tools and dependent
report bytes. No behavioral report fields differ.

| Report | Comparison |
|---|---|
| `compiler-boundaries.json` | Byte-identical |
| `diagnostic-dispositions.json` | Byte-identical |
| `diagnostic-witnesses.json` | Semantically identical; only digests/inventory order changed |
| `final-evidence.json` | Semantically identical; only digests/inventory order changed |
| `interpolation-binding.json` | Semantically identical; only digests/inventory order changed |
| `negative-boundaries.json` | Byte-identical |
| `phase3-coverage.json` | Semantically identical; only digests/inventory order changed |
| `phase5-lexical-evidence.json` | Semantically identical; only digests/inventory order changed |
| `phase6-boundary-folding.json` | Byte-identical |
| `phase6-evidence.json` | Semantically identical; only digests/inventory order changed |
| `phase6-matrices.json` | Semantically identical; only digests/inventory order changed |
| `phase6-reconciliation.json` | Semantically identical; only digests/inventory order changed |
| `phase6-upstream-tests.json` | Byte-identical |
| `remediation-folding-evidence.json` | Byte-identical |
| `scanner-product.json` | Semantically identical; only digests/inventory order changed |
| `source-correspondence.json` | Byte-identical |
| `source-inventory.json` | Semantically identical; only digests/inventory order changed |
| `systematic-structure.json` | Semantically identical; only digests/inventory order changed |

The exact AST identity proves that this organization change preserves grammar
behavior; the release gates independently confirm the bounded scanner, parser,
compiler-context and binding evidence. Existing documented conformance limits
remain in force; this phase does not claim a new exhaustive PHP equivalence proof.
