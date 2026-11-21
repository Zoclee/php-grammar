# PHP 8.6.0 GA adoption and certification remediation

**NOT SIGNED OFF — RELEASE BLOCKED.** PHP 8.6 remains `draft` at GA commit
`8aaada3f9605d81a38b8240f93c7389dfbc77ef9`. The versioned source lock is
[`source-lock-ga-v1.json`](../../tools/8.6/source-lock-ga-v1.json); the prior
RC4 and RC7 profiles and PHP 8.5 artifacts remain historical evidence.

The GA successor uses the original 36 gate identities plus the two hybrid
historical-integrity and current-compatibility obligations. Executing a check
or authenticating its results does not imply satisfaction of its full obligation.
Historical freshness refusals retain their meaning.

The ten-section change document now cites final GA sources. Its material
PHP 8.5-to-8.6 changes remain unchanged: five modified productions, contextual
and diagnostic changes, and explicit unresolved findings. No EBNF, scanner,
or normative PHP 8.6 specification edit is authorized by this source-profile
transition. The successor change map preserves the original implementation,
fixture, structural and documentary identities.

## Blocker dispositions

| Blocker | GA disposition and next concrete obligation |
|---|---|
| PHP86-013 | Open. The existing residual discovery inventory must receive per-hunk source review; an unchanged RC7-to-GA file does not resolve unreviewed PHP 8.5-to-8.6 changes. |
| PHP86-014 | Open. GA accepts the ordinary assignment-dimension write through lint and emits an invalid VM opcode. This supports an upstream compiler/VM inconsistency, not a new syntax rule. A bounded limitation requires an explicit scope decision and independent compiler correspondence. |
| PHP86-015 | Open. The explanation in issue #24794 narrows the cause to ordered compiler visitation. Per-construct live/retained/discarded validity and source correspondence remain required. |
| CA86-001 | Open. The explanation in issue #24795 does not repair the structural matcher's false rejection of an unknown hook in a discarded declaration. A Gate B correction or evidenced, enforceable scope restriction is needed. |
| C6-CONTEXT | Open. Descriptions of 73 contextual constraints are not 73 isolated predicates. Preserve external enforcement labels and close the material predicate/stage/scope obligations. |
| C6-COVERAGE | Open. Classify material uncovered production/alternative identities and remaining scanner state/helper and binding obligations. No percentage threshold replaces this work. |
| C6-PROVENANCE | Exact-GA source/build custody is available and adopted. Complete, successful release evidence and historical/current compatibility remain separate requirements. |

The maintainer explanations and GA observations narrow uncertainty; they do
not establish the policy's grammar-complete claim. The known discarded-hook
false rejection remains in scope. Neither an issue closure nor an unchanged
compiler file is a waiver under the conformance policy.

## Evidence boundaries and preservation

The GA binary is the local exact-Git build with SHA-256
`880d0073cb54474a886e2dd4d9e4088374c0e945920490ad60b7809b8d9b804b`.
Its DLL, generated files, toolchain, SDK, source inputs and build logs are
bound by the retained build receipt and independently rechecked. The custody
claim assumes trusted local tooling and host execution; it is not hermetic
or bit-reproducible attestation.

The prior GA evidence seal remains
`05b43c14e487160b5451d34bbd330276feaffacedebbe3249d415e576563bfb7`.
The Phase 8D baseline and exact predecessor files are retained under
[`ga-certification-remediation`](ga-certification-remediation/).
The legacy ledger and its failures are not regenerated or relabeled.

## Release readiness

The prospective repository release remains `v1.1.0`, following published
`v1.0.2`; this is independent of the PHP version. No stable promotion, release
tag, publication or package deployment is permitted by the current results.
The supported claim is a standalone draft structural grammar with explicitly
external contextual restrictions and the bounded observations recorded in
the executed evidence. No complete GA compatibility claim is made.
