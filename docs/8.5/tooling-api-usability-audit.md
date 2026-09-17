# PHP 8.5 tooling API usability audit

This phase treats consumption as a public product while preserving the stable
PHP 8.5 language definition. No production bodies, production names, scanner
behavior, contextual rules, precedence, compiler boundaries or fixture
classifications are intentionally changed. The supported contract now lives in
[`../api.md`](../api.md), with executable workflows in
[`../usage.md`](../usage.md). Validation results are recorded separately in
[`tooling-api-validation.md`](tooling-api-validation.md).

## Surface inventory and disposition

| Surface inspected | Classification | Finding and disposition |
| --- | --- | --- |
| `grammar/8.5/php.ebnf` | PUBLIC | Canonical complete grammar; all 360 names/bodies preserved. |
| `grammar/8.5/php.md` | PUBLIC | Language-first specification and three-layer contract; unchanged. |
| `php-grammar.json` | PUBLIC | Existing array shape and field names retained; additive discovery metadata and schema. |
| `grammar/<version>/` | PUBLIC | Explicit standalone version packages; manifest, not directory scanning, determines support. |
| `RepositoryManifest`, `VersionPackage` | PUBLIC | Existing discovery/path APIs retained; status/source metadata, primitive lookup, hasVersion/latestVersion added. |
| `GrammarRepository` in `Php\Conformance` | PUBLIC | Already reusable despite its namespace; preserve constructor/load/manifest and add productionIndex. |
| `Ebnf\Parser`, `Grammar`, `Production`, node types | PUBLIC | Existing parse/tree API; add production, productionNames and reverse-reference lookup. |
| `PhpGrammarMatcher`, `MatchResult` | PUBLIC | Existing structural matcher and result preserved; unknown selection now diagnosed before source lexing. |
| `Matcher`, `ChartMatcher`, `Input`, `StringInput` | PUBLIC | Low-level matching remains available with explicit primitive and recursion limitations. |
| `Php\Lexing\Lexer`, `Token`, `TokenStream`, `TokenType`, `PhpVersion` | PUBLIC | Tokenization and byte locations documented; no source behavior changes. |
| `MatchContext`, `PhpGrammarInput`, `StringSyntax`, `LexerState`, EBNF lexer/token implementation | INTERNAL | Accidental visibility via PSR-4/public methods; explicitly not extension points, no classes removed. |
| `GrammarValidator`, validation result/error helpers | SEMI-PUBLIC | Useful existing integrity tooling; retained, not a general PHP-validity contract. |
| `Coverage*`, fixture repositories/runners, coverage cases/classification, modifier validator | INTERNAL | Audit and test architecture; unnecessary for consumer integration. |
| `bin/php-grammar` | PUBLIC | Small JSON navigation CLI installed by Composer. |
| `bin/grammar-coverage.php`, `lexer-coverage.php`, `php85-conformance.php` | SEMI-PUBLIC | Maintainer entry points remain usable; their report layouts are not stable consumer APIs. |
| `tools/8.5/*.php`, `*.py`, `source-lock.json` | INTERNAL | Generators, source locks and audit procedures, not runtime dependencies. |
| `tools/grammar-release.py`, Composer validation scripts | SEMI-PUBLIC | Maintainer workflows; new manifest/schema and API gates augment the 24 existing checks. |
| Generated semantic production index inside `php.md` | PUBLIC | Human navigation already exists; machine sections now derived from comments plus parsed production locations. |
| `docs/8.5/phase3-coverage.json`, other generated coverage metadata | SEMI-PUBLIC | Discoverable evidence, not stable IDs or a parser API. No copied nullable/coverage truth. |
| `source-inventory.json`, `source-correspondence.json` | SEMI-PUBLIC | Pinned source evidence, exposed through optional manifest paths; internal fields remain report-specific. |
| `scanner-product.json`, `compiler-boundaries.json`, `final-evidence.json` | SEMI-PUBLIC | Lexical/contextual/source-profile evidence; link instead of importing into runtime code. |
| Other `docs/8.5/*.json` and audit Markdown ledgers | SEMI-PUBLIC | Retained published research records, not runtime contracts. |
| `.audit/`, temporary reports, PHPUnit cache, test fixtures | INTERNAL | Excluded local scratch/cache from Composer archives; fixtures retained as intentional published evidence. |
| Lexical primitive names and specification declarations | PUBLIC | Eight byte primitives; add descriptive metadata and state requirements without wildcard substitution. |
| `composer.json`, PSR-4 namespace, manifest and version artifacts | PUBLIC | Package type corrected to library; binary registered; runtime PHP >=8.2 remains. |
| README, usage/API/versioning documentation | PUBLIC | Integration now precedes historical conformance machinery. |
| Repository Git tags / Composer versions | PUBLIC | Package SemVer remains independent of PHP versions and schema versions; no invented release number. |
| Four previously removed interface/enum member aliases | DEPRECATED | Historical selectors already absent; publish migration to shared class-member names, no aliases restored. |

Previously unclear boundaries (especially the `Conformance` namespace, generic
matcher versus PHP matcher, and machine-readable audit reports) now have explicit
dispositions. PHP's public visibility alone does not establish support. No
previously visible class is made private or removed during this phase.

## Decisions across the requested phases

Discovery uses the existing `RepositoryManifest::versions/package` and
`GrammarRepository::load` rather than duplicating them under new names.
`hasVersion` and numerically ordered `latestVersion` fill actual gaps.
Specification and grammar paths already have stable fields, so no redundant
`getSpecification` or `specification` alias was added. Status/source pin/profile
answer consumer questions without requiring evidence-ledger knowledge.

The manifest stays backward compatible for existing readers and existing
constructor calls. JSON Schema `1.0` accepts legacy manifests lacking a schema
version, while versioned manifests require source/status and primitive metadata.
Unknown additive properties are permitted; unknown schema versions are rejected.
Full schema validation checks field types, version/name/path syntax, commit pin,
optional metadata and lexical definitions. A complementary integrity pass checks
duplicates, definition correspondence and actual package files. Runtime errors
identify malformed fields and unsafe paths without adding a runtime dependency.

Production lookup and reverse references operate directly on the existing parsed
grammar. Section metadata is derived on demand from canonical comments and
parsed production lines, preserving all 21 identifiers. No generated duplicate
production database is needed. The CLI can deterministically query this data.
Nullable analysis, generated/manual flags, contextual attachment and evidence
classification were evaluated and deferred: they would require semantic policy
or duplicate internal analysis beyond basic navigation. Existing source-order,
expression-tree and direct-reference data are already sufficient for visualizers.

Evidence lookup remains documentation metadata. The manifest points to the
existing source inventory, correspondence, scanner, compiler-boundary and final
evidence files. Inspect parser production/scanner state/compiler function names
within those reports and follow the immutable commit; line numbers are optional
navigation hints. There is no promise of a complete one-to-one production/source
mapping, and no runtime dependency on fragile line offsets.

Lexical primitive metadata is descriptive, not executable replacement behavior.
Stateful HTML, comment, encapsed and nowdoc primitives cannot be treated as
wildcards. The generic matcher still defaults only two byte primitives; the PHP
matcher continues to configure scanner/token adapters. Unsupported direct byte
primitive selection now tells the consumer which layer is responsible. Existing
return types and normal matching paths remain intact.

The API names already distinguish matching from validation; no `isValidPhp`
facade or parser redesign was necessary. Unknown rule selection remains a failed
`MatchResult`, avoiding an exception-based break. Grammar lookup gets a separate
throwing `production()` accessor. Unknown versions enumerate available versions.
The three-layer model is visible in both quick start and API documentation.

The CLI prioritizes versions, rules, a rule, direct/reverse references and
sections. Loading a rule already parses and validates its grammar. Full manifest
validation and release/generated checks retain maintainer command conventions;
no duplicate command hierarchy or match-service interface was built.

Each version remains standalone. Discovery/indexing code has no hard-coded 8.5
path; numeric ordering is tested with synthetic 8.5/8.9/8.10 manifest entries.
No unsupported grammar or lexer is advertised. `GrammarRepository`'s existing
empty-production/reachability policy and the lexer version registry are future
version onboarding work: another version must supply verified validation policy
and scanner behavior, not inherit 8.5 grammar or claim maturity from discovery.

Composer retains intentionally published evidence/docs/fixtures and adds only
scratch/cache/vendor exclusions. The package archive is inspected and installed
into a separate consumer project to verify autoloading and artifact availability.
No network service, plugin architecture, compiler, language server or IDE plugin
is introduced. The existing package SemVer policy now explicitly covers API,
schema and production-identifier compatibility, with migration guidance for the
four previously removed aliases.

## Compatibility limits

Existing manifest shape, loading/matching APIs and constructor parameters remain
valid. Invalid manifest paths (absolute, backslash or dot segments) now produce
clear errors; migrate custom manifests to package-relative forward-slash paths.
Unknown matcher-rule failures now have richer expected messages, so consumers
must not parse diagnostic prose. No aliases hide removed production names.
Display labels, source line numbers, generated evidence schemas and JSON node
serialization are explicitly outside stable identifier guarantees.
