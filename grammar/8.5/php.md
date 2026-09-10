# PHP 8.5 grammar and source contract

This standalone specification has three parts: lexical/source rules, syntactic
EBNF, and contextual syntax constraints. All three apply when deciding whether
source is valid PHP 8.5. The EBNF is the repository's authoritative syntactic
artifact; it is not by itself an implementation of all Zend compile-time checks.
The implementation remains under audit. The known discrepancies below prevent
a claim of full PHP 8.5 conformance.

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
be followed by a semicolon or newline. All nonblank body lines must have at least
the closing indentation. Tabs and spaces must not be mixed in the indentation
being stripped. Blank lines may have less indentation. The scanner enforces
label equality and indentation; these are lexical constraints beyond ordinary
context-free EBNF. An empty body is valid.

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
operand are accepted. Low-precedence prefix forms and AST grouping still have
the implementation limitations listed below; the table is the binding contract.

The EBNF preserves Zend's `simple_variable`, `new_variable`, `variable`,
`callable_variable`, `callable_expr`, `fully_dereferenceable`,
`array_object_dereferenceable`, and `new_dereferenceable` distinctions.
In this file the lexical `variable` is `T_VARIABLE`, and `variable-expression`
is Zend's syntactic `variable`. There is no universal postfix production.
`new Foo()` is directly dereferenceable; `new Foo` is not. Heredoc tokens are
scalars but not direct dereferenceable scalars. Removed curly-brace offsets
are excluded. Write-context restrictions remain contextual.

Bare `...` is a complete first-class-callable argument list. It cannot be mixed
with ordinary arguments. There is no call-time `&` argument modifier.
Clone-with permits named/unpacked lists and callable conversion. `clone($o)`
is also unary clone applied to a parenthesized expression; the special clone
argument production follows Zend's separate comma/named/unpacked alternatives.

## Contextual syntax constraints

These constraints are mandatory even when structural EBNF accepts a construct.
The repository's structural matcher does not implement this entire layer.

| Context | Required validation and source |
|---|---|
| Constant expressions | `zend_is_allowed_in_const_expr`, `zend_compile_const_expr`, and constant folding define the permitted AST forms. No arbitrary calls, variables, assignments, shell execution, interpolation, match, throw, or arrow functions. Static noncapturing closures and named first-class callables are permitted in 8.5. |
| Parameter defaults, global constants, attribute/constructor arguments | `allow_dynamic=true`: `new` with a statically determined class and object casts are allowed. No anonymous/dynamic class construction, `static`, unpacked constructor arguments, or constructor callable conversion. |
| Property defaults, class constants, enum values | `allow_dynamic=false`: `new` and object casts are forbidden recursively. Other supported casts, static closures, and named callable conversion remain subject to the declared type and context. |
| Static locals | Initializers are runtime `expression`, not the constant-expression subset; PHP 8.3+ behavior is retained. |
| Constant calls | Only callable conversion is accepted; names/classes/methods must satisfy Zend's static-name checks. Literal-folding cases are broader than simple source names. `static` is forbidden. |
| Arguments | No positional argument after named arguments/unpacking, no unpacking after named arguments, no duplicate named arguments. Attribute argument lists forbid unpacking and bare callable conversion. Built-in arity/name checks may reject clone/exit forms. |
| Attributed constants | Only one **global** constant per attributed declaration. Class constants may share attributes in a multiple-constant declaration. |
| Types | `mixed`, `void`, `never` must stand alone as applicable; `?mixed`, `?null`, duplicate/redundant unions, `true|false`, and built-in/scoped-name intersection members are invalid. `void`/`never` are return-only. Properties/promoted properties/class constants cannot use callable, void, or never. `static` is return-only with class scope. `self`/`parent` require the appropriate scope. |
| Parameters and closures | Unique parameters, only final parameter variadic, no variadic defaults, no forbidden auto-global/`$this` parameter or capture names, no duplicate captures or capture/parameter collisions. Nonempty `use` is structural. |
| Promotion | Only a concrete constructor in a legal class/trait context; no variadic promotion, duplicate property, invalid property type, or illegal modifiers. PHP 8.5 permits final promotion. Defaults initialize parameters, not property defaults. |
| Modifiers and declarations | Reject duplicate/conflicting modifiers, abstract-final conflicts, reserved class names, redeclarations, illegal nested class declarations, and invalid anonymous-class modifiers. Interfaces cannot use traits. Method bodies/visibility must match abstract/interface/concrete context. |
| Properties | Readonly properties need a type and cannot be static or have ordinary defaults. Only hooked properties may be abstract. Visibility/set visibility combinations and final/private combinations must be valid. |
| Hooks | One or two distinct hook kinds; no duplicate get/set; no static/readonly hooked properties. Only final is an explicit hook modifier. A get hook has no parameter list; a set list has one non-reference, nonvariadic parameter without a default and compatible type. Only get may return by reference. Concrete hooks need bodies; abstract/interface hooks follow body restrictions. A final hook cannot be private or abstract. |
| Interface properties | Public hooked properties only; no final, protected/private, or explicitly abstract property. Hooks have no implementation body. |
| Class constants | One type precedes the entire list. Enforce type restrictions/value compatibility, modifier legality, and final/private restrictions. |
| Enums | Only int/string backing types. Backed cases require constant values; unbacked cases forbid values. Case names/values must satisfy uniqueness and type rules; some value checks are deferred beyond lint. No properties; trait composition and forbidden magic/member declarations require further checks. |
| Writable variables | Assignment, reference binding, increment, unset, isset, destructuring, and foreach need appropriate read/write targets. Calls can reduce as `variable` but cannot be unset; nullsafe access cannot be written. Foreach keys cannot be references or destructuring lists. Empty array elements are only valid in destructuring. A destructuring tree cannot mix `[]` and `list()` forms (pinned `zend_compile.c`, lines 3250–3255). |
| Control flow | break/continue need a legal enclosing construct and positive literal level; goto targets/scope crossings must be legal; yield requires function scope; generator return types and return statements must be compatible. Match/switch permit only one default. |
| declare and namespaces | Directives require their specific literal values and placement; strict_types is 0/1, first statement, and not block form. Namespace styles cannot mix and namespace/import placement/conflicts must be legal. |

Authoritative compiler areas include `zend_compile_params`,
`zend_compile_typename_ex`, `zend_compile_attributes`, `zend_compile_prop_decl`,
`zend_compile_property_hooks`, `zend_compile_class_const_decl`,
`zend_compile_enum_case`, `zend_compile_foreach`, `zend_compile_conditional`,
`zend_compile_declare`, and `zend_compile_const_expr`.

## Deprecated but accepted syntax

PHP 8.5 accepts `(integer)`, `(boolean)`, `(double)`, and `(binary)` with
deprecations; casts are case insensitive and allow spaces/tabs inside their
parentheses, not comments/newlines. `(real)` and `(unset)` are removed and
excluded. `${...}` interpolation and backticks have applicable deprecations;
deprecation is not syntax rejection. Other deprecations unrelated to grammar
do not remove accepted forms.

## Remaining discrepancies and deliberate abstractions

- The low-precedence prefix escape paths do not establish a unique parse tree.
  Mixed-prefix acceptance has regressions, but lint tests alone cannot verify
  operator AST grouping. Dangling
  else binding likewise requires an explicit disambiguation policy.
- The constant-expression subset still needs an exhaustive comparison with
  constant folding. Literal computed class/callable names and folded/dead
  subexpressions are not fully reconciled.
- The repository lexer does not fully implement Zend's nested scanner stack
  for every interpolation/comment/heredoc combination. Property lookup,
  scripting shebang handling, and numeric string offset spelling have dedicated
  regressions, but the full scanner-state transition space is not proven.
- The external lexical primitives require a stateful scanner. A raw
  character-only EBNF expansion is not a PHP source validator. Numeric overflow
  token categorization is deliberately abstracted; accepted spelling is retained.
- Contextual rules above are normative but not fully automated. The contextual
  negative corpus explicitly demonstrates this implementation boundary.

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
    | attribute-groups , "const" , constant-element , ";" ;

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
    constant-logical-or-expression ;

constant-coalesce-expression =
    constant-boolean-or-expression , [ "??" , constant-coalesce-expression ] ;

constant-conditional-expression =
    constant-coalesce-expression ,
    [ "?" , constant-expression , ":" , constant-coalesce-expression
    | "?" , ":" , constant-coalesce-expression , { "?" , ":" , constant-coalesce-expression } ] ;

constant-logical-or-expression =
    constant-logical-xor-expression , { "or" , constant-logical-xor-expression } ;

constant-logical-xor-expression =
    constant-logical-and-expression , { "xor" , constant-logical-and-expression } ;

constant-logical-and-expression =
    constant-conditional-expression , { "and" , constant-conditional-expression } ;

constant-boolean-or-expression =
    constant-boolean-and-expression , { "||" , constant-boolean-and-expression } ;

constant-boolean-and-expression =
    constant-bitwise-or-expression , { "&&" , constant-bitwise-or-expression } ;

constant-bitwise-or-expression =
    constant-bitwise-xor-expression , { "|" , constant-bitwise-xor-expression } ;

constant-bitwise-xor-expression =
    constant-bitwise-and-expression , { "^" , constant-bitwise-and-expression } ;

constant-bitwise-and-expression =
    constant-equality-expression , { "&" , constant-equality-expression } ;

constant-equality-expression =
    constant-relational-expression ,
    [ ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , constant-relational-expression ] ;

constant-relational-expression =
    constant-concatenation-expression ,
    [ ( "<" | "<=" | ">" | ">=" ) , constant-concatenation-expression ] ;

constant-concatenation-expression =
    constant-shift-expression , { "." , constant-shift-expression } ;

constant-shift-expression =
    constant-additive-expression , { ( "<<" | ">>" ) , constant-additive-expression } ;

constant-additive-expression =
    constant-multiplicative-expression , { ( "+" | "-" ) , constant-multiplicative-expression } ;

constant-multiplicative-expression =
    constant-unary-expression , { ( "*" | "/" | "%" ) , constant-unary-expression } ;

constant-power-expression =
    constant-postfix-expression , [ "**" , constant-unary-expression ] ;

constant-unary-expression =
      constant-power-expression
    | "+" , constant-unary-expression
    | "-" , constant-unary-expression
    | "!" , constant-unary-expression
    | "~" , constant-unary-expression
    | constant-cast-expression ;

constant-cast-expression =
      "(int)" , constant-unary-expression
    | "(integer)" , constant-unary-expression
    | "(float)" , constant-unary-expression
    | "(double)" , constant-unary-expression
    | "(string)" , constant-unary-expression
    | "(binary)" , constant-unary-expression
    | "(array)" , constant-unary-expression
    | "(object)" , constant-unary-expression
    | "(bool)" , constant-unary-expression
    | "(boolean)" , constant-unary-expression ;

constant-primary-expression =
    constant-literal | constant | constant-class-access | magic-constant
    | "(" , constant-expression , ")" | array-creation-constant-expression
    | [ attribute-groups ] , "static" , "function" , [ "&" ] ,
      "(" , parameter-list , ")" , return-type , compound-statement
    | constant-callable
    | "new" , name , [ constant-argument-list ] ;

array-creation-constant-expression =
      "array" , "(" , constant-array-pair-list , ")"
    | "[" , constant-array-pair-list , "]" ;

constant-array-pair-list =
    [ constant-array-pair , { "," , constant-array-pair } , [ "," ] ] ;

constant-array-pair =
      constant-expression
    | constant-expression , "=>" , constant-expression
    | "..." , constant-expression ;

expression =
    throw-expression ;

logical-or-expression =
    logical-xor-expression , { "or" , logical-xor-expression } ;

logical-xor-expression =
    logical-and-expression , { "xor" , logical-and-expression } ;

logical-and-expression =
    print-expression , { "and" , print-expression } ;

assignment-expression =
      conditional-expression
    | variable-expression , assignment-operator , assignment-expression
    | list-expression , "=" , assignment-expression
    | variable-expression , "=" , "&" , variable-expression ;

assignment-operator =
      "=" | "+=" | "-=" | "*=" | "/=" | ".=" | "%="
    | "&=" | "|=" | "^=" | "<<=" | ">>=" | "**=" | "??=" ;

conditional-expression =
    coalesce-expression ,
    [ "?" , expression , ":" , coalesce-expression
    | "?" , ":" , coalesce-expression , { "?" , ":" , coalesce-expression } ] ;

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
    relational-expression ,
    [ ( "==" | "!=" | "===" | "!==" | "<=>" | "<>" ) , relational-expression ] ;

relational-expression =
    pipe-expression ,
    [ ( "<" | "<=" | ">" | ">=" ) , pipe-expression ] ;

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
    clone-expression , [ "**" , unary-expression ] ;

instanceof-expression =
    unary-expression , { "instanceof" , class-name-reference } ;

unary-expression =
    power-expression | prefix-unary-expression ;

prefix-unary-expression =
    "++" , variable-expression | "--" , variable-expression
    | ( "+" | "-" | "~" | "@" ) , unary-expression
    | "!" , boolean-not-expression
    | cast-expression | prefix-expression | closure-expression | match-expression ;

cast-expression =
      "(int)" , unary-expression
    | "(integer)" , unary-expression
    | "(float)" , unary-expression
    | "(double)" , unary-expression
    | "(string)" , unary-expression
    | "(binary)" , unary-expression
    | "(array)" , unary-expression
    | "(object)" , unary-expression
    | "(bool)" , unary-expression
    | "(boolean)" , unary-expression ;

void-cast-statement =
    "(void)" , expression , ";" ;

postfix-expression =
    variable-expression , [ "++" | "--" ] | primary-expression ;

fully-dereferenceable-expression =
    variable-expression | "(" , expression , ")" | dereferenceable-scalar
    | class-constant | new-dereferenceable ;

callable-expression =
    callable-variable | "(" , expression , ")" | dereferenceable-scalar | new-dereferenceable ;

primary-expression =
    literal | array-creation-expression | constant | class-constant | magic-constant
    | "(" , expression , ")" | new-dereferenceable | "new" , class-name-reference
    | backtick-string
    | "isset" , "(" , expression-list , [ "," ] , ")"
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
      "(" , "..." , ")"
    | "(" , [ argument , { "," , argument } , [ "," ] ] , ")" ;

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
    [ array-pair , { "," , [ array-pair ] } , [ "," ] ] ;

array-pair =
      expression
    | expression , "=>" , expression
    | "&" , variable-expression
    | expression , "=>" , "&" , variable-expression
    | "..." , expression
    | list-expression
    | expression , "=>" , list-expression ;

list-expression =
      "list" , "(" , array-pair-list , ")"
    | "[" , array-pair-list , "]" ;

include-expression =
      "include" , expression
    | "include_once" , expression
    | "require" , expression
    | "require_once" , expression ;

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
    [ attribute-groups ] , [ "static" ] , "fn" , [ "&" ] ,
    "(" , parameter-list , ")" , return-type , "=>" , expression ;

lexical-variable-list =
    "use" , "(" , lexical-variable , { "," , lexical-variable } , [ "," ] , ")" ;

lexical-variable =
      variable
    | "&" , variable ;

backtick-string-part-list =
    { encapsulated-string-part } ;

statement =
      inline-html
    | compound-statement
    | if-statement
    | while-statement
    | do-statement
    | for-statement
    | foreach-statement
    | switch-statement
    | declare-statement
    | try-statement
    | expression-statement
    | void-cast-statement
    | echo-statement
    | global-statement
    | static-statement
    | unset-statement
    | return-statement
    | throw-statement
    | break-statement
    | continue-statement
    | goto-statement
    | label-statement
    | empty-statement ;

compound-statement =
    "{" , inner-statement-list , "}" ;

inner-statement-list =
    { inner-statement } ;

inner-statement =
      statement
    | [ attribute-groups ] ,
      ( function-declaration
      | class-declaration
      | interface-declaration
      | trait-declaration
      | enum-declaration ) ;

expression-statement =
    [ expression ] , statement-terminator ;

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

throw-statement =
    "throw" , expression , ";" ;

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
      "if" , "(" , expression , ")" , statement , [ elseif-list ] , [ else-clause ]
    | "if" , "(" , expression , ")" , ":" , inner-statement-list ,
      [ alt-elseif-list ] , [ alt-else-clause ] , "endif" , ";" ;

elseif-list =
    "elseif" , "(" , expression , ")" , statement ,
    { "elseif" , "(" , expression , ")" , statement } ;

else-clause =
    "else" , statement ;

alt-elseif-list =
    "elseif" , "(" , expression , ")" , ":" , inner-statement-list ,
    { "elseif" , "(" , expression , ")" , ":" , inner-statement-list } ;

alt-else-clause =
    "else" , ":" , inner-statement-list ;

while-statement =
      "while" , "(" , expression , ")" , statement
    | "while" , "(" , expression , ")" , ":" , inner-statement-list ,
      "endwhile" , ";" ;

do-statement =
    "do" , statement , "while" , "(" , expression , ")" , ";" ;

for-statement =
      "for" , "(" , for-expression-list , ";" , for-condition-expression-list , ";" ,
      for-expression-list , ")" , statement
    | "for" , "(" , for-expression-list , ";" , for-condition-expression-list , ";" ,
      for-expression-list , ")" , ":" , inner-statement-list , "endfor" , ";" ;

for-expression-list =
    [ for-expression , { "," , for-expression } ] ;

for-expression =
      expression
    | "(void)" , expression ;

for-condition-expression-list =
    [ [ for-expression-list , "," ] , expression ] ;

foreach-statement =
      "foreach" , "(" , expression , "as" , foreach-target , ")" , statement
    | "foreach" , "(" , expression , "as" , foreach-target , ")" , ":" ,
      inner-statement-list , "endforeach" , ";" ;

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
      "declare" , "(" , declare-directive-list , ")" , statement
    | "declare" , "(" , declare-directive-list , ")" , ":" ,
      inner-statement-list , "enddeclare" , ";" ;

declare-directive-list =
    declare-directive , { "," , declare-directive } ;

declare-directive =
    identifier , "=" , constant-expression ;

try-statement =
    "try" , compound-statement ,
    ( catch-clause , catch-list , [ finally-clause ] | finally-clause ) ;

catch-list =
    { catch-clause } ;

catch-clause =
    "catch" , "(" , catch-type-list , [ variable ] , ")" , compound-statement ;

catch-type-list =
    name , { "|" , name } ;

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
    [ attribute-groups ] , class-modifiers , "class" , [ argument-list ] , [ extends-clause ] ,
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
    name , { "," , name } ;

class-member-list =
    { class-member } ;

class-member =
      [ attribute-groups ] ,
      ( property-declaration
      | method-declaration
      | class-constant-declaration )
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
    [ attribute-groups ] , property-hook , { [ attribute-groups ] , property-hook } ;

property-hook =
    property-hook-modifiers ,
    ( [ "&" ] , "get" , property-hook-body
    | "set" , [ "(" , parameter , [ "," ] , ")" ] , property-hook-body ) ;

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
      [ attribute-groups ] ,
      ( method-declaration | class-constant-declaration | interface-property-declaration ) ;

interface-property-declaration =
    interface-property-modifiers , optional-type-without-static , hooked-property ;

interface-property-modifiers =
    property-modifier , { property-modifier } ;

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
    ( identifier | reserved-non-modifiers | method-modifier , [ semi-reserved-identifier ] ) , ";" ;

trait-method-reference =
    semi-reserved-identifier | class-name , "::" , semi-reserved-identifier ;

enum-declaration =
    "enum" , identifier , [ enum-backing-type ] , [ implements-clause ] ,
    "{" , enum-member-list , "}" ;

enum-backing-type =
    ":" , ( "int" | "string" ) ;

enum-member-list =
    { enum-member } ;

enum-member =
    [ attribute-groups ] , ( enum-case | method-declaration | class-constant-declaration )
    | trait-use-declaration ;

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
    class-name , [ constant-argument-list ] ;

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

constant-argument-list =
    "(" , [ constant-argument , { "," , constant-argument } , [ "," ] ] , ")" ;

constant-argument =
    [ semi-reserved-identifier , ":" ] , attribute-or-constructor-argument ;

constant-callable =
    ( name | "readonly" | "clone" | "exit" | "die"
    | single-quoted-string | constant-double-quoted-string | "(" , constant-expression , ")"
    | constant-class-reference , "::" , ( semi-reserved-identifier | "{" , constant-expression , "}" ) ) ,
    "(" , "..." , ")" ;

constant-class-access =
    constant-class-reference , "::" , ( semi-reserved-identifier | "{" , constant-expression , "}" ) ;

constant-class-reference =
    name | single-quoted-string | constant-double-quoted-string | "(" , constant-expression , ")" ;

boolean-not-expression =
    "!" , boolean-not-expression | instanceof-expression ;

clone-expression =
    "clone" , ( clone-expression | prefix-unary-expression )
    | "clone" , clone-argument-list | postfix-expression ;

dereferenceable-scalar =
    array-creation-expression | single-quoted-string | double-quoted-string ;

new-dereferenceable =
    "new" , class-name-reference , argument-list | "new" , anonymous-class ;

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

constant-postfix-expression =
    constant-primary-expression
    | constant-dereferenceable , ( "[" , constant-expression , "]"
    | ( "->" | "?->" ) , ( semi-reserved-identifier | "{" , constant-expression , "}" ) ) ,
    { "[" , constant-expression , "]"
    | ( "->" | "?->" ) , ( semi-reserved-identifier | "{" , constant-expression , "}" ) } ;

constant-dereferenceable =
    constant | magic-constant | constant-class-access | array-creation-constant-expression
    | single-quoted-string | constant-double-quoted-string | "(" , constant-expression , ")"
    | constant-callable | "new" , name , constant-argument-list ;

throw-expression =
    "throw" , expression | arrow-expression ;

arrow-expression =
    arrow-function | include-expression | logical-or-expression ;

print-expression =
    "print" , print-expression | yield-expression ;

yield-expression =
    "yield" , [ yield-from-expression , [ "=>" , yield-from-expression ] ] | yield-from-expression ;

yield-from-expression =
    "yield" , "from" , assignment-expression | assignment-expression ;

prefix-expression =
    "throw" , expression | arrow-function | include-expression
    | "print" , print-expression
    | "yield" , [ yield-from-expression , [ "=>" , yield-from-expression ] ]
    | "yield" , "from" , assignment-expression ;

constant-literal =
    integer-literal | floating-literal | constant-string-literal ;

constant-string-literal =
    single-quoted-string | constant-double-quoted-string | nowdoc-string | constant-heredoc ;

constant-double-quoted-string =
    [ "b" | "B" ] , "\"" , { string-text } , "\"" ;

constant-heredoc =
    [ "b" | "B" ] , "<<<" , { " " | "\t" } ,
    ( heredoc-label | "\"" , heredoc-label , "\"" ) , newline ,
    { string-text } , { " " | "\t" } , heredoc-label ;

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

attribute-or-constructor-argument =
    constant-expression ;
```
<!-- END GENERATED EBNF -->
