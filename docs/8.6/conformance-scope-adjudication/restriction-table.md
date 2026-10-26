| ID | Restriction | Native class | Validation stage | Remaining obligation |
|---|---|---|---|---|
| C6-C001 | duplicate modifiers | M | parser-action | Extract target lists before folding; duplicate rejection in the rule API is not source integration. |
| C6-C002 | conflicting visibility | M | parser-action | Conflicting visibility needs source target extraction and isolated RC4 witnesses. |
| C6-C003 | abstract/final | M | parser-action and compiler | Early conflicting flags differ from later declaration/body checks. |
| C6-C004 | anonymous abstract/final | M | parser-action | Anonymous-class target is separately identified; no late-folding exemption. |
| C6-C005 | target-specific readonly/static/final | M | parser-action and compiler | Allowed modifier tokens do not establish legality of the resulting declaration. |
| C6-C006 | set visibility | M | parser-action and compiler | Duplicate set visibility and comparison with read visibility are distinct predicates. |
| C6-C007 | promoted-property modifiers | M | parser-action and compiler | Target-valid tokens do not establish legal promotion context. |
| C6-C008 | void/never by position | E | compiler, visited type | Void/never remain structurally representable; test each type position and surviving/discarded path. |
| C6-C009 | static by position | S-partial | structure and compiler | type-without-static excludes the literal in selected positions; relative/name/scope checks remain external. |
| C6-C010 | self/parent scope | E | compiler and name resolution | Scope and resolved parent availability need separate evidence. |
| C6-C011 | nullable mixed/void/never/null | E | compiler, visited type | Nullable syntax is represented broadly; isolate each forbidden builtin. |
| C6-C012 | duplicate/redundant union/intersection | E | compiler and name resolution | Textual duplicates, resolved aliases and redundant DNF components differ. |
| C6-C013 | DNF structural grouping | S | structural parse | Union/intersection grouping is encoded by parenthesized-intersection-type; semantic redundancy remains C012. |
| C6-C014 | relative/intersection name resolution | E | compiler and name resolution | Relative/resolved class names require name-resolution proof, not just grouping. |
| C6-C015 | constructor-only promotion | E | compiler, visited parameters | Promotion requires concrete constructor scope; preserve discarded controls. |
| C6-C016 | abstract promotion | E | compiler, visited parameters | Abstract constructor promotion requires a visited-declaration predicate. |
| C6-C017 | variadic promotion | E | compiler, visited parameters | Variadic promotion is distinct from ordinary variadic syntax. |
| C6-C018 | variadic order/default | E | compiler, visited parameters | Order and default checks are not enforced by parameter-list repetition. |
| C6-C019 | duplicate parameter names | E | compiler, visited parameters | Name uniqueness requires declaration context. |
| C6-C020 | by-reference parameters/returns/captures | E | compiler and reference binding | Parameter/reference/return/capture predicates need separate isolation; PHP86-007 covers only a subset. |
| C6-C021 | void/never returns | E | compiler, visited return | Return statements require function return-type context. |
| C6-C022 | generator and yield-from restrictions | E | compiler, visited generator | Yield scope, yield-from reference and generator return compatibility are separate. |
| C6-C023 | NoDiscard and reserved declarations | E | compiler and attribute validation | NoDiscard applicability and reserved declaration names require separate predicates. |
| C6-C024 | readonly type/default/static restrictions | E | compiler, linking and value checks | PHP86-003 permits defaults; inherited default-prohibition labels are historical, not current negatives. |
| C6-C025 | private final properties | E | compiler, visited property | Private/final legality is not duplicate-modifier validation. |
| C6-C026 | abstract ordinary properties | E | compiler, visited property | Abstract ordinary versus hooked properties require visited-declaration context. |
| C6-C027 | get/set names | S-divergent | structure versus visited hook compiler | CA86-001: get/set-only structure rejects unknown discarded hooks accepted by Zend. |
| C6-C028 | duplicate/empty hooks | E | compiler, visited hook list | Empty/duplicate hook lists remain structurally possible and can be discarded. |
| C6-C029 | get/set parameter count/reference/default/variadic | E | compiler, visited hook parameters | Explicit get parentheses and set parameter properties require distinct checks; set return-reference marker is not a forbidden parameter. |
| C6-C030 | abstract/final/body combinations | E | parser-action and visited compiler | Target flags, abstract/final combinations and body requirements run at different stages. |
| C6-C031 | interface property/default/visibility restrictions | E | compiler and declaration linking | Shared class-member grammar deliberately leaves target-specific legality external. |
| C6-C032 | promotion/hooks interaction | E | compiler, visited promotion/hooks | Hook list triggers promotion even without visibility; constructor context remains external. |
| C6-C033 | parent hook scope | E | compiler and scope resolution | Parent-hook calls need current hook/class context and callable-conversion guards. |
| C6-C034 | backing int/string | E | compiler, visited enum backing | Full type syntax is parsed; int/string-only backing is late validation. |
| C6-C035 | backed case requires value | E | compiler, visited enum case | Case initializer syntax is optional; backing-dependent requirement is external. |
| C6-C036 | unbacked case forbids value | E | compiler, visited enum case | Initializer syntax alone cannot determine enum backing context. |
| C6-C037 | forbidden properties/abstract methods | E | compiler and declaration linking | Properties, abstract methods and magic methods need distinct predicates; PHP86-006 relaxes debugInfo only. |
| C6-C038 | case declarations in other class kinds | E | compiler, visited class body | Shared class-member permits cases structurally; declaration kind determines legality. |
| C6-C039 | deferred case value compatibility and duplicates | E | deferred value evaluation and linking | Resolved case values/types and duplicate values are not a source-shape verdict; historical scope exclusion requires separate current adjudication. |
| C6-C040 | method-target alias modifiers before folding | M | parser-action | Method-target token restrictions precede folding; rule-level API requires a supplied target/list. |
| C6-C041 | static/abstract aliases after folding | E | compiler, visited trait alias | Early method-target permission does not permit surviving static/abstract aliases. |
| C6-C042 | qualified precedence reference | S | structural parse | trait-precedence requires class-name :: method before insteadof; member lookup remains C045. |
| C6-C043 | class-name lists | E | compiler and class-name resolution | List structure is native; static/name validity depends on context and resolution. |
| C6-C044 | interface trait use | E | compiler, visited interface | Shared member syntax permits trait use; surviving interface compilation rejects it. |
| C6-C045 | deferred alias/precedence member lookup | E | declaration linking and member resolution | Resolved member lookup is historical out-of-scope semantics, not an approved PHP 8.6 release exclusion. |
| C6-C046 | assignment/compound/inc-dec writable operands | E | compiler write context and runtime operands | PHP86-004 constant-object permission is distinct from unresolved PHP86-014 assignment-result writes. |
| C6-C047 | reference and nullsafe restrictions | E | compiler reference/short-circuit context | PHP86-007 covers nullsafe reference return/yield; not every binding path is isolated. |
| C6-C048 | $this/$GLOBALS writes | E | compiler and variable scope | $this/$GLOBALS require scope and target-specific checks. |
| C6-C049 | unset calls/empty dimensions | E | compiler, visited unset | Syntactic calls may be variable expressions but cannot be unset; empty dimensions require separate checks. |
| C6-C050 | isset expression/empty dimensions | E | compiler, visited isset | isset parses expressions; variable-kind and read-offset checks remain external. |
| C6-C051 | foreach key/value/reference targets | E | compiler, visited foreach | Key/value/reference targets differ; accepted method-result iteration does not establish universal reference validity. |
| C6-C052 | destructuring empty/keyed/mixed syntax/unpack/literal targets | E | compiler, visited destructuring | Tree-wide mixed syntax, key/omission and writable-target rules are not implied by local list structure. |
| C6-C053 | named/positional/unpack order | E | compiler, visited arguments | Argument syntax is broad; order/unpack/PFA checks run only when visited. |
| C6-C054 | duplicate named arguments | E | compiler, visited arguments | Duplicate named arguments require per-call identity checks; attribute evidence covers a bounded context. |
| C6-C055 | constructor/nullsafe callable conversion | E | compiler, visited callable conversion | Constructor, nullsafe, parent-hook and PFA/FCC cases need distinct source predicates. |
| C6-C056 | attribute unpack/conversion/constant arguments | E | compiler attribute validation and constant evaluation | Surviving declaration ordering differs from discarded enclosing closures; target/link checks are additional. |
| C6-C057 | unparenthesized arrow pipe operand | E | compiler, visited pipe | Unparenthesized arrow survives parsing; visited compiler checks must respect discard boundaries. |
| C6-C058 | ordinary versus initializer traversal | E | ordinary compiler and constant evaluation | Traversal is context/dispatch-dependent; PHP86-015 remains unresolved. |
| C6-C059 | removed unset versus real cast | L-partial | scanner for real; visited compiler/evaluator for unset | Lexer rejects real before folding; unset remains a token/structure and rejects only when visited. |
| C6-C060 | invalid surviving constant-expression nodes | E | constant evaluation then surviving-node validation | Allowed initializer structure is broad; discarded nodes and surviving legality are distinct. |
| C6-C061 | static/noncapturing closure requirement | E | constant evaluation and closure compilation | Static/noncapturing checks apply to surviving constant closures, not arbitrary discarded branches. |
| C6-C062 | class and callable name folding | E | constant folding and resolved name/value | Name folding and resolved callable validity require separate classification; no whole-source guarantee. |
| C6-C063 | array unpack/offset errors during evaluation | E | constant evaluation | Array/offset diagnostics depend on values and evaluation order; do not relabel runtime/value errors syntax failures. |
| C6-C064 | warning-sensitive folding | E | constant evaluation and diagnostics | Warnings may inhibit folding; evidence needs configuration and precise traversal/diagnostic witnesses. |
| C6-C065 | dangling else/alternative binding | S | structural parse and binding | Matched/unmatched and closed-inner helpers encode binding; broader AST correspondence remains C6-COVERAGE. |
| C6-C066 | break/continue depth | E | compiler control context | Literal positive depth and enclosing loop/switch require scope information. |
| C6-C067 | duplicate labels/defaults | E | compiler control context | Labels and switch/match defaults require distinct uniqueness checks. |
| C6-C068 | goto into loops | E | compiler control context | Label existence and illegal scope entry require function-wide context. |
| C6-C069 | try requires catch/finally on compilation | E | compiler, visited try | Empty catch-list and optional finally are intentional; surviving bare try rejects later. |
| C6-C070 | namespace placement/mixing/import collisions | E | compiler namespace/import context | Namespace styles, placement and import collisions require source-level context. |
| C6-C071 | encoding/strict_types/declare | E | parser-action and compiler declare context | Encoding literal checks can run early; strict_types placement/value/block checks are distinct. |
| C6-C072 | halt outermost scope | S-partial | structure, scanner halt state and parser action | Top-level halt production restricts placement; upstream nested error action and scanner stop behavior require separate correspondence. |
| C6-C073 | reserved/special names and nested declarations | L-partial | lexical token category, structure and compiler scope | Reserved token classification is native; special names and nested-declaration legality remain contextual. |
