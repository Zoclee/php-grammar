# Phase 7H.1 validation results

All **15 compatibility commands pass**, and independent verification passes.
The full PHPUnit suite passes **6311 tests / 58955 assertions**.
PHP 8.6 remains **draft** and uncertified.

The current assessment has **27 passed / 2 failed / 9 blocked** across 38 obligations.
The claims check exits 1 for eleven unmet obligations and seven mixed blockers.
Nine obligations are blocked. Two original historical command outcomes remain
failed: coverage-measurement requires the unchanged historical specification,
and evidence-freshness requires the three pre-Phase 7H documentation hashes.
Their original requirements and failures are preserved. The explicit successor
transition and all current independent evidence verifications pass.

| Command | Exit | Log |
|---|---:|---|
| historical-integrity | 0 | [receipt](phase7h1-audited-historical-integrity.log) |
| upstream-preservation | 0 | [receipt](phase7h1-audited-upstream-preservation.log) |
| negative-regression | 0 | [receipt](phase7h1-audited-negative-regression.log) |
| traceability | 0 | [receipt](phase7h1-audited-traceability.log) |
| manifest | 0 | [receipt](phase7h1-audited-manifest.log) |
| compatibility-generate | 0 | [receipt](phase7h1-audited-compatibility-generate.log) |
| compatibility-check | 0 | [receipt](phase7h1-audited-compatibility-check.log) |
| current-generate | 0 | [receipt](phase7h1-audited-current-generate.log) |
| current-check | 0 | [receipt](phase7h1-audited-current-check.log) |
| differential-check | 0 | [receipt](phase7h1-audited-differential-check.log) |
| compose-generate | 0 | [receipt](phase7h1-audited-compose-generate.log) |
| compose-check | 0 | [receipt](phase7h1-audited-compose-check.log) |
| claims | 1 | [receipt](phase7h1-audited-claims.log) |

[Summary](summary.json) records exact command arrays, artifact hashes, the current
assessment and all blocker dispositions. Historical integrity, explicit v3
preservation, upstream evidence, negative regression, traceability, manifest,
JSON, local links and whitespace checks pass. Both grammars, normative
specifications, all pre-phase historical evidence and source pins are unchanged.
The only six existing files changed are the exact test dependency correction,
mandatory documentation input additions, and four opt-in preservation/runner
integration files listed in the summary. Original release gates remain intact.

The initial phase7h1 compatibility attempt is retained as a checkpoint predating
final input stabilization. Its logs are not substituted for the independently
verified phase7h1-audited evidence. The previous Phase 7H failing suite and all original
historical hashes remain preserved.

The authenticated contextual successor contains 162 lint observations and 37
reviewed execution probes with zero errors; whole-record correspondence permits
only the reviewed documentation dependency. The independently verified ordinary
differential successor retains all 353 cases and existing stage expectations.

See [the phase report](../../upstream-resolution-freshness.md) for root cause,
exact hashes, transition boundaries and reproduction commands.
