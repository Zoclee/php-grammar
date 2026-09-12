# PHP 8.5 final completeness verification — Phase 7 of 7

**Decision: Yes — grammar-complete, with documented external contextual
constraints. Conformance evidence is bounded and uses PHP 8.5.10.** The package
can be released as a complete syntax grammar under that definition. It cannot
be advertised as a complete repository-only PHP source validator, an exact-pin
executable certification, or exhaustive equivalence.

“Canonical” denotes the repository's authoritative EBNF, not an official PHP
specification. “Grammar-complete” accounts for known constructs and classifies
all coverage gaps. “Conformance-validated” applies only to named, passing
matrices. See [the release policy](../conformance-policy.md) for the definitions
and [final-evidence.json](final-evidence.json) for the machine-readable decision,
14 major areas, nine evidence categories, 73 restrictions, historical issue
table and individual blocker records.

## Scope and source authority

The package describes byte-oriented PHP 8.5 syntax: source/PHP/HTML transitions,
names/imports, types, expressions and precedence, dereferencing/calls, variables,
arrays/destructuring, statements/control flow, functions/closures/arrows,
classes/interfaces/traits/enums, property hooks, attributes, constant-expression
forms and PHP 8.5-specific syntax. Each has positive and nearby-invalid evidence
anchors in the final ledger. Scanner primitives and ordered contextual compiler
constraints supplement the EBNF. Structural matching intentionally accepts
parser-valid forms that a surviving compilation check rejects.

The normative source is PHP revision
[`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`](https://github.com/php/php-src/tree/7a4c62795365ed6a97a0184c96375b9fb4d53b1e).
The [source lock](../../tools/php85-source-lock.json) pins parser, scanner and
compiler SHA-256 hashes. PHP 8.5.10 is the executable oracle, with ext-ast 1.1.3,
schema 120. Both short-tag configurations are tested; `zend.multibyte=0`.
Multibyte script transcoding, recovery ASTs/diagnostic wording, runtime execution,
autoloading, name resolution, resolved value/type compatibility and optimizer
equivalence unrelated to source validity are outside the contract.

## Final grammar, parser and scanner reconciliation

The canonical EBNF and its Markdown productions are unchanged in Phase 7.
Checkout attributes preserve the audited LF/CRLF source and fixture bytes;
Python report serialization now fixes its existing CRLF convention explicitly.
This corrects a reproducibility hazard from automatic Git newline conversion
without changing fixture contents or grammar productions.
Coverage remains **301/364 productions (82.7%) and 606/790 alternatives (76.7%)**;
attempted coverage is 301 productions and 607 alternatives. There is no
denominator change and **zero unclassified meaningful grammar gaps**.

| Uncovered classification | Productions | Alternatives |
|---|---:|---:|
| Primitive-bypassed | 52 | 174 |
| Trivia-removed | 11 | 9 |
| Scanner-context-only | 0 | 1 |

The scanner-only alternative is `reserved-non-modifiers/alternative:25`: an
unmodified `enum` trait alias before `;` is an identifier in Zend's lookahead
context. The prior never-parameter contextual-only classification stays removed:
a discarded closure provides a valid positive witness. No meaningful element is
hidden by a raw percentage target.

[Parser reconciliation](phase6-reconciliation.json) maps all **177 productions
and 623 alternatives/actions** with explicit canonical anchors. Three `backup_*`
productions are internal metadata; nested `__halt_compiler()` is an error-only
alternative. Bison precedence becomes expression layers, dangling else becomes
matched/unmatched statements, list recursion becomes repetition, and aggregate
strings/name tokens use documented scanner abstractions. Compiler-only
restrictions remain contextual. Mappings and sources are checked against the
unchanged final grammar, not copied into a new unverified map.

[Scanner evidence](phase5-lexical-evidence.json) retains **190 rules, 25 families,
11 states, 2,700 direct cases, 101 primitive cases and 144 syntax cases**.
Primitive-bypassed bodies retain direct lexical links. Represented-differently
families retain byte/token contracts and justifications. Phase 7 changes no
scanner architecture or source-recognition code.

## Positive, negative, AST and folding evidence

The ordinary corpus remains **653 valid, 362 structural-negative and 356
contextual-negative files**, including both short-tag profiles: **1,371 paired
comparisons**. The default positive profile has 648 files; five more use short
tags disabled. The Phase 4 ledger retains its 14 boundary categories and reviewed
contextual reclassifications. New Phase 7 witnesses live in dedicated diagnostic
and interpolation matrices, so they do not inflate ordinary fixture totals.

The structural matrix covers **15,342 sources**, including **14,287 unique
accepted derivations/AST comparisons and 1,055 parser rejections**. The explicit
AST set contains **63 positive and 10 negative witnesses**. The additional
34-case ambiguity matrix has 32 unique raw derivations and two intentionally
retained helper ambiguities: `public int $x;` and
`public int $x { get => 1; set {} }`. In each, `int` is both an explicit builtin
type literal and the equivalent `name` helper. Type spans and normalized
structure agree. No other ambiguity is observed in these matrices; this is not
a global uniqueness proof.

[Interpolation binding](interpolation-binding.json) adds **44 positive sources,
six malformed sources and 54 embedded EBNF operand comparisons**. An audit-only
view uses repository brace matching and interpolation fragments to expose text,
simple variables, offsets, properties, complex/dynamic properties, nested braces,
`${}` variable variables and arithmetic operands in double quotes and heredocs.
Ordered normalized segments are compared with Zend; unique EBNF derivations
force arithmetic operand boundaries with parentheses. Zend must preserve the
binding. The normalizer removes only `${}` surface flags, empty text segments
and integer unary-minus versus numeric-offset representation differences;
operator identities, operand order and dynamic access remain significant.

This closes the absence of any aggregate binding evidence, not every aggregate
form. Nested aggregate strings, closures/classes inside embedded expressions,
escape-value and heredoc dedent normalization, bare string-offset operand
derivation and arbitrary recursive binding remain C3. Unsupported audit-view
escapes fail explicitly. Production recognition continues to use its existing
scanner/EBNF checks and the broader acceptance corpus.

Folding evidence remains **115 declaration families × 16 templates = 1,840
comparisons**, **70 direct ordinary/constant-context cases**, and the existing
38-case remediation matrix in PHPUnit. Modifier evidence has **584 comparisons**
over eight targets. Recursive source acceptance has **156 comparisons**, malformed
recursive/recovery evidence has **16**, and the scanner product has **482**.

`false && (unset) 1;` is valid ordinary source because folding discards the cast.
The corresponding constant initializer rejects: constant logical evaluation
visits children first. Later declaration predicates must inspect only surviving
nodes; early modifier predicates run before folding. A structural negative that
depends on an obsolete pre-fold assumption would fail the differential gates.

## Whole-source validation and diagnostic disposition

Whole-source contextual validation was re-evaluated and deliberately not
implemented through token-pattern guesses. `PhpGrammarMatcher` returns structural
recognition results, not a source AST with declaration scopes. The audit forest
uses host tokens and lacks aggregate primitives and ordered folding. Neither is
a robust production representation for contextual traversal. The existing
`Php85ModifierValidator` remains a rule-level API for supplied target/modifier
lists; a null result certifies only that list.

The exact external rule inventory is in `final-evidence.json/restriction_inventory`:
modifiers; type positions/combinations/scopes; parameters, promotion, returns and
captures; properties/hooks; enum consistency; trait adaptations; write/reference
targets; calls/attributes; constant-expression folding; control/declaration scopes.
These 73 descriptions must not be mistaken for 73 implemented source rules.
Three descriptions concern resolved names, values or members and are explicitly
out of scope in the ledger; the remaining descriptions retain their syntax and
contextual implementation dispositions.

[Diagnostic dispositions](diagnostic-dispositions.json) audit all **244 direct
compiler fatal sites** with exact source locations, functions, diagnostics,
source context, enforcement layer and final disposition. **20 sites** now have
observed diagnostic-specific negative/repair pairs (**40 comparisons**), including
early encoding literals, variadics, void/never parameters, promotion, hook names
and duplicates, enum cases/properties, trait aliases, argument ordering and
constructor/nullsafe callable conversion. All negatives remain structurally
accepted, confirming the external boundary. Diagnostic text plus a repaired
positive is evidence for the named behavior, not instrumented C branch coverage.

**13 early modifier sites** have systematic rule evidence; **10 direct sites**
are individually excluded for resource/internal errors or resolved binding/value
type semantics. The remaining **201 direct sites** are individually marked
unresolved for predicate isolation, with function-family evidence retained.
The 84 delegated candidates have 77 unresolved and seven excluded dispositions.
Delegated diagnostic calls and attribute-validation returns from `zend_ast.c`,
`zend_inheritance.c`, `zend_enum.c` and `zend_attributes.c` are separately indexed.
Mixed source/value/name predicates are conservatively unresolved; nonfatal,
internal registration and resolved enum-value checks have explicit exclusions.
This inventory does not pretend that diagnostic-call extraction discovers every
indirect failure path or proves delegated reachability.

## Exact-source executable and source diff

No exact-pin executable was built. The environment has no configured `cl`/`nmake`,
container engine or WSL distribution; obtaining a trustworthy matching build
would require provisioning a build environment and PHP dependencies. The
existing Windows CLI reports PHP 8.5.10, but its commit identity is unverified.
The measured `php.exe` SHA-256 is
`32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9`;
this identifies the tested file without asserting how it was built.

The official annotated release tag resolves to commit
`34308a6666b2d489c509541ea9befea9e2b42348`.
[Source correspondence](source-correspondence.json) records immutable hashes and
the complete seven-file release-to-pin diff. The parser, compiler, AST,
inheritance, enum and attribute files are byte-identical. The only differing
file is the scanner: bounds handling in `zend_multibyte_detect_utf_encoding`
changes UTF-16/UTF-32 detection. That path belongs to multibyte preprocessing,
disabled in the audited profile. No syntax-path difference was found in these
seven files for the declared byte-source profile. Headers, generated files,
build options and binary provenance are not covered by that comparison, so C4
is partially closed rather than silently replaced by a nearby release.

## Final blocker dispositions and release impact

| Blocker | Final disposition | Exact residue and risk | Release impact / next work |
|---|---|---|---|
| C1: source contextual validator | Accepted limitation | In-scope restrictions within the ten areas listed above need source-context integration; structural matching can falsely certify invalid live source, and unconditional checks can reject discarded forms. Existing modifier lists do not identify source targets. | Allow grammar release with external constraints; prohibit whole-source validator claims. Build repository structural/scoping/folding representation first. |
| C2: diagnostic predicates | Partially closed with precisely defined residue | The 201 unresolved direct site IDs and unresolved delegated candidate IDs in `diagnostic-dispositions.json`; each has source/function/trigger and evidence. Unknown guards can hide accept/reject differences outside the sample. | Prohibit complete compiler-predicate validation claims. Isolate guards or justify individual scope exclusions. |
| C3: aggregate binding | Partially closed with precisely defined residue | Nested aggregate/closure/class expressions, dedent/escape normalization, string-key operand derivation, unbounded recursion; possible binding disagreement beyond the new subset. | Advertise bounded interpolation evidence only. Extend the derivation adapter and check the existing depth-six recursive corpus. |
| C4: exact executable | Partially closed with precisely defined residue | Exact binary revision, headers/generated sources/build options; multibyte preprocessing outside the profile. | No exact-pin executable claim. Provision the pinned build with ext-ast and rerun every gate. |

Each machine-readable blocker also records authoritative evidence, why it
remains, affected syntax, risk, release impact and recommended work. There are
four restrictions on stronger conformance claims; none demonstrates an
unaccounted-for EBNF construct in the declared grammar-completeness definition.
These limitations are not a claim of no possible undiscovered mismatch.

## Historical closure, release gates and reproduction

The [consolidated historical table](historical-dispositions.md) and final ledger
record the **79 historical dispositions** from Phase 6,
updates source validation/aggregate binding/executable statuses and retains
original source and regression evidence. The original index assigned no
severity labels; the final table records that explicitly rather than inventing
retrospective severities. Previously fixed scanner/grammar defects stay fixed;
historical counts and superseded assumptions remain visible as provenance.
Historical Phase 1–6 documents are retained and point to this report.

The [regression/release policy](../conformance-policy.md) defines mandatory
source, positive/negative, lexical, binding, contextual and coverage evidence
for future changes. [Versioning guidance](../versioning.md) requires future
versions to inherit methodology and independent evidence, never grammar
dependencies. The existing single-version manifest and boundary test framework
remain compatible; no future version has been fabricated.

`composer release:check` runs the complete 23-gate set: strict Composer validation,
PHPUnit, grammar/lexer coverage, ordinary differential, explicit/systematic AST,
declaration folding, Phase 6 matrices and freshness, scanner product, both lexical
oracles, positive/negative report freshness, compiler source hashes, parser
reconciliation, Phase 6 evidence, interpolation, diagnostics, source correspondence,
final certification and whitespace review. It writes logs and `results.json`
under `.audit/phase7-validation/` and returns failure if any gate fails. See the
policy for prerequisites, source fetching and explicit binary selection.

## Final verification on 2026-09-12

All 23 release gates passed on PHP 8.5.10/ext-ast 1.1.3. `php` below denotes that
configured executable; Python source checks use the pinned cache. The initial
runner launch lacked the intended ini because of shell argument expansion and
was stopped; the corrected launch enabled the required extensions and completed
every gate. No failing launch is counted as conformance evidence.

| Command | Result |
|---|---|
| `composer validate --strict` | Pass |
| `composer test` | Pass: 5,108 tests, 48,147 assertions |
| `composer grammar:coverage` | Pass: 301/364 productions, 606/790 alternatives; zero unclassified meaningful gaps |
| `composer lexer:coverage` | Pass: 190 rules, 2,700 direct, 101 primitive, 144 syntax cases |
| `php bin/php85-conformance.php /path/to/php85` | Pass: 1,371 comparisons; 653 valid, 362 structural-negative, 356 contextual-negative; zero mismatches/exceptions |
| `php tools/php85-ast-conformance.php` | Pass: 63 positive, 10 negative |
| `php tools/php85-systematic-structure.php` | Pass: 15,342 sources; 14,287 accepted unique derivations/AST comparisons, 1,055 parser rejections |
| `php tools/php85-boundary-folding.php /path/to/php85` | Pass: 1,840 comparisons |
| `composer conformance:phase6` | Pass: 860 cases, including 584 modifiers, 70 folding, 34 ambiguity, 156 recursive and 16 malformed |
| `php tools/php85-phase6.php --check` | Pass: matrix freshness |
| `php tools/php85-scanner-product.php` | Pass: 482 comparisons |
| `php -d short_open_tag=1 tools/php85-lexer-differential.php` | Pass: 2,342 token comparisons, 84 rejection checks, 144 syntax checks, 648 corpus streams |
| `php -d short_open_tag=0 tools/php85-lexer-differential.php` | Pass: 273 token comparisons, five corpus streams |
| `php tools/php85-coverage-report.php --check` | Pass |
| `php tools/php85-negative-report.php --check` | Pass |
| `python tools/php85-compiler-boundaries.py .audit --check` | Pass: three source hashes, 244 sites, 87 functions, 14 early sites |
| `python tools/php85-reconcile.py .audit --check` | Pass: 177 productions, 623 alternatives |
| `python tools/php85-phase6-evidence.py --check` | Pass |
| `php tools/php85-interpolation-binding.php --check` | Pass: 44 positive, six malformed, 54 operand comparisons |
| `php tools/php85-diagnostic-witnesses.php --check` | Pass: 20 sites, 40 comparisons |
| `python tools/php85-source-correspondence.py --check` | Pass: seven files compared, six identical; exact binary remains unverified |
| `python tools/php85-certification.py --check` | Pass: 14 areas, 73 restrictions, 79 historical dispositions, four final blocker records |
| `git diff --check` | Pass |

The enabled lexical oracle retains one intentional tokenizer-recovery exception
for malformed halt directives and four expected escape-overflow diagnostics.
Neither is an unexplained source-validity mismatch. Integrity, Markdown parity,
contextual expectation checks, remediation folding and version-boundary/repository
consistency are included in PHPUnit.

After fixing report serialization, the evidence-focused recheck passed **191
tests and 1,053 assertions**, and source/report freshness checks passed again.
An isolated Git index/checkout reproduced **1,520 certification inputs
byte-for-byte** and passed final certification; the primary index was unchanged.
Mutation probes verified rejection of an unclassified grammar gap, stale input
hash and unknown diagnostic site. Source inventory regeneration separately
confirmed 177 parser productions, 190 scanner entries, 143 selected compiler
functions and 364 EBNF productions. Exact-pin executable validation was not run;
C4 records why and the alternative source evidence.

## Phase 7 file inventory

Added 17 repository files:

- `.gitattributes`;
- `docs/conformance-policy.md`;
- `docs/8.5/completeness.md`, `final-evidence.json`, `diagnostic-dispositions.json`,
  `diagnostic-witnesses.json`, `interpolation-binding.json`,
  `source-correspondence.json`, `historical-dispositions.md`;
- `tests/Php/InterpolationSegmentsTest.php`, `tests/Support/InterpolationSegments.php`,
  `tests/fixtures/php/8.5/diagnostic-predicates.json`;
- `tools/grammar-release.py`, `tools/php85-certification.py`,
  `tools/php85-diagnostic-witnesses.php`, `tools/php85-interpolation-binding.php`,
  `tools/php85-source-correspondence.py`.

Changed 11 repository files: `README.md`, `composer.json`, `docs/conformance.md`,
`docs/versioning.md`, `docs/8.5/phase6-conformance-closure.md`,
`docs/8.5/phase6-evidence.json`, `docs/8.5/phase6-reconciliation.json`,
`tools/php85-compiler-boundaries.py`, `tools/php85-phase6-evidence.py`,
`tools/php85-reconcile.py`, and `tools/php85-source-inventory.py`.
The historical JSON changes refresh generator/dependency hashes only.
No repository files were removed. Source downloads, oracle binaries, probe
checkouts and command logs remain in ignored `.audit/`.

Recommended release wording:

> PHP 8.5 is grammar-complete, with documented external contextual constraints;
> conformance evidence is bounded and uses PHP 8.5.10.

PHP 8.5 grammar status: Grammar-complete with documented external contextual constraints and bounded conformance evidence.
