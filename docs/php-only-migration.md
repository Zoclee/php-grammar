# PHP tooling migration

## Inventory before implementation changes

The maintained repository contained these eleven Python entry points. Each is
replaced by the PHP entry point in the same directory:

| Previous file | Replacement | Contract |
|---|---|---|
| `tools/validate-manifest.py` | `tools/validate-manifest.php` | Optional manifest path; schema and package validation; exit 0/1 |
| `tools/test-manifest.py` | `tools/test-manifest.php` | Three manifest regression groups; nonzero on failure |
| `tools/check-consumer-package.py` | `tools/check-consumer-package.php` | `--php`, `--composer`; isolated archive/install smoke test |
| `tools/grammar-release.py` | `tools/grammar-release.php` | Runtime/source/log options; all 27 gates, durable logs and results |
| `tools/8.5/fetch-sources.py` | `tools/8.5/fetch-sources.php` | Destination; download three pinned source files |
| `tools/8.5/source-inventory.py` | `tools/8.5/source-inventory.php` | Source directory; verify hashes and generate inventory |
| `tools/8.5/compiler-boundaries.py` | `tools/8.5/compiler-boundaries.php` | Source directory, `--check`; classify direct diagnostics |
| `tools/8.5/reconcile.py` | `tools/8.5/reconcile.php` | Source directory, `--check`; parser alternatives and anchors |
| `tools/8.5/phase6-evidence.py` | `tools/8.5/phase6-evidence.php` | `--check`; restriction/history/blocker report |
| `tools/8.5/source-correspondence.py` | `tools/8.5/source-correspondence.php` | `--cache`, `--fetch`, `--check`; seven immutable source comparisons |
| `tools/8.5/certification.py` | `tools/8.5/certification.php` | `--source-cache`, `--check`; evidence, diagnostic and Markdown reports |

`tools/requirements.txt` declared `jsonschema`. `.gitignore` contained a bytecode
cache exclusion. Three Composer scripts invoked Python. README, API/usage,
conformance/release documentation and generated evidence contained old commands
or tool paths. No tracked CI workflows or other non-PHP implementation languages
were found. Grammar fixtures and vendored PHP dependencies are separate from
maintainer implementation code.

The ignored `.audit` directory also contained 164 disposable historical Python
scripts and old checkout copies, plus two PowerShell scratch scripts. A full local inventory and original tool/report
snapshots were saved outside the repository at
`C:/Data/php-grammar-migration` before implementation changes. These scratch
scripts are not release inputs or supported maintenance entry points.

The historical scratch scripts and checkout copies were moved to that external
archive, preserving them without shipping or maintaining obsolete workflows.
The local PHP distribution and immutable upstream C/Bison/re2c evidence remain
in the ignored cache. These are runtime infrastructure and audited source data,
not project implementations. Composer-generated platform launchers in `vendor`
are dependency infrastructure.
Five JavaScript files under PHPUnit's vendored HTML coverage templates are
third-party browser presentation assets. They are not maintained project logic,
and generating reports or running any repository workflow requires no JavaScript
runtime. They remain unmodified with the Composer dependency.

## Compatibility decisions

Canonical grammars, specifications and fixtures must retain their original bytes.
Generated JSON retains object/array distinctions, insertion order, two-space
indentation and audited CRLF bytes. Tool paths and their dependent SHA-256 hashes
necessarily change. Static reviewed policy tables are stored separately from
the PHP algorithms; they are inputs, not cached generated answers.

CLI argument errors use exit 2; operational/validation failures use exit 1.
Documented argument names and generation/check modes are unchanged. Help and
argument-error prose use the shared PHP parser rather than runtime-library text.
Manifest schema diagnostics may use PHP-native wording while preserving JSON
paths and validation decisions. Test-runner timing/format is not a stable API.
Console summaries preserve their text and use LF; committed report bytes retain
their original serialization independently of the host platform.
Composer and Git remain package/repository infrastructure; PHP implements the
project workflows. RTK is an optional interactive command wrapper, not a runtime
dependency of the maintained tools.

## Shared implementation and regression coverage

`tools/lib/Support.php` supplies strict CLI parsing, deterministic report
serialization, immutable source hashes, downloads and shell-free process calls.
`ParserReconciliation.php` handles Bison branches and reviewed production anchors.
`UnifiedDiff.php` preserves source comparison matching, popular-line suppression,
context grouping and range formatting. `ManifestValidator.php` evaluates every
keyword used by the bundled manifest schema and checks resolved package paths;
it is not advertised as a general-purpose JSON Schema library.

The three `tools/8.5/data/*.json` files preserve the original reviewed anchors,
restriction inventories, exclusions and historical classifications. Generators
recompute all source/evidence-dependent results. Reconciliation and evidence
hashes now cover their shared code and policy inputs as well as entry points.

`ToolManifestTest` ports all three original regression groups and adds object/array,
empty-string, naming and package-directory cases. `ToolingMigrationTest` covers
Bison quoting/actions/comments, malformed actions, diff context/ranges/popular
lines, serialization, CLI errors, subprocess failures and six certification
failure scenarios. No existing gate or test was removed or relaxed.

## Behavioral comparisons

- Source inventory, compiler inventory, source correspondence and diagnostic
  dispositions are byte-identical to the saved original artifacts.
- Parser reconciliation, Phase 6 evidence and final certification have identical
  non-hash data after entry-point path substitution. Historical Markdown differs
  only in the generator filename.
- Both source-fetch implementations returned the same revision and source bytes.
- Corrupt-source probes for compiler boundaries, source inventory and parser
  reconciliation matched the original exit status and diagnostic text.
- The PHP unified diff matched 301 deterministic reference cases, including
  repeated lines, insertions, deletions and multiple hunks.
- Manifest acceptance/rejection matched the original schema validator across
  262 current, legacy, missing-field, malformed-type and invalid-value cases.
- Both consumer-package implementations passed archive verification, isolated
  Composer installation, public API matching and the installed CLI. Temporary
  artifact paths differ; optional wrapper banners are no longer emitted.
- Grammar/specification and fixture SHA-256 comparisons found no byte changes.

The maintained entry points use PHP 8.2 or later; release conformance requires
PHP 8.5 with ext-ast and PHPUnit extensions. The optional archive smoke test also
requires ext-zip. Composer/Git remain package/repository infrastructure.

## Validation results

The complete PHPUnit run on PHP 8.5.10 passed **5,211 tests and 50,112
assertions**, including 11 migration regression tests with 58 assertions.
The consumer archive/install smoke test passed after removal of the old tools.
All eight artifact comparisons passed; canonical grammar/specification/fixture
bytes remain unchanged.

Final searches, including hidden directories, found no `.py` or bytecode files
in the working repository or Git index. The only maintained textual occurrences
are this historical migration inventory. All 18 migrated/shared/test PHP files
passed syntax checks. README, contributor guidance, Composer scripts, API/usage,
conformance/release instructions and historical command examples were updated.
There was no tracked CI configuration to migrate.

The PHP release runner passed **27/27 gates**, including Composer validation,
manifest regressions, consumer contracts, PHPUnit, grammar/lexer coverage,
ordinary differential conformance, AST and systematic structure, declaration
folding, Phase 6 matrices/freshness, scanner products, both lexical profiles,
positive/negative report freshness, compiler hashes, parser reconciliation,
Phase 6 evidence, interpolation, diagnostic witnesses, source correspondence,
final certification and whitespace checks. The differential corpus had 653 valid,
362 invalid and 356 contextual-invalid cases, with zero mismatches.

Per-gate logs and machine-readable exit codes are in
`.audit/php-only-validation/`; the full original local inventory, comparison
results and archived scratch scripts are in `C:/Data/php-grammar-migration`.

**PHP-only status:** PASS
