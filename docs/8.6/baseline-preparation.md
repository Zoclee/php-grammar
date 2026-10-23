# PHP 8.6 Phase 3A protected baseline

Prepared October 23, 2026 under [Gate A](../versioning.md#31-readiness-gates).
**Unverified working baseline:** both [php.ebnf](../../grammar/8.6/php.ebnf)
and [php.md](../../grammar/8.6/php.md) are unchanged PHP 8.5 copies. Their PHP 8.5
labels describe their origin, not PHP 8.6 accuracy. No PHP 8.6 syntax, lexical,
or contextual implementation has begun. PHP 8.6 remains unregistered and
unsupported; these files are not a complete version package or conformance
evidence.

## Repository baseline and equality

Repository commit: `d4cd2c5446a9de7fa783533ef8d9e8f8f0fad3ee`.
The initial working tree was clean, with no unrelated changes. Both readable
source files matched the raw blobs at this commit, including their SHA-256
hashes. No destination directory existed; copying used exclusive file creation.

| File | SHA-256 |
|---|---|
| `grammar/8.5/php.ebnf` (original) | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| `grammar/8.6/php.ebnf` (copy) | `2acfcf904ba14cbdfab787854cee449205a805cdc8ac8c24bad1fd44983b4ceb` |
| `grammar/8.5/php.md` (original) | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |
| `grammar/8.6/php.md` (copy) | `bbb81cbda09da80a6e326c5153ccfb4bc436ba9e306a3e1fa419b1ff002f6d7f` |

Both pairs passed SHA-256 equality and strict full-byte comparison. No production,
heading, comment, version reference, formatting, or contextual restriction changed.
Gate A explicitly defers the three-file package requirement: `changes.md` will
be required for the later version package, as described in the
[implementation plan](implementation-plan.md). It is not created in this phase.

## Research and implementation boundaries

The selected research source is PHP 8.6 RC4, commit
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`; the PHP 8.5 source pin remains
`7a4c62795365ed6a97a0184c96375b9fb4d53b1e`. These upstream pins are distinct
from the repository copying baseline. See the existing
[risk assessment](discovery-risk-assessment.md) and
[language-change inventory](language-change-inventory.md) for source evidence
and dispositions, all preserved unchanged.

- PHP86-013 remains open: material source discovery and interaction review are
  incomplete.
- PHP86-014 remains open and blocks assignment-based writable-target changes.
- PHP86-015 remains open and blocks unverified optimized-call traversal rules.

All other finding classifications, source references, and outstanding obligations
remain unchanged. Gate B still requires finding-specific pinned authoritative
evidence, traceability, affected productions or lexical/contextual constructs,
positive/negative/stage-specific/version-boundary tests, and dependency and
interaction assessment before any edit. This baseline is available for later
Gate B-controlled work; copying does not establish any finding's Gate B readiness.
Discovery completion, complete implementation, and final certification remain
**NOT READY**. No next-phase implementation is performed here.

## Validation and preservation

Commands below ran from the repository root. PHPUnit used PHP 8.5.10 with `-n`
and `-d extension=.audit/php85/ext/php_mbstring.dll`; no exact-pin PHP 8.6
interpreter or differential conformance claim is involved.

| Command | Actual result |
|---|---|
| `rtk git rev-parse HEAD`; `rtk git status --short` | Baseline above; initially clean |
| `rtk php -n .audit/phase3a.php prepare` | Source bytes and hashes match committed blobs; snapshot of 1,590 tracked files |
| `rtk php -n .audit/phase3a.php copy` | Two exclusive, unchanged copies created |
| `rtk php -n .audit/phase3a.php verify` | Both hash/byte comparisons and copied Markdown EBNF parity pass; all 1,590 prior tracked files unchanged; local links/anchors and whitespace pass |
| `rtk .audit/php85/php.exe -n tools/validate-manifest.php` | PASS: schema 1.0 and package paths |
| `rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Repository` | 21 tests, 887 assertions, one expected directory/manifest consistency failure; remaining 20 tests pass |
| `rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Ebnf` | PASS: 74 tests, 155 assertions; includes direct parsing and reference/integrity validation of the unregistered 8.6 EBNF |
| `rtk .audit/php85/php.exe -n -d extension=.audit/php85/ext/php_mbstring.dll vendor/phpunit/phpunit/phpunit --do-not-cache-result --filter 'Php85DocumentationParityTest\|Php85SpecificationStructureTest'` | PASS: four tests, 65 assertions; copied-file parity also checked directly above |
| `rtk git diff --check` | PASS |

The repository suite's `testGrammarDirectoriesExactlyMatchManifestVersions`
requires every grammar directory to be registered. It reports the additional
`8.6` directory against the unchanged `8.5`-only manifest. This is an existing
tooling limitation for Gate A, not a passing repository-wide gate. No test,
manifest, discovery behavior, lexer configuration, or release gate was weakened
to accommodate the draft.

The preservation snapshot covers every pre-existing tracked file, including PHP
8.5 grammar, documentation, fixtures, certification evidence, validation tooling,
manifest, README, and all PHP 8.6 research. Historical certification hashes were
not refreshed. Final scope is exactly the two baseline copies and this record.
Temporary PHP verification code and the hash snapshot remain in ignored `.audit/`;
they add no tracked workflow or release gate. Git ownership restrictions required
read-only Git operations outside the restricted runner; Git configuration was
not changed.
