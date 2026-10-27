# PHP 8.6: Assignment-result writes compile but fail with invalid opcode

## Summary

PHP 8.6.0RC4 compiles an ordinary assignment-result dimension write but fails
when executing the emitted opcode. A reference-assignment control compiles and
executes successfully. PHP 8.5.10 rejects both writes during compilation.

## Minimal reproducer

Save as `example.php`:

```php
<?php
($a=[1])[0]=2; echo json_encode($a);
```

Run `php -n -l example.php`, then `php -n example.php` using PHP 8.6.0RC4.
The echo exposes the result in successful cases; the failing write never reaches it.

Reference-assignment control (save separately):

```php
<?php
$b=[1]; ($a=&$b)[0]=2; echo json_encode([$a,$b]);
```

## Actual behaviour

In RC4, both examples lint successfully (exit 0,
`No syntax errors detected in <file>`). The ordinary assignment then exits 255
with `Fatal error: Invalid opcode 23/2/1.` on line 2, followed by a main stack
frame. The reference control exits 0 and prints `[[2],[2]]`.

PHP 8.5.10 rejects both during lint (exit 255) with
`Fatal error: Cannot use temporary expression in write context` on line 2.

These additional operations also lint successfully in RC4 and fail lint in
8.5.10 with the same temporary-expression diagnostic. Each source below is a
separate file beginning with `<?php`. Runtime results are from RC4; invalid
opcode messages have the `Fatal error:` prefix and exit 255.

| Source after `<?php` | RC4 runtime result |
|---|---|
| `($a=new stdClass)->p=2; echo json_encode($a);` | `Invalid opcode 24/2/1.` |
| `$b=new stdClass; ($a=&$b)->p=2; echo json_encode([$a,$b]);` | Exit 0; `[{"p":2},{"p":2}]` |
| `$b=[1]; ([$a]=$b)[0]=2; echo json_encode([$a,$b]);` | `Invalid opcode 23/2/1.` |
| `([$a]=[1])[0]=2; echo json_encode($a);` | `Invalid opcode 23/1/1.` |
| `$b=[1]; ([&$a]=$b)[0]=2; echo json_encode([$a,$b]);` | Exit 0; `[2,[2]]` |
| `($a=[1])[0]+=2; echo json_encode($a);` | `Invalid opcode 27/2/1.` |
| `unset(($a=[1])[0]); echo json_encode($a);` | `Invalid opcode 75/2/1.` |
| `$r=&($a=[1])[0]; $r=2; echo json_encode([$a,$r]);` | `Invalid opcode 84/2/1.` |

Successful lint demonstrates compilation, not an isolated parser verdict.
The runtime failures concern generated operands; they do not establish that
these source forms should be structurally invalid.

## Expected behaviour / clarification requested

**Should assignment-derived dimension/property writes be rejected during
compilation, or should the compiler produce valid writable operands?**

If the answer differs between ordinary assignment, reference assignment and
destructuring results, which boundaries are intended? The successful reference
controls alone do not establish the intended validity of ordinary assignment writes.

## Affected versions

Observed on Windows x64 with PHP 8.5.10 (distribution comparator) and PHP
8.6.0RC4 NTS VC2022, built from source commit
`f23b4c222cebe33be93ee8ecaaa14390c5507a08`. CLI OPcache and JIT were disabled.
The 8.5 distribution is not attested to an exact source commit.

## Relevant implementation

- Related change: [PR 20628](https://github.com/php/php-src/pull/20628).
- Pinned [`zend_compile_var_inner`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_compile.c#L12774)
  accepts assignment ASTs. Ordinary assignment produces TMP, reference assignment
  VAR, and the literal-list case can supply CONST.
- Pinned [`ASSIGN_DIM` VM handlers](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2680)
  support VAR/CV write operands. This explains the failing operand path without
  deciding whether compilation should reject the source or emit supported operands.
- Related handlers: [`ASSIGN_OBJ`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L2489),
  [`ASSIGN_DIM_OP`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L1155),
  [`UNSET_DIM`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L6801)
  and [`FETCH_DIM_W`](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/zend_vm_def.h#L1971).
- Existing [read regression](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/gh20628_003.phpt)
  and [related regression](https://github.com/php/php-src/blob/f23b4c222cebe33be93ee8ecaaa14390c5507a08/Zend/tests/gh20628_005.phpt).
