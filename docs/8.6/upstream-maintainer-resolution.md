# PHP 8.6 upstream maintainer resolution

October 28, 2026 — Phase 7H. **PHP 8.6 remains draft and uncertified.**
CA86-001 and PHP86-015 now have attributed upstream explanations. Their original
certification requirements are only partly satisfied, so both remain blocking.
PHP86-014 remains unresolved. No EBNF change, claim exclusion, release waiver,
package promotion or subsequent phase is authorized by this report.

## 1. Authenticated upstream record

The [response index](upstream-maintainer-resolution/upstream.json) preserves
exact comment bodies, IDs, authors, GitHub author associations, creation/update
timestamps and permanent URLs. Adjacent raw issue, comment and event responses
retain their HTTP headers, retrieval times and SHA-256 digests. These were fetched
directly over HTTPS from `api.github.com`; all responses returned 200. Comment
counts agree with issue metadata, with no next-page link. Both closing actors and
the explanatory comments identify `iluuu1994` (GitHub `MEMBER`). This is source
authentication under the existing local custody model, not a cryptographic
signature or assurance that upstream comments cannot later be edited.

| Finding | Exact issue | Created (UTC) | Closed (UTC) | State/reason |
|---|---|---|---|---|
| CA86-001 | [#24795](https://github.com/php/php-src/issues/24795) | 2026-10-27 17:18:49 | 2026-10-27 23:00:29 | closed / not_planned |
| PHP86-015 | [#24794](https://github.com/php/php-src/issues/24794) | 2026-10-27 16:56:26 | 2026-10-27 23:05:40 | closed / not_planned |

PHP86-015 was located through the retained closed-issue search for the reporter
in `php/php-src`, then matched by its exact logical, dynamic-call and ternary
examples and source pin. It is not PR #19658 or an inferred duplicate.

CA86-001 comments: maintainer [initial response](https://github.com/php/php-src/issues/24795#issuecomment-6449699275)
(October 27, 17:59:55 UTC), reporter [reply](https://github.com/php/php-src/issues/24795#issuecomment-6451988015)
(19:49:12 UTC), maintainer [explanation](https://github.com/php/php-src/issues/24795#issuecomment-6455687623)
(23:00:29 UTC), and reporter [acknowledgment](https://github.com/php/php-src/issues/24795#issuecomment-6461721039)
(October 28, 05:46:59 UTC).

PHP86-015 comments: maintainer [explanation and closure](https://github.com/php/php-src/issues/24794#issuecomment-6455774723)
(October 27, 23:05:40 UTC), and reporter [acknowledgment](https://github.com/php/php-src/issues/24794#issuecomment-6461672069)
(October 28, 05:44:20 UTC). Created and updated timestamps match for all six
captured comments. No message was posted by this phase.

## 2. CA86-001: skipped contextual compilation

The maintainer explains that the compiler skips a right-hand expression known
to be dead. Its contextual compile-time errors can consequently be skipped.
Checking such errors would require compiling the discarded AST while throwing
away its opcodes; the maintainer declines that complexity and closes the issue
as not planned. This is confirmed behavior, not an outstanding request to decide
whether the observed discarded-hook acceptance occurs intentionally.

The five retained paired hook controls distinguish discarded ordinary and
ternary-initializer closures, retained closures, ordinary live declarations and
valid hooks. Skipped compilation explains acceptance of the discarded unknown
hook; it does not make unknown hooks valid in compiled declarations. Parser and
lexical rejection still precede elimination. Nor does the explanation mean every
runtime-unreachable statement is eliminated before compilation.

The source remains RC4 `f23b4c222cebe33be93ee8ecaaa14390c5507a08`:
parser `property_hook` admits `T_STRING`; `zend_get_property_hook_kind_from_name`
and property-hook compilation enforce get/set when visited. Original source
locations, slices and ten observation pointers remain in the
[Phase 7G reproducer record](upstream-language-validity-clarification/reproducers.json)
and [hook adjudication](conformance-scope-adjudication/hook-adjudication.json).

**Disposition:** upstream intent/explanation is resolved; D-U03 remains partial.
The original requirement is an explained divergence accepted within an explicit
policy-compliant claim, or a separately authorized correction. The EBNF still
rejects discarded unknown hooks accepted by PHP. Explaining that mismatch alone
does not satisfy the requirement. CA86-001 and `discarded-hook-disposition`
remain blocking until separate Gate B work or an independently justified,
enforceable scope exclusion is reviewed and accepted. Neither is granted here.

## 3. PHP86-015: general cause and bounded version differences

The maintainer links #24794 to #24795 and explains that the cause is not specific
to `printf()`: standalone `true || (unset)$x` also demonstrates skipped errors.
The closure is a wontfix decision, not a landed fix or a new PHP syntax rule.

The explanation and the earlier observations answer different questions:

| Lint-only expression | PHP 8.5.10 distribution | Exact-source PHP 8.6 RC4 |
|---|---|---|
| `true || (unset)$x;` | accepts | accepts |
| `printf(true || (unset)$x);` | accepts | rejects unsupported unset cast |
| dynamic `printf` call with the same logical argument | accepts | accepts |
| `printf(false ? (unset)$x : "ok");` | rejects unsupported unset cast | accepts |

The standalone example demonstrates the general elimination mechanism. It does
not say that all call paths visit the same AST nodes. The pinned
`zend_compile_func_printf`, `zend_compile_func_sprintf` and
`zend_eval_const_expr` mappings explain why specialization and ordinary
compilation can differ. The recorded opposite version outcomes remain evidence;
they are not withdrawn or relabeled as a function-specific language feature.
PHP 8.5.10 is a distribution comparator, not an attested build of the PHP 8.5
source pin `7a4c62795365ed6a97a0184c96375b9fb4d53b1e`.

**Disposition:** the general cause and upstream decision are resolved; D-U02
remains partial. Its original criterion requires coherent implementation and
live/retained/discarded correspondence, without inventing a universal argument
traversal rule. The maintainer reply does not individually adjudicate the 31
retained direct/imported/qualified/namespace/dynamic/named/unpack/PFA and
initializer/logical/ternary/coalesce controls. PHP86-015 and `folding-order`
remain blocking for that bounded reconciliation and conformance work. The
structural matcher still does not enforce this compiler traversal model.

## 4. Specification, ledger and certification implications

[php.md](../../grammar/8.6/php.md) and [changes.md](../../grammar/8.6/changes.md)
receive informative clarifications only. All productions, terminals, source
pins, fixtures and the inherited get/set restriction remain unchanged. No
separate Gate B review establishes a structural change in this phase.

The additive [work ledger](upstream-maintainer-resolution/work-items.json)
retains all eleven original Phase 7D work items and their completion criteria,
all seven original blocker rows and all nine blocked gate requirements. It
records D-U02/D-U03 progress and retains Phase 7E/7F predecessors. The
[dispositions](upstream-maintainer-resolution/dispositions.json) narrow the
unanswered questions but retain every blocking flag. Historical findings,
drafts, observations, source slices, pins and prior certification receipts are
preserved rather than rewritten. These explanatory successor records do not
replace the executable certification authority or silently change a contract.

All seven blockers remain: **PHP86-013, PHP86-014, PHP86-015, CA86-001,
C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE**. In particular, neither upstream
reply decides PHP86-014 writable-target validity. Dependent discovery/contextual
requirements remain. No closed GitHub issue automatically closes a release gate.

## 5. Validation and remaining work

[Focused regression](upstream-maintainer-resolution/regression.json) records
18 fresh lint observations with zero mismatches: sixteen retained selected
observations and the standalone maintainer witness on both interpreters.
Fixture programs were not executed. The full retained 31-control traversal and
five-pair hook investigations remain available; this selection is not a new
exhaustive matrix. Source/executable authentication, traceability, historical
integrity and certification command receipts are recorded in the
[validation appendix](certification-contract-remediation/phase7h-validation/results.md).
That appendix distinguishes fresh checks, failed/stale prerequisites, blocked
obligations and historical counts; no prior result is presented as a new run.

The prior Phase 7F 29 passed / 0 failed / 9 blocked assessment is historical
after these input changes. Current evidence must use new revisions of the
existing compatibility and v2/v4 certification contracts, with independent
verification. No old report is regenerated and no freshness exclusion is added.
Any failed prerequisite remains visible and prevents a fresh certification claim.

Remaining work is the original finite ledger: hook structural/contextual
adjudication, traversal-boundary reconciliation, unresolved writable-target
validity, residual discovery, contextual predicate enforcement/scope, meaningful
coverage/scanner/binding closure and complete release/final-source evidence.
PHP 8.6 stays `draft`; release gates and promotion requirements remain in force.
