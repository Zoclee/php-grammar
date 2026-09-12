# PHP 8.5 grammar and source contract

This standalone specification has three parts: lexical/source rules, syntactic
EBNF, and contextual syntax constraints. All three apply when deciding whether
source is valid PHP 8.5. The EBNF is the repository's authoritative syntactic
artifact; it is not by itself an implementation of all Zend compile-time checks.
The implementation remains under audit. The verification limits below prevent
a claim of full PHP 8.5 conformance.

Grammar Completeness Phase 4 adds paired negative-boundary evidence without
changing the canonical productions below. Its 250 new structural-negative
fixtures reject during repository recognition; 22 new contextual-negative
fixtures parse structurally and reject during PHP compilation. All have nearby
valid repairs. The [boundary ledger](../../docs/php85-negative-boundaries.md)
records source evidence and the [Phase 4 report](../../docs/php85-phase4-negative-coverage.md)
records methodology and validation. This evidence does not replace the
standalone lexical or contextual rules in this specification. The
[parser/compiler boundary remediation](../../docs/php85-parser-compiler-remediation.md)
resolves the previously recorded discarded-closure witnesses and expands
coverage to the broader declaration category. Full conformance remains unproven.

Sources are `php-src` branch `PHP-8.5`, pinned at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`:

- [Parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y)
- [Scanner](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l)
- [Compiler](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c)

PHP 8.5.10 CLI lint is the executable comparison used in this audit. It is a
released patch build, not a build of the exact branch commit. Source references
below use function/production names, which remain useful if line numbers move.

## Lexical/source rules

The input is a byte stream with `zend.multibyte=0`. PHP does not require Unicode
normalization or valid UTF-8 in identifiers. A consumer using Zend's multibyte
conversion must perform that configured conversion before this byte model.

The initial state is HTML. Only recognized opening tags enter PHP mode:

| Source | Recognition and parser effect |
|---|---|
| `<?php` | Case insensitive; followed by space, tab, CR/LF newline, or EOF. Skip the tag. |
| `<?=` | Always enabled; emit the parser keyword `echo`. |
| `<?` | Enabled only with `short_open_tag`; skip the tag. |
| `?>` in PHP mode | Emit `;`, leave PHP mode, and consume one immediately following CRLF, CR, or LF. |

An unrecognized opening sequence remains HTML when short tags are disabled.
When enabled, `<?php!` starts a short-tag PHP region containing `php!`; it is
not a long opening tag. PHP scanner states for strings and block comments do
not treat embedded tags as source transitions. Single-line comments stop at
CR, LF, EOF, or `?>`. The closing tag itself is processed after the comment.

The syntactic root consumes **one token stream**, not independently complete
PHP regions. Inline HTML emits a nonempty statement token (`T_INLINE_HTML`).
Thus `<?php if ($x): ?>text<?php endif; ?>` is one conditional statement.
A closing tag supplies a semicolon wherever the parser requires that token;
it does not supply missing expressions or braces. EOF supplies no semicolon:
`<?= 1` is invalid, while `<?= 1 ?>` and `<?= 1;` are valid.
Once an opening tag is recognized, malformed PHP cannot be reclassified as HTML.
`__halt_compiler();` ends parsing; subsequent bytes are uninterpreted payload.
Its placement is subject to the outermost-scope contextual restriction.

Whitespace is one or more bytes from space, HT, LF, CR. FF and VT are not PHP
whitespace. `/*` ends at the **first** `*/`; comments do not nest. `/**` followed
by scanner whitespace starts a documentation comment; other `/**` forms remain
ordinary block comments. `#[` starts an attribute, never a `#` comment.

### Lexical primitives

These external primitives are explicitly declared in `php-grammar.json`.
They each consume exactly one byte; their surrounding lexical production
groups those bytes into tokens. They are not unrestricted token wildcards.

| Primitive | Definition |
|---|---|
| `code-unit` | Any byte 0x00–0xFF in an uninterpreted region. |
| `non-ascii-code-unit` | Only 0x80–0xFF. |
| `html-code-unit` | Byte in HTML state, stopping before the next enabled recognized opening tag. |
| `line-comment-code-unit` | Byte before CR, LF, EOF, or the first `?>`. |
| `block-comment-code-unit` | Byte before the first `*/`; never consume that terminator. |
| `single-quoted-code-unit` | Byte other than apostrophe or backslash inside a single-quoted string. |
| `encapsed-code-unit` | Text byte within the active double-quote, backtick, or heredoc state, excluding its closing delimiter, backslash, and interpolation starts. |
| `nowdoc-code-unit` | Uninterpreted body byte before the matching closing label at a line start, respecting indentation. |

`escape-sequence` consumes a backslash and its following byte. Single quotes
decode only `\\` and `\'`; other escapes preserve their backslash. Interpolating
strings decode `\n`, `\r`, `\t`, `\v`, `\e`, `\f`, `\\`, `\$`, the applicable
escaped quote, up to three octal digits, one or two hexadecimal digits after
`\x`, and `\u{HEX}`. Unicode escapes require at least one hexadecimal digit,
a closing brace, and a value no greater than 0x10FFFF. Unknown escapes remain
text; they are not all syntax errors. Binary `b`/`B` prefixes are accepted.

Complex `{$foo}` interpolation consumes `{` followed by the existing variable
syntax; the dollar sign is not duplicated. Simple interpolated offsets have
scanner-specific identifier, numeric-string, or variable forms, not arbitrary
quoted expressions. Braced interpolation re-enters PHP scanning. `${...}`
forms remain accepted but deprecated. Backticks are expressions, not constant
string literals.

Heredoc/nowdoc require a header newline. Spaces/tabs may follow `<<<`; no
trailing header whitespace follows the label or its quote. A heredoc label may
be unquoted or double quoted; a nowdoc label is single quoted. The closing label
must equal the opening label byte for byte, start at a line boundary after
optional indentation, and not be followed by an identifier byte. It need not
be followed by a semicolon or newline. All nonblank lines of text in the active heredoc/nowdoc state must have at least
the closing indentation. Lines inside nested scripting or nested strings are
not outer heredoc text and do not inherit its indentation requirement. Tabs and spaces must not be mixed in the indentation
being stripped. Blank lines may have less indentation. The scanner enforces
label equality and indentation; these are lexical constraints beyond ordinary
context-free EBNF. An empty body is valid.

### Scanner state transitions

The scanner maintains a stack; a string is not terminated by a quote, brace or
heredoc label that belongs to a nested state. The following transitions are
normative, following the pinned scanner's named states:

| Active state | Event and transition |
|---|---|
| SHEBANG | At executable-file entry with shebang skipping enabled, consume an initial `#!` line including its newline and enter INITIAL. Otherwise replay the input in INITIAL. `#!` in scripting is an ordinary hash comment. |
| INITIAL | Emit HTML until a recognized enabled opening tag; enter ST_IN_SCRIPTING. Short-tag configuration applies at every re-entry, including nested interpolation. |
| ST_IN_SCRIPTING | Quotes/backticks enter their string state. A valid heredoc header pushes its label and enters ST_HEREDOC or ST_NOWDOC. `{` pushes scripting; `}` pops its matching state. `?>` enters INITIAL and emits a semicolon. |
| ST_DOUBLE_QUOTES / ST_BACKQUOTE / ST_HEREDOC | Escapes and text remain in this state. `{$` pushes scripting and leaves `$` for normal variable scanning; `${` pushes ST_LOOKING_FOR_VARNAME. A simple variable followed immediately by `[` enters ST_VAR_OFFSET; a following `->`/`?->` enters property lookup only when an identifier-start byte follows the operator immediately. |
| ST_LOOKING_FOR_VARNAME | A label immediately followed by `[` or `}` emits T_STRING_VARNAME (including keyword spellings) and replaces this state with scripting. Otherwise replay the next byte in scripting. This distinguishes `${class[0]}` from `${name + 1}`. |
| ST_LOOKING_FOR_PROPERTY | Whitespace and comments retain lookup state. `->`/`?->` retain it; a label emits an ordinary property identifier and pops it. Any other byte pops and is replayed in the enclosing state. In particular `#[` here starts a hash comment, unlike its attribute meaning in scripting. |
| ST_VAR_OFFSET | Accept a label, variable, or T_NUM_STRING optionally preceded by `-`; `]` restores the string state. Quotes, comments, whitespace and floating-point spellings are not offset syntax. A following second offset is literal text unless a braced interpolation starts it. |
| ST_HEREDOC / ST_NOWDOC | Recognize only the active label at a line boundary, with the indentation and identifier-boundary conditions above. A label inside a nested interpolation/string cannot close the outer heredoc. ST_NOWDOC performs no interpolation/escape decoding. ST_END_HEREDOC pops the label and restores scripting. |

Comments have meaning in scripting and property lookup; their markers are text
in the enclosing string states. Braced interpolation can itself contain a
closure that exits PHP with `?>` and re-enters it: intervening HTML braces do
not close the interpolation. Nested strings use the same rules recursively.

T_NUM_STRING spelling is decimal LNUM (including leading zeroes and separators),
HNUM, BNUM or ONUM. Plain decimal values within the signed-long range may carry
an integer token value; other spellings keep their source text as the offset
string. Do not validate `08` as an octal PHP numeral in this state. The parser's
leading minus is separate; it is not allowed before a label or variable.

`yield` plus scanner whitespace/comments plus `from` with an identifier boundary
is the case-insensitive T_YIELD_FROM token. This EBNF spells it as two terminals;
the adapter must preserve that lexical decision. `&` lookahead selects Zend's
two ampersand token categories across whitespace/comments; their identical
spelling does not remove reference/type-position restrictions.

### Names and literal tokens

Identifier byte spelling is `[A-Za-z_\x80-\xFF][A-Za-z0-9_\x80-\xFF]*`.
That spelling does not imply every occurrence becomes `T_STRING`.
`identifier` in this EBNF means ordinary `T_STRING`; `semi-reserved-identifier`
corresponds to Zend parser `identifier`, including `reserved_non_modifiers`
and the seven modifier keywords. `__halt_compiler` is excluded. Variables use
identifier spelling after `$` independently of keyword classification.

Classes, interfaces, traits, labels, goto targets, global constants, and import
aliases require `T_STRING`. Function names also allow `readonly`. Methods,
class constants, enum cases, and named arguments allow the parser's wider
identifier category. `enum` is a keyword only in its scanner lookahead context;
it otherwise remains usable as a name. Property scanning after `->`/`?->`
has its own identifier state. `__halt_compiler` after an object operator is a
property name token, unlike a semi-reserved static method identifier.

Qualified, fully qualified, and namespace-relative names are atomic tokens.
Their components may have keyword spelling. Trivia is forbidden inside them.
An unqualified reserved keyword does not become a callable name through a
character-level production. Group imports have a separate trailing namespace
separator before `{`; it is not part of the preceding name token.

Numbers follow scanner `LNUM`, `DNUM`, `EXPONENT_DNUM`, `HNUM`, `BNUM`, `ONUM`.
Separators occur only singly between digits of the relevant base. Legacy octal
includes `0_7`. Prefixes accept upper/lower case. Integer overflow changes the
Zend numeric token/value category, not whether that numeral is valid source.
The repository retains a spelling-based integer category for overflowed forms.

## Expressions and dereferencing

Bison declares precedence from lowest to highest:

| Level | Operators/forms | Binding |
|---|---|---|
| 1–3 | throw; arrow body; include/include_once/require/require_once | Prefix/body precedence |
| 4–6 | or; xor; and | Left |
| 7–10 | print; yield; yield key `=>`; yield from | Prefix precedence |
| 11 | Assignment and compound assignments | Nested right operands |
| 12 | `? :` | Bison left; compile-time restrictions below |
| 13 | `??` | Right |
| 14–18 | `||`; `&&`; bitwise `|`; `^`; `&` | Left |
| 19 | `== != <> === !== <=>` | Non-associative |
| 20 | `< <= > >=` | Non-associative |
| 21–26 | `|>`; `.`; shifts; `+ -`; `* / %`; `!` | Binary left; `!` prefix |
| 27–30 | instanceof; unary `+ - ~`, casts, `@`; `**`; clone | `**` right; other forms follow parser categories |

Increment/decrement take Zend `variable`, not arbitrary postfix results.
`-2 ** 2` groups as `-(2 ** 2)`; `!$x instanceof Foo` groups as
`!($x instanceof Foo)`; coalescing groups right. Equality/relational chains are
invalid without parentheses. Full ternary chains and full/short mixed chains
require parentheses. Repeated short ternaries and nesting in the middle
operand are accepted. Ternary chains are structurally left-associated; the
mandatory restriction is applied during compilation, after constant folding
where applicable. A constant initializer can therefore discard an otherwise
forbidden chain before that check.

The EBNF preserves Zend's `simple_variable`, `new_variable`, `variable`,
`callable_variable`, `callable_expr`, `fully_dereferenceable`,
`array_object_dereferenceable`, and `new_dereferenceable` distinctions.
In this file the lexical `variable` is `T_VARIABLE`, and `variable-expression`
is Zend's syntactic `variable`. There is no universal postfix production.
`new Foo()` is directly dereferenceable; `new Foo` is not. Heredoc tokens are
scalars but not direct dereferenceable scalars. Removed curly-brace offsets
are excluded. Write-context restrictions remain contextual.

`ordinary-argument-list` and `first-class-callable-arguments` are disjoint.
Bare `...` is a complete callable-conversion list and cannot be mixed with
ordinary arguments. `constructor-argument-list` retains both parser forms:
Zend rejects constructor conversion during compilation, not parsing.
There is no call-time `&` argument modifier.
Clone-with permits named/unpacked lists and callable conversion. `clone($o)`
is also unary clone applied to a parenthesized expression; the special clone
argument production follows Zend's separate comma/named/unpacked alternatives.

### Unambiguous prefix and statement structure

Each `*-prefix-context` is a **nonempty expression prefix with its final
operand absent**. It records pending higher-precedence operators. Only a
prefix operator's own layer fills that position and consumes its operand.
For example, in `$x = print $y = 1`, the assignment before `print` belongs to
`assignment-prefix-context`; `print` consumes the complete right assignment.
In `2 ** -2 ** 2`, the first exponentiation is pending and unary minus consumes
the second exponentiation. Context productions do not introduce a second
high-precedence `throw`, `print`, arrow, include, yield, or `!` expression.
Binary repetition denotes a left fold; coalescing and exponentiation recurse
on their right operand. Context nodes record pending operations, not additional
PHP AST operations.

Yield's optional `=>` needs a further distinction: `closed-yield-*` productions
exclude a trailing yield with an operand but without its own key separator.
The separator therefore binds to the nearest such yield. This preserves
`yield yield 1 => 2`, `yield 1 => yield 2`, and nested keyed yields without
duplicating their trees. Parentheses delimit a complete ordinary expression.

Matched/unmatched statements propagate through the final bodies of `while`,
`for`, `foreach`, and `declare`. Braces, alternative-syntax terminators, and
the terminating `while` of `do` close that propagation. Before an alternative
`else:`/`elseif:`, `closed-inner-statement-list` requires a matched final
statement or declaration; otherwise Zend shifts the keyword toward an inner
unmatched if and rejects the colon. An `endif` terminator needs no such check. Both `else` and
`elseif` attach to the nearest unmatched `if`. A semicolon, including the
token emitted by `?>`, has only the `empty-statement` derivation when no
expression precedes it. `throw $e;` is an expression statement.

## Contextual syntax constraints

These constraints are mandatory even when structural EBNF accepts a construct.
The repository's structural matcher does not implement this entire layer.

| Context | Required validation and source |
|---|---|
| Constant expressions | Parse `expression`, perform the folding procedure below, then validate the surviving AST with `zend_is_allowed_in_const_expr` and `zend_compile_const_expr`. Surviving arbitrary calls, variables, assignments, shell execution, interpolation, match, throw, and arrow functions are forbidden. Static noncapturing closures and named first-class callables are permitted in 8.5. |
| Parameter defaults, global constants, attribute/constructor arguments | `allow_dynamic=true`: `new` with a statically determined class and object casts are allowed. No anonymous/dynamic class construction, `static`, unpacked constructor arguments, or constructor callable conversion. |
| Property defaults, class constants, enum values | `allow_dynamic=false`: surviving `new` and object casts are forbidden recursively. Other supported casts, static closures, and named callable conversion remain subject to the declared type and context. |
| Static locals | Initializers are runtime `expression`, not the constant-expression subset; PHP 8.3+ behavior is retained. |
| Constant calls | Surviving calls must be function/static-method callable conversions. The function/class/method name must already be an appropriate literal AST when its compiler check runs; see folding order below. Object-method conversions are forbidden. `static` class references are forbidden. |
| Arguments | No positional argument after named arguments/unpacking, no unpacking after named arguments, no duplicate named arguments. Attribute argument lists forbid unpacking and bare callable conversion. Built-in arity/name checks may reject clone/exit forms. |
| Attributed constants | Parser structure permits a **global** constant list; compilation requires only one constant per attributed declaration. Class constants may share attributes in a multiple-constant declaration. |
| Types | `mixed`, `void`, `never` must stand alone as applicable; `?mixed`, `?null`, duplicate/redundant unions, `true|false`, and built-in/scoped-name intersection members are invalid. `void`/`never` are return-only. Properties/promoted properties/class constants cannot use callable, void, or never. `static` is return-only with class scope. `self`/`parent` require the appropriate scope. |
| Parameters and closures | Unique parameters, only final parameter variadic, no variadic defaults, no forbidden auto-global/`$this` parameter or capture names, no duplicate captures or capture/parameter collisions. Nonempty `use` is structural. |
| Promotion | Only a concrete constructor in a legal class/trait context; no variadic promotion, duplicate property, invalid property type, or illegal modifiers. PHP 8.5 permits final promotion. Defaults initialize parameters, not property defaults. |
| Modifiers and declarations | Reject duplicate/conflicting modifiers, abstract-final conflicts, reserved class names, redeclarations, illegal nested class declarations, and invalid anonymous-class modifiers. Interfaces cannot use traits. Method bodies/visibility must match abstract/interface/concrete context. |
| Properties | Readonly properties need a type and cannot be static or have ordinary defaults. Only hooked properties may be abstract. Visibility/set visibility combinations and final/private combinations must be valid. |
| Hooks | One or two distinct hook kinds; no duplicate get/set; no static/readonly hooked properties. Only final is an explicit hook modifier. A get hook has no parameter list; a set list has one non-reference, nonvariadic parameter without a default and compatible type. Set parameters cannot be references. The parser accepts a reference-return marker on either hook; see the pinned-version note below. Concrete hooks need bodies; interface hooks and bodyless hooks on abstract properties are abstract. Abstract properties may mix concrete and abstract hooks, but must satisfy abstract-property validation. A final hook cannot be private or abstract. |
| Interface properties | Public or historical `var` hooked properties only; no property default; no final, protected/private, or explicitly abstract property. Hooks have no implementation body. |
| Class constants | One type precedes the entire list. Enforce type restrictions/value compatibility, modifier legality, and final/private restrictions. |
| Enums | Only int/string backing types. Backed cases require constant values; unbacked cases forbid values. Case names/values must satisfy uniqueness and type rules; some value checks are deferred beyond lint. No properties; cases are forbidden outside enums; trait composition and forbidden magic/member declarations require further checks. |
| Callable conversion | Reject surviving `new Foo(...)` and anonymous-class constructor conversion (`zend_compile_new`, `zend_compile_const_expr_new`). Reject conversion on a nullsafe call or the same nullsafe short-circuit chain (`zend_compile_call_common`). Ordinary function, object-method and static-method conversions remain valid. Clone conversion follows its separate parser production. |
| isset | `isset_variables` is a nonempty comma-separated list with optional trailing comma; pinned `isset_variable` is **expr**, not variable. At compilation, require `zend_is_variable`: VAR, DIM, PROP, NULLSAFE_PROP, STATIC_PROP. Direct calls and arithmetic fail, while `foo()[0]`, `foo()->p`, parenthesized variables, and nullsafe property reads can qualify. Reject empty read offsets and invalid read targets later in `zend_compile_isset_or_empty`. |
| Trait aliases | At most one method-target alias modifier, or an alias name alone; a compiled alias permits only public/protected/private/final. `zend_compile_trait_alias` rejects static and abstract; readonly is rejected by the parser's modifier-target conversion. Modifiers cannot be combined. |
| Writable variables | Assignment, reference binding, increment, unset, isset, destructuring, and foreach need appropriate read/write targets. Calls can reduce as `variable` but cannot be unset; nullsafe access cannot be written. Foreach keys cannot be references or destructuring lists. Empty array elements are only valid in destructuring. A destructuring tree cannot mix `[]` and `list()` forms (pinned `zend_compile.c`, lines 3250–3255). |
| Control flow | break/continue need a legal enclosing construct and positive literal level; goto targets/scope crossings must be legal; yield requires function scope; generator return types and return statements must be compatible. Match/switch permit only one default. A compiled try requires at least one catch or finally. A surviving pipe cannot take an unparenthesized arrow function as its right operand. |
| declare and namespaces | Directives require their specific literal values and placement; strict_types is 0/1, first statement, and not block form. Namespace styles cannot mix and namespace/import placement/conflicts must be legal. |

Authoritative compiler areas include `zend_compile_params`,
`zend_compile_typename_ex`, `zend_compile_attributes`, `zend_compile_prop_decl`,
`zend_compile_property_hooks`, `zend_compile_class_const_decl`,
`zend_compile_enum_case`, `zend_compile_foreach`, `zend_compile_conditional`,
`zend_compile_declare`, and `zend_compile_const_expr`.

### Parser/compiler declaration boundary

All class, interface, trait, enum, and anonymous-class bodies use the same
`class-member` alternatives, corresponding to `class_statement_list` and
`class_statement` (parser lines 973–1006). Optional attributes apply to ordinary
and hooked properties, methods, class constants, and enum cases; they do not
apply to trait-use statements. Declaration-kind legality is checked only when
the declaration is compiled. In particular, enum properties, non-enum cases,
interface trait use and ordinary interface properties must remain reducible.

`enum-backing-type` uses the full `type` expression (parser 655–657), including
nullable, union, intersection and static forms. `zend_compile_enum_backing_type`
then requires exactly int/string. `name-list` and `catch-type-list` consume
`class-name`, including static, as do Bison `class_name_list` and
`catch_name_list`; class-name scope/target checks remain contextual.

Hook syntax mirrors parser 1129–1177: an empty list is structurally possible;
each hook has attributes, optional target-valid modifiers, optional `&`, a
generic identifier, an optional complete parameter list, and either `;`, a
compound body, or `=> expression ;`. `zend_compile_property_hooks` (8655–8835)
enforces the live rules in the table. Explicit get parentheses, even `get()`,
are invalid when compiled; a supplied set list must have exactly one parameter.
Empty/unknown/duplicate hooks and invalid parameter/body combinations can be
discarded along with their enclosing static closure. Parameter hook lists use
the same grammar. A hook list itself triggers promotion, even without explicit
visibility; compilation requires a concrete constructor in an allowed context.

The pinned compiler does **not** reject the reference-return marker on a set
hook. PHP 8.5.10 accepts and executes `class C { public int $x { &set {} } }`
with a void-reference-return deprecation (`zend_compile_params`, 7752–7756).
This differs from the previous specification's unconditional “only get” rule.
It does not permit a reference **parameter** on set. Declaration/inheritance
checks outside these pinned files must not be inferred from lint alone.

Attributes consume ordinary `argument-list` (parser `attribute_decl`, 367–371).
`zend_compile_attributes` rejects unpacking and a callable-conversion list
before validating surviving argument expressions; those are not structural
argument-list exclusions. Folding an argument cannot erase an unpack marker
or invalid argument order on a surviving attribute declaration. Discarding the
entire enclosing closure can avoid compiling that attribute declaration.

### Checks that run during parser actions

Execution phase matters even for helpers defined in `zend_compile.c`.
`zend_modifier_token_to_flag`, `zend_modifier_list_to_flags`,
`zend_add_member_modifier`, and class/anonymous-class modifier helpers are
called by Bison actions. Target-invalid modifiers fail before folding:
properties accept visibility/set visibility, static, abstract, final, readonly;
methods accept visibility, static, abstract, final; constants accept visibility
and final; parameters accept visibility/set visibility, readonly and final;
hooks accept final only. Thus static/abstract **trait aliases** reach compilation,
but readonly aliases and static/abstract hook modifiers do not. Repetition in
the EBNF additionally requires pre-fold rejection of duplicates, conflicting
visibility and abstract/final combinations. Anonymous classes reject explicit
abstract/final modifiers in parser actions. These early constraints remain in
the contextual layer of the three-layer model and cannot be bypassed by a
discarded branch. The structural matcher does not enforce all parser actions.

The boundary fixture matrix tests 85 live/retained/discarded families, including
84 compiler-invalid families and the accepted set-reference exception. All six
discarding operators are tested against every family. Separate parser-action
controls require rejection even inside a discarded closure. This is systematic
category coverage, not a claim that every diagnostic path or deferred validator
has an independent witness.

### Constant-expression validation order

The constant wrappers all derive `expression`. A source-level subset cannot
faithfully specify Zend's acceptance: `const X = true ? 1 : foo();` is valid,
as are discarded `new Foo(...)` and `isset(1 + 2)` branches. Their live
companions are invalid. Consequently constructor/FCC and isset target checks
are contextual, even though syntactic helper productions distinguish their
lists. This is a necessary correction to the assumption that the pinned
`isset_variable` parser production takes `variable`.

Apply the following order from `zend_const_expr_to_zval` (compiler lines
11634–11649), not a blanket recursive ban on source tokens:

1. Apply parser actions and lexical validity first. A discarded branch must
   still parse; folding cannot repair an unmatched delimiter or invalid token.
2. Run `zend_eval_const_expr` (12079–12383). Fold literal binary/comparison,
   unary, supported cast, array and offset operations when the corresponding
   `zend_try_ct_eval_*` helper succeeds. Resolve eligible ordinary/class/magic
   constants and class names using Zend's compile-time environment. This is
   not arbitrary evaluation of user code or resolution of every named constant.
3. For `&&`/`||` (including word forms), visit both children for folding, then
   discard the irrelevant child when a literal left operand determines the
   result. For `??`, a literal non-null left operand discards the right without
   visiting it; literal null selects the right. Ternary folding visits the
   condition and only the selected arm when the condition is literal. With a
   nonliteral condition, visit both arms. A folding-time error in a visited
   node is not suppressed merely because later validation could discard it.
4. Validate only the surviving AST. Allowed kinds are literal values, binary
   operations, greater/greater-equal, AND/OR, unary operations/plus/minus,
   casts, conditional, dimensions, arrays/elements/unpack, constants, class
   constants/names, magic constants, coalesce, enum initialization, new,
   argument lists/named arguments, property/nullsafe-property reads, closures,
   function/static calls, and callable-conversion markers. Every other surviving
   kind is invalid. Array unpacking is distinct from constructor-argument
   unpacking; the latter is forbidden.
5. Enforce `allow_dynamic` on surviving new/object-cast nodes. It is true for
   parameter defaults, global constants, and attribute arguments (including
   nested constructor arguments), and false for property defaults, class
   constants and enum case values. Inherit it into surviving children. Scalar,
   boolean and array casts remain allowed; object casts are not folded into
   literal objects by `zend_try_ct_eval_cast`. Warning-sensitive operations may
   remain ASTs for later evaluation rather than being compile-time literals.
6. New expressions require a literal resolved class reference, no anonymous
   class or late-static reference, no callable conversion and no unpacked
   arguments. Names/class references may become literal through the actual
   folding traversal, as in `new ("std" . "Class")()`.
7. Function/static-method callable conversion requires literal string names
   at `zend_compile_const_expr_fcc`. Folding does not recursively traverse
   CALL/STATIC_CALL in `zend_eval_const_expr`; parser-time literal concatenation
   can nevertheless have produced a literal name already. Thus
   `("str" . "len")(...)` is valid, while
   `(true ? "strlen" : "foo")(...)` is not. The same distinction applies to
   computed method names. CLASS_CONST, NEW, properties, named arguments and
   argument lists have their own explicit traversal cases; do not infer a
   universal recursive folding rule. `static::`/`static::class` are forbidden;
   `self`/`parent` still require the appropriate class scope.
8. A surviving closure must be static and have no `use` captures. Compile its
   body as function code; do not apply the constant AST whitelist to that body.
   Compile parameter defaults and attributes using their own contexts. An
   ordinary nonstatic closure or arrow can occur in a discarded branch only.
9. Apply declared-type, declaration and argument-order checks in their actual
   compiler contexts. Lint does not prove callable existence, enum value
   uniqueness/type evaluation or all deferred constant resolution succeeds.

This reconciles the previous restricted constant-expression hierarchy with
folding and retains the three-layer architecture. The structural matcher
intentionally accepts the contextual-negative corpus; it does not execute this
validation algorithm.

## Deprecated but accepted syntax

PHP 8.5 accepts `(integer)`, `(boolean)`, `(double)`, and `(binary)` with
deprecations; casts are case insensitive and allow spaces/tabs inside their
parentheses, not comments/newlines. `(real)` and `(unset)` are removed and
excluded. `${...}` interpolation and backticks have applicable deprecations;
deprecation is not syntax rejection. Other deprecations unrelated to grammar
do not remove accepted forms.

## Remaining discrepancies and deliberate abstractions

- Parser/compiler declaration boundaries have been broadened for common members,
  enum backing types, trait aliases, hooks, attributes, try and class-name lists.
  The two previously recorded discarded-closure examples now pass in the
  ordinary valid corpus. They were examples of a wider category, not its full
  extent. The 85-family matrix and fatal-site inventory do not establish
  complete equivalence for every parser production or compiler path.
- Prefix-context and matched/unmatched equivalence remains a conformance
  blocker at the exhaustive level. Targeted derivation-count/operand-span
  regression tests pass and 59 explicit-grouping comparisons agree with the
  PHP 8.5 Zend AST. A newly confirmed alternative-if boundary error is fixed:
  before outer `else:`/`elseif:`, the preceding body cannot end in an unmatched
  inner if, including through loop/declare bodies. Ten negative AST witnesses
  cover that boundary. These results do not prove every prefix/operator or
  statement combination equivalent; retain the regression category until then.
- The lexical contract specifies the requested scanner states and the lexer
  now recurses through nested interpolation, comments and heredoc labels.
  Its token abstraction is not Zend's exact token stream. Source acceptance
  across the complete scanner/parser product has not been proven equivalent.
- External lexical primitives require a stateful scanner; raw character-only
  expansion is not a PHP source validator. Numeric overflow token/value
  categorization is deliberately abstracted while preserving numeral spelling.
- Contextual constraints, especially folding and deferred name/type checks,
  remain normative rather than a complete repository-owned validator. PHP lint
  is an independent compilation oracle, not a proof of later evaluation.

## Syntactic productions

The following block is generated verbatim from `php.ebnf` and checked for parity.

<!-- BEGIN GENERATED EBNF -->
```ebnf
(* Standalone PHP 8.5 syntactic EBNF over the source-token stream.
   Apply the lexical-state contract and contextual constraints in php.md. *)

source-file =
    top-statement-list ;

inline-html =
    inline-html-text ;

top-statement-list =
    { top-statement } ;

top-statement =
      statement
    | attributed-top-declaration
    | namespace-definition
    | namespace-use-declaration
    | halt-compiler-statement ;

attributed-top-declaration =
    [ attribute-groups ] ,
    ( function-declaration | class-declaration | interface-declaration
    | trait-declaration | enum-declaration )
    | constant-declaration
    | attribute-groups , "const" , constant-list , ";" ;

(* Lexical grammar. Whitespace and comments may appear between tokens unless
   a lexical production states otherwise. PHP keywords are case-insensitive. *)

whitespace =
    whitespace-character , { whitespace-character } ;

comment =
      line-comment
    | block-comment
    | doc-comment ;

line-comment =
      "#" , line-comment-text
    | "//" , line-comment-text ;

block-comment =
    "/*" , block-comment-text , "*/" ;

doc-comment =
    "/**" , doc-comment-text , "*/" ;

identifier =
    identifier-start , { identifier-part } ;

identifier-start =
      identifier-start-character
    | non-ascii-byte ;

identifier-part =
      identifier-start-character
    | decimal-digit
    | non-ascii-byte ;

identifier-start-character =
      ascii-letter
    | "_" ;

variable =
    "$" , identifier ;

variable-variable =
      "$" , variable-like
    | "$" , "{" , expression , "}" ;

variable-like =
      variable
    | variable-variable ;

label =
    identifier ;


semi-reserved-identifier =
    identifier | reserved-non-modifiers | "static" | "abstract" | "final"
    | "private" | "protected" | "public" | "readonly" ;

name-identifier =
    identifier-start , { identifier-part } ;

namespace-declaration-name =
    semi-reserved-identifier | qualified-name ;

qualified-name =
    name-identifier , "\\" , name-identifier , { "\\" , name-identifier } ;

fully-qualified-name =
    "\\" , name-identifier , { "\\" , name-identifier } ;

namespace-relative-name =
    "namespace" , "\\" , name-identifier , { "\\" , name-identifier } ;

name =
    identifier | qualified-name | fully-qualified-name | namespace-relative-name ;

class-name =
      name
    | "static" ;

integer-literal =
      decimal-integer-literal
    | binary-integer-literal
    | octal-integer-literal
    | explicit-octal-integer-literal
    | hexadecimal-integer-literal ;

decimal-integer-literal =
      "0"
    | decimal-digit-nonzero , { [ numeric-separator ] , decimal-digit } ;

binary-integer-literal =
    ( "0b" | "0B" ) , binary-digit , { [ numeric-separator ] , binary-digit } ;

octal-integer-literal =
    "0" , [ numeric-separator ] , octal-digit , { [ numeric-separator ] , octal-digit } ;

explicit-octal-integer-literal =
    ( "0o" | "0O" ) , octal-digit , { [ numeric-separator ] , octal-digit } ;

hexadecimal-integer-literal =
    ( "0x" | "0X" ) , hexadecimal-digit , { [ numeric-separator ] , hexadecimal-digit } ;

floating-literal =
      decimal-digits , "." , [ decimal-digits ] , [ exponent-part ]
    | "." , decimal-digits , [ exponent-part ]
    | decimal-digits , exponent-part ;

exponent-part =
    exponent-marker , [ "+" | "-" ] , decimal-digits ;

decimal-digits =
    decimal-digit , { [ numeric-separator ] , decimal-digit } ;

numeric-separator =
    "_" ;

string-literal =
      single-quoted-string
    | double-quoted-string
    | heredoc-string
    | nowdoc-string ;

single-quoted-string =
    [ "b" | "B" ] , "'" , single-quoted-string-content , "'" ;

double-quoted-string =
    [ "b" | "B" ] , "\"" , [ encapsulated-string-part , { encapsulated-string-part } ] , "\"" ;

heredoc-string =
    [ "b" | "B" ] , "<<<" , { " " | "\t" } ,
    ( heredoc-label | "\"" , heredoc-label , "\"" ) , newline ,
    heredoc-body , { " " | "\t" } , heredoc-label ;

nowdoc-string =
    [ "b" | "B" ] , "<<<" , { " " | "\t" } ,
    "'" , heredoc-label , "'" , newline , nowdoc-body , { " " | "\t" } , heredoc-label ;

encapsulated-string-part =
      string-text
    | encapsulated-variable ;

encapsulated-variable =
      variable
    | variable , "[" , encapsulated-offset , "]"
    | variable , object-operator , identifier
    | variable , nullsafe-object-operator , identifier
    | "${" , expression , "}"
    | "${" , identifier , "}"
    | "${" , identifier , "[" , expression , "]" , "}"
    | "{" , variable-expression , "}" ;

encapsulated-offset =
      identifier
    | numeric-string
    | "-" , numeric-string
    | variable ;

numeric-string =
    decimal-digits | binary-integer-literal | explicit-octal-integer-literal | hexadecimal-integer-literal ;

literal =
      integer-literal
    | floating-literal
    | string-literal ;

magic-constant =
      "__LINE__"
    | "__FILE__"
    | "__DIR__"
    | "__CLASS__"
    | "__TRAIT__"
    | "__METHOD__"
    | "__FUNCTION__"
    | "__PROPERTY__"
    | "__NAMESPACE__" ;

binary-digit =
      "0" | "1" ;

octal-digit =
      "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" ;

decimal-digit =
      "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;

decimal-digit-nonzero =
      "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;

hexadecimal-digit =
      decimal-digit | "a" | "b" | "c" | "d" | "e" | "f"
    | "A" | "B" | "C" | "D" | "E" | "F" ;

exponent-marker =
      "e" | "E" ;

ascii-letter =
      "a" | "b" | "c" | "d" | "e" | "f" | "g" | "h" | "i"
    | "j" | "k" | "l" | "m" | "n" | "o" | "p" | "q" | "r"
    | "s" | "t" | "u" | "v" | "w" | "x" | "y" | "z"
    | "A" | "B" | "C" | "D" | "E" | "F" | "G" | "H" | "I"
    | "J" | "K" | "L" | "M" | "N" | "O" | "P" | "Q" | "R"
    | "S" | "T" | "U" | "V" | "W" | "X" | "Y" | "Z" ;

type =
      union-type
    | intersection-type
    | nullable-type
    | simple-type ;

optional-type-without-static =
    [ type-without-static ] ;

type-without-static =
      union-type-without-static
    | intersection-type-without-static
    | nullable-type-without-static
    | simple-type-without-static ;

simple-type =
      "array" | "callable" | "iterable" | "bool" | "int" | "float"
    | "string" | "object" | "mixed" | "never" | "void" | "null"
    | "false" | "true" | "self" | "parent" | "static" | name ;

simple-type-without-static =
      "array" | "callable" | "iterable" | "bool" | "int" | "float"
    | "string" | "object" | "mixed" | "never" | "void" | "null"
    | "false" | "true" | "self" | "parent" | name ;

nullable-type =
    "?" , simple-type ;

nullable-type-without-static =
    "?" , simple-type-without-static ;

union-type =
    union-type-element , "|" , union-type-element , { "|" , union-type-element } ;

union-type-element =
      simple-type
    | parenthesized-intersection-type ;

union-type-without-static =
    union-type-without-static-element , "|" , union-type-without-static-element ,
    { "|" , union-type-without-static-element } ;

union-type-without-static-element =
      simple-type-without-static
    | parenthesized-intersection-type-without-static ;

intersection-type =
    simple-type , "&" , simple-type , { "&" , simple-type } ;

intersection-type-without-static =
    simple-type-without-static , "&" , simple-type-without-static ,
    { "&" , simple-type-without-static } ;

parenthesized-intersection-type =
    "(" , intersection-type , ")" ;

parenthesized-intersection-type-without-static =
    "(" , intersection-type-without-static , ")" ;

return-type =
    [ ":" , type ] ;

constant-expression =
    expression ;

expression =
    throw-expression ;

logical-or-expression =
    logical-xor-expression , { "or" , logical-xor-expression } ;

logical-xor-expression =
    logical-and-expression , { "xor" , logical-and-expression } ;

logical-and-expression =
    print-expression , { "and" , print-expression } ;

assignment-expression =
    conditional-expression | assignment-prefix , assignment-expression
    | variable-expression , "=" , "&" , variable-expression ;

assignment-operator =
      "=" | "+=" | "-=" | "*=" | "/=" | ".=" | "%="
    | "&=" | "|=" | "^=" | "<<=" | ">>=" | "**=" | "??=" ;

conditional-expression =
    coalesce-expression , { "?" , [ expression ] , ":" , coalesce-expression } ;

coalesce-expression =
    boolean-or-expression , [ "??" , coalesce-expression ] ;

boolean-or-expression =
    boolean-and-expression , { "||" , boolean-and-expression } ;

boolean-and-expression =
    bitwise-or-expression , { "&&" , bitwise-or-expression } ;

bitwise-or-expression =
    bitwise-xor-expression , { "|" , bitwise-xor-expression } ;

bitwise-xor-expression =
    bitwise-and-expression , { "^" , bitwise-and-expression } ;

bitwise-and-expression =
    equality-expression , { "&" , equality-expression } ;

equality-expression =
    relational-expression , [ ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , relational-expression ] ;

relational-expression =
    pipe-expression , [ ( "<" | "<=" | ">" | ">=" ) , pipe-expression ] ;

pipe-expression =
    concatenation-expression , { "|>" , concatenation-expression } ;

concatenation-expression =
    shift-expression , { "." , shift-expression } ;

shift-expression =
    additive-expression , { ( "<<" | ">>" ) , additive-expression } ;

additive-expression =
    multiplicative-expression , { ( "+" | "-" ) , multiplicative-expression } ;

multiplicative-expression =
    boolean-not-expression , { ( "*" | "/" | "%" ) , boolean-not-expression } ;

power-expression =
    clone-expression , [ "**" , power-expression ] ;

instanceof-expression =
    unary-expression , { "instanceof" , class-name-reference } ;

unary-expression =
    power-expression
    | [ power-prefix-context ] , ( ( "+" | "-" | "~" | "@" ) , unary-expression | cast-expression ) ;

cast-expression =
    cast-operator , unary-expression ;

void-cast-statement =
    "(void)" , expression , ";" ;

postfix-expression =
    variable-expression , [ "++" | "--" ] | primary-expression
    | ( "++" | "--" ) , variable-expression | closure-expression | match-expression ;

fully-dereferenceable-expression =
    variable-expression | "(" , expression , ")" | dereferenceable-scalar
    | class-constant | new-dereferenceable ;

callable-expression =
    callable-variable | "(" , expression , ")" | dereferenceable-scalar | new-dereferenceable ;

primary-expression =
    literal | array-creation-expression | constant | class-constant | magic-constant
    | "(" , expression , ")" | new-dereferenceable | "new" , class-name-reference
    | backtick-string
    | "isset" , "(" , isset-variable-list , [ "," ] , ")"
    | "empty" , "(" , expression , ")" | "eval" , "(" , expression , ")"
    | "exit" , [ argument-list ] | "die" , [ argument-list ] ;

variable-expression =
    callable-variable | static-member
    | array-object-dereferenceable , ( object-operator | nullsafe-object-operator ) , property-name ;

member-name =
    semi-reserved-identifier | "{" , expression , "}" | variable-like ;

object-operator =
    "->" ;

nullsafe-object-operator =
    "?->" ;

constant =
    name ;

class-constant =
    ( class-name | fully-dereferenceable-expression ) , "::" ,
    ( semi-reserved-identifier | "{" , expression , "}" ) ;

class-name-reference =
    class-name | new-variable | "(" , expression , ")" ;

function-call =
    name , argument-list | "readonly" , argument-list
    | ( class-name | fully-dereferenceable-expression ) , "::" , member-name , argument-list
    | callable-expression , argument-list ;

argument-list =
    ordinary-argument-list | first-class-callable-arguments ;

clone-argument-list =
    "(" , [ "..." | expression , "," , [ argument , { "," , argument } , [ "," ] ]
    | argument-no-expression , { "," , argument } , [ "," ] ] , ")" ;

argument =
      expression
    | argument-no-expression ;

argument-no-expression =
    semi-reserved-identifier , ":" , expression | "..." , expression ;

expression-list =
    expression , { "," , expression } ;

array-creation-expression =
      "array" , "(" , array-pair-list , ")"
    | "[" , array-pair-list , "]" ;

array-pair-list =
    [ array-pair ] , { "," , [ array-pair ] } ;

array-pair =
      expression
    | expression , "=>" , expression
    | "&" , variable-expression
    | expression , "=>" , "&" , variable-expression
    | "..." , expression
    | long-list-expression
    | expression , "=>" , long-list-expression ;

list-expression =
      long-list-expression
    | "[" , array-pair-list , "]" ;

long-list-expression =
    "list" , "(" , array-pair-list , ")" ;

include-expression =
    logical-or-expression
    | [ logical-or-prefix-context ] , include-operator , include-expression ;

match-expression =
    "match" , "(" , expression , ")" , "{" , [ match-arm-list ] , "}" ;

match-arm-list =
    match-arm , { "," , match-arm } , [ "," ] ;

match-arm =
      match-arm-condition-list , [ "," ] , "=>" , expression
    | "default" , [ "," ] , "=>" , expression ;

match-arm-condition-list =
    expression , { "," , expression } ;

closure-expression =
    [ attribute-groups ] , [ "static" ] , "function" , [ "&" ] ,
    "(" , parameter-list , ")" , [ lexical-variable-list ] , return-type ,
    compound-statement ;

arrow-function =
    arrow-function-header , arrow-expression ;

lexical-variable-list =
    "use" , "(" , lexical-variable , { "," , lexical-variable } , [ "," ] , ")" ;

lexical-variable =
      variable
    | "&" , variable ;

backtick-string-part-list =
    { encapsulated-string-part } ;

statement =
    simple-statement | if-statement | while-statement | for-statement
    | foreach-statement | declare-statement ;

compound-statement =
    "{" , inner-statement-list , "}" ;

inner-statement-list =
    { inner-statement } ;

inner-statement =
    statement | inner-declaration ;

inner-declaration =
    [ attribute-groups ] ,
      ( function-declaration
      | class-declaration
      | interface-declaration
      | trait-declaration
      | enum-declaration ) ;

expression-statement =
    expression , statement-terminator ;

echo-statement =
    "echo" , expression-list , statement-terminator ;

statement-terminator =
    ";" ;

global-statement =
    "global" , global-variable-list , ";" ;

global-variable-list =
    global-variable , { "," , global-variable } ;

global-variable =
    variable-like ;

static-statement =
    "static" , static-variable-list , ";" ;

static-variable-list =
    static-variable , { "," , static-variable } ;

static-variable =
    variable , [ "=" , expression ] ;

unset-statement =
    "unset" , "(" , unset-variable-list , [ "," ] , ")" , ";" ;

unset-variable-list =
    unset-variable , { "," , unset-variable } ;

unset-variable =
    variable-expression ;

return-statement =
    "return" , [ expression ] , ";" ;

break-statement =
    "break" , [ expression ] , ";" ;

continue-statement =
    "continue" , [ expression ] , ";" ;

goto-statement =
    "goto" , label , ";" ;

label-statement =
    label , ":" ;

empty-statement =
    ";" ;

if-statement =
    matched-if-statement | unmatched-if-statement ;

alt-elseif-list =
    "elseif" , "(" , expression , ")" , ":" ,
    ( inner-statement-list
    | [ closed-inner-statement-list ] , ( alt-elseif-list | alt-else-clause ) ) ;

alt-else-clause =
    "else" , ":" , inner-statement-list ;

while-statement =
    matched-while-statement | unmatched-while-statement ;

do-statement =
    "do" , statement , "while" , "(" , expression , ")" , ";" ;

for-statement =
    matched-for-statement | unmatched-for-statement ;

for-expression-list =
    [ for-expression , { "," , for-expression } ] ;

for-expression =
      expression
    | "(void)" , expression ;

for-condition-expression-list =
    [ [ for-expression-list , "," ] , expression ] ;

foreach-statement =
    matched-foreach-statement | unmatched-foreach-statement ;

foreach-target =
    [ foreach-key , "=>" ] , foreach-value ;

foreach-key =
    foreach-variable ;

foreach-value =
    foreach-variable ;

foreach-variable =
      variable-expression
    | "&" , variable-expression
    | list-expression ;

switch-statement =
      "switch" , "(" , expression , ")" , "{" , switch-case-list , "}"
    | "switch" , "(" , expression , ")" , ":" , switch-case-list ,
      "endswitch" , ";" ;

switch-case-list =
    [ ";" ] , { switch-case } ;

switch-case =
      "case" , expression , ( ":" | ";" ) , inner-statement-list
    | "default" , ( ":" | ";" ) , inner-statement-list ;
declare-statement =
    matched-declare-statement | unmatched-declare-statement ;

declare-directive-list =
    declare-directive , { "," , declare-directive } ;

declare-directive =
    identifier , "=" , constant-expression ;

try-statement =
    "try" , compound-statement , catch-list , [ finally-clause ] ;

catch-list =
    { catch-clause } ;

catch-clause =
    "catch" , "(" , catch-type-list , [ variable ] , ")" , compound-statement ;

catch-type-list =
    class-name , { "|" , class-name } ;

finally-clause =
    "finally" , compound-statement ;

function-declaration =
    "function" , [ "&" ] , ( identifier | "readonly" ) , "(" , parameter-list , ")" ,
    return-type , compound-statement ;

parameter-list =
    [ parameter , { "," , parameter } , [ "," ] ] ;

parameter =
    [ attribute-groups ] , parameter-modifiers , optional-type-without-static ,
    [ "&" ] , [ "..." ] , variable , [ "=" , parameter-default ] ,
    [ property-hook-block ] ;

parameter-modifiers =
    { parameter-modifier } ;

parameter-modifier =
      "public" | "protected" | "private" | "public(set)" | "protected(set)"
    | "private(set)" | "readonly" | "final" ;

class-declaration =
    class-modifiers , "class" , identifier , [ extends-clause ] ,
    [ implements-clause ] , "{" , class-member-list , "}" ;

anonymous-class =
    [ attribute-groups ] , class-modifiers , "class" , [ constructor-argument-list ] , [ extends-clause ] ,
    [ implements-clause ] , "{" , class-member-list , "}" ;

class-modifiers =
    { class-modifier } ;

class-modifier =
      "abstract" | "final" | "readonly" ;

extends-clause =
    "extends" , class-name ;

implements-clause =
    "implements" , name-list ;

name-list =
    class-name , { "," , class-name } ;

class-member-list =
    { class-member } ;

class-member =
      [ attribute-groups ] ,
      ( property-declaration
      | method-declaration
      | class-constant-declaration
      | enum-case )
    | trait-use-declaration ;

property-declaration =
      property-modifier-list , optional-type-without-static , property-list , ";"
    | "var" , optional-type-without-static , ( property-list , ";" | hooked-property )
    | property-modifier-list , optional-type-without-static , hooked-property ;

property-modifier-list =
    property-modifier , { property-modifier } ;

property-list =
    property-element , { "," , property-element } ;

property-element =
    variable , [ "=" , property-default ] ;

hooked-property =
    variable , [ "=" , property-default ] , property-hook-block ;

property-hook-block =
    "{" , property-hook-list , "}" ;

property-hook-list =
    { [ attribute-groups ] , property-hook } ;

property-hook =
    property-hook-modifiers , [ "&" ] , identifier ,
    [ "(" , parameter-list , ")" ] , property-hook-body ;

property-hook-body =
      ";"
    | compound-statement
    | "=>" , expression , ";" ;

property-hook-modifiers =
    { property-hook-modifier } ;

property-hook-modifier =
    "final" ;

property-modifier =
    property-visibility-modifier | set-visibility-modifier | "static" | "readonly" | "final" | "abstract" ;

property-visibility-modifier =
      "public" | "protected" | "private" ;

set-visibility-modifier =
      "public(set)" | "protected(set)" | "private(set)" ;

method-declaration =
    method-modifiers , "function" , [ "&" ] , semi-reserved-identifier ,
    "(" , parameter-list , ")" , return-type , method-body ;

method-modifiers =
    { method-modifier } ;

method-modifier =
      "public" | "protected" | "private" | "abstract" | "final" | "static" ;

method-body =
      compound-statement
    | ";" ;

class-constant-declaration =
    class-constant-modifiers , "const" , [ type ] , class-constant-list , ";" ;

class-constant-modifiers =
    { class-constant-modifier } ;

class-constant-modifier =
      "public" | "protected" | "private" | "final" ;

class-constant-list =
    class-constant-element , { "," , class-constant-element } ;

class-constant-element =
    semi-reserved-identifier , "=" , class-constant-initializer ;

constant-declaration =
    "const" , constant-list , ";" ;

constant-list =
    constant-element , { "," , constant-element } ;

constant-element =
    identifier , "=" , global-constant-initializer ;

interface-declaration =
    "interface" , identifier , [ interface-extends-clause ] ,
    "{" , interface-member-list , "}" ;

interface-extends-clause =
    "extends" , name-list ;

interface-member-list =
    { interface-member } ;

interface-member =
    class-member ;

trait-declaration =
    "trait" , identifier , "{" , class-member-list , "}" ;

trait-use-declaration =
      "use" , name-list , ";"
    | "use" , name-list , trait-adaptation-block ;

trait-adaptation-block =
    "{" , { trait-adaptation } , "}" ;

trait-adaptation =
      trait-precedence
    | trait-alias ;

trait-precedence =
    class-name , "::" , semi-reserved-identifier , "insteadof" , name-list , ";" ;

trait-alias =
    trait-method-reference , "as" ,
    ( identifier | reserved-non-modifiers | trait-alias-modifier , [ semi-reserved-identifier ] ) , ";" ;

trait-method-reference =
    semi-reserved-identifier | class-name , "::" , semi-reserved-identifier ;

enum-declaration =
    "enum" , identifier , [ enum-backing-type ] , [ implements-clause ] ,
    "{" , enum-member-list , "}" ;

enum-backing-type =
    ":" , type ;

enum-member-list =
    { enum-member } ;

enum-member =
    class-member ;

enum-case =
    "case" , semi-reserved-identifier , [ "=" , enum-case-initializer ] , ";" ;

namespace-definition =
      "namespace" , namespace-declaration-name , ";"
    | "namespace" , namespace-declaration-name , "{" , top-statement-list , "}"
    | "namespace" , "{" , top-statement-list , "}" ;

namespace-use-declaration =
      "use" , [ use-type ] , use-declaration-list , ";"
    | "use" , mixed-group-use-declaration , ";"
    | "use" , use-type , group-use-declaration , ";" ;

use-type =
      "function"
    | "const" ;

use-declaration-list =
    use-declaration , { "," , use-declaration } ;

use-declaration =
    legacy-namespace-name , [ "as" , identifier ] ;

legacy-namespace-name =
    identifier | qualified-name | fully-qualified-name ;

group-use-declaration =
    legacy-namespace-name , "\\" , "{" , unprefixed-use-declaration-list , [ "," ] , "}" ;

mixed-group-use-declaration =
    legacy-namespace-name , "\\" , "{" , inline-use-declaration-list , [ "," ] , "}" ;

unprefixed-use-declaration-list =
    unprefixed-use-declaration , { "," , unprefixed-use-declaration } ;

unprefixed-use-declaration =
    ( identifier | qualified-name ) , [ "as" , identifier ] ;

inline-use-declaration-list =
    inline-use-declaration , { "," , inline-use-declaration } ;

inline-use-declaration =
    [ use-type ] , unprefixed-use-declaration ;

attribute-groups =
    attribute-group , { attribute-group } ;

attribute-group =
    "#[" , attribute-list , [ "," ] , "]" ;

attribute-list =
    attribute , { "," , attribute } ;

attribute =
    class-name , [ argument-list ] ;

halt-compiler-statement =
    "__halt_compiler" , "(" , ")" , ";" , halt-compiler-data ;

(* Lexical leaves intentionally described as named categories instead of
   implementation-specific regular expressions. *)

inline-html-text =
    inline-html-character , { inline-html-character } ;

line-comment-text =
    { line-comment-character } ;

block-comment-text =
    { block-comment-character } ;

doc-comment-text =
    { block-comment-character } ;

single-quoted-string-content =
    { single-quoted-string-character | escape-sequence } ;

string-text =
    ( string-character | escape-sequence ) , { string-character | escape-sequence } ;

heredoc-label =
    name-identifier ;

heredoc-body =
    { encapsulated-string-part } ;

nowdoc-body =
    { nowdoc-character } ;

halt-compiler-data =
    { source-character } ;

source-character =
    code-unit ;

inline-html-character =
    html-code-unit ;

line-comment-character =
    line-comment-code-unit ;

block-comment-character =
    block-comment-code-unit ;

single-quoted-string-character =
    single-quoted-code-unit ;

string-character =
    encapsed-code-unit ;

nowdoc-character =
    nowdoc-code-unit ;

escape-sequence =
    "\\" , source-character ;

whitespace-character =
    " " | "\t" | "\n" | "\r" ;

non-ascii-byte =
    non-ascii-code-unit ;


reserved-non-modifiers =
    "and" | "array" | "as" | "break" | "callable" | "case" | "catch" | "class" | "clone" | "const" | "continue" | "declare" | "default" | "do" | "echo" | "else" | "elseif" | "empty" | "enddeclare" | "endfor" | "endforeach" | "endif" | "endswitch" | "endwhile" | "enum" | "eval" | "exit" | "die" | "extends" | "finally" | "fn" | "for" | "foreach" | "function" | "global" | "goto" | "if" | "implements" | "include" | "include_once" | "instanceof" | "insteadof" | "interface" | "isset" | "list" | "match" | "namespace" | "new" | "or" | "print" | "require" | "require_once" | "return" | "switch" | "throw" | "trait" | "try" | "unset" | "use" | "var" | "while" | "xor" | "yield"
    | magic-constant ;

boolean-not-expression =
    instanceof-expression
    | [ instanceof-prefix-context ] , "!" , boolean-not-expression ;

clone-expression =
    "clone" , clone-expression | "clone" , clone-argument-list | postfix-expression ;

dereferenceable-scalar =
    array-creation-expression | single-quoted-string | double-quoted-string ;

new-dereferenceable =
    "new" , class-name-reference , constructor-argument-list | "new" , anonymous-class ;

array-object-dereferenceable =
    fully-dereferenceable-expression | constant | magic-constant ;

callable-variable =
    variable-like
    | array-object-dereferenceable , "[" , [ expression ] , "]"
    | array-object-dereferenceable , ( object-operator | nullsafe-object-operator ) , property-name , argument-list
    | function-call ;

static-member =
    ( class-name | fully-dereferenceable-expression ) , "::" , variable-like ;

new-variable =
    variable-like
    | new-variable , "[" , [ expression ] , "]"
    | new-variable , ( object-operator | nullsafe-object-operator ) , property-name
    | ( class-name | new-variable ) , "::" , variable-like ;

property-name =
    identifier | "{" , expression , "}" | variable-like ;

backtick-string =
    "`" , backtick-string-part-list , "`" ;

newline =
    "\r\n" | "\n" | "\r" ;

throw-expression =
    arrow-expression
    | [ arrow-prefix-context ] , "throw" , throw-expression ;

arrow-expression =
    include-expression | [ include-prefix-context ] , arrow-function ;

print-expression =
    yield-expression
    | [ yield-prefix-context ] , "print" , print-expression ;

yield-expression =
    yield-key-expression
    | [ yield-key-prefix-context ] , "yield" , [ yield-expression ] ;

yield-from-expression =
    assignment-expression
    | [ assignment-prefix-context ] , "yield" , "from" , yield-from-expression ;

parameter-default =
    constant-expression ;

property-default =
    constant-expression ;

class-constant-initializer =
    constant-expression ;

global-constant-initializer =
    constant-expression ;

enum-case-initializer =
    constant-expression ;

trait-alias-modifier =
    method-modifier ;


ordinary-argument-list =
    "(" , [ argument , { "," , argument } , [ "," ] ] , ")" ;


first-class-callable-arguments =
    "(" , "..." , ")" ;


constructor-argument-list =
    ordinary-argument-list | first-class-callable-arguments ;


isset-variable-list =
    isset-variable , { "," , isset-variable } ;


isset-variable =
    expression ;


matched-statement =
    simple-statement | matched-if-statement
    | matched-while-statement | matched-for-statement
    | matched-foreach-statement | matched-declare-statement ;


simple-statement =
    inline-html
    | compound-statement
    | do-statement
    | switch-statement
    | try-statement
    | expression-statement
    | void-cast-statement
    | echo-statement
    | global-statement
    | static-statement
    | unset-statement
    | return-statement
    | break-statement
    | continue-statement
    | goto-statement
    | label-statement
    | empty-statement ;


unmatched-statement =
    unmatched-if-statement | unmatched-while-statement
    | unmatched-for-statement | unmatched-foreach-statement | unmatched-declare-statement ;


matched-if-statement =
    "if" , "(" , expression , ")" , matched-statement ,
    { "elseif" , "(" , expression , ")" , matched-statement } , "else" , matched-statement
    | alternative-if-statement ;


unmatched-if-statement =
    "if" , "(" , expression , ")" ,
    ( statement
    | matched-statement , { "elseif" , "(" , expression , ")" , matched-statement } ,
      ( "elseif" , "(" , expression , ")" , unmatched-statement
      | "else" , unmatched-statement )
    | matched-statement , "elseif" , "(" , expression , ")" , matched-statement ,
      { "elseif" , "(" , expression , ")" , matched-statement } ) ;


alternative-if-statement =
    "if" , "(" , expression , ")" , ":" ,
    ( inner-statement-list
    | [ closed-inner-statement-list ] , ( alt-elseif-list | alt-else-clause ) ) , "endif" , ";" ;

closed-inner-statement-list =
    { inner-statement } , ( matched-statement | inner-declaration ) ;


matched-while-statement =
    "while" , "(" , expression , ")" , matched-statement
    | "while" , "(" , expression , ")" , ":" , inner-statement-list ,
      "endwhile" , ";" ;


unmatched-while-statement =
    "while" , "(" , expression , ")" , unmatched-statement ;


matched-for-statement =
    "for" , "(" , for-expression-list , ";" , for-condition-expression-list , ";" ,
      for-expression-list , ")" , matched-statement
    | "for" , "(" , for-expression-list , ";" , for-condition-expression-list , ";" ,
      for-expression-list , ")" , ":" , inner-statement-list , "endfor" , ";" ;


unmatched-for-statement =
    "for" , "(" , for-expression-list , ";" , for-condition-expression-list , ";" ,
      for-expression-list , ")" , unmatched-statement ;


matched-foreach-statement =
    "foreach" , "(" , expression , "as" , foreach-target , ")" , matched-statement
    | "foreach" , "(" , expression , "as" , foreach-target , ")" , ":" ,
      inner-statement-list , "endforeach" , ";" ;


unmatched-foreach-statement =
    "foreach" , "(" , expression , "as" , foreach-target , ")" , unmatched-statement ;


matched-declare-statement =
    "declare" , "(" , declare-directive-list , ")" , matched-statement
    | "declare" , "(" , declare-directive-list , ")" , ":" ,
      inner-statement-list , "enddeclare" , ";" ;


unmatched-declare-statement =
    "declare" , "(" , declare-directive-list , ")" , unmatched-statement ;


arrow-prefix-context =
    include-prefix-context | [ include-prefix-context ] , arrow-function-header , [ arrow-prefix-context ] ;


include-prefix-context =
    logical-or-prefix-context | [ logical-or-prefix-context ] , include-operator , [ include-prefix-context ] ;


logical-or-prefix-context =
    logical-xor-prefix-context | logical-or-expression , "or" , [ logical-xor-prefix-context ] ;


logical-xor-prefix-context =
    logical-and-prefix-context | logical-xor-expression , "xor" , [ logical-and-prefix-context ] ;


logical-and-prefix-context =
    print-prefix-context | logical-and-expression , "and" , [ print-prefix-context ] ;


print-prefix-context =
    yield-prefix-context | [ yield-prefix-context ] , "print" , [ print-prefix-context ] ;


yield-prefix-context =
    yield-key-prefix-context
    | [ yield-key-prefix-context ] , "yield" , [ yield-prefix-context ] ;


yield-from-prefix-context =
    assignment-prefix-context | [ assignment-prefix-context ] , "yield" , "from" , [ yield-from-prefix-context ] ;


assignment-prefix =
    variable-expression , assignment-operator | list-expression , "=" ;


assignment-prefix-context =
    conditional-prefix-context | assignment-prefix , { assignment-prefix } , [ conditional-prefix-context ] ;


conditional-prefix-context =
    coalesce-prefix-context
    | conditional-expression , "?" , [ expression ] , ":" , [ coalesce-prefix-context ] ;


coalesce-prefix-context =
    boolean-or-prefix-context | boolean-or-expression , "??" , { boolean-or-expression , "??" } , [ boolean-or-prefix-context ] ;


boolean-or-prefix-context =
    boolean-and-prefix-context | boolean-or-expression , "||" , [ boolean-and-prefix-context ] ;


boolean-and-prefix-context =
    bitwise-or-prefix-context | boolean-and-expression , "&&" , [ bitwise-or-prefix-context ] ;


bitwise-or-prefix-context =
    bitwise-xor-prefix-context | bitwise-or-expression , "|" , [ bitwise-xor-prefix-context ] ;


bitwise-xor-prefix-context =
    bitwise-and-prefix-context | bitwise-xor-expression , "^" , [ bitwise-and-prefix-context ] ;


bitwise-and-prefix-context =
    equality-prefix-context | bitwise-and-expression , "&" , [ equality-prefix-context ] ;


equality-prefix-context =
    relational-prefix-context | relational-expression , ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , [ relational-prefix-context ] ;


relational-prefix-context =
    pipe-prefix-context | pipe-expression , ( "<" | "<=" | ">" | ">=" ) , [ pipe-prefix-context ] ;


pipe-prefix-context =
    concatenation-prefix-context | pipe-expression , "|>" , [ concatenation-prefix-context ] ;


concatenation-prefix-context =
    shift-prefix-context | concatenation-expression , "." , [ shift-prefix-context ] ;


shift-prefix-context =
    additive-prefix-context | shift-expression , ( "<<" | ">>" ) , [ additive-prefix-context ] ;


additive-prefix-context =
    multiplicative-prefix-context | additive-expression , ( "+" | "-" ) , [ multiplicative-prefix-context ] ;


multiplicative-prefix-context =
    boolean-not-prefix-context | multiplicative-expression , ( "*" | "/" | "%" ) , [ boolean-not-prefix-context ] ;


boolean-not-prefix-context =
    instanceof-prefix-context | [ instanceof-prefix-context ] , "!" , [ boolean-not-prefix-context ] ;


instanceof-prefix-context =
    unary-prefix-context ;


unary-prefix-context =
    power-prefix-context | [ power-prefix-context ] , unary-operator , [ unary-prefix-context ] ;


power-prefix-context =
    clone-prefix-context | clone-expression , "**" , { clone-expression , "**" } , [ clone-prefix-context ] ;


clone-prefix-context =
    "clone" , { "clone" } ;


arrow-function-header =
    [ attribute-groups ] , [ "static" ] , "fn" , [ "&" ] ,
    "(" , parameter-list , ")" , return-type , "=>" ;


include-operator =
    "include" | "include_once" | "require" | "require_once" ;


unary-operator =
    "+" | "-" | "~" | "@" | cast-operator ;


cast-operator =
    "(int)" | "(integer)" | "(float)" | "(double)" | "(string)"
    | "(binary)" | "(array)" | "(object)" | "(bool)" | "(boolean)" ;

yield-key-expression =
    yield-from-expression
    | [ yield-from-prefix-context ] , "yield" , yield-key , "=>" , yield-key-expression ;

yield-key-prefix-context =
    yield-from-prefix-context
    | [ yield-from-prefix-context ] , "yield" , yield-key , "=>" , [ yield-key-prefix-context ] ;

closed-yield-throw-expression =
    closed-yield-arrow-expression
    | [ closed-yield-arrow-prefix-context ] , "throw" , closed-yield-throw-expression ;

closed-yield-arrow-expression =
    closed-yield-include-expression | [ closed-yield-include-prefix-context ] , arrow-function-header , closed-yield-arrow-expression ;

closed-yield-arrow-prefix-context =
    closed-yield-include-prefix-context | [ closed-yield-include-prefix-context ] , arrow-function-header , [ closed-yield-arrow-prefix-context ] ;

closed-yield-include-expression =
    closed-yield-logical-or-expression
    | [ closed-yield-logical-or-prefix-context ] , include-operator , closed-yield-include-expression ;

closed-yield-include-prefix-context =
    closed-yield-logical-or-prefix-context | [ closed-yield-logical-or-prefix-context ] , include-operator , [ closed-yield-include-prefix-context ] ;

closed-yield-logical-or-expression =
    closed-yield-logical-xor-expression , { "or" , closed-yield-logical-xor-expression } ;

closed-yield-logical-or-prefix-context =
    closed-yield-logical-xor-prefix-context | closed-yield-logical-or-expression , "or" , [ closed-yield-logical-xor-prefix-context ] ;

closed-yield-logical-xor-expression =
    closed-yield-logical-and-expression , { "xor" , closed-yield-logical-and-expression } ;

closed-yield-logical-xor-prefix-context =
    closed-yield-logical-and-prefix-context | closed-yield-logical-xor-expression , "xor" , [ closed-yield-logical-and-prefix-context ] ;

closed-yield-logical-and-expression =
    closed-yield-print-expression , { "and" , closed-yield-print-expression } ;

closed-yield-logical-and-prefix-context =
    closed-yield-print-prefix-context | closed-yield-logical-and-expression , "and" , [ closed-yield-print-prefix-context ] ;

closed-yield-print-expression =
    closed-yield-yield-expression
    | [ closed-yield-yield-prefix-context ] , "print" , closed-yield-print-expression ;

closed-yield-print-prefix-context =
    closed-yield-yield-prefix-context | [ closed-yield-yield-prefix-context ] , "print" , [ closed-yield-print-prefix-context ] ;

closed-yield-yield-expression =
    yield-key-expression | [ yield-key-prefix-context ] , "yield" ;

closed-yield-yield-prefix-context =
    yield-key-prefix-context ;

yield-key =
    closed-yield-yield-expression
    | [ yield-key-prefix-context ] ,
      ( "throw" , closed-yield-throw-expression
      | arrow-function-header , closed-yield-arrow-expression
      | include-operator , closed-yield-include-expression
      | "print" , closed-yield-print-expression ) ;
```
<!-- END GENERATED EBNF -->
