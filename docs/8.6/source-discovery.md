# PHP 8.6 Phase 2 source discovery

For the subsequent copying-policy adjustment, see the
[Phase 2.3 readiness decision](implementation-plan.md#phase-23-readiness-decision--october-23-2026).
Earlier baseline prohibitions below are historical; discovery remains open.

For the current assessment, see the
[Phase 2.2 risk review](discovery-risk-assessment.md). Original and Phase 2.1
evidence below remains historical; the source baselines are unchanged.

Research date: October 22, 2026. This is a prerelease research record, not a
grammar package, release certification, or authorization to begin Phase 3.
The repository baseline is `796da52c86a742dd82227d312df22452aefdec42`.

## Baselines

| Version | Branch / tag | Immutable commit |
|---|---|---|
| PHP 8.5 normative baseline | `PHP-8.5` | `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` |
| PHP 8.6.0RC4 | `PHP-8.6`, `php-8.6.0RC4` | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |

The existing [8.5 lock](../../tools/8.5/source-lock.json) remains authoritative.
RC4's commit timestamp is `2026-10-21T14:39:23Z`; its annotated tag object is
`2363aac87f03893686ee28db710ed2ca11cf701e`. GitHub reported valid signatures;
this investigation did not independently verify them with GPG.

The [official release schedule](https://wiki.php.net/todo/php86) and
[PHP homepage](https://www.php.net/) identified RC4 as released October 21,
ahead of the previously scheduled October 22 date. RC5 (November 5) and final
8.6.0 (November 19) were planned, not published baselines at review time.
These live pages are discovery evidence; the immutable tag/commit defines the
comparison. Review later RCs incrementally and do not claim final verification.

| File | PHP 8.5 SHA-256 | PHP 8.6 RC4 SHA-256 |
|---|---|---|
| `Zend/zend_language_parser.y` | `f108f2c2ff4c98c68b6478e7fbdebc16d8afb8bbbc3ae6079e5b285b448dac0c` | `2daed30b2d5d3286c72d4b4794950a977263201213f876f5761dc4a068bd8235` |
| `Zend/zend_language_scanner.l` | `8dfed84b0f6cabe73f004ea00c69614ea84542130a9b97e7699f801ef7ce7403` | `fecd6a22465408397133e6fe3357ed7c598b57d17e3f9886a0935896881fda39` |
| `Zend/zend_compile.c` | `25190ee20f2660aa66577f5da71b43be63268e90fa7c0ecb2e3cf8f1e621b225` | `377701d28fa678b41b2d8ca7ffeeb34d9f6e1b7aa953830d7f8eec57f34f34cd` |

[Source evidence](source-evidence.json) records 168 selected core-file hash
pairs, archive hashes, executable hashes, and source-tree counts. These hashes
identify research inputs; they are not a new package source lock. Sources were
retrieved from `https://api.github.com/repos/php/php-src/tarball/<commit>`.
Selected extracted-file hashes were checked against the archive inventory.

## Interpreters and probes

The existing `.audit/php85/php.exe` is PHP 8.5.10, NTS, VS2022 x64, built
August 25, 2026. It is not a build of the normative pin. The existing
[source correspondence](../8.5/source-correspondence.json) describes its
seven-file comparison and scanner preprocessing difference; that bounded
comparison does not establish exact executable provenance.

The official Windows QA archive
`https://windows.php.net/downloads/qa/php-8.6.0RC4-nts-Win32-vs18-x64.zip`
provided `.audit/php86/php.exe`: PHP 8.6.0RC4, NTS, Visual C++ 2026 x64,
built October 21 at 15:12:05. Its reported release matches the selected tag;
the binary was not independently rebuilt from the pin. Hashes identify both
downloaded archive and executable, without claiming reproducible-build proof.

[Syntax probes](syntax-probes.json) initially preserved 54 temporary sources and both
observations (108 lint invocations), including exit codes and diagnostics.
The command for each interpreter and file was:

```text
rtk proxy .audit/php85/php.exe -n -d short_open_tag=1 -d zend.multibyte=0 -d error_reporting=-1 -l <temporary-file>
rtk proxy .audit/php86/php.exe -n -d short_open_tag=1 -d zend.multibyte=0 -d error_reporting=-1 -l <temporary-file>
```

The research driver invoked the same executable argument vectors through PHP
`proc_open`. Sources stayed in ignored `.audit/`; the JSON is an observation
record, not registered conformance fixtures. No probe programs were executed.
Lint does not prove that deferred class linking, attribute checks, or runtime
operations succeed. Qualified `namespace\static` inside a class crashed the
8.5.10 oracle (exit -1073741819); this is not evidence of valid 8.5 syntax.

## Method and coverage

Reviewed the repository instructions, versioning §§14–17, source and conformance
policies, conventions, 8.5 EBNF/specification, source lock, reconciliation and
completeness limitations. Compared full parser/scanner diffs and compiler
hunks, followed AST/interface changes into attributes, inheritance and enums,
and examined NEWS/UPGRADING Core sections and compiler commit history.
Temporary research drivers were PHP; no repository tooling was changed.

The source archives contain 26,532 and 28,445 files, with 7,971 differing paths.
This is a discovery universe, not a count of reviewed semantic changes.
[The comparison](source-comparison.md) distinguishes reviewed components from
remaining work. The [RFC record](rfc-review.json) captures 58 candidate pages,
reported statuses, target excerpts, implementation links and snapshot hashes.
Live wiki status is not treated as proof of implementation. The 27 entries
listed under implemented PHP 8.6 include runtime/library changes; their index
placement alone does not certify their individual integration.

## Disposition and readiness

Verified findings have stable IDs in the [inventory](language-change-inventory.md).
Remaining audit work is explicit in PHP86-013 and the
[implementation plan](implementation-plan.md). In particular, the entire
changed upstream test corpus and all compiler/optimizer acceptance interactions
have not been individually reconciled. Library RFC implementation links also
need a complete per-proposal selected-pin reconciliation.

**Phase 2 is not certified exhaustive; Phase 3 readiness is withheld pending
those checks and review.** No PHP 8.6 grammar, registration, fixtures, validation
implementation, or `changes.md` has been created. The evidence supports the
identified changes, not a claim that no other contextual changes exist.

## Repository checks

Checks executed with the existing PHP 8.5.10 executable:

| Check | Result |
|---|---|
| `tools/validate-manifest.php` | PASS, schema 1.0 and package paths |
| PHPUnit `tests/Repository` | PASS, 21 tests / 885 assertions |
| PHPUnit `tests/Ebnf` | PASS, 73 tests / 153 assertions |
| PHPUnit filter `Php85DocumentationParityTest\|Php85SpecificationStructureTest` in `tests/Php/Conformance` | PASS, 4 tests / 65 assertions |
| Temporary PHP research-artifact check | PASS, four JSON files decoded, 16 local links resolved, 54 paired observations checked, three baseline hashes matched the existing source lock |
| `git diff --check` and explicit whitespace check of new Markdown | PASS |
| `git diff --name-only HEAD` / `git status --short` | No tracked-file changes; only untracked `docs/8.6/` research artifacts |

Initial PHPUnit attempts lacked `mbstring`. Re-running with
`-d extension_dir=.audit/php85/ext -d extension=mbstring` succeeded; no installed
configuration was edited. These checks validate repository integrity and the
existing package, not PHP 8.6 conformance. No certification report was refreshed.

## Phase 2.1 follow-up (October 23, 2026)

The [closure assessment](discovery-closure.md) records an **incomplete audit and
NOT READY** decision. PHP86-013 remains open. All 341 recorded artifact and
selected-file hash checks passed before comparison; neither source pin changed.
The refreshed official schedule still announces RC4 on October 21 and schedules
RC5 for November 5. Its snapshot hash is recorded in `source-evidence.json`.

The expanded [PHPT ledger](upstream-test-reconciliation.json) indexes 5,989
changed tests, including 1,124 Zend and 198 opcache tests. Only 127 records have
received the documented focused or homogeneous-delta examination: 40 are
resolved, 87 retain external/generated-input obligations, and 5,862 remain
merely indexed. These are not passing-test or completeness counts.

Forty-one additional paired probes bring the evidence to 95 sources and 190
observations. New records distinguish lint, declaration linking, and controlled
runtime checks. Only the short sources preserved in those records were executed;
no upstream PHPT suite was executed. PHP86-014 records a newly observed
lint/runtime disagreement for assignment-based dimension writes. The executable
provenance limitations above still apply.

All 58 RFC snapshots were refreshed without content changes. Primary candidate
implementation ancestry was verified for 28 of 30 accepted/implemented
candidates; this does not finish per-component integration or exclusion review.
The raw compiler ledger inventories 578 hunks in eight files, with 23 examined
hunks linked to focused review groups. Remaining hunks and other source paths
are not silently classified as irrelevant.

## Phase 2.2 follow-up (October 23, 2026)

The risk report and machine-readable companion preserve bounded exclusions
and independent baseline/implementation/certification decisions. PHP86-013
remains open. PHP86-014 is bounded to assignment-based writable contexts;
PHP86-015 adds the printf traversal discrepancy. Material source/test
association, compiler/linking interactions and RFC index review remain
unfinished, so this is not discovery closure.

Twenty additional lint-only sources bring the probe ledger to 115 sources and
230 paired observations. Their command uses the same explicit executables,
`-n -d error_reporting=-1 -l`; no new runtime probes or upstream PHPTs were
executed. Source-level VM evidence explains PHP86-014 but does not establish
an exact-pin executable or intended syntax. The official GH20628 PR metadata
was fetched and hashed; no new baseline or final-release claim was introduced.

Current JSON `phase22` counts distinguish individual review from grouped
exclusion. Low-risk test exclusions have runner/helper/generated-input evidence;
mechanical C exclusions use restricted token equivalence. API-only proposal
scope exclusions do not require full library implementation certification.
Historical status/count fields remain intact for comparison.
