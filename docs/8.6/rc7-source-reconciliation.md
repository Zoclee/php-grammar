# PHP 8.6 RC7 source reconciliation

November 17, 2026 — Phase 8A. PHP 8.6 remains **draft and uncertified**, with
RC4 still the active source profile. RC7 requires **no identified EBNF or lexical
change** in this review. PHP86-014 is **not fixed in RC7**. Its proposed correction
has been submitted as PR #25046 but is not merged. No normative artifact, active
source lock, manifest registration, historical expectation, or release gate is
changed by this phase.

## 1. Official RC7 identity

| Identity | Value |
|---|---|
| Official repository | `https://github.com/php/php-src.git` |
| Release tag | `php-8.6.0RC7` |
| Annotated tag object | `163f211f0aed3b16d30091187d9b742159ebdbd8` |
| Full commit | `b63cd83da3279445ca4a913f03186141f5c3466e` |
| Git tree | `4301be636a48d1c4c69c257d1e885086b2dba257` |
| Commit date | `2026-11-11T23:26:44Z` |
| Retained RC4 commit | `f23b4c222cebe33be93ee8ecaaa14390c5507a08` |
| Official source archive SHA-256 | `68b753e1d45e1417c6874008500de66102963cf0791e8c541211e9386318722e` |
| Official Windows NTS x64 archive SHA-256 | `03da81bc29974c42eb0678c2c4cf94e52c484d1dfa7a61b3c36d98eb23350588` |

The source archive is
[`php-8.6.0RC7.tar.gz`](https://downloads.php.net/~svpernova09/php-8.6.0RC7.tar.gz).
The published checksum is retained with the [official QA page](rc7-source-reconciliation/qa-downloads.body).
The [tag API record](rc7-source-reconciliation/rc7-tag.body),
[commit API record](rc7-source-reconciliation/rc7-commit.body), and
[local Git tag object](rc7-source-reconciliation/tag.log) agree. GitHub reports
valid signature verification; this review does not claim an independent local
GPG trust-chain verification. HTTPS response headers, URLs, times, and body hashes
are retained alongside the API records.

The [source check](rc7-source-reconciliation/source-check.json) independently
checks all **235** files in the retained certification source inventory against
RC4 and compares their RC7 Git bytes with the authenticated release archive.
There is one packaging discrepancy: archive `NEWS` inserts an extra empty
`12 Nov 2026, PHP 8.6.0RC7` heading before the same Git NEWS content. Its SHA-256
is `db709774aece005421bbfcf5151a6390cfb05fbe64f956edde2f75de5e5042bb`;
Git NEWS is `0bd186766cf4df413fa9074804c6cf8f64fb64137ad4395e50ab378c420af8b4`.
The checker verifies that exact insertion, without normalizing source bytes or
asserting whole-archive byte identity with Git. All other 234 selected files match.
The broader [tree inventory](rc7-source-reconciliation/tree-inventory.json)
compares complete Git trees and checks archive bytes for every changed RC7 path
plus the pinned inventory: **673 match**, **NEWS differs**, and **none are
missing**. Unchanged unrelated archive files are not individually compared;
the complete downloaded archive is authenticated by its published SHA-256.

## 2. RC4 versus RC7 source differences

The comparison uses the two complete Git trees, **not** a merge-base diff.
GitHub's compare endpoint reports divergent history and limits its changed-file
list; that response is retained as discovery evidence, not as the complete
comparison. The [changed-path list](rc7-source-reconciliation/changed-files.log)
contains **480 paths**, including **237 PHPT paths**, of which **52 are under
Zend/tests**. The [review ledger](rc7-source-reconciliation/review-ledger.json)
accounts for every changed path and separately records the higher-risk findings.
The [source diff](rc7-source-reconciliation/source-diff.log) and
[commit history](rc7-source-reconciliation/history.log) preserve the review basis.

| Area | Classification and conclusion |
|---|---|
| Parser/scanner | Byte-identical; no structural grammar or lexical change identified. |
| `zend_compile_func_array_map` | Compiler correction: preserve literal callable-name AST for namespace, import and global-fallback resolution. No argument syntax change. |
| Constant PFA argument evaluation | Compiler correction: named-position bookkeeping applies to placeholders, fixing GH-25007's out-of-bounds write. No new placeholder form. |
| Constant callable resolution in traits | Runtime correction: cache only scope-independent public method resolutions; retain using-class scope. |
| Static closure caching | Compiler/runtime correction: cache the called scope with the closure rather than reusing it across incompatible scopes. |
| Match, constant argument cleanup, declaration linking | Compiler corrections: throw-expression live ranges, duplicate named-argument cleanup, and cache dependencies on linked immutable classes. No changed validity predicate identified. |
| AST export / `zend_test_compile_to_ast` | Implementation/export corrections for modifiers, readonly anonymous classes, imports and group use. These represent existing syntax. |
| Length-aware `%pS` formatting | Diagnostic change, especially anonymous-class names containing NUL. Rejection predicates remain unchanged. |
| VM/operands, closures, fibers, optimizer | Runtime/control-flow and operand bookkeeping corrections, including finally interrupts and reference-return unwrapping. The PHP86-014 assignment guard is absent. |
| Remaining extensions, SAPI, APIs and build files | Runtime/API or nonmaterial maintenance changes; no additional credible source-syntax change identified in the risk review. |

`UPGRADING` adds Sodium state-class API entries and a Fileinfo MIME change, not
language productions. NEWS records the corrections above and extension/runtime
fixes. This is a language-focused risk review, not exhaustive runtime correctness
certification; it does not close PHP86-013's broader discovery obligation.

## 3. PHP86-014 proposed upstream correction

The [issue](https://github.com/php/php-src/issues/24792) remains open.
[PR #25046](https://github.com/php/php-src/pull/25046) targets **PHP-8.6**, is
**open and unmerged**, and has head
`5089bcc83c8193e6a965dd1579767b1d503401ba`, matching the supplied fork commit.
The [PR record](rc7-source-reconciliation/fix-pr.body),
[files](rc7-source-reconciliation/fix-pr-files.body), and
[commits](rc7-source-reconciliation/fix-pr-commits.body) preserve this evidence.
The commit-association endpoint returns an empty list; repository-wide issue
search finds the PR. An empty association response is therefore not evidence
that the patch was never submitted.

There is **no official merged implementation commit** to record. The proposed
commit is not an ancestor of RC7 or of the independently recorded PHP-8.6 branch
snapshot `ac377d55a9e7af2e1dd16446a9a1a7a7d6bd300e`. Inspection of
`zend_compile_var_inner` in both snapshots also finds no equivalent proposed
guard. [Fix verification](rc7-source-reconciliation/fix-status.json) retains the
commands, exit codes and function bodies. Shared GitHub access to a fork commit
through the official repository API is not proof of integration.

The patch compiles assignment and reference-assignment ASTs only when `by_ref`
is set or the fetch type is not `BP_VAR_W`, `BP_VAR_RW`, or `BP_VAR_UNSET`.
Otherwise it falls through to the existing temporary-expression compile error.
The nine added PHPTs contain one positive read/direct-reference-argument test and
eight rejection tests: ordinary dimension/property writes, compound assignment,
unset, dimension reference binding, dimension reference arguments,
reference-assignment writes and reference-bearing destructuring writes.
They are proposed tests, **not RC7 tests**. Increment/decrement is discussed in
the PR but has no separate added PHPT; further property/reference combinations
still need explicit boundary coverage before any repository closure.

The [48 fresh control observations](rc7-source-reconciliation/proposal-controls.json)
run twelve isolated programs through lint and execution on both distributions:

| Case | PHP 8.5.10 | RC7 |
|---|---|---|
| Ordinary assignment dimension/property, compound, increment, unset, reference binding and reference dimension argument | Compile rejection | Lint accepts; execution fails with invalid opcode |
| Reference-assignment dimension write | Compile rejection | Executes; both referenced arrays change |
| Reference-bearing destructuring-result write | Compile rejection | Executes |
| Direct reference assignment/destructuring passed by reference | Executes | Executes |
| `foreach (($a =& $b) as &$v)` mutation | Changes both arrays | Leaves both original arrays unchanged |

The PR explicitly leaves the last behavior outside its patch and invites a
maintainer decision on rejecting the currently executable reference-derived
writes. Its NEWS entry targets 8.6.1. Neither that entry nor its PHP-8.6 target
establishes a schedule for the final 8.6.0 release. No final tag was adopted or
certified here. The proposed patch was **not built or executed** in this phase;
the PR author's Linux test claims remain attributed upstream evidence.
PHP86-014 remains unresolved and Gate B is not ready for a new validity rule.

## 4. Parser and scanner

Both pins and the RC7 archive have these SHA-256 hashes:

| File | SHA-256 |
|---|---|
| `Zend/zend_language_parser.y` | `2daed30b2d5d3286c72d4b4794950a977263201213f876f5761dc4a068bd8235` |
| `Zend/zend_language_scanner.l` | `fecd6a22465408397133e6fe3357ed7c598b57d17e3f9886a0935896881fda39` |

PHP86-001's five productions remain `ordinary-argument-list`,
`first-class-callable-arguments`, `clone-argument-list`, `argument`, and
`argument-no-expression`. No added/removed alternative, token, repetition,
optionality, precedence or lexical rule was identified for RC7.

## 5. Compiler/contextual findings and existing blockers

PHP86-003 through PHP86-007 retain their readonly-default, constant-object write,
Override-constant, enum `__debugInfo`, and nullsafe reference-return boundaries
in the focused stage-specific observations. PHP86-010 remains accepted syntax
with diagnostic-stage distinctions; all 67 existing diagnostic cases were
checked at lint and execution on each distribution.

PHP86-015 and CA86-001 retain the official maintainer explanations and
not-planned closures documented in the
[Phase 7H report](upstream-maintainer-resolution.md). No contrary validity
evidence was found. Ordinary discarded-expression and retained/initializer
controls keep their prior acceptance outcomes. The retained unknown-hook error
now includes the anonymous-class location suffix because `%pS` preserves the
embedded NUL; it still rejects. The original diagnostic expectation and all RC4
observations are preserved. Neither an unchanged outcome nor an explained
compiler traversal resolves the known EBNF mismatch or the required claim scope.

All seven blockers remain: **PHP86-013, PHP86-014, PHP86-015, CA86-001,
C6-CONTEXT, C6-COVERAGE, C6-PROVENANCE**. New source and distribution evidence
advances their investigation but supplies no waiver or automatic closure.

## 6. Official tests and targeted validation

The authenticated Windows executable identifies itself as PHP 8.6.0RC7, built
November 11, 2026, NTS Visual C++ 2026 x64. It is an **official distribution**,
not a locally attested build of the exact Git commit. The collector verifies
every extracted EXE/DLL against the hash-verified ZIP before running probes and
checks those files again afterward. PHP 8.5.10's executable matches the retained
historical hash `32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9`.

[RC7 observations](rc7-source-reconciliation/probes-8.6.json) contain **410**
stage-specific results; [PHP 8.5 observations](rc7-source-reconciliation/probes-8.5.json)
contain **385**. Different execution counts follow the original reviewed
execution eligibility and expectations; lint rejection is not silently counted
as successful execution. Both cover PFA, contextual restrictions, deprecations,
writable targets, compiler traversal, exact-pin reconciliation controls, and
existing version boundaries. All acceptance expectations agree. The one RC7
diagnostic-text mismatch is explicitly retained, not rewritten as a full match.
Structural boundary metadata selects lint for interpreter comparison; PHPUnit
separately checks grammar matching.

[Official PHPT validation](rc7-source-reconciliation/upstream-tests.json) passes
**14/14**, with zero failures, skips or warnings: array-map name resolution,
constant PFA GH-25007, closure scope, six trait callable scope cases, duplicate
named-argument cleanup, modifier/readonly export, and anonymous-class diagnostic
controls. This is not the full upstream PHP test suite or an optimizer matrix.

Initial local collection checkpoints remain available. The first probe collector
incorrectly gave execution expectations to six lint-negative cases; the expanded
boundary collector initially executed six structural-only fixtures. Corrected
successor observations retain the original manifests and use the proper stages.
Initial source capture also included Git background-maintenance stderr in three
files; reacquisition with maintenance disabled matches all original RC4 hashes.
These are evidence-collector defects, not PHP changes.

## 7. Proposed implementation and Gate B

**No EBNF, normative specification, or lexer change is required by the verified
RC4-to-RC7 findings.** Existing standalone artifacts remain byte-identical.
The source comparison does not authorize rewriting the already documented
PHP86-014 uncertainty, discarded-hook restriction, or traversal limitations.

For a separately authorized profile transition, add versioned RC7 diagnostic
expectations for anonymous-class names and retain the new PFA/cache regression
witnesses. Preserve RC4 expectations. A later merged PHP86-014 correction would
require renewed review of `variable-expression`, `assignment-expression`,
destructuring, `unset-statement`, reference-argument and `foreach-statement`
contextual clauses; the patch's intended boundary, PHP 8.5 controls, discarded
contexts and positive direct-reference cases must all be reconciled before Gate B.
No speculative normative edit is prepared here.

## 8. Versioned source-profile transition

The [inactive candidate source profile](rc7-source-reconciliation/candidate-source-profile.json)
records the release identity and all reviewed file hashes independently. It is
research metadata, not a new accepted profile name for certification commands.
`historical-rc4` and `exact-pin-rc4` remain unchanged and active where previously
selected. RC7 observations cannot be substituted into their receipts.

Activation requires a separately reviewed profile registration, explicit runner
selection, source/build custody, contextual and diagnostic successors, scanner
and differential successors, and fresh current compatibility and certification
dependency records. A locally authenticated exact-source build is still needed
for an exact-source executable claim. No existing RC4 receipt is regenerated.

## 9. Certification dependencies and local validation limits

The [validation receipts](rc7-source-reconciliation/validation.json) retain exact
commands, exits and log hashes. Manifest validation and change traceability pass.
The full PHPUnit suite on **PHP 8.5.10 passes 6,311 tests / 58,955 assertions**.
The separate full RC7 run also passes **6,311 tests / 58,955 assertions**; these
overlapping runs are not added together as unique tests. They cover EBNF
integrity/reference checks, Markdown production parity, repository consistency,
PHP 8.5 regressions and existing PHP 8.6 regressions.

The historical-integrity check refuses because historical input bytes required
by its anchored ledger are unavailable or unauthenticated in this checkout.
The retained current-compatibility revision refuses stale/incomplete mandatory
dependency scope after new tooling inputs. Current v4 certification verification
and claims refuse, including missing original RC4 executable/source custody.
The separately restored source files here are not substituted into historical
profile paths. No original report or gate is modified to obtain a pass.
The retained Phase 7H.1 27/2/9 assessment remains historical; this phase does not
relabel it a fresh passing assessment. Full current compatibility/certification
verification is therefore **not achieved** in this environment.

The phase checker verifies the preserved working-tree baseline, selected source
hashes, official archive hash, exact NEWS discrepancy, Git tree identity,
probe dependencies, artifact membership, JSON and local report links. Its
[result](rc7-source-reconciliation/check.json) is an evidence-integrity result,
not release certification. Hash authentication uses the repository's reviewed
local-custody model; it does not protect against coordinated replacement of the
controller and every trust anchor.

## 10. Reproduction and historical preservation

Use [the PHP entry point](../../tools/8.6/rc7-source-reconciliation.php). It stores
downloads and source/binary caches under ignored `.audit/`, and phase evidence
under this report's adjacent directory. The source Git cache is a bare clone of
the official repository containing both recorded commits and the proposed fix.
Do not regenerate retained evidence to change its historical result.

```text
rtk php tools/8.6/rc7-source-reconciliation.php
rtk php -d memory_limit=1G tools/8.6/rc7-source-reconciliation.php --artifacts
rtk php tools/8.6/rc7-source-reconciliation.php --sources
rtk php -n -d memory_limit=1G tools/8.6/rc7-source-reconciliation.php --inventory
rtk php tools/8.6/rc7-source-reconciliation.php --fix
rtk php -d memory_limit=1G tools/8.6/rc7-source-reconciliation.php --probes 8.6
rtk php -d memory_limit=1G tools/8.6/rc7-source-reconciliation.php --probes 8.5
rtk php tools/8.6/rc7-source-reconciliation.php --proposal-controls
rtk php tools/8.6/rc7-source-reconciliation.php --upstream-tests
rtk php tools/8.6/rc7-source-reconciliation.php --validate
rtk php tools/8.6/rc7-source-reconciliation.php --check
```

The [baseline](rc7-source-reconciliation/baseline.json) and
[artifact inventory](rc7-source-reconciliation/artifacts.json) support independent
verification. All pre-phase tracked bytes, including PHP 8.5 evidence, RC4 source
locks, normative files, fixtures, manifest and historical certification records,
remain unchanged. No PHP 8.5 historical evidence was regenerated.

## 11. Final PHP 8.6.0 reconciliation plan

The repository has a reproducible RC7 source review and a separate distribution
observation profile from which to conduct the next comparison. It is ready to
**start final-source reconciliation when the official final release exists**,
but is not ready for adoption or release certification.

Resolve the official final tag and immutable commit; authenticate its archives;
compare its complete tree with RC7 and account for archive packaging differences.
Recheck PR #25046 and any successor/cherry-pick by implementation, tests and
ancestry. Re-run the writable-target and foreach controls, preserve the maintainer
resolutions for discarded expressions, and review diagnostic/profile changes.
Then establish the intended exact-source or explicitly bounded distribution
profile, satisfy Gate B for any actual language correction, and restore all
required source/build/historical custody before fresh compatibility and full
release-gate validation. Keep every remaining blocker explicit. No final release
was adopted and no final certification work was begun in Phase 8A.
