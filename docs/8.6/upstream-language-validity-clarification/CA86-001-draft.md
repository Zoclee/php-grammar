# Must unknown property-hook names be rejected in discarded declarations?

Prepared October 26, 2026. **Unpublished; ready for user review.** This is a
language-validity question, not a proposed grammar correction or a claim of
official specification authority. Expected language behavior remains undecided.

## Affected versions and reproduction profile

Observed with PHP **8.5.10** (Windows distribution comparator, not an exact-source
attestation) and **8.6.0RC4 NTS VC2022 x64**, built from immutable PHP source
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. The exact RC4 executable SHA-256 is
`445d2bde0998cd54e04b4be4401ae45b5e54df4ef677294a64cbcd1faf6d9fe6`;
core DLL SHA-256 is
`1ffb38fee2a383dfc5095d4cad90c2309d90ec7285942f401707f3fe73caae3f`.
The 8.5 executable SHA-256 is
`32da1562c56dfe511a621627e3bbd9721058e855a6f14225c5803bc19ba623e9`.

The examples below are byte-identical retained fixtures, independently checked
against the authenticated Phase 7C.4 observations; they were not rerun in this
documentation phase. Save each fenced example separately. From the repository
root, use either executable with the recorded lint profile (PowerShell):

```powershell
& .audit/php85/php.exe -n -d zend.multibyte=0 -d short_open_tag=1 -d error_reporting=-1 -d memory_limit=64M -d max_execution_time=5 -d opcache.enable_cli=0 -d opcache.jit=0 -l example.php
& .audit/php86-exact/source/x64/Release/php.exe -n -d zend.multibyte=0 -d short_open_tag=1 -d error_reporting=-1 -d memory_limit=64M -d max_execution_time=5 -d opcache.enable_cli=0 -d opcache.jit=0 -l example.php
```

`example.php` is the selected fixture or saved example. Lint exit 0 means
compilation succeeded, with `No syntax errors detected in <file>`. Exit 255
means rejection; it is not by itself a pure-parser verdict. Exact original
command arrays, fixture hashes and full stdout/stderr are in the
[evidence index](reproducers.json), pointing into the unchanged
[full observations](../exact-pin-blocker-reconciliation/probes-verified.json).
CLI OPcache and JIT are disabled for lint; compiler built-in lowering still runs.
## Minimal reproducer and controls

### `hook-discarded`

```php
<?php
false && static function(){return new class {public int $x {unknown=>1;}};};
```

- PHP 8.5: lint exit 0; no runtime execution recorded.
- PHP 8.6: lint exit 0; no runtime execution recorded.

### `hook-retained`

```php
<?php
$f=static function(){return new class {public int $x {unknown=>1;}};};
```

- PHP 8.5: lint exit 255; no runtime execution recorded.
- PHP 8.6: lint exit 255; no runtime execution recorded.

### `hook-live`

```php
<?php
class C {public int $x {unknown=>1;}}
```

- PHP 8.5: lint exit 255; no runtime execution recorded.
- PHP 8.6: lint exit 255; no runtime execution recorded.

### `hook-constant-discarded`

```php
<?php
const F=true ? 1 : static function(){return new class {public int $x {unknown=>1;}};};
```

- PHP 8.5: lint exit 0; no runtime execution recorded.
- PHP 8.6: lint exit 0; no runtime execution recorded.

### `hook-get-repair`

```php
<?php
class C {public int $x {get=>1;}}
```

- PHP 8.5: lint exit 0; no runtime execution recorded.
- PHP 8.6: lint exit 0; no runtime execution recorded.

The live diagnostic is
`Fatal error: Unknown hook "unknown" for property C::$x, expected "get" or "set"`.
The retained anonymous-class diagnostic uses `class@anonymous::$x`. Both occur
on line 2 and exit 255. The ordinary discarded example's pre-optimizer dump
contains only `RETURN int(1)`; the initializer example is specifically a
discarded ternary arm, not evidence about every constant-expression path.

## Implementation and precise question
The pinned [property-hook parser productions](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_language_parser.y#L1160) accept `T_STRING`. [`zend_get_property_hook_kind_from_name`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L9348) recognizes get/set, and [hook compilation](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L9409) rejects unknown names when visited. Thus parser permissiveness, a visited declaration and a declaration eliminated before compilation must be distinguished. Lint is not an isolated parser test.

Should PHP reject an unknown property-hook name regardless of whether its
enclosing declaration survives compilation? Specifically, should both the
short-circuited closure and discarded ternary constant-initializer arm above
reject, or is their acceptance intended? If acceptance is intentional, what
precise elimination/validation boundary defines source validity?

A source-backed grammar restricted to get/set rejects these sources even when
the compiler accepts them. Broadening hook names everywhere would incorrectly
accept the live negative control. We need the intended language boundary to
decide whether this is a compiler validation omission, a contextual exception,
or an explicitly bounded grammar/conformance distinction. No interpretation is
assumed, and this repository is not an officially endorsed PHP specification.

Proposed location: a new focused php-src issue for property-hook/compiler
maintainers, after a duplicate check immediately before submission. The bounded
refresh found no exact issue deciding discarded unknown-hook validity. That is
not proof that no discussion exists. If an exact issue is identified, contribute
there instead. Uniform rejection may require an upstream check before elimination
with no hook-name EBNF change. Intentional acceptance requires a separately
authorized contextual/structural scope review, not a blanket unknown-name rule.

## Review boundary

The [October 26 upstream review](upstream-review.md) distinguishes unchanged
PR evidence from newly retrieved related material. No authoritative answer to
this exact question was found within that scope. The [response/action map](response-actions.json)
retains the original work item and separates future EBNF, contextual, fixture
and upstream actions. An unanswered or qualified response leaves the blocker
open. No issue has been posted and no maintainer has been contacted.
