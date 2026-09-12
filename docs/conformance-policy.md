# Conformance terminology and release policy

This policy governs claims for every version. PHP 8.5's current decision is in
[its completeness report](8.5/completeness.md) and
[final evidence ledger](8.5/final-evidence.json).

## Defined claims

**Canonical** means the repository's authoritative, standalone EBNF artifact.
Markdown must have production parity. It does not imply official PHP endorsement.

**Grammar-complete** means the EBNF accounts for every known syntax construct
at the declared source pin; every uncovered element has an explicit, justified
classification. Scanner contracts and ordered contextual constraints may be
external, but must be documented and their implementation status stated.

**Conformance-validated** means no known mismatch exists in the specifically
named audited matrices and configuration. Always name those bounds and the
oracle/source correspondence. Contextual negatives passing structural matching
are expected boundary evidence, not proof of whole-source rejection.

**Exhaustively equivalent** requires an actual mathematical or exhaustive proof
over the stated language and configuration. No current version qualifies. Do
not use unqualified “fully conformant,” “complete PHP validator,” or equivalent
wording for a structural matcher. “Complete grammar” must link to the definition
and external constraint inventory.

## Release gates

Run `composer release:check` with PHP 8.5, ext-ast, the PHPUnit extensions,
Python 3.10+, Composer, Git and RTK. Alternatively invoke `tools/grammar-release.py`
with `--php`, `--composer`, `--source-directory`, `--source-cache` and `--logs`.
`PHPRC` selects the oracle ini. Download immutable sources before the offline run:

```text
rtk proxy python tools/fetch-php85-sources.py .audit
rtk proxy python tools/php85-source-correspondence.py --fetch --check
rtk composer release:check
```

The runner is the durable CI/release entry point. It executes all 23 gates,
keeps per-command logs and machine-readable exit codes, and fails if any fails.
A CI job must install those prerequisites, restore/fetch the immutable source
cache, run this command and retain the logs. No raw coverage percentage gate is
permitted as a substitute for classified coverage.

| Required evidence | Gate |
|---|---|
| EBNF syntax, references, reachability, nullability; Markdown parity; repository and version-boundary consistency | `composer test` |
| Positive, structural-negative and contextual-negative expectations | PHPUnit and ordinary differential corpus |
| Zero unclassified meaningful coverage gaps | Grammar coverage, positive report freshness, final certification |
| Scanner rules, families, states and primitive links | Lexer coverage/freshness, scanner product, both lexer oracle profiles |
| Zend production/alternative/action mapping | Pinned compiler hashes and parser reconciliation freshness |
| AST binding and intentionally retained ambiguities | Explicit AST, systematic derivation, Phase 6 matrices, interpolation binding |
| Folding-sensitive validity | Declaration folding, Phase 6 direct folding, PHPUnit remediation cases |
| Contextual evidence and honest enforcement status | Modifier matrix, diagnostic witnesses, diagnostic disposition ledger |
| Source correspondence | Three normative hashes; seven-file pin/release comparison |
| Generated artifacts | Matrix/report freshness and final evidence input hashes |

The exact-pin executable is an additional prerequisite for any future
exact-executable certification. Passing today's gates cannot silently promote
the claim. Every remaining blocker must retain affected syntax, risk, evidence,
reason, release impact and future work. New observed in-scope false rejection or
unexplained acceptance/binding mismatch blocks release until corrected or until
the scope/claim is explicitly narrowed with evidence.

## Regeneration and freshness

Regenerate only after reviewing the changed evidence, in dependency order:
positive and negative reports; source inventory if changed; lexical ledger;
structural/scanner/folding/Phase 6 matrices; compiler/parser reconciliation;
Phase 6 evidence; source correspondence, diagnostic and interpolation reports;
final certification. The last step is:

```text
rtk proxy python tools/php85-certification.py
rtk composer release:check
```

Final evidence hashes every grammar/source/test/bin input and the report/policy
inputs. The final generator fails on unknown family anchors, unclassified gaps,
changed source hashes, failing matrices and stale inputs. The source comparison
uses immutable commits, not a moving branch. Historical reports remain available;
their counts describe their original runs.

Checkout bytes matter to these hashes and scanner fixtures. `.gitattributes`
sets LF by default and explicitly preserves existing CRLF/mixed-byte exceptions.
Python report generators require Python 3.10+ and explicitly retain their audited
CRLF serialization on every platform; PHP-generated reports use LF. Do not
normalize scanner fixtures or bypass attributes during checkout. When adding a
fixture whose CRLF bytes matter, add its attribute alongside its evidence.

## Regression requirements

For each syntax-affecting change record the exact PHP version and authoritative
source location. Supply a focused positive witness and a nearby negative where
meaningful. Distinguish structural rejection from surviving compiler rejection.
Add lexical evidence for token/boundary changes, AST or operand-span evidence
for binding changes, and live/retained/discarded witnesses for contextual changes.
Update relevant coverage/source/diagnostic ledgers and Markdown in the same
change. Explain any evidence category that is not applicable; do not invent
redundant fixtures merely to increase totals.

Preserve regression evidence for `false && (unset) 1;`: ordinary folding can
discard the removed cast, while initializer evaluation visits logical operands
before that folding decision. Never move a late predicate into an unconditional
syntax rule without testing discarded declarations. Early parser modifier
actions still reject before folding.

Production renames and structural rewrites require reference and downstream
compatibility review. Any denominator change needs a written explanation and
fresh classified coverage. New versions reuse this policy and evidence schema,
but must supply independent grammar, scanner configuration, pin and witnesses;
see [versioning.md](versioning.md#14-evidence-required-for-a-mature-new-version).
