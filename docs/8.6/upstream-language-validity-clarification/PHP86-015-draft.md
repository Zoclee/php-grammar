# Should optimized printf compilation change discarded-cast source validity?

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

### `printf-or`

```php
<?php
printf(true || (unset)$x);
```

- PHP 8.5: lint exit 0; no runtime execution recorded.
- PHP 8.6: lint exit 255; no runtime execution recorded.

### `dynamic-or`

```php
<?php
$f="printf"; $f(true || (unset)$x);
```

- PHP 8.5: lint exit 0; no runtime execution recorded.
- PHP 8.6: lint exit 0; no runtime execution recorded.

### `printf-ternary`

```php
<?php
printf(false ? (unset)$x : "ok");
```

- PHP 8.5: lint exit 255; no runtime execution recorded.
- PHP 8.6: lint exit 0; no runtime execution recorded.

The rejecting cast diagnostic is `Fatal error: The (unset) cast is no longer
supported`, followed by file/line and a main stack frame. The ternary example
reverses the version difference: exact RC4 eliminates the cast and emits
`ECHO string("ok")` in the separate pre-optimizer dump. No runtime execution is
needed to demonstrate this compile-time acceptance difference.

## Implementation and precise question
The pinned [`zend_compile_func_printf`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5264), [`zend_compile_func_sprintf`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5096), [call eligibility](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5525) and [`zend_eval_const_expr`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12920) establish compiler lowering and constant-expression traversal. Ordinary short-circuit compilation and the constant evaluator do not visit the same discarded forms in the same order.

The retained 31-control traversal investigation further bounds the question:
fully qualified/imported printf follows the direct logical case; namespace
fallback and dynamic calls accept that logical case in both versions. A named
format argument and an additional `%s` format change eligibility. Literal unpack,
sprintf, coalesce and ternary have distinct acceptance patterns. PFA calls and
constant-initializer PFA calls also differ. These are finite observations, not a
complete dispatch rule; full sources and outcomes remain in the linked report.

Is optimization-specific traversal intended to alter **compile-time source
validity**, or should optimized and ordinary calls apply a common validity rule?
For the logical and ternary witnesses, which outcome is intended? Does that rule
extend to sprintf, qualified/imported/namespace/dynamic calls, named arguments,
unpack, partial application and constant initializers? If boundaries are
intentional, which source-validity rule specifies them?

This asks about validation of discarded ASTs, not evaluation of unreachable
runtime branches. Disabling OPcache does not disable this compiler lowering.
The separate opcode profile used CLI OPcache with optimization level 0 and JIT
disabled; it is not an optimized-runtime experiment.

Proposed location: a focused php-src issue referencing
[PR 19658](https://github.com/php/php-src/pull/19658), unless an exact existing
issue is identified before submission. The refreshed discussions cover printf
lowering, format handling and pass-one constant collection; they do not decide
discarded-cast source validity. No unconditional argument-traversal rule or
function-specific EBNF correction follows yet. An intended uniform rule may
require an upstream traversal correction; intentional differences require a
precisely bounded contextual specification. Either needs separate Gate B review.

## Review boundary

The [October 26 upstream review](upstream-review.md) distinguishes unchanged
PR evidence from newly retrieved related material. No authoritative answer to
this exact question was found within that scope. The [response/action map](response-actions.json)
retains the original work item and separates future EBNF, contextual, fixture
and upstream actions. An unanswered or qualified response leaves the blocker
open. No issue has been posted and no maintainer has been contacted.
