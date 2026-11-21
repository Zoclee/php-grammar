# PHP 8.6.0 GA adoption and certification remediation

**NOT SIGNED OFF — RELEASE BLOCKED.** PHP 8.6 remains `draft` at authenticated
GA commit `8aaada3f9605d81a38b8240f93c7389dfbc77ef9`, tag `php-8.6.0`.
No stable promotion, tag, release publication or package deployment occurred.

## GA adoption and changes reconciliation

The active manifest selects [source profile v2](../../tools/8.6/source-lock-ga-v2.json),
[change map v2](../../tools/8.6/change-map-ga-v2.json) and the explicit
[adoption record](ga-certification-remediation/adoption-v2.json). The source lock
binds the exact-Git local executable, DLL, authenticated source files, build
receipt and GA-specific validation inputs. RC4, RC7, the original additive GA
audit, and the first GA certification attempt retain their original bytes and
meaning. Historical manifest validation metadata is explicitly labeled in the
adoption record; it is not relabeled as GA execution evidence.

`grammar/8.6/changes.md` retains all ten sections and the material PHP 8.5-to-8.6
changes. Current references cite GA; original phase results and RC4 commands
remain labeled as historical. The five-production delta, canonical EBNF,
normative Markdown and lexer are unchanged. The existing traceability validator
checks the GA successor map, fixtures, implementation hashes and documentary
parity without changing the legacy map or checker.

The exact binary SHA-256 is
`880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b`;
its DLL SHA-256 is
`9b2a8bad3ff00c1aa57c83676cff79a830df63ed5af1fc3eb6d55de8b8ab57f9`.
The retained build receipt covers 28,633 source files, generated artifacts,
SDK, toolchain and build logs. Custody assumes trusted local tooling and host
execution; this is not hermetic or bit-reproducible attestation.

## Freshness repairs and historical preservation

The [closed preservation transition](ga-certification-remediation/transition-v2.json)
anchors every permitted before/after digest to the pre-edit baseline. Everything
outside that exact change list remains byte-identical. PHP 8.5 grammar,
specification, fixtures, source locks, historical evidence and manifest entry
remain unchanged. The original GA audit seal remains
`05b43c14e487160b5451d34bbd330276feaffacedebbe3249d415e576563bfb7`.

Five versioned GA runners retain the original algorithms and fixture
expectations; only their source/profile inputs change. The coverage successor
authenticates the already-reviewed current specification rather than pretending
the old specification digest is fresh. Legacy RC4 freshness tests now use
authenticated predecessor documents, manifest and test bytes, and explicitly
assert that the old checker refuses the changed current inputs. The legacy
checkers and historical results are untouched. Composer uses an explicit
OpenSSL extension; TLS checks are retained.

The complete retained 410-stage inventory is replayed on GA. One diagnostic
presentation predicate has an explicit source-backed successor: the unknown-hook
message includes the anonymous class's NUL-delimited filename, line and counter,
as emitted by `zend_compile.c` at lines 9426 and 9936. The exact hook/property
message and rejection remain required. The historical failed shorthand predicate
and observations remain recorded; no fixture acceptance expectation changes.

The first completed GA run had **21 passed, 8 failed and 9 blocked** obligations.
Its evidence passed independent verification before successor edits. Its
[report](ga-certification-remediation/runs/phase8d-ga-r1/evidence.json),
[verification output](ga-certification-remediation/revisions/r1/independent-verification.stdout.json)
and [predecessor inputs](ga-certification-remediation/revisions/r1/snapshot.json)
are retained. Those results are historical, not current successes.

Exact-byte recovery authenticated **1,544 of 1,545** historical inputs.
The unavailable historical `php-grammar.json` requires SHA-256
`0280ff2b531e0e5cdaa9db2b6265991c3b5d511f4905683a88c2bea5ce2ac373`.
None of the four available local Git versions matched. No text normalization or
reconstructed historical result was substituted. The historical-integrity gate
therefore fails; current compatibility does not replace it or renew PHP 8.5
certification. The legacy historical aggregate also retains its stale-ledger
refusal.

## Original and updated blocker dispositions

The [GA disposition ledger](ga-certification-remediation/blockers-v2.json)
preserves each original requirement, evidence, risk and release impact.

| Blocker | Evidence-backed disposition and smallest remaining action |
|---|---|
| PHP86-013 | One exact redundant-include hunk is discharged by the [local source proof](ga-certification-remediation/local-obligations-v1.json). Effective pending review decreases **198 → 197**; **17 examined-unresolved** identities, bounded groups and outside-ledger associations remain. Continue those existing IDs; no new unrestricted discovery is required. |
| PHP86-014 | GA accepts ordinary assignment-derived dimension writes through lint and emits an invalid VM opcode; execution fails with `Invalid opcode 23/2/1`. The compiler dispatch and controlled stage observations support a compiler/VM defect. [PR #25046](https://github.com/php/php-src/pull/25046) remains open, unmerged and outside GA ancestry. Runtime failure is not a new grammar rule. Intended writable-target validity and an enforceable limitation satisfying the original consistency requirement remain unresolved. |
| PHP86-015 | The maintainer explanation in [#24794](https://github.com/php/php-src/issues/24794#issuecomment-6455774723) resolves the general cause: ordered/skipped compiler visitation. It does not establish each construct's live/retained/discarded validity or the bounded conformance claim. Complete those existing source/predicate associations. |
| CA86-001 | The explanation in [#24795](https://github.com/php/php-src/issues/24795#issuecomment-6455687623) confirms skipped contextual hook checks in discarded ASTs. The EBNF still rejects the unknown hook before elimination. An evidenced Gate B correction or an explicit enforceable scope restriction is required; issue closure does not cure this in-scope false rejection. |
| C6-CONTEXT | The 73 descriptions are not 73 isolated predicates. External enforcement is labeled. Material predicate/source/stage combinations and scope acceptance remain open; implement only those required bounded obligations. |
| C6-COVERAGE | Current ordinary coverage remains **298/361 productions and 545/800 alternatives**. Full retained parser/scanner mappings are independently reverified on byte-identical GA sources: **176 productions, 623 alternatives, 190 scanner rules and 11 states**. Focused scanner and binding observations do not establish every state/helper path or binding case. Classify remaining material identities using the existing ledgers, without a percentage target. |
| C6-PROVENANCE | Authenticated final source and exact local build custody are adopted and verified. That sub-obligation is satisfied. Complete successful release evidence and independent historical/current compatibility remain separate requirements; the overall blocker is not waived. |

The include proof covers only `Zend/zend_compile.c:27:26`: the three added
headers were already included behind guards. It does not exclude header-body
changes, PFA dependencies or transitive compiler behavior. The parser/scanner
successor checks every retained RHS/action digest, scanner slice, identity and
EBNF anchor; it preserves the original limits on individual witnesses,
instrumentation and language equivalence.

The policy permits evidenced bounded claims and explicitly external contextual
constraints. It does not permit an unexplained in-scope false rejection,
unreviewed material source risk or a missing mandatory evidence prerequisite.
None of the seven complete blocker requirements is closed by an upstream
explanation, a passing suite or a source hash alone.

## Supported claim and release readiness

The supported artifact remains a standalone **draft structural grammar** with
explicit external contextual constraints and the named executed matrices.
There is no grammar-complete, whole-language GA compatibility, exhaustive
equivalence or complete-validator claim.

The smallest remaining release actions are to restore the one authenticated
historical manifest; complete the release-obligation decision for the retained
stale historical aggregate; finish the identified RFC/discovery and material
predicate/coverage obligations; and resolve the writable-target and discarded-hook
scope/correction decisions. Then run a new immutable GA certification revision.

The prospective repository release remains **v1.1.0**, following published
**v1.0.2**, independently of the PHP version. Release documentation and package
promotion remain conditional on genuine certification success. No publication
is authorized by this blocked decision.

## Final executed gate results

The current **php86-ga-certification-v2** run executed all **36 original + 2 hybrid = 38** obligations: **26 passed, 3 failed, 9 blocked**.

[Machine-readable evidence](ga-certification-remediation/runs/stable-closure-r1/evidence.json) retains every original obligation, command, exit code, log, input digest and artifact digest. A passing bounded gate does not close a separate material blocker.

| Obligation | Result | Exit |
|---|---|---|
| [manifest-schema](ga-certification-remediation/runs/stable-closure-r1/manifest-schema.log) | passed | 0 |
| [manifest-regressions](ga-certification-remediation/runs/stable-closure-r1/manifest-regressions.log) | passed | 0 |
| [standalone-structure-parity](ga-certification-remediation/runs/stable-closure-r1/standalone-structure-parity.log) | passed | 0 |
| [positive-negative-fixtures](ga-certification-remediation/runs/stable-closure-r1/positive-negative-fixtures.log) | passed | 0 |
| [ordinary-differential](ga-certification-remediation/runs/stable-closure-r1/ordinary-differential.log) | passed | 0 |
| [lexer-profiles](ga-certification-remediation/runs/stable-closure-r1/lexer-profiles.log) | passed | 0 |
| [pfa-differential](ga-certification-remediation/runs/stable-closure-r1/pfa-differential.log) | passed | 0 |
| [contextual-differential](ga-certification-remediation/runs/stable-closure-r1/contextual-differential.log) | passed | 0 |
| [diagnostic-differential](ga-certification-remediation/runs/stable-closure-r1/diagnostic-differential.log) | passed | 0 |
| [version-boundaries](ga-certification-remediation/runs/stable-closure-r1/version-boundaries.log) | passed | 0 |
| [coverage-measurement](ga-certification-remediation/runs/stable-closure-r1/coverage-measurement.log) | passed | 0 |
| [parser-scanner-correspondence](ga-certification-remediation/runs/stable-closure-r1/parser-scanner-correspondence.log) | passed | 0 |
| [compiler-contextual-reconciliation](ga-certification-remediation/runs/stable-closure-r1/compiler-contextual-reconciliation.log) | passed | 0 |
| [rfc-implementation-traceability](ga-certification-remediation/runs/stable-closure-r1/rfc-implementation-traceability.log) | failed | 1 |
| [production-change-traceability](ga-certification-remediation/runs/stable-closure-r1/production-change-traceability.log) | passed | 0 |
| [changes-document-completeness](ga-certification-remediation/runs/stable-closure-r1/changes-document-completeness.log) | passed | 0 |
| [historical-preservation](ga-certification-remediation/runs/stable-closure-r1/historical-preservation.log) | passed | 0 |
| [public-api-cli](ga-certification-remediation/runs/stable-closure-r1/public-api-cli.log) | passed | 0 |
| [source-lock-integrity](ga-certification-remediation/runs/stable-closure-r1/source-lock-integrity.log) | passed | 0 |
| [evidence-freshness](ga-certification-remediation/runs/stable-closure-r1/evidence-freshness.log) | passed | 0 |
| [blocker-dispositions](ga-certification-remediation/runs/stable-closure-r1/blocker-dispositions.log) | passed | 0 |
| [documentation-json](ga-certification-remediation/runs/stable-closure-r1/documentation-json.log) | passed | 0 |
| [full-regression](ga-certification-remediation/runs/stable-closure-r1/full-regression.log) | passed | 0 |
| [infrastructure-negatives](ga-certification-remediation/runs/stable-closure-r1/infrastructure-negatives.log) | passed | 0 |
| [historical-certification-check](ga-certification-remediation/runs/stable-closure-r1/historical-certification-check.log) | failed | 1 |
| [composer-validation](ga-certification-remediation/runs/stable-closure-r1/composer-validation.log) | passed | 0 |
| [production-coverage-closure](ga-certification-remediation/runs/stable-closure-r1/production-coverage-closure.log) | blocked | 1 |
| [alternative-coverage-closure](ga-certification-remediation/runs/stable-closure-r1/alternative-coverage-closure.log) | blocked | 1 |
| [scanner-state-closure](ga-certification-remediation/runs/stable-closure-r1/scanner-state-closure.log) | blocked | 1 |
| [ast-binding](ga-certification-remediation/runs/stable-closure-r1/ast-binding.log) | blocked | 1 |
| [folding-order](ga-certification-remediation/runs/stable-closure-r1/folding-order.log) | blocked | 1 |
| [discovery-closure](ga-certification-remediation/runs/stable-closure-r1/discovery-closure.log) | blocked | 1 |
| [writable-target-validity](ga-certification-remediation/runs/stable-closure-r1/writable-target-validity.log) | blocked | 1 |
| [contextual-enforcement-scope](ga-certification-remediation/runs/stable-closure-r1/contextual-enforcement-scope.log) | blocked | 1 |
| [discarded-hook-disposition](ga-certification-remediation/runs/stable-closure-r1/discarded-hook-disposition.log) | blocked | 1 |
| [exact-pin-executable](ga-certification-remediation/runs/stable-closure-r1/exact-pin-executable.log) | passed | 0 |
| [historical-artifact-integrity85](ga-certification-remediation/runs/stable-closure-r1/historical-artifact-integrity85.log) | failed | 1 |
| [current-compatibility85](ga-certification-remediation/runs/stable-closure-r1/current-compatibility85.log) | passed | 0 |

## Full regression and independent verification

**PHPUnit passed: 6,320 tests, 59,008 assertions.**

Verify this exact revision without writing evidence using `php tools/8.6/certification-ga.php --check --report=docs/8.6/ga-certification-remediation/runs/stable-closure-r1/evidence.json`. The independent verifier reconstructs the 38-command inventory, source and runtime identities, preservation transition, current compatibility dependencies, parser/scanner mappings, local source proof and this generated report. Evidence verification authenticates failed and blocked outcomes too; it does not grant certification.

The separate `--claims --report=docs/8.6/ga-certification-remediation/runs/stable-closure-r1/evidence.json` check must refuse sign-off while any mandatory obligation or material blocker remains. This generated summary is an output bound by the evidence receipt; its template and all execution dependencies are protected inputs.
