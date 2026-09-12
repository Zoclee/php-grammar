# PHP 8.5 Grammar Completeness — Phase 5 of 7

Current status: the [subsequent remediation audit](remediation-audit.md) resolves the discarded-unset, for-condition, assignment-prefix and instanceof-power defects, expands the boundary matrix to 114 families, and supplies systematic AST and scanner-product comparisons. Counts and unresolved findings below describe the historical stage unless explicitly updated.

All 190 inventoried rules in the pinned PHP 8.5 scanner have explicit audit
dispositions across 25 lexical families. Scanner behavior is no longer an
unexplored completeness blocker. This is strong, finite source-backed evidence,
not a proof of all possible scanner/parser state combinations or full PHP 8.5
language conformance. One newly discovered **folding-dependent acceptance
discrepancy** remains assigned to Phase 6.

The [generated lexical ledger](phase5-lexical-evidence.json) is the
machine-readable audit index. It records the original scanner rule and line,
lexical states, repository implementation, status, family evidence, deliberate
abstractions, supporting helper/parser sources, and every primitive/trivia
grammar identity. Source and implementation hashes make stale evidence visible.

## Sources and audit method

The unchanged implementation pin is
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e` on PHP-8.5:

- [Scanner](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_scanner.l)
- [Parser](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_language_parser.y)
- [Compiler](https://github.com/php/php-src/blob/7a4c62795365ed6a97a0184c96375b9fb4d53b1e/Zend/zend_compile.c)

Review followed the 190 state-rule inventory entries, including fallback/EOF
rules, rather than selecting only common language examples. Related literal
keyword/operator rules share a family but retain individual ledger entries.
Review also covered scanner macros (1371–1392), `zend_lex_tstring` (300–319),
escape conversion (928–1148), newline/indentation helpers (1153–1240), nesting
diagnostics (1254–1357), and the parser's halt directive (407–410). Encoding
filters, token values, allocation, event callbacks, and diagnostic delivery
have explicit out-of-profile dispositions. The source-inventory generator now
retains the SHEBANG rule's `{NEWLINE}` macro instead of truncating its display.

The three source hashes are checked against `tools/php85-source-lock.json`.
PHP 8.5.10 is an executable cross-check, not a build of the exact source pin.
No third-party grammar or tokenizer replaces repository recognition.

## Findings by lexical family

| Family | Evidence and disposition |
|---|---|
| Tags | Long-tag case, required SP/TAB/CR/LF or EOF boundary, echo tags, close-tag newline consumption, short-tag profiles, malformed opener near-misses. Long-tag whitespace is retained separately in project tokens. |
| Inline HTML | Empty source, arbitrary bytes, non-openers, before/between/after PHP regions. Recognized PHP never falls back to HTML to hide an error. |
| Shebang | LF/CR/CRLF initial lines and EOF/no-initial-position boundaries. File scanner skipping is represented by a retained comment; tokenizer INITIAL behavior is normalized separately. |
| Whitespace | Exactly SP/TAB/CR/LF; VT/FF/NUL/DEL and other controls fail in scripting. Fixed CRLF tracking across separate consumptions. |
| Comments/doc comments | Both line forms, newline/EOF/close-tag termination, first block terminator, nesting-like text, empty comments, NUL, doc whitespace, attribute/property state distinction, unterminated failures. Ordinary comments and composite lookahead have different NUL rules. |
| Identifiers | Every ASCII letter, underscore, continuation digit, and byte 80–FF. Keyword adjacency, punctuation, high-byte and invalid UTF-8 boundaries. |
| Keywords | Every keyword/magic constant, case variants and suffixes; die/exit/readonly/from/enum, contextual type/hook names, and keyword property names. Scanner classification is distinct from parser identifier legality. |
| Enum lookahead | Fixed exclusion of `extends`/`implements` prefixes and NUL-containing lookahead comments. The longer exclusion rules do not require an identifier end. |
| Yield-from | Fixed atomic lookahead recognition, NUL-sensitive trivia, EOF, suffix boundaries, close tags within lookahead comments, and the same behavior in nested scripting. |
| Variables | All legal start bytes, digits after the first byte, extra dollar signs, dollar/braces, invalid digit starts, property and interpolation interaction. Standalone punctuation need not form a valid expression. |
| Property state | Whitespace/comments preserve state; keywords become identifiers; hash plus bracket remains a comment in this state; braces and other fallback tokens restore scripting. |
| Qualified names | Simple, qualified, fully qualified, namespace-relative, keyword components, repeated/trailing separators, and spaces at separators. Names remain atomic. |
| Integers | Decimal/legacy octal and both prefix cases for every radix; every radix digit, missing/invalid digits, underscores and token termination, identifier adjacency, overflow-sized spellings. Legacy octal 8/9 rejection occurs in numeric primitives. |
| Floats | Point/exponent forms, both exponent cases and signs, underscores, leading zeros, malformed exponents, adjacency, dot/ellipsis maximal munch. Runtime numeric values are not asserted. |
| Operators/punctuation | Every project operator, both object operators, set-visibility tokens, punctuation, overlapping prefix collisions, EOF and token adjacency. Two Zend ampersand categories share project `&`; grammar placement preserves their relevant distinction. |
| Casts | Canonical and deprecated integer/double/boolean/binary aliases; case and horizontal whitespace; newline exclusions; void; removed real/unset. Fixed lexical rejection of `(real)` even without an operand. See the unset finding below. |
| Single strings | Empty/multiline, every byte, escaped quote/backslash, unknown escapes, binary prefixes, tag-like text, termination/EOF. Values are not decoded by the token model. |
| Double strings | Empty/plain/multiline, delimiters, binary prefixes, escapes, simple/complex interpolation, nested strings/comments, termination and state restoration. Whole strings are aggregate tokens. |
| Backticks | Separate delimiter with shared interpolation/escape families, empty/multiline content, quotes as text, malformed termination. Tests never execute commands. |
| Escapes | Named escapes, applicable quote, unknown escapes, octal widths/overflow, hex cases, Unicode lower/upper bounds and invalid forms. `StringSyntax` rejects invalid Unicode escapes after source-span aggregation. |
| Interpolation | Variable/property/nullsafe/offset forms; braced scripting; deprecated dollar-brace varname and expression paths; numeric-string spellings; invalid offsets/expressions. Scanner boundaries and EBNF legality remain separate. |
| Heredoc | Unquoted/quoted/binary/high-byte labels; all three line endings; empty and interpolated bodies; nested labels; spaces/tabs, blank lines, mixed/insufficient/extra indentation; label prefixes and punctuation. Fixed exact-EOF label recognition. |
| Nowdoc | Independently tested quoted labels, literal dollar/braces/invalid escapes, binary/high-byte labels, indentation, matching/incorrect terminators and EOF. No interpolation is performed. |
| Halt compiler | Exact directive tokens with trivia, close-tag terminator, EOF, all 256 payload bytes, PHP-looking payload, malformed arguments, property spelling. Payload cannot resume PHP scanning. Scope is contextual. |
| Source bytes | All bytes as code units, HTML and single-string contents; every high byte in variables/identifiers/heredoc/nowdoc labels; UTF-8 and invalid UTF-8 sequences; NUL and byte offsets/lengths. No Unicode normalization or decoding. |

## State audit

The audit covers the source equivalents of **SHEBANG, INITIAL,
ST_IN_SCRIPTING, ST_DOUBLE_QUOTES, ST_BACKQUOTE, ST_HEREDOC, ST_NOWDOC,
ST_END_HEREDOC, ST_LOOKING_FOR_VARNAME, ST_LOOKING_FOR_PROPERTY, and
ST_VAR_OFFSET**. The ledger records the exact states of each source rule.

The project implements modes through the main source loop, a property flag,
recursive `interpolationEnd`, heredoc label/indentation handling, and
`StringSyntax::fragments`. It does not expose Zend's state stack as its public
token API. Nested strings, comments, heredocs, nowdocs, property fallbacks, and
PHP/HTML transitions are checked by direct cases and the existing
`remaining-scanner-*` corpus. Reusing the same lexer after successful and failed
scans is tested. All mutable state belongs to the individual call.

Source positions are zero-based byte offsets/lengths, with one-based byte
columns and lines. CRLF is one line break, including across consumptions; tabs
advance one byte column. No display-width, grapheme, or code-point guarantees
are implied. The raw-byte profile is `zend.multibyte=0`; callers needing Zend
input conversion must perform it before this API. BOM bytes are ordinary bytes
in this profile, not an implicit encoding switch.

## Confirmed corrections and their layers

1. **Lexer enum lookahead:** `enum extendsName` previously became a keyword.
   Scanner rules 1568/1572 select an identifier because the exclusion matches
   the prefix. NUL is excluded from the scanner lookahead comment macros.
2. **Lexer composite yield-from:** previously inferred from the preceding
   nontrivia token. It now requires the exact scanner lookahead and consumes
   the atomic span before exposing the EBNF's two terminals. A close tag inside
   its line comment does not change source mode, even in nested interpolation.
3. **Token adapter:** an Identifier with spelling `enum` or `from` previously
   also matched the corresponding keyword terminal. Those terminals now require
   scanner keyword classification; identifier primitives still consume the
   original token. This fixes enum declaration and false yield-from acceptance.
4. **Heredoc/nowdoc EOF:** an ending label must have a following non-LABEL byte.
   A label at exact file EOF no longer closes the aggregate string. The non-root
   fragment adapter provides a trailing newline boundary, preserving the useful
   standalone heredoc-fragment API. Whole-file bytes are never augmented.
5. **Removed real cast:** `(real)` always raises the scanner's parser-mode
   removed-cast error; treating it as parentheses around a constant was wrong.
6. **Position tracking:** split CR/LF consumption no longer counts two lines.
7. **Evidence tooling:** corrected SHEBANG inventory text and kept Phase 5
   fixtures out of historical Phase 3 baseline/witness reconstruction.

These are lexer, source-contract, adapter, and evidence corrections. No
canonical EBNF production was added, removed, renamed, or weakened. No token
type was added; the token-position implementation was corrected.

## Independent evidence and optional differential

`tests/Support/Php85LexicalCases.php` owns **2,700 direct source/token cases**,
**101 primitive cases**, and **144 whole-source lexical integration cases**.
The direct matrix labels 1,713 positive cases and 987 boundary cases, including
84 expected lexical failures. Boundary cases also include valid token splits
and accepted edge forms; they are not all negative source programs.
Expected token categories and lexemes are constructed from explicit source
definitions, not extracted from the repository lexer. PHPUnit also checks
every resulting source offset, length, line, and column; raw code-unit tests
exercise all 256 bytes. Deterministic loops enumerate byte classes, keywords,
radix digits and prefixes, underscores, exponent cases/signs, operator
collisions, cast whitespace, line endings, and high-byte labels. There is no
random fuzzing and no fabricated EBNF traversal.

The optional `tools/php85-lexer-differential.php` requires PHP 8.5.x. It uses
`token_get_all()` without parser feedback for token boundaries, and TOKEN_PARSE
for selected lexical rejection and whole-source integration expectations.
Neither function is used by production recognition or normal correctness tests.
The existing whole-corpus command separately uses `php -l` for compilation.

The independent normalization preserves exact source bytes while:

- splitting opening-tag whitespace and atomic yield-from into project tokens;
- aggregating Zend string-state tokens through matching quote/heredoc and
  interpolation-stack boundaries;
- mapping name/operator/keyword token IDs to project categories;
- retaining numeral spelling categories across platform overflow;
- representing the file scanner's initial shebang as a comment;
- mapping halt payload to its project category and documenting removed-cast
  recovery behavior.

Direct expected data is checked against both implementations. Additionally,
all **602 ordinary valid corpus files** have their normalized token streams
compared; these broader comparisons are not counted as independent expected
data. Combined short-tag profiles report **2,615 successful expected-token
comparisons, 84 expected lexical rejections, 144 parser-mode integration
comparisons, and 602 corpus-token comparisons**: **3,445 comparisons**,
plus one documented oracle exception. That exception is malformed
`__halt_compiler(1);`: the tokenizer's three-token halt heuristic differs from
the parser's actual directive requirement. A separate parser-mode case checks
rejection. Expected deprecations and octal-escape overflow warnings are not
acceptance failures; no string/backtick fixture is executed.

## Lexical bypasses and corrected grammar coverage

The ledger maps **52 primitive-bypassed productions and 174 alternatives** to
their direct lexical families, including every identifier/digit/string/offset/
label/HTML/payload helper. It also maps **11 removed-trivia productions and nine
alternatives** to whitespace/comment handling and tests. Their behavior belongs
before syntactic token-stream matching; restoring them for percentages would
misrepresent the architecture. The raw `code-unit` primitive is independently
checked as one byte, including invalid UTF-8 and NUL.

| Measurement | Before Phase 5 | After Phase 5 |
|---|---:|---:|
| Productions | 300/363 (82.6%) | 300/363 (82.6%) |
| Alternatives | 601/785 (76.6%) | 600/785 (76.4%) |
| Attempted productions | 300/363 | 300/363 |
| Attempted alternatives | 602/785 | 602/785 |
| Primitive-bypassed | 52 productions / 174 alternatives | unchanged |
| Trivia-removed | 11 productions / 9 alternatives | unchanged |
| Contextual-only alternatives | 1 | 1 |
| Scanner-context-only alternatives | 0 | 1 |
| Unclassified meaningful gaps | 0 | 0 |

The one removed hit is `reserved-non-modifiers/alternative:25` (`enum`). Its
non-primitive path is an unmodified trait alias immediately before a semicolon.
The scanner then emits an identifier, which the alias's identifier alternative
already accepts; T_ENUM would require a following label-start byte. Earlier
token adaptation falsely counted both spellings as keyword evidence. The new
classification is structural and follows the alternative identity if reordered.
It does not classify arbitrary missing rules as lexical gaps.

The original `simple-type-without-static/alternative:10` contextual-only
alternative remains classified and retains its return-only type/contextual
evidence. It is not forced through positive source coverage.

## Known discrepancy and preserved parser/compiler decisions

`false && (unset) 1;` is accepted by PHP 8.5.10 but rejected by repository
recognition. Zend still scans `T_UNSET_CAST` (1708), the parser creates the cast
(1368), and the compiler rejects surviving casts (10472/12366). Short-circuit
folding can remove this particular cast first. A live cast and the probed
ternary/static-closure variants fail compilation. The earlier report's claim
that the scanner did not supply this token was incorrect and is corrected.

The fixture is recorded under `known-discrepancies/valid/`, with its source,
owner, and disposition in the negative ledger. Phase 5 preserves the requested
removed-cast grammar policy; **Phase 6 must reconcile this folding-dependent
acceptance**. It is not concealed as an ordinary invalid fixture or a claim
that removed live casts are valid. No unexplained scanner mismatch remains in
the checked cases, but this explained full-source acceptance difference remains.

The common class-like syntax, enum contextual legality, trait aliases, hooks,
attributes, try statements, static class-name lists, alternative-if correction,
and folding boundary decisions from the parser/compiler remediation remain
in force. The ordinary corpus, 85-family folding matrix, and Zend AST checks
continue to verify them. Phase 5 does not implement AST equivalence, name
resolution, type checking, deferred context, or complete folding semantics.

## Validation and reproduction

Run Composer with PHP 8.5 and the PHPUnit extensions available. The optional
AST check additionally needs ext-ast (validated here with 1.1.3/schema 120).
The machine's default PHP was 8.4; verification used the existing local
PHP 8.5.10 binary and process-local configuration, without changing global PHP.

```text
rtk composer validate --strict
rtk composer test -- --no-progress
rtk composer grammar:coverage
rtk composer lexer:coverage
rtk proxy php bin/php85-conformance.php /path/to/php-8.5
rtk proxy php tools/php85-boundary-folding.php /path/to/php-8.5
rtk proxy php -d extension=ast tools/php85-ast-conformance.php
rtk proxy php-8.5 -d short_open_tag=1 tools/php85-lexer-differential.php
rtk proxy php-8.5 -d short_open_tag=0 tools/php85-lexer-differential.php
rtk proxy php tools/php85-coverage-report.php --check
rtk proxy php tools/php85-negative-report.php --check
rtk proxy python tools/php85-source-inventory.py .audit
rtk proxy python tools/php85-compiler-boundaries.py .audit --check
rtk git diff --check
```

Regenerate the lexical ledger with `composer lexer:coverage -- --write` after
reviewed changes. The normal command and PHPUnit fail if input hashes, rules,
family evidence, or coverage classifications are stale. The scanner oracle
fails on unexpected mismatches and reports recovery exceptions separately.

Validation on 2026-09-12:

| Check | Result |
|---|---|
| `composer validate --strict` | Pass |
| `composer test` on PHP 8.5.10 | Pass; 4,730 tests, 47,973 assertions |
| `composer grammar:coverage` | Pass; 300/363 productions, 600/785 alternatives; no unclassified meaningful gaps |
| `composer lexer:coverage` | Pass; 190 source rules, 25 families, 2,700 direct + 101 primitive + 144 integration cases |
| Ordinary PHP 8.5 differential corpus | 602 valid, 349 structural-negative, 276 contextual-negative; zero unexpected mismatches |
| Known acceptance discrepancies | One recorded discarded-unset witness; EBNF rejects, PHP accepts, as documented |
| Folding matrix | 1,105 comparisons; zero failures |
| Zend AST grouping | 59 positive and 10 negative comparisons; zero failures |
| Scanner/token differential | 3,445 comparisons; zero unexpected mismatches; one documented tokenizer-recovery exception |
| EBNF integrity and Markdown parity | Pass for all 363 productions; canonical EBNF bytes unchanged |
| Source hashes and compiler inventory | Pass; 244 fatal sites, including 14 parser-action sites, across 87 functions |
| Coverage/negative/lexical report freshness | Pass |
| Ambiguity, derivation, and repository consistency tests | Pass in the full PHPUnit suite |
| `git diff --check` | Pass |

## File inventory

**13 files added, 22 files changed, no files removed.**

Added:

- `bin/lexer-coverage.php`
- `tools/php85-lexer-differential.php`
- `tests/Support/Php85LexicalCases.php`
- `tests/Support/Php85LexicalAudit.php`
- `tests/Php/Lexing/Php85ScannerAuditTest.php`
- `tests/fixtures/php/8.5/valid/phase5-{yield-comment-close,real-name,enum-name}.php`
- `tests/fixtures/php/8.5/invalid/phase5-{real-parentheses,enum-prefix}.php`
- `tests/fixtures/php/8.5/known-discrepancies/valid/phase5-discarded-unset.php`
- `docs/8.5/phase5-lexer-audit.md`
- `docs/8.5/phase5-lexical-evidence.json`

Changed:

- `src/Php/Lexing/{Lexer,LexerState}.php`
- `src/Php/Conformance/{PhpGrammarInput,PhpGrammarMatcher,Php85CoverageClassification}.php`
- `tests/Php/Conformance/{Php85CoverageClassificationTest,Php85NegativeBoundaryLedgerTest}.php`
- `tools/{php85-source-inventory.py,php85-coverage-report.php}`
- `composer.json`, `README.md`, `grammar/8.5/php.md`
- `docs/{conformance,grammar-conventions,sources}.md` and `docs/8.5/{phase3-coverage,phase4-negative-coverage,parser-compiler-remediation}.md`
- Generated `docs/8.5/phase3-coverage.json`, `docs/8.5/source-inventory.json`,
  and the negative ledger's `.json` and `.md` files.

Removed: **none**. The canonical `grammar/8.5/php.ebnf` remains unchanged and
standalone. Local source downloads and verification helpers remain untracked
inside the existing ignored `.audit` directory.

## Remaining proof limits

Every meaningful scanner family has a disposition and direct evidence. The
remaining unproven claim is **all-input equivalence of recursively nested
scanner/parser combinations**; finite matrices and corpus agreement cannot
prove it. Exact malformed-input recovery streams and diagnostic text are not
project guarantees. Token-value conversion, encoding filters, resource limits,
and engine callbacks are explicitly outside this source-byte profile.

Phases 6–7 retain parser/AST/ambiguity closure, contextual and constant-folding
closure (including discarded unset), and the final completeness assessment.
This report does not claim full PHP 8.5 language conformance.
