# Grammar Conventions

This document defines the canonical EBNF notation and grammar-writing conventions used by the `php-grammar` repository.

The purpose is to ensure that every PHP grammar version is written consistently, is understandable by humans, and can be consumed by general-purpose tooling without relying on parser-generator-specific syntax.

## 1. Scope

These conventions apply to all canonical grammar files:

```text
grammar/<version>/php.ebnf
```

They also define how grammar productions are represented in the corresponding human-readable Markdown files:

```text
grammar/<version>/php.md
```

The `.ebnf` file is authoritative when any discrepancy exists.

## 2. EBNF Dialect

This repository uses a deliberately small ISO-style EBNF subset.

The supported notation is:

| Construct | Syntax | Meaning |
|---|---|---|
| Production | `name = expression ;` | Defines a non-terminal |
| Alternative | `a | b` | Either `a` or `b` |
| Sequence | `a , b` | `a` followed by `b` |
| Optional | `[ a ]` | Zero or one occurrence |
| Repetition | `{ a }` | Zero or more occurrences |
| Grouping | `( a )` | Groups an expression |
| Terminal | `"text"` | Literal terminal text |
| Comment | `(* text *)` | Informative comment |

Example:

```ebnf
argument-list =
    "(" ,
    [ argument , { "," , argument } ] ,
    ")" ;
```

## 3. Production Syntax

Every production must use this form:

```ebnf
production-name =
    expression ;
```

Production names use lowercase ASCII letters and hyphens:

```ebnf
function-declaration
qualified-name
class-member
```

Avoid:

```ebnf
FunctionDeclaration
function_declaration
functionDeclaration
```

A production name should describe a grammatical concept rather than the implementation detail of a parser.

## 4. Terminals

Literal PHP syntax is written using double-quoted terminals.

Examples:

```ebnf
"function"
"("
")"
"=>"
"?->"
```

Terminals are case-sensitive unless a production explicitly defines otherwise.

Do not use single quotes for canonical terminals.

Avoid parser-generator token names such as:

```text
T_FUNCTION
T_STRING
T_VARIABLE
```

unless the grammar is specifically defining the lexical relationship to PHP lexer tokens.

Canonical grammar should describe PHP syntax, not internal token constants used by the PHP implementation.

## 5. Sequences

A sequence is written using commas:

```ebnf
"function" , identifier , "(" , ")" ;
```

Do not rely on whitespace alone to imply sequencing.

This is intentionally explicit so grammar files are easier to parse mechanically.

## 6. Alternatives

Alternatives use `|`:

```ebnf
visibility-modifier =
      "public"
    | "protected"
    | "private" ;
```

When alternatives span multiple lines, place `|` at the beginning of continuation lines.

For large alternative sets, use one alternative per line.

## 7. Optional Elements

Optional syntax uses square brackets:

```ebnf
return-type =
    [ ":" , type ] ;
```

Do not use postfix operators such as:

```text
type?
```

in canonical EBNF.

## 8. Repetition

Zero-or-more repetition uses braces:

```ebnf
attribute-groups =
    { attribute-group } ;
```

One-or-more repetition should be expressed explicitly:

```ebnf
qualified-name =
    identifier , { "\" , identifier } ;
```

Do not introduce `+`, `*`, or other regular-expression-style repetition operators.

## 9. Grouping

Parentheses group grammar expressions:

```ebnf
parameter =
    [ ( type , [ "&" ] ) ] ,
    variable ;
```

Grouping parentheses are EBNF notation unless quoted.

Literal PHP parentheses must therefore be terminals:

```ebnf
"("
")"
```

## 10. Empty Productions

Avoid explicit empty-string notation where an optional construct expresses the grammar more clearly.

Prefer:

```ebnf
argument-list =
    "(" ,
    [ argument , { "," , argument } ] ,
    ")" ;
```

instead of introducing an explicit epsilon production.

If an empty production is genuinely necessary, use a named optional structure rather than inventing notation such as:

```text
ε
epsilon
EMPTY
```

unless this convention is formally amended.

## 11. Comments

Comments use:

```ebnf
(* comment *)
```

Comments are informative only and must not alter grammar semantics.

Use comments sparingly for:

- version-specific behavior;
- non-obvious grammar decisions;
- known limitations;
- references to lexical assumptions;
- distinctions that would otherwise be unclear.

Do not place normative syntax requirements only in comments.

## 12. Lexical and Syntactic Grammar

The grammar should clearly distinguish lexical grammar from syntactic grammar.

Lexical productions describe concepts such as:

- identifiers;
- variables;
- numeric literals;
- string literals;
- whitespace;
- comments;
- keywords;
- operators and punctuation where lexical treatment matters.

Syntactic productions describe constructs such as:

- expressions;
- statements;
- declarations;
- types;
- namespaces;
- classes;
- functions;
- attributes.

Do not mix lexer implementation details into higher-level syntax unless necessary to describe accepted PHP source text accurately.

## 13. Whitespace and Comments

Unless a production explicitly states otherwise, insignificant whitespace and comments may occur between lexical tokens according to PHP lexical rules.

Canonical syntactic productions should normally omit whitespace productions.

For example, write:

```ebnf
if-statement =
    "if" , "(" , expression , ")" , statement ;
```

rather than:

```ebnf
if-statement =
    "if" , whitespace , "(" , whitespace , expression , ...
```

Whitespace that is semantically significant inside a lexical construct must be handled by the relevant lexical production.

## 14. Keywords

PHP keywords should be represented as literal terminals where they appear syntactically:

```ebnf
"class"
"function"
"readonly"
"match"
```

Context-sensitive keyword behavior must be represented by grammar structure or lexical rules rather than explained away informally.

Do not assume that every keyword is globally reserved in every grammatical position.

## 15. Identifiers and Names

Use named productions for identifier classes where PHP distinguishes them.

For example:

```ebnf
identifier
qualified-name
fully-qualified-name
namespace-relative-name
```

Do not collapse distinct name forms merely to simplify the grammar.

If PHP permits keywords or other special tokens in a specific name position, model that explicitly.

## 16. Case Sensitivity

Terminal spelling in EBNF represents the canonical textual form.

Where PHP treats a keyword or lexical category case-insensitively, define that behavior in the lexical grammar or accompanying normative note rather than duplicating every spelling variation.

Do not assume that all identifiers share the same case-sensitivity rules.

## 17. Unicode

The grammar must not imply ASCII-only identifiers or source text unless PHP itself imposes that restriction.

Where Unicode behavior belongs to lexical rules, describe it there.

Do not invent Unicode normalization requirements that PHP does not impose.

## 18. Character Ranges

Avoid implementation-specific range notation unless it has been formally defined in this document.

Prefer named lexical productions:

```ebnf
decimal-digit
hexadecimal-digit
identifier-start
identifier-part
```

rather than ad hoc constructs such as:

```text
[a-zA-Z]
\x00-\x7F
```

If character-range notation becomes necessary, it must first be standardized here.

## 19. Precedence and Associativity

Expression precedence and associativity are part of the grammar model and must be represented unambiguously.

Prefer layered productions such as:

```ebnf
logical-or-expression
logical-and-expression
equality-expression
relational-expression
additive-expression
multiplicative-expression
```

or another structure that accurately models PHP.

Do not rely only on prose tables when the canonical EBNF can encode the distinction.

Do not use parser-generator precedence directives in canonical grammar.

## 20. Left Recursion

Left recursion is permitted when it is the clearest faithful representation of PHP syntax.

The canonical grammar is a language specification, not a grammar tailored to a specific LL, LR, PEG, or recursive-descent parser.

Do not rewrite correct grammar solely to satisfy one parser implementation.

Tools consuming the grammar are responsible for transforming productions when necessary.

## 21. Ambiguity

Avoid accidental ambiguity.

Where PHP's language grammar is inherently context-sensitive or depends on lexical disambiguation, document that explicitly.

Do not silently resolve ambiguity by changing the accepted language.

When an implementation-specific parser technique differs from the abstract grammar, prefer the language-level representation.

## 22. Runtime Semantics

Canonical EBNF describes syntax only.

Do not encode runtime requirements such as:

- whether a referenced class exists;
- whether a type combination is semantically legal;
- whether a variable has been defined;
- whether a function call is valid at runtime;
- whether an expression evaluates successfully.

A source form may be syntactically valid even if PHP later rejects it during compilation or semantic analysis.

If the distinction matters, document it outside the grammar production.

## 23. Version-Specific Syntax

Each PHP version directory must describe that version independently.

A production must not silently include syntax introduced only in a later PHP version.

When syntax changes between versions, update the affected production in the appropriate version.

Version-specific comments may be used where helpful:

```ebnf
(* Available in PHP 8.4 and later. *)
```

However, the grammar itself must remain correct without relying on the comment.

## 24. Production Ordering

Within `php.ebnf`, use a stable logical order.

Recommended top-level order:

1. document/source structure;
2. lexical grammar;
3. names and identifiers;
4. literals;
5. types;
6. primary expressions;
7. expressions and operators;
8. statements;
9. functions and closures;
10. classes, interfaces, traits, and enums;
11. attributes;
12. namespaces and imports;
13. top-level declarations;
14. version-specific or exceptional constructs.

Dependencies may justify minor deviations.

## 25. Formatting

Use four spaces for indentation.

Example:

```ebnf
function-declaration =
    [ attribute-groups ] ,
    [ function-modifiers ] ,
    "function" ,
    [ "&" ] ,
    identifier ,
    parameter-list ,
    [ ":" , type ] ,
    compound-statement ;
```

Keep lines reasonably short where practical.

Do not align large blocks using variable amounts of whitespace purely for visual columns, because this creates unnecessary diff noise.

Use a blank line between productions.

## 26. Markdown Representation

The human-readable Markdown file may reproduce productions in fenced `ebnf` blocks.

Example:

````markdown
## Function declarations

```ebnf
function-declaration =
    "function" ,
    identifier ,
    parameter-list ,
    compound-statement ;
```
````

The Markdown version may include explanation and examples, but the grammar production must remain semantically equivalent to the canonical `.ebnf` file.

If generated from canonical EBNF, generated sections should not be edited manually.

## 27. Examples

Examples in Markdown are informative unless explicitly marked otherwise.

Use PHP code fences:

````markdown
```php
function example(int $value): string
{
    return (string) $value;
}
```
````

Examples do not override canonical EBNF.

## 28. Source References

When a production is based on or changed because of a specific authoritative source, record that source in the repository's source documentation or nearby Markdown explanation.

Preferred evidence includes:

- PHP source grammar;
- accepted PHP RFCs;
- official PHP documentation;
- official migration guides;
- PHP parser tests.

Do not embed large copied sections from upstream sources into the grammar.

## 29. Extensions Not Allowed in Canonical EBNF

Do not use parser-generator-specific or regex-like extensions unless this document is amended.

Examples of notation that is currently not allowed:

```text
*
+
?
:=
::
->
%left
%right
%prec
%token
/regex/
[a-z]
(?=...)
```

Likewise, do not include semantic actions such as:

```text
{ createNode(...) }
```

Canonical files must remain implementation-neutral.

## 30. Grammar Validation

A grammar change should be checked for at least:

- syntactically valid EBNF;
- duplicate production definitions;
- undefined non-terminals;
- unreachable productions where detectable;
- accidental recursion errors;
- Markdown/EBNF drift;
- unintended syntax differences from the corresponding PHP version.

Tooling may impose additional validation, but must not redefine the canonical grammar dialect without updating this document.

## 31. Example Canonical Style

```ebnf
function-declaration =
    [ attribute-groups ] ,
    "function" ,
    [ "&" ] ,
    identifier ,
    parameter-list ,
    [ ":" , type ] ,
    compound-statement ;

parameter-list =
    "(" ,
    [ parameter , { "," , parameter } , [ "," ] ] ,
    ")" ;

parameter =
    [ attribute-groups ] ,
    [ type ] ,
    [ "&" ] ,
    [ "..." ] ,
    variable ,
    [ "=" , constant-expression ] ;
```

This example demonstrates notation and formatting only. It must not be treated as authoritative PHP grammar unless it also appears in the canonical grammar for the relevant PHP version.

## 32. Changing These Conventions

Changes to this document affect every grammar version and downstream tool.

Before changing the canonical notation:

1. identify the concrete limitation in the existing conventions;
2. verify that the new notation is needed;
3. consider compatibility with existing grammar files and tooling;
4. update validation tools;
5. migrate affected grammar files consistently;
6. document any breaking impact.

Prefer extending the existing EBNF subset conservatively rather than introducing dialect-specific syntax.
