# PHP 8.6 Phase 3C contextual changes

October 23, 2026. **Provisional, unregistered specification; not certification.**
PHP86-003 through PHP86-007 independently meet Gate B for the bounded
specification and evidence scope below. No EBNF bytes, lexical rules, public
manifest, or contextual validator implementation changed. No subsequent phase
has begun.

## Gate B assessment

Repository baseline: `0730c0867f2de5f80b83d21bb3b3b2206e9ce483`; initial working
tree clean. Both upstream pins remain unchanged: `PHP-8.5` at
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`, and `PHP-8.6`, RC4 at
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`.
The [source lock](../../tools/8.6/context-source-lock.json) records both pins,
pre-edit grammar/specification hashes, eight implementation files on each side,
17 relevant RC4 PHPTs, executable distribution hashes, and production mappings.
The implementation hashes and executable hashes match the existing research
ledger. Upstream PHPTs were reviewed as source, not executed as an upstream suite.

| Finding | Independent evidence and decision | Scope and remaining boundary |
|---|---|---|
| PHP86-003 | PASS: `zend_compile_prop_decl` removes the readonly-default fatal while retaining type/static checks; readonly-class propagation and default validation remain. Upstream readonly-default and inheritance PHPTs corroborate it. | Typed nonstatic unhooked defaults, implicit readonly, inheritance and promotion distinctions. Reassignment remains restricted; arbitrary inheritance/runtime combinations are not certified. |
| PHP86-004 | PASS: `zend_can_write_to_variable` tracks `via_prop`; constant object fetch marking/compilation supports property paths through constants and dimensions. GH10497 PHPTs establish positive writes and dimension-only guardrails. | Property mutation/reference paths only. Neither constant reassignment nor dimension-only constant array writes are authorized. PHP86-014 remains separate; no assignment-expression-based target is added. |
| PHP86-005 | PASS: Override gains the class-constant target; compiler flags class constants and enum cases, and inheritance resolves matching nonprivate parent/interface constants or rejects unresolved flags. | Attributes remain structurally unchanged. Declaration-link tests cover matches, private/missing/final/type failures, and delayed parent declaration. Broader linking-order uncertainty remains PHP86-013. |
| PHP86-006 | PASS: enum verification removes only its `__debugInfo` prohibition; API magic-method validation remains. Pinned parameter/return/visibility PHPTs distinguish fatal errors from warnings. | Nonstatic, no-argument method; declared return type compatible with `?array`. Nonpublic visibility warns. Other forbidden enum magic methods, especially `__get`, remain prohibited. |
| PHP86-007 | PASS: reference return/yield compilation changes `zend_is_variable` to `zend_is_variable_or_call` and applies the nullsafe-chain assertion. GH20628 and paired controls corroborate the boundary. | Reference contexts and actual compiler traversal only. Ordinary value returns/yields and foreach controls remain accepted; discarded closure differs from an unreachable statement. No generalized optimization rule. |

Normative changes are in the standalone specification's Properties, Class
constants, Enums, Writable variables and destructuring, and Control-flow
constraints sections. The overview states their incomplete enforcement status.
The [ten-section changes document](../../grammar/8.6/changes.md) maps every
finding to source anchors, unchanged productions, and tests while retaining
PHP86-001's entries. All existing EBNF productions and its generated Markdown
block remain byte-identical to Phase 3B.

## Stage-specific evidence

The [case manifest](../../tests/fixtures/php/8.6/context-cases.json) contains
63 sources: 38 lint cases, 22 declaration-link cases, and three controlled-runtime
cases. Each has separate PHP 8.5/8.6 expectations and source/test evidence.
The [result ledger](context-validation.json) preserves 126 paired-version stage
observations with fixture hashes and diagnostics. Lint is also recorded for
every linking/runtime case; a successful lint does not substitute for its later
stage. The executed declaration fixtures contain only controlled declarations
and, in one delayed case, an explicit parent-declaration branch. Runtime witnesses
are limited to readonly reassignment, constant-object mutation, and scalar-as-object
failure; they do not exercise PHP86-014's unresolved opcode cases.
The scalar-as-object failure lives in a separate `runtime/` fixture directory:
it is an evaluation failure, not a source-syntax or contextual-compilation negative.

Notable boundaries:

- The enum `__debugInfo` lint control succeeds on both executables, whereas the
  corresponding declaration-link case fails on 8.5 and succeeds on RC4.
- Private `__debugInfo` is accepted with a public-visibility warning in RC4;
  argument/static/return-type violations remain fatal. Nullable-return
  deprecation is recorded as diagnostic behavior, not structural rejection.
- Constant-object foreach already passes 8.5 lint; it is a compatibility control,
  not evidence of a new lint boundary. The readonly-hook failure is specifically
  `Hooked properties cannot be readonly`, separate from the removed default ban.
- Nullsafe reference method returns/yields differ across versions. Existing
  property-reference negatives remain negative; value and foreach controls pass.
  Dead `if(false)` bodies/unreachable statements still trigger compilation checks,
  while a folded-away enclosing closure avoids them.

Seven [contextual boundary records](../../tests/fixtures/version-boundaries/contextual-8.6)
identify their required validation stage. Existing `VersionBoundaryCase` metadata
parsing is reused, but these are contextual boundaries: both EBNFs structurally
accept them. The public structural runner cannot enforce their stage expectations.
The focused PHPUnit test connects metadata, fixture bytes, paired stage results,
and structural acceptance without claiming contextual enforcement.

PHPUnit uses a test-local in-memory manifest and the unchanged repository 8.5
lexer, as in Phase 3B. No public package registration or new lexer configuration
is introduced. PHP 8.6 contextual enforcement and full integration remain deferred.

## Validation commands and results

PHPUnit prefix below is
`rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result`.
Interpreter distributions are PHP 8.5.10 and PHP 8.6.0RC4, with hashes checked
against the research ledger. Runs use `-n`, no opcache/JIT, byte-oriented source,
and the stage recorded per case. These distributions are not proven builds of
the exact source pins; no exact-executable or final-release certification follows.

| Command | Actual result |
|---|---|
| PHPUnit prefix + `tests/Php/Conformance/Php86ContextualChangesTest.php` | PASS: 128 tests, 725 assertions |
| PHPUnit prefix + `tests/Php/Conformance/Php86PartialApplicationTest.php` | PASS: 61 tests, 151 assertions |
| PHPUnit prefix + `tests/Ebnf` | PASS: 74 tests, 155 assertions; both grammars parse and pass structural/reference integrity |
| PHPUnit prefix + `tests/Repository` | 21 tests, 888 assertions; two existing registration-related failures |
| PHPUnit prefix + `--filter 'Php85DocumentationParityTest\|Php85SpecificationStructureTest\|PhpConformanceFixtureTest\|Php85ContextualFixtureTest\|VersionBoundaryTest'` | 1,373 tests, 2,452 assertions; one existing empty-boundary assumption failure; PHP 8.5 corpus and documentation cases pass |
| `rtk .audit/php85/php.exe -n tools/validate-manifest.php` | PASS: schema 1.0 and package paths |
| `rtk .audit/php85/php.exe -n tools/8.6/context-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources85=.audit/php-php-src-7a4c627 --sources86=.audit/php-php-src-f23b4c2 --report=docs/8.6/context-validation.json` | PASS: 278 checks, 126 paired-version stage observations, zero failures; includes source/executable hashes, mapped productions, finding/fixture traceability, unchanged EBNF and Markdown parity |
| `rtk .audit/php85/php.exe -n tools/8.6/pfa-check.php --php85=.audit/php85/php.exe --php86=.audit/php86/php.exe --sources=.audit/php-php-src-f23b4c2 --report=.audit/pfa-phase3c.json` | PASS: 225 checks, 114 paired lint observations, zero failures; historical PFA report untouched |
| `rtk php -n .audit/verify-phase3c.php` | PASS: 1,657 prior files, 78 changed/new files, 91 local links/anchors, JSON, and whitespace checked; only the two authorized existing documents differ |
| `rtk git diff --check` | PASS |

The repository failures are unchanged in kind: grammar directories must equal
manifest versions, documentation must reference registered versions, and the
historical boundary test assumes an empty repository directory. It now finds
the six PFA plus seven contextual boundary records. These are actual failures,
not waived release gates; no test has been changed to hide them. The full release
runner was not run, and whole-version conformance is not claimed.

## Preservation and outstanding obligations

The pre-edit snapshot covers 1,657 existing files. Only `grammar/8.6/php.md` and
`grammar/8.6/changes.md` are authorized to differ. PHP 8.5 grammar, specification,
fixtures, certification evidence and hashes, all pre-existing validation tooling,
the PFA implementation/fixtures/evidence, manifest, README, and research ledgers
remain unchanged. New files are limited to contextual fixtures, their test and
boundary metadata, source lock, focused checker, and this phase's report/results.
Preparation and preservation drivers stay in ignored `.audit/`. No staging or
Git configuration was changed.

PHP86-003 through PHP86-007 are complete within this specification and finite
evidence matrix. A new contextual validator, exhaustive inheritance/initializer
combinations, broader optimizer coverage, exact-pin interpreter provenance,
public registration and stage-aware boundary integration, and final-release
reconciliation remain outstanding. PHP86-013, PHP86-014, and PHP86-015 remain
open and retain their implementation boundaries. No earlier finding classification
or research evidence was rewritten.

Further independently verified changes may be assessed under Gate B. Complete
implementation, discovery closure, and final certification remain **NOT READY**.
This report does not start another phase.
