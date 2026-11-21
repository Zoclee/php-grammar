# Stable structural grammar certification

The maintainer-authorized **`php86-structural-v1`** contract certifies a
standalone, source-backed structural PHP 8.6.0 grammar. Its evidence schema is
`php86-structural-evidence-v1`; its executable gate inventory is
`StructuralCertification::gates()` in `tools/lib/StructuralCertification.php`.
It is separate from the preserved 36-gate and 38-obligation extended contracts.

## Claim and source profile

The claim covers the canonical productions, parser syntactic forms and token
categories in the authenticated PHP 8.6.0 GA parser/scanner, with the repository's
documented byte-oriented scanner abstraction. Both short-open-tag profiles are
tested. Bytes are not Unicode-normalized; `zend.multibyte=0` and `-n` define the
oracle profile. Comments/trivia and aggregate string tokens follow the normative
lexical contract and interpolation checks. Encoding conversion, malformed-input
recovery token streams and scanner implementation instrumentation are not claimed.

Contextual parser actions and compiler checks (including declaration legality,
get/set hook names, type relationships, writable-target validity and ordered AST
visitation) remain documented external constraints. Structural acceptance alone
is not valid-source acceptance. Expression precedence, associativity, token
boundaries and grammatical alternatives remain in scope; a known error in those
cannot be excluded as an AST-equivalence limitation.

Stable structural certification does not assert exhaustive parser/compiler
equivalence, a complete contextual validator, AST/opcode equivalence, constant
folding equivalence, runtime validity, mathematical language equivalence or 100%
fixture coverage. All known structural constructs must be represented and every
uncovered production/alternative must retain an evidence-backed classification.

## Independent release obligations

The runner independently checks official source identity and exact local build
provenance; immutable parser/scanner mappings; standalone loading; EBNF syntax,
references and structural validation; full generated Markdown parity; the five
PFA changes and separate hook correction; lexer profiles; positive and negative
fixtures; paired version boundaries; traceability and all ten change sections;
source references; explicit contextual boundaries and unresolved-defect refusal;
API/CLI behavior; unchanged PHP 8.5 grammar/specification/fixtures/evidence;
current shared compatibility; complete PHPUnit regression and infrastructure
negative tests; package inventory; and freshness with read-only verification.
Each gate executes a check, records command, exit status and output hash, and
fails closed. The gate inventory is versioned and must match the evidence exactly.

Negative tests must demonstrate refusal of malformed EBNF, missing references,
unreviewed alternatives, missing changes, wrong version-boundary expectations,
missing source mappings, stale source locks, tampered evidence, unresolved
structural defects, incorrect metadata, missing required tests and broken PHP
8.5 compatibility. A successful PHPUnit exit alone cannot grant certification.

## Historical and extended evidence

PHP 8.5's EBNF, Markdown, fixtures and historical evidence are immutable inputs.
Current PHP 8.5 API/tool and regression evidence is required separately. This
does not renew historical certification or authenticate the missing original
manifest. The missing historical digest and original failed checks remain visible
in the retained release reports. This is an explicitly authorized new claim,
not a successful result for the legacy historical aggregate.

The original seven blocker IDs and all 36/38 obligations remain under their
original contracts. New scope dispositions must identify source evidence and
explain structural relevance; an out-of-scope obligation remains unresolved.
Historical test assertions use authenticated predecessor inputs after a
versioned transition; new structural tests exercise the current grammar.
Neither changing old checkers to accept new hashes nor deleting failing tests
is an authorized transition.

## Evidence and promotion

Generation and verification are separate. Runs use new revision directories;
existing evidence is never overwritten. Inputs include governance, canonical
files, fixture discovery, shared implementation, tools, dependency lock and
installed dependency bytes. Run outputs and the derived sign-off are separately
hashed outputs, not excluded semantic inputs. Verification reconstructs commands,
inventories, checks and decisions; it rejects omitted gates, stale inputs and
unsupported claims. The custody model assumes trusted reviewed tooling and host
execution, not a hermetic or independently reproducible build.

The package remains `draft` until a complete structural run independently passes.
Promotion and consumer-document changes then require a final new execution
against the exact stable release inputs. The package inventory excludes temporary
builds, `.audit`, caches, Git internals and installed dependencies. Execution
evidence accompanies the distribution and is not recursively embedded in itself.
Publishing requires separate maintainer authorization.

The derived release sign-off is reconstructed deterministically from the final
independently verified evidence by `tools/8.6/structural-release.php`.
Its `--check-signoff` mode compares every byte with that reconstruction; the
sign-off is a companion output, excluded from the recursive input/package
inventory. Failed diagnostic runs remain immutable and do not authorize
promotion. The first diagnostic run also records input changes made while its
remaining gates ran; only a later frozen-input run can certify the release.
