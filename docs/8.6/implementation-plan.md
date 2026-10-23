# Proposed minimal PHP 8.6 implementation plan

This document proposes future work. It neither starts Phase 3 nor creates a
grammar package. Material discovery gaps in
[PHP86-013](language-change-inventory.md#php86-013--remaining-exhaustive-audit-work)
must be resolved or adequately bounded and this research reviewed before
baseline copying proceeds. Final-certification obligations can remain open
without automatically blocking an unchanged baseline.

The current [Phase 2.2 risk assessment](discovery-risk-assessment.md) retains
**NOT READY** for baseline creation, complete syntax implementation and final
certification for separate reasons. Its phase-specific blocker matrix supersedes
the earlier blanket treatment of library/API integration and raw ledger totals.

## Research completion gate

Apply these steps by source risk, not by requiring individual inspection of
every library test. Evidence-backed grouped exclusions are allowed; filenames,
expected-output changes and absence of a headline RFC are insufficient.
Full unrelated API integration/runtime certification is not a copying gate.

1. Disposition every changed upstream test family, including relevant opcache
   tests outside the Zend-only index. Separate source changes from diagnostic,
   runtime and infrastructure changes; minimize any new acceptance difference.
2. Finish durable per-hunk compiler/AST/reference-flow reconciliation. In
   particular examine list references, nested assignment, pipe return references,
   optimized calls, initializer traversal and discarded declarations.
3. Complete each remaining RFC's selected-pin implementation/status record and
   cross-check recent rejected/withdrawn proposals. Do not equate an index entry
   or a PR URL with a merged implementation.
4. Add exact PHPT anchors for family-level entries, record deferred-linking
   validation evidence, and resolve any conflicting oracle/source results.
5. Review later published RCs incrementally. RC4 evidence is not final-release
   compatibility evidence, and a matching binary version is not exact-pin build
   provenance.

## Later baseline preparation

After approval and readiness, follow versioning policy: byte-for-byte copies of
the immediately preceding standalone 8.5 artifacts, recording repository commit
`796da52c86a742dd82227d312df22452aefdec42` or the actual reviewed baseline if it
changes. Preserve 8.5 fixtures, grammar, specification and evidence. Register a
development package only in its authorized implementation phase. Create the
required `changes.md` then, never as a reconstruction dependency.

## Minimal Phase 4 edits

| IDs | Existing production or specification section | Proposed edit | Fixtures and risks |
|---|---|---|---|
| PHP86-001 | `argument-list`, `ordinary-argument-list`, `first-class-callable-arguments`, `argument-no-expression`, `constructor-argument-list`, `clone-argument-list` | Add placeholder alternatives and trailing-comma callable form; preserve production names and disjoint derivations; review constructor/clone paths separately | Positional/named placeholders, bound arguments, unpacking/order failures, clone and constructor distinctions, malformed markers, 8.5/8.6 boundaries |
| PHP86-001 | Arguments and arrays; Attributes; Callable conversion; Constant-expression validation order | Describe PFA-specific compiler checks and traversal; do not structurally forbid discarded checks | Ordinary versus constant initializer, false-branch and discarded closure pairs, attribute/nullsafe/constructor negatives |
| PHP86-002 | Callable conversion / PFA explanation | Clarify required `?` and optionality of `...`; no extra production | Separate signature behavior from source acceptance |
| PHP86-003 | Properties contextual constraints | Remove ordinary readonly-default prohibition only | Typed and readonly-class defaults; static, untyped, hooks, interfaces, inheritance and discarded declarations |
| PHP86-004 | Writable variables and destructuring | Allow property writes through constant object paths; retain direct constant/array-write prohibition | Assignment/reference/unset/inc paths, nested dimensions and runtime type distinction |
| PHP86-005 | Attributes; Class constants; Enums | Expand Override target and inherited-constant constraints | Matching/nonmatching and private targets; enum/interface and delayed-linking checks |
| PHP86-006 | Enums | Permit `__debugInfo`; preserve remaining magic-method prohibitions | Linking positive and other magic-method negatives; lint is insufficient |
| PHP86-007 | Control-flow/reference constraints | Reject live nullsafe method-call reference returns/yields at the appropriate contextual stage | Live/discarded pairs and ordinary method-call controls |
| PHP86-008 | Types / historical notes | Preserve qualified-static rejection; record historical crash separately | No claim that an oracle crash is accepted syntax |
| PHP86-009/011 | Parameters / closures | No EBNF edit for metadata or caching; retain explicit static constant-closure restrictions | Comment/default and closure/pipe regression families |
| PHP86-010 | Deprecated but accepted syntax; Names and tokens | Document warnings without reserving let/is or removing accepted alternatives | Positive deprecated forms and context/case variants |
| PHP86-012/013 | None yet | Exclude proposals; investigate unresolved interactions | No speculative fixtures or grammar changes |

The PFA list design must be reviewed before choosing a new helper production.
Current ordinary/FCC lists are disjoint and clone avoids a unary-expression
ambiguity. Simply inserting bare `...` everywhere would create overlapping
derivations or apply contextual restrictions at the wrong stage. No existing
production removal, renaming, precedence change, or token/state change is
currently justified by the reviewed evidence.

## Validation obligations for implementation

Run EBNF structural/reference checks and Markdown parity; verify manifest
discovery and version-specific lexer/parser selection. Reproduce the unchanged
8.5 regression families for 8.6 before assessing new syntax. Add positive,
structural-negative, contextual-negative and version-boundary pairs for every
material finding. Use the appropriate interpreter, disclose provenance, and
separate parser, compile, linking and runtime evidence.

Rebuild source locks, reconciliation, contextual-predicate coverage and blocker
dispositions for 8.6. Do not copy 8.5 coverage percentages or certification.
Implement the future change-traceability validator contract only in its
authorized phase; account for additions/removals/modifications, optionality,
repetition, token references and lexical/contextual changes without EBNF edits.
Preserve existing release gates and report unresolved discrepancies explicitly.

## Historical preservation

This phase adds only `docs/8.6/` research artifacts. The source hashes and lint
observations here are evidence, not package registration or conformance fixtures.
Known 8.5 discarded-declaration, contextual-adapter and qualified-static issues
remain separate historical correction work. No requirement to make 8.6
standalone authorizes changes to the preceding version.

## Phase 2.1 readiness decision and revised obligations

**NOT READY.** The [closure assessment](discovery-closure.md) and machine-readable
ledgers preserve unfinished work; PHP86-013 remains open. No baseline copy,
grammar directory, manifest registration, fixture change, or release-gate change
is authorized by this report.

Resolve PHP86-014 before deciding whether assignment-based write contexts need
new contextual acceptance rules. RC4 lint accepts ordinary/list assignment
dimension writes that fail controlled execution with an invalid-opcode error;
the reference-assignment control succeeds. Preserve these as distinct cases,
including negative read/write and destructuring controls. Reconcile the intended
language boundary and exact-pin implementation before changing
`assignment-expression`, `variable-expression`, or dereference documentation.

Continue the research gate using the ledgers: disposition 5,862 indexed tests
and 87 examined records with unresolved external inputs as relevant or excluded
with evidence, review 555 indexed compiler hunks and remaining source paths,
and complete each RFC's selected-pin component review. Primary implementation
ancestry for 28 candidates does not prove that all optional parts survived at
RC4. Stream-errors and optional-placeholder commit checks remain unresolved;
preserve existing source evidence instead of treating an unmerged PR or
placeholder URL as implementation proof.

The 41 new probes strengthen PHP86-001/003/004/005/006/007/011 evidence. Use their
explicit lint, declaration-link, and controlled-runtime stages for future
conformance work. Dead statements and folded-away declarations require separate
expectations. The PHP 8.5 package and historical certification remain untouched.

## Phase 2.2 implementation boundaries

Keep PHP86-014 blocked specifically for assignment-based writable-target edits.
The pinned compiler/VM operand mismatch explains the invalid-opcode observation
but does not settle intended syntax. A successful reference-assignment write
must not authorize ordinary/list assignment writes. This bounded discrepancy
alone need not block an unchanged baseline after discovery is adequate.

Add PHP86-015 to the future contextual-validation work: printf specialization
changes whether discarded unset casts are visited, in both acceptance
directions. Map `function-call`, `cast-expression`, `conditional-expression`,
`coalesce-expression`, `boolean-and-expression` and their specification
constraints. No terminal or EBNF change follows from the current evidence.
Use direct/dynamic/fully qualified calls, live/dead/discarded controls and
one/two-argument boundaries. The new probes are research observations, not
registered conformance fixtures.

Prioritize the remaining source queue by checks and traversal: optimized calls
and fallback paths, constant/declaration traversal, interface inheritance order,
and reference/writable contexts. Associate their relevant PHPTs before disposing
of unrelated groups. The 255 mechanical C hunks, 82 Windows test deltas and 20
API-only RFC scopes now have explicit bounded exclusions; do not re-expand
these into a requirement for full runtime certification. Other records retain
their unresolved status. PHP86-013 remains open because material discovery is
unfinished, not because a numerical coverage target has not been met.
