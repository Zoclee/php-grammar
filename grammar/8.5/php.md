# PHP 8.5 syntax specification

## Status and scope

This standalone specification describes PHP 8.5 source syntax: byte-level
recognition, structural syntax, and contextual syntax constraints. It is a
repository specification grounded in the pinned PHP implementation, not an
official PHP language standard or a proof of full PHP conformance.

[`php.ebnf`](php.ebnf) is authoritative for structural syntax. The source and
lexical rules and contextual constraints in this document also apply when
deciding whether source is valid. Runtime execution and exact malformed-input
recovery are outside this source-validity contract.

Sources are `php-src` branch `PHP-8.5`, pinned at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`:

- [Parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y)
- [Scanner](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l)
- [Compiler](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c)

PHP 8.5.10 CLI lint is the executable comparison used for bounded differential evidence. It is a
released patch build, not a build of the exact branch commit. Source references
below use function/production names, which remain useful if line numbers move.

## Conformance model

Source validity requires all three applicable layers:

### Layer 1 — Source/lexical rules

These rules define byte-level source recognition, scanner states and their
stack, tokens, PHP/HTML transitions, comments, strings, heredoc/nowdoc behavior,
and lexical primitives. A stateful scanner must apply them before structural
matching; character-only expansion of the EBNF is insufficient.

### Layer 2 — Syntactic EBNF

The canonical EBNF defines structural syntax over the resulting token/source
abstraction. It is complete and independently consumable for PHP 8.5.
Structural acceptance alone does not establish source validity.

### Layer 3 — Contextual syntax constraints

These mandatory constraints describe parser-action and compiler restrictions
that the context-free EBNF does not faithfully or usefully encode. Parser actions
run before folding; later compiler checks apply in their actual compilation
contexts, including whether an AST survives folding. This layer is not a claim
that the repository implements every contextual validator.

In normative prose, **must** and **must not** express requirements, **may**
expresses permission, and **is** describes a required property. Examples and
implementation explanations illustrate the rules; they do not override them.
Verified rules remain normative within this source profile. Bounded tests do
not establish exhaustive scanner, parser, or compiler equivalence; see
[known abstractions and conformance limits](#known-abstractions-and-conformance-limits).

### Reading guide

- [Source and lexical model](#source-and-lexical-model),
  [scanner states](#scanner-state-transitions), [names](#names-and-tokens), and
  [literals](#literals-and-strings) define the input model.
- [Types](#types), [expressions](#expressions-and-precedence),
  [dereferencing](#variables-calls-and-dereferencing),
  [arguments and arrays](#arguments-and-arrays), and
  [statements](#statements-and-control-flow) explain structural distinctions.
- [Functions](#functions-closures-and-parameters),
  [classes](#classes-interfaces-traits-and-enums),
  [namespaces](#namespaces-and-imports), and [attributes](#attributes)
  identify declaration boundaries.
- [Constant expressions](#constant-expressions),
  [contextual constraints](#contextual-syntax-constraints), and
  [parser/compiler boundaries](#parsercompiler-boundary-rules) specify validation.
- [Deprecated and removed forms](#deprecated-but-accepted-syntax), the generated
  [production index](#production-index), [canonical EBNF](#canonical-ebnf), and
  [conformance evidence](#conformance-evidence) provide lookup and traceability.

### Terminology

| Term | Meaning in this specification |
|---|---|
| Source / byte stream | Input bytes under the source profile below. |
| Token | A scanner-classified unit; token category and source spelling are distinct. |
| Terminal | A quoted EBNF symbol matched through the token/source abstraction. |
| Primitive | An external lexical byte rule with explicit state and delimiter constraints. |
| Identifier / name | An identifier has a position-specific token category; a name may be qualified or namespace-relative. |
| Expression | A structural expression; acceptance in an initializer or write position needs its contextual checks. |
| `variable` / `variable-expression` | Respectively lexical `T_VARIABLE` and Zend's syntactic `variable` category. |
| Callable / dereferenceable | Syntactic categories for calls/access; neither guarantees runtime callability or a valid write target. |
| Structural / contextual | EBNF acceptance / additional mandatory parser-action or compiler constraints. |
| Parser action | Validation executed during a Bison reduction, before discarded-branch folding. |
| Compiler validation | Checks after parsing, in the relevant compilation context. |
| Folding | Zend's specified compile-time AST traversal and simplification, not arbitrary user-code evaluation. |
| Visited AST | A node reached by the folding traversal; folding-time errors still apply. |
| Discarded branch / AST | A branch removed by folding; it still had to satisfy lexical validity and parser actions. |
| Surviving AST | The tree remaining for constant-expression and subsequent compiler validation. |

## Source and lexical model

The input is a byte stream with `zend.multibyte=0`. PHP does not require Unicode
normalization or valid UTF-8 in identifiers. A consumer using Zend's multibyte
conversion must perform that configured conversion before this byte model.

### Opening and closing tags

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

### PHP/HTML transitions, terminators and EOF

The syntactic root consumes **one token stream**, not independently complete
PHP regions. Inline HTML emits a nonempty statement token (`T_INLINE_HTML`).
Thus `<?php if ($x): ?>text<?php endif; ?>` is one conditional statement.
A closing tag supplies a semicolon wherever the parser requires that token;
it does not supply missing expressions or braces. EOF supplies no semicolon:
`<?= 1` is invalid, while `<?= 1 ?>` and `<?= 1;` are valid.
Once an opening tag is recognized, malformed PHP cannot be reclassified as HTML.
`__halt_compiler();` ends parsing; subsequent bytes are uninterpreted payload.
Its placement is subject to the outermost-scope contextual restriction.

### Whitespace and comments

Whitespace is one or more bytes from space, HT, LF, CR. FF and VT are not PHP
whitespace. `/*` ends at the **first** `*/`; comments do not nest. `/**` followed
by scanner whitespace starts a documentation comment; other `/**` forms remain
ordinary block comments. In normal scripting state, `#[` starts an attribute,
never a `#` comment.

### State-sensitive comment and keyword lookahead

While looking for a property after `->` or `?->`, `#` still begins a line
comment, including `#[`.
Zend's composite yield-from lookahead also has its own comment boundaries:
`yield // ?>` followed by a newline and `from` is one composite token when the
complete lookahead matches. That embedded closing tag does not leave PHP mode.
The lookahead comment macros exclude NUL, although ordinary comments may contain
NUL. These distinctions apply inside braced interpolation as well.

`enum` is a keyword only before scanner whitespace/comments and a label-start
byte, except when the longer lookahead matches `extends` or `implements`.
Those two exclusions have no identifier-end requirement: `enum extendsName`
starts with an identifier token. `from` is a keyword terminal only as part of
the composite yield-from form. The token adapter must preserve those categories
when matching EBNF terminals; identifier primitives retain the original lexemes.

Source: the pinned scanner’s `ST_IN_SCRIPTING`, `ST_LOOKING_FOR_PROPERTY`,
opening-tag rules and composite `T_YIELD_FROM` recognition.

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

## Scanner state transitions

The scanner maintains a stack; a string is not terminated by a quote, brace or
heredoc label that belongs to a nested state. The following transitions are
normative, following the pinned scanner's named states:

| Current state | Recognized event | Token or parser effect | Next state / stack action | Special rule |
|---|---|---|---|---|
| SHEBANG | Initial `#!` with executable-file shebang skipping enabled | Consume line including newline | INITIAL | Otherwise replay input in INITIAL; scripting `#!` is an ordinary hash comment. |
| INITIAL | HTML or recognized enabled opening tag | Emit HTML; tag effect follows the opening-tag table | ST_IN_SCRIPTING on opening tag | Short-tag configuration applies at every re-entry, including nested interpolation. |
| ST_IN_SCRIPTING | Quote or backtick | Begin string | Corresponding string state | Nested delimiters belong to their own state. |
| ST_IN_SCRIPTING | Valid heredoc/nowdoc header | Begin labeled string | Push label; enter ST_HEREDOC / ST_NOWDOC | Header requires a newline. |
| ST_IN_SCRIPTING | `{` / `}` | Braced scripting structure | Push scripting / pop matching state | HTML braces do not pop this stack. |
| ST_IN_SCRIPTING | `?>` | Emit `;` | INITIAL | Consume one immediately following CRLF, CR or LF. |
| ST_DOUBLE_QUOTES / ST_BACKQUOTE / ST_HEREDOC | Text or escape | String content | Remain | Delimiters in nested states do not close this string. |
| Same interpolating states | `{$` | Begin braced interpolation; leave `$` for variable scanning | Push scripting | Normal PHP scanning resumes. |
| Same interpolating states | `${` | Begin deprecated interpolation form | Push ST_LOOKING_FOR_VARNAME | Varnames use the following lookahead. |
| Same interpolating states | Simple variable immediately followed by `[` | Begin simple offset | ST_VAR_OFFSET | A second offset is text unless braced interpolation begins it. |
| Same interpolating states | Simple variable followed by `->` / `?->` | Begin simple property lookup | ST_LOOKING_FOR_PROPERTY | An identifier-start byte must immediately follow the operator. |
| ST_LOOKING_FOR_VARNAME | Label immediately followed by `[` or `}` | Emit T_STRING_VARNAME, including keyword spellings | Replace with scripting | Distinguishes `${class[0]}` from `${name + 1}`. |
| ST_LOOKING_FOR_VARNAME | Other input | Replay next byte | Scripting | No varname token from the failed lookahead. |
| ST_LOOKING_FOR_PROPERTY | Whitespace, comment, `->` or `?->` | Retain property lookup | Remain | `#[` starts a hash comment here. |
| ST_LOOKING_FOR_PROPERTY | Label | Emit ordinary property identifier | Pop lookup state | Keyword spelling is allowed. |
| ST_LOOKING_FOR_PROPERTY | Other byte | Replay byte in enclosing state | Pop lookup state | Failed lookup does not consume the byte. |
| ST_VAR_OFFSET | Label, variable, or T_NUM_STRING; closing `]` | Simple offset | `]` restores string state | Only T_NUM_STRING may have a leading `-`; quotes, comments, whitespace and floating-point spellings are excluded. |
| ST_HEREDOC / ST_NOWDOC | Active closing label at line boundary | End labeled string | ST_END_HEREDOC pops label and restores scripting | Apply [closing-label and indentation rules](#heredoc-and-nowdoc); nested labels cannot close the outer string. ST_NOWDOC does not interpolate or decode escapes. |

Comments have meaning in scripting and property lookup; their markers are text
in the enclosing string states. Braced interpolation can itself contain a
closure that exits PHP with `?>` and re-enters it: intervening HTML braces do
not close the interpolation. Nested strings use the same rules recursively.

### Interpolated offsets

T_NUM_STRING spelling is decimal LNUM (including leading zeroes and separators),
HNUM, BNUM or ONUM. Plain decimal values within the signed-long range may carry
an integer token value; other spellings keep their source text as the offset
string. A scanner must not validate `08` as an octal PHP numeral in this state.
The parser's leading minus is separate; it is not allowed before a label or variable.

## Names and tokens

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

### Composite yield-from and ampersand categories

`yield` plus scanner whitespace/comments plus `from` with an identifier boundary
is the case-insensitive T_YIELD_FROM token. This EBNF spells it as two terminals;
the adapter must preserve that lexical decision. `&` lookahead selects Zend's
two ampersand token categories across whitespace/comments; their identical
spelling does not remove reference/type-position restrictions.

## Literals and strings

### Numeric literals

Numbers follow scanner `LNUM`, `DNUM`, `EXPONENT_DNUM`, `HNUM`, `BNUM`, `ONUM`.
Separators occur only singly between digits of the relevant base. Legacy octal
includes `0_7`. Prefixes accept upper/lower case. Integer overflow changes the
Zend numeric token/value category, not whether that numeral is valid source.
The repository retains a spelling-based integer category for overflowed forms.

### String escapes

`escape-sequence` consumes a backslash and its following byte. Single quotes
decode only `\\` and `\'`; other escapes preserve their backslash. Interpolating
strings decode `\n`, `\r`, `\t`, `\v`, `\e`, `\f`, `\\`, `\$`, the applicable
escaped quote, up to three octal digits, one or two hexadecimal digits after
`\x`, and `\u{HEX}`. Unicode escapes require at least one hexadecimal digit,
a closing brace, and a value no greater than 0x10FFFF. Unknown escapes remain
text; they are not all syntax errors. Binary `b`/`B` prefixes are accepted.

### Interpolation

Complex `{$foo}` interpolation consumes `{` followed by the existing variable
syntax; the dollar sign is not duplicated. Simple interpolated offsets have
scanner-specific identifier, numeric-string, or variable forms, not arbitrary
quoted expressions. Braced interpolation re-enters PHP scanning. `${...}`
forms remain accepted but deprecated. Backticks are expressions, not constant
string literals.

### Heredoc and nowdoc

Heredoc/nowdoc require a header newline. Spaces/tabs may follow `<<<`; no
trailing header whitespace follows the label or its quote. A heredoc label may
be unquoted or double quoted; a nowdoc label is single quoted. The closing label
must equal the opening label byte for byte, start at a line boundary after
optional indentation, and be followed by a non-identifier source byte: a closing
label ending at exact file EOF is not recognized. The following byte need not
be a semicolon or newline.

All nonblank lines of text in the active heredoc/nowdoc state must have at least
the closing indentation. Lines inside nested scripting or nested strings are
not outer heredoc text and do not inherit its indentation requirement.

Tabs and spaces must not be mixed in the indentation being stripped. Blank
lines may have less indentation. The scanner enforces label equality and
indentation; these are lexical constraints beyond ordinary context-free EBNF.
An empty body is valid.

## Types

The EBNF distinguishes simple, nullable, union and intersection types,
including parenthesized intersections in unions. The `*-without-static`
family follows the pinned parser's `type_expr_without_static`: it separates
type syntax from the `static` property modifier to avoid that parser conflict.
It does not replace return-type or class-scope validation.

Builtin type literal/name helper overlap does not change the interpreted type.
The [type constraints](#type-constraints) specify stand-alone, return-only,
scope, duplicate/redundant and property/class-constant restrictions.

## Expressions and precedence

Bison declares precedence from lowest to highest:

| Level | Operators/forms | Binding |
|---|---|---|
| 1–3 | throw; arrow body; include/include_once/require/require_once | Prefix/body precedence |
| 4–6 | or; xor; and | Left |
| 7–10 | print; yield; yield key `=>`; yield from | Prefix precedence |
| 11 | Assignment and compound assignments | Nested right operands |
| 12 | `? :` | Bison left; compile-time restrictions below |
| 13 | `??` | Right |
| 14–18 | `\|\|`; `&&`; bitwise `\|`; `^`; `&` | Left |
| 19 | `== != <> === !== <=>` | Non-associative |
| 20 | `< <= > >=` | Non-associative |
| 21–26 | `\|>`; `.`; shifts; `+ -`; `* / %`; `!` | Binary left; `!` prefix |
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

Assignment has a variable/list left operand in Bison, rather than an arbitrary
expression left operand. A pending higher-precedence operator can therefore
precede that assignment: `$a + $b = $c` groups as `$a + ($b = $c)`.
`assignment-expression` and its prefix context preserve that forced shift,
including compound and reference assignments.

`instanceof` has a restricted `class_name_reference` right operand. Once that
reference is complete, a following exponentiation applies to the completed
instanceof result: `$a instanceof $b ** $c` groups as
`($a instanceof $b) ** $c`. The optional power suffix in
`instanceof-expression` applies to the accumulated result; its prefix context
also admits a pending exponentiation before a lower-precedence prefix operand.

Yield's optional `=>` needs a further distinction: `closed-yield-*` productions
exclude a trailing yield with an operand but without its own key separator.
The separator therefore binds to the nearest such yield. This preserves
`yield yield 1 => 2`, `yield 1 => yield 2`, and nested keyed yields without
duplicating their trees. Parentheses delimit a complete ordinary expression.

The table and prefix families follow the pinned parser's precedence
declarations and `expr` alternatives. Pipe (`|>`) is at the level shown;
the restriction on a surviving unparenthesized arrow right operand is
[contextual](#control-flow-constraints).

## Variables, calls and dereferencing

The EBNF preserves Zend's `simple_variable`, `new_variable`, `variable`,
`callable_variable`, `callable_expr`, `fully_dereferenceable`,
`array_object_dereferenceable`, and `new_dereferenceable` distinctions.
In this file the lexical `variable` is `T_VARIABLE`, and `variable-expression`
is Zend's syntactic `variable`. There is no universal postfix production.
`new Foo()` is directly dereferenceable; `new Foo` is not. Heredoc tokens are
scalars but not direct dereferenceable scalars. Removed curly-brace offsets
are excluded. Write-context restrictions remain contextual.

## Arguments and arrays

`ordinary-argument-list` and `first-class-callable-arguments` are disjoint.
Bare `...` is a complete callable-conversion list and cannot be mixed with
ordinary arguments. `constructor-argument-list` retains both parser forms:
Zend rejects constructor conversion during compilation, not parsing.
There is no call-time `&` argument modifier.
Clone-with permits named/unpacked lists and callable conversion. `clone($o)`
is also unary clone applied to a parenthesized expression; the special clone
argument production follows Zend's separate comma/named/unpacked alternatives.

Array construction and destructuring share some structural forms but differ
in their valid targets and empty elements. See [argument constraints](#argument-constraints),
[callable conversion](#callable-conversion), and
[writable variables and destructuring](#writable-variables-and-destructuring).
The argument distinctions follow parser `argument_list` and `clone_argument_list`.

## Statements and control flow

### Dangling else and closed statement lists

A matched statement has no exposed `if` waiting for a possible `else` or
`elseif`; an unmatched statement has such an `if`. This distinction preserves
attachment to the nearest unmatched `if` through enclosing statement bodies.

Matched/unmatched statements propagate through the final bodies of `while`,
`for`, `foreach`, and `declare`. Braces, alternative-syntax terminators, and
the terminating `while` of `do` close that propagation. Before an alternative
`else:`/`elseif:`, `closed-inner-statement-list` requires a matched final
statement or declaration; otherwise Zend shifts the keyword toward an inner
unmatched if and rejects the colon. An `endif` terminator needs no such check.

Both `else` and `elseif` attach to the nearest unmatched `if`. A semicolon, including the
token emitted by `?>`, has only the `empty-statement` derivation when no
expression precedes it. `throw $e;` is an expression statement.

### For-loop expression lists

For-loop conditions use a nonempty comma-list prefix followed by an ordinary
expression. Empty conditions are valid; leading/trailing commas are not.
`(void)` is permitted on initializer/update elements and nonfinal condition
elements, but never on the final condition element (parser `for_cond_exprs`
and `non_empty_for_exprs`).

## Functions, closures and parameters

Function declarations, closures and arrow functions have separate productions.
An arrow body uses the expression precedence described above. A closure's
nonempty `use` list is structural; capture legality and parameter restrictions
are [contextual](#parameter-and-closure-constraints).

In particular, a `never` parameter fails when its declaration survives
compilation, but may occur inside a discarded closure. Surviving static
noncapturing closures in constant expressions compile their bodies as function
code, not through the constant-expression AST whitelist.

## Classes, interfaces, traits and enums

These declarations share `class-member-list` and `class-member` structure.
The parser accepts member forms whose legality depends on the enclosing
declaration kind. Source validity additionally requires
[declaration and modifier](#declarations-and-modifiers), [property](#properties),
[hook](#hooks), [class-constant](#class-constants), [enum](#enums), and
[trait-alias](#trait-aliases) constraints.

The [declaration boundary](#parsercompiler-declaration-boundary) explains why
these forms remain structurally representable even when a surviving declaration
would be rejected.

## Namespaces and imports

Qualified names are atomic tokens; group imports have a separate separator
before `{`. The EBNF distinguishes namespace definitions, ordinary imports,
group imports and mixed group imports. Namespace-style, placement and name
conflicts require [contextual validation](#namespace-and-declare-constraints);
token spelling alone does not establish their legality.

## Attributes

Attributes begin with `#[` in normal scripting state. Their argument lists use
the ordinary parser argument-list structure; unpacking and callable conversion
are rejected during attribute compilation. Optional attributes apply to
properties, hooks, methods, class constants and enum cases as specified by their
productions; they do not apply to trait-use statements.

See [attribute arguments](#attribute-arguments) for validation before argument
folding, and [attributed declarations](#declarations-and-modifiers) for the
single-global-constant restriction.

## Constant expressions

The constant wrappers all derive `expression`. A source-level subset cannot
faithfully specify Zend's acceptance: `const X = true ? 1 : foo();` is valid,
as are discarded `new Foo(...)` and `isset(1 + 2)` branches. Their live
companions are invalid. Consequently constructor/FCC and isset target checks
are contextual, even though syntactic helper productions distinguish their
lists. The pinned parser’s `isset_variable` production takes `expr`.

### Constant-expression validation order

A validator must apply the following order from the pinned compiler’s
`zend_const_expr_to_zval`. A blanket recursive ban on source tokens does not
implement these rules:

1. **Lexical validity and parser actions.** Apply these checks first. A discarded branch must
   still parse; folding cannot repair an unmatched delimiter or invalid token.
2. **Constant-expression folding.** Run `zend_eval_const_expr`. Fold literal binary/comparison,
   unary, supported cast, array and offset operations when the corresponding
   `zend_try_ct_eval_*` helper succeeds. Resolve eligible ordinary/class/magic
   constants and class names using Zend's compile-time environment. This is
   not arbitrary evaluation of user code or resolution of every named constant.
3. **Branch traversal and discarding.** For `&&`/`||` (including word forms), visit both children for folding, then
   discard the irrelevant child when a literal left operand determines the
   result.

   For `??`, a literal non-null left operand discards the right without
   visiting it; literal null selects the right. Ternary folding visits the
   condition and only the selected arm when the condition is literal. With a
   nonliteral condition, visit both arms.

   A folding-time error in a visited
   node is not suppressed merely because later validation could discard it.
   In particular, visiting an unset-cast node immediately raises the removed-cast
   error, before visiting its operand (`zend_eval_const_expr`, CAST case).
4. **Surviving-AST whitelist.** Validate only the surviving AST. Allowed kinds are literal values, binary
   operations, greater/greater-equal, AND/OR, unary operations/plus/minus,
   casts, conditional, dimensions, arrays/elements/unpack, constants, class
   constants/names, magic constants, coalesce, enum initialization, new,
   argument lists/named arguments, property/nullsafe-property reads, closures,
   function/static calls, and callable-conversion markers. Every other surviving
   kind is invalid. Array unpacking is distinct from constructor-argument
   unpacking; the latter is forbidden.
5. **Dynamic-context flag.** Enforce `allow_dynamic` on surviving new/object-cast nodes. It is true for
   parameter defaults, global constants, and attribute arguments (including
   nested constructor arguments), and false for property defaults, class
   constants and enum case values. Inherit it into surviving children. Scalar,
   boolean and array casts remain allowed; object casts are not folded into
   literal objects by `zend_try_ct_eval_cast`. Warning-sensitive operations may
   remain ASTs for later evaluation rather than being compile-time literals.
6. **New-expression restrictions.** New expressions require a literal resolved class reference, no anonymous
   class or late-static reference, no callable conversion and no unpacked
   arguments. Names/class references may become literal through the actual
   folding traversal, as in `new ("std" . "Class")()`.
7. **Callable-conversion restrictions.** Function/static-method callable conversion requires literal string names
   at `zend_compile_const_expr_fcc`. Folding does not recursively traverse
   CALL/STATIC_CALL in `zend_eval_const_expr`; parser-time literal concatenation
   can nevertheless have produced a literal name already. Thus
   `("str" . "len")(...)` is valid, while
   `(true ? "strlen" : "foo")(...)` is not. The same distinction applies to
   computed method names.

   CLASS_CONST, NEW, properties, named arguments and
   argument lists have their own explicit traversal cases; do not infer a
   universal recursive folding rule. `static::`/`static::class` are forbidden;
   `self`/`parent` still require the appropriate class scope.
8. **Closure restrictions.** A surviving closure must be static and have no `use` captures. Compile its
   body as function code; do not apply the constant AST whitelist to that body.
   Compile parameter defaults and attributes using their own contexts. An
   ordinary nonstatic closure or arrow can occur in a discarded branch only.
9. **Remaining compiler checks.** Apply declared-type, declaration and argument-order
   checks in their actual compiler contexts. Lint does not prove callable existence, enum value
   uniqueness/type evaluation or all deferred constant resolution succeeds.

The repository structural matcher does not execute this validation algorithm.
Its acceptance of structurally valid contextual-negative cases is intentional.

## Contextual syntax constraints

These constraints are mandatory even when structural EBNF accepts a construct.
The repository's structural matcher does not implement this entire layer.
Unless identified as a parser action, the restrictions below apply in their
compiler contexts; folding may discard the enclosing AST before those checks.
The [boundary rules](#parsercompiler-boundary-rules) identify checks that run
before folding and those that depend on compilation of a surviving declaration.

### Constant-expression contexts

**Constant expressions.**

- Parse `expression`, perform the [folding procedure](#constant-expression-validation-order), then validate the surviving AST with `zend_is_allowed_in_const_expr` and `zend_compile_const_expr`.

- Surviving arbitrary calls, variables, assignments, shell execution, interpolation, match, throw, and arrow functions are forbidden.

- Static noncapturing closures and named first-class callables are permitted in 8.5.

**Parameter defaults, global constants, attribute/constructor arguments.**

- `allow_dynamic=true`: `new` with a statically determined class and object casts are allowed.

- No anonymous/dynamic class construction, `static`, unpacked constructor arguments, or constructor callable conversion.

**Property defaults, class constants, enum values.**

- `allow_dynamic=false`: surviving `new` and object casts are forbidden recursively.

- Other supported casts, static closures, and named callable conversion remain subject to the declared type and context.

**Constant calls.**

- Surviving calls must be function/static-method callable conversions.

- The function/class/method name must already be an appropriate literal AST when its compiler check runs; see [constant-expression validation order](#constant-expression-validation-order).

- Object-method conversions are forbidden.

- `static` class references are forbidden.

**Static locals.**

- Initializers are runtime `expression`, not the constant-expression subset; PHP 8.3+ behavior is retained.

### Type constraints

- `mixed`, `void`, `never` must stand alone as applicable; `?mixed`, `?null`, duplicate/redundant unions, `true|false`, and built-in/scoped-name intersection members are invalid.

- `void`/`never` are return-only.

- Properties/promoted properties/class constants cannot use callable, void, or never.

- `static` is return-only with class scope.

- `self`/`parent` require the appropriate scope.

Source: pinned compiler `zend_compile_typename_ex`.

### Parameter and closure constraints

**Parameters and closures.**

- Parameter names must be unique.

- Only the final parameter may be variadic; a variadic parameter must not have a default.

- Parameter and capture names must not use forbidden auto-globals or `$this`.

- Captures must not duplicate each other or collide with parameter names.

- Nonempty `use` is structural.

**Promotion.**

- Only a concrete constructor in a legal class/trait context; no variadic promotion, duplicate property, invalid property type, or illegal modifiers.

- PHP 8.5 permits final promotion.

- Defaults initialize parameters, not property defaults.

Source: pinned compiler `zend_compile_params`.

### Argument constraints

- A positional argument must not follow named arguments or unpacking.

- Unpacking must not follow named arguments.

- Named arguments must not be duplicated.

- Attribute argument lists forbid unpacking and bare callable conversion.

- Built-in arity/name checks may reject clone/exit forms.

Source: pinned compiler `zend_compile_attributes` and call/argument compilation.

### Declarations and modifiers

**Modifiers and declarations.**

- Reject duplicate/conflicting modifiers, abstract-final conflicts, reserved class names, redeclarations, illegal nested class declarations, and invalid anonymous-class modifiers.

- Interfaces cannot use traits.

- Method bodies/visibility must match abstract/interface/concrete context.

**Attributed constants.**

- Parser structure permits a **global** constant list; compilation requires only one constant per attributed declaration.

- Class constants may share attributes in a multiple-constant declaration.

### Properties

**Properties.**

- Readonly properties need a type and cannot be static or have ordinary defaults.

- Only hooked properties may be abstract.

- Visibility/set visibility combinations and final/private combinations must be valid.

**Interface properties.**

- Public or historical `var` hooked properties only; no property default; no final, protected/private, or explicitly abstract property.

- Hooks have no implementation body.

Source: pinned compiler `zend_compile_prop_decl`.

### Hooks

- A compiled hooked property must have one or two distinct hook kinds, without duplicate get/set hooks.

- Hooked properties must not be static or readonly.

- Only final is an explicit hook modifier.

- A get hook must not have a parameter list.

- A supplied set list must have exactly one non-reference, nonvariadic parameter with a compatible type and no default.

- The parser accepts a reference-return marker on either hook; see [property hooks and promotion](#property-hooks-and-promotion).

- Concrete hooks need bodies; interface hooks and bodyless hooks on abstract properties are abstract.

- Abstract properties may mix concrete and abstract hooks, but must satisfy abstract-property validation.

- A final hook cannot be private or abstract.

Source: pinned compiler `zend_compile_property_hooks`.

### Class constants

- One type precedes the entire list.

- Enforce type restrictions/value compatibility, modifier legality, and final/private restrictions.

Source: pinned compiler `zend_compile_class_const_decl`.

### Enums

- Only int/string backing types.

- Backed cases require constant values; unbacked cases forbid values.

- Case names/values must satisfy uniqueness and type rules; some value checks are deferred beyond lint.

- No properties; cases are forbidden outside enums; trait composition and forbidden magic/member declarations require further checks.

Source: pinned compiler `zend_compile_enum_case`.

### Callable conversion

- Reject surviving `new Foo(...)` and anonymous-class constructor conversion (`zend_compile_new`, `zend_compile_const_expr_new`).

- Reject conversion on a nullsafe call or the same nullsafe short-circuit chain (`zend_compile_call_common`).

- Ordinary function, object-method and static-method conversions remain valid.

- Clone conversion follows its separate parser production.

### isset and unset

- `isset_variables` is a nonempty comma-separated list with optional trailing comma; pinned `isset_variable` is **expr**, not variable.

- At compilation, require `zend_is_variable`: VAR, DIM, PROP, NULLSAFE_PROP, STATIC_PROP.

- Direct calls and arithmetic fail, while `foo()[0]`, `foo()->p`, parenthesized variables, and nullsafe property reads may qualify.

- Reject empty read offsets and invalid read targets later in `zend_compile_isset_or_empty`.

### Writable variables and destructuring

- Assignment, reference binding, increment, unset, isset, destructuring, and foreach need appropriate read/write targets.

- Calls may reduce as Zend's syntactic `variable` (`variable-expression` here), but must not be unset.

- Nullsafe access must not be written.

- Foreach keys cannot be references or destructuring lists.

- Empty array elements are only valid in destructuring.

- A destructuring tree must not mix `[]` and `list()` forms (`zend_verify_list_assign_target`).

### Trait aliases

- At most one method-target alias modifier, or an alias name alone; a compiled alias permits only public/protected/private/final.

- `zend_compile_trait_alias` rejects static and abstract; readonly is rejected by the parser's modifier-target conversion.

- Modifiers cannot be combined.

### Control-flow constraints

- `break` and `continue` require a legal enclosing construct and a positive literal level.

- `goto` targets and scope crossings must be legal.

- `yield` requires function scope.

- Generator return types and return statements must be compatible.

- Match/switch permit only one default.

- A compiled try requires at least one catch or finally.

- A surviving pipe cannot take an unparenthesized arrow function as its right operand.

Source: pinned compiler `zend_compile_foreach`, `zend_compile_conditional` and `zend_compile_pipe`.

### Namespace and declare constraints

- Directives require their specific literal values and placement; strict_types is 0/1, first statement, and not block form.

- Namespace styles cannot mix and namespace/import placement/conflicts must be legal.

Source: pinned compiler `zend_compile_declare` and namespace compilation.

## Parser/compiler boundary rules

Validation phase is part of the source-validity contract. The location of a
helper in `zend_compile.c` does not establish when it runs.

### Structural parse acceptance

The EBNF describes reductions over the token/source abstraction. It permits
some forms that parser actions or later compilation reject. A structural match
therefore must not be reported as a complete source-validity verdict.

### Parser-action validation

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

### Compiler validation

After parsing, declaration, type, argument and read/write checks execute in
their compiler contexts. The following declaration boundaries illustrate
constraints that structural parsing must not enforce prematurely.

### Constant-folding/discard behavior

Lexical errors and parser-action failures cannot be erased by folding.
Later restrictions may be avoided if the relevant AST is discarded before
compilation reaches it. A folding-time error in a visited node still fails.
The [constant-expression algorithm](#constant-expression-validation-order)
specifies traversal and surviving-AST validation; ordinary expression
compilation has [different short-circuit behavior](#ordinary-compilation-versus-constant-folding).

### Parser/compiler declaration boundary

All class, interface, trait, enum, and anonymous-class bodies use the same
`class-member-list` and `class-member` productions directly, corresponding to
`class_statement_list` and `class_statement`.
Optional attributes apply to ordinary
and hooked properties, methods, class constants, and enum cases; they do not
apply to trait-use statements. Declaration-kind legality is checked only when
the declaration is compiled. In particular, enum properties, non-enum cases,
interface trait use and ordinary interface properties must remain reducible.

### Enum backing types and class-name lists

`enum-backing-type` uses the full `type` expression (parser `enum_backing_type`), including
nullable, union, intersection and static forms. `zend_compile_enum_backing_type`
then requires exactly int/string. `name-list` and `catch-type-list` consume
`class-name`, including static, as do Bison `class_name_list` and
`catch_name_list`; class-name scope/target checks remain contextual.

### Property hooks and promotion

Hook syntax mirrors parser `property_hook_list`, `property_hook` and
`property_hook_body`: an empty list is structurally possible;
each hook has attributes, optional target-valid modifiers, optional `&`, a
generic identifier, an optional complete parameter list, and either `;`, a
compound body, or `=> expression ;`. `zend_compile_property_hooks`
enforces the [hook constraints](#hooks) when compiled. Explicit get parentheses, even `get()`,
are invalid when compiled; a supplied set list must have exactly one parameter.
Empty/unknown/duplicate hooks and invalid parameter/body combinations can be
discarded along with their enclosing static closure. Parameter hook lists use
the same grammar. A hook list itself triggers promotion, even without explicit
visibility; compilation requires a concrete constructor in an allowed context.

The pinned compiler does **not** reject the reference-return marker on a set
hook. PHP 8.5.10 accepts and executes `class C { public int $x { &set {} } }`
with a void-reference-return deprecation (`zend_compile_params`).
It does not permit a reference **parameter** on set. Declaration/inheritance
checks outside these pinned files must not be inferred from lint alone.

### Attribute arguments

Attributes consume ordinary `argument-list` (parser `attribute_decl`).
`zend_compile_attributes` rejects unpacking and a callable-conversion list
before validating surviving argument expressions; those are not structural
argument-list exclusions. Folding an argument cannot erase an unpack marker
or invalid argument order on a surviving attribute declaration. Discarding the
entire enclosing closure can avoid compiling that attribute declaration.

## Deprecated but accepted syntax

| Forms | Status and validation boundary |
|---|---|
| `(integer)`, `(boolean)`, `(double)`, `(binary)` | Accepted with deprecation. |
| `${...}`, backticks | Accepted with applicable interpolation/shell-expression deprecations. |
| `(real)` | Removed; scanner parser-mode error before folding. |
| `(unset)` | Removed cast, still structurally represented; rejected when compilation or folding reaches it. |

PHP 8.5 accepts `(integer)`, `(boolean)`, `(double)`, and `(binary)` with
deprecations; casts are case insensitive and allow spaces/tabs inside their
parentheses, not comments/newlines. `(real)` and `(unset)` remain removed;
their different validation phases are specified below. `${...}` interpolation and backticks have applicable deprecations;
deprecation is not syntax rejection. Other deprecations unrelated to grammar
do not remove accepted forms.

### Removed forms and validation phase

The removed forms have different implementation boundaries: `(real)` raises a
scanner parser-mode error, while Zend still emits `T_UNSET_CAST` and rejects
surviving unset casts during compilation. `cast-operator` therefore recognizes
`(unset)` structurally, with ordinary cast precedence and scanner space/tab
rules. This is a surviving parser representation, not a restored language cast.
`zend_compile_cast` rejects it when reached; `zend_eval_const_expr` rejects it
as soon as folding visits it, even if its parent could subsequently discard it.

### Ordinary compilation versus constant folding

Ordinary expression compilation and constant-initializer folding use different
traversals. `zend_compile_short_circuiting` skips a determined right operand;
`zend_compile_conditional` compiles both arms and `zend_compile_coalesce`
compiles the default. Constant initializers instead follow the procedure above.
The exact PHP 8.5.10 outcomes are:

| Expression | Ordinary expression statement | Parenthesized global constant initializer |
|---|---|---|
| `(unset) 1` | Reject | Reject |
| `false && (unset) 1` / `false and (unset) 1` | Accept | Reject |
| `true \|\| (unset) 1` / `true or (unset) 1` | Accept | Reject |
| `null ?? (unset) 1` | Reject | Reject |
| `1 ?? (unset) 1` | Reject | Accept |
| `true ? 1 : (unset) 1` | Reject | Accept |
| `false ? (unset) 1 : 1` | Reject | Accept |
| `true xor (unset) 1` | Reject | Reject |

The [folding evidence](../../docs/8.5/remediation-folding-evidence.json) records
separate fixtures. Substituting `(real)` rejects even in discarded branches,
because its scanner parser-mode error precedes all folding.

## Known abstractions and conformance limits

The repository implementation remains under audit. The rules in this
specification and the evidence supporting the implementation have distinct scope:

- **Scanner abstraction.** The lexical contract specifies scanner states,
  nested interpolation, comments and heredoc labels. The repository token
  abstraction is not Zend's exact token stream. Complete scanner/parser source
  acceptance equivalence has not been proven. Remaining uncertainty includes
  exhaustive combinations and the documented abstractions.
- **Lexical primitives.** External primitives require a stateful scanner;
  character-only expansion is not a PHP source validator. Numeric overflow
  token/value categorization is deliberately abstracted while retaining numeral
  spelling. Standalone rule fragments are tested with a trailing newline boundary;
  complete files receive no such boundary, including for heredoc closing labels.
- **Binding.** Finite pairwise/nested operator and statement templates provide
  differential evidence for acceptance, derivation uniqueness, operand spans,
  left folds and statement binding. They are not a formal proof for arbitrary
  nesting depth.
- **Contextual coverage.** Declaration-family matrices and fatal-site inventories
  do not establish equivalence for every parser production, diagnostic path,
  deferred validator or compiler path. Contextual constraints, especially folding
  and deferred name/type checks, remain normative without a complete
  repository-owned validator. The modifier API validates only an identified list,
  not whole-source validity.
- **Executable comparison.** PHP 8.5.10 lint is an independent compilation oracle,
  not the exact pinned build and not a proof of later evaluation. Callable
  existence, deferred constant resolution, enum value checks, and declaration or
  inheritance checks outside the pinned files must not be inferred from lint alone.

The [evidence section](#conformance-evidence) records the bounded audits and
historical corrections without extending these conformance claims.

## Syntactic productions

The canonical EBNF below is generated verbatim from `php.ebnf` and checked
for exact parity. The generated semantic index immediately precedes it and
covers every production in canonical section order.

Binary precedence and matching prefix-context families are maintained by
`tools/8.5/generate-expressions.php`. All generated output remains explicit,
standalone EBNF. Two marked generated regions keep the closed-yield family
together. `tools/8.5/sync-documentation.php --check` verifies the index and
grammar block. The source, binding and contextual rules above also apply.

<!-- BEGIN GENERATED PRODUCTION INDEX -->
## Production index

Grouped in canonical section order. Each name can be searched in the EBNF block below;
the same numbered section headings appear in `php.ebnf`.

### Source/root grammar

`source-file`, `inline-html`, `top-statement-list`, `top-statement`, `attributed-top-declaration`.

### Lexical and token-level grammar

`whitespace`, `comment`, `line-comment`, `block-comment`, `doc-comment`.

### Names and identifiers

`identifier`, `identifier-start`, `identifier-part`, `identifier-start-character`, `label`,
`semi-reserved-identifier`, `name-identifier`, `namespace-declaration-name`, `qualified-name`,
`fully-qualified-name`, `namespace-relative-name`, `name`, `class-name`, `name-list`.

### Literals and strings

`integer-literal`, `decimal-integer-literal`, `binary-integer-literal`, `octal-integer-literal`,
`explicit-octal-integer-literal`, `hexadecimal-integer-literal`, `floating-literal`,
`exponent-part`, `decimal-digits`, `numeric-separator`, `string-literal`, `single-quoted-string`,
`double-quoted-string`, `heredoc-string`, `nowdoc-string`, `encapsulated-string-part`,
`encapsulated-variable`, `encapsulated-offset`, `numeric-string`, `literal`, `magic-constant`,
`backtick-string`, `backtick-string-part-list`.

### Types

`type`, `optional-type-without-static`, `type-without-static`, `simple-type`,
`simple-type-without-static`, `nullable-type`, `nullable-type-without-static`, `union-type`,
`union-type-element`, `union-type-without-static`, `union-type-without-static-element`,
`intersection-type`, `intersection-type-without-static`, `parenthesized-intersection-type`,
`parenthesized-intersection-type-without-static`, `return-type`.

### Expressions

`expression`, `primary-expression`, `postfix-expression`, `assignment-expression`,
`assignment-operator`, `conditional-expression`, `instanceof-expression`, `unary-expression`,
`cast-expression`, `boolean-not-expression`, `clone-expression`, `throw-expression`,
`arrow-expression`, `include-expression`, `print-expression`, `yield-expression`,
`yield-from-expression`, `match-expression`, `match-arm-list`, `match-arm`,
`match-arm-condition-list`.

### Variables, dereferencing and calls

`variable`, `variable-variable`, `variable-like`, `variable-expression`, `callable-variable`,
`new-variable`, `static-member`, `fully-dereferenceable-expression`, `array-object-dereferenceable`,
`callable-expression`, `new-dereferenceable`, `dereferenceable-scalar`, `property-name`,
`member-name`, `object-operator`, `nullsafe-object-operator`, `constant`, `class-constant`,
`class-name-reference`, `function-call`, `isset-variable-list`, `isset-variable`.

### Arguments and arrays

`argument-list`, `ordinary-argument-list`, `first-class-callable-arguments`,
`constructor-argument-list`, `clone-argument-list`, `argument`, `argument-no-expression`,
`expression-list`, `array-creation-expression`, `array-pair-list`, `array-pair`, `list-expression`,
`long-list-expression`.

### Statements

`statement`, `compound-statement`, `inner-statement-list`, `inner-statement`, `inner-declaration`,
`expression-statement`, `echo-statement`, `statement-terminator`, `global-statement`,
`global-variable-list`, `global-variable`, `static-statement`, `static-variable-list`,
`static-variable`, `unset-statement`, `unset-variable-list`, `unset-variable`, `return-statement`,
`break-statement`, `continue-statement`, `goto-statement`, `label-statement`, `empty-statement`,
`if-statement`, `while-statement`, `do-statement`, `for-statement`, `for-expression-list`,
`nonempty-for-expression-list`, `for-expression`, `for-condition-expression-list`,
`foreach-statement`, `foreach-target`, `foreach-key`, `foreach-value`, `foreach-variable`,
`switch-statement`, `switch-case-list`, `switch-case`, `declare-statement`,
`declare-directive-list`, `declare-directive`, `try-statement`, `catch-list`, `catch-clause`,
`catch-type-list`, `finally-clause`, `void-cast-statement`.

### Functions, closures and parameters

`function-declaration`, `closure-expression`, `arrow-function`, `arrow-function-header`,
`lexical-variable-list`, `lexical-variable`, `parameter-list`, `parameter`, `parameter-modifiers`,
`parameter-modifier`, `constant-declaration`, `constant-list`, `constant-element`.

### Classes, interfaces, traits and enums

`class-declaration`, `anonymous-class`, `class-modifiers`, `class-modifier`, `extends-clause`,
`implements-clause`, `class-member-list`, `class-member`, `property-declaration`,
`property-modifier-list`, `property-modifier`, `property-visibility-modifier`,
`set-visibility-modifier`, `property-list`, `property-element`, `hooked-property`,
`property-hook-block`, `property-hook-list`, `property-hook`, `property-hook-body`,
`property-hook-modifiers`, `property-hook-modifier`, `method-declaration`, `method-modifiers`,
`method-modifier`, `method-body`, `class-constant-declaration`, `class-constant-modifiers`,
`class-constant-modifier`, `class-constant-list`, `class-constant-element`, `interface-declaration`,
`interface-extends-clause`, `trait-declaration`, `trait-use-declaration`, `trait-adaptation-block`,
`trait-adaptation`, `trait-precedence`, `trait-alias`, `trait-method-reference`,
`trait-alias-modifier`, `enum-declaration`, `enum-backing-type`, `enum-case`.

### Namespaces and imports

`namespace-definition`, `namespace-use-declaration`, `use-type`, `use-declaration-list`,
`use-declaration`, `legacy-namespace-name`, `group-use-declaration`, `mixed-group-use-declaration`,
`unprefixed-use-declaration-list`, `unprefixed-use-declaration`, `inline-use-declaration-list`,
`inline-use-declaration`.

### Attributes

`attribute-groups`, `attribute-group`, `attribute-list`, `attribute`.

### Source termination / __halt_compiler

`halt-compiler-statement`, `halt-compiler-data`.

### Lexical primitive adapters

`binary-digit`, `octal-digit`, `decimal-digit`, `decimal-digit-nonzero`, `hexadecimal-digit`,
`exponent-marker`, `ascii-letter`, `inline-html-text`, `line-comment-text`, `block-comment-text`,
`doc-comment-text`, `single-quoted-string-content`, `string-text`, `heredoc-label`, `heredoc-body`,
`nowdoc-body`, `source-character`, `inline-html-character`, `line-comment-character`,
`block-comment-character`, `single-quoted-string-character`, `string-character`, `nowdoc-character`,
`escape-sequence`, `whitespace-character`, `non-ascii-byte`, `newline`.

### Reserved identifiers / token categories

`reserved-non-modifiers`.

### Parser-derived expression helper machinery

`arrow-prefix-context`, `include-prefix-context`, `print-prefix-context`, `yield-prefix-context`,
`yield-from-prefix-context`, `assignment-prefix`, `assignment-prefix-context`,
`conditional-prefix-context`, `boolean-not-prefix-context`, `instanceof-prefix-context`,
`unary-prefix-context`, `clone-prefix-context`, `include-operator`, `unary-operator`,
`cast-operator`.

### Constant-expression attachment aliases

`constant-expression`, `parameter-default`, `property-default`, `class-constant-initializer`,
`global-constant-initializer`, `enum-case-initializer`.

### Matched/unmatched statement machinery

`matched-statement`, `simple-statement`, `unmatched-statement`, `matched-if-statement`,
`unmatched-if-statement`, `alternative-if-statement`, `closed-inner-statement-list`,
`matched-while-statement`, `unmatched-while-statement`, `matched-for-statement`,
`unmatched-for-statement`, `matched-foreach-statement`, `unmatched-foreach-statement`,
`matched-declare-statement`, `unmatched-declare-statement`, `alt-elseif-list`, `alt-else-clause`.

### Generated precedence/prefix families

`logical-or-expression`, `logical-or-prefix-context`, `logical-xor-expression`,
`logical-xor-prefix-context`, `logical-and-expression`, `logical-and-prefix-context`,
`coalesce-expression`, `coalesce-prefix-context`, `boolean-or-expression`,
`boolean-or-prefix-context`, `boolean-and-expression`, `boolean-and-prefix-context`,
`bitwise-or-expression`, `bitwise-or-prefix-context`, `bitwise-xor-expression`,
`bitwise-xor-prefix-context`, `bitwise-and-expression`, `bitwise-and-prefix-context`,
`equality-expression`, `equality-prefix-context`, `relational-expression`,
`relational-prefix-context`, `pipe-expression`, `pipe-prefix-context`, `concatenation-expression`,
`concatenation-prefix-context`, `shift-expression`, `shift-prefix-context`, `additive-expression`,
`additive-prefix-context`, `multiplicative-expression`, `multiplicative-prefix-context`,
`power-expression`, `power-prefix-context`.

### Yield-key / closed-yield machinery

`yield-key-expression`, `yield-key-prefix-context`, `closed-yield-throw-expression`,
`closed-yield-arrow-expression`, `closed-yield-arrow-prefix-context`,
`closed-yield-include-expression`, `closed-yield-include-prefix-context`,
`closed-yield-logical-or-expression`, `closed-yield-logical-or-prefix-context`,
`closed-yield-logical-xor-expression`, `closed-yield-logical-xor-prefix-context`,
`closed-yield-logical-and-expression`, `closed-yield-logical-and-prefix-context`,
`closed-yield-print-expression`, `closed-yield-print-prefix-context`,
`closed-yield-yield-expression`, `closed-yield-yield-prefix-context`, `yield-key`.

<!-- END GENERATED PRODUCTION INDEX -->

## Canonical EBNF

<!-- BEGIN GENERATED EBNF -->
```ebnf
(* Standalone PHP 8.5 syntactic EBNF over the source-token stream.
   Binary expression/prefix families: tools/8.5/generate-expressions.php.
   Apply the lexical-state contract and contextual constraints in php.md. *)

(* SECTION 01 source: Source/root grammar *)

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

(* SECTION 02 lexical: Lexical and token-level grammar *)

(* Whitespace and comments may occur between tokens under the scanner-state
   contract in php.md. Keywords are case-insensitive; token categories are
   resolved before syntactic matching, not by these character rules alone. *)

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

(* SECTION 03 names: Names and identifiers *)

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

name-list =
    class-name , { "," , class-name } ;

(* SECTION 04 literals: Literals and strings *)

(* Interpolation crosses back into expression/variable syntax. Delimiter and
   heredoc-label recognition still obey the scanner-state contract in php.md. *)

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
    [ "b" | "B" ] , "\"" , { encapsulated-string-part } , "\"" ;

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

backtick-string =
    "`" , backtick-string-part-list , "`" ;

backtick-string-part-list =
    { encapsulated-string-part } ;

(* SECTION 05 types: Types *)

(* Zend duplicates type syntax without static to avoid conflicts with the
   static property modifier. The without-static hierarchy is a syntactic
   distinction, not merely a contextual validation check. *)

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

(* SECTION 06 expressions: Expressions *)

expression =
    throw-expression ;

primary-expression =
    literal | array-creation-expression | constant | class-constant | magic-constant
    | "(" , expression , ")" | new-dereferenceable | "new" , class-name-reference
    | backtick-string
    | "isset" , "(" , isset-variable-list , [ "," ] , ")"
    | "empty" , "(" , expression , ")" | "eval" , "(" , expression , ")"
    | "exit" , [ argument-list ] | "die" , [ argument-list ] ;

postfix-expression =
    variable-expression , [ "++" | "--" ] | primary-expression
    | ( "++" | "--" ) , variable-expression | closure-expression | match-expression ;

assignment-expression =
    conditional-expression
    | [ conditional-prefix-context ] , assignment-prefix , assignment-expression
    | [ conditional-prefix-context ] , variable-expression , "=" , "&" , variable-expression ;

assignment-operator =
      "=" | "+=" | "-=" | "*=" | "/=" | ".=" | "%="
    | "&=" | "|=" | "^=" | "<<=" | ">>=" | "**=" | "??=" ;

conditional-expression =
    coalesce-expression , { "?" , [ expression ] , ":" , coalesce-expression } ;

instanceof-expression =
    unary-expression , { "instanceof" , class-name-reference , [ "**" , unary-expression ] } ;

unary-expression =
    power-expression
    | [ power-prefix-context ] , ( ( "+" | "-" | "~" | "@" ) , unary-expression | cast-expression ) ;

cast-expression =
    cast-operator , unary-expression ;

boolean-not-expression =
    instanceof-expression
    | [ instanceof-prefix-context ] , "!" , boolean-not-expression ;

clone-expression =
    "clone" , clone-expression | "clone" , clone-argument-list | postfix-expression ;

throw-expression =
    arrow-expression
    | [ arrow-prefix-context ] , "throw" , throw-expression ;

arrow-expression =
    include-expression | [ include-prefix-context ] , arrow-function ;

include-expression =
    logical-or-expression
    | [ logical-or-prefix-context ] , include-operator , include-expression ;

print-expression =
    yield-expression
    | [ yield-prefix-context ] , "print" , print-expression ;

yield-expression =
    yield-key-expression
    | [ yield-key-prefix-context ] , "yield" , [ yield-expression ] ;

yield-from-expression =
    assignment-expression
    | [ assignment-prefix-context ] , "yield" , "from" , yield-from-expression ;

match-expression =
    "match" , "(" , expression , ")" , "{" , [ match-arm-list ] , "}" ;

match-arm-list =
    match-arm , { "," , match-arm } , [ "," ] ;

match-arm =
      match-arm-condition-list , [ "," ] , "=>" , expression
    | "default" , [ "," ] , "=>" , expression ;

match-arm-condition-list =
    expression , { "," , expression } ;

(* SECTION 07 dereferencing: Variables, dereferencing and calls *)

(* PHP distinguishes variable, callable and dereference categories rather
   than permitting one universal postfix chain. Keep these mutually recursive
   categories distinct; their entry points allow different continuations. *)

variable =
    "$" , identifier ;

variable-variable =
      "$" , variable-like
    | "$" , "{" , expression , "}" ;

variable-like =
      variable
    | variable-variable ;

variable-expression =
    callable-variable | static-member
    | array-object-dereferenceable , ( object-operator | nullsafe-object-operator ) , property-name ;

callable-variable =
    variable-like
    | array-object-dereferenceable , "[" , [ expression ] , "]"
    | array-object-dereferenceable , ( object-operator | nullsafe-object-operator ) , property-name , argument-list
    | function-call ;

new-variable =
    variable-like
    | new-variable , "[" , [ expression ] , "]"
    | new-variable , ( object-operator | nullsafe-object-operator ) , property-name
    | ( class-name | new-variable ) , "::" , variable-like ;

static-member =
    ( class-name | fully-dereferenceable-expression ) , "::" , variable-like ;

fully-dereferenceable-expression =
    variable-expression | "(" , expression , ")" | dereferenceable-scalar
    | class-constant | new-dereferenceable ;

array-object-dereferenceable =
    fully-dereferenceable-expression | constant | magic-constant ;

callable-expression =
    callable-variable | "(" , expression , ")" | dereferenceable-scalar | new-dereferenceable ;

new-dereferenceable =
    "new" , class-name-reference , constructor-argument-list | "new" , anonymous-class ;

dereferenceable-scalar =
    array-creation-expression | single-quoted-string | double-quoted-string ;

property-name =
    identifier | "{" , expression , "}" | variable-like ;

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

isset-variable-list =
    isset-variable , { "," , isset-variable } ;

isset-variable =
    expression ;

(* SECTION 08 arguments: Arguments and arrays *)

(* Callable conversion (...) is structurally separate from ordinary arguments:
   its compiler context differs from unpacking an expression. Constructor and
   clone entry points intentionally retain their own productions. *)

argument-list =
    ordinary-argument-list | first-class-callable-arguments ;

ordinary-argument-list =
    "(" , [ argument , { "," , argument } , [ "," ] ] , ")" ;

first-class-callable-arguments =
    "(" , "..." , ")" ;

constructor-argument-list =
    ordinary-argument-list | first-class-callable-arguments ;

(* A first unnamed expression needs a comma here so clone($expr) continues
   to use the unary construct rather than an ambiguous argument-list parse. *)

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

(* SECTION 09 statements: Statements *)

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

while-statement =
    matched-while-statement | unmatched-while-statement ;

do-statement =
    "do" , statement , "while" , "(" , expression , ")" , ";" ;

for-statement =
    matched-for-statement | unmatched-for-statement ;

for-expression-list =
    [ nonempty-for-expression-list ] ;

nonempty-for-expression-list =
    for-expression , { "," , for-expression } ;

for-expression =
      expression
    | "(void)" , expression ;

for-condition-expression-list =
    [ expression | nonempty-for-expression-list , "," , expression ] ;

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

void-cast-statement =
    "(void)" , expression , ";" ;

(* SECTION 10 functions: Functions, closures and parameters *)

function-declaration =
    "function" , [ "&" ] , ( identifier | "readonly" ) , "(" , parameter-list , ")" ,
    return-type , compound-statement ;

closure-expression =
    [ attribute-groups ] , [ "static" ] , "function" , [ "&" ] ,
    "(" , parameter-list , ")" , [ lexical-variable-list ] , return-type ,
    compound-statement ;

arrow-function =
    arrow-function-header , arrow-expression ;

arrow-function-header =
    [ attribute-groups ] , [ "static" ] , "fn" , [ "&" ] ,
    "(" , parameter-list , ")" , return-type , "=>" ;

lexical-variable-list =
    "use" , "(" , lexical-variable , { "," , lexical-variable } , [ "," ] , ")" ;

lexical-variable =
      variable
    | "&" , variable ;

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

constant-declaration =
    "const" , constant-list , ";" ;

constant-list =
    constant-element , { "," , constant-element } ;

constant-element =
    identifier , "=" , global-constant-initializer ;

(* SECTION 11 classes: Classes, interfaces, traits and enums *)

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

(* Class, interface, trait and enum bodies share this structural member list.
   Declaration-kind legality remains contextual/compiler validation. *)

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

(* Target-specific modifier families remain separate. This property list is
   nonempty; other modifier families can have different multiplicities. *)

property-modifier-list =
    property-modifier , { property-modifier } ;

property-modifier =
    property-visibility-modifier | set-visibility-modifier | "static" | "readonly" | "final" | "abstract" ;

property-visibility-modifier =
      "public" | "protected" | "private" ;

set-visibility-modifier =
      "public(set)" | "protected(set)" | "private(set)" ;

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

interface-declaration =
    "interface" , identifier , [ interface-extends-clause ] ,
    "{" , class-member-list , "}" ;

interface-extends-clause =
    "extends" , name-list ;

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

trait-alias-modifier =
    method-modifier ;

enum-declaration =
    "enum" , identifier , [ enum-backing-type ] , [ implements-clause ] ,
    "{" , class-member-list , "}" ;

enum-backing-type =
    ":" , type ;

enum-case =
    "case" , semi-reserved-identifier , [ "=" , enum-case-initializer ] , ";" ;

(* SECTION 12 namespaces: Namespaces and imports *)

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

(* SECTION 13 attributes: Attributes *)

attribute-groups =
    attribute-group , { attribute-group } ;

attribute-group =
    "#[" , attribute-list , [ "," ] , "]" ;

attribute-list =
    attribute , { "," , attribute } ;

attribute =
    class-name , [ argument-list ] ;

(* SECTION 14 termination: Source termination / __halt_compiler *)

halt-compiler-statement =
    "__halt_compiler" , "(" , ")" , ";" , halt-compiler-data ;

halt-compiler-data =
    { source-character } ;

(* SECTION 15 primitives: Lexical primitive adapters *)

(* Named byte-category adapters retain the lexical/source contract in php.md.
   External primitives are declared in php-grammar.json; these rules do not
   turn scanner-state boundaries into unrestricted character matching. *)

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

newline =
    "\r\n" | "\n" | "\r" ;

(* SECTION 16 reserved: Reserved identifiers / token categories *)

reserved-non-modifiers =
    "and" | "array" | "as" | "break" | "callable" | "case" | "catch" | "class" | "clone"
    | "const" | "continue" | "declare" | "default" | "do" | "echo" | "else" | "elseif" | "empty"
    | "enddeclare" | "endfor" | "endforeach" | "endif" | "endswitch" | "endwhile" | "enum"
    | "eval" | "exit" | "die" | "extends" | "finally" | "fn" | "for" | "foreach" | "function"
    | "global" | "goto" | "if" | "implements" | "include" | "include_once" | "instanceof"
    | "insteadof" | "interface" | "isset" | "list" | "match" | "namespace" | "new" | "or"
    | "print" | "require" | "require_once" | "return" | "switch" | "throw" | "trait" | "try"
    | "unset" | "use" | "var" | "while" | "xor" | "yield"
    | magic-constant ;

(* SECTION 17 prefix: Parser-derived expression helper machinery *)

(* A *-prefix-context is a nonempty expression prefix with its final operand
   missing. These helpers encode Bison shift/precedence behavior beyond ordinary
   precedence layers: $a + $b = $c; $x = print $y = 1; 2 ** -2 ** 2;
   Generated binary pairs follow in the precedence section; yield-key helpers
   remain with the closed-yield family. *)

arrow-prefix-context =
    include-prefix-context | [ include-prefix-context ] , arrow-function-header , [ arrow-prefix-context ] ;

include-prefix-context =
    logical-or-prefix-context | [ logical-or-prefix-context ] , include-operator , [ include-prefix-context ] ;

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
    conditional-prefix-context
    | [ conditional-prefix-context ] , assignment-prefix , [ assignment-prefix-context ] ;

conditional-prefix-context =
    coalesce-prefix-context
    | conditional-expression , "?" , [ expression ] , ":" , [ coalesce-prefix-context ] ;

boolean-not-prefix-context =
    instanceof-prefix-context | [ instanceof-prefix-context ] , "!" , [ boolean-not-prefix-context ] ;

instanceof-prefix-context =
    unary-prefix-context
    | instanceof-expression , "instanceof" , class-name-reference , "**" , [ unary-prefix-context ] ;

unary-prefix-context =
    power-prefix-context | [ power-prefix-context ] , unary-operator , [ unary-prefix-context ] ;

clone-prefix-context =
    "clone" , { "clone" } ;

include-operator =
    "include" | "include_once" | "require" | "require_once" ;

unary-operator =
    "+" | "-" | "~" | "@" | cast-operator ;

cast-operator =
    "(int)" | "(integer)" | "(float)" | "(double)" | "(string)"
    | "(binary)" | "(array)" | "(object)" | "(bool)" | "(boolean)" | "(unset)" ;

(* SECTION 18 initializers: Constant-expression attachment aliases *)

(* These attachment aliases intentionally share structural syntax while
   retaining distinct compiler contexts. Identical bodies do not imply
   identical constant-expression validation; see php.md. *)

constant-expression =
    expression ;

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

(* SECTION 19 matched: Matched/unmatched statement machinery *)

(* Ordinary EBNF encodes PHP/Bison dangling-else behavior with matched and
   unmatched statements. The nearest unmatched if propagates through while,
   for, foreach and declare bodies, including elseif continuations. Alternative
   syntax keeps its closed-inner-statement boundary in this same family. *)

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

alt-elseif-list =
    "elseif" , "(" , expression , ")" , ":" ,
    ( inner-statement-list
    | [ closed-inner-statement-list ] , ( alt-elseif-list | alt-else-clause ) ) ;

alt-else-clause =
    "else" , ":" , inner-statement-list ;

(* SECTION 20 precedence: Generated precedence/prefix families *)

(* Each complete expression is followed by its pending-prefix counterpart.
   Operator order and optional/repeated tails preserve precedence and binding.
   Owned by tools/8.5/generate-expressions.php; do not edit generated rules.
   Consumers use this standalone EBNF without running the generator. *)

(* BEGIN GENERATED EXPRESSION PRECEDENCE RULES *)

logical-or-expression =
    logical-xor-expression , { "or" , logical-xor-expression } ;

logical-or-prefix-context =
      logical-xor-prefix-context
    | logical-or-expression , "or" , [ logical-xor-prefix-context ] ;

logical-xor-expression =
    logical-and-expression , { "xor" , logical-and-expression } ;

logical-xor-prefix-context =
      logical-and-prefix-context
    | logical-xor-expression , "xor" , [ logical-and-prefix-context ] ;

logical-and-expression =
    print-expression , { "and" , print-expression } ;

logical-and-prefix-context =
      print-prefix-context
    | logical-and-expression , "and" , [ print-prefix-context ] ;

coalesce-expression =
    boolean-or-expression , [ "??" , coalesce-expression ] ;

coalesce-prefix-context =
      boolean-or-prefix-context
    | boolean-or-expression , "??" , { boolean-or-expression , "??" } , [ boolean-or-prefix-context ] ;

boolean-or-expression =
    boolean-and-expression , { "||" , boolean-and-expression } ;

boolean-or-prefix-context =
      boolean-and-prefix-context
    | boolean-or-expression , "||" , [ boolean-and-prefix-context ] ;

boolean-and-expression =
    bitwise-or-expression , { "&&" , bitwise-or-expression } ;

boolean-and-prefix-context =
      bitwise-or-prefix-context
    | boolean-and-expression , "&&" , [ bitwise-or-prefix-context ] ;

bitwise-or-expression =
    bitwise-xor-expression , { "|" , bitwise-xor-expression } ;

bitwise-or-prefix-context =
      bitwise-xor-prefix-context
    | bitwise-or-expression , "|" , [ bitwise-xor-prefix-context ] ;

bitwise-xor-expression =
    bitwise-and-expression , { "^" , bitwise-and-expression } ;

bitwise-xor-prefix-context =
      bitwise-and-prefix-context
    | bitwise-xor-expression , "^" , [ bitwise-and-prefix-context ] ;

bitwise-and-expression =
    equality-expression , { "&" , equality-expression } ;

bitwise-and-prefix-context =
      equality-prefix-context
    | bitwise-and-expression , "&" , [ equality-prefix-context ] ;

equality-expression =
    relational-expression , [ ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , relational-expression ] ;

equality-prefix-context =
      relational-prefix-context
    | relational-expression , ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , [ relational-prefix-context ] ;

relational-expression =
    pipe-expression , [ ( "<" | "<=" | ">" | ">=" ) , pipe-expression ] ;

relational-prefix-context =
      pipe-prefix-context
    | pipe-expression , ( "<" | "<=" | ">" | ">=" ) , [ pipe-prefix-context ] ;

pipe-expression =
    concatenation-expression , { "|>" , concatenation-expression } ;

pipe-prefix-context =
      concatenation-prefix-context
    | pipe-expression , "|>" , [ concatenation-prefix-context ] ;

concatenation-expression =
    shift-expression , { "." , shift-expression } ;

concatenation-prefix-context =
      shift-prefix-context
    | concatenation-expression , "." , [ shift-prefix-context ] ;

shift-expression =
    additive-expression , { ( "<<" | ">>" ) , additive-expression } ;

shift-prefix-context =
      additive-prefix-context
    | shift-expression , ( "<<" | ">>" ) , [ additive-prefix-context ] ;

additive-expression =
    multiplicative-expression , { ( "+" | "-" ) , multiplicative-expression } ;

additive-prefix-context =
      multiplicative-prefix-context
    | additive-expression , ( "+" | "-" ) , [ multiplicative-prefix-context ] ;

multiplicative-expression =
    boolean-not-expression , { ( "*" | "/" | "%" ) , boolean-not-expression } ;

multiplicative-prefix-context =
      boolean-not-prefix-context
    | multiplicative-expression , ( "*" | "/" | "%" ) , [ boolean-not-prefix-context ] ;

power-expression =
    clone-expression , [ "**" , power-expression ] ;

power-prefix-context =
      clone-prefix-context
    | clone-expression , "**" , { clone-expression , "**" } , [ clone-prefix-context ] ;

(* END GENERATED EXPRESSION PRECEDENCE RULES *)

(* SECTION 21 yield: Yield-key / closed-yield machinery *)

(* The closed-yield family prevents the wrong yield from capturing a following
   =>. Keep complete and pending-prefix forms together: this preserves key
   operand spans, not just accepted token sequences. The marked binary subset
   is owned by tools/8.5/generate-expressions.php; do not edit it manually. *)

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

(* BEGIN GENERATED CLOSED-YIELD RULES *)

closed-yield-logical-or-expression =
    closed-yield-logical-xor-expression , { "or" , closed-yield-logical-xor-expression } ;

closed-yield-logical-or-prefix-context =
      closed-yield-logical-xor-prefix-context
    | closed-yield-logical-or-expression , "or" , [ closed-yield-logical-xor-prefix-context ] ;

closed-yield-logical-xor-expression =
    closed-yield-logical-and-expression , { "xor" , closed-yield-logical-and-expression } ;

closed-yield-logical-xor-prefix-context =
      closed-yield-logical-and-prefix-context
    | closed-yield-logical-xor-expression , "xor" , [ closed-yield-logical-and-prefix-context ] ;

closed-yield-logical-and-expression =
    closed-yield-print-expression , { "and" , closed-yield-print-expression } ;

closed-yield-logical-and-prefix-context =
      closed-yield-print-prefix-context
    | closed-yield-logical-and-expression , "and" , [ closed-yield-print-prefix-context ] ;

(* END GENERATED CLOSED-YIELD RULES *)

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

## Conformance evidence

Evidence supports bounded claims about the repository implementation; it does
not replace any normative layer. Detailed reports preserve their original case
counts, methods, diagnostics and limitations.

| Evidence | Scope |
|---|---|
| [Negative boundaries](../../docs/8.5/negative-boundaries.md) and [Phase 4 coverage](../../docs/8.5/phase4-negative-coverage.md) | Paired structural/contextual rejection and valid repairs. |
| [Phase 5 scanner audit](../../docs/8.5/phase5-lexer-audit.md) | All 190 scanner rules mapped to dispositions and direct evidence; exhaustive combinations remain unproven. |
| [Parser/compiler remediation](../../docs/8.5/parser-compiler-remediation.md) | Live, retained and discarded declaration families and parser-action controls. |
| [Remediation audit](../../docs/8.5/remediation-audit.md) | Finite binding templates, scanner/parser products and folding corrections. |
| [Phase 6 conformance](../../docs/8.5/phase6-conformance-closure.md) | Production/action reconciliation, modifier checks, recursive tests and implementation limits. |
| [Current completeness claim](../../docs/8.5/completeness.md), [final evidence](../../docs/8.5/final-evidence.json), and [historical dispositions](../../docs/8.5/historical-dispositions.md) | Phase 7 certification, individual diagnostic dispositions, and remaining contextual, interpolation-binding and executable-provenance limits. |
| [Simplification audit](../../docs/8.5/grammar-simplification-audit.md) | Production decisions and equivalence checks; declaration legality remains contextual. |
| [Readability audit](../../docs/8.5/grammar-readability-audit.md) | Organization, unchanged production ASTs and its complete release results. |
| [Specification polish audit](../../docs/8.5/specification-polish-audit.md) | Documentation classification, terminology review and before/after validation for this edition. |

The boundary fixture matrix tests 114 live/retained/discarded families, including
113 compiler-invalid families and the accepted set-reference exception. All six
discarding operators are tested against every family. Separate parser-action
controls require rejection even inside a discarded closure. This is systematic
category coverage, not a claim that every diagnostic path or deferred validator
has an independent witness.

### Conformance history

The following records describe historical validation stages, not additional
language requirements. Their counts refer to those stages.

Grammar Completeness Phase 4 adds paired negative-boundary evidence without
changing the canonical productions below. Its 250 new structural-negative
fixtures reject during repository recognition; 22 new contextual-negative
fixtures parse structurally and reject during PHP compilation. All have nearby
valid repairs. The [boundary ledger](../../docs/8.5/negative-boundaries.md)
records source evidence and the [Phase 4 report](../../docs/8.5/phase4-negative-coverage.md)
records methodology and validation. This evidence does not replace the
standalone lexical or contextual rules in this specification. The
[parser/compiler boundary remediation](../../docs/8.5/parser-compiler-remediation.md)
resolves the previously recorded discarded-closure witnesses and expands
coverage to the broader declaration category. Full conformance remains unproven.

Grammar Completeness Phase 5 supplies a rule-by-rule scanner audit and direct
byte/token evidence in the [Phase 5 report](../../docs/8.5/phase5-lexer-audit.md).
The source contract incorporates the confirmed lookahead and EOF corrections.
The subsequent [remediation audit](../../docs/8.5/remediation-audit.md) corrects
the nullable for-condition prefix and reconciles the removed unset cast with
the parser/compiler boundary.

Grammar Completeness Phase 6 adds production/action reconciliation, systematic
binding evidence, early modifier-list validation and bounded recursive tests.
Parser actions run before discarded-branch folding; surviving declaration,
write and type checks run afterward. In particular, `never` parameters fail
when their declaration survives, but may occur inside a discarded closure.
That distinction does not change the EBNF below. Builtin type literal/name
helper overlap does not change the interpreted type. The repository's modifier
API checks only an identified list; it is not a whole-source validity verdict.
The [Phase 6 report](../../docs/8.5/phase6-conformance-closure.md) indexes evidence
and implementation limits; the lexical and contextual specification here
remains standalone. Exact malformed-input recovery and runtime execution are
outside the source-validity contract.

The declaration remediation broadened common members, enum backing types,
trait aliases, hooks, attributes, try and class-name lists. The two formerly
recorded discarded-closure witnesses pass in the ordinary valid corpus; they
represented a wider category rather than its full extent. The pinned set-hook
reference-return behavior also corrected an earlier unconditional “only get” rule.

The remediation's 11,294-case operator/statement matrix had no acceptance,
duplicate-derivation, operand-span, left-fold or statement-binding mismatch.
It exhausted its finite pairwise/nested templates and compared Zend ASTs after
forcing EBNF operand/body boundaries. It corrected assignment-prefix and
instanceof-power defects and resolved the previous targeted-only binding
blocker for that matrix, without proving arbitrary nesting equivalence.
Later coverage and counts are recorded in the linked release reports.

The scanner audit maps all 190 rules to reviewed dispositions and direct
evidence; remaining uncertainty is not silently unaudited scanner families.
A separate 482-case scanner/parser product matrix passes across both short-tag
profiles, including nested source transitions and exact heredoc EOF.
The former restricted constant-expression hierarchy was replaced with
structural expressions and contextual folding validation; the pinned
`isset_variable` production takes `expr`, not `variable`.
