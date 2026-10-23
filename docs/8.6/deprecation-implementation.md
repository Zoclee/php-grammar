# PHP 8.6 Phase 3E: deprecation specification

October 23, 2026. **PHP86-010 documentation is complete within the verified
scope below. PHP 8.6 remains provisional, incomplete, and unregistered.**
No integration, contextual validator, or release certification is started.

## Evidence and Gate B decision

The source pins remain `PHP-8.5` at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and `PHP-8.6`, RC4 at
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. The existing 341 source,
archive, executable, and absent-side checks pass. Repository baseline HEAD is
`0730c0867f2de5f80b83d21bb3b3b2206e9ce483`; the preservation snapshot includes
the existing uncommitted Phase 3C/D work.

The pinned compiler, constant registration, API/magic-method checks, and object
debug handler establish diagnostic behavior without a structural language
change. Relevant upstream PHPTs corroborate acceptance, diagnostics, and
controls. They were inspected as source, not run as an upstream suite.
[The case manifest](../../tests/fixtures/php/8.6/deprecation-cases.json) locks
both-side implementation/test hashes and contains 67 isolated source witnesses.
[The validation report](deprecation-validation.json) records every source hash,
interpreter hash/version, lint result, execution result, and diagnostic.
The source/function, production, test, and case-group mappings are in
[changes.md section 6](../../grammar/8.6/changes.md#6-contextual-and-compiler-enforced-restrictions).

| Verified behavior | Stage and boundary |
|---|---|
| Functions named `readonly`, `let`, `is` | New compilation deprecation, without invoking the function. Methods and `_` function names remain controls. |
| Class/interface/trait/enum names `let`, `is`; class-type name checks | New compilation deprecations. Declaration names use their unqualified spelling case-insensitively; resolved qualified type suffix checks are literal. `N\LET` is a type-name control, not a warning-free class declaration. |
| Class/constant import aliases `_`, `let`, `is`; function aliases `let`, `is` | New compilation diagnostics. `_` function alias remains warning-free; `readonly` function alias remains a parse error in both versions. |
| Constants `_`, `let`, `is` | New execution-time registration diagnostics, not lint rejection. Qualified suffix case boundaries are retained. |
| Qualified `_` type names and runtime class aliases | Broadened name checking. Bare and namespaced `_` class declarations already warned in 8.5. `class_alias` lowercases the full alias before checking, and warns on execution. |
| `namespace` class/interface/trait/enum constants and enum cases | New compilation deprecation, case-insensitive. Same-named properties, methods, and a global `define` control remain warning-free. |
| Return from finally | New compilation diagnostic for bare/value returns and a nested try in finally. Nested closures have separate contexts; an enclosing discarded closure can avoid compilation. |
| Constructor/destructor value returns and generators | New compilation diagnostics; `return null;` counts as a value return, but `return;` does not. Methods need not execute to warn. |
| Nullable `__debugInfo` return declaration | New compilation deprecation for otherwise permitted nullable types; array return and omitted type controls remain accepted. Null received by the debug handler already warned at runtime in 8.5; a direct ordinary call is distinct. |

All deprecated witnesses remain structurally accepted by both grammars and exit
successfully in both distributions. The deliberately invalid `readonly` import
alias remains rejected by both grammars and interpreters. No `let`/`is` token
reservation or syntax removal follows. The reverted `__sleep`/`__wakeup`
deprecation is not implemented; the declaration control remains warning-free.
Existing contextual errors are not waived by these diagnostic rules.

## Specification and consistency review

Notes were added beside Names and tokens; Functions, closures and parameters;
Classes, interfaces, traits and enums; Namespaces and imports; Class constants;
and Control-flow constraints. The Enums paragraph now points to the precise
nullable-declaration/debug-handler distinction while retaining PHP86-006's
acceptance and signature constraints. The status paragraph records Phase 3E.
The existing deprecated-cast discussion is unchanged.

The ten-section change document records PHP86-010's old/new behavior, unchanged
productions, source/test references, paired evidence, and limitations. Deprecated
forms are explicitly excluded from syntax removals. The Phase 3D research
assessment and historical readiness fields remain untouched.

The focused PHP86-001–007 review found no additional rule correction necessary:

- PHP86-001's five changed productions retain exact generated Markdown parity;
  Arguments, constant-expression, attribute, argument-order, and callable
  clauses retain structural/contextual separation.
- PHP86-002 remains the required/optional placeholder explanation, without
  a separate production or runtime-signature certification claim.
- PHP86-003/004 retain readonly/property-write constraints and the separation
  from assignment-based writable targets.
- PHP86-005/006 retain declaration-link and magic-method checks. Deprecation
  does not turn an otherwise compatible nullable return declaration into an error.
- PHP86-007 retains its reference return/yield boundary, ordinary-value and
  foreach controls, and visited-compilation limits.

All implemented IDs retain normative clauses and change-document mappings.
Source/test locks revalidate; the grammar remains standalone. No entire
specification or generated section was rewritten.

## Validation

PHPUnit prefix:
`rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result`.

| Command appended to PHPUnit prefix | Actual result |
|---|---|
| `tests/Ebnf` | PASS: 74 tests, 155 assertions |
| `tests/Php/Conformance/Php86PartialApplicationTest.php` | PASS: 61 tests, 151 assertions |
| `tests/Php/Conformance/Php86ContextualChangesTest.php` | PASS: 128 tests, 725 assertions, including EBNF/Markdown parity |
| `tests/Php/Conformance/Php86DeprecationTest.php` | PASS: 135 tests, 1,376 assertions |
| `tests/Repository` | 21 tests, 888 assertions; two existing registration-related failures |
| `--filter 'Php85DocumentationParityTest\|Php85SpecificationStructureTest\|PhpConformanceFixtureTest\|Php85ContextualFixtureTest\|VersionBoundaryTest'` | 1,373 tests, 2,452 assertions; one existing empty-boundary assumption failure; PHP 8.5 cases pass |

Additional commands, with `rtk .audit/php85/php.exe -n` as prefix:

| Command | Actual result |
|---|---|
| `tools/validate-manifest.php` | PASS: schema 1.0 and package paths |
| `tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=.audit/pfa-phase3e.json` | PASS: 225 checks, 114 observations, zero failures |
| `tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=.audit/context-phase3e.json` | PASS: 278 checks, 126 stage observations, zero failures |
| `tools/8.6/deprecation-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/deprecation-validation.json` | PASS: 712 checks, 268 observations, zero failures |
| `.audit/verify-phase3e.php` | PASS: 1,682 prior files; seven changed/new files; 95 local links/anchors; JSON, whitespace, production/source mappings, ten change-document sections, and parity |

`rtk git diff --check` and `rtk git diff --cached --check` also pass.
Read-only Git checks ran outside the restricted runner for repository ownership;
no Git configuration or staging changed. Temporary preparation/preservation
drivers remain in ignored `.audit/`.

The actual retained failures are
`VersionPackageConsistencyTest::testGrammarDirectoriesExactlyMatchManifestVersions`,
`VersionPackageConsistencyTest::testDocumentationReferencesPointToExistingVersionPaths`,
and `VersionBoundaryTest::testBoundaryFixtureRepositoryAllowsEmptyDirectory`.
The last sees the unchanged 13 PFA/contextual records. No gate or test was weakened.
The new tests use the existing test-local manifest convention; no public
registration or lexer configuration changed.

## Preservation, limitations, and readiness

The pre-edit snapshot covers 1,682 files. Only `grammar/8.6/php.md` and
`grammar/8.6/changes.md` change among those files. PHP 8.5 grammar, Markdown,
fixtures, certification evidence, and tooling remain byte-identical, as do the
Phase 3B/C implementation code, fixtures, boundary records, and reports and all
Phase 3D research artifacts. PHP 8.6 EBNF and its generated Markdown block remain
unchanged; its SHA-256 remains
`12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4`.

The paired executables are distribution PHP 8.5.10 and PHP 8.6.0RC4, not proven
exact-pin builds. The profile is `-n`, all diagnostics enabled, no opcache/JIT or
custom error handler. User error handlers can escalate runtime diagnostics;
this does not constitute parser rejection. Full optimization, dynamic loading,
and runtime API conformance are outside this finite matrix. No new PHP86-010
structural finding or unsupported subfinding requires an EBNF edit.

PHP86-013 remains open for incomplete discovery; PHP86-014 still blocks
assignment-based writable-target edits; PHP86-015 still blocks unverified
optimized-call traversal rules. Contextual validator integration, stage-aware
boundary integration, exact-pin provenance, and final-release review remain
outstanding. PHP86-010 does not resolve these obligations.

The documented, verified changes are sufficiently consolidated for a **dedicated
integration-readiness assessment**. This is readiness to assess the remaining
work, not authorization or a claim of readiness to integrate or certify. Public
manifest discovery, release gates, and provisional status remain unchanged.
