# PHP 8.5 Audit Remediation

This note records the Phase 2 authoritative audit remediation disposition for
`grammar/8.5/php.ebnf`.

## Conformance Boundary

The canonical grammar describes valid PHP 8.5 language syntax. It is not a
mirror of every form the Zend parser can reduce before contextual compile-time
validation, and it is not based on the installed PHP CLI.

Primary references:

- PHP 8.5 parser grammar:
  <https://github.com/php/php-src/blob/php-8.5.10/Zend/zend_language_parser.y>
- PHP 8.5 scanner:
  <https://github.com/php/php-src/blob/php-8.5.10/Zend/zend_language_scanner.l>
- PHP 8.5 migration guide:
  <https://www.php.net/manual/en/migration85.php>

## Disposition Checklist

| Audit area | Disposition |
|---|---|
| Valid PHP syntax boundary | Documented in README, conformance docs, source policy, and PHP 8.5 companion docs. |
| Inline HTML versus PHP opening tags | Fixed in lexer/source-mode model; inline HTML is emitted only outside PHP mode. |
| `<?php` boundary | Fixed in lexer: `<?php` requires a non-identifier boundary. |
| `<?=` expression lists | Fixed in EBNF; echo open tag accepts `expression-list`. |
| `?>` source transition | Fixed in lexer and EBNF statement terminators. |
| Short open tags | Documented as lexer configuration-dependent. |
| `code-unit` | Retained as byte-oriented lexical primitive for uninterpreted source regions. |
| Ordinary identifiers versus keywords | Fixed in conformance primitives; ordinary `identifier` matches identifier tokens only. |
| Semi-reserved/contextual names | Fixed in conformance adapter with a narrower contextual keyword primitive. |
| `die` exit alias | Fixed in lexer keyword table and EBNF expression syntax. |
| `from` keyword handling | Fixed in lexer; `from` is no longer a general reserved keyword. |
| `__halt_compiler` | Retained as reserved keyword and top-level halt compiler statement. |
| `enum` contextual handling | Represented as contextual name keyword in the adapter. |
| Non-ASCII identifier bytes | Lexer continues to restrict identifier bytes to `0x80`-`0xff`; EBNF keeps `code-unit` primitive for byte-level leaves. |
| Numeric literal prefixes and decimal zero | Fixed in EBNF and token-lexeme primitive predicates. |
| Numeric separator placement | Fixed in EBNF and token-lexeme primitive predicates. |
| Comments and `#[` | Lexer already separated `#[`; line comments now stop before `?>`. |
| String delimiter constraints | Retained in project lexer; deeper interpolation equality/indentation constraints remain contextual. |
| Heredoc/nowdoc label equality and indentation | Documented as contextual lexical constraints enforced by the project lexer where currently implemented. |
| Expression hierarchy | Rebuilt around PHP precedence layers. |
| `or`, `xor`, `and` | Fixed in EBNF. |
| `**` associativity | Fixed as right-associative in EBNF. |
| Prefix `++` and `--` | Fixed in EBNF. |
| `<>` inequality | Fixed in EBNF. |
| `??` associativity | Fixed as right-associative in EBNF. |
| Equality and relational chaining | Fixed as non-associative grammar levels. |
| Ternary associativity | Retained as a right-nested conditional production to reject chained modern invalid forms. |
| Clone and clone-with precedence | Fixed as unary expression forms, including `clone(...)`. |
| Dereferenceability and literal calls/member access | Fixed by separating fully dereferenceable/callable expression categories and removing universal postfixing. |
| Curly-brace offset syntax | Removed from postfix operations. |
| Destructuring assignment | Fixed for `list(...) =` and `[...] =`. |
| Named arguments and by-reference special case | Fixed by removing separate named-by-reference argument syntax. |
| Bare `...` first-class-callable argument list | Fixed as whole argument-list alternative only. |
| Recursive variable variables | Fixed in lexer and EBNF. |
| Member-name breadth | Fixed by limiting member-name to identifier, variable, or expression braces. |
| Closure and arrow function attributes | Fixed in EBNF. |
| Anonymous class attributes | Fixed in EBNF. |
| Empty closure `use ()` | Fixed in EBNF by requiring at least one lexical variable. |
| `unset` target restrictions | Fixed in EBNF with variable-only unset targets. |
| `foreach` target restrictions | Fixed in EBNF with foreach-variable categories. |
| `for` `(void)` locations | Fixed by separating condition expression lists. |
| `switch` optional leading semicolon | Fixed in EBNF. |
| `match` comma before `=>` | Fixed in EBNF. |
| Nullable/DNF types | Fixed by rejecting nullable parenthesized intersections. |
| Property type `static` | Fixed by using `type-without-static` for properties. |
| Hooked properties and ordinary property lists | Fixed by separating ordinary list properties from single hooked properties. |
| Unmodified class properties | Fixed by requiring a property modifier list or `var`. |
| Interface property hooks | Fixed in EBNF. |
| Bare class/interface member semicolons | Removed from class and interface member alternatives. Enum separators remain modeled. |
| Typed class constants | Fixed as `const [type] X = ...`. |
| Trait use adaptation semicolon | Fixed by separating `use T;` from `use T { ... }`. |
| Attributes on trait use | Fixed by excluding trait-use declarations from attributed member alternatives. |
| Cast aliases and `(unset)` | Fixed by accepting deprecated valid aliases and removing `(unset)` from valid cast syntax. |
| Constant expressions | Fixed by replacing `constant-expression = expression` with a restricted expression hierarchy. |

## Contextual Constraints

The following remain documented contextual constraints rather than ordinary EBNF
rules: heredoc/nowdoc label equality, flexible heredoc indentation, duplicate
modifiers, impossible type combinations, invalid attribute targets, callable
validity for `|>`, and other compile-time checks that are not pure syntax.
