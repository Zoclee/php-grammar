# PHP 8.6 Phase 4B draft integration

October 24, 2026. **PHP 8.6 is registered as a functional, incomplete draft.**
This is integration evidence, not a declaration of language completeness,
candidate readiness, or release certification. PHP86-013, PHP86-014, and
PHP86-015 remain open. The [Phase 4A assessment](integration-readiness.md)
defines the bounded scope implemented here.

## Baseline and preservation

Repository HEAD: `d63a9d206b389f5df41e49b1c7c41f5109638ee1`. Initial Git status
contained only the untracked Phase 4A `docs/8.6/integration-readiness.md`;
that file was preserved. No staging, commit, or Git configuration change was
performed. A pre-edit snapshot recorded 1,744 existing files, including that
assessment and the root configuration. Read-only Git commands required the
repository owner's execution context because the restricted runner reported
dubious ownership.

| Starting artifact | SHA-256 |
|---|---|
| PHP 8.5 EBNF | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| PHP 8.5 specification | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |
| PHP 8.6 EBNF | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| PHP 8.6 specification | `9f4c1d404cb2b94e5401809b1b19e5ccfa346e9542edc449993e7e4b8434e03a` |

Both EBNF files remain byte-identical to these hashes. Only the registration
status line changes in the 8.6 specification; its generated EBNF stays exact.
The protected set comprises 1,437 files under `grammar/8.5/`,
`tests/fixtures/php/8.5/`, `tools/8.5/`, and `docs/8.5/`: all hashes remain
unchanged. This includes source locks, production mappings, scanner evidence,
and certification artifacts. No historical evidence was regenerated.

Before integration, existing PFA and context checkers revalidated hashes,
production mappings, and EBNF/Markdown parity. The three known failures were
already reproduced in Phase 4A at this same repository HEAD and are recorded
there with exact tests and causes. This phase did not reinterpret them as passes.

## Registration, lexer, APIs, and defaults

`php-grammar.json` retains schema 1.0 and its unchanged 8.5 stable entry. The new
entry selects standalone 8.6 EBNF/specification, `source-file`, lexer `8.6`, and
`tests/fixtures/php/8.6`. Its status is `draft`; the profile explicitly limits
claims to RC4, the byte-oriented source profile, focused checks, and incomplete
contextual enforcement. Metadata exposes changes, source locks/evidence,
traceability mappings, a boundary index, integration documentation, and existing
focused reports. All paths are real package-relative files. No new schema field
or version was required.

`PhpVersion::php86()` returns version identity 8.6 using the shared, unchanged
keyword spellings. `php85()` retains its original keywords/defaults.
`forVersion('8.6')` is now supported without an 8.5 package dependency. The
scanning engine itself is unchanged. PFA uses existing `?`, `...`, colon, comma,
and call tokens; recognition differs in the version's argument productions.
Deprecations do not reserve `let` or `is`, and `readonly`'s contextual controls
remain intact. No new contextual compiler validator is introduced.

`GrammarRepository`, `PhpGrammarMatcher`, and production navigation use existing
manifest-driven interfaces. The three focused Phase 3 test classes now exercise
the public registration and real 8.6 lexer rather than appending test-local
packages. A standalone test copies only the 8.6 grammar/specification into an
isolated directory and successfully loads and matches through a one-package
manifest: no 8.5 grammar, change document, or evidence file is loaded at runtime.

The existing navigation CLI supports explicit 8.6 `rules`, `rule`, `refs`,
`sections`, and `section` commands. `versions` retains its array and original
fields and adds `sourceProfile`, `phpSource`, and `metadata`; draft status is
visible. Tests cover metadata, success, unknown rules, and existing errors.
There is no new public syntax-check command and no compiler-validity claim for
a structural match.

PHP 8.5 remains the recommended stable/default selection and the default of
existing maintainer commands. Public loaders require explicit versions.
`latestVersion()` **returns 8.6**, as its pre-existing documented contract
includes drafts; it is not a stable alias. README, API documentation, and usage
examples make that behavior explicit. No default or public signature was changed.

## Fixtures, tokenization, and validation stages

Sixteen new compatibility fixtures supplement the verified Phase 3 corpus:
11 positives cover declarations, precedence, DNF types, hooks, namespaces,
attributes, strings, heredoc, nowdoc, callable/clone forms, and folding; five
structural negatives cover missing types/arguments/expressions, delimiters, and
empty attributes. Each has its own hash and pinned parser reference in
`tests/fixtures/php/8.6/integration-cases.json` and is checked with both
interpreters and both short-tag profiles. The existing 8.5 corpus was not copied.

The 8.6 top-level corpus now has 82 valid files, 13 structural invalids,
40 contextual invalids, and one runtime-only file. Existing PFA (57 records),
contextual (63), and deprecation (67 inline witnesses) matrices remain separate;
their counts describe different representations and are not a completeness metric.
Ordinary valid/invalid discovery is automatic through the manifest. Contextual,
runtime, and diagnostic expectations retain their dedicated providers/checkers.

The new lexical matrix has 11 source/profile entries: PFA, operator boundaries,
deprecated names, property names, short tags on/off, echo tags, heredoc, nowdoc,
interpolation, and comments. All preserve source bytes. Seven entries compare
token lexeme boundaries against each distribution's native tokenizer; all have
paired lint evidence. PFA is lexically representable in both versions but its
syntax is rejected by 8.5. Focused tests also check malformed string/comment
rejection, explicit version identities, and contextual keyword controls.

`VersionBoundaryCase` now retains `validationStage` and `finding`, defaulting
legacy records to structural. Unknown stages are rejected.
`VersionBoundaryRunner::evaluate()` rejects nonstructural cases with a clear
diagnostic instead of presenting matcher acceptance as a compiler verdict.
The new integration checker discovers every boundary and validates six
structural, four lint, and three declaration-link records for both versions.
For contextual cases it verifies a unique matching reviewed context fixture,
stage, and finding before invoking an oracle. Declaration linking is limited to
those controlled cases. No execution occurs during grammar loading. The new
boundary index records all 13 metadata hashes and is checked for completeness.

## Previous failures and the shared scanner freshness issue

| Test | Resolution |
|---|---|
| `testGrammarDirectoriesExactlyMatchManifestVersions` | Both directories are now registered; exact equality remains required. |
| `testDocumentationReferencesPointToExistingVersionPaths` | 8.6 is registered and all referenced fixture paths exist; membership/path assertions remain intact. |
| `testBoundaryFixtureRepositoryAllowsEmptyDirectory` | Uses a genuinely empty temporary directory with cleanup. Additional tests cover an absent directory and all 13 real records, stages, pinned references, and expected structural outcomes. |

Single-version boundary behavior remains tested with an isolated 8.5 manifest.
Repository/consumer assertions now require both versions and exact stable/draft
statuses; `latestVersion()` and unsupported-version diagnostics follow the
existing API. No test was deleted or skipped.

The first broader run exposed a fourth integration issue:
`Php85ScannerAuditTest::testLedgerIsFreshAndEveryRuleAndBypassedIdentityHasEvidence`.
The generated scanner ledger differed only in `PhpVersion.php`'s input hash,
because the new version configuration lives in that shared file. Rewriting the
historical 8.5 ledger would violate preservation requirements.

[The separate shared-lexer attestation](shared-lexer-regression.json) records
the historical ledger hash, current generated ledger hash, and the single
reviewed old/new configuration hash. The test verifies both whole-ledger hashes,
the exact allowed path, old hash, actual current source hash, and equality of
every other input, rule, mapping, family, and disposition. All original scanner
matrix assertions still run. This preserves historical evidence while checking
the reviewed current implementation; future unreviewed changes still fail.
The remaining full-release freshness/orchestration issue identified by Phase 4A
is not solved or waived here. Release scripts and certification remain unchanged.

## Change traceability and provenance

`tools/8.6/change-traceability.php` uses the existing EBNF parser through
`tools/lib/ChangeTraceability.php`. Its reviewed [map](../../tools/8.6/change-map.json)
locks the copying baseline and inventories node structures, including alternatives,
optionality, repetition, terminals, and references. Actual differences are the
five existing PHP86-001 modified productions; no additions, removals, or renames.
No grammar production changed during Phase 4B.

The validator checks exact reviewed before/after structures, unmapped/stale
production mappings, source pins, required IDs, production references, fixture
existence/hashes, all ten change-document headings, local change-document paths,
and generated EBNF parity. It also hashes reviewed changes.md text and compares
13 non-generated specification sections with documented finding dispositions.
Reviewed lexer/configuration/adapter and compatibility-input hashes detect
unreviewed non-EBNF changes. Negative tests exercise added/removed productions,
an unreviewed alternative inside a mapped production, invalid/stale fixture and
production references, missing IDs/headings, and documentary/lexical drift.

[The result](change-traceability.json) retains complete structural before/after
trees and outstanding obligations. Hashes make changes require review; they do
not prove that prose is semantically correct. The Phase 3B/C/E clause/source
review remains the human evidence. PHP86-013/014/015, broader initializer and
optimizer interactions, and parent-hook PFA obligations remain explicitly open.

The independent [source lock](../../tools/8.6/source-lock.json) unifies existing
focused source/test hashes and records their original lock/case-manifest hashes.
It includes parser, scanner, compiler, relevant implementation/tests, distribution
hashes, and open findings. Source baselines remain:

- PHP 8.5 branch `PHP-8.5`: `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.
- PHP 8.6 branch `PHP-8.6`, tag `php-8.6.0RC4`:
  `f23b4c222cebe33be93ee8ecaaa14390c5507a08`.

Available interpreters are Windows PHP 8.5.10 and PHP 8.6.0RC4 distributions;
their hashes are checked. Neither is established as an exact-pin build.
The [draft validation report](draft-validation.json) records configuration,
input hashes, version/build output, token results, diagnostics, exit codes,
and stages. This is independent finite 8.6 evidence, not copied 8.5 certification.

## Files changed

Modified package/consumer files: `php-grammar.json`, `bin/php-grammar`,
`src/Php/Lexing/PhpVersion.php`, `src/Php/Conformance/VersionBoundaryCase.php`,
`src/Php/Conformance/VersionBoundaryRunner.php`, README, `docs/api.md`,
`docs/usage.md`, and `grammar/8.6/{php.md,changes.md}`.

Modified tests: the three `Php86PartialApplicationTest`,
`Php86ContextualChangesTest`, and `Php86DeprecationTest` classes;
`VersionBoundaryTest`; `Php85ScannerAuditTest`; and the repository
`ConsumerCliTest`, `ConsumerWorkflowTest`, and `RepositoryManifestTest` classes.

Added tooling: `tools/lib/ChangeTraceability.php`,
`tools/8.6/{change-traceability.php,integration-check.php,change-map.json,source-lock.json}`.
Added tests: `tests/Php/Conformance/{Php86IntegrationTest.php,Php86TraceabilityTest.php}`.
Added fixture manifests: `tests/fixtures/php/8.6/{integration-cases.json,boundary-cases.json}`.
Added fixture files use the `integration-` prefix: positive suffixes
`attributes`, `callable`, `declarations`, `folding`, `heredoc`, `hooks`,
`namespaces`, `nowdoc`, `precedence`, `strings`, `types`; negative suffixes
`argument-missing`, `attribute-empty`, `delimiter`, `expression-missing`,
`type-missing` (all `.php` in their respective valid/invalid directories).
Added evidence: this report, `draft-validation.json`, `change-traceability.json`,
and `shared-lexer-regression.json` under `docs/8.6/`.
The pre-existing untracked Phase 4A assessment remains unchanged.

## Commands and results

Run from the repository root. `P` below is `rtk .audit/php85/php.exe -n`;
`U` is `rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress`.
The full-suite logs remain in ignored `.audit/`. Scratch preparation/review
drivers are PHP and are not new project entry points.

| Command / selection | Actual result |
|---|---|
| `P tools/validate-manifest.php` | PASS: schema 1.0 and all package/metadata paths. |
| Repository tests plus `VersionBoundaryTest.php` | PASS: 30 tests, 968 assertions at the initial integration checkpoint. |
| Focused `Php86`, boundary, consumer, manifest, package and ToolManifest selection | PASS: 368 tests, 3,381 assertions at the pre-attestation checkpoint. |
| `P tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2` | PASS: 225 checks, 114 observations; zero failures. |
| `P tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2` | PASS: 278 checks, 126 stage observations; zero failures. |
| `P tools/8.6/deprecation-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=.audit/phase4b-deprecation.json` | PASS: 712 checks, 268 observations; zero failures. |
| `P tools/8.6/integration-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/draft-validation.json` | PASS: 431 checks, 126 observations; zero failures. |
| `P tools/8.6/change-traceability.php --report=docs/8.6/change-traceability.json` | PASS: five modified productions, 13 reviewed documentary sections, no errors; three open findings retained. |
| Broad suite before shared-lexer attestation, stopped at first failure | 5,489 tests, 49,148 assertions; scanner-ledger input-hash failure described above. |
| `U` (complete PHPUnit suite; `.audit/phase4b-phpunit-final.log`) | **PASS: 5,673 tests, 53,083 assertions; zero failures.** Includes EBNF integrity/parity, lexer matrices, both-version fixtures, focused 8.6 findings, APIs/CLI, traceability negatives, and repository integrity. |
| `rtk proxy .audit/php85/php.exe -n .audit/phase4b-review.php` | PASS: 1,744 baseline files compared; all 1,437 protected historical files unchanged; 88 local link paths checked; no broken paths or trailing whitespace in changed Markdown. |
| `rtk git diff --check`; `rtk git diff --cached --check` | PASS; staging remains untouched. New Markdown is also checked directly by the PHP review driver. |

These selections overlap; counts are not added together. The complete suite is
the final PHPUnit result. Source-lock, interpreter, and traceability commands are
additional checks, not PHPUnit tests. The final integration checker also records
its own source hash and removes its temporary probe on success or failure.

## Remaining work and phase boundary

PHP86-013 remains incomplete discovery, PHP86-014 remains an unresolved intended
assignment-based writable-target boundary, and PHP86-015 remains optimized-call
validation-order uncertainty. No finding is closed, no speculative production is
added, and no built-in-specific traversal rule is introduced. Contextual checks
and warnings remain separate from structural grammar matching and runtime success.

The package is ready for independently scoped completeness research and further
verified Gate B work. It is **not ready for candidate promotion or stable
certification**. Remaining work includes material discovery closure, full
parser/compiler reconciliation, independent classified scanner/grammar coverage,
broader contextual and optimizer matrices, exact-pin provenance where claimed,
subsequent RC/final-release reconciliation, and independent release orchestration.
The historical/current release-evidence dependency in Phase 4A remains; no full
release runner was executed or changed and no certification report was regenerated.
This phase does not begin that work.
