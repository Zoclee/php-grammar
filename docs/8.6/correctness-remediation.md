# PHP 8.6 Phase 5D correctness remediation

October 24, 2026. **Verified explanatory clarifications implemented.** No EBNF
production or normative source-validity rule changed. PHP86-013, PHP86-014 and
PHP86-015 remain unresolved. PHP 8.6 remains draft; Phase 5E is not begun.

## Baseline and authorized scope

Repository commit: `11496fba8ae706b05bc500d99b8c0326a41054fc`. Existing uncommitted
Phase 5A/5B/5C work is included in the preservation baseline. The
[baseline ledger](correctness-remediation-baseline.json) records the complete
starting Git status and hashes of 2,036 pre-existing project files. The prior
verified suite passed 6,019 tests and 56,847 assertions, with zero failures.
Coverage remains the Phase 5B measurement, 280/361 productions and 425/800
alternatives; 30 version-boundary records are retained. No new fixture inventory
or coverage claim is introduced.

Source pins are unchanged: PHP 8.5 `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`
and PHP 8.6 RC4 `f23b4c222cebe33be93ee8ecaaa14390c5507a08`. Existing PHP 8.5.10
and 8.6.0RC4 distributions provide paired observations; they are not proven
exact-pin builds. The manifest still registers 8.6 as draft and recommends
8.5 as stable/default; numeric latest-version discovery continues to include
the draft. No manifest, lexer, API or release-gate change is made.

The review uses the source-backed [Phase 5C conclusions](compiler-contextual-reconciliation.md)
and [proposal dispositions](compiler-contextual-reconciliation.json), together
with the existing versioning, source, grammar and conformance policies. It does
not reopen discovery or revise earlier assessments.

## Proposal review and implemented clarifications

| Proposal | Existing specification clause | Classification, evidence and decision |
|---|---|---|
| C5C-D01 / PHP86-015 | Ordinary compilation versus constant folding, under Deprecated but accepted syntax | Implemented as an explicitly informative paragraph. The historical table covers expression statements and initializers, not all call arguments. Phase 5C groups G02/G03 and direct/named/dynamic/PFA/array controls establish the explanatory scope. No normative traversal rule changes; Gate B is not treated as authorization to settle intended compatibility. |
| C5C-D02 / PHP86-014 | Writable variables and destructuring, under Contextual syntax constraints | Implemented as an explicitly informative paragraph following the existing PHP86-004 carve-out. G01 and assignment/property/compound/unset/reference probes support the wider affected area. Parsing, compilation, opcode emission and execution remain distinct; neither lint nor runtime success establishes intended validity. No new permission or prohibition; no normative Gate B correction is claimed. |
| C5C-D03 / CA86-001 | Property hooks and promotion, under Parser/compiler boundary rules | Deferred unchanged. The existing get/set restriction and explicit historical divergence already describe the policy. Replacing hook-name terminals with an identifier and moving checking into contextual validation would change accepted structure; that requires a separate policy decision, authorization and historical-correction work. |

`grammar/8.6/changes.md` section 9 now records the explanatory changes, links to
Phase 5C evidence and this report, preserves all ten required sections, and
explicitly retains PHP86-013/014/015. Other change-document sections remain
unchanged. The current traceability map records reviewed prose hashes and
source/fixture associations for the two informative finding entries. Those
entries do not mark the findings resolved or implemented as language rules.

No new conformance fixtures are needed. Existing Phase 5C cases already protect
the documented distinctions. Its regression test now reads a
[fresh Phase 5D probe report](correctness-remediation-probes.json), retaining
every existing expectation and input-hash check. The historical Phase 5C report
and its original specification hash remain intact. No hash check was waived.

## Focused specification consistency review

| Finding / area | Specification, implementation and evidence consistency |
|---|---|
| PHP86-001 | Five argument/PFA production modifications remain intact. Arguments, callable conversion, constant-expression and attribute restrictions retain structural versus contextual distinctions; existing PFA fixtures and boundary records remain unchanged. |
| PHP86-002 | Required versus variadic placeholder behavior is explanatory; no optional-placeholder syntax is invented. Existing PFA source and fixtures retain the distinction. |
| PHP86-003 | Readonly default relaxation remains contextual; property type/static/hook and inheritance constraints are not relaxed by these edits. Existing context matrix retained. |
| PHP86-004 | Constant-object property mutation remains separate from assignment-derived targets. Direct constant-array and nullsafe restrictions retain their existing scope; PHP86-014 evidence adds no new target permission. |
| PHP86-005 | Override constants/enum cases retain declaration/linking validation. Boundary metadata and delayed-linking cases do not use structural acceptance as a linking verdict. |
| PHP86-006 | Enum debug-info permission retains signature and other magic-method constraints, including warning versus rejection distinctions. |
| PHP86-007 | Nullsafe reference return/yield constraints retain visited-declaration scope and discarded-closure controls; no generalized foreach or nullsafe ban is added. |
| PHP86-008 | Qualified-static correction/provenance uncertainty remains research evidence, not an invented EBNF removal or historical repair. |
| PHP86-009 | Parameter doccomments remain metadata/trivia, not a new lexical or structural rule. No new boundary claim is made. |
| PHP86-010 | Deprecated-but-accepted names and control-flow constructs retain acceptance; diagnostics are not syntax removals. The new paragraph in that section addresses traversal scope only. |
| PHP86-013 | Source discovery remains incomplete. Phase 5C's bounded group dispositions are unchanged; this prose review does not close discovery. |
| PHP86-014 | UNRESOLVED — BOUNDED. Source-explained compiler/VM defects remain distinct from intended validity; no categorical valid/invalid rule added. |
| PHP86-015 | UNRESOLVED — BOUNDED. Optimized-call traversal is observed and source-explained, but intended compatibility remains unresolved. No function-specific EBNF or complete validator claim. |
| CA86-001 | Both grammars deliberately reject unknown hooks even in discarded declarations; both tested interpreters can accept discarded forms. Existing prose explicitly describes the historical policy and contextual enforcement boundary. No reversal. |

The focused review identifies no additional independently verified structural or
normative correction. This conclusion covers the named clauses, source mappings
and existing matrices, not all PHP compiler behavior. New paragraphs cite the
immutable source/probe mappings through the preserved Phase 5C report. No
source pin, existing negative expectation or version-boundary record changes.

## Validation and preservation

Validation results and actual command logs are recorded in the
[validation ledger](correctness-remediation-validation.json). Existing PHP tools
write new `correctness-remediation-*` reports; no historical certification or
Phase 5A/5B/5C report is regenerated. The full suite includes EBNF structural and
reference integrity, EBNF/Markdown parity, lexer/conformance, boundary, public
API/CLI and PHP 8.5 regressions.

| Check | Result |
|---|---|
| Full PHPUnit suite | PASS: 6,019 tests, 56,847 assertions, zero failures |
| Focused discrepancy, traceability and boundary tests | PASS: 99 tests, 1,313 assertions |
| Phase 5C discrepancy probes, fresh report | PASS: 1,708 checks, 501 hash/absence checks, 162 paired-version observations; 162 lint and 37 execution invocations |
| PHP86-001 PFA regression | PASS: 225 checks, 114 paired-version observations |
| Contextual PHP86-003–007 matrix | PASS: 278 checks, 126 stage observations |
| PHP86-010 deprecation matrix | PASS: 712 checks, 268 observations |
| Draft integration and boundaries | PASS: 498 checks, 160 observations; all 30 boundary records retained |
| Change traceability | PASS: five modified productions relative to 8.5, zero added/removed; 14 reviewed documentary sections |
| Manifest | PASS: schema 1.0 and package paths |
| Preservation | PASS: 2,031 of 2,036 starting files identical; exactly five authorized existing files changed; all 1,437 files under 8.5 paths identical |
| Documentation and whitespace | PASS: local link targets, JSON decoding, Markdown trailing whitespace and Git diff check; existing prose recovered byte-for-byte after removing only the two informative paragraphs and one changes.md paragraph |

All checker runs reported zero failures/errors. Reproduction commands below use
the same distribution executables and pinned local source directories. PHPUnit
requires the audit copy's mbstring extension; `-n` disables local PHP settings.

```powershell
rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress
rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --no-progress --filter 'Php86CompilerContextualTest|Php86TraceabilityTest|VersionBoundaryTest'
rtk proxy .audit/php85/php.exe -n tools/8.6/compiler-contextual-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --cache=.audit --report=docs/8.6/correctness-remediation-probes.json
rtk proxy .audit/php85/php.exe -n tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=docs/8.6/correctness-remediation-pfa.json
rtk proxy .audit/php85/php.exe -n tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/correctness-remediation-context.json
rtk proxy .audit/php85/php.exe -n tools/8.6/deprecation-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/correctness-remediation-deprecation.json
rtk proxy .audit/php85/php.exe -n tools/8.6/integration-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/correctness-remediation-integration.json
rtk proxy .audit/php85/php.exe -n tools/8.6/change-traceability.php --report=docs/8.6/correctness-remediation-traceability.json
rtk proxy .audit/php85/php.exe -n tools/validate-manifest.php
rtk git diff --check
rtk proxy .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll .audit/phase5d-review.php
```

The temporary preservation driver's PHP source is embedded in the validation
ledger for reproducibility. It is not a new release gate. The ledger preserves
the checker log contents and hashes of the resulting Phase 5D artifacts.

The preservation review permits only `grammar/8.6/php.md`,
`grammar/8.6/changes.md`, `tools/8.6/change-map.json`, the evidence-report path
in `tests/Php/Conformance/Php86CompilerContextualTest.php`, and the exact
documentation-section count in `Php86TraceabilityTest.php` to differ from the
starting files. Traceability now records 14 reviewed sections instead of 13,
because Deprecated but accepted syntax gains its first difference from the
historical specification. The five production-difference assertion is unchanged.
The canonical 8.6 EBNF hash remains
`12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4`.
PHP 8.5 artifacts, historical certification evidence, source locks, fixtures,
manifest and release gates remain unchanged. Historical evidence retains its
dated input hashes; it is not represented as freshly certified against changed
shared documentation.

## Outstanding risks and Phase 5E readiness

**Phase 5E: READY WITH BOUNDED LIMITATIONS** for targeted coverage using the
existing grammar and stage-aware expectations. PHP86-013 discovery gaps,
PHP86-014 intended writable-target validity, PHP86-015 intended traversal
compatibility, the inherited hook-policy divergence, incomplete contextual
enforcement and exact-build/final-release provenance remain explicit limits.
Candidate promotion and stable certification remain NOT READY.

No separately authorized normative correction is implemented here. Future hook
policy changes require historical-correction governance; writable/traversal
rules require authoritative intended-language evidence and their own Gate B
review. Phase 5E, broad discovery and certification have not begun.
