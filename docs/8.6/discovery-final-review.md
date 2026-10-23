# PHP 8.6 Phase 3D discovery review

October 23, 2026. **PHP86-013 remains OPEN; discovery closure is NOT READY.**
This review adds source associations, upstream discussion, an omitted RFC
candidate, and 60 paired probes. It does not finish the substantive compiler,
optimizer, test-family, and RFC component audit. The filename records the
requested Phase 3D assessment, not a claim of final discovery or certification.
No canonical grammar or normative specification change is made in this phase.

The [evidence ledger](discovery-final-review.json) contains all new probe sources,
hashes, stage results, reviewed PHPT identities, source hashes, upstream URLs,
snapshot hashes, and per-finding dispositions. Historical Phase 2 fields and
assessments remain intact. Additive `phase3d` fields identify this review in the
existing source, compiler, test, RFC, and risk ledgers.

## Baselines and source coverage

Repository HEAD is `0730c0867f2de5f80b83d21bb3b3b2206e9ce483`. The existing
uncommitted Phase 3C work is part of the preservation baseline. Upstream pins
remain PHP 8.5 `7a4c62795365ed6a97a0184c96375b9fb4d53b1e` and PHP 8.6 RC4
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. All 341 original source,
archive, executable, and absent-side checks pass. No newer revision is substituted.

| Area | Review and limit |
|---|---|
| Parser/scanner | Complete textual diffs reread; dispositions below. No additional token, precedence, associativity, declaration syntax, or lexical-pattern change identified. Executable scanner equivalence remains unproved. |
| Eight-file compiler ledger | 54 raw hunks receive an additive source-review record: the complete inheritance diff and nine type/metadata/warning hunks. This includes previously examined/mechanical hunks; it is not 54 newly resolved risks. |
| Focused compiler/VM paths | Revisited printf/array_map dispatch, assignment-result handling, dimension writes, and property-cache implementation with selected-pin source and upstream history. Broader AST/optimizer coverage remains incomplete. |
| PHPT ledger | 32 individual records receive full available-side source review and hash verification. All sections and inline input were inspected; these are source reviews, not upstream test executions. |
| RFC ledger | Original 58 identities retained; one additional candidate reconciled separately. Current index refreshed; selected history/follow-ups reviewed. Full component/reversion reconciliation remains open. |

The Phase 2.2 totals—578 core hunks, 276 pending direct reviews, and 5,862
indexed-only PHPTs—remain historical counts. This review does not overwrite them
or count every new source association as a resolved record. Unannotated records
retain their earlier disposition. Files outside the eight-file ledger have no
default harmless classification. These gaps prevent closing PHP86-013.

## Parser and scanner reconciliation

The parser changes remain PHP86-001 argument-list/PFA construction and PHP86-009
parameter documentation capture. Moving `backup_doc_comment` after a parameter
default changes metadata attachment. Removing the empty `backup_lex_pos` action
does not remove a consumed terminal; the surrounding expression and function-flag
actions remain. Renaming the trait-method AST kind changes representation, not
trait source syntax. Token and precedence declarations remain unchanged.

The scanner diff retains all lexical patterns and states. It extracts the existing
AST compilation sequence into `zend_compile_ast_internal`, exposes an AST API,
changes TLS storage and line-counter types/formatting, removes redundant length
casts, and replaces exception save/restore around heredoc lookahead. That last
change is an error-state interaction, not evidence of lexical equivalence.
Ordinary heredoc, bad indentation, and unterminated-comment probes agree across
the two distributions. Reentrant compilation with a pre-existing exception,
nested interpolation, ZTS, and source-encoding profiles remain validation work.
No lexical or canonical EBNF edit is justified by this review.

## Compiler, AST, linking, and upstream tests

The full inheritance diff preserves subtype and member validity checks apart
from the already implemented PHP86-005 Override handling. Diagnostic rendering,
case-insensitive lookup helpers, allocation ownership for internal traits,
qualification/boolean changes, and unreachable returns do not establish additional
PHP source rules. Internal-trait APIs do not authorize new userland trait syntax.

The interface-order move is now tied to commit
`f93b17076a25e2a06af50a6d177ae4badc5e44c3` and
[PR 18622](https://github.com/php/php-src/pull/18622). Its motivation was preparation
for a generic-types proof of concept; this is not evidence that generics were
implemented. In RC4, inherited interfaces are installed before constant, method,
and property checks. Paired declaration tests cover transitive-interface
covariance, an incompatible return, a get-only property, Override, and the
non-enum/UnitEnum prohibition. Results show the expected existing constraints
and PHP86-005 boundary. Autoload/reentrant linking and broader interface graphs
remain unresolved; this finite matrix does not exclude that risk globally.

The 32 reviewed PHPTs cover qualified-static negatives, three parameter
doccomment Reflection tests, `let`/`is` declarations and aliases, readonly-name
and underscore-function controls, namespace constants, finally and constructor
diagnostics, three asymmetric-visibility regressions, and indirect UnitEnum
implementation. Exact identities and both side hashes are in the evidence ledger
and individual `phase3d` records. The underscore-function SKIPIF only avoids
gettext's existing `_` function; the Reflection tests use inline declarations
and `opcache.save_comments`, without external/generated PHP. No PHPT suite ran.

Parameter metadata tests now give PHP86-009 precise source associations.
PHP86-010 remains warning-only documentation work: `readonly` is accepted as a
function name with a warning, accepted as a method name without that warning,
but still rejected as a function import alias. `_` function names and aliases
are not deprecated by the constant-name change. A global `_` constant warns
during execution rather than lint. A return inside a closure nested in a finally
block does not inherit the enclosing finally warning. These distinctions belong
in the eventual prose and fixtures, not in new token reservations.

## PHP86-014: defect mechanism confirmed, intended language unresolved

The new probes reproduce the existing result with `-n`:

| Expression | PHP 8.5 lint / execution | RC4 lint / execution |
|---|---|---|
| `($v=[1])[0]=2` | Compiler rejection / same rejection | Pass / invalid opcode `23/2/1` |
| `$w=[1]; ([$v]=$w)[0]=2` | Compiler rejection / same rejection | Pass / invalid opcode `23/2/1` |
| `$w=[1]; ($v=&$w)[0]=2` | Compiler rejection / same rejection | Pass / both aliases become `[2]` |
| Assignment dimension read | Pass / success | Pass / success |
| Literal-array write | Reject / reject | Reject / reject |
| Folded-away ordinary assignment write | Pass / success | Pass / success |

Parser reduction, compiler acceptance, opcode generation, and execution are
distinct. `zend_compile_var_inner` dispatches ASSIGN/ASSIGN_REF; ordinary/list
results use TMP, while reference assignment preserves VAR outside R/IS contexts.
The pinned `ZEND_ASSIGN_DIM` container operands support VAR/CV, not TMP. This
explains the invalid-handler fatal and is compiler/VM defect evidence. It does
not decide whether the correct repair should reject or support these expressions.
The reference case's success likewise does not establish an intended syntax rule.

Newly fetched [GH20628 issue comments](https://github.com/php/php-src/pull/20628)
and review comments discuss faulty assertions and passing `new` expressions
to reference parameters. The latter was deliberately treated as a runtime bug
fix: the new paired `f(new stdClass)` witness lints in both versions, then changes
from an 8.5 notice to an RC4 Error. It belongs under PHP86-011's runtime/reference
reconciliation, not a structural source rejection. None of the fetched discussion
decides the exact dimension-write cases. This bounded search is not proof that
no upstream discussion exists elsewhere.

**Disposition:** unresolved language discrepancy with a source-explained
compiler/VM inconsistency. Keep assignment-based writable-target changes blocked.
No EBNF or contextual-prose expansion is authorized. The existing constant-object
rules in PHP86-004 remain separate.

No exact-pin executable was built. `cl`, `nmake`, `bison`, and `re2c` were absent
from PATH; CMake alone does not supply the Windows PHP SDK. An elevated read-only
`wsl --list --quiet` returned no distributions. This identifies no ready local
build environment, not impossibility of building elsewhere. The existing
distribution hashes and version labels remain corroboration, not exact-pin proof.

## PHP86-015: validation order depends on call dispatch

`zend_compile_func_printf` at RC4 line 5264 evaluates the first argument before
ordinary compilation and may invoke the existing sprintf optimizer. The
constant evaluator visits both AND/OR children, but can discard a known untaken
conditional/coalesce branch. The specialization can therefore reject a source
that ordinary compilation accepts, or accept one that ordinary compilation rejects.

The new matrix confirms both directions for direct calls and compares dynamic
printf, existing sprintf behavior, live unset casts, one/two arguments, and a
format-string second argument. Fully qualified and imported printf calls take
the specialized path. An unqualified printf call inside a namespace retains
fallback dispatch and does not show the AND rejection. Unreachable statements
are still checked; a folded-away enclosing closure or anonymous class avoids
compiling its body. This extends PHP86-015's boundaries without creating a new ID.

Commit `26c96d38f440a5b6476a97527e1748f9bf187541`,
[PR 19658](https://github.com/php/php-src/pull/19658), and follow-up
`09c39a8fd9e944a1c0e85cdaf542f838440f4d63` describe optimization and constant-result
handling. They do not establish an intended language rule for discarded casts.
The array_map follow-ups, including strict-types fallback, dynamic-name handling,
and literal-bound-argument eligibility, further show why optimization cannot
receive a blanket no-impact exclusion. New strict/non-strict PFA ordering
controls preserve the expected compiler error, but do not certify every path.

**Disposition:** verified contextual validation-order observation, unresolved
intended language boundary. No function-specific EBNF or unconditional unset-cast
rule is justified. Any eventual general prose must describe validation of the
AST actually visited at each compiler phase, with dispatch/folding boundaries;
it must not imply all calls traverse arguments alike. Gate B remains blocked
for that unverified generalization.

## RFC integration and additional findings

The refreshed index moves function autoloading to declined, case-sensitive PHP
to withdrawn, and type aliases, void-as-null, data classes, and static constructors
to inactive. These changes do not authorize source syntax. Existing PFA,
readonly-default, constant-object, Override, enum, metadata, and diagnostic
findings retain selected-pin evidence. Closure history confirms only stateless
caching was merged; automatic static inference remains excluded. The compiler
history also records the `__sleep`/`__wakeup` deprecation revert, and the new
`__sleep` declaration control emits no warning.

The omitted candidate **direction-aware property resolution** is now associated
with PHP86-012. The refreshed index lists it as implemented, while the page still
says Draft. [PR 22709](https://github.com/php/php-src/pull/22709) merged as
`94136cf9bfbab89e072e3f04992c1e16c3a981a1`; API comparison verifies that commit
as an ancestor of RC4. Selected source has a narrower design than the draft:
`zend_std_write_property` clears a denied-write cache, `ZEND_ASSIGN_OBJ` can
skip a repeated writable check, and `compact_literals.c` separates assignment
cache slots from other property operations. The draft's `write_access` argument
is absent. The original per-operation slow-path checks remain.

This is an implementation change without a demonstrated language implication.
Three exact asymmetric-visibility PHPTs constrain denied writes, scope rebinding,
and cache sharing. They were read, not run under opcache/JIT. No new production
or property-visibility rule is authorized. The ledger preserves 58 historical
records and stores this 59th distinct candidate in `phase3d.additionalCandidates`.
Optional-placeholder independent ancestry, stream-errors ancestry, and complete
component/follow-up/reversion review remain explicit limitations.

**No additional verified structural or lexical change is established.** New
evidence refines existing IDs 009–015; it does not justify another language-change
ID. This is a statement about the reviewed evidence, not absence of missing
changes in the remaining discovery universe.

## Implementation consistency and prioritized backlog

The Phase 3B/C production mappings, contextual clauses, and regression evidence
remain consistent with this review. No new evidence contradicts the bounded
PHP86-001–007 implementation. In particular, PHP86-004 excludes assignment-based
targets, PHP86-007 distinguishes discarded closures from dead statements, and
PHP86-006 permits `?array` despite a deprecation warning. The change document
links this review without changing those rules.

| Priority / finding | Mapping and required work | Gate B, evidence, tests, dependencies, and blocking impact |
|---|---|---|
| P0 PHP86-013 | Complete material compiler/AST/optimizer source association and relevant PHPT/RFC reconciliation; no speculative production | NOT READY for complete implementation. Preserve raw IDs; investigate reentrant interface/linking, constant traversal, and paths outside the eight-file ledger. Blocks integration and certification. |
| P1 PHP86-010 | Deprecated but accepted syntax; `identifier`, `function-declaration`, `class-constant-declaration`, `namespace-use-declaration`, `return-statement`, `yield-expression` | READY WITH BOUNDED LIMITATIONS for warning-only prose. Pinned warning sites and reviewed PHPTs support it. Add permanent accepted-with-warning, alias/method/underscore controls, case/namespace, discarded/live and stage-specific boundary tests with the edit. No reserved tokens or removed productions. Required before implementation completeness. |
| P1 PHP86-014 | `assignment-expression`, `variable-expression`, `array-object-dereferenceable`; writable-target prose | BLOCKED pending intended validity and source-corresponding execution. Test ordinary/reference/list bases, read/write/unset/inc/reference paths, live/discarded contexts and VM profiles. Does not block independent PHP86-010 work; blocks affected-area integration/certification claims. |
| P1 PHP86-015 / 011 | `function-call`, `cast-expression`, `conditional-expression`, `coalesce-expression`; ordinary/constant validation-order prose | BLOCKED pending intended general rule and optimized-call coverage. Preserve direct/imported/qualified/namespace/dynamic, AND/OR, conditional/coalesce, live/unreachable/discarded pairs. No builtin-specific EBNF. Blocks complete traversal claims and certification. |
| P2 PHP86-001–007 | Existing argument, property, attribute, enum and control-flow mappings | Existing bounded implementation retained. Parent-hook PFA fixtures, wider initializer combinations, reentrant linking and independent lexer/stage-aware boundary integration remain evidence obligations; assess new edits independently. No new contextual validator is authorized here. |
| P2 PHP86-008/009/012 | Types, parameters, excluded proposals and runtime-only optimization | No grammar edit currently needed. Preserve qualified-static negatives and historical provenance limits; metadata/runtime behavior stays separate from syntax. Exact-pin correspondence and remaining RFC reconciliation still limit certification. |

Language specification work is distinct from contextual-validator implementation.
The repository's structural matcher is not being expanded into a PHP compiler.

## Validation and preservation

PHPUnit prefix:
`rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result`.
All commands below ran in the repository root. No historical report was regenerated.

| Command | Actual result |
|---|---|
| `rtk .audit/php85/php.exe -n .audit/phase3d.php hashes` | PASS: 341 original source/archive/executable/absence checks |
| PHPUnit prefix + `tests/Ebnf` | PASS: 74 tests, 155 assertions |
| PHPUnit prefix + `tests/Php/Conformance/Php86PartialApplicationTest.php` | PASS: 61 tests, 151 assertions |
| PHPUnit prefix + `tests/Php/Conformance/Php86ContextualChangesTest.php` | PASS: 128 tests, 725 assertions |
| PHPUnit prefix + `tests/Repository` | 21 tests, 888 assertions; two known unregistered-package failures |
| PHPUnit prefix + `--filter 'Php85DocumentationParityTest\|Php85SpecificationStructureTest\|PhpConformanceFixtureTest\|Php85ContextualFixtureTest\|VersionBoundaryTest'` | 1,373 tests, 2,452 assertions; known empty-boundary assumption failure; PHP 8.5 corpus/documentation cases pass |
| `rtk .audit/php85/php.exe -n tools/validate-manifest.php` | PASS: schema 1.0 and package paths |
| `rtk .audit/php85/php.exe -n tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=.audit/pfa-phase3d.json` | PASS: 225 checks, 114 paired lint observations, zero failures |
| `rtk .audit/php85/php.exe -n tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=.audit/context-phase3d.json` | PASS: 278 checks, 126 paired stage observations, zero failures; includes parity |
| `rtk .audit/php85/php.exe -n .audit/probe-phase3d.php` | 60 sources: 33 lint-only, 19 declaration-link, eight controlled-runtime; 174 observations including lint before execution |
| `rtk .audit/php85/php.exe -n .audit/verify-phase3d.php` | PASS: 1,733 preservation hashes, only 11 authorized existing files changed; all historical fields in five JSON ledgers unchanged; 97 additional hashes/absence checks, unique IDs, review/probe totals and expectations, production anchors, 61 local links/anchors and whitespace |
| `rtk git diff --check` | PASS |

The three known failures are directory/manifest equality, documentation links to
an unregistered version, and the test assuming an empty boundary directory.
They remain failures; no release gate is waived. Research integrity, historical
field preservation, Markdown links/whitespace, and file preservation are checked
by the temporary PHP review driver recorded in the evidence ledger.

PHP 8.5 grammar, specification, fixtures, certification evidence, and tooling;
Phase 3B/C implementation and evidence; the manifest and release gates remain
unchanged from the 1,733-file starting snapshot. Phase 3D changes are research
records and an explanatory link in `grammar/8.6/changes.md`. Temporary PHP drivers
and downloaded snapshots remain under ignored `.audit/`. Git staging and
configuration are untouched.

## Next-phase readiness

- **Remaining grammar implementation: READY WITH BOUNDED LIMITATIONS** for
  PHP86-010 warning-only documentation under its stated tests and dependencies.
  Complete implementation remains **NOT READY**; PHP86-013/014/015 are open.
- **Manifest and tooling integration: NOT READY.** Discovery gaps, independent
  lexer/contextual-stage integration, and existing consistency failures remain.
- **PHP 8.6 release certification: NOT READY.** RC4 is prerelease evidence;
  exact-pin correspondence, unresolved findings, and full release validation remain.

No subsequent phase has begun.
