# PHP 8.6 Phase 3B: PHP86-001 implementation

October 23, 2026. **Provisional, unregistered, incomplete PHP 8.6 draft.**
The focused structural PHP86-001 implementation and its declared test matrix
are complete. Broader PFA integration, discovery closure, and certification
are not complete. No other PHP 8.6 finding is implemented in this phase.

## Gate B decision and preservation

The [Gate B requirements](../versioning.md#31-readiness-gates) were applied to
PHP86-001 at PHP 8.6 RC4 commit
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`, branch `PHP-8.6`; the PHP 8.5 pin
remains `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. Parser, scanner, compiler,
AST construction, and partial-application implementation hashes were checked
against the existing research evidence before grammar edits.

- Parser `argument_no_expr` directly establishes positional/named `?` and bare
  `...`; `argument_list` and `possible_comma` establish callable trailing commas.
  AST construction identifies placeholder lists as callable construction.
- Parser call, method, constructor, attribute, and clone entry points establish
  where the lists parse. `zend_compile_args_ex` and `zend_compile_call_common`
  establish the separate visited-AST ordering/unpacking/constructor/nullsafe
  restrictions. Clone's initial-expression comma rule is preserved.
- `zend_compile_const_expr_fcc`, `zend_compile_const_expr_args`, and
  `zend_eval_const_expr` establish constant PFA support and argument traversal.
  Direct live/discarded witnesses separate this from general optimizer claims.
- Existing token spellings suffice: no scanner state, token definition, or
  precedence edit is justified. Pinned upstream overview, named, clone,
  compile-error, and constexpr PHPTs supply independent regression references.

These source-backed constructs are separable from PHP86-014 writable targets:
no write-target production or constraint is changed. PHP86-013's broader
discovery and PHP86-015's optimized traversal remain open. No independence claim
is made for all optimization, linking, or evaluation combinations. Parent-hook
specific regression fixtures, runtime signature checks, broader initializer
combinations, and optimizer profiles remain deferred obligations; no additional
syntax is inferred from them. PHP86-002 explains required/optional parameters
without a separate EBNF addition.

The [Phase 3A record](baseline-preparation.md) remains unchanged as historical
evidence of exact copying. At this phase's start, its three files were staged;
their staging was preserved. Pre-edit SHA-256 hashes of both 8.6 files still
matched that record. The repository copying baseline remains
`d4cd2c5446a9de7fa783533ef8d9e8f8f0fad3ee`.

## Implementation and evidence

The [change document](../../grammar/8.6/changes.md) supplies all ten required
sections, exact production mappings, source references, fixtures, contextual
enforcement status, and pending findings. Five productions change:
`ordinary-argument-list`, `first-class-callable-arguments`, `clone-argument-list`,
`argument`, and `argument-no-expression`. None is added, removed, renamed, or
reordered. All other production structures are compared unchanged.

The standalone specification updates Arguments and arrays, Attributes,
Constant-expression validation order, Argument constraints, Callable conversion,
and related constant-call constraints. Its generated EBNF block is synchronized
without regenerating unrelated prose or production names. Draft status and
inherited audit labels prevent PHP 8.5 evidence becoming an 8.6 certification claim.

The [source lock](../../tools/8.6/pfa-source-lock.json) records five implementation
files and 19 upstream PHPT hashes. Those upstream PHPTs were reviewed as source;
the upstream suite was not executed. The [local fixture manifest](../../tests/fixtures/php/8.6/pfa-cases.json)
contains 57 cases: 32 positive, eight structural-negative, and 17
contextual-negative. Six [boundary records](../../tests/fixtures/version-boundaries/8.6)
represent PHP 8.5/8.6 differences. Contextual negatives intentionally match the
EBNF; the PHP executables provide separate compiler rejection evidence.

The focused PHPUnit harness uses the existing `RepositoryManifest::fromArray`
and `PhpGrammarMatcher::forManifest` public APIs. Its in-memory test package uses
the unchanged 8.5 lexer for the bounded token corpus. It does not register 8.6
publicly or implement an 8.6 contextual validator. Whole-version scanner and
conformance integration remain deferred. No production loader or release gate
was changed to bypass registration checks.

## Commands and actual results

All commands ran from the repository root. PHPUnit prefix:
`rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result`.
The two interpreters are PHP 8.5.10 and PHP 8.6.0RC4 Windows CLI builds, run
with `-n`; neither is represented as a reproducibly verified exact-pin build.
No new fixture program was executed: differential validation was lint only.

| Command (PHPUnit rows append to the prefix above) | Result |
|---|---|
| `tests/Php/Conformance/Php86PartialApplicationTest.php` | PASS: 61 tests, 151 assertions |
| `tests/Ebnf` | PASS: 74 tests, 155 assertions; directly includes unregistered 8.6 parsing, reference, reachability, and nullability checks |
| `tests/Repository` | 21 tests, 888 assertions; two registration-related failures described below |
| `--filter 'Php85DocumentationParityTest\|Php85SpecificationStructureTest\|PhpConformanceFixtureTest\|Php85ContextualFixtureTest\|VersionBoundaryTest'` | 1,373 tests, 2,452 assertions; one empty-boundary-assumption failure described below; all PHP 8.5 corpus and documentation cases pass |
| `rtk .audit/php85/php.exe -n tools/validate-manifest.php` | PASS: schema 1.0 and package paths |
| `rtk .audit/php85/php.exe -n tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=docs/8.6/pfa-validation.json` | PASS: 225 checks, 114 lint observations, zero failures; includes pinned source/test hashes, all expected RC4 diagnostics, protected 8.5 hashes, parity, and mapped production changes |
| `rtk php -n .audit/verify-phase3b.php` | PASS: 1,587 pre-existing files checked, only the two authorized artifacts differ; 72 implementation files, 88 local links/anchors, JSON and whitespace checked |
| `rtk git diff --check`; `rtk git diff --cached --check` | PASS |

The [machine-readable lint report](pfa-validation.json) preserves interpreter
versions and every exit code/diagnostic. The focused checker records skipped
source/interpreter checks explicitly if its corresponding options are omitted;
this run supplied all three inputs. It checks this phase's mapping, not the
complete future version-upgrade traceability/release contract.

Three existing test assumptions remain failing, without modification:

1. `testGrammarDirectoriesExactlyMatchManifestVersions`: the provisional 8.6
   directory is intentionally absent from the public manifest.
2. `testDocumentationReferencesPointToExistingVersionPaths`: new change-document
   links point to 8.6 fixtures that exist but whose version is unregistered.
3. `testBoundaryFixtureRepositoryAllowsEmptyDirectory`: the repository boundary
   directory now contains six required records instead of being empty.

These are actual suite failures, not passing release gates. The public boundary
runner remains manifest-based and cannot validate an unregistered 8.6 package.
The focused test evaluates both grammars using test-local configuration and the
same boundary metadata. No test was disabled or rewritten to hide these limits.

## Final review and next-phase boundary

The pre-edit preservation snapshot covers 1,587 existing files in grammar,
documentation, tests, tools, source, and entry points, plus the manifest, README,
instructions, and Composer configuration. Only the two intended 8.6 grammar
artifacts differ. All protected PHP 8.5 files, historical certification hashes,
validation tooling, release gates, and prior research remain unchanged.
Temporary preparation/synchronization/preservation drivers remain in ignored
`.audit/`; tracked additions are confined to PHP86-001 artifacts and evidence.
Git ownership restrictions required read-only Git inspection outside the
restricted runner; no Git configuration or staging was changed.

PHP86-013, PHP86-014, and PHP86-015 retain their open dispositions; all other
findings retain their earlier classifications. The declared structural PFA scope
is ready for review, and further independently verified edits can be assessed
under Gate B. Full PFA runtime/optimizer integration, complete implementation,
discovery completion, and final certification remain **NOT READY**. No next-phase
work has begun, and PHP 8.6 remains unregistered and unsupported.
