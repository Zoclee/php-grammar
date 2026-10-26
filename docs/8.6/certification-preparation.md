# PHP 8.6 independent certification preparation

October 24, 2026. Phase 7A infrastructure and bounded evidence preparation only.
**PHP 8.6 remains draft and uncertified. Phase 7B has not begun.** PHP86-013,
PHP86-014 and PHP86-015 remain unresolved certification blockers. PHP 8.5 remains
the recommended stable/default grammar; the numeric latestVersion API continues
to include drafts. Neither normative grammar nor specification is changed.

## Baseline and architecture

The clean repository baseline is `4625873a6bac908b5cff4c677dba4ac10dd050e0`.
[The preparation baseline](certification-baseline.json) retains every original
project-file hash. It includes PHP 8.5 grammar, specification, fixtures, source
locks and certification evidence, both PHP 8.6 specification artifacts, manifest,
public APIs, release tooling and prior audit reports. Preservation compares every
entry, not only the two EBNF files. Initial owner-context Git status was clean;
Git required a command-scoped ownership exception in that context, without a
persistent configuration change.

| Artifact | Preserved SHA-256 |
|---|---|
| PHP 8.5 EBNF | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| PHP 8.5 specification | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |
| PHP 8.6 EBNF | `12cfcfa3a0122eeac769862a7644740eb20a533048267f23c8ea984e261dbaf4` |
| PHP 8.6 specification | `45688b7d36ff16d823360f9072f43aa8e52c4dbd9189c890a6fdeef592bca521` |

[The version-specific runner](../../tools/8.6/certification.php) reuses PHP
Support process execution, strict argument parsing and JSON serialization. Its
[internal validator](../../tools/lib/Certification86.php) owns the independent
gate contract, source verification, evidence digests and claim refusal. Existing
PHP 8.5 orchestration is unchanged. No historical conclusion is inherited.
The runtime consumer package does not load certification evidence.

## Source lock and executable provenance

The immutable pins remain PHP 8.5
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and PHP 8.6 RC4
`f23b4c222cebe33be93ee8ecaaa14390c5507a08` (branch PHP-8.6, tag
php-8.6.0RC4). [The independent certification lock](../../tools/8.6/certification-source-lock.json)
unifies 235 previously recorded RC4 source/test hashes. The existing integration
lock is preserved. The required set is independently reconstructed from reviewed
discovery/implementation records; deleting an entry or changing its expected hash
fails. Core requirements explicitly include parser, scanner, compiler, AST,
inheritance, attributes, enums, constants and VM definitions. The lock records
acquisition provenance and hashes the original evidence. Verification does not
learn expected hashes from the current cache and does not fetch moving branches.
This is not a full-tree build attestation. No upstream source is copied into Git.

Available oracles are the existing Windows NTS PHP 8.5.10 and PHP 8.6.0RC4
distributions. Source hashes and binary hashes are different evidence. The runner
records executable paths, SHA-256, commands, exit codes and selected `php -i`
build/configuration fields independently. Arbitrary environment variables are
excluded from persisted metadata. Matching the RC4 version string cannot establish ancestry.
Neither distribution is a proven exact-pin build. The
[build preparation record](certification-build.md) describes the environment,
isolated build recipe, required provenance and remaining verification work.

## Release obligations and execution modes

[The machine-readable inventory](certification-gates.json) lists every gate and
its executable check or explicit missing prerequisite. There are 36 gates, not
a copied 27-gate count. They cover manifest validity; standalone loading;
structural/reference integrity and parity; positive and negative conformance;
ordinary lint; lexer profiles; PFA, contextual and diagnostic differentials;
version boundaries; coverage; parser/scanner/compiler/RFC correspondence;
production/change-document traceability; historical preservation; APIs/CLI;
source integrity; freshness; dispositions; documentation; full regression;
negative infrastructure tests; historical certification and Composer metadata.

Additional mandatory gates retain production/alternative coverage closure,
scanner state evidence, AST/binding, folding order, discovery, writable targets,
contextual enforcement scope, discarded hooks and exact-pin provenance. Their
status is **blocked**, with the responsible finding and concrete obligation.
An inventory match, sampled test pass or generated report does not discharge them.
No percentage threshold replaces classified coverage. AST and folding categories
remain in the evidence schema even where independent closure evidence is absent.

Run from the repository root, using a PHP runtime with the PHPUnit extensions.
The default local paths match this workstation; all oracle/source paths are
configurable. `--phpunit-extension` accepts an explicit mbstring extension path.
For other platforms provide appropriate PHP executables and dependencies.

```text
rtk proxy .audit/php85/php.exe -n tools/8.6/certification.php --inventory
rtk proxy .audit/php85/php.exe -n tools/8.6/certification.php --prepare
rtk proxy .audit/php85/php.exe -n tools/8.6/certification.php --check
rtk proxy .audit/php85/php.exe -n tools/8.6/certification.php --claims
```

`--prepare` executes bounded checks and writes independent results and logs. Its
zero exit means a preparation report was produced, including failures and missing
prerequisites; it does not mean release gates passed. Each child exit code is
retained. `--check` independently validates the stored preparation ledger, exact
input/output digests, required outcomes and unchanged dispositions. It performs
no regeneration or unrelated writes. `--claims` additionally evaluates every
mandatory gate and blocker and must exit nonzero. `--run` is the full-certification
entry point with a mandatory preflight; it refuses execution while prerequisites
or phase authorization are absent. Phase 7A never grants certification, even if
someone supplies a report claiming all gates passed. Phase 7B needs a separately
reviewed policy/evidence change, not a command-line bypass.

## Evidence schema and freshness

[The final preparation ledger](final-evidence.json) uses schema 1 and the existing
nine categories: grammar integrity, positive conformance, negative boundary,
lexical/scanner, AST/binding, folding, contextual validation, upstream source
mapping and differential executable. It preserves version/source/profile,
coverage/classifications, scanner/parser inventories, fixtures, restriction
inventory, individual blocker dispositions and input hashes. Additive fields
identify phase 7A, actual command outcomes, executable metadata, output digests
and explicitly false certification/completeness claims.

Evidence is under `docs/8.6/certification/`; original research reports remain
unchanged. Each executed gate has its exact argument array, exit code, log digest,
text log and independent JSON outcome. Reports distinguish passed, failed,
blocked and not-executed states; absent evidence cannot become a pass. The ledger
hashes all source, tests, grammar, tools, CLI, schema, documentation and root
configuration inputs, including added/deleted files. Inputs are snapshotted before
command execution and checked again at the end; edits during a run make the
preparation stale rather than silently attaching new hashes to old execution.
Output reports and command
logs have a separate digest map to avoid self-hashing cycles. Missing required
outputs, truncated gate inventories and stale input/output bytes fail with paths.
These are local integrity checks, not cryptographic proof against an adversary
rewriting both evidence and its hashes. Generation does not prove completeness.

The ordinary differential command rechecks every reviewed Phase 6 fixture with
the registered 8.6 matcher and RC4 lint. Contextual and declaration-link fixtures
retain their reviewed stages; structural acceptance is not compiler validity.
Only previously controlled runtime probes execute in the focused checkers.
Oracle profiles use `-n`, byte source, explicit short tags and no CLI opcache/JIT.

## Validation results and historical compatibility

The pre-edit full suite passed **6,147 tests and 58,021 assertions**. Initial
infrastructure tests passed **9 tests and 38 assertions**; the expanded suite
passes **10 tests and 40 assertions**, also rejecting invented coverage/test counts
and altered interpreter metadata. Their isolated scenarios
cover wrong source bytes/hash, missing lock entry, wrong pin, missing provenance,
invalid manifest metadata/status, missing/stale evidence, missing/failed gates,
forged closure passes, omitted/reclassified blockers, interpreter/source mismatch,
unexplained production change, strict CLI usage and non-writing missing-report checks.

The expanded preparation run's full suite passed **6,157 tests and
58,061 assertions** with zero failures (the earlier nine-test infrastructure
checkpoint passed 6,156 tests and 58,059 assertions). The final preparation ledger's
`validation` field records the latest actual full suite count and exit status.
Per-gate logs record the actual source, manifest,
integrity/parity, positive/negative, differential, boundary, traceability,
API/CLI, documentation and regression commands. Selections overlap and are not
summed. The initial preparation run exposed missing mbstring in a nested manifest
test invocation and missing OpenSSL in Composer's PHP process. The new runner
now invokes the same manifest tests with its explicit PHPUnit extension profile
and loads the local OpenSSL DLL for Composer; no shared or host configuration is
changed and TLS validation is not disabled. The initial documentation gate also
reported the final evidence file before its first generation. The final
independent check must validate that generated artifact after preparation.
The corrected run records **25 passed gates, one failed historical freshness
check, and ten blocked closure/provenance gates**. The failed historical check
and blocked obligations are explicit results, not failures hidden by the
passing PHPUnit suite. The final evidence distinguishes fresh observations from Phase 6 source
inventories checked for reproducibility. No upstream PHPT-suite or exact-build
execution is claimed.

The unmodified PHP 8.5 certification `--check` is included. Its historical
all-repository digest contract predates 8.6 and already has documented freshness
limitations after shared-file integration. A nonzero historical check remains a
failed gate with its actual error (`Stale certification artifact:
docs/8.5/final-evidence.json` here); it is not a new passing certification or an
excuse to rewrite historical evidence. The complete original-file hash baseline
and historical negative certification tests protect previous behavior. The new
runner does not weaken the old release runner or its inputs.

## Blockers and Phase 7B readiness

All seven [Phase 6 dispositions](source-audit-dispositions.json) are carried
forward unchanged, with construct, risk, evidence, missing evidence, impact and
remediation. PHP86-013 is incomplete discovery; PHP86-014 is unresolved intended
writable-target validity with compiler/VM inconsistencies; PHP86-015 is uncertain
optimized-call validation order. CA86-001 retains the discarded-hook discrepancy.
C6-CONTEXT retains incomplete contextual enforcement; C6-COVERAGE retains
production/alternative, scanner-state and binding obligations; C6-PROVENANCE
retains exact-build and release evidence requirements.

Infrastructure can collect and validate bounded independent evidence. Independent
prerelease certification **cannot begin successfully** until these prerequisites
are resolved and the separate phase is authorized. Candidate/stable promotion and
completeness claims remain prohibited. This work stops at preparation; it neither
begins Phase 7B nor changes a finding's disposition.
