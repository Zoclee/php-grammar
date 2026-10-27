# PHP 8.6: Optimized printf changes compile-time acceptance of discarded expressions

## Summary

In PHP 8.6.0RC4, a direct `printf()` call rejects an unsupported cast in a
short-circuited expression, while the corresponding dynamic call compiles.
A ternary example shows the opposite version difference: RC4 accepts a direct
call that PHP 8.5.10 rejects. These are compile-time validation results.

## Minimal reproducer

Save each example separately and run `php -n -l example.php`.

Direct call:

```php
<?php
printf(true || (unset)$x);
```

Dynamic-call control:

```php
<?php
$f="printf"; $f(true || (unset)$x);
```

Ternary example:

```php
<?php
printf(false ? (unset)$x : "ok");
```

## Actual behaviour

| Example | PHP 8.5.10 lint | PHP 8.6.0RC4 lint |
|---|---|---|
| Direct short-circuit call | Exit 0 | Exit 255 |
| Dynamic short-circuit call | Exit 0 | Exit 0 |
| Direct ternary call | Exit 255 | Exit 0 |

Successful lint reports `No syntax errors detected in <file>`. Rejection reports
`Fatal error: The (unset) cast is no longer supported` on line 2, followed by a
main stack frame. The separate RC4 pre-optimizer opcode dump for the ternary
example contains `ECHO string("ok")`: the cast is eliminated during compilation.
No runtime execution is needed to demonstrate these differences, and lint is
not an isolated parser test.

Supporting controls show that call eligibility matters: qualified/imported
`printf` follows the direct logical case, whereas namespace fallback and dynamic
calls accept that case in both versions. Named format arguments, literal
unpacking, partial function application and constant-initializer combinations
also affect the observed traversal/acceptance patterns. These observations do
not establish a complete dispatch rule.

## Expected behaviour / clarification requested

**Is optimization-specific traversal intended to change compile-time source
validity, or should optimized and ordinary calls enforce the same validity rules?**

Which outcome is intended for the short-circuit and ternary examples above?
This concerns validation of discarded expressions during compilation, not
runtime evaluation of unreachable branches.

## Affected versions

Observed on Windows x64 with PHP 8.5.10 (distribution comparator) and PHP
8.6.0RC4 NTS VC2022, built from source commit
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. The 8.5 distribution is not attested
to an exact source commit. CLI OPcache and JIT were disabled for lint;
compiler built-in lowering still runs. The separate opcode dump used CLI
OPcache with optimization level 0 and JIT disabled.

## Relevant implementation

- [PR 19658](https://github.com/php/php-src/pull/19658) implements the related `printf` lowering.
- Pinned [`zend_compile_func_printf`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5264)
  and [`zend_compile_func_sprintf`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5096).
- Pinned [call eligibility](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L5525)
  and [`zend_eval_const_expr`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12920).
  Ordinary short-circuit compilation and constant-expression evaluation do not
  traverse all discarded forms in the same order.