# PHP 8.6 stable release sign-off

**NOT SIGNED OFF — EXACT REMAINING REQUIREMENTS.** The authenticated GA
profile remains `draft`. Stable `php-grammar v1.1.0` is not ready. No release
commit, tag, publication, package deployment, or official PHP endorsement is
claimed. This decision applies the existing policy; it does not replace a
failed obligation with a narrower passing check.

## Authenticated baseline and release identity

The official `php-8.6.0` annotated tag object is
`48a5ee8a311db69103a6fa25b330e5ff3847af3d`, pointing to
`8aaada3f9605d81a38b8240f93c7389dfbc77ef9`. Fresh official API responses,
headers, retrieval times and SHA-256 receipts are in
[the closure inventory](release-closure/artifacts.json). The
[baseline](release-closure/baseline.json) records the repository HEAD,
preexisting staged and unstaged work, complete certification input hashes,
successful independent verification of R2, source/executable checks and
preservation results before these additive reports were written.

The official repository latest-release API and remote tags still identify
`v1.0.2`; `v1.1.0` is the appropriate proposed minor version for additive PHP
8.6 support under [versioning section 6](../versioning.md#6-repository-release-versioning).
Composer has no hard-coded package version. No version field needs inventing.

The exact local GA executable SHA-256 is
`880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b`;
its DLL is
`9b2a8bad3ff00c1aa57c83676cff79a830df63ed5af1fc3eb6d55de8b8ab57f9`.
The [adopted source lock](../../tools/8.6/source-lock-ga-v2.json) and
[build source inventory](ga-finalization/build-source.json) retain 28,633
authenticated source files and the reviewed local build chain. This is local
custody evidence, not a hermetic or reproducible-build assertion.

## Grammar identity and material changes

No canonical grammar, lexical rule, normative specification, fixture, PHP 8.5
artifact, or certification algorithm was changed during this final assessment.
The three PHP 8.6 artifacts remain standalone; these are their assessed hashes:

| Artifact | SHA-256 |
|---|---|
| `grammar/8.5/php.ebnf` | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| `grammar/8.5/php.md` | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |
| `grammar/8.6/changes.md` | `d8646847f828db89d54516a7f2a07f3abbda6681b96a63cc88a3d136d2cf2997` |
| `grammar/8.6/php.ebnf` | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| `grammar/8.6/php.md` | `1aa67b423e391c55f92f5b65ffba81bdd9d0dd8ab4c49201e66fab9eff1d100e` |

The [ten-section changes document](../../grammar/8.6/changes.md) remains the
complete material-change record: PHP86-001 adds partial function application
and callable trailing commas through five modified productions, with no added
or removed productions. PHP86-003 through PHP86-007 describe readonly defaults,
constant-object write paths, Override on constants/enum cases, enum
`__debugInfo`, and nullsafe call reference restrictions. PHP86-010 records
accepted-but-deprecated forms and diagnostic stages, not syntax removals.
The locks and finding-specific witnesses in that document remain authoritative.
The RC7-to-GA comparison identified no additional structural or lexical change.

## Three failed obligations

| Obligation | Why it remains mandatory | Exact next action |
|---|---|---|
| `rfc-implementation-traceability` | Versioning sections 3.1 and 16 require disposition of material source changes. GA parser equality does not resolve compiler, optimizer, linking or candidate ancestry/absence obligations. | Close the existing candidate/component/absence/reversion rows in R2's RFC gate details and the [214 outstanding source identities](release-closure/remaining-source-identities.json): 197 pending and 17 examined-unresolved. Retain bounded-group and outside-ledger associations in the source review. For each, supply an immutable implementation/test association or evidence-backed exclusion; implement only findings satisfying Gate B. |
| `historical-artifact-integrity85` | The hybrid contract requires every byte of the anchored historical inventory. Current compatibility is a separate claim. | Restore `php-grammar.json` with SHA-256 `0280ff2b531e0e5cdaa9db2b6265991c3b5d511f4905683a88c2bea5ce2ac373` to the historical snapshot and rerun the unmodified checker. 1,544/1,545 inputs authenticate. The new [Git object search](release-closure/historical-object-search.json) checked 2,398 locally available blobs, including unreachable objects, without finding it. The search was bounded to blobs of 100–100,000 bytes; it is not proof that no external archive exists. |
| `historical-certification-check` | The [opt-in contract](../conformance-policy.md#hybrid-historical-and-current-evidence-opt-in-contract-v1) explicitly retains the original aggregate and forbids substitution by the two added checks. | Complete a separate release-obligation decision and validated successor contract for how immutable historical certification and current compatibility satisfy the release requirement. Keep the original `Stale certification artifact: docs/8.5/final-evidence.json` refusal as historical evidence. Merely restoring the missing manifest does not cure this separate failure. Do not regenerate the old ledger against new inputs or silently remove this gate. |

The new search recovered no bytes, so it changes neither the historical result
nor its scope. No runner defect was identified that would justify converting
any of these failures to success.

## Nine blocked obligations and all seven blocker dispositions

The [existing GA adjudication](ga-certification-remediation/blockers-v2.json)
retains each original requirement, affected syntax, risk and release impact.
The following is the concrete closure checklist, not a new discovery program.

| Gate / blocker | Evidence and remaining action required by policy |
|---|---|
| `production-coverage-closure` / C6-COVERAGE | Use `scanner-grammar-binding-closure/coverage.json` at `after.remainingProductions` and the corresponding `rows`. Classify each material gap as a demonstrated witness, scanner/primitive abstraction with evidence, contextual boundary, or justified non-language obligation. Current ordinary witnesses cover 298/361 productions; uncovered counts alone are not defects or a percentage gate. |
| `alternative-coverage-closure` / C6-COVERAGE | Resolve the exact `after.remainingAlternatives` identities and their row obligations, including context-only `from`. Current witnesses cover 545/800 alternatives. Supply the missing branch-specific correspondence or a justified classification; do not delete alternatives or require arbitrary 100% fixture coverage. |
| `scanner-state-closure` / C6-COVERAGE | Close the `scanner-grammar-binding-closure/scanner.json` row/helper/error obligations and `remaining` entries. The 190 source slices and 11 state names authenticate, but source-range associations are not instrumented branch/state hits. Document any excluded encoding/filter configuration with an enforceable byte-source profile, and supply source-backed transition/error evidence for the supported profile. |
| `ast-binding` / C6-COVERAGE | Finish `scanner-grammar-binding-closure/binding.json` at `remaining` and its predecessor's `remaining`: declaration/interpolation, writable/reference result categories, and PFA/argument binding. Preserve the reviewed operand-span/discriminating observations and retained ambiguities. Source equality and equivalent program output do not alone establish AST correspondence. |
| `folding-order` / PHP86-015 | Reconcile the retained optimized-call/constant-expression dispatch predicates with live, retained and discarded cases. Include direct/imported/qualified/namespace/dynamic/named/unpack/PFA controls already named in the original obligation. The maintainer's #24794 explanation establishes skipped compilation as the cause, not an unconditional argument-traversal rule or completion of every row. |
| `discovery-closure` / PHP86-013 | Discharge the exact source identities and candidate obligations listed above, including bounded groups and transitive paths. The previously proved redundant-include hunk remains the sole GA exclusion added to that queue. No unexamined hunk is classified as harmless by this assessment. |
| `writable-target-validity` / PHP86-014 | Obtain an authoritative intended-validity disposition for the ordinary/reference/destructuring write forms and validate the corresponding compiler/VM consistency or an explicit enforceable exclusion under the conformance contract. GA lint and opcode construction accept cases whose execution fails with `Invalid opcode 23/2/1`; this is independently supported as a compiler/VM defect. Fresh #24792 and #25046 records still show an open issue and open, unmerged proposal; the proposal head is not a GA ancestor (exit 1, not an ancestry error). Do not turn the VM defect into an EBNF restriction or use proposed patched behavior as GA behavior. |
| `contextual-enforcement-scope` / C6-CONTEXT | Finish predicate/source/stage and positive/negative/live/discarded associations for material entries in the 73-description contextual inventory and record which are external. Existing policy permits external contextual constraints; it does not equate 73 descriptions with 73 isolated predicates or waive unexplained source-validity discrepancies. A complete PHP compiler is not required. |
| `discarded-hook-disposition` / CA86-001 | GA parser `property_hook` at `Zend/zend_language_parser.y:1160` consumes `T_STRING`; `zend_compile_property_hooks` rejects unknown names only if visited. The repository's `hook-name = "get" | "set"` rejects them even when discarded. #24795 confirms that dead ASTs skip compilation. The concrete correction candidate is a T_STRING-equivalent hook-name production with get/set legality external at the visited compilation stage, backed by identifier/keyword, case, live/retained/discarded and parameter-hook controls, canonical/Markdown parity and a versioned preservation/traceability transition. Alternatively, a separately evidenced enforceable claim restriction must be explicitly adjudicated. No such correction or restriction was completed here; issue closure alone does not close this in-scope mismatch. PHP 8.5 corrections must remain separate. |

**C6-PROVENANCE:** exact GA source/build custody is satisfied under the stated
local assumptions. Its remaining requirement is successful complete release
evidence, including the separate historical and current obligations and an
independent verifier. It is not a demand to rebuild an already authenticated
executable. None of the seven complete blockers is closed by this assessment.

Fresh upstream comments retain the maintainer's explanation that compiling dead
ASTs while discarding generated opcodes would be nontrivial and is not planned.
This supports the visited-AST distinction already documented. It does not grant
this repository an exemption from its own explicit source-validity contract.

## Actual gate evidence and conformance bounds

The independently authenticated R2 execution ran **36 original + 2 hybrid = 38**
obligations: **26 passed, 3 failed, 9 blocked**. Its full PHPUnit run passed
**6,320 tests / 59,008 assertions**, and its separate current-compatibility
regression passed the same totals. The table below records those actual R2
outcomes; it does not predict a later execution.

| Obligation | R2 result |
|---|---|
| `manifest-schema` | passed |
| `manifest-regressions` | passed |
| `standalone-structure-parity` | passed |
| `positive-negative-fixtures` | passed |
| `ordinary-differential` | passed |
| `lexer-profiles` | passed |
| `pfa-differential` | passed |
| `contextual-differential` | passed |
| `diagnostic-differential` | passed |
| `version-boundaries` | passed |
| `coverage-measurement` | passed |
| `parser-scanner-correspondence` | passed |
| `compiler-contextual-reconciliation` | passed |
| `rfc-implementation-traceability` | failed |
| `production-change-traceability` | passed |
| `changes-document-completeness` | passed |
| `historical-preservation` | passed |
| `public-api-cli` | passed |
| `source-lock-integrity` | passed |
| `evidence-freshness` | passed |
| `blocker-dispositions` | passed |
| `documentation-json` | passed |
| `full-regression` | passed |
| `infrastructure-negatives` | passed |
| `historical-certification-check` | failed |
| `composer-validation` | passed |
| `production-coverage-closure` | blocked |
| `alternative-coverage-closure` | blocked |
| `scanner-state-closure` | blocked |
| `ast-binding` | blocked |
| `folding-order` | blocked |
| `discovery-closure` | blocked |
| `writable-target-validity` | blocked |
| `contextual-enforcement-scope` | blocked |
| `discarded-hook-disposition` | blocked |
| `exact-pin-executable` | passed |
| `historical-artifact-integrity85` | failed |
| `current-compatibility85` | passed |

The final repeat execution is retained separately at
`docs/8.6/ga-certification-remediation/runs/stable-closure-r1/evidence.json`.
That immutable record supplies the repeat's actual per-command outcomes and
input/output hashes. It includes this sign-off assessment and all closure
receipts as protected inputs. Its independently checked results, rather than
the earlier counts above, determine whether the repeat execution is fresh.
Verification and claim-refusal logs are retained under the adjacent
`runs/stable-closure-verification/` directory, outside the sealed run folder.
No execution is considered complete merely because these destinations are named.

Use `php tools/8.6/certification-ga.php --check --report=docs/8.6/ga-certification-remediation/runs/stable-closure-r1/evidence.json`
to independently verify the repeat. Use the separate `--claims` mode to test
release eligibility; authenticating failed outcomes is not sign-off.
R2 is retained unchanged and its successful pre-assessment verification is
recorded in the closure baseline. New report files change the input inventory,
so R2 is not presented as fresh evidence for the expanded workspace.

The strongest supported claim remains a standalone draft structural grammar
with the named executed matrices and explicitly external contextual constraints.
The evidence does not establish grammar-completeness, whole-source validation,
exhaustive equivalence, or stable release readiness. The additive contract
expressly cannot grant certification; the complete release-obligation decision
must precede any certifying successor and must preserve all mandatory evidence.

## PHP 8.5 preservation and remaining release preparation

The baseline records no PHP 8.5 change; the existing current-compatibility gate
independently covers shared implementation and paired boundaries. Its success
does not renew historical certification. The [consumer observation](release-closure/consumer-selection.json)
confirms `latestVersion()` is 8.6 including drafts, while 8.5 remains stable and
8.6 remains draft. Existing maintainer defaults remain 8.5.

Before a stable release, reconcile the RC4 status/source prose still present in
`grammar/8.6/php.md`, `README.md`, `docs/usage.md` and `docs/api.md` through an
explicit successor preservation transition. Retain historical RC4 observations
as historical, update current GA references, and verify complete EBNF/Markdown
production parity. Do not relabel the specification as complete while the
material requirements above remain open. The current ten-section `changes.md`
already identifies the active GA profile and its unresolved findings.

After those mandatory obligations are satisfied, the remaining release steps
are: validate the final package inventory/distribution, update stable consumer
documentation and release notes, promote the manifest in the reviewed certifying
transition, and independently certify that exact candidate. Current Composer
metadata already identifies the library and excludes `.audit`, PHPUnit cache
and `vendor` from Composer archives; that configuration is not a validated
v1.1.0 distribution. No signed-off release description or release commands are
provided for this blocked candidate. Publication remains a separate action.
