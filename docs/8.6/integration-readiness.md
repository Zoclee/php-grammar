# PHP 8.6 Phase 4A integration readiness

Assessment date: October 24, 2026. Repository baseline:
`d63a9d206b389f5df41e49b1c7c41f5109638ee1` (initial working tree clean).
**Next-phase implementation: READY WITH BOUNDED LIMITATIONS. The current
checkout is not ready for public draft registration without the work below.**
Complete language implementation, candidate promotion, and stable certification
remain **NOT READY**. This assessment changes no policy, manifest, grammar,
fixture, test, lexer, API, or release gate. It does not begin integration.

## 1. Eligibility and the scope of the decision

[Versioning section 9](../versioning.md#9-version-status) explicitly defines
draft as “incomplete or under active construction.”
[Section 16](../versioning.md#16-prerelease-and-final-release-review) says:
“Registration occurs when the implementation supplies the required package,
fixtures, and lexer configuration, not during governance or source discovery,”
and “For an unfinished version use `draft`.” These provisions permit a functional,
explicitly incomplete draft. They do not require final discovery closure or a
certified interpreter merely to advertise that bounded package.

[Gate A](../versioning.md#31-readiness-gates) requires the unchanged provisional
copy to remain unregistered; copying alone cannot justify registration. PHP 8.6
has subsequently acquired reviewed Gate B changes and its three-file package.
Registration must therefore be a separate implementation step satisfying section
16, not a reinterpretation of the original copying permission. Gate B continues
to prohibit speculative syntax edits. AGENTS.md's implementation-completion
requirements and [section 17](../versioning.md#17-change-traceability-validation-contract)
remain mandatory: calling an integration task complete requires its structural,
parity, fixture, differential, boundary, traceability, and preservation evidence.
A registered draft is not a declaration that the entire language implementation
is complete.

The [Phase 3D review](discovery-final-review.md) explicitly said “Manifest and
tooling integration: NOT READY” and described PHP86-013 as blocking integration.
That dated assessment is retained. This assessment distinguishes a narrower
structural draft from complete integration using the existing draft policy; it
does not close PHP86-013 or claim that the Phase 3D research gaps disappeared.
The [Phase 3E report](deprecation-implementation.md) authorizes assessment only;
it is not itself evidence of completed integration.

| Claim | Eligibility and required boundary |
|---|---|
| Discoverable draft | Permitted after the registration prerequisites in sections 2–7 below. Status, RC4 provenance, incomplete discovery, and unenforced contextual constraints must be explicit. |
| Functional draft | Explicit version loading, tokenization, structural matching, CLI navigation, fixtures, and stage-aware evidence work for the declared scope. No whole-source validity promise. |
| Complete conformance integration / candidate | Not ready. Candidate means believed complete; unfinished material discovery and unexplained source-acceptance differences prevent that claim. Independent coverage, reconciliation, and validation remain necessary. |
| Certified stable specification | Not ready. Requires the bounded conformance-policy claim, independent release evidence, final-release reconciliation, and all applicable gates. Stable still does not mean a complete compiler validator or exhaustive equivalence. |

PHP86-013 may remain open for a draft only with an explicit limitation covering
unreviewed compiler/AST/optimizer, linking, PHPT, and RFC interactions; no claim
of complete RC4 coverage follows. PHP86-014 continues to block assignment-based
writable-target changes and their validity claims. PHP86-015 continues to block
generalized optimized-call traversal rules. Draft status does not turn either
observation into an intended language rule. Their affected areas must be named
in discoverable limitations, not presented as reliably validated source syntax.
No new research finding in this assessment changes their dispositions.

## 2. Manifest and source metadata

The existing [schema](../../schema/php-grammar.schema.json) supports everything
needed. Keep `schemaVersion: "1.0"`, the versions array, existing lexical primitive
definitions, and the PHP 8.5 entry unchanged. Append the following entry only
after functional prerequisites pass (the example is a plan, not registration):

```json
{
  "version": "8.6",
  "rootProduction": "source-file",
  "grammar": "grammar/8.6/php.ebnf",
  "documentation": "grammar/8.6/php.md",
  "conformanceFixtures": "tests/fixtures/php/8.6",
  "lexer": "8.6",
  "status": "draft",
  "sourceProfile": "Provisional PHP 8.6.0RC4 structural grammar; byte-oriented source, zend.multibyte=0. Short-tag profiles require version-specific validation. Contextual constraints are documented but incompletely enforced. Focused differential evidence uses distribution builds, not verified exact-pin executables; discovery and optimized-call/writable-target discrepancies remain open.",
  "phpSource": {
    "branch": "PHP-8.6",
    "commit": "f23b4c222cebe33be93ee8ecaaa14390c5507a08"
  },
  "metadata": {
    "changes": "grammar/8.6/changes.md",
    "sourceEvidence": "docs/8.6/source-evidence.json",
    "pfaSourceLock": "tools/8.6/pfa-source-lock.json",
    "contextSourceLock": "tools/8.6/context-source-lock.json",
    "pfaValidation": "docs/8.6/pfa-validation.json",
    "contextValidation": "docs/8.6/context-validation.json",
    "deprecationValidation": "docs/8.6/deprecation-validation.json",
    "deprecationCases": "tests/fixtures/php/8.6/deprecation-cases.json",
    "discoveryReview": "docs/8.6/discovery-final-review.md",
    "integrationReadiness": "docs/8.6/integration-readiness.md"
  }
}
```

Use the profile actually validated in the implementation results; do not upgrade
the planned short-tag checks into an audited claim prematurely. Metadata values
are package-relative **file paths**, not URLs, prose, arrays, or directories.
Source URLs and per-finding references belong in those linked documents.
`metadata.changes` needs no schema change. `VersionPackage::$metadata` already
exposes it; neither `GrammarRepository::load()` nor the EBNF depends on it.
The schema has no separate boundary-directory field. Existing boundary JSON
under `tests/fixtures/version-boundaries/` supplies first/last version, fixture,
and reference; any optional manifest boundary index must point to an actual file.

`tools/lib/ManifestValidator.php::validate()` checks schema vocabulary, unique
versions, primitive definitions, contained paths, files, and fixture directories.
It does **not** prove a lexer identifier is implemented or validate fixture
semantics. An in-memory proposed draft entry passed this validator and loaded
361 productions; `Lexer::forVersion('8.6')` still failed. Schema validity alone
is therefore demonstrably insufficient.

Preserve the two source baselines: PHP 8.5 branch `PHP-8.5` at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`, and PHP 8.6 branch `PHP-8.6`,
tag `php-8.6.0RC4`, at `f23b4c222cebe33be93ee8ecaaa14390c5507a08`.
The copying baseline remains `d4cd2c5446a9de7fa783533ef8d9e8f8f0fad3ee`.
These repository and upstream commits serve different purposes.

The PFA lock records five implementation files and 19 upstream tests; the context
lock records both-side implementation hashes, relevant tests, distribution
hashes, and mappings. Deprecation source/test hashes are currently in
`tests/fixtures/php/8.6/deprecation-cases.json`. Before registration, add a
version-owned `tools/8.6/source-lock.json` index/lock connecting these existing
records to the parser/scanner/compiler pin and all implemented findings; preserve
the historical locks. It must validate hashes, not merely copy identifiers or
borrow PHP 8.5 certification. Put new integration results under `docs/8.6/` and
link them when they exist; do not invent `conformance`, `scanner`, or `coverage`
metadata pointing to PHP 8.5 results or nonexistent PHP 8.6 reports.

## 3. Lexer and public interfaces

`src/Php/Lexing/PhpVersion.php::forVersion()` currently accepts only `8.5`.
Add an independent `php86()` configuration and dispatch for `8.6`, retaining
`php85()` and its behavior. An immutable shared keyword list is acceptable;
the returned version identity must be `8.6`. No external configuration file
exists or is required. `Lexer::forVersion()` and
`PhpGrammarMatcher::lexerFor()` already select the manifest lexer identifier.
They need no version-specific grammar rewrite or contextual compiler emulation.

The [PFA report](pfa-implementation.md) and Phase 3D parser/scanner comparison
identify no new token spelling, token declaration, scanner pattern/state,
precedence, or keyword reservation. `Lexer::OPERATORS` already includes `?`,
`...`, `??`, and `?->`; punctuation already includes colon and comma. PHP86-001
changes argument-list recognition, not tokenization. Preserve longest-token
matching, named-argument colons, comments, interpolation, and clone boundaries.
PHP86-010 is warning behavior: `let` and `is` must not become reserved keywords;
`readonly` contextual acceptance and the invalid function-import alias remain
distinct. Do not make deprecated-but-accepted forms lexical errors.

Reuse the byte-oriented scanning engine and reviewed 8.5 rules with new evidence,
not inherited certification. Phase 3D specifically leaves scanner error-state,
reentrant exception, ZTS, nesting, and encoding questions unresolved. Before
publishing the draft, run both short-tag profiles, heredoc/nowdoc, interpolation,
identifier/property-name contexts, PFA token boundaries, malformed inputs, and
deprecation controls through explicit `8.6` configuration. Add focused version
selection tests alongside `tests/Php/Lexing/LexerTest.php` and
`RepositoryLexerSmokeTest.php`; retain `Php85ScannerAuditTest.php` results as 8.5
evidence. Migrate the three `Php86*Test.php` in-memory packages from lexer `8.5`
to the real `8.6` selection and test the public manifest separately.

| Interface / command | Integration implication |
|---|---|
| `RepositoryManifest::versions()`, `packages()`, `package()` | Discover both versions, sorted numerically; preserve unsupported-version exceptions and legacy manifests. |
| `latestVersion()` | Contract explicitly includes drafts. It **must become `8.6`** after registration; do not silently change it to “latest stable.” |
| `GrammarRepository::load('8.6')`, `productionIndex('8.6')` | Existing code loads and validates the standalone artifact. No status-based fallback to 8.5 or runtime dependency on changes/evidence. |
| `PhpGrammarMatcher::matches()` / `matchesRule()` | Explicit version required; structural result only. Preserve `MatchResult` and error contracts. Document incomplete contextual coverage rather than fabricate definitive validity errors. |
| `bin/php-grammar versions` | Existing JSON already emits version, status, grammar, documentation. New entry must say `draft`. Preserve the array shape and existing fields. |
| `rules`, `rule`, `refs`, `sections`, `section` | Explicit `8.6` navigation should work with current code; test success, missing rule, and unsupported version exits/JSON. |
| Syntax checking | There is no public `check` CLI command. Matching is structural; `bin/php85-conformance.php` is an explicitly 8.5 maintainer oracle, not a generic syntax API. |
| `bin/grammar-coverage.php` | Default remains `8.5`; explicit 8.6 can use generic coverage, but 8.5-specific rule cases/classification must not be copied as 8.6 evidence. |
| `bin/lexer-coverage.php`, 8.5 conformance/release tools | Explicitly 8.5-specific; add independent 8.6 tooling instead of redirecting existing commands to the new draft. |

There is no global manifest default field or implicit `latest` alias in the
loader/CLI. Retain PHP 8.5 as the recommended stable/default example and the
existing maintainer command default. Applications wanting the latest stable
package should filter `packages()` by status; changing `latestVersion()` would
break the [public API contract](../api.md). Update `docs/api.md`, `docs/usage.md`,
and README's single-advertised-version statements during implementation. Explain
draft versus stable and the three lexical/structural/contextual layers. Optional
additive CLI metadata is unnecessary for minimum integration: consumers already
have `VersionPackage::$metadata` and the manifest. No public signature change,
new schema, or blanket “unsupported draft” exception is needed.

## 4. Existing failures and additional integration traps

The following failures were reproduced, not inferred from earlier reports.

| Test and current failure | Root cause | Planned correction without weakening the test |
|---|---|---|
| `VersionPackageConsistencyTest::testGrammarDirectoriesExactlyMatchManifestVersions`, line 53 | Disk contains 8.5 and 8.6; manifest advertises only 8.5. | Register the fully prepared draft. Keep exact directory/manifest equality; no ignore rule for provisional directories. |
| `VersionPackageConsistencyTest::testDocumentationReferencesPointToExistingVersionPaths`, line 91 | `grammar/8.6/changes.md` references existing 8.6 fixture paths; the version membership assertion fails first. | Register 8.6 and verify every matched path still exists. Keep the unsupported-version/path checks. The valid/invalid directories already exist; empty-directory creation is not the missing fix. |
| `VersionBoundaryTest::testBoundaryFixtureRepositoryAllowsEmptyDirectory`, line 45 | The test loads the real repository and asserts `[]`, but it now contains six PFA and seven contextual records. | Retain the empty-directory test using a genuinely empty temporary directory with cleanup. Add a separate real-corpus discovery test asserting all required records, fixture paths, pins, and stage expectations. Registration cannot make this test's empty-array expectation correct. |

Registration will also expose currently passing assumptions:
`RepositoryManifestTest::testLoadsSupportedVersionsFromManifest` (line 17),
`ConsumerWorkflowTest::testDiscoverLoadInspectAndMatch` (versions equals only
8.5, `hasVersion('8.6')` false, latest equals 8.5), and
`VersionBoundaryTest::testBoundaryRunnerHandlesSingleVersionRepository`
(hard-coded `['8.5' => true]`). Update repository expectations to both versions
and explicit statuses/latest semantics; retain single-version behavior in an
isolated one-package manifest test. Do not replace exact assertions with
nonempty checks. Add two-version CLI/API and wrong-lexer regressions.

`VersionBoundaryFixtureRepository::load()` already recursively discovers JSON.
`VersionBoundaryCase::fromArray()` currently discards `validationStage` and
`finding`; `VersionBoundaryRunner::evaluate()` only calls the structural matcher,
while `expected()` uses version ranges. Consequently, the seven contextual
records cannot be judged by comparing these two arrays: both EBNFs accept the
forms, but their lint/declaration-link outcomes differ. Examples include the
8.6-only enum `__debugInfo` declaration and the 8.5-only nullsafe reference
return. No lexer edit will fix this mismatch.

Preserve structural runner semantics for legacy records. Add validated stage
metadata (default structural), retain finding identity, and provide a separate
stage-aware evidence/oracle runner under `tools/8.6/` for lint and declaration
linking. A structural-only route must reject an inappropriate stage clearly,
not silently skip it or count structural acceptance as compiler acceptance.
Every real boundary record must appear in the combined report. Reuse the
controlled stage expectations from `context-cases.json` and `context-check.php`;
do not execute arbitrary fixture programs as a side effect of grammar loading.
Deprecation witnesses need acceptance plus diagnostic-stage evidence, not false
8.5/8.6 structural rejection ranges. Keep runtime-only failures separate.

## 5. Conformance and change-document integration

The current 8.6 corpus contains 71 top-level valid PHP files, eight structural
invalids, 40 contextual invalids, and one runtime-only file. PFA has 57 case
records; contextual review has 63; deprecation review has 67 inline witnesses.
These are different representations, not interchangeable coverage totals.
`PhpConformanceFixtureTest` discovers top-level valid/invalid files for every
manifest package automatically. `Php85ContextualFixtureTest` is explicitly 8.5;
8.6 contextual checks must continue through their own stage-aware suite.
Inline deprecation cases require their provider/checker; ordinary glob discovery
will not run them. Neither existing corpus counts nor successful focused tests
establish full historical-syntax coverage.

Minimum functional integration must add representative unchanged-syntax checks
for PHP 8.6: declarations, expressions/precedence, types, hooks, namespaces,
attributes, strings/interpolation, short tags, clone/callable and folding
boundaries. Use independent 8.6 expectations, with positive and nearby negative
controls. Keep PHP 8.5 fixtures byte-identical. An expanded compatibility corpus
may reuse fixture methodology, never historical verdicts or coverage statistics.
Continue paired PHP 8.5.10 / 8.6.0RC4 checks with explicit binary hashes,
configuration, lint/link/runtime separation, and exact-pin limitations.

The [change document](../../grammar/8.6/changes.md) has all ten policy headings,
in order, and supplies the copying baseline, both source pins, RC4 status,
profile, source/fixture references, exclusions, and outstanding obligations.
Its five modified productions are `ordinary-argument-list`,
`first-class-callable-arguments`, `clone-argument-list`, `argument`, and
`argument-no-expression`; no production is added, removed, or renamed.
PHP86-003–007 and PHP86-010 are prose/contextual or diagnostic changes without
further EBNF edits. Existing focused tests verify generated EBNF parity.
Ten-section compliance is not complete traceability certification: parent-hook
PFA, wider initializer interactions, lexer integration, contextual enforcement,
and broader discovery remain explicitly outstanding. Update the “unregistered”
status and integration-result references only when registration actually occurs.
Retain the provisional/incomplete label and distinguish inherited 8.5 audit text.

Implement the section 17 validator through a PHP entry point, proposed
`tools/8.6/change-traceability.php`, using `Ebnf\Parser`, the existing node types,
`GrammarValidator`, and PHP reporting utilities. Compare the recorded immutable
8.5 baseline with the standalone 8.6 package by production name and node
structure; report additions, removals, alternatives, optionality, repetition,
terminals, and references. Track textual/prose/lexical/contextual changes too.
Map each material difference to ID, pinned source, fixture/stage/boundary result,
or an explicit unfulfilled obligation. Fail on unexplained differences, missing
headings, stale production/fixture references, or missing required evidence.
Report approved bounded limitations separately; do not automatically pass an
unresolved item because it has an ID. Require human review of non-EBNF mappings.
Run this before calling functional draft integration complete; do not defer the
entire contract to stable status. Existing PFA/context checkers cover focused
parts of this contract but are not its general implementation.

## 6. Requirement classification and minimum publishable draft

Each row's classification is its earliest mandatory gate. Requirements continue
to apply at subsequent stages; the functional rows must be delivered in the same
integration change before the draft becomes publicly discoverable.

| Requirement / concrete work | Classification |
|---|---|
| Three standalone artifacts, ten-section change document, draft/RC4 labels, source profile and PHP86-013/014/015 limitations | **Required before draft registration** |
| Schema 1.0 entry, real metadata paths, immutable pin, version-owned lock/index with validated hashes and executable provenance | **Required before draft registration** |
| Real `PhpVersion::php86()` selection and bounded lexer compatibility evidence; no 8.5 identity masquerading as 8.6 | **Required before draft registration** |
| Populated positive/structural-negative corpus and stage-specific contextual evidence; no fabricated certification metadata | **Required before draft registration** |
| Explicit API/CLI load, rule match/navigation, version/status listing, defaults/latest compatibility, documented structural limits | **Required for functional draft integration** |
| Resolve all three failures and newly exposed single-version assumptions; preserve isolated empty/single-version tests | **Required for functional draft integration** |
| Discover all 13 boundaries and distinguish structural, lint, declaration-link, diagnostic, and runtime outcomes | **Required for functional draft integration** |
| Structural/reference/reachability/nullability validation, parity/prose review, both-version regression, both short-tag profiles, representative unchanged syntax, paired differential results | **Required for functional draft integration** |
| Section 17 production/textual change reconciliation and explicit unresolved obligations; preservation snapshot and fresh integration report | **Required for functional draft integration** |
| Complete material source/PHPT/RFC discovery; independently justified disposition of all known language discrepancies | **Required before candidate status** |
| Version-specific classified grammar/scanner coverage, parser/compiler reconciliation, contextual inventory and stage matrices; source-lock freshness and independent runner/evidence | **Required before candidate status** |
| Subsequent RC reviews, final-release pin and delta reconciliation, source/executable correspondence and full applicable release/differential gates | **Required before stable certification** |
| PHP86-014 intended writable-target boundary and source-corresponding execution; no speculative expansion | **Blocked by unresolved research** |
| PHP86-015/011 optimized-call traversal boundary and relevant dispatch/folding/optimizer profiles | **Blocked by unresolved research** |
| PHP86-013 remaining compiler/AST/linking/test-family/RFC associations; no completeness or blanket lexical-equivalence claim | **Blocked by unresolved research** |

This minimum supplies all ten requested capabilities: valid registration,
standalone loading, correct lexer identity, explicit API/CLI selection, basic
positive/negative coverage, correct boundary discovery, provisional status,
provenance/limitations, documentation/change metadata, and passing repository
integrity tests. It does not require a complete contextual validator, exhaustive
equivalence, or stable certification. Missing required bounded integration
evidence is still a blocker to publishing the draft.

## 7. Release infrastructure and historical evidence

`tools/grammar-release.php` currently orchestrates 27 gates for PHP 8.5, including
global PHPUnit, coverage, scanner, differential, reconciliation, correspondence,
and certification checks. Do not redirect it to RC4, delete gates, suppress
failures for draft status, or copy its 8.5 report totals. A future
`tools/8.6/certification.php` and version-specific runner must supply independent
evidence in the conformance policy's nine categories, logs, freshness checks,
and blocker dispositions. The focused draft integration runner should clearly
identify its smaller scope and must not be named a successful full release run.

There is an additional maintenance dependency beyond the three test failures:
`tools/8.5/certification.php` builds its final input list by recursively enumerating
**all** `src`, `tests`, `grammar`, `bin`, and `tools` files, plus policy inputs.
New version files and shared integration edits therefore change the expected
`docs/8.5/final-evidence.json` input set even if historical 8.5 grammar and fixture
bytes remain unchanged. Preserving that historical evidence and blindly
regenerating its current-tree freshness output are incompatible operations.
This assessment does not run or claim that freshness gate passed.

Before a repository release, resolve this explicitly in release-maintenance
work: preserve the original historical certification and reproduce its original
input set at its recorded revision, while adding a separately stored current-tree
8.5 regression attestation that includes changed shared code and tests. Retain
the substantive checks and freshness guarantees for both scopes; do not narrow
hash coverage silently or overwrite the historical evidence. Review that design
under the existing preservation/conformance policy before modifying orchestration.
If it cannot retain every applicable gate, obtain an explicit governance decision;
this plan supplies no waiver. A draft integration commit can be assessed within
its bounded scope, but cannot be called a passing repository release while the
release-gate conflict or any gate failure remains.

## 8. Proposed implementation sequence

1. Record the new implementation baseline and protected-file hashes. Recheck
   existing pins and all focused locks. Record the structural draft scope and
   unresolved-area limitations before making public claims.
2. Implement `PhpVersion::php86()` and version dispatch. Test explicit version
   identity, both tag profiles, existing lexical contexts, PFA, and deprecation
   boundaries while preserving 8.5 behavior.
3. Prepare the source-lock index and focused integration evidence. Extend
   compatibility witnesses and section 17 traceability validation. Keep the
   public manifest unchanged while these dependencies are incomplete.
4. Implement stage-preserving boundary metadata and the separate oracle/evidence
   runner. Retain empty-directory and single-version unit coverage; add real
   corpus and two-version tests with no silent stage skips.
5. Prepare the draft manifest entry and status/documentation updates together.
   Update exact consumer/discovery expectations and exercise all public commands,
   structural matching, errors, source metadata, and latest/default semantics.
   Keep PHP 8.5 stable and all 8.5 artifacts/evidence unchanged.
6. Run all applicable repository, EBNF, lexer, parity, conformance, differential,
   boundary, source-lock, and change-traceability checks. Retain commands/results,
   preserve known research limitations, and verify the protected snapshot. Only
   then expose the manifest entry as a functional draft.
7. Continue research and full conformance integration separately. Resolve the
   historical/current release-evidence dependency before claiming release gates
   pass; add independent 8.6 reconciliation, classified coverage, and runner.
   Promote to candidate only when believed complete, and to stable only after
   final-release review and the applicable evidence gates.

No policy change is needed simply to allow an honestly scoped functional draft.
Research resolution is required before expanding the blocked syntax claims or
claiming candidate completeness. Registration prerequisites and release evidence
must not be confused with permission to speculate about those areas.

## 9. Validation performed and preservation

Commands ran from the repository root. Let `P` denote
`rtk .audit/php85/php.exe -n`; let `U` denote
`rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result`.
The PHP executables are distribution PHP 8.5.10 and PHP 8.6.0RC4, not verified
exact-pin builds. Existing focused checks use their recorded isolated lint,
declaration-link, and controlled-runtime profiles. No upstream PHPT suite,
new research probe of PHP86-014, full release runner, or final certification ran.

| Actual command (prefix as defined above) | Result |
|---|---|
| `P tools/validate-manifest.php` | PASS: existing schema 1.0 and package paths; does not validate a registered 8.6 package because none exists. |
| `U tests/Repository` | 21 tests, 888 assertions, two failures identified in section 4. |
| `U tests/Ebnf` | PASS: 74 tests, 155 assertions, including unregistered 8.6 integrity. |
| `U --filter 'Php86\|Php85DocumentationParityTest\|Php85SpecificationStructureTest\|PhpConformanceFixtureTest\|Php85ContextualFixtureTest\|VersionBoundaryTest'` | 1,697 tests, 4,704 assertions; one empty-boundary-assumption failure. All selected 8.6 focused, 8.5 corpus, and parity cases passed. |
| `U --no-progress tests/Php/Lexing` | PASS: 2,980 tests, 42,173 assertions; existing 8.5 lexical suite, not independent 8.6 certification. |
| `P tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2` | PASS: 225 checks, 114 paired lint observations, zero failures; source/test hashes and parity included. |
| `P tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2` | PASS: 278 checks, 126 paired stage observations, zero failures; source/executable hashes included. |
| `P tools/8.6/deprecation-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=.audit/phase4a-deprecation.json` | PASS: 712 checks, 268 observations, zero failures. Only ignored scratch report written; historical report unchanged. |
| `P tools/test-manifest.php` | Could not run its child PHPUnit: child invocation does not inherit the explicit mbstring option. |
| `U --no-progress --filter ToolManifestTest` | PASS: direct equivalent test selection, four tests, 29 assertions. Wrapper environment limitation remains recorded. |
| `P .audit/phase4a-review.php inspect` | In-memory schema/path validation passed; both versions discovered, latest 8.6, standalone load 361 productions; absent 8.6 lexer reproduced; ten change headings and corpus counts verified. No manifest write. |
| `rtk proxy .audit/php85/php.exe -n .audit/phase4a-review.php preserve` | PASS: 1,742 pre-existing file hashes, zero changes. |
| `P .audit/phase4a-review.php links` | PASS: ten local links/anchors and no trailing whitespace. |

The initial scratch inspection needed an explicit require of the validator class;
that scratch-only loading error was corrected before the successful inspection.
Scratch PHP checks are assessment aids, not new tracked validation tooling.
The selected PHPUnit groups total **4,776 tests, 47,949 assertions, three failures**;
the full PHPUnit suite was not run. Source checks cover the focused locks and
deprecation records supplied to the existing checkers, not a new exhaustive
source audit. External web links were not refetched; immutable local source
hashes and preserved research evidence ground this assessment.

Preservation verification covers 1,742 pre-existing files across grammar,
documentation, fixtures/tests, tooling, source, entry points, schema, manifest,
instructions, and Composer configuration. The protected PHP 8.5 EBNF SHA-256
remains `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb`;
the specification remains
`bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f`.
The PHP 8.6 EBNF remains
`12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4`.
PHP 8.5 fixtures and certification evidence remain unchanged; nothing was
regenerated or staged. Git read-only inspection required the repository owner's
execution context because the restricted runner reported dubious ownership;
no global Git configuration was changed.

The sole intended tracked addition is this assessment. The local documentation
check passed all ten local links/anchors and found no trailing whitespace.
`rtk git diff --check` and `rtk git diff --cached --check` passed; Git status
showed only this new document. Because untracked content is not covered by
`git diff --check`, the PHP documentation check inspected this file directly.
