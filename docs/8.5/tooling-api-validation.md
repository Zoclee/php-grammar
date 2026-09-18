# Tooling API validation report

The complete PHPUnit suite passes on PHP 8.5.10: **5,200 tests / 50,048
assertions**. All **27 release gates are verified**: the 24 existing gates plus
manifest schema validation, manifest regressions, and the focused consumer
contract gate. No grammar expectations changed.

The main runner finished 26/27 because its early PHPUnit execution found a stale
source digest. After refreshing the dependent evidence records, a separate full
PHPUnit run passed. The original runner results remain in
`.audit/consumer-validation/results.json`, the successful rerun in
`phpunit-final.log`, and the consolidated results in `final-results.json` in the
same directory. Two failed PHPUnit attempts detected the lexical ledger and
its inherited Phase 6 hash respectively; neither assertion was weakened.

## Release gates

| Gates | Final result |
| --- | --- |
| composer-validate | PASS |
| manifest-schema, manifest-regressions, consumer-contract | PASS |
| expression-generation | PASS |
| phpunit | PASS after evidence refresh; 5,200 tests / 50,048 assertions |
| grammar-coverage, lexer-coverage | PASS |
| ordinary-differential, explicit-ast | PASS |
| systematic-structure | PASS; 15,342 cases, zero mismatches |
| declaration-folding | PASS |
| phase6-matrices, phase6-matrix-freshness | PASS |
| scanner-product | PASS |
| lexer-short-enabled, lexer-short-disabled | PASS |
| positive-report-freshness, negative-report-freshness | PASS |
| compiler-source-hashes, parser-reconciliation | PASS |
| phase6-evidence-freshness | PASS |
| interpolation-binding, diagnostic-predicates | PASS |
| source-correspondence, final-certification | PASS |
| diff-whitespace | PASS |

## Delivered consumer surfaces

The [surface inventory](tooling-api-usability-audit.md) classifies canonical
artifacts, manifest, schema, runtime/parser/matcher/token APIs, CLI, generated
indexes, coverage/source evidence, Composer layout and internal test machinery.
The [API contract](../api.md), [usage guide](../usage.md), README quick start and
expanded existing versioning policy define what consumers may depend on.

Added APIs: manifest `hasVersion`, `latestVersion`, `lexicalPrimitive`; package
status/source/profile/metadata fields; grammar `production`, `productionNames`,
`referencesTo`; repository `productionIndex`; index `sections`, `rulesInSection`,
`sectionForRule`. Existing loading and matching signatures remain. Unknown
version/rule and malformed-manifest diagnostics now identify the failing input.

The manifest retains `versions` as an array and its existing paths/names.
Additions are schema `1.0`, per-version status/profile/source pin and optional
evidence paths, plus descriptive byte primitive definitions. JSON Schema accepts
legacy unversioned manifests; the runtime retains legacy defaults. Invalid path
forms now require package-relative forward-slash paths. No aliases are restored;
the four earlier interface/enum member selectors have explicit migration guidance.

`bin/php-grammar` supplies JSON `versions`, `rules`, `rule`, `refs`, `sections`,
and `section` commands. Composer now identifies the package as a library,
installs the CLI, and excludes local scratch/cache/vendor directories from its
archive. Manifest validation is implemented in PHP without additional dependencies.

## Executable integration checks

`ConsumerWorkflowTest` and `ConsumerCliTest` exercise public discovery, loading,
production enumeration/lookup, valid and invalid expression matching, complete
source matching, primitive/section queries, reverse references, numeric version
ordering, legacy manifests, and deterministic unknown selections/CLI exits.
The focused run passed **12 tests / 812 assertions** on PHP 8.4.22 and PHP 8.5.10.
The JSON Schema regression suite passed **3 original regression groups**, including malformed
fields/paths, version/schema mismatches, duplicate entries and primitive metadata.

`tools/check-consumer-package.php` created a Composer ZIP, checked required public
artifacts, installed it through a local path repository into an isolated project,
and ran loading/matching/section queries and the installed Composer CLI without
development dependencies. All checks passed. The schema, EBNF, specification,
manifest, runtime, CLI and consumer documentation were present; local audit,
cache and vendor files were absent. No remote publishing or release was performed.

Reproduce the consumer checks in a development checkout:

```sh
php vendor/phpunit/phpunit/phpunit --filter Consumer --no-progress
php tools/validate-manifest.php
php tools/test-manifest.php
php tools/check-consumer-package.php
```

On Windows, the package check can take
`--composer C:/ProgramData/ComposerSetup/bin/composer.phar`. The release runner
accepts `--php` and `--composer`; configure `PHPRC` for PHP 8.5/ext-ast and set
`COMPOSER_PROCESS_TIMEOUT=0` for the complete suite.

## Preserved language and evidence

The final invariant comparison, after all release generators ran, confirmed:

- canonical EBNF byte-identical, therefore all 360 names and production bodies unchanged;
- full specification byte-identical;
- all fixture bytes and classifications unchanged, including 1,371 PHP fixtures;
- all 18 behavioral reports equivalent, preserving list order and all non-digest data;
- all 15,342 operator/statement matrix cases rerun with zero mismatches and the artifact unchanged;
- EBNF/Markdown synchronization check passes.

Only digest fields in `phase5-lexical-evidence.json`, `phase6-evidence.json`, and `final-evidence.json`
changed. The diagnostic improvement changed the matcher source hash; new API,
CLI and maintainer files also enter the existing final evidence input inventory.
The initial PHPUnit run detected the stale lexical ledger. It was regenerated
using the existing report builder, followed by its dependent Phase 6 evidence
and certification generators;
comparisons verified that no behavioral evidence changed. Checks are rerun on
the refreshed evidence, without weakening freshness assertions.

The conformance scope remains lexical/source processing plus structural grammar
with explicitly external contextual constraints. This phase adds no compiler,
AST API, grammar versions, language features or fixture expectations.

The preserved canonical file SHA-256 values are:

```text
grammar/8.5/php.ebnf  7bede7421acf06152892210cc2a53c17628e88402057edf0a50a76cc11fb0a0c
grammar/8.5/php.md    9f8c381e406ebe41f19612de6dd0ff84d413c0b61b31333de475b5c9f11b092b
```

Local invariant details are retained in `.audit/consumer-invariants.json`.
Evidence comparison excludes only top-level digest dictionaries and preserves
every other field and list order. These local logs are working evidence, not a
new public runtime dependency.
