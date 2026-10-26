# PHP 8.6 scanner, grammar coverage and binding evidence closure

October 26, 2026 — Phase 7F. **D-S01, D-S02 and D-S03 advance, but remain
partial. C6-COVERAGE remains blocking. PHP 8.6 remains draft and uncertified.**
This phase adds focused evidence under the original Phase 7D completion
criteria. It does not change normative grammar, compiler-validity policy,
source pins or release requirements.

## 1. Baseline and provenance

The [baseline](scanner-grammar-binding-closure/baseline-baseline.json) records
HEAD `4625873a6bac908b5cff4c677dba4ac10dd050e0`, the preexisting dirty tree,
protected file hashes, all eleven original work items, seven blockers and
predecessor coverage/scanner/binding records. The
[pre-edit checks](scanner-grammar-binding-closure/baseline-checks.json) independently
verify source locks, current Phase 7E certification evidence, historical byte
integrity and current compatibility before project inputs change.

The PHP 8.6 RC4 source remains
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`; PHP 8.5 remains pinned to
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. Exact-source executable SHA-256 is
`445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`, with core DLL
`1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f`.
The existing provenance verifier authenticates the build before new observations.
Pinned source slices are compared in both the clean and build source trees.

## 2. Original work items and batch boundaries

The [successor work ledger](scanner-grammar-binding-closure/work-items.json)
retains every original work item verbatim. D-S01 requires identity-specific
coverage dispositions, D-S02 requires scanner/state/helper evidence, and D-S03
requires source-corresponding binding evidence. None is redefined as a percentage
target or closed merely because observations increase.

| Batch | Evidence added | Disposition |
|---|---|---|
| A | Ordinary statement, declaration, member, name and built-in alternatives | Focused accepted witnesses |
| B | Nested arrow/include yield keys and three negative boundaries | Accepted alternatives and separately classified parser/compiler rejection |
| C | Fourteen scanner scenarios with exact token bytes and lint outcomes | Source-associated observations; no instrumented hits |
| D | Six unique native derivations, forced grouping and different-grouping controls | Bounded operand correspondence |
| E | Complete successor inventories, independent verification and current release assessment | Original blockers and criteria retained |

## 3. Production and alternative coverage

| Ordinary accepted whole-source measurement | Before | After |
|---|---:|---:|
| Productions | 298 / 361 | 298 / 361 |
| Alternatives | 524 / 800 | 545 / 800 |
| Unexercised productions | 63 | 63 |
| Unexercised alternatives | 276 | 255 |

The [coverage ledger](scanner-grammar-binding-closure/coverage.json) retains all
382 original uncovered identities (64 productions and 318 alternatives), their
prior dispositions and the Phase 7C.9 gains. It records exact new fixture and
source mappings, complete matched/remaining identities and per-fixture completed
chart items. Twenty-one additional alternatives are exercised by fourteen new
ordinary valid fixtures. No negative or scanner-only case contributes to these
accepted-source counts, and there are no artificial rule-level traversals.

New witnesses cover alternative `if`/`elseif` branches; final/static and setter
visibility modifiers; contextual `readonly` function declarations/calls;
static-property class names; `eval`, `exit` and `die`; grouped namespace imports;
reference list destructuring; `var` properties; dynamic class/member forms;
attributed top-level constants; and nested arrow/include forms in yield keys.
The [reviewed manifest](../../tests/fixtures/php/8.6/scanner-grammar-binding/cases.json)
names each exact alternative rather than inferring coverage from a parent match.

All 63 remaining productions are primitive-bypassed lexical helpers or removed
trivia. Their canonical definitions and denominator membership remain intact.
Per-identity lexical rows identify related scanner support where available,
explicitly distinguishing that family support from a hit on the helper body.
Non-ASCII bytes, halt-compiler payloads and other unselected boundaries retain
their previous evidence and outstanding obligations. The context-only `from`
alternative is not relabeled as covered by a composite yield-from token.

## 4. Negative boundaries and validation stages

Three dedicated boundaries are outside the ordinary accepted corpus:

- `new A(...)` structurally matches, but exact-source lint rejects creating a
  closure for a new expression. This is compiler rejection, not parser rejection
  or a new normative grammar restriction.
- `yield $a or print $b => $c` is rejected structurally and by exact-source parsing.
- `$a instanceof !$b` is rejected structurally and by exact-source parsing.

Their full diagnostics are retained in the
[observations](scanner-grammar-binding-closure/observations.json). Fixtures are
linted or tokenized; arbitrary fixture code is not executed.

## 5. Scanner and lexical evidence

The [scanner successor](scanner-grammar-binding-closure/scanner.json) preserves
all 190 headers and eleven state names, including every predecessor row and
limitation. Fourteen new scenarios record repository lexer acceptance and token
boundaries, exact `token_get_all` token names/bytes, and exact-source lint results.

The new cases cover shebang with newline and at EOF; echo/PHP tags and CRLF close
tags; binary, explicit/legacy octal, hexadecimal, decimal and exponent forms;
numeric error token splitting; interpolated numeric/name offsets and contextual
property names; nested heredoc return transitions; nowdoc indentation and an
under-indented rejection; unterminated comment; valid and out-of-range Unicode
escapes; attribute/PFA tokens; and qualified/fully qualified/relative names.

The prior ten associated state names plus SHEBANG now associate all eleven names
with scenarios. This is **source correspondence, not eleven instrumented state
hits**. Neither scanner-rule nor state execution is instrumented; no percentage
or exhaustive hit claim is made. Source ranges alone do not prove execution.
The combined scenario-to-source inventory associates **42 of the retained 190
headers**, up from 21; this count describes mappings, not executed rule hits.

The shebang distinction is explicit: CLI lint initializes the shebang path,
whereas `token_get_all` exposes the same bytes as inline HTML. Likewise,
tokenization without `TOKEN_PARSE` can return an unterminated comment or invalid
escape as token text while lint rejects it. `0b102` splits into `0b10` and `2`
before parser rejection. Repository lexing accepts that token sequence and the
out-of-range Unicode escape; these are not represented as complete native
whole-source validation. Nowdoc indentation and unterminated comments are
rejected by the repository lexer in the selected cases.

Encoding/filter configurations, ZTS, reentrant exception restoration, unselected
helper branches and full recursive transition coverage remain outstanding.

## 6. Binding and Zend source correspondence

The [binding successor](scanner-grammar-binding-closure/binding.json) preserves
the five prior witnesses and adds six comparisons:

| Expression | Discriminating correspondence |
|---|---|
| `~$a ** $b` | Bitwise-not contains exponentiation; grouping the unary operand first changes compiler output |
| `$a + $b * $c` | Addition contains the multiplication operand |
| `yield from $a ?? $b` | Yield-from contains coalesce; coalescing a completed yield-from differs |
| `f(?, $a * $b + $c)` | Placeholder position is retained while multiplication/addition bind within the second argument |
| `clone $a ** $b` | Exponentiation receives the clone result; passing exponentiation into clone differs |
| `yield $a ?? $b => $c + $d` | Coalesce belongs to the key; addition belongs to the value |

Each has a unique native expression derivation, explicit operand spans, pinned
Zend precedence/actions, an original fixture, forced parentheses derived from
the native spans, and a separately grouped control. All three forms compile.
The original and forced forms produce equal nonempty pre-optimizer opcode
sequences; the differently grouped control produces a different sequence.
No fixture runtime result is used to infer validity.

Zend source mappings identify unary/binary nodes, clone's fully qualified CALL
representation, argument-list/placeholder construction, and YIELD's value/key
child order. These are reviewed source correspondences, not live Zend AST dumps.
Equal opcode sequences do not establish full AST equivalence. The native forest
uses the existing PHP 8.5 test-only token adapter; exact PHP 8.6 compiles every
comparison. Dangling-else, declaration/member, interpolation and writable/reference
result-category obligations remain explicit. PHP86-014 is not resolved by these
controls, and no writable-target rule changes.

## 7. Evidence inventory transition

The [new ordinary inventory](scanner-grammar-binding-closure/inventory.json)
preserves all 339 predecessor rows byte-for-byte as JSON values and adds exactly
fourteen reviewed cases: **353 ordinary cases**. The new
[differential report](scanner-grammar-binding-closure/differential.json) checks
lexical acceptance, structural acceptance, lint exit and normalized diagnostics
against the authenticated exact interpreter. Membership is checked against the
entire live ordinary corpus; missing or unreviewed cases fail verification.

The versioned `php86-ordinary-differential-v3` contract is additive. Its
`php86-certification-v4` composition retains the unchanged v2 release assessment,
all original gate obligations and blockers, and selects this independently
verified inventory for the ordinary-differential gate. The old v3 certification
composition and its fixed 339-case contract remain unchanged and historical.
Their current-membership checks must not be used to claim freshness after the
corpus grows. The prior 313-case gate result remains retained in the base run.

The initial [differential checkpoint](scanner-grammar-binding-closure/differential-checkpoint.json)
records a changed-input refusal while snapshot dependency declarations were
being corrected. The initial [scanner/binding checkpoint](scanner-grammar-binding-closure/observations-checkpoint.json)
predates the final mandatory observation dependency in the tests. Both are
retained with their receipts; neither is represented as final fresh evidence.
The final reports are generated after those input changes stabilize.

The existing 80-case context/coverage mechanism is also executed into a
[new current report](scanner-grammar-binding-closure/context-current.json),
preserving its original reports. Its freshness now includes the expanded
ordinary corpus. Neither transition overwrites an earlier outcome.

## 8. Certification impact and validation

C6-COVERAGE remains blocking: 255 chart alternatives remain unexercised, lexical
and scanner obligations retain their stated bounds, and full branch/AST
correspondence is not established. All seven original blockers remain open.
The 578 source identities and 73 inherited contextual descriptions are unchanged;
this phase does not claim new PHP86-013 or C6-CONTEXT closure.

Fresh validation and gate results are recorded in the
[final validation appendix](certification-contract-remediation/phase7f-validation/results.md),
[validation summary](certification-contract-remediation/phase7f-validation/summary.json)
and [current assessment](certification-contract-remediation/runs/phase7f/evidence.json).
These receipts distinguish executed commands, independently verified retained
evidence, failed historical gates and blocked current obligations. Passing native
tests or improved coverage cannot override a blocked release obligation.

Validation includes ordinary and negative conformance, EBNF/reference integrity,
Markdown parity, scanner/lexer and binding tests, version boundaries, exact-source
PFA and contextual/deprecation regressions, differential and source-provenance
checks, historical integrity, current compatibility, manifest/JSON/link/whitespace
checks and the full PHPUnit suite. Independent evidence checks recompute native
coverage and operand derivations and reject lost observations, altered tokens,
non-discriminating controls, changed profiles and unsupported claims.

## 9. Preservation and readiness

The [preservation receipt](certification-contract-remediation/phase7f-validation/preservation.json)
checks every pre-phase file against the baseline. PHP 8.5 grammar, specification,
fixtures and certification evidence; PHP 8.6 EBNF and normative specification;
source pins, public APIs and existing gates remain unchanged. New compatibility
and assessment revisions are additive. PHP 8.5 remains recommended stable and
PHP 8.6 remains draft.

Further local coverage and binding reconciliation is ready to continue from the
retained identities and original D-S01–D-S03 criteria. Upstream writable-target
and optimized discarded-AST validity questions remain dependencies, not excuses
to weaken local evidence. Final blocker reconciliation can use this evidence,
but candidate promotion and independent complete certification are not ready.
No subsequent phase is begun.
