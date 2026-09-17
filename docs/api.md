# Public consumer contract

This contract covers the packaged artifacts and interfaces below. PHP language
versions and package releases are independent; see [versioning](versioning.md).
Select an explicit language version for reproducible results. PHP 8.5 is currently
the only advertised version. Every version is a complete standalone grammar.

## Artifacts and discovery

| Public artifact | Contract |
| --- | --- |
| `php-grammar.json` | Primary discovery entry point; do not scan directories. |
| `grammar/<version>/php.ebnf` | Canonical EBNF, stable production identifiers, no cross-version dependencies. |
| `grammar/<version>/php.md` | Full language specification, including lexical and contextual constraints. |
| `schema/php-grammar.schema.json` | JSON Schema draft 2020-12 for manifest schema `1.0`, also accepting legacy unversioned manifests. |
| `docs/api.md`, `docs/usage.md`, `docs/grammar-conventions.md`, `docs/versioning.md` | Consumer contracts, examples, dialect and compatibility policy. |
| `bin/php-grammar` | Composer-installed JSON navigation CLI. |

Paths in the manifest are package-root-relative, use forward slashes, and exclude
absolute paths and `.`/`..` segments. Resolve them relative to the manifest,
never the application's working directory. Existing `versions` remains an array;
`documentation` remains the specification path. No replacement `specification`
field or redundant production catalog is introduced.

Manifest schema `1.0` adds `status`, `sourceProfile`, and `phpSource` (branch plus
immutable commit) to each package. `status` is `draft`, `candidate`, `stable`,
`historical`, or `deprecated`; stable describes the grammar, not a full compiler
validator. Optional `metadata` maps descriptive names to files. These paths are
discoverable, but the evidence files' internal report schemas are semi-public.
They are not runtime dependencies.

`lexicalPrimitives` keeps its existing list of names. The additive
`lexicalPrimitiveDefinitions` map supplies `meaning`, `scannerContext`, and
`independentMatcher`. All declared primitives have definitions in schema `1.0`.
An independent byte predicate still needs the specified lexical interpretation;
it does not make an entire string or source file independently recognizable.

Readers should ignore unknown additive fields. An unsupported `schemaVersion`
requires a reader upgrade. An absent version denotes the legacy manifest;
runtime defaults remain `['code-unit']` for omitted lexical primitives and
`unspecified` for omitted status. There is no fabricated minimum package release:
use Composer's package constraint and the schema version. The runtime requires
PHP 8.2 or later; PHP 8.5 is needed for release conformance testing, not loading.

Run `python tools/validate-manifest.py` for full JSON Schema and package-path
validation (install `tools/requirements.txt`). It reports JSON field paths,
checks version uniqueness and primitive-definition correspondence, and verifies
referenced files/directories. `RepositoryManifest` performs defensive runtime
checks without requiring Python; it is not a general JSON Schema engine.

## PHP interfaces

All names below are relative to the `PhpGrammar` namespace. Existing namespaces,
including `Php\Conformance`, remain supported; their historical placement does
not make the listed consumer APIs internal.

| Interface | Supported operations |
| --- | --- |
| `Repository\RepositoryManifest` | `fromRepositoryRoot()`, `fromArray()`, `versions()`, `packages()`, `package()`, `hasVersion()`, `latestVersion()`, `absolutePath()`, `lexicalPrimitives()`, `lexicalPrimitive()` |
| `Repository\VersionPackage` | Readonly version, rootProduction, grammarPath, documentationPath, conformanceFixturePath, lexerVersion, status, sourceProfile, phpSource and metadata fields; old constructor arguments remain valid. |
| `Php\Conformance\GrammarRepository` | Constructor accepting root or manifest, `load(version)`, `manifest()`, `productionIndex(version)` |
| `Ebnf\Parser` | `parse(source)` returns `Grammar`; malformed EBNF raises `ParserException` or `LexerException`. |
| `Ebnf\Grammar` | `productions()`, `productionMap()`, `hasProduction()`, `production()`, `productionNames()`, `referencesTo()` |
| `Ebnf\Production` | Readonly `name`, `expression`, `line`; `references()` |
| `Ebnf\Node` and node classes | Existing expression tree fields and `references()`; source lines are navigation hints, not stable IDs. |
| `Repository\ProductionIndex` | `sections()`, `rulesInSection()`, `sectionForRule()`; `fromSource(grammar, source)` requires the parsed grammar and its matching source. |
| `Php\Conformance\PhpGrammarMatcher` | Existing factories/constructor, `matches(version, source)`, `matchesRule(version, rule, source)`, `withShortOpenTag()`, `lexicalPrimitiveNames()` |
| `Ebnf\Matching\MatchResult` | `matched`, `rule`, `input`, `furthestOffset`, `expected`, `consumedInput()` |
| `Php\Lexing\Lexer`, `Token`, `TokenStream`, `TokenType`, `PhpVersion` | Existing source tokenization and token inspection APIs; use `Lexer::forVersion()` and the manifest lexer identifier. |
| `Ebnf\Matching\Matcher`, `ChartMatcher`, `Input`, `StringInput` | Existing low-level matching interfaces; consumers supply appropriate primitives/input. These are not whole-PHP validity APIs. |

`versions()` sorts numerically ascending. `latestVersion()` is the highest
advertised numeric version, including draft/historical/deprecated packages; it
does not silently apply a stability filter. Filter `packages()` by status if
needed. `load()` caches within its repository instance; treat installed package
files as immutable during that instance's lifetime.

Production lists and reverse references use canonical source order. References
are direct, not transitive. `referencesTo()` includes self references and accepts
external primitive names; an unreferenced or unknown name returns `[]`.
`production()` throws `OutOfBoundsException` for an unknown name. No aliases are
resolved implicitly. See migration guidance below.

Sections are derived at query time from canonical section comments, with
membership obtained from parsed production locations. The mapping is
`id => ['title' => string, 'productions' => list<string>]`. Titles are display
text. Stable identifiers for 8.5 are `source`, `lexical`, `names`, `literals`,
`types`, `expressions`, `dereferencing`, `arguments`, `statements`, `functions`,
`classes`, `namespaces`, `attributes`, `termination`, `primitives`, `reserved`,
`prefix`, `initializers`, `matched`, `precedence`, and `yield`.
Unknown section/rule queries throw `OutOfBoundsException`; a known production
without a section returns `null`. Other versions need not have the same sections.

## Matching and diagnostics

The specification has three layers: lexical/source rules, structural EBNF, and
external contextual constraints. `PhpGrammarMatcher` combines the configured
lexer and structural recognizer. `matched === true` requires complete structural
consumption, but does not certify all compiler constraints, type semantics,
runtime success, AST construction or source transformation safety.

`matches()` accepts a complete source file, including tags and HTML.
`matchesRule()` accepts PHP-code fragments without an opening tag; fragments
receive a trailing source boundary for heredoc scanning. Input starting with
`<?` is treated as whole source. Trivia is removed; open/close tags are adapted
to parser input. Short tags are enabled by default; configure them explicitly.
Token objects retain exact source bytes and offsets; the matching adapter does
not return an AST or a lossless parse tree.

`MatchResult` is an object, not a boolean: inspect `->matched`. Ordinary lexical
or structural failure returns a result. Unknown selected productions continue
to return failure results, now with a deterministic version-qualified diagnostic
before lexing. Unsupported versions throw `RepositoryManifestException` with
the available versions; missing package files raise `ConformanceException` with
the version/path. Exception classes and failure modes are contracts; diagnostic
wording and suggested expectations may improve in compatible releases.

`furthestOffset` is an input-element offset (tokens for normal PHP matching,
bytes for raw strings and lexer-error results). It is not always a source byte
offset. `expected` is recognition/debugging information, not compiler-quality
syntax diagnostics or an exhaustive completion list.

## Lexical primitives

Consult manifest definitions and the specification's lexical contract before
implementing a byte matcher. `code-unit` means one raw byte; `non-ascii-code-unit`
means one byte from 0x80 to 0xFF. HTML scanning depends on recognized enabled
opening tags; line comments stop before CR/LF/EOF or `?>`; block comments stop
before `*/`. Encapsed and nowdoc scanning require active delimiters, interpolation
rules, labels and indentation. These are not wildcard character productions.

`Matcher::withDefaultPrimitives()` supplies only `code-unit` and
`non-ascii-code-unit`. It is useful for small EBNF/lexical leaves, not full PHP
source recognition; its recursive guard also differs from chart recognition of
left-recursive rules. Missing custom primitives produce failed matches under the
existing low-level API. Use `PhpGrammarMatcher` for PHP expressions and files.
It supplies token-level adapters and scanner behavior; its
`lexicalPrimitiveNames()` lists overridden token productions, a different concept
from the manifest's external byte primitives. Direct selection of an unsupported
external byte primitive now explains the need for a byte matcher/scanner context.
No unrestricted wildcard substitution is introduced.

## CLI and maintenance boundaries

`php bin/php-grammar --help` lists supported commands. Successful navigation
commands emit JSON to stdout and exit 0; usage, unknown versions/rules/sections,
or loading failures emit a diagnostic to stderr and exit 1. `versions`, `rules`,
`refs`, `sections`, and `section` mirror the documented array shapes. `rule`
serializes the current `Production` tree for inspection; generic JSON AST node
serialization is semi-public, so durable tooling should use the PHP node API or
canonical EBNF. Rule lookup also parses/validates the selected grammar.

Maintainer commands remain separate: `composer manifest:check`,
`php tools/8.5/generate-expressions.php --check`,
`php tools/8.5/sync-documentation.php --check`, and `composer release:check`.
The release suite requires source caches, PHP 8.5/ext-ast and the prerequisites
in the README; applications do not need those to load or match grammars.

Coverage classes, conformance fixture runners, modifier validators, coverage
case builders, `MatchContext`, `PhpGrammarInput`, `StringSyntax`, lexer state
machinery, and other unlisted implementation classes are internal. Their public
PHP visibility is not an API promise. No existing class is removed or hidden.
Evidence files under `docs/8.5/` are semi-public research records; generated
coverage IDs, hashes, line numbers, report fields and audit CLIs are not stable
consumer identifiers. `.audit/`, fixtures and PHPUnit architecture are internal.

## Compatibility and migration

The existing [SemVer policy](versioning.md#6-repository-release-versioning)
applies. Documentation, evidence and internal performance fixes are patch
changes; new APIs, PHP versions and optional manifest capabilities are minor
changes. Production rename/removal, incompatible schema changes, required-field
changes to an existing schema, and public signature changes require a breaking
release with migration guidance. Even before 1.0, document these changes.
Small non-breaking descriptive metadata corrections may ship in patch releases.
The manifest schema version is independent of both package and PHP versions.
Keep schema `1.0` available if a future incompatible schema is introduced.

The previous grammar simplification removed these aliases:

| Previous production | Current production |
| --- | --- |
| `interface-member-list` | `class-member-list` |
| `interface-member` | `class-member` |
| `enum-member-list` | `class-member-list` |
| `enum-member` | `class-member` |

Update stored selectors explicitly. API aliases were considered and declined:
they would conceal canonical membership and could imply interface/enum-specific
contextual validation that a shared member rule does not perform. All 360 current
identifiers and bodies are unchanged by this consumer API work.
